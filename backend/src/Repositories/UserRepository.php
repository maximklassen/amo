<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;

/**
 * Datenbankzugriff auf die Tabellen "users" (Login, Rolle) und "users_data" (persönliche Daten).
 * Alle Werte werden als Parameter gebunden (Prepared Statements), nie in den SQL-Text eingesetzt.
 */
final class UserRepository
{
    public function __construct(private readonly Connection $db)
    {
    }

    /**
     * Für den Login: enthält als einzige Abfrage den Passwort-Hash.
     *
     * @return array<string, mixed>|null
     */
    public function findForLogin(string $email): ?array
    {
        return $this->db->first(
            'SELECT id, email, password_hash, role FROM users WHERE email = :email',
            ['email' => $email],
        );
    }

    /**
     * Benutzer mit persönlichen Daten, ohne Passwort-Hash.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        return $this->db->first(
            'SELECT u.id, u.email, u.role, u.created_at,
                    d.first_name, d.last_name, d.phone, d.street, d.city, d.zip
             FROM users u
             LEFT JOIN users_data d ON d.user_id = u.id
             WHERE u.id = :id',
            ['id' => $id],
        );
    }

    public function emailExists(string $email): bool
    {
        return $this->db->scalar('SELECT COUNT(*) FROM users WHERE email = :email', ['email' => $email]) > 0;
    }

    /** @return int ID des neuen Benutzers */
    public function create(string $email, string $passwordHash, string $role = 'bewerber'): int
    {
        $this->db->execute(
            'INSERT INTO users (email, password_hash, role) VALUES (:email, :password_hash, :role)',
            ['email' => $email, 'password_hash' => $passwordHash, 'role' => $role],
        );

        return (int) $this->db->pdo()->lastInsertId();
    }

    /** @param array<string, mixed> $profile first_name, last_name, phone, street, city, zip */
    public function createProfile(int $userId, array $profile): void
    {
        $this->db->execute(
            'INSERT INTO users_data (user_id, first_name, last_name, phone, street, city, zip)
             VALUES (:user_id, :first_name, :last_name, :phone, :street, :city, :zip)',
            [
                'user_id'    => $userId,
                'first_name' => $profile['first_name'],
                'last_name'  => $profile['last_name'],
                'phone'      => $profile['phone'] ?? null,
                'street'     => $profile['street'] ?? null,
                'city'       => $profile['city'] ?? null,
                'zip'        => $profile['zip'] ?? null,
            ],
        );
    }

    public function updatePasswordHash(int $id, string $passwordHash): void
    {
        $this->db->execute(
            'UPDATE users SET password_hash = :password_hash WHERE id = :id',
            ['password_hash' => $passwordHash, 'id' => $id],
        );
    }
}
