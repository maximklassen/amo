// NUR FÜR DIE ENTWICKLUNG: automatische Anmeldung, solange die Login-Seite noch nicht fertig ist.
//
// - Wird aus main.jsx nur geladen, wenn import.meta.env.DEV gilt (beim `npm run build` fliegt der Aufruf
//   samt dieser Datei aus dem Bundle).
// - Zugangsdaten stehen nur in frontend/.env.development.local (von Git ignoriert), nie im Code.
// - Ohne diese Variablen oder bei Fehlern passiert nichts: die App verhält sich dann wie ohne Dev-Login.

import { login, me } from '@/api/api.js';

export async function devLogin() {
    const email = import.meta.env.VITE_DEV_LOGIN_EMAIL;
    const password = import.meta.env.VITE_DEV_LOGIN_PASSWORD;

    if (!email || !password) {
        return;
    }

    try {
        // me() holt auch den CSRF-Token. Besteht die Session noch (Cookie), ist nichts weiter nötig.
        if (await me()) {
            return;
        }

        await login({ email, password });
        console.info(`[Dev-Login] angemeldet als ${email}`);
    } catch (error) {
        console.warn('[Dev-Login] fehlgeschlagen:', error.message, '(läuft das Backend auf localhost:8000?)');
    }
}
