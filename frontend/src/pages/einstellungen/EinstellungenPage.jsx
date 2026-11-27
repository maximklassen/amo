import { useState } from 'react';
import { Eye, EyeOff, KeyRound } from 'lucide-react';
import { changePassword } from '@/api/api.js';
import AccountLayout from '@/components/shared/AccountLayout/AccountLayout.jsx';
import AccountCard from '@/components/shared/AccountLayout/AccountCard.jsx';
import FormField from '@/components/shared/FormField/FormField.jsx';
import Notice from '@/components/shared/Notice/Notice.jsx';
import styles from './EinstellungenPage.module.css';

const MIN_LENGTH = 8;
const EMPTY = { old_password: '', new_password: '', repeat: '' };

function validate(form) {
    const errors = {};
    if (!form.old_password) errors.old_password = 'Bitte das aktuelle Passwort eingeben';
    if (form.new_password.length < MIN_LENGTH) errors.new_password = `Mindestens ${MIN_LENGTH} Zeichen`;
    else if (new TextEncoder().encode(form.new_password).length > 72) errors.new_password = 'Zu lang (höchstens 72 Bytes)';
    else if (form.new_password === form.old_password) errors.new_password = 'Das neue Passwort muss sich vom alten unterscheiden';
    if (form.repeat !== form.new_password) errors.repeat = 'Die Passwörter stimmen nicht überein';
    return errors;
}

export default function EinstellungenPage() {
    const [form, setForm] = useState(EMPTY);
    const [errors, setErrors] = useState({});
    const [message, setMessage] = useState({ type: '', text: '' });
    const [saving, setSaving] = useState(false);
    const [visible, setVisible] = useState(false);

    const handleChange = (event) => {
        const { name, value } = event.target;
        setForm((current) => ({ ...current, [name]: value }));
        setErrors((current) => ({ ...current, [name]: undefined }));
        setMessage({ type: '', text: '' });
    };

    const handleSubmit = async (event) => {
        event.preventDefault();

        const clientErrors = validate(form);
        if (Object.keys(clientErrors).length > 0) {
            setErrors(clientErrors);
            return;
        }

        setSaving(true);
        setMessage({ type: '', text: '' });

        try {
            await changePassword(form.old_password, form.new_password);
            setForm(EMPTY);
            setErrors({});
            setMessage({ type: 'success', text: 'Dein Passwort wurde geändert.' });
        } catch (error) {
            // Feldfehler (z. B. zu kurz) stehen am Feld; "altes Passwort falsch" kommt als allgemeine Meldung (400)
            setErrors(error.errors ?? {});
            setMessage({ type: 'error', text: error.message });
        } finally {
            setSaving(false);
        }
    };

    const toggle = (
        <button
            type="button"
            className={styles.toggle}
            onClick={() => setVisible((current) => !current)}
            aria-label={visible ? 'Passwörter verbergen' : 'Passwörter anzeigen'}
            aria-pressed={visible}
        >
            {visible ? <EyeOff size={18} strokeWidth={2} /> : <Eye size={18} strokeWidth={2} />}
        </button>
    );

    const type = visible ? 'text' : 'password';

    return (
        <AccountLayout>
            <AccountCard
                title="Passwort ändern"
                description="Wähle ein Passwort, das du nirgendwo sonst verwendest. Du bleibst nach der Änderung angemeldet."
            >
                <form onSubmit={handleSubmit} noValidate className={styles.form}>
                    <Notice type={message.type || 'success'}>{message.text}</Notice>

                    <FormField
                        label="Aktuelles Passwort" name="old_password" type={type} required
                        autoComplete="current-password"
                        value={form.old_password} onChange={handleChange} error={errors.old_password}
                        adornment={toggle}
                    />
                    <FormField
                        label="Neues Passwort" name="new_password" type={type} required
                        autoComplete="new-password"
                        hint={`Mindestens ${MIN_LENGTH} Zeichen.`}
                        value={form.new_password} onChange={handleChange} error={errors.new_password}
                    />
                    <FormField
                        label="Neues Passwort wiederholen" name="repeat" type={type} required
                        autoComplete="new-password"
                        value={form.repeat} onChange={handleChange} error={errors.repeat}
                    />

                    <div className={styles.actions}>
                        <button type="submit" className="btn btn--primary" disabled={saving}>
                            <KeyRound size={18} strokeWidth={2} aria-hidden="true" />
                            {saving ? 'Wird geändert…' : 'Passwort ändern'}
                        </button>
                    </div>
                </form>
            </AccountCard>
        </AccountLayout>
    );
}
