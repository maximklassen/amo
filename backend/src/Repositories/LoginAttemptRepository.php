<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;

/**
 * Fehlgeschlagene Anmeldeversuche (Tabelle "login_attempts"), Grundlage für die Sperre nach zu vielen Versuchen.
 * Zeitpunkte werden als Text im Format "Y-m-d H:i:s" übergeben (immer in der Zeitzone der Anwendung).
 */
final class LoginAttemptRepository
{
    public function __construct(private readonly Connection $db)
    {
    }

    public function record(string $email, string $ip, string $at): void
    {
        $this->db->execute(
            'INSERT INTO login_attempts (email, ip, attempted_at) VALUES (:email, :ip, :at)',
            ['email' => $email, 'ip' => $ip, 'at' => $at],
        );
    }

    public function countByEmail(string $email, string $since): int
    {
        return (int) $this->db->scalar(
            'SELECT COUNT(*) FROM login_attempts WHERE email = :email AND attempted_at >= :since',
            ['email' => $email, 'since' => $since],
        );
    }

    public function countByIp(string $ip, string $since): int
    {
        return (int) $this->db->scalar(
            'SELECT COUNT(*) FROM login_attempts WHERE ip = :ip AND attempted_at >= :since',
            ['ip' => $ip, 'since' => $since],
        );
    }

    /** Nach erfolgreichem Login: Zähler dieser E-Mail-Adresse zurücksetzen. */
    public function clearForEmail(string $email): void
    {
        $this->db->execute('DELETE FROM login_attempts WHERE email = :email', ['email' => $email]);
    }

    /** Aufräumen: alte Einträge werden nicht mehr gebraucht. */
    public function deleteOlderThan(string $cutoff): void
    {
        $this->db->execute('DELETE FROM login_attempts WHERE attempted_at < :cutoff', ['cutoff' => $cutoff]);
    }
}
