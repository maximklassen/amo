import styles from './AccountCard.module.css';

/** Weiße Karte mit Überschrift für ein Formular im Konto-Bereich. */
export default function AccountCard({ title, description, children }) {
    return (
        <section className={styles.card}>
            <header className={styles.header}>
                <h2 className={styles.title}>{title}</h2>
                {description && <p className={styles.description}>{description}</p>}
            </header>
            {children}
        </section>
    );
}
