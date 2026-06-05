<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Models\Transaccion;
use Psr\Http\Message\ResponseInterface as ResponseMessage;
use Psr\Http\Message\ServerRequestInterface as Request;

class PaymentController
{
    private \PDO $pdo;
    private const VOUCHER_DIR = '/uploads/pagos/vouchers/';
    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp'];
    private const MAX_VOUCHER_SIZE = 2 * 1024 * 1024;

    public function __construct()
    {
        $this->pdo = \App\Models\Database::getInstance()->getConnection();
    }

    public function getDebts(Request $request, ResponseMessage $response): ResponseMessage
    {
        $alumnoId = $request->getAttribute('alumno_id');

        $transaccion = new Transaccion();
        $debts = $transaccion->getDebtsByAlumnoId($alumnoId);
        $totalDebt = $transaccion->getTotalDebt($alumnoId);

        return Response::json($response, [
            'total_deuda' => $totalDebt,
            'deudas' => $debts,
        ]);
    }

    public function getPayments(Request $request, ResponseMessage $response): ResponseMessage
    {
        $alumnoId = $request->getAttribute('alumno_id');

        $transaccion = new Transaccion();
        $payments = $transaccion->getPaymentsByAlumnoId($alumnoId);

        $params = $request->getQueryParams();
        $page = max(1, (int)($params['page'] ?? 1));
        $limit = min(50, max(1, (int)($params['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $total = count($payments);
        $paged = array_slice($payments, $offset, $limit);

        return Response::json($response, $paged, 200, null, [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
        ]);
    }

    public function reportPayment(Request $request, ResponseMessage $response): ResponseMessage
    {
        $alumnoId = $request->getAttribute('alumno_id');
        $body = $request->getParsedBody();

        $formaPagoId = (int)($body['forma_pago_id'] ?? 0);
        $noRequiereBancoRef = in_array($formaPagoId, [4, 6]);

        $required = ['cuota_id', 'monto', 'forma_pago_id'];
        if (!$noRequiereBancoRef) {
            $required[] = 'numero_control';
            $required[] = 'banco_id';
        }
        $missing = [];
        foreach ($required as $field) {
            $val = $body[$field] ?? '';
            if (is_string($val)) $val = trim($val);
            if ($val === '' || $val === null) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            return Response::error($response,
                'Campos requeridos faltantes: ' . implode(', ', $missing),
                422, 'VALIDATION_ERROR');
        }

        if ((float)$body['monto'] <= 0) {
            return Response::error($response, 'Monto debe ser positivo', 422, 'VALIDATION_ERROR');
        }

        $cuotaStmt = $this->pdo->prepare("SELECT id, nombre, monto FROM cuota WHERE id = :id");
        $cuotaStmt->execute([':id' => $body['cuota_id']]);
        $cuota = $cuotaStmt->fetch();
        if (!$cuota) {
            return Response::error($response, 'Cuota no encontrada', 404, 'NOT_FOUND');
        }
        $montoCuota = (float)$cuota['monto'];

        $pagadoStmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(monto), 0) FROM pago
             WHERE cuota_id = :cuota_id AND alumno_id = :alumno_id AND estatus_pago_id IN (1, 2)"
        );
        $pagadoStmt->execute([':cuota_id' => $body['cuota_id'], ':alumno_id' => $alumnoId]);
        $totalPagado = (float)$pagadoStmt->fetchColumn();

        $nuevoMonto = (float)$body['monto'];
        if ($totalPagado + $nuevoMonto > $montoCuota) {
            $disponible = max(0, $montoCuota - $totalPagado);
            return Response::error($response,
                "El monto excede el saldo disponible. La cuota es \${$montoCuota}, ya has reportado \${$totalPagado}. Puedes reportar hasta \${$disponible}.",
                422, 'EXCEDE_SALDO');
        }

        if (!$noRequiereBancoRef && !empty($body['banco_id'])) {
            $checkBanco = $this->pdo->prepare("SELECT id FROM banco WHERE id = :id");
            $checkBanco->execute([':id' => $body['banco_id']]);
            if (!$checkBanco->fetch()) {
                return Response::error($response, 'Banco no válido', 422, 'VALIDATION_ERROR');
            }
        }

        $checkForma = $this->pdo->prepare("SELECT id FROM forma_pago WHERE id = :id");
        $checkForma->execute([':id' => $formaPagoId]);
        if (!$checkForma->fetch()) {
            return Response::error($response, 'Forma de pago no válida', 422, 'VALIDATION_ERROR');
        }

        $fecha = $body['fecha'] ?? date('Y-m-d');

        $voucherPath = null;
        $uploadedFiles = $request->getUploadedFiles();
        if (isset($uploadedFiles['voucher']) && $uploadedFiles['voucher']->getError() === UPLOAD_ERR_OK) {
            $file = $uploadedFiles['voucher'];

            if ($file->getSize() > self::MAX_VOUCHER_SIZE) {
                return Response::error($response, 'La imagen del voucher no debe exceder 2MB', 422, 'VALIDATION_ERROR');
            }

            $tmpStream = $file->getStream();
            $tmpContents = $tmpStream->__toString();
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_buffer($finfo, $tmpContents);
            finfo_close($finfo);
            $tmpStream->rewind();

            if (!in_array($mime, self::ALLOWED_MIME)) {
                return Response::error($response, 'El voucher debe ser una imagen JPG, PNG o WebP', 422, 'VALIDATION_ERROR');
            }

            $ext = match ($mime) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'jpg',
            };
            $voucherName = 'pago_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $voucherDir = __DIR__ . '/../../public' . self::VOUCHER_DIR;
            if (!is_dir($voucherDir)) {
                mkdir($voucherDir, 0755, true);
            }
            $file->moveTo($voucherDir . $voucherName);
            $voucherPath = self::VOUCHER_DIR . $voucherName;
        }

        // Start Transaction to ensure consistency between 'pago' and 'transaccion'
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO pago
                    (cuota_id, alumno_id, monto, numero_control, banco_id, forma_pago_id,
                     fecha, estatus_pago_id, voucher)
                VALUES
                    (:cuota_id, :alumno_id, :monto, :numero_control, :banco_id, :forma_pago_id,
                     :fecha, 1, :voucher)
            ");

            $stmt->execute([
                ':cuota_id' => (int)$body['cuota_id'],
                ':alumno_id' => $alumnoId,
                ':monto' => (float)$body['monto'],
                ':numero_control' => !empty($body['numero_control']) ? $body['numero_control'] : null,
                ':banco_id' => !empty($body['banco_id']) ? (int)$body['banco_id'] : null,
                ':forma_pago_id' => $formaPagoId,
                ':fecha' => $fecha,
                ':voucher' => $voucherPath,
            ]);

            $pagoId = (int)$this->pdo->lastInsertId();

            // Insert matching Transaction Record (tipo: 2 [Credito], estatus: 2 [Pago registrado])
            $transaccion = new Transaccion();
            $transaccion->create([
                'alumno_id' => $alumnoId,
                'cuota_id' => (int)$body['cuota_id'],
                'fecha_pago' => $fecha,
                'tipo' => 2, // Credito
                'monto' => (float)$body['monto'],
                'estatus' => 2, // Pago registrado
                'id_transaccion_origen' => $pagoId,
            ]);

            $this->pdo->commit();

            return Response::json($response, [
                'pago_id' => $pagoId,
                'estatus_pago' => 'Pendiente',
            ], 201, 'Pago reportado exitosamente. Pendiente de validación.');

        } catch (\Exception $e) {
            $this->pdo->rollBack();
            error_log('Error en reportPayment (transacción revertida): ' . $e->getMessage());
            return Response::error($response, 'Error de base de datos al reportar el pago: ' . $e->getMessage(), 500, 'DATABASE_ERROR');
        }
    }

    public function getBancos(Request $request, ResponseMessage $response): ResponseMessage
    {
        $stmt = $this->pdo->query("SELECT id, nombre FROM banco ORDER BY nombre");
        return Response::json($response, $stmt->fetchAll());
    }

    public function getFormasPago(Request $request, ResponseMessage $response): ResponseMessage
    {
        $stmt = $this->pdo->query("SELECT id, nombre FROM forma_pago ORDER BY nombre");
        return Response::json($response, $stmt->fetchAll());
    }
}