import { createContext, useEffect, useState } from 'react';
import { me, login as apiLogin, logout as apiLogout, register as apiRegister } from '../api/api';


export const AuthContext = createContext(null);

export function AuthProvider({ children }) {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);

    // Beim Start pruefen ob Session noch aktiv ist
    useEffect(() => {
        me()
            .then(setUser)
            .catch(() => setUser(null))
            .finally(() => setLoading(false));
    }, []);

    const login = async (email, password) => {
        const userData = await apiLogin({ email, password: password });
        setUser(userData);
        return userData;
    }

    const logout = async () => {
        await apiLogout();
        setUser(null);
    }

    const register = async () => {
        const userData = await apiRegister(formData);
        setUser(userData);
        return userData;
    }

    if (loading) return null;

    return (
        <AuthContext.Provider value={{ user, login, logout, register }}>
            { children }
        </AuthContext.Provider>
    );
}
