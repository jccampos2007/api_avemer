<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Models\Inscripcion;
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
}
