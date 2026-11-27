import { useAuth } from '@/hooks/useAuth.js';
import { useModal } from '@/context/ModalContext.jsx';
import { ApiError } from '@/api/api.js';
import { useState } from 'react';
import { NavLink, useNavigate } from 'react-router-dom';
import { User, Mail, Phone, Home, Hash, MapPin, Lock, Leaf } from 'lucide-react';
import styles from './AuthForm.module.css';

// Schritt-Felder
const STEP_ONE_FIELDS = [
  { id: 'vorname',   label: 'Vorname',   icon: <User size={16} />,  type: 'text',  placeholder: 'z. B. Max',               required: true },
  { id: 'nachname',  label: 'Nachname',  icon: <User size={16} />,  type: 'text',  placeholder: 'z. B. Mustermann',        required: true },
  { id: 'email',     label: 'E-Mail',    icon: <Mail size={16} />,  type: 'email', placeholder: 'z. B. max@beispiel.de',   required: true },
  { id: 'telefon',   label: 'Telefon',   icon: <Phone size={16} />, type: 'tel',   placeholder: 'z. B. 0151 12345678',    required: true },
];

const STEP_TWO_FIELDS = [
  { id: 'strasse',   label: 'Straße',    icon: <Home size={16} />,   type: 'text', placeholder: 'z. B. Gartenstraße',  required: true },
  { id: 'hausnr',    label: 'Hausnummer',icon: <Hash size={16} />,   type: 'text', placeholder: 'z. B. 12',            required: true },
  { id: 'plz',       label: 'PLZ',       icon: <MapPin size={16} />, type: 'text', placeholder: 'z. B. 30159',         required: true },
  { id: 'ort',       label: 'Ort',       icon: <MapPin size={16} />, type: 'text', placeholder: 'z. B. Hannover',      required: true },
];

export default function RegisterForm({ onClose }) {

  const { register } = useAuth();
  const { closeModal } = useModal();
  const navigate = useNavigate();
  const [serverError, setServerError] = useState('');

  const [step, setStep] = useState(1);
  const [formData, setFormData] = useState({
    vorname: '', nachname: '', email: '', telefon: '',
    strasse: '', hausnr: '', plz: '', ort: '',
    passwort: '', passwortWiederholen: '', datenschutz: false,
  });

  const [errors, setErrors] = useState({});
  const [showPassword, setShowPassword] = useState(false);

  // Eingabe-Handler
  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value,
    }));
    // Fehler beim Tippen entfernen
    if (errors[name]) {
      setErrors((prev) => ({ ...prev, [name]: '' }));
    }
  };

  // Schritt-1-Validierung
  const validateStepOne = () => {
    const newErrors = {};
    if (!formData.vorname.trim())  newErrors.vorname  = 'Vorname ist erforderlich.';
    if (!formData.nachname.trim()) newErrors.nachname = 'Nachname ist erforderlich.';
    if (!formData.email.trim())    newErrors.email    = 'E-Mail ist erforderlich.';
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email))
      newErrors.email = 'Bitte gültige E-Mail eingeben.';
    if (!formData.telefon.trim())  newErrors.telefon  = 'Telefon ist erforderlich.';
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  // Schritt-2-Validierung
  const validateStepTwo = () => {
    const newErrors = {};
    if (!formData.strasse.trim())  newErrors.strasse  = 'Straße ist erforderlich.';
    if (!formData.hausnr.trim())   newErrors.hausnr   = 'Hausnummer ist erforderlich.';
    if (!formData.plz.trim())      newErrors.plz      = 'PLZ ist erforderlich.';
    else if (!/^\d{5}$/.test(formData.plz)) newErrors.plz = 'PLZ muss 5 Ziffern haben.';
    if (!formData.ort.trim())      newErrors.ort      = 'Ort ist erforderlich.';
    if (!formData.passwort)        newErrors.passwort = 'Passwort ist erforderlich.';
    else if (formData.passwort.length < 8) newErrors.passwort = 'Mindestens 8 Zeichen.';
    if (formData.passwort !== formData.passwortWiederholen)
      newErrors.passwortWiederholen = 'Passwörter stimmen nicht überein.';
    if (!formData.datenschutz)
      newErrors.datenschutz = 'Bitte Datenschutz akzeptieren.';
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  // Weiter zu Schritt 2
  const handleNextStep = () => {
    if (validateStepOne()) setStep(2);
  };

  // Formular absenden
  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!validateStepTwo()) return;

    try {
      setServerError('');
      await register({
        email: formData.email,
        password: formData.passwort,
        first_name: formData.vorname,
        last_name: formData.nachname,
        phone: formData.telefon,
        street: formData.strasse + ' ' + formData.hausnr,
        city: formData.ort,
        zip: formData.plz,
      });
      closeModal();
      navigate('/dashboard');
    } catch (err) {
      if (err instanceof ApiError) {
        setServerError(err.message);
      } else {
        setServerError('Verbindungsfehler. Bitte versuchen Sie es erneut.');
      }
    }
  };

  return (
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
            <p>„Ein Garten ist ein Stück Lebensqualität."</p>
          </div>

          <ul className={styles.leftFeatures}>
            <li className={styles.featureItem}>
              <span className={styles.featureIcon}>👥</span>
              <div>
                <strong>Kleingärten verbinden Menschen</strong>
                <span>Gemeinschaft. Natur. Zukunft.</span>
              </div>
            </li>
            <li className={styles.featureItem}>
              <span className={styles.featureIcon}>📅</span>
              <div>
                <strong>Einfach verwalten</strong>
                <span>Weniger Papier, mehr Zeit für das Wesentliche.</span>
              </div>
            </li>
            <li className={styles.featureItem}>
              <span className={styles.featureIcon}>🌿</span>
              <div>
                <strong>Für eine grünere Zukunft</strong>
                <span>Unsere Kleingärten — lebenswerte Städte.</span>
              </div>
            </li>
          </ul>
        </div>
      </div>

      {/* Rechte Seite — Formular */}
      <div className={styles.rightPanel}>
        <div className={styles.formWrapper}>

          {/* Fortschrittsanzeige */}
          <div className={styles.progress}>
            <div className={`${styles.progressStep} ${step >= 1 ? styles.progressStepActive : ''}`}>
              <span>1</span>
            </div>
            <div className={`${styles.progressLine} ${step === 2 ? styles.progressLineActive : ''}`} />
            <div className={`${styles.progressStep} ${step === 2 ? styles.progressStepActive : ''}`}>
              <span>2</span>
            </div>
          </div>

          <div className={styles.formHeader}>
            <h2>{step === 1 ? 'Konto erstellen' : 'Adresse & Zugangsdaten'}</h2>
            <p>{step === 1 ? 'Werden Sie Teil unserer Kleingarten-Community.' : 'Fast geschafft! Noch ein paar Angaben.'}</p>
          </div>

          <form onSubmit={handleSubmit} className={styles.form} noValidate>

            {step === 1 && (
              <>
                {/* Persönliche Daten */}
                <div className={styles.sectionLabel}>Persönliche Daten</div>
                <div className={styles.formGrid}>
                  {STEP_ONE_FIELDS.slice(0, 2).map((field) => (
                    <div key={field.id} className={styles.formGroup}>
                      <label htmlFor={field.id}>
                        {field.label}
                        {field.required && <span className={styles.required}>*</span>}
                      </label>
                      <div className={styles.inputWrapper}>
                        <span className={styles.inputIcon}>{field.icon}</span>
                        <input
                          id={field.id}
                          name={field.id}
                          type={field.type}
                          placeholder={field.placeholder}
                          value={formData[field.id]}
                          onChange={handleChange}
                          className={errors[field.id] ? styles.inputError : ''}
                        />
                      </div>
                      {errors[field.id] && (
                        <span className={styles.fieldError}>{errors[field.id]}</span>
                      )}
                    </div>
                  ))}
                </div>

                {/* Kontakt */}
                <div className={styles.sectionLabel}>Kontakt</div>
                <div className={styles.formGrid}>
                  {STEP_ONE_FIELDS.slice(2).map((field) => (
                    <div key={field.id} className={styles.formGroup}>
                      <label htmlFor={field.id}>
                        {field.label}
                        {field.required && <span className={styles.required}>*</span>}
                      </label>
                      <div className={styles.inputWrapper}>
                        <span className={styles.inputIcon}>{field.icon}</span>
                        <input
                          id={field.id}
                          name={field.id}
                          type={field.type}
                          placeholder={field.placeholder}
                          value={formData[field.id]}
                          onChange={handleChange}
                          className={errors[field.id] ? styles.inputError : ''}
                        />
                      </div>
                      {errors[field.id] && (
                        <span className={styles.fieldError}>{errors[field.id]}</span>
                      )}
                    </div>
                  ))}
                </div>

                <button
                  type="button"
                  className={`btn btn--primary ${styles.submitBtn}`}
                  onClick={handleNextStep}
                >
                  Weiter
                </button>
              </>
            )}

            {step === 2 && (
              <>
                {/* Adresse */}
                <div className={styles.sectionLabel}>Adresse</div>
                <div className={styles.formGrid}>
                  {STEP_TWO_FIELDS.map((field) => (
                    <div key={field.id} className={styles.formGroup}>
                      <label htmlFor={field.id}>
                        {field.label}
                        {field.required && <span className={styles.required}>*</span>}
                      </label>
                      <div className={styles.inputWrapper}>
                        <span className={styles.inputIcon}>{field.icon}</span>
                        <input
                          id={field.id}
                          name={field.id}
                          type={field.type}
                          placeholder={field.placeholder}
                          value={formData[field.id]}
                          onChange={handleChange}
                          className={errors[field.id] ? styles.inputError : ''}
                        />
                      </div>
                      {errors[field.id] && (
                        <span className={styles.fieldError}>{errors[field.id]}</span>
                      )}
                    </div>
                  ))}
                </div>

                {/* Zugangsdaten */}
                <div className={styles.sectionLabel}>Zugangsdaten</div>
                <div className={styles.formGroup}>
                  <label htmlFor="passwort">
                    Passwort <span className={styles.required}>*</span>
                  </label>
                  <div className={styles.inputWrapper}>
                    <span className={styles.inputIcon}><Lock size={16} /></span>
                    <input
                      id="passwort"
                      name="passwort"
                      type={showPassword ? 'text' : 'password'}
                      placeholder="Mindestens 8 Zeichen"
                      value={formData.passwort}
                      onChange={handleChange}
                      className={errors.passwort ? styles.inputError : ''}
                    />
                    <button
                      type="button"
                      className={styles.togglePassword}
                      onClick={() => setShowPassword(!showPassword)}
                    >
                      {showPassword ? '🙈' : '👁'}
                    </button>
                  </div>
                  {errors.passwort && (
                    <span className={styles.fieldError}>{errors.passwort}</span>
                  )}
                </div>

                <div className={styles.formGroup}>
                  <label htmlFor="passwortWiederholen">
                    Passwort wiederholen <span className={styles.required}>*</span>
                  </label>
                  <div className={styles.inputWrapper}>
                    <span className={styles.inputIcon}><Lock size={16} /></span>
                    <input
                      id="passwortWiederholen"
                      name="passwortWiederholen"
                      type={showPassword ? 'text' : 'password'}
                      placeholder="Passwort wiederholen"
                      value={formData.passwortWiederholen}
                      onChange={handleChange}
                      className={errors.passwortWiederholen ? styles.inputError : ''}
                    />
                  </div>
                  {errors.passwortWiederholen && (
                    <span className={styles.fieldError}>{errors.passwortWiederholen}</span>
                  )}
                </div>

                {/* Datenschutz */}
                <div className={styles.checkboxGroup}>
                  <input
                    type="checkbox"
                    id="datenschutz"
                    name="datenschutz"
                    checked={formData.datenschutz}
                    onChange={handleChange}
                  />
                  <label htmlFor="datenschutz">
                    Ich akzeptiere die{' '}
                    <NavLink to="/datenschutz" className={styles.link}>
                      Datenschutzerklärung
                    </NavLink>{' '}
                    <span className={styles.required}>*</span>
                  </label>
                </div>
                {errors.datenschutz && (
                  <span className={styles.fieldError}>{errors.datenschutz}</span>
                )}

                {/* Buttons */}
                <div className={styles.stepButtons}>
                  <button
                    type="button"
                    className={`btn btn--outline ${styles.backBtn}`}
                    onClick={() => setStep(1)}
                  >
                    Zurück
                  </button>
                  <button
                    type="submit"
                    className={`btn btn--primary ${styles.submitBtn}`}
                  >
                    Konto erstellen
                  </button>
                </div>
              </>
            )}

          </form>

          <p className={styles.loginHint}>
            Bereits ein Konto?{' '}
            <NavLink to="/login" className={styles.link} onClick={onClose}>
              Jetzt anmelden
            </NavLink>
          </p>

        </div>
      </div>
    </div>
  );
}