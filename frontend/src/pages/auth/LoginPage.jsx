import { useState } from 'react';
import { NavLink, useNavigate } from 'react-router-dom';
import { Mail, Lock, Leaf } from 'lucide-react';
import { useAuth } from '@/hooks/useAuth.js';
import { useModal } from '@/context/ModalContext.jsx';
import styles from './AuthForm.module.css';
import loginStyles from './LoginPage.module.css';

export default function LoginPage() {
  const { login } = useAuth();
  const { openRegister } = useModal();
  const navigate = useNavigate();

  const [formData, setFormData] = useState({ email: '', passwort: '' });
  const [errors, setErrors]     = useState({});
  const [showPassword, setShowPassword] = useState(false);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    if (errors[name]) setErrors((prev) => ({ ...prev, [name]: '' }));
  };

  const validate = () => {
    const newErrors = {};
    if (!formData.email.trim()) newErrors.email = 'E-Mail ist erforderlich.';
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email))
      newErrors.email = 'Bitte gültige E-Mail eingeben.';
    if (!formData.passwort) newErrors.passwort = 'Passwort ist erforderlich.';
    else if (formData.passwort.length < 8)
      newErrors.passwort = 'Mindestens 8 Zeichen erforderlich.';
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!validate()) return;

    const data = new FormData(e.target);

    // API-Aufruf folgt später
    console.log('Login:', Object.fromEntries(data));
  };

  return (
    <div className={loginStyles.page}>
      <div className={styles.authLayout}>

        {/* Linke Seite */}
        <div className={styles.leftPanel}>
          <div className={styles.leftInner}>
            <div className={styles.logo}>
              <div className={styles.logoIcon}><Leaf size={22} /></div>
              <div>
                <strong className={styles.logoName}>ParzellenPlaner</strong>
                <span className={styles.logoTagline}>Gemeinsam grün. Besser organisiert.</span>
              </div>
            </div>

            <div className={styles.leftQuote}>
              <p>„Kleine Gärten.<br />Große Gemeinschaft."</p>
            </div>

            <ul className={styles.leftFeatures}>
              <li className={styles.featureItem}>
                <span className={styles.featureIcon}>🌿</span>
                <div>
                  <strong>Parzellen verwalten</strong>
                  <span>Übersichtlich und digital.</span>
                </div>
              </li>
              <li className={styles.featureItem}>
                <span className={styles.featureIcon}>👥</span>
                <div>
                  <strong>Gemeinschaft stärken</strong>
                  <span>Gemeinsam mehr erreichen.</span>
                </div>
              </li>
              <li className={styles.featureItem}>
                <span className={styles.featureIcon}>📅</span>
                <div>
                  <strong>Zeit sparen</strong>
                  <span>Mehr Zeit im Grünen.</span>
                </div>
              </li>
            </ul>
          </div>
        </div>

        {/* Rechte Seite */}
        <div className={styles.rightPanel}>
          <div className={styles.formWrapper}>

            <div className={styles.formHeader}>
              <h2>Willkommen zurück</h2>
              <p>Melden Sie sich in Ihrem Konto an.</p>
            </div>

            <form onSubmit={handleSubmit} className={styles.form} noValidate>

              {/* E-Mail */}
              <div className={styles.formGroup}>
                <label htmlFor="email">E-Mail</label>
                <div className={styles.inputWrapper}>
                  <span className={styles.inputIcon}><Mail size={16} /></span>
                  <input
                    id="email"
                    name="email"
                    type="email"
                    placeholder="z. B. max@beispiel.de"
                    value={formData.email}
                    onChange={handleChange}
                    className={errors.email ? styles.inputError : ''}
                    autoComplete="email"
                  />
                </div>
                {errors.email && <span className={styles.fieldError}>{errors.email}</span>}
              </div>

              {/* Passwort */}
              <div className={styles.formGroup}>
                <label htmlFor="passwort">Passwort</label>
                <div className={styles.inputWrapper}>
                  <span className={styles.inputIcon}><Lock size={16} /></span>
                  <input
                    id="passwort"
                    name="passwort"
                    type={showPassword ? 'text' : 'password'}
                    placeholder="Ihr Passwort"
                    value={formData.passwort}
                    onChange={handleChange}
                    className={errors.passwort ? styles.inputError : ''}
                    autoComplete="current-password"
                  />
                  <button
                    type="button"
                    className={styles.togglePassword}
                    onClick={() => setShowPassword(!showPassword)}
                  >
                    {showPassword ? '🙈' : '👁'}
                  </button>
                </div>
                {errors.passwort && <span className={styles.fieldError}>{errors.passwort}</span>}
              </div>

              {/* Passwort vergessen */}
              <div className={loginStyles.forgotRow}>
                <NavLink to="/passwort-vergessen" className={styles.link}>
                  Passwort vergessen?
                </NavLink>
              </div>

              <button type="submit" className={`btn btn--primary ${styles.submitBtn}`}>
                Anmelden
              </button>

            </form>

            <p className={styles.loginHint}>
              Noch kein Konto?{' '}
              <button onClick={openRegister} className={loginStyles.registerLink}>
                Jetzt registrieren
              </button>
            </p>

            <p className={loginStyles.tagline}>Schön, dass Sie da sind! 🌱</p>

          </div>
        </div>

      </div>
    </div>
  );
}