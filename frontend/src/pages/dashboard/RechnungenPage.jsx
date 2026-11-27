import { useEffect, useRef, useState } from 'react';
import { EllipsisVertical, FileDown, Printer } from 'lucide-react';
import { getInvoices } from '../../api/api.js';
import DashboardPageHeader from './components/DashboardPageHeader.jsx';

const STATUS_LABELS = { offen: 'Offen', bezahlt: 'Bezahlt', storniert: 'Storniert' };

const dateFormat = new Intl.DateTimeFormat('de-DE');
const moneyFormat = new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' });
const numberFormat = new Intl.NumberFormat('de-DE', { maximumFractionDigits: 2 });

const formatDate = (date) => dateFormat.format(new Date(date));

function InvoiceMenu({ onPrint }) {
    const [open, setOpen] = useState(false);
    const ref = useRef(null);

    useEffect(() => {
        if (!open) return;
        const close = (event) => {
            if (!ref.current.contains(event.target)) setOpen(false);
        };
        document.addEventListener('mousedown', close);
        return () => document.removeEventListener('mousedown', close);
    }, [open]);

    function select() {
        setOpen(false);
        onPrint();
    }

    return (
        <div className="invoice-menu" ref={ref}>
            <button
                className="invoice-menu__toggle"
                type="button"
                aria-label="Weitere Aktionen"
                aria-expanded={open}
                onClick={() => setOpen(!open)}
            >
                <EllipsisVertical size={20} />
            </button>
            {open && (
                <div className="invoice-menu__list" role="menu">
                    <button type="button" role="menuitem" onClick={select}>
                        <Printer size={16} /> Drucken
                    </button>
                    {/* Gleicher Druckdialog – dort "Als PDF speichern" wählen */}
                    <button type="button" role="menuitem" onClick={select}>
                        <FileDown size={16} /> Als PDF speichern
                    </button>
                </div>
            )}
        </div>
    );
}

function InvoiceCard({ invoice }) {
    const ref = useRef(null);

    // Nur diese Karte drucken, siehe @media print in Dashboard.css
    function handlePrint() {
        ref.current.classList.add('invoice--print');
        window.print();
        ref.current.classList.remove('invoice--print');
    }

    return (
        <article className="invoice" ref={ref}>
            <header className="invoice__header">
                <div>
                    <h3>Rechnung {invoice.number}</h3>
                    <p className="invoice__meta">
                        Parzelle {invoice.parcel_number} · Jahr {invoice.year}
                    </p>
                </div>
                <span className={`invoice__status invoice__status--${invoice.status}`}>
                    {STATUS_LABELS[invoice.status]}
                </span>
            </header>

            <dl className="invoice__dates">
                <div>
                    <dt>Ausgestellt</dt>
                    <dd>{formatDate(invoice.issued_at)}</dd>
                </div>
                <div>
                    <dt>Fällig</dt>
                    <dd>{formatDate(invoice.due_at)}</dd>
                </div>
            </dl>

            <table className="invoice__items">
                <thead>
                    <tr>
                        <th>Position</th>
                        <th>Menge</th>
                        <th>Einzelpreis</th>
                        <th>Betrag</th>
                    </tr>
                </thead>
                <tbody>
                    {invoice.items.map((item) => (
                        <tr key={item.id}>
                            <td>{item.description}</td>
                            <td>
                                {numberFormat.format(item.quantity)} {item.unit}
                            </td>
                            <td>{moneyFormat.format(item.unit_price)}</td>
                            <td>{moneyFormat.format(item.amount)}</td>
                        </tr>
                    ))}
                </tbody>
                <tfoot>
                    <tr>
                        <th colSpan={3}>Gesamt</th>
                        <td>{moneyFormat.format(invoice.total)}</td>
                    </tr>
                </tfoot>
            </table>

            <footer className="invoice__actions">
                {invoice.status === 'offen' && (
                    // TODO: Modal zum Bezahlen öffnen
                    <button className="btn btn--primary btn--sm" type="button" onClick={() => {}}>
                        Bezahlen
                    </button>
                )}
                <InvoiceMenu onPrint={handlePrint} />
            </footer>
        </article>
    );
}

export default function RechnungenPage() {
    const [invoices, setInvoices] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        getInvoices()
            .then(setInvoices)
            .catch((err) => setError(err.message))
            .finally(() => setLoading(false));
    }, []);

    return (
        <>
            <DashboardPageHeader title="Rechnungen" subtitle="Deine Rechnungen zum Ansehen, Drucken und als PDF." />
            {loading && <p>Lade Rechnungen …</p>}
            {error && <p className="reading-form__error">{error}</p>}
            {!loading && !error && invoices.length === 0 && <p>Noch keine Rechnungen vorhanden.</p>}
            <div className="invoices">
                {invoices.map((invoice) => (
                    <InvoiceCard key={invoice.id} invoice={invoice} />
                ))}
            </div>
        </>
    );
}
