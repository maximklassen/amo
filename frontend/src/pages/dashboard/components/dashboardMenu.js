// Neuer Menüpunkt: Eintrag hier + Route in App.jsx. `path` ist relativ zu /mein-garten
export const DASHBOARD_BASE = '/mein-garten';

export const dashboardMenu = [
    { path: '',           label: 'Übersicht',  icon: '🏡' },
    { path: 'zaehler',    label: 'Zähler',     icon: '💧' },
    { path: 'rechnungen', label: 'Rechnungen', icon: '🧾' },
    { path: 'begehung',   label: 'Begehung',   icon: '📋' },
];
