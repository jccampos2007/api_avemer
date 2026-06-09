<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Models\Inscripcion;
use App\Models\AlumnoAuth;
use Psr\Http\Message\ResponseInterface as ResponseMessage;
use Psr\Http\Message\ServerRequestInterface as Request;

class EnrollmentController
{
    public function list(Request $request, ResponseMessage $response): ResponseMessage
    {
        $alumnoId = $request->getAttribute('alumno_id');

        $inscripcion = new Inscripcion();
        $enrollments = $inscripcion->getByAlumnoId($alumnoId);

        $params = $request->getQueryParams();
        $page = max(1, (int)($params['page'] ?? 1));
        $limit = min(50, max(1, (int)($params['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $total = count($enrollments);
        $paged = array_slice($enrollments, $offset, $limit);

        return Response::json($response, $paged, 200, null, [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
        ]);
    }

    public function preRegister(Request $request, ResponseMessage $response): ResponseMessage
    {
        $alumnoId = $request->getAttribute('alumno_id');
        $body = $request->getParsedBody();

        $tipo = $body['tipo'] ?? '';
        $abiertoId = (int)($body['abierto_id'] ?? 0);

        $tiposValidos = ['curso', 'diplomado', 'maestria', 'evento'];
        if (!in_array($tipo, $tiposValidos) || $abiertoId <= 0) {
            return Response::error($response, 'Tipo de oferta o ID inválido.', 400);
        }

        $inscripcion = new Inscripcion();
        $result = $inscripcion->preRegister($alumnoId, $tipo, $abiertoId);

        if ($result['success']) {
            $this->sendNotificationEmail($alumnoId, $tipo, $abiertoId);
        }

        return $result['success']
            ? Response::json($response, null, 201, 'Pre-inscripción realizada con éxito.')
            : Response::error($response, $result['message'], 409);
    }

    private function sendNotificationEmail(int $alumnoId, string $tipo, int $abiertoId): void
    {
        try {
            $pdo = \App\Models\Database::getInstance()->getConnection();

            $alumnoAuth = new AlumnoAuth();
            $alumno = $alumnoAuth->findByAlumnoId($alumnoId);
            if (!$alumno) return;

            $offerInfo = $this->getOfferInfo($pdo, $tipo, $abiertoId);
            if (!$offerInfo) return;

            $alumnoName = htmlspecialchars(($alumno['primer_nombre'] ?? '') . ' ' . ($alumno['primer_apellido'] ?? ''));
            $ci = htmlspecialchars($alumno['ci_pasaporte'] ?? '');
            $correo = htmlspecialchars($alumno['correo'] ?? '');
            $tlf = htmlspecialchars($alumno['tlf_celular'] ?? '');

            $tipoLabel = match ($tipo) {
                'curso' => 'Curso / Taller',
                'diplomado' => 'Diplomado',
                'maestria' => 'Maestría',
                'evento' => 'Evento',
                default => $tipo,
            };

            $numero = htmlspecialchars($offerInfo['numero'] ?? '');
            $nombre = htmlspecialchars($offerInfo['nombre'] ?? '');
            $sede = htmlspecialchars($offerInfo['sede_nombre'] ?? '');

            $emailBody = '
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Nueva Preinscripción</title></head>
<body style="font-family:Arial,sans-serif;background:#f4f4f4;padding:20px">
<div style="max-width:600px;margin:auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1)">
<div style="background:#1e3a5f;color:#fff;padding:20px;text-align:center">
<h2 style="margin:0">Nueva Preinscripción</h2>
</div>
<div style="padding:20px">
<table style="width:100%;border-collapse:collapse">
<tr><td style="padding:8px;font-weight:bold;border-bottom:1px solid #eee">Estudiante</td><td style="padding:8px;border-bottom:1px solid #eee">' . $alumnoName . '</td></tr>
<tr><td style="padding:8px;font-weight:bold;border-bottom:1px solid #eee">C.I / Pasaporte</td><td style="padding:8px;border-bottom:1px solid #eee">' . $ci . '</td></tr>
<tr><td style="padding:8px;font-weight:bold;border-bottom:1px solid #eee">Correo</td><td style="padding:8px;border-bottom:1px solid #eee">' . $correo . '</td></tr>
<tr><td style="padding:8px;font-weight:bold;border-bottom:1px solid #eee">Teléfono</td><td style="padding:8px;border-bottom:1px solid #eee">' . $tlf . '</td></tr>
<tr><td style="padding:8px;font-weight:bold;border-bottom:1px solid #eee">Tipo Programa</td><td style="padding:8px;border-bottom:1px solid #eee">' . $tipoLabel . '</td></tr>
<tr><td style="padding:8px;font-weight:bold;border-bottom:1px solid #eee">Código</td><td style="padding:8px;border-bottom:1px solid #eee">' . $numero . '</td></tr>
<tr><td style="padding:8px;font-weight:bold;border-bottom:1px solid #eee">Programa</td><td style="padding:8px;border-bottom:1px solid #eee">' . $nombre . '</td></tr>
<tr><td style="padding:8px;font-weight:bold;border-bottom:1px solid #eee">Sede</td><td style="padding:8px;border-bottom:1px solid #eee">' . $sede . '</td></tr>
</table>
</div>
</div>
</body>
</html>';

            $subject = "Nueva preinscripción de {$alumnoName} en {$tipoLabel}";
            $this->sendEmail($subject, $emailBody, 'info@grupoavemer.net');
        } catch (\Exception $e) {
            error_log('Error al enviar email de preinscripcion: ' . $e->getMessage());
        }
    }

    private function getOfferInfo(\PDO $pdo, string $tipo, int $abiertoId): ?array
    {
        $queries = [
            'curso' => "SELECT ca.numero, c.nombre, s.nombre AS sede_nombre
                        FROM curso_abierto ca
                        INNER JOIN curso c ON ca.curso_id = c.id
                        LEFT JOIN sede s ON ca.sede_id = s.id
                        WHERE ca.id = ?",
            'diplomado' => "SELECT da.numero, d.nombre, s.nombre AS sede_nombre
                           FROM diplomado_abierto da
                           LEFT JOIN diplomado d ON da.diplomado_id = d.id
                           LEFT JOIN sede s ON da.sede_id = s.id
                           WHERE da.id = ?",
            'maestria' => "SELECT ma.numero, m.nombre, s.nombre AS sede_nombre
                          FROM maestria_abierto ma
                          INNER JOIN maestria m ON ma.maestria_id = m.id
                          LEFT JOIN sede s ON ma.sede_id = s.id
                          WHERE ma.id = ?",
            'evento' => "SELECT ea.numero, e.nombre, s.nombre AS sede_nombre
                        FROM evento_abierto ea
                        INNER JOIN evento e ON ea.evento_id = e.id
                        LEFT JOIN sede s ON ea.sede_id = s.id
                        WHERE ea.id = ?",
        ];

        $sql = $queries[$tipo] ?? null;
        if (!$sql) return null;

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$abiertoId]);
        return $stmt->fetch() ?: null;
    }

    private function sendEmail(string $subject, string $body, string $to): void
    {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'] ?? 'mail.privateemail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USER'] ?? 'info@grupoavemer.net';
        $mail->Password = $_ENV['SMTP_PASS'] ?? 'Avemer*g2026';
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->setFrom('info@grupoavemer.net', 'Grupo Avemer');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = mb_encode_mimeheader($subject, 'UTF-8', 'B');
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);
        $mail->send();
    }
}
