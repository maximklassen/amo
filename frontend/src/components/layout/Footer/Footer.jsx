import { NavLink } from 'react-router-dom';
import { Leaf, Mail, Phone } from 'lucide-react';
import styles from './Footer.module.css';

export default function Footer() {
  return (
    <footer className={styles.footer}>
      <div className={styles.inner}>

        {/* Logo und Beschreibung */}
        <div className={styles.brand}>
          <NavLink to="/" className={styles.logo}>
            <div className={styles.logoIcon}>
              <Leaf size={20} strokeWidth={2} />
            </div>
            <span className={styles.logoName}>
              Parzellen<strong>Planer</strong>
            </span>
          </NavLink>
          <p className={styles.brandText}>
            Digitale Verwaltung für Kleingartenvereine.
            Gemeinsam grün, besser organisiert.
          </p>
        </div>

        {/* Navigation Links */}
        <div className={styles.links}>
          <h4 className={styles.linksTitle}>Navigation</h4>
          <NavLink to="/"          className={styles.link}>Startseite</NavLink>
          <NavLink to="/ueber-uns" className={styles.link}>Über uns</NavLink>
          <NavLink to="/kontakt"   className={styles.link}>Kontakt</NavLink>
          <NavLink to="/login"     className={styles.link}>Anmelden</NavLink>
          <NavLink to="/register"  className={styles.link}>Registrieren</NavLink>
        </div>

        {/* Kontakt */}
        <div className={styles.links}>
          <h4 className={styles.linksTitle}>Kontakt</h4>
          <a href="mailto:info@parzellenplaner.de" className={styles.link}>
            <Mail size={15} />
            info@parzellenplaner.de
          </a>
          <a href="tel:+4951112345678" className={styles.link}>
            <Phone size={15} />
            +49 511 123 456 78
          </a>
        </div>

      </div>

      {/* Copyright */}
      <div className={styles.bottom}>
        <p>© {new Date().getFullYear()} ParzellenPlaner. Alle Rechte vorbehalten.</p>
        <div className={styles.bottomLinks}>
          <NavLink to="/datenschutz" className={styles.bottomLink}>Datenschutz</NavLink>
          <NavLink to="/impressum"   className={styles.bottomLink}>Impressum</NavLink>
        </div>
      </div>
    </footer>
  );
}