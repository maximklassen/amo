import { useId } from 'react';
import styles from './FormField.module.css';

/**
 * Beschriftetes Eingabefeld mit Hinweis- und Fehlertext.
 * `adornment` ist ein Element am rechten Rand des Feldes (z. B. der Button "Passwort anzeigen").
 */
export default function FormField({
    label,
    name,
    value,
    onChange,
    error,
    hint,
    required = false,
    adornment,
    type = 'text',
    ...inputProps
}) {
    const id = useId();
    const describedBy = error ? `${id}-error` : hint ? `${id}-hint` : undefined;

    return (
        <div className={styles.field}>
            <label htmlFor={id} className={styles.label}>
                {label}
                {required && <span className={styles.required} aria-hidden="true"> *</span>}
            </label>

            <div className={styles.control}>
                <input
                    id={id}
                    name={name}
                    type={type}
                    value={value}
                    onChange={onChange}
                    required={required}
                    aria-invalid={error ? 'true' : undefined}
                    aria-describedby={describedBy}
                    className={`${styles.input} ${error ? styles.inputError : ''} ${adornment ? styles.withAdornment : ''}`}
                    {...inputProps}
                />
                {adornment && <div className={styles.adornment}>{adornment}</div>}
            </div>

            {error ? (
                <p id={`${id}-error`} className={styles.error} role="alert">{error}</p>
            ) : hint ? (
                <p id={`${id}-hint`} className={styles.hint}>{hint}</p>
            ) : null}
        </div>
    );
}
