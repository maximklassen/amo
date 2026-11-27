export default function DashboardPageHeader({ title, subtitle, children }) {
    return (
        <header className="dashboard__page-header">
            <div>
                <h2>{title}</h2>
                {subtitle && <p>{subtitle}</p>}
            </div>
            {children && <div className="dashboard__page-actions">{children}</div>}
        </header>
    );
}
