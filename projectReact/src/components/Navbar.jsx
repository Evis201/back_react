import './Navbar.css';
import { Link } from "react-router-dom";
import logoHexagone from '../assets/Logohexagone.png';

function Navbar() {
    return (
        <nav>
            <img className="logo-hexagone" src={logoHexagone} alt="Logo Hexagone"/>

            <div>
                <Link to="/">Accueil</Link>
                <Link to="/offers">Offres</Link>
                <Link to="/eleves">Élèves</Link>
                <Link to="/login" className="nav-button">Se connecter</Link>
            </div>
        </nav>
    );
}

export default Navbar;