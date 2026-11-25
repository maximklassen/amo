import { createContext, useEffect, useState } from 'react';
import { me } from '../api/api';


export const AuthContext = createContext(null);

export function AuthProvider({ children }) {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        me()
            .then((user) => setUser(user))
            .catch(() => setUser(null))
            .finally(() => setLoading(false))
    }, []);

    const login = (userData) => setUser(userData);
    const logout = () => setUser(null);

    return (
        <AuthContext.Provider value={{ user, loading, login, logout }}>
            { children }
        </AuthContext.Provider>
    );
}
