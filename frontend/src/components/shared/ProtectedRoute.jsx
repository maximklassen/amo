import { Navigate, Outlet } from 'react-router-dom';
import { useAuth } from '@/hooks/useAuth.js';


export default function ProtectedRoute() {
    const { user, loading } = useAuth();

    if (loading) return <p>Laden…</p>; //TODO: Подумать как можно по другому
    if (!user) return <Navigate to="/login" replace />;

    return <Outlet />;

}