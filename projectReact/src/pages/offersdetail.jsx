import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import './offersdetail.css'

function OfferDetail() {
  const { id } = useParams()
  const [offer, setOffer] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
    if (!id) {
      return
    }

    setLoading(true)
    setError(null)

    fetch(`/api/offers/${id}`)
      .then((response) => {
        if (!response.ok) {
          throw new Error(`Erreur ${response.status} lors du chargement de l'offre.`)
        }
        return response.json()
      })
      .then((payload) => {
        const data = payload?.data ?? payload
        setOffer(data)
      })
      .catch((fetchError) => {
        setError(fetchError.message)
      })
      .finally(() => {
        setLoading(false)
      })
  }, [id])

  if (loading) {
    return (
      <main className="offer-detail-page">
        <div className="offer-detail-status">Chargement de l'offre...</div>
      </main>
    )
  }

  if (error || !offer) {
    return (
      <main className="offer-detail-page">
        <div className="offer-detail-status offer-detail-error">
          {error || "Offre introuvable."}
        </div>
        <Link to="/offers" className="detail-back">
          ← Retour aux offres
        </Link>
      </main>
    )
  }

  return (
    <main className="offer-detail-page">
      <Link to="/offers" className="detail-back">
        ← Retour aux offres
      </Link>

      <article className="offer-detail-card">
        <header className="offer-detail-header">
          <div>
            <span className="offer-detail-type">{offer.type || 'Type inconnu'}</span>
            <h1>{offer.title || 'Titre non disponible'}</h1>
            <p className="offer-detail-company">{offer.company?.name ?? 'Entreprise inconnue'}</p>
          </div>
          <div className="offer-detail-badges">
            <span className="detail-badge">{offer.location ?? 'Localisation non renseignée'}</span>
            <span className="detail-badge">{offer.isRemote ? 'Télétravail possible' : 'Présentiel'}</span>
            <span className="detail-badge">{offer.status || 'Statut inconnu'}</span>
          </div>
        </header>

        <section className="offer-detail-meta">
          <div>
            <h2>Description</h2>
            <p>{offer.description || 'Aucune description disponible.'}</p>
          </div>

          <div className="offer-detail-info">
            <div>
              <h3>Salaire</h3>
              <p>
                {offer.salaryMin || offer.salaryMax
                  ? `${offer.salaryMin ?? '—'} — ${offer.salaryMax ?? '—'}`
                  : 'Non renseigné'}
              </p>
            </div>
            <div>
              <h3>Début</h3>
              <p>{offer.startsAt ?? 'Non renseigné'}</p>
            </div>
            <div>
              <h3>Expiration</h3>
              <p>{offer.expiresAt ?? 'Non renseigné'}</p>
            </div>
            <div>
              <h3>Créée le</h3>
              <p>{offer.createdAt ? new Date(offer.createdAt).toLocaleDateString('fr-FR') : '—'}</p>
            </div>
            {offer.company?.website && (
              <div>
                <h3>Site</h3>
                <p>
                  <a href={offer.company.website} target="_blank" rel="noreferrer">
                    {offer.company.website}
                  </a>
                </p>
              </div>
            )}
          </div>
        </section>

        {offer.requiredSkills?.length > 0 && (
          <section className="offer-skills">
            <h2>Compétences requises</h2>
            <div className="skills-list">
              {offer.requiredSkills.map((skill) => (
                <span key={skill.id} className="skill-chip">
                  {skill.name}
                </span>
              ))}
            </div>
          </section>
        )}
      </article>
    </main>
  )
}

export default OfferDetail
