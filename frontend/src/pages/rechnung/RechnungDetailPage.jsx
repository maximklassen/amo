import { useParams } from 'react-router-dom';

export default function RechnungDetailPage() {
    const { id } = useParams();

    return (
        <div className="container">
            <h2>Rechnung #{id}</h2>
            <button className="btn btn--primary" onClick={() => window.print()}>
                Drucken / PDF
            </button>
        </div>
    );
}
