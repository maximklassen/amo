import { useModal } from '@/context/ModalContext.jsx';
import { useState } from 'react';
import { NavLink, useNavigate } from 'react-router-dom';
import { LogIn, UserPlus, Menu, X, Leaf } from 'lucide-react';
import { useAuth } from '@/hooks/useAuth.js';
import styles from './Header.module.css';

export default function Header () {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [menuOpen, setMenuOpen] = useState(false);

  const { openRegister } = useModal();

  const handleLogout = () => {
    logout();
    navigate('/');
  };

  return (
    <header className={styles.header}>
      <div className={styles.inner}>

        {/* Logo */}
        <NavLink to="/" className={styles.logo}>
          <div className={styles.logoIcon}>
            <Leaf size={22} strokeWidth={2} />
          </div>
          <div className={styles.logoText}>
            <span className={styles.logoName}>
              Parzellen<strong>Planer</strong>
            </span>
            <span className={styles.logoTagline}>
              Gemeinsam grün. Besser organisiert.
            </span>
          </div>
        </NavLink>

        {/* Desktop Navigation */}
        <nav className={styles.nav}>
          {!user ? (
            /* Public menu */
            <>
              <NavLink
                to="/"
                className={({ isActive }) =>
                  `${styles.navLink} ${isActive ? styles.navLinkActive : ''}`
                }
              >
                Startseite
              </NavLink>
              <NavLink
                to="/ueber-uns"
                className={({ isActive }) =>
                  `${styles.navLink} ${isActive ? styles.navLinkActive : ''}`
                }
              >
                Über uns
              </NavLink>
              <NavLink
                to="/kontakt"
                className={({ isActive }) =>
                  `${styles.navLink} ${isActive ? styles.navLinkActive : ''}`
                }
              >
                Kontakt
              </NavLink>
            </>
          ) : (
            /* Menu after logging in */
            <>
              <NavLink
                to="/dashboard"
                className={({ isActive }) =>
                  `${styles.navLink} ${isActive ? styles.navLinkActive : ''}`
                }
              >
                Dashboard
              </NavLink>
              <NavLink
                to="/parzellen"
                className={({ isActive }) =>
                  `${styles.navLink} ${isActive ? styles.navLinkActive : ''}`
                }
              >
                Parzellen
              </NavLink>
              <NavLink
                to="/gemeinschaft"
                className={({ isActive }) =>
                  `${styles.navLink} ${isActive ? styles.navLinkActive : ''}`
                }
              >
                Gemeinschaft
              </NavLink>
              <NavLink
                to="/schwarzes-brett"
                className={({ isActive }) =>
                  `${styles.navLink} ${isActive ? styles.navLinkActive : ''}`
                }
              >
                Schwarzes Brett
              </NavLink>
            </>
          )}
        </nav>

        {/* Auth buttons */}
        <div className={styles.actions}>
          {!user ? (
            <>
              <NavLink to="/login" className={styles.btnLogin}>
                <LogIn size={18} strokeWidth={2} />
                <span>Anmelden</span>
              </NavLink>
              <button onClick={openRegister} className={styles.btnRegister}>
                <UserPlus size={18} strokeWidth={2} />
                <span>Registrieren</span>
              </button>
            </>
          ) : (
            <div className={styles.userMenu}>
              <span className={styles.userName}>
                {user.vorname}
              </span>
              <button
                onClick={handleLogout}
                className={styles.btnLogout}
              >
                Abmelden
              </button>
            </div>
          )}
        </div>

        {/* Burger menu */}
        <button
          className={styles.burger}
          onClick={() => setMenuOpen(!menuOpen)}
          aria-label="Menü öffnen"
        >
          {menuOpen ? <X size={24} /> : <Menu size={24} />}
        </button>

      </div>

      {/* Mobile menu */}
      {menuOpen && (
        <div className={styles.mobileMenu}>
          {!user ? (
            <>
              <NavLink to="/"          onClick={() => setMenuOpen(false)} className={styles.mobileLink}>Startseite</NavLink>
              <NavLink to="/ueber-uns" onClick={() => setMenuOpen(false)} className={styles.mobileLink}>Über uns</NavLink>
              <NavLink to="/kontakt"   onClick={() => setMenuOpen(false)} className={styles.mobileLink}>Kontakt</NavLink>
              <div className={styles.mobileDivider} />
              <NavLink to="/login"    onClick={() => setMenuOpen(false)} className={styles.mobileLink}>Anmelden</NavLink>
              <NavLink to="/register" onClick={() => setMenuOpen(false)} className={styles.mobileLink}>Registrieren</NavLink>
            </>
          ) : (
            <>
              <NavLink to="/dashboard"      onClick={() => setMenuOpen(false)} className={styles.mobileLink}>Dashboard</NavLink>
              <NavLink to="/parzellen"      onClick={() => setMenuOpen(false)} className={styles.mobileLink}>Parzellen</NavLink>
              <NavLink to="/gemeinschaft"   onClick={() => setMenuOpen(false)} className={styles.mobileLink}>Gemeinschaft</NavLink>
              <NavLink to="/schwarzes-brett" onClick={() => setMenuOpen(false)} className={styles.mobileLink}>Schwarzes Brett</NavLink>
              <div className={styles.mobileDivider} />
              <button onClick={handleLogout} className={styles.mobileLink}>Abmelden</button>
            </>
          )}
        </div>
      )}
    </header>
  );
}