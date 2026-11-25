<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\Config;
use App\Http\Csrf;
use App\Http\Request;
use App\Http\Response;
use App\Support\Validator;

/**
 * Registrierung und Anmeldung. Alle Antworten enthalten den CSRF-Token, den das Frontend
 * bei jeder ändernden Anfrage im Header "X-CSRF-Token" mitschicken muss.
 */
final class AuthController
{
    public function __construct(
        private readonly Auth $auth,
        private readonly Csrf $csrf,
        private readonly Validator $validator,
        private readonly Config $config,
    ) {
    }

    /**
     * GET /api/auth/me – Start-Abfrage des Frontends: wer ist angemeldet (oder null) und welcher CSRF-Token gilt.
     *
     * @return array<string, mixed>
     */
    public function me(Request $request): array
    {
        return ['user' => $this->auth->user(), 'csrf_token' => $this->csrf->token()];
    }

    /** POST /api/auth/register */
    public function register(Request $request): Response
    {
        $minLength = (int) $this->config->get('auth.password_min_length', 8);

        $data = $this->validator->validated($request->body(), [
            'email'      => ['required', 'string', 'email', 'max:255', 'lowercase'],
            // bcrypt verarbeitet maximal 72 Bytes, daher die Obergrenze
            'password'   => ['required', 'string', 'min:' . $minLength, 'max_bytes:72', 'no_trim'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'phone'      => ['string', 'phone'],
            'street'     => ['string', 'max:150'],
            'city'       => ['string', 'max:100'],
            'zip'        => ['string', 'zip'],
        ]);

        $user = $this->auth->register($data);

        return Response::json(['user' => $user, 'csrf_token' => $this->csrf->token()], 201);
    }

    /** POST /api/auth/login */
    public function login(Request $request): Response
    {
        $data = $this->validator->validated($request->body(), [
            'email'    => ['required', 'string', 'email', 'max:255', 'lowercase'],
            'password' => ['required', 'string', 'max_bytes:255', 'no_trim'],
        ]);

        $user = $this->auth->login((string) $data['email'], (string) $data['password'], $request->ip);

        return Response::json(['user' => $user, 'csrf_token' => $this->csrf->token()]);
    }

    /**
     * POST /api/auth/logout  ->  { "ok": true }
     *
     * Die Sitzung ist danach beendet; ein neues CSRF-Token holt das Frontend über GET /api/auth/me.
     *
     * @return array<string, mixed>
     */
    public function logout(Request $request): array
    {
        $this->auth->logout();

        return ['ok' => true];
    }
}
