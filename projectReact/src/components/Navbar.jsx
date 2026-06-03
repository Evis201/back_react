import './Navbar.css';
import { Link, useNavigate } from "react-router-dom";
import logoHexagone from '../assets/Logohexagone.png';
import { useAuth } from '../context/AuthContext.jsx';

function Navbar() {
    const { token, user, logout } = useAuth();
    const navigate = useNavigate();

    function handleLogout() {
        logout();
        navigate('/');
    }

    const isStudent = user?.roles?.includes('ROLE_STUDENT');

    return (
        <nav>
            <img className="logo-hexagone" src={logoHexagone} alt="Logo Hexagone"/>

            <div>
                <Link to="/">Accueil</Link>
                <Link to="/offers">Offres</Link>
                <Link to="/eleves">Élèves</Link>
                {token ? (
                    <>
                        {isStudent && (
                            <Link to="/profil/creer">Mon Profil</Link>
                        )}
                        <button className="nav-button nav-logout" onClick={handleLogout}>
                            Déconnexion
                        </button>
                    </>
                ) : (
                    <Link to="/login" className="nav-button">Se connecter</Link>
                )}
            </div>
        </nav>
    );
}

export default Navbar;
