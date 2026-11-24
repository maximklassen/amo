import { BrowserRouter, Routes, Route, Link } from "react-router-dom";
import Home from "./pages/home/Home";
import Login from "./pages/login/Login";
import Register from "./pages/register/Register";
import Dashboard from "./pages/dashboard/Dashboard";
import Zaehler from "./pages/zaehler/Zaehler";
import Rechnungen from "./pages/rechnungen/Rechnungen";
import Begehung from "./pages/begehung/Begehung";


function App() {
    return (
        <BrowserRouter>
            <nav className="app-nav">
                <Link to="/">Home</Link>
                <Link to="/login">Login</Link>
                <Link to="/register">Register</Link>

                <Link to="/mein-garten">Dashboard</Link>
                <Link to="/mein-garten/zaehler">Zaehler</Link>
                <Link to="/mein-garten/rechnungen">Rechnungen</Link>
                <Link to="/mein-garten/begehung">Begehung</Link>
            </nav>

            <Routes>
                <Route path="/" element={<Home />} />
                <Route path="/login" element={<Login  />} />
                <Route path="/register" element={<Register />} />

                <Route path="/mein-garten" element={<Dashboard />} />
                <Route path="/mein-garten/zaehler" element={<Zaehler />} />
                <Route path="/mein-garten/rechnungen" element={<Rechnungen />} />
                <Route path="/mein-garten/begehung" element={<Begehung />} />
            </Routes>
        </BrowserRouter>
    );
}

export default App;
