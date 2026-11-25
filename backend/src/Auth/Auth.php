<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Config;
use App\Database\Connection;
use App\Http\Csrf;
use App\Http\HttpException;
use App\Http\Session;
use App\Repositories\LoginAttemptRepository;
use App\Repositories\UserRepository;
use PDOException;

/**
 * Registrierung, Login, Logout und "wer ist gerade angemeldet?".
 *
 * Sicherheitsmaßnahmen:
 * - Passwörter nur als bcrypt-Hash (password_hash), nie im Klartext
 * - Login-Fehler sind immer gleich ("E-Mail oder Passwort falsch"), auch bei unbekannter E-Mail
 *   (verrät nicht, welche Adressen registriert sind); die Prüfzeit ist in beiden Fällen ähnlich
 * - Sperre nach zu vielen Fehlversuchen (pro E-Mail und pro IP), gegen Passwort-Raten
 * - Neue Session-ID beim Login (gegen Session Fixation), Inaktivitäts-Timeout
 * - Die Rolle wird bei jeder Anfrage frisch aus der Datenbank gelesen, nicht aus der Session
 */
final class Auth
{
    /**
     * bcrypt-Hash eines zufälligen Textes. Wird bei unbekannter E-Mail geprüft, damit die Antwortzeit
     * nicht verrät, ob es das Konto gibt.
     */
    private const DUMMY_HASH = '$2y$12$3HK752Q4t6AVWGy.AXZd1.JVPFw1ewskZifOn2yvOKoUx84kTDwJS';

    /** @var array<string, mixed>|null */
    private ?array $user = null;
    private bool $loaded = false;

    public function __construct(
        private readonly Session $session,
        private readonly Csrf $csrf,
        private readonly Connection $db,
        private readonly UserRepository $users,
        private readonly LoginAttemptRepository $attempts,
        private readonly Config $config,
    ) {
    }

    /**
     * Legt Konto und persönliche Daten an (immer mit Rolle "bewerber") und meldet den Benutzer an.
     *
     * @param array<string, mixed> $data geprüfte Eingaben: email, password, first_name, last_name, phone, street, city, zip
     * @return array<string, mixed> der angemeldete Benutzer
     */
    public function register(array $data): array
    {
        $email = (string) $data['email'];

        if ($this->users->emailExists($email)) {
            throw $this->emailTaken();
        }

        $hash = $this->hashPassword((string) $data['password']);

        try {
            // Beides oder nichts: ohne users_data wäre das Konto unvollständig
            $id = $this->db->transaction(function () use ($email, $hash, $data): int {
                $id = $this->users->create($email, $hash, 'bewerber');
                $this->users->createProfile($id, $data);

                return $id;
            });
        } catch (PDOException $e) {
            // Zwei Registrierungen mit derselben E-Mail gleichzeitig: die UNIQUE-Bedingung der Datenbank greift.
            // SQLSTATE 23000 steht für jede Integritätsverletzung (auch NOT NULL, Fremdschlüssel), darum
            // zählt es nur als "E-Mail vergeben", wenn die Adresse jetzt wirklich existiert.
            if ($e->getCode() === '23000' && $this->users->emailExists($email)) {
                throw $this->emailTaken();
            }

            throw $e;
        }

        $this->startSession($id);

        return $this->user() ?? throw new \LogicException('Neu angelegter Benutzer nicht gefunden');
    }

    /**
     * @return array<string, mixed> der angemeldete Benutzer
     * @throws HttpException 401 bei falschen Zugangsdaten, 429 bei zu vielen Fehlversuchen
     */
    public function login(string $email, string $password, string $ip): array
    {
        $this->assertNotThrottled($email, $ip);

        $row = $this->users->findForLogin($email);

        // Auch ohne Konto wird ein Hash geprüft (ähnliche Dauer, keine Information für Angreifer)
        $valid = password_verify($password, $row['password_hash'] ?? self::DUMMY_HASH);

        if ($row === null || !$valid) {
            $now = time();
            $this->attempts->record($email, $ip, date('Y-m-d H:i:s', $now));
            $this->attempts->deleteOlderThan(date('Y-m-d H:i:s', $now - 86400));

            throw new HttpException(401, 'invalid_credentials', 'E-Mail oder Passwort falsch');
        }

        $this->attempts->clearForEmail($email);

        // Hash auf aktuellen Stand bringen, falls sich der Aufwand (cost) geändert hat
        if (password_needs_rehash((string) $row['password_hash'], PASSWORD_BCRYPT, ['cost' => $this->cost()])) {
            $this->users->updatePasswordHash((int) $row['id'], $this->hashPassword($password));
        }

        $this->startSession((int) $row['id']);

        return $this->user() ?? throw new \LogicException('Angemeldeter Benutzer nicht gefunden');
    }

    public function logout(): void
    {
        $this->session->invalidate();
        $this->csrf->rotate();
        $this->user = null;
        $this->loaded = true;
    }

    /**
     * Der angemeldete Benutzer oder null. Prüft Inaktivitäts-Timeout und ob das Konto noch existiert.
     *
     * @return array<string, mixed>|null id, email, role, first_name, last_name, phone, street, city, zip, created_at
     */
    public function user(): ?array
    {
        if ($this->loaded) {
            return $this->user;
        }

        $this->loaded = true;
        $id = $this->session->get('user_id');

        if (!is_int($id)) {
            return null;
        }

        $idleSeconds = (int) $this->config->get('auth.session.idle_minutes', 120) * 60;
        $lastActivity = (int) $this->session->get('last_activity', 0);

        if (time() - $lastActivity > $idleSeconds) {
            $this->session->invalidate();

            return null;
        }

        $row = $this->users->findById($id);

        if ($row === null) {
            $this->session->invalidate();

            return null;
        }

        $this->session->set('last_activity', time());

        return $this->user = [
            'id'         => (int) $row['id'],
            'email'      => (string) $row['email'],
            'role'       => (string) $row['role'],
            'first_name' => $row['first_name'],
            'last_name'  => $row['last_name'],
            'phone'      => $row['phone'],
            'street'     => $row['street'],
            'city'       => $row['city'],
            'zip'        => $row['zip'],
            'created_at' => (string) $row['created_at'],
        ];
    }

    private function startSession(int $userId): void
    {
        $this->session->regenerate();
        $this->session->set('user_id', $userId);
        $this->session->set('last_activity', time());
        $this->csrf->rotate();

        $this->user = null;
        $this->loaded = false;
    }

    private function assertNotThrottled(string $email, string $ip): void
    {
        $window = (int) $this->config->get('auth.lockout_minutes', 15) * 60;
        $since = date('Y-m-d H:i:s', time() - $window);

        $tooManyForEmail = $this->attempts->countByEmail($email, $since) >= (int) $this->config->get('auth.max_failed_per_email', 5);
        $tooManyForIp = $this->attempts->countByIp($ip, $since) >= (int) $this->config->get('auth.max_failed_per_ip', 20);

        if ($tooManyForEmail || $tooManyForIp) {
            throw HttpException::tooManyRequests($window);
        }
    }

    private function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $this->cost()]);
    }

    private function cost(): int
    {
        return (int) $this->config->get('auth.bcrypt_cost', 12);
    }

    private function emailTaken(): HttpException
    {
        return HttpException::conflict('email_taken', 'Diese E-Mail-Adresse ist bereits registriert');
    }
}
