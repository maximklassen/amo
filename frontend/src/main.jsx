import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { AuthProvider } from '@/context/AuthContext.jsx';
import App from "./App.jsx";
import './styles/main.css';
import { ModalProvider } from './context/ModalContext.jsx';

createRoot(document.getElementById('root')).render(
    <StrictMode>
        <BrowserRouter>
            <AuthProvider>
                <ModalProvider >
                    <App />
                </ModalProvider>
            </AuthProvider>
        </BrowserRouter>
    </StrictMode>
);
