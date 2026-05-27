<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Models\Transaccion;
use Psr\Http\Message\ResponseInterface as ResponseMessage;
use Psr\Http\Message\ServerRequestInterface as Request;

class PaymentController
{
    private \PDO $pdo;

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

        $required = ['cuota_id', 'monto', 'numero_control', 'banco_id', 'forma_pago_id'];
        $missing = [];
        foreach ($required as $field) {
            if (empty($body[$field] ?? '')) {
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

        $checkCuota = $this->pdo->prepare("SELECT id, nombre FROM cuota WHERE id = :id");
        $checkCuota->execute([':id' => $body['cuota_id']]);
        if (!$checkCuota->fetch()) {
            return Response::error($response, 'Cuota no encontrada', 404, 'NOT_FOUND');
        }

        $checkBanco = $this->pdo->prepare("SELECT id FROM banco WHERE id = :id");
        $checkBanco->execute([':id' => $body['banco_id']]);
        if (!$checkBanco->fetch()) {
            return Response::error($response, 'Banco no válido', 422, 'VALIDATION_ERROR');
        }

        $checkForma = $this->pdo->prepare("SELECT id FROM forma_pago WHERE id = :id");
        $checkForma->execute([':id' => $body['forma_pago_id']]);
        if (!$checkForma->fetch()) {
            return Response::error($response, 'Forma de pago no válida', 422, 'VALIDATION_ERROR');
        }

        $fecha = $body['fecha'] ?? date('Y-m-d');

        $stmt = $this->pdo->prepare("
            INSERT INTO pago
                (cuota_id, alumno_id, monto, numero_control, banco_id, forma_pago_id,
                 fecha, oferta_academica_id, tipo_oferta_academica_id,
                 estatus_pago_id, diplomado_control_id)
            VALUES
                (:cuota_id, :alumno_id, :monto, :numero_control, :banco_id, :forma_pago_id,
                 :fecha, 0, 0, 1, 1)
        ");

        $stmt->execute([
            ':cuota_id' => (int)$body['cuota_id'],
            ':alumno_id' => $alumnoId,
            ':monto' => (float)$body['monto'],
            ':numero_control' => $body['numero_control'],
            ':banco_id' => (int)$body['banco_id'],
            ':forma_pago_id' => (int)$body['forma_pago_id'],
            ':fecha' => $fecha,
        ]);

        $pagoId = (int)$this->pdo->lastInsertId();

        return Response::json($response, [
            'pago_id' => $pagoId,
            'estatus_pago' => 'Pendiente',
        ], 201, 'Pago reportado exitosamente. Pendiente de validación.');
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
