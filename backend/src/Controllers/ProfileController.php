<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\Config;
use App\Http\HttpException;
use App\Http\Request;
use App\Support\Validator;

/** Eigene Daten des angemeldeten Benutzers. Alle Routen liegen hinter der AuthMiddleware. */
final class ProfileController
{
    public function __construct(
        private readonly Auth $auth,
        private readonly Validator $validator,
        private readonly Config $config,
    ) {
    }

    /**
     * GET /api/profile
     *
     * @return array<string, mixed>
     */
    public function show(Request $request): array
    {
        return $this->currentUser($request);
    }

    /**
     * PUT /api/profile – ändert nur users_data des angemeldeten Benutzers (nie E-Mail oder Rolle).
     * Felder, die fehlen oder leer sind, werden bei den optionalen Angaben geleert.
     *
     * @return array<string, mixed>
     */
    public function update(Request $request): array
    {
        $data = $this->validator->validated($request->body(), [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'phone'      => ['string', 'phone'],
            'street'     => ['string', 'max:150'],
            'city'       => ['string', 'max:100'],
            'zip'        => ['string', 'zip'],
        ]);

        return $this->auth->updateProfile((int) $this->currentUser($request)['id'], $data);
    }

    /**
     * PUT /api/profile/password
     *
     * @return array<string, mixed>
     */
    public function changePassword(Request $request): array
    {
        $minLength = (int) $this->config->get('auth.password_min_length', 8);

        $data = $this->validator->validated($request->body(), [
            'old_password' => ['required', 'string', 'max_bytes:255', 'no_trim'],
            // bcrypt verarbeitet maximal 72 Bytes
            'new_password' => ['required', 'string', 'min:' . $minLength, 'max_bytes:72', 'no_trim'],
        ]);

        if ($data['old_password'] === $data['new_password']) {
            throw HttpException::validation(['new_password' => 'Das neue Passwort muss sich vom alten unterscheiden']);
        }

        $this->auth->changePassword((int) $this->currentUser($request)['id'], (string) $data['old_password'], (string) $data['new_password']);

        return ['ok' => true];
    }

    /** @return array<string, mixed> */
    private function currentUser(Request $request): array
    {
        // Von der AuthMiddleware gesetzt; ohne Anmeldung kommt die Anfrage gar nicht bis hierher
        $user = $request->attribute('user');

        return is_array($user) ? $user : throw HttpException::unauthorized();
    }
}
