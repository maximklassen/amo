import { NavLink } from 'react-router-dom';
import { User, ShieldCheck } from 'lucide-react';
import { useAuth } from '@/hooks/useAuth.js';
import styles from './AccountLayout.module.css';

const ROLE_LABELS = {
    bewerber: 'Bewerber',
    paechter: 'Pächter',
    vorstand: 'Vorstand',
};

/** "2020-01-15 18:20:00" -> "Januar 2020" (MySQL-Format, ohne Zeitzone) */
function memberSince(createdAt) {
    const date = new Date(String(createdAt).replace(' ', 'T'));
    return Number.isNaN(date.getTime())
        ? null
        : date.toLocaleDateString('de-DE', { month: 'long', year: 'numeric' });
}

/**
 * Gemeinsamer Rahmen für Profil und Einstellungen: Kopfkarte mit dem Benutzer und Reiter zum Wechseln.
 * `profile` kommt von GET /api/profile (die Seite lädt es und gibt es hierher weiter).
 */
export default function AccountLayout({ profile, children }) {
    const { user } = useAuth();
    const person = profile ?? user;

    const fullName = [person?.first_name, person?.last_name].filter(Boolean).join(' ') || 'Mein Konto';
    const initials = [person?.first_name, person?.last_name]
        .filter(Boolean)
        .map((part) => part[0].toUpperCase())
        .join('') || '?';
    const since = person?.created_at ? memberSince(person.created_at) : null;

    const tabClass = ({ isActive }) => `${styles.tab} ${isActive ? styles.tabActive : ''}`;

    return (
        <div className={styles.page}>
            <div className="container">
                <section className={styles.hero}>
                    <div className={styles.avatar} aria-hidden="true">{initials}</div>
                    <div className={styles.heroText}>
                        <h1 className={styles.name}>{fullName}</h1>
                        <p className={styles.meta}>
                            {person?.role && (
                                <span className={`${styles.role} ${styles[`role_${person.role}`] ?? ''}`}>
                                    {ROLE_LABELS[person.role] ?? person.role}
                                </span>
                            )}
                            {since && <span>Mitglied seit {since}</span>}
                        </p>
                    </div>
                </section>

                <nav className={styles.tabs} aria-label="Konto">
                    <NavLink to="/profil" className={tabClass}>
                        <User size={17} strokeWidth={2} aria-hidden="true" />
                        Persönliche Daten
                    </NavLink>
                    <NavLink to="/einstellungen" className={tabClass}>
                        <ShieldCheck size={17} strokeWidth={2} aria-hidden="true" />
                        Passwort
                    </NavLink>
                </nav>

                {children}
            </div>
        </div>
    );
}
