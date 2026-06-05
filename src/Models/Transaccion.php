<?php

namespace App\Models;

class Transaccion
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function getDebtsByAlumnoId(int $alumnoId): array
    {
        $sql = "
            SELECT
                t.id AS transaccion_id,
                t.cuota_id,
                t.tipo,
                t.monto,
                (t.monto - COALESCE(pg.total_pagado, 0)) AS saldo_pendiente,
                t.fecha,
                t.estatus AS estatus_transaccion,
                c.nombre AS concepto,
                c.monto AS monto_cuota,
                c.fecha_vencimiento,
                toa.nombre AS tipo_oferta
            FROM transaccion t
            INNER JOIN cuota c ON c.id = t.cuota_id
            LEFT JOIN tipo_oferta_academica toa ON toa.id = c.tipo_oferta_academica_id
            LEFT JOIN (
                SELECT cuota_id, alumno_id, COALESCE(SUM(monto), 0) AS total_pagado
                FROM pago
                WHERE estatus_pago_id NOT IN (3, 4)
                GROUP BY cuota_id, alumno_id
            ) pg ON pg.cuota_id = t.cuota_id AND pg.alumno_id = t.alumno_id
            WHERE t.alumno_id = :alumno_id
              AND t.tipo = 1
              AND t.estatus = 1
              AND t.monto > COALESCE(pg.total_pagado, 0)
            ORDER BY c.fecha_vencimiento ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':alumno_id' => $alumnoId]);
        return $stmt->fetchAll();
    }

    public function getPaymentsByAlumnoId(int $alumnoId): array
    {
        $sql = "
            SELECT
                p.id AS pago_id,
                p.alumno_id,
                p.monto,
                p.fecha AS fecha_pago,
                p.numero_control AS referencia,
                p.voucher,
                p.estatus_pago_id,
                ep.nombre AS estatus_pago,
                f.nombre AS forma_pago,
                b.nombre AS banco,
                c.nombre AS concepto,
                c.id AS cuota_id
            FROM pago p
            LEFT JOIN estatus_pago ep ON ep.id = p.estatus_pago_id
            LEFT JOIN forma_pago f ON f.id = p.forma_pago_id
            LEFT JOIN banco b ON b.id = p.banco_id
            INNER JOIN cuota c ON c.id = p.cuota_id
            WHERE p.cuota_id IN (
                SELECT DISTINCT cuota_id FROM transaccion WHERE alumno_id = :alumno_id
            )
            ORDER BY p.fecha DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':alumno_id' => $alumnoId]);
        return $stmt->fetchAll();
    }

    public function getTotalDebt(int $alumnoId): float
    {
        $debts = $this->getDebtsByAlumnoId($alumnoId);
        return round(array_sum(array_column($debts, 'saldo_pendiente')), 2);
    }

    /**
     * Inserts a transaction record associated with a student payment or academic debt.
     *
     * @param array $data Contains transaction fields (alumno_id, cuota_id, fecha_pago, tipo, monto, estatus, id_transaccion_origen)
     * @return bool
     */
    public function create(array $data): bool
    {
        $sql = "
            INSERT INTO transaccion 
                (alumno_id, cuota_id, fecha_pago, tipo, monto, fecha, estatus, id_transaccion_origen)
            VALUES 
                (:alumno_id, :cuota_id, :fecha_pago, :tipo, :monto, NOW(), :estatus, :id_transaccion_origen)
        ";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':alumno_id' => (int)$data['alumno_id'],
            ':cuota_id' => (int)$data['cuota_id'],
            ':fecha_pago' => !empty($data['fecha_pago']) ? $data['fecha_pago'] : null,
            ':tipo' => isset($data['tipo']) ? (int)$data['tipo'] : 2, // 2: Credito
            ':monto' => (float)$data['monto'],
            ':estatus' => isset($data['estatus']) ? (int)$data['estatus'] : 2, // 2: Pago registrado
            ':id_transaccion_origen' => !empty($data['id_transaccion_origen']) ? (int)$data['id_transaccion_origen'] : null,
        ]);
    }
}