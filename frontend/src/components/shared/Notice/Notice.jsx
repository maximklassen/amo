import { CheckCircle2, AlertCircle } from 'lucide-react';
import styles from './Notice.module.css';

/** Meldung über einem Formular: type="success" oder type="error". Rendert nichts ohne Text. */
export default function Notice({ type = 'success', children }) {
    if (!children) return null;

    const Icon = type === 'success' ? CheckCircle2 : AlertCircle;

    return (
        <div
            className={`${styles.notice} ${type === 'success' ? styles.success : styles.error}`}
            role={type === 'success' ? 'status' : 'alert'}
        >
            <Icon size={18} strokeWidth={2} aria-hidden="true" />
            <span>{children}</span>
        </div>
    );
}
