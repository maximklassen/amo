import { NavLink } from 'react-router-dom';
import { DASHBOARD_BASE, dashboardMenu } from './dashboardMenu.js';

export default function DashboardSidebar() {
    return (
        <aside className="dashboard__sidebar">
            <nav aria-label="Dashboard-Navigation">
                <ul className="dashboard__menu">
                    {dashboardMenu.map(({ path, label, icon }) => (
                        <li key={path || 'index'}>
                            <NavLink
                                to={path ? `${DASHBOARD_BASE}/${path}` : DASHBOARD_BASE}
                                // ohne `end` wäre die Übersicht auf jeder Unterseite aktiv
                                end={!path}
                                className={({ isActive }) =>
                                    'dashboard__link' + (isActive ? ' dashboard__link--active' : '')
                                }
                            >
                                <span className="dashboard__icon" aria-hidden="true">{icon}</span>
                                {label}
                            </NavLink>
                        </li>
                    ))}
                </ul>
            </nav>
        </aside>
    );
}
