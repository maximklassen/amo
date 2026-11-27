import { Routes, Route } from 'react-router-dom';
import { useModal } from '@/context/ModalContext.jsx';
import Modal from '@/components/ui/Modal.jsx';
import RegisterForm from '@/pages/auth/RegisterForm.jsx';

import ProtectedRoute from '@/components/shared/ProtectedRoute.jsx';
import Header from '@/components/layout/Header/Header.jsx';
import Footer from '@/components/layout/Footer/Footer.jsx';

import StartseitePage     from '@/pages/public/StartseitePage/StartseitePage.jsx';
import UeberUnsPage       from '@/pages/public/UeberUnsPage.jsx';
import KontaktPage        from '@/pages/public/KontaktPage.jsx';
import LoginPage          from '@/pages/auth/LoginPage.jsx';
import DashboardPage      from '@/pages/dashboard/DashboardPage.jsx';
import ParzellenPage      from '@/pages/parzellen/ParzellenPage.jsx';
import ParzellenDetailPage from '@/pages/parzellen/ParzellenDetailPage.jsx';
import WartelistePage     from '@/pages/warteliste/WartelistePage.jsx';
import GemeinschaftPage   from '@/pages/gemeinschaft/GemeinschaftPage.jsx';
import SchwarztesBrettPage from '@/pages/schwarzesbrett/SchwarztesBrettPage.jsx';
import ProfilPage         from '@/pages/profil/ProfilPage.jsx';
import EinstellungenPage  from '@/pages/einstellungen/EinstellungenPage.jsx';
import NotFoundPage       from '@/pages/NotFoundPage.jsx';



export default function App() {

  const { modal, closeModal } = useModal();

  return (
    <>
      <Header />
      <main>
        <Routes>
          <Route path="/"          element={<StartseitePage />} />
          <Route path="/ueber-uns" element={<UeberUnsPage />} />
          <Route path="/kontakt"   element={<KontaktPage />} />
          <Route path="/login"     element={<LoginPage />} />

          <Route element={<ProtectedRoute />}>
            <Route path="/dashboard"       element={<DashboardPage />} />
            <Route path="/parzellen"       element={<ParzellenPage />} />
            <Route path="/parzellen/:id"   element={<ParzellenDetailPage />} />
            <Route path="/warteliste"      element={<WartelistePage />} />
            <Route path="/gemeinschaft"    element={<GemeinschaftPage />} />
            <Route path="/schwarzes-brett" element={<SchwarztesBrettPage />} />
            <Route path="/profil"          element={<ProfilPage />} />
            <Route path="/einstellungen"   element={<EinstellungenPage />} />
          </Route>

          <Route path="*" element={<NotFoundPage />} />
        </Routes>
      </main>
      <Footer />

      {/* Register-Modal */}
      <Modal isOpen={modal === 'register'} onClose={closeModal}>
        <RegisterForm onClose={closeModal} />
      </Modal>
    </>
  );
}