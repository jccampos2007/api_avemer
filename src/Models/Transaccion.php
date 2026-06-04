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
                t.fecha,
                t.estatus AS estatus_transaccion,
                c.nombre AS concepto,
                c.monto AS monto_cuota,
                c.fecha_vencimiento,
                toa.nombre AS tipo_oferta
            FROM transaccion t
            INNER JOIN cuota c ON c.id = t.cuota_id
            LEFT JOIN tipo_oferta_academica toa ON toa.id = c.tipo_oferta_academica_id
            WHERE t.alumno_id = :alumno_id
              AND t.tipo = 1
              AND t.estatus = 1
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
        return round(array_sum(array_column($debts, 'monto')), 2);
    }
}
