import { NavLink } from 'react-router-dom';
import { Leaf, Users, Calendar, ArrowRight, CheckCircle } from 'lucide-react';
import styles from './StartseitePage.module.css';

/* Feature-Daten */
const FEATURES = [
  {
    icon: <Leaf size={28} strokeWidth={1.75} />,
    title: 'Parzellen verwalten',
    text: 'Übersichtliche digitale Verwaltung aller Gartenparzellen mit Status und Zuordnung.',
  },
  {
    icon: <Users size={28} strokeWidth={1.75} />,
    title: 'Gemeinschaft stärken',
    text: 'Gemeinschaftsarbeit planen, Stunden erfassen und gemeinsam mehr erreichen.',
  },
  {
    icon: <Calendar size={28} strokeWidth={1.75} />,
    title: 'Zeit sparen',
    text: 'Weniger Papier, weniger Aufwand — mehr Zeit für das Wesentliche im Grünen.',
  },
];

/* Vorteile-Liste */
const BENEFITS = [
  'Digitale Warteliste für freie Parzellen',
  'Automatische Jahresabrechnung',
  'Schwarzes Brett für Ankündigungen',
  'Buchung von Vereinsheim und Geräten',
];

export default function StartseitePage() {
  return (
    <div className={styles.page}>

      {/* Hero-Bereich */}
      <section className={styles.hero}>
        <div className={styles.heroInner}>
          <div className={styles.heroBadge}>
            <Leaf size={14} />
            <span>Für Kleingartenvereine</span>
          </div>

          <h1 className={styles.heroTitle}>
            Gemeinsam grün.<br />
            <span className={styles.heroTitleAccent}>Besser organisiert.</span>
          </h1>

          <p className={styles.heroText}>
            ParzellenPlaner digitalisiert die Verwaltung Ihres Kleingartenvereins —
            von der Warteliste bis zur Jahresabrechnung, alles an einem Ort.
          </p>

          <div className={styles.heroActions}>
            <NavLink to="/register" className={`btn btn--primary btn--lg ${styles.heroBtnPrimary}`}>
              Jetzt registrieren
              <ArrowRight size={18} />
            </NavLink>
            <NavLink to="/ueber-uns" className={`btn btn--outline btn--lg ${styles.heroBtnOutline}`}>
              Mehr erfahren
            </NavLink>
          </div>
        </div>

        {/* Dekoratives Element */}
        <div className={styles.heroDecor} aria-hidden="true">
          <div className={styles.heroDecorCircle} />
          <div className={styles.heroDecorLeaf}>🌿</div>
        </div>
      </section>

      {/* Features */}
      <section className={styles.features}>
        <div className={styles.sectionInner}>
          <h2 className={styles.sectionTitle}>Alles was Ihr Verein braucht</h2>
          <p className={styles.sectionText}>
            Eine Plattform für alle Aufgaben rund um Ihren Kleingartenverein.
          </p>

          <div className={styles.featureGrid}>
            {FEATURES.map((feature) => (
              <div key={feature.title} className={styles.featureCard}>
                <div className={styles.featureIcon}>{feature.icon}</div>
                <h3 className={styles.featureTitle}>{feature.title}</h3>
                <p className={styles.featureText}>{feature.text}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Vorteile */}
      <section className={styles.benefits}>
        <div className={styles.sectionInner}>
          <div className={styles.benefitsGrid}>

            <div className={styles.benefitsContent}>
              <h2 className={styles.sectionTitle}>Warum ParzellenPlaner?</h2>
              <p className={styles.sectionText}>
                Schluss mit Excel-Tabellen und Papierlisten.
                Verwalten Sie Ihren Verein einfach, transparent und digital.
              </p>
              <ul className={styles.benefitsList}>
                {BENEFITS.map((benefit) => (
                  <li key={benefit} className={styles.benefitsItem}>
                    <CheckCircle size={20} strokeWidth={2} />
                    <span>{benefit}</span>
                  </li>
                ))}
              </ul>
              <NavLink to="/register" className="btn btn--primary">
                Kostenlos starten
              </NavLink>
            </div>

            {/* Statistik-Karte */}
            <div className={styles.statsCard}>
              <div className={styles.statItem}>
                <span className={styles.statNumber}>120+</span>
                <span className={styles.statLabel}>Parzellen verwaltet</span>
              </div>
              <div className={styles.statDivider} />
              <div className={styles.statItem}>
                <span className={styles.statNumber}>300+</span>
                <span className={styles.statLabel}>Aktive Mitglieder</span>
              </div>
              <div className={styles.statDivider} />
              <div className={styles.statItem}>
                <span className={styles.statNumber}>100%</span>
                <span className={styles.statLabel}>Papierlos</span>
              </div>
            </div>

          </div>
        </div>
      </section>

      {/* CTA */}
      <section className={styles.cta}>
        <div className={styles.sectionInner}>
          <h2 className={styles.ctaTitle}>Bereit loszulegen?</h2>
          <p className={styles.ctaText}>
            Registrieren Sie sich jetzt und bringen Sie Ihren Verein ins digitale Zeitalter.
          </p>
          <NavLink to="/register" className="btn btn--primary btn--lg">
            Jetzt kostenlos registrieren
            <ArrowRight size={18} />
          </NavLink>
        </div>
      </section>

    </div>
  );
}