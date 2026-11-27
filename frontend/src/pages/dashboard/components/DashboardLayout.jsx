import { Outlet } from 'react-router-dom';
import DashboardSidebar from './DashboardSidebar.jsx';
import './Dashboard.css';

export default function DashboardLayout() {
    return (
        <div className="dashboard container">
            <DashboardSidebar />
            <section className="dashboard__content">
                <Outlet />
            </section>
        </div>
    );
}
