<?php

namespace App\Models;

class AlumnoAuth
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT aa.*, a.ci_pasaporte, a.correo,
                   a.primer_nombre, a.segundo_nombre,
                   a.primer_apellido, a.segundo_apellido,
                   a.tlf_celular, a.tlf_habitacion, a.tlf_trabajo,
                   a.direccion
            FROM alumno_auth aa
            INNER JOIN alumno a ON a.id = aa.alumno_id
            WHERE a.correo = :email
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findByAlumnoId(int $alumnoId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT aa.*, a.ci_pasaporte, a.correo,
                   a.primer_nombre, a.segundo_nombre,
                   a.primer_apellido, a.segundo_apellido,
                   a.tlf_celular, a.tlf_habitacion, a.tlf_trabajo,
                   a.direccion, a.fecha_nacimiento, a.estado_id
            FROM alumno_auth aa
            INNER JOIN alumno a ON a.id = aa.alumno_id
            WHERE aa.alumno_id = :alumno_id
            LIMIT 1
        ");
        $stmt->execute([':alumno_id' => $alumnoId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(int $alumnoId, string $hashedPassword): bool
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO alumno_auth (alumno_id, password_hash)
            VALUES (:alumno_id, :password_hash)
        ");
        return $stmt->execute([
            ':alumno_id' => $alumnoId,
            ':password_hash' => $hashedPassword,
        ]);
    }

    public function updatePassword(int $alumnoId, string $hashedPassword): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE alumno_auth
            SET password_hash = :password_hash
            WHERE alumno_id = :alumno_id
        ");
        return $stmt->execute([
            ':alumno_id' => $alumnoId,
            ':password_hash' => $hashedPassword,
        ]);
    }

    public function updateResetToken(string $email, string $token, string $expiresAt): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE alumno_auth aa
            INNER JOIN alumno a ON a.id = aa.alumno_id
            SET aa.verification_token = :token,
                aa.reset_token_expires_at = :expires_at
            WHERE a.correo = :email
        ");
        return $stmt->execute([
            ':token' => $token,
            ':expires_at' => $expiresAt,
            ':email' => $email,
        ]);
    }

    public function findByResetToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT aa.*, a.correo
            FROM alumno_auth aa
            INNER JOIN alumno a ON a.id = aa.alumno_id
            WHERE aa.verification_token = :token
              AND aa.reset_token_expires_at IS NOT NULL
              AND aa.reset_token_expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([':token' => $token]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function clearResetToken(int $alumnoId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE alumno_auth
            SET verification_token = NULL,
                reset_token_expires_at = NULL
            WHERE alumno_id = :alumno_id
        ");
        return $stmt->execute([':alumno_id' => $alumnoId]);
    }

    public function updateRememberToken(int $alumnoId, ?string $token): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE alumno_auth
            SET remember_token = :token
            WHERE alumno_id = :alumno_id
        ");
        return $stmt->execute([
            ':token' => $token,
            ':alumno_id' => $alumnoId,
        ]);
    }

    public function existsByAlumnoId(int $alumnoId): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM alumno_auth WHERE alumno_id = :alumno_id
        ");
        $stmt->execute([':alumno_id' => $alumnoId]);
        return $stmt->fetchColumn() > 0;
    }
}
