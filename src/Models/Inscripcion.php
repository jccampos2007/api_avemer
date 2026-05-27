<?php

namespace App\Models;

class Inscripcion
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function getByAlumnoId(int $alumnoId): array
    {
        $sql = "
            SELECT 'curso' AS tipo, ic.id AS inscripcion_id,
                   CONCAT(ca.numero, ' - ', c.nombre) AS titulo,
                   ic.fecha,
                   COALESCE(ic.estatus_inscripcion_id, 0) AS estatus_id,
                   ei.nombre AS estatus
            FROM inscripcion_curso ic
            INNER JOIN curso_abierto ca ON ca.id = ic.curso_abierto_id
            INNER JOIN curso c ON c.id = ca.curso_id
            LEFT JOIN estatus_inscripcion ei ON ei.id = ic.estatus_inscripcion_id
            WHERE ic.alumno_id = :id1

            UNION ALL

            SELECT 'diplomado' AS tipo, idp.id AS inscripcion_id,
                   CONCAT(da.numero, ' - ', d.nombre) AS titulo,
                   idp.fecha,
                   COALESCE(idp.estatus_inscripcion_id, 0) AS estatus_id,
                   ei.nombre AS estatus
            FROM inscripcion_diplomado idp
            INNER JOIN diplomado_abierto da ON da.id = idp.diplomado_abierto_id
            INNER JOIN diplomado d ON d.id = da.diplomado_id
            LEFT JOIN estatus_inscripcion ei ON ei.id = idp.estatus_inscripcion_id
            WHERE idp.alumno_id = :id2

            UNION ALL

            SELECT 'maestria' AS tipo, im.id AS inscripcion_id,
                   CONCAT(ma.numero, ' - ', m.nombre) AS titulo,
                   im.fecha,
                   COALESCE(im.estatus_inscripcion_id, 0) AS estatus_id,
                   ei.nombre AS estatus
            FROM inscripcion_maestria im
            INNER JOIN maestria_abierto ma ON ma.id = im.maestria_abierto_id
            INNER JOIN maestria m ON m.id = ma.maestria_id
            LEFT JOIN estatus_inscripcion ei ON ei.id = im.estatus_inscripcion_id
            WHERE im.alumno_id = :id3

            UNION ALL

            SELECT 'evento' AS tipo, ie.id AS inscripcion_id,
                   CONCAT(ea.numero, ' - ', e.nombre) AS titulo,
                   ie.fecha,
                   COALESCE(ie.estatus_inscripcion_id, 0) AS estatus_id,
                   ei.nombre AS estatus
            FROM inscripcion_evento ie
            INNER JOIN evento_abierto ea ON ea.id = ie.evento_abierto_id
            INNER JOIN evento e ON e.id = ea.evento_id
            LEFT JOIN estatus_inscripcion ei ON ei.id = ie.estatus_inscripcion_id
            WHERE ie.alumno_id = :id4

            ORDER BY fecha DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id1' => $alumnoId,
            ':id2' => $alumnoId,
            ':id3' => $alumnoId,
            ':id4' => $alumnoId,
        ]);
        return $stmt->fetchAll();
    }
}
