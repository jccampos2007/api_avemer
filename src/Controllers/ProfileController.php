<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Models\Alumno;
use App\Models\AlumnoAuth;
use Psr\Http\Message\ResponseInterface as ResponseMessage;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProfileController
{
    public function get(Request $request, ResponseMessage $response): ResponseMessage
    {
        $alumnoId = $request->getAttribute('alumno_id');

        $alumno = new Alumno();
        $profile = $alumno->findById($alumnoId);

        if (!$profile) {
            return Response::error($response, 'Alumno no encontrado', 404, 'NOT_FOUND');
        }

        $profile['nombre_completo'] = trim(
            ($profile['primer_nombre'] ?? '') . ' ' .
            ($profile['segundo_nombre'] ?? '') . ' ' .
            ($profile['primer_apellido'] ?? '') . ' ' .
            ($profile['segundo_apellido'] ?? '')
        );

        $fotoData = $alumno->getFoto($alumnoId);
        if ($fotoData) {
            $profile['foto_base64'] = base64_encode($fotoData['foto']);
        }

        return Response::json($response, $profile);
    }

    public function update(Request $request, ResponseMessage $response): ResponseMessage
    {
        $alumnoId = $request->getAttribute('alumno_id');
        $body = $request->getParsedBody();

        $allowedFields = ['correo', 'tlf_celular', 'tlf_habitacion', 'tlf_trabajo', 'direccion'];
        $data = array_intersect_key($body, array_flip($allowedFields));
        $data = array_filter($data, fn($v) => $v !== null && $v !== '');

        if (empty($data)) {
            return Response::error($response, 'No hay campos válidos para actualizar', 422, 'VALIDATION_ERROR');
        }

        if (isset($data['correo'])) {
            $data['correo'] = filter_var($data['correo'], FILTER_VALIDATE_EMAIL);
            if (!$data['correo']) {
                return Response::error($response, 'Email inválido', 422, 'VALIDATION_ERROR');
            }
        }

        $alumno = new Alumno();
        $alumno->update($alumnoId, $data);

        $profile = $alumno->findById($alumnoId);

        return Response::json($response, $profile, 200, 'Perfil actualizado');
    }

    public function uploadPhoto(Request $request, ResponseMessage $response): ResponseMessage
    {
        $alumnoId = $request->getAttribute('alumno_id');
        $uploadedFiles = $request->getUploadedFiles();

        if (!isset($uploadedFiles['foto'])) {
            return Response::error($response, 'Archivo de foto requerido', 422, 'VALIDATION_ERROR');
        }

        $foto = $uploadedFiles['foto'];

        if ($foto->getError() !== UPLOAD_ERR_OK) {
            return Response::error($response, 'Error al subir el archivo', 400, 'UPLOAD_ERROR');
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $mimeType = $foto->getClientMediaType();

        if (!in_array($mimeType, $allowedTypes)) {
            return Response::error($response, 'Tipo de imagen no permitido. Use JPG, PNG o WebP', 422, 'VALIDATION_ERROR');
        }

        $blob = $foto->getStream()->getContents();

        if (strlen($blob) > 2 * 1024 * 1024) {
            return Response::error($response, 'La imagen no debe exceder 2MB', 413, 'FILE_TOO_LARGE');
        }

        $alumno = new Alumno();
        $alumno->updateFoto($alumnoId, $blob);

        return Response::json($response, [
            'foto' => base64_encode($blob),
        ], 200, 'Foto actualizada');
    }

    public function getPhoto(Request $request, ResponseMessage $response): ResponseMessage
    {
        $alumnoId = $request->getAttribute('alumno_id');

        $alumno = new Alumno();
        $fotoData = $alumno->getFoto($alumnoId);

        if (!$fotoData) {
            return Response::error($response, 'Foto no encontrada', 404, 'NOT_FOUND');
        }

        $response->getBody()->write($fotoData['foto']);
        return $response
            ->withHeader('Content-Type', 'image/jpeg')
            ->withHeader('Content-Length', strlen($fotoData['foto']))
            ->withHeader('Cache-Control', 'private, max-age=3600');
    }

    public function changePassword(Request $request, ResponseMessage $response): ResponseMessage
    {
        $alumnoId = $request->getAttribute('alumno_id');
        $body = $request->getParsedBody();

        $currentPassword = $body['current_password'] ?? '';
        $newPassword = $body['new_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword)) {
            return Response::error($response, 'Contraseña actual y nueva son requeridas', 422, 'VALIDATION_ERROR');
        }

        if (strlen($newPassword) < 8) {
            return Response::error($response, 'La nueva contraseña debe tener al menos 8 caracteres', 422, 'VALIDATION_ERROR');
        }

        $alumnoAuth = new AlumnoAuth();
        $user = $alumnoAuth->findByAlumnoId($alumnoId);

        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            return Response::error($response, 'Contraseña actual incorrecta', 401, 'INVALID_PASSWORD');
        }

        $alumnoAuth->updatePassword($alumnoId, password_hash($newPassword, PASSWORD_DEFAULT));

        return Response::json($response, [], 200, 'Contraseña actualizada');
    }
}
