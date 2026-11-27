import { createContext, useState, useContext } from 'react';

const ModalContext = createContext(null);

export function ModalProvider({ children }) {
  const [modal, setModal] = useState(null); // 'register' | 'login' | null

  const openRegister = () => setModal('register');
  const openLogin    = () => setModal('login');
  const closeModal   = () => setModal(null);

  return (
    <ModalContext.Provider value={{ modal, openRegister, openLogin, closeModal }}>
      {children}
    </ModalContext.Provider>
  );
}

export function useModal() {
  return useContext(ModalContext);
}