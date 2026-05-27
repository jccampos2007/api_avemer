<?php

namespace App\Models;

class Oferta
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function getAbiertos(): array
    {
        $sql = "
            SELECT 'curso' AS tipo, c.id,
                   CONCAT(ca.numero, ' - ', c.nombre) AS titulo,
                   c.nombre,
                   c.horas AS duracion,
                   'horas' AS duracion_unidad,
                   ca.fecha AS fecha_inicio,
                   NULL AS fecha_fin,
                   sede.nombre AS sede
            FROM curso c
            INNER JOIN curso_abierto ca ON ca.curso_id = c.id
            LEFT JOIN sede ON sede.id = ca.sede_id
            WHERE ca.estatus_id = 1
              AND ca.deleted_at IS NULL

            UNION ALL

            SELECT 'diplomado' AS tipo, d.id,
                   CONCAT(da.numero, ' - ', d.nombre) AS titulo,
                   d.nombre,
                   d.costo AS duracion,
                   'meses' AS duracion_unidad,
                   da.fecha_inicio,
                   da.fecha_fin,
                   sede.nombre AS sede
            FROM diplomado d
            INNER JOIN diplomado_abierto da ON da.diplomado_id = d.id
            LEFT JOIN sede ON sede.id = da.sede_id
            WHERE da.estatus_id = 1
              AND da.deleted_at IS NULL

            UNION ALL

            SELECT 'maestria' AS tipo, m.id,
                   CONCAT(ma.numero, ' - ', m.nombre) AS titulo,
                   m.nombre,
                   m.horas AS duracion,
                   'semestres' AS duracion_unidad,
                   ma.fecha AS fecha_inicio,
                   NULL AS fecha_fin,
                   sede.nombre AS sede
            FROM maestria m
            INNER JOIN maestria_abierto ma ON ma.maestria_id = m.id
            LEFT JOIN sede ON sede.id = ma.sede_id
            WHERE ma.estatus_id = 1
              AND ma.deleted_at IS NULL

            UNION ALL

            SELECT 'evento' AS tipo, e.id,
                   CONCAT(ea.numero, ' - ', e.nombre) AS titulo,
                   e.nombre,
                   NULL AS duracion,
                   NULL AS duracion_unidad,
                   ea.fecha_inicio,
                   ea.fecha_fin,
                   sede.nombre AS sede
            FROM evento e
            INNER JOIN evento_abierto ea ON ea.evento_id = e.id
            LEFT JOIN sede ON sede.id = ea.sede_id
            WHERE ea.estatus_id = 1
              AND ea.deleted_at IS NULL

            ORDER BY titulo ASC
        ";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
}
