<?php

namespace App\Models;

class Alumno
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, ci_pasapote, correo, primer_nombre, segundo_nombre,
                   primer_apellido, segundo_apellido,
                   tlf_celular, tlf_habitacion, tlf_trabajo,
                   calle_avenida, casa_apartamento, direccion,
                   fecha_nacimiento, estado_id, nacionalidad_id
            FROM alumno
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findByCiPasaporte(string $ci): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, ci_pasapote, correo, primer_nombre, primer_apellido,
                   tlf_celular, tlf_habitacion, tlf_trabajo, direccion
            FROM alumno
            WHERE ci_pasapote = :ci
            LIMIT 1
        ");
        $stmt->execute([':ci' => $ci]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, ci_pasapote, correo, primer_nombre, primer_apellido,
                   tlf_celular, tlf_habitacion, tlf_trabajo, direccion
            FROM alumno
            WHERE correo = :email
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['correo', 'tlf_celular', 'tlf_habitacion', 'tlf_trabajo', 'direccion'];
        $sets = [];
        $params = [':id' => $id];

        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $sets[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (empty($sets)) {
            return false;
        }

        $sql = "UPDATE alumno SET " . implode(', ', $sets) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function getFoto(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, foto
            FROM alumno
            WHERE id = :id AND foto IS NOT NULL
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function updateFoto(int $id, string $blob): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE alumno SET foto = :foto
            WHERE id = :id
        ");
        return $stmt->execute([
            ':foto' => $blob,
            ':id' => $id,
        ]);
    }
}
