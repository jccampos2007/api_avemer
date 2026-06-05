<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Models\AlumnoAuth;
use Firebase\JWT\JWT;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Psr\Http\Message\ResponseInterface as ResponseMessage;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    public function login(Request $request, ResponseMessage $response): ResponseMessage
    {
        $body = $request->getParsedBody();
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';

        if (empty($email) || empty($password)) {
            return Response::error($response, 'Email y contraseña son requeridos', 422, 'VALIDATION_ERROR');
        }

        $alumnoAuth = new AlumnoAuth();
        $user = $alumnoAuth->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return Response::error($response, 'Credenciales inválidas', 401, 'INVALID_CREDENTIALS');
        }

        $now = time();
        $secret = $_ENV['JWT_SECRET'] ?? '';
        $expiry = (int)($_ENV['JWT_EXPIRY'] ?? 900);
        $refreshExpiry = (int)($_ENV['JWT_REFRESH_EXPIRY'] ?? 604800);

        $tokenId = bin2hex(random_bytes(16));

        $accessToken = JWT::encode([
            'sub' => $user['alumno_id'],
            'jti' => $tokenId,
            'iat' => $now,
            'exp' => $now + $expiry,
        ], $secret, 'HS256');

        $refreshToken = JWT::encode([
            'sub' => $user['alumno_id'],
            'jti' => $tokenId,
            'iat' => $now,
            'exp' => $now + $refreshExpiry,
            'type' => 'refresh',
        ], $secret, 'HS256');

        $alumnoAuth->updateRememberToken($user['alumno_id'], $tokenId);

        $nombre = trim(
            ($user['primer_nombre'] ?? '') . ' ' . ($user['segundo_nombre'] ?? '')
        );
        $apellido = trim(
            ($user['primer_apellido'] ?? '') . ' ' . ($user['segundo_apellido'] ?? '')
        );

        return Response::json($response, [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $expiry,
            'user' => [
                'id' => $user['alumno_id'],
                'nombre' => $nombre,
                'apellido' => $apellido,
                'email' => $user['correo'],
                'ci_pasaporte' => $user['ci_pasaporte'],
            ],
        ]);
    }

    public function forgotPassword(Request $request, ResponseMessage $response): ResponseMessage
    {
        $body = $request->getParsedBody();
        $email = $body['email'] ?? '';

        if (empty($email)) {
            return Response::error($response, 'El correo es requerido', 422, 'VALIDATION_ERROR');
        }

        $alumnoAuth = new AlumnoAuth();
        $user = $alumnoAuth->findByEmail($email);

        if (!$user) {
            return Response::json($response, [], 200, 'Si el correo existe, recibirás un enlace de recuperación.');
        }

        $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);

        $alumnoAuth->updateResetToken($email, $otp, $expiresAt);

        try {
            $oldReporting = error_reporting();
            error_reporting($oldReporting & ~E_WARNING);

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = getenv('SMTP_HOST') ?: 'mail.privateemail.com';
            $mail->SMTPAuth = true;
            $mail->Username = getenv('SMTP_USER') ?: 'info@grupoavemer.net';
            $mail->Password = getenv('SMTP_PASS') ?: 'Grupo2026Avemer..';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            $fromEmail = getenv('SMTP_FROM_EMAIL') ?: 'info@grupoavemer.net';
            $fromName = getenv('SMTP_FROM_NAME') ?: 'AVEMER';
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($email);
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';

            $mail->isHTML(true);

            $mail->Subject = '=?UTF-8?B?' . base64_encode('Código de verificación - AVEMER') . '?=';
            $mail->Body = '
                <!DOCTYPE html>
                <html lang="es">
                <head><meta charset="UTF-8"></head>
                <body style="margin:0;padding:0;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 16px">
                <tr><td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="max-width:100%">
                <tr><td style="background:#1e3a5f;border-radius:16px 16px 0 0;padding:32px;text-align:center">
                    <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:700">AVEMER</h1>
                    <p style="margin:8px 0 0;color:#94a3b8;font-size:14px">Portal de Alumnos</p>
                </td></tr>
                <tr><td style="background:#ffffff;padding:32px;border-radius:0 0 16px 16px">
                    <h2 style="margin:0 0 16px;color:#1e293b;font-size:20px">Recuperación de contraseña</h2>
                    <p style="margin:0 0 20px;color:#64748b;font-size:15px;line-height:1.5">Recibimos una solicitud para restablecer tu contraseña. Ingresa el siguiente código en la aplicación:</p>
                    <div style="background:#f1f5f9;border:2px solid #cbd5e1;border-radius:12px;padding:24px;text-align:center;margin-bottom:20px;user-select:all;-webkit-user-select:all">
                        <p style="margin:0 0 12px;color:#64748b;font-size:13px;text-transform:uppercase;letter-spacing:1px">Código de verificación</p>
                        <p style="margin:0 0 12px;font-family:\'Courier New\',monospace;font-size:40px;font-weight:700;color:#1e3a5f;letter-spacing:8px">' . $otp . '</p>
                        <p style="margin:0;color:#94a3b8;font-size:12px">Selecciona el código para copiarlo</p>
                    </div>
                    <p style="margin:0;color:#94a3b8;font-size:13px">Este código expira en <strong>1 hora</strong>.</p>
                    <p style="margin:12px 0 0;color:#94a3b8;font-size:13px">Si no solicitaste este cambio, ignora este mensaje.</p>
                </td></tr>
                <tr><td style="padding:24px 0 0;text-align:center">
                    <p style="margin:0;color:#94a3b8;font-size:12px">AVEMER &copy; 2026 &bull; Grupo AVEMER</p>
                </td></tr>
                </table>
                </td></tr>
                </table>
                </body>
                </html>
            ';
            $mail->AltBody = 'Código de verificación - AVEMER' . "\r\n\r\n" . 'Tu código: ' . $otp . "\r\n\r\n" . 'Ingrésalo en la aplicación para restablecer tu contraseña.' . "\r\n\r\n" . 'Este código expira en 1 hora.';

            $mail->send();
            error_reporting($oldReporting);
        } catch (PHPMailerException $e) {
            error_reporting($oldReporting);
            error_log('Error al enviar email de recuperación: ' . $e->getMessage());
        }

        return Response::json($response, [], 200, 'Si el correo existe, recibirás un enlace de recuperación.');
    }

    public function resetPassword(Request $request, ResponseMessage $response): ResponseMessage
    {
        $body = $request->getParsedBody();
        $token = $body['token'] ?? '';
        $newPassword = $body['password'] ?? '';

        if (empty($token) || empty($newPassword)) {
            return Response::error($response, 'Token y nueva contraseña son requeridos', 422, 'VALIDATION_ERROR');
        }

        if (strlen($newPassword) < 8) {
            return Response::error($response, 'La contraseña debe tener al menos 8 caracteres', 422, 'VALIDATION_ERROR');
        }

        $alumnoAuth = new AlumnoAuth();
        $user = $alumnoAuth->findByResetToken($token);

        if (!$user) {
            return Response::error($response, 'Token inválido o expirado', 401, 'INVALID_TOKEN');
        }

        $alumnoAuth->updatePassword($user['alumno_id'], password_hash($newPassword, PASSWORD_DEFAULT));
        $alumnoAuth->clearResetToken($user['alumno_id']);

        return Response::json($response, [], 200, 'Contraseña actualizada exitosamente.');
    }

    public function refresh(Request $request, ResponseMessage $response): ResponseMessage
    {
        $body = $request->getParsedBody();
        $refreshToken = $body['refresh_token'] ?? '';

        if (empty($refreshToken)) {
            return Response::error($response, 'Refresh token requerido', 422, 'VALIDATION_ERROR');
        }

        try {
            $secret = $_ENV['JWT_SECRET'] ?? '';
            $decoded = JWT::decode($refreshToken, new \Firebase\JWT\Key($secret, 'HS256'));

            if (!isset($decoded->type) || $decoded->type !== 'refresh') {
                return Response::error($response, 'Tipo de token inválido', 401, 'INVALID_TOKEN_TYPE');
            }

            $alumnoAuth = new AlumnoAuth();
            $storedToken = $alumnoAuth->findByAlumnoId($decoded->sub);

            if (!$storedToken || $storedToken['remember_token'] !== $decoded->jti) {
                return Response::error($response, 'Token revocado', 401, 'TOKEN_REVOKED');
            }

            $now = time();
            $expiry = (int)($_ENV['JWT_EXPIRY'] ?? 900);
            $newTokenId = bin2hex(random_bytes(16));

            $newAccessToken = JWT::encode([
                'sub' => $decoded->sub,
                'jti' => $newTokenId,
                'iat' => $now,
                'exp' => $now + $expiry,
            ], $secret, 'HS256');

            $alumnoAuth->updateRememberToken($decoded->sub, $newTokenId);

            return Response::json($response, [
                'access_token' => $newAccessToken,
                'expires_in' => $expiry,
            ]);
        } catch (\Exception $e) {
            return Response::error($response, 'Refresh token inválido o expirado', 401, 'INVALID_TOKEN');
        }
    }

    public function verifyOtp(Request $request, ResponseMessage $response): ResponseMessage
    {
        $body = $request->getParsedBody();
        $token = $body['token'] ?? '';

        if (empty($token)) {
            return Response::error($response, 'El código es requerido', 422, 'VALIDATION_ERROR');
        }

        $alumnoAuth = new AlumnoAuth();
        $user = $alumnoAuth->findByResetToken($token);

        if (!$user) {
            return Response::error($response, 'Código inválido o expirado', 401, 'INVALID_TOKEN');
        }

        return Response::json($response, ['email' => $user['correo']], 200, 'Código válido.');
    }
}
