import './Navbar.css';
import { useState } from 'react';
import { Link, useNavigate } from "react-router-dom";
import logoHexagone from '../assets/Logohexagone.png';
import { useAuth } from '../context/AuthContext.jsx';

function Navbar() {
    const { token, user, logout } = useAuth();
    const navigate = useNavigate();
    const [open, setOpen] = useState(false);

    function handleLogout() {
        logout();
        navigate('/');
        setOpen(false);
    }

    function close() { setOpen(false); }

    const isStudent = user?.roles?.includes('ROLE_STUDENT');
    const isCompany = user?.roles?.includes('ROLE_COMPANY');
    const isStaff = user?.roles?.includes('ROLE_STAFF');

    return (
        <nav>
            <img className="logo-hexagone" src={logoHexagone} alt="Logo Hexagone"/>

            <button className="nav-hamburger" aria-label="Menu" onClick={() => setOpen(o => !o)}>
                <span className={`nav-ham-bar${open ? ' open' : ''}`}/>
                <span className={`nav-ham-bar${open ? ' open' : ''}`}/>
                <span className={`nav-ham-bar${open ? ' open' : ''}`}/>
            </button>

            <div className={`nav-links${open ? ' nav-links--open' : ''}`}>
                <Link to="/" onClick={close}>Accueil</Link>
                <Link to="/offers" onClick={close}>Offres</Link>
                <Link to="/eleves" onClick={close}>Élèves</Link>
                {token ? (
                    <>
                        {isStudent && (
                            <Link to="/profil/creer" onClick={close}>Mon Profil</Link>
                        )}
                        {isCompany && (
                            <>
                                <Link to="/swipe" onClick={close}>CV Finder</Link>
                                <Link to="/swipe/liked" onClick={close}>Mes CV</Link>
                            </>
                        )}
                        {isStaff && (
                            <Link to="/admin" onClick={close}>Admin</Link>
                        )}
                        <button className="nav-button nav-logout" onClick={handleLogout}>
                            Déconnexion
                        </button>
                    </>
                ) : (
                    <Link to="/login" className="nav-button" onClick={close}>Se connecter</Link>
                )}
            </div>
        </nav>
    );
}

export default Navbar;
