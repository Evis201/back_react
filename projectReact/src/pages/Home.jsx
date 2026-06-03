import { useEffect } from 'react';
import { Link } from "react-router-dom";
import "./Home.css";

function Home() {
  useEffect(() => {
    document.body.classList.add('no-scroll');
    return () => document.body.classList.remove('no-scroll');
  }, []);
  return (
    <main className="home-page">
      <section className="hero-section">
        <div className="hero-copy">
          <span className="hero-label">Plateforme étudiante</span>
          <h1>Découvrez les talents, écoles et domaines des élèves</h1>
          <p>
            Un espace clair pour explorer les profils des étudiants, suivre leurs projets,
            visualiser compétences et badges, et naviguer facilement entre chaque fiche.
          </p>
          <div className="hero-actions">
            <Link className="hero-button primary" to="/eleves">
              Voir les élèves
            </Link>
            <Link className="hero-button secondary" to="/info">
              À propos
            </Link>
          </div>
        </div>
        <div className="hero-highlight">
          <div className="feature-card">
            <h2>Recherche intelligente</h2>
            <p>Filtre par année, école, domaine et nom.</p>
          </div>
          <div className="feature-card">
            <h2>Profils détaillés</h2>
            <p>Fiches complètes avec badges, compétences et parcours.</p>
          </div>
          <div className="feature-card">
            <h2>Design moderne</h2>
            <p>Une interface simple et élégante pour faciliter la navigation.</p>
          </div>
        </div>
      </section>
    </main>
  );
}

export default Home;
