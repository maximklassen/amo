import { useEffect, useState } from 'react';
import { Save } from 'lucide-react';
import { getProfile, updateProfile } from '@/api/api.js';
import { useAuth } from '@/hooks/useAuth.js';
import AccountLayout from '@/components/shared/AccountLayout/AccountLayout.jsx';
import AccountCard from '@/components/shared/AccountLayout/AccountCard.jsx';
import FormField from '@/components/shared/FormField/FormField.jsx';
import Notice from '@/components/shared/Notice/Notice.jsx';
import styles from './ProfilPage.module.css';

const FIELDS = ['first_name', 'last_name', 'phone', 'street', 'city', 'zip'];

// API-Werte (null) -> Formularwerte (leerer Text) und zurück
const toForm = (profile) => Object.fromEntries(FIELDS.map((field) => [field, profile[field] ?? '']));

/** Prüfung im Browser (nur für schnelles Feedback; maßgeblich ist die Prüfung im Backend). */
function validate(form) {
    const errors = {};
    if (!form.first_name.trim()) errors.first_name = 'Bitte Vornamen angeben';
    if (!form.last_name.trim()) errors.last_name = 'Bitte Nachnamen angeben';
    if (form.zip.trim() && !/^\d{5}$/.test(form.zip.trim())) errors.zip = 'Bitte 5 Ziffern angeben';
    return errors;
}

export default function ProfilPage() {
    const { login: setCurrentUser } = useAuth(); // setzt den Benutzer im AuthContext (z. B. für den Namen im Header)

    const [profile, setProfile] = useState(null);   // zuletzt gespeicherter Stand
    const [form, setForm] = useState(null);
    const [errors, setErrors] = useState({});
    const [loadError, setLoadError] = useState('');
    const [message, setMessage] = useState({ type: '', text: '' });
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        let cancelled = false;

        getProfile()
            .then((data) => {
                if (cancelled) return;
                setProfile(data);
                setForm(toForm(data));
            })
            .catch((error) => !cancelled && setLoadError(error.message));

        return () => { cancelled = true; };
    }, []);

    if (loadError) {
        return (
            <AccountLayout>
                <Notice type="error">Das Profil konnte nicht geladen werden: {loadError}</Notice>
            </AccountLayout>
        );
    }

    if (!form) {
        return (
            <AccountLayout>
                <p className={styles.loading}>Profil wird geladen…</p>
            </AccountLayout>
        );
    }

    const dirty = FIELDS.some((field) => form[field] !== (profile[field] ?? ''));

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
            setMessage({ type: 'error', text: 'Bitte die markierten Felder prüfen.' });
            return;
        }

        setSaving(true);
        setMessage({ type: '', text: '' });

        try {
            const updated = await updateProfile(form);
            setProfile(updated);
            setForm(toForm(updated));
            setErrors({});
            setCurrentUser(updated);
            setMessage({ type: 'success', text: 'Deine Daten wurden gespeichert.' });
        } catch (error) {
            setErrors(error.errors ?? {});
            setMessage({ type: 'error', text: error.message });
        } finally {
            setSaving(false);
        }
    };

    return (
        <AccountLayout profile={profile}>
            <AccountCard
                title="Persönliche Daten"
                description="Diese Angaben sehen nur du und der Vorstand."
            >
                <form onSubmit={handleSubmit} noValidate className={styles.form}>
                    <Notice type={message.type || 'success'}>{message.text}</Notice>

                    <div className={styles.grid}>
                        <FormField
                            label="Vorname" name="first_name" required maxLength={100}
                            autoComplete="given-name"
                            value={form.first_name} onChange={handleChange} error={errors.first_name}
                        />
                        <FormField
                            label="Nachname" name="last_name" required maxLength={100}
                            autoComplete="family-name"
                            value={form.last_name} onChange={handleChange} error={errors.last_name}
                        />

                        <div className={styles.full}>
                            <FormField
                                label="E-Mail-Adresse" name="email" type="email" disabled
                                value={profile.email} onChange={() => {}}
                                hint="Die E-Mail-Adresse ist dein Login und kann hier nicht geändert werden."
                            />
                        </div>

                        <div className={styles.full}>
                            <FormField
                                label="Telefon" name="phone" type="tel" maxLength={30}
                                autoComplete="tel"
                                value={form.phone} onChange={handleChange} error={errors.phone}
                            />
                        </div>

                        <div className={styles.full}>
                            <FormField
                                label="Straße und Hausnummer" name="street" maxLength={150}
                                autoComplete="street-address"
                                value={form.street} onChange={handleChange} error={errors.street}
                            />
                        </div>

                        <FormField
                            label="PLZ" name="zip" inputMode="numeric" maxLength={5}
                            autoComplete="postal-code"
                            value={form.zip} onChange={handleChange} error={errors.zip}
                        />
                        <FormField
                            label="Ort" name="city" maxLength={100}
                            autoComplete="address-level2"
                            value={form.city} onChange={handleChange} error={errors.city}
                        />
                    </div>

                    <div className={styles.actions}>
                        <button type="submit" className="btn btn--primary" disabled={saving || !dirty}>
                            <Save size={18} strokeWidth={2} aria-hidden="true" />
                            {saving ? 'Wird gespeichert…' : 'Änderungen speichern'}
                        </button>
                        {dirty && !saving && (
                            <button
                                type="button" className="btn btn--outline"
                                onClick={() => { setForm(toForm(profile)); setErrors({}); setMessage({ type: '', text: '' }); }}
                            >
                                Zurücksetzen
                            </button>
                        )}
                    </div>
                </form>
            </AccountCard>
        </AccountLayout>
    );
}
