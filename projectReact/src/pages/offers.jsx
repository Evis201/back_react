import { useState, useEffect, useMemo } from 'react'
import { Link } from 'react-router-dom'
import './offers.css'

function Offers() {
  const [offers, setOffers] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [search, setSearch] = useState('')
  const [activeFilter, setActiveFilter] = useState('all')
  const [sortKey, setSortKey] = useState('latest')

  const filterOptions = [
    { key: 'all', label: 'Tous' },
    { key: 'title', label: 'Titre' },
    { key: 'company', label: 'Entreprise' },
    { key: 'location', label: 'Localisation' },
    { key: 'type', label: 'Type' },
  ]

  const sortOptions = [
    { key: 'latest', label: 'Plus récentes' },
    { key: 'salaryAsc', label: 'Salaire croissant' },
    { key: 'salaryDesc', label: 'Salaire décroissant' },
    { key: 'title', label: 'Titre A → Z' },
  ]

  useEffect(() => {
    const controller = new AbortController()

    fetch('/api/offers', { signal: controller.signal })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`Erreur ${response.status} lors du chargement des offres.`)
        }
        return response.json()
      })
      .then((payload) => {
        const data = payload?.data ?? payload
        const offersData = Array.isArray(data)
          ? data
          : Array.isArray(data?.items)
          ? data.items
          : []

        setOffers(offersData)
      })
      .catch((fetchError) => {
        if (fetchError.name !== 'AbortError') {
          setError(fetchError.message)
        }
      })
      .finally(() => {
        setLoading(false)
      })

    return () => controller.abort()
  }, [])

  const filteredOffers = useMemo(() => {
    const term = search.trim().toLowerCase()
    if (!term) {
      return offers
    }

    return offers.filter((offer) => {
      const title = offer.title?.toLowerCase() ?? ''
      const company = offer.company?.name?.toLowerCase() ?? ''
      const location = offer.location?.toLowerCase() ?? ''
      const type = offer.type?.toLowerCase() ?? ''
      const status = offer.status?.toLowerCase() ?? ''

      if (activeFilter === 'title') {
        return title.includes(term)
      }

      if (activeFilter === 'company') {
        return company.includes(term)
      }

      if (activeFilter === 'location') {
        return location.includes(term)
      }

      if (activeFilter === 'type') {
        return type.includes(term)
      }

      return [title, company, location, type, status].some((value) => value.includes(term))
    })
  }, [offers, search, activeFilter])

  const sortedOffers = useMemo(() => {
    const sorted = [...filteredOffers]

    return sorted.sort((a, b) => {
      if (sortKey === 'salaryAsc') {
        return (Number(a.salaryMin ?? 0) - Number(b.salaryMin ?? 0)) || (Number(a.salaryMax ?? 0) - Number(b.salaryMax ?? 0))
      }
      if (sortKey === 'salaryDesc') {
        return (Number(b.salaryMax ?? 0) - Number(a.salaryMax ?? 0)) || (Number(b.salaryMin ?? 0) - Number(a.salaryMin ?? 0))
      }
      if (sortKey === 'title') {
        return (a.title || '').localeCompare(b.title || '', 'fr', { sensitivity: 'base' })
      }

      const dateA = new Date(a.createdAt || a.startsAt || '').getTime() || 0
      const dateB = new Date(b.createdAt || b.startsAt || '').getTime() || 0
      return dateB - dateA
    })
  }, [filteredOffers, sortKey])

  const placeholderText =
    activeFilter === 'all'
      ? 'Rechercher...' 
      : `Rechercher par ${filterOptions.find((filter) => filter.key === activeFilter)?.label.toLowerCase()}...`

  return (
    <main className="offers-page">
      <section className="offers-hero">
        <div>
          <h1>Offres disponibles</h1>
          <p>Retrouvez toutes les offres publiées sur l'API et consultez les détails principaux d'un coup d'œil.</p>
        </div>
      </section>

      <div className="filter-bar">
        <div className="filter-buttons">
          {filterOptions.map((filter) => (
            <button
              key={filter.key}
              type="button"
              className={`filter-button ${activeFilter === filter.key ? 'active' : ''}`}
              onClick={() => setActiveFilter(filter.key)}
            >
              {filter.label}
            </button>
          ))}
        </div>
      </div>

      <div className="search-wrapper">
        <input
          type="search"
          className="offer-search"
          placeholder={placeholderText}
          value={search}
          onChange={(e) => setSearch(e.target.value)}
        />

        <div className="sort-wrapper">
          <label htmlFor="offer-sort">Trier :</label>
          <select
            id="offer-sort"
            className="sort-select"
            value={sortKey}
            onChange={(e) => setSortKey(e.target.value)}
          >
            {sortOptions.map((option) => (
              <option key={option.key} value={option.key}>
                {option.label}
              </option>
            ))}
          </select>
        </div>
      </div>

      {loading ? (
        <div className="offers-status">Chargement des offres...</div>
      ) : error ? (
        <div className="offers-status offers-error">Erreur : {error}</div>
      ) : sortedOffers.length === 0 ? (
        <div className="offers-status">Aucune offre trouvée pour cette recherche.</div>
      ) : (
        <section className="offers-grid">
          {sortedOffers.map((offer) => (
            <Link key={offer.id} to={`/offers/${offer.id}`} className="offer-card-link">
              <article className="offer-card">
                <header>
                  <h2>{offer.title || 'Titre non disponible'}</h2>
                  <span className="offer-type">{offer.type || 'Type inconnu'}</span>
                </header>
                <div className="offer-meta">
                  <span>{offer.company?.name ?? 'Entreprise inconnue'}</span>
                  <span>{offer.location ?? 'Localisation non renseignée'}</span>
                  <span>{offer.isRemote ? 'Télétravail possible' : 'Présentiel'}</span>
                </div>
                <p className="offer-description">
                  {offer.description ? offer.description.slice(0, 180) + (offer.description.length > 180 ? '…' : '') : 'Description non disponible.'}
                </p>
                <div className="offer-details">
                  {offer.salaryMin || offer.salaryMax ? (
                    <span>
                      Salaire : {offer.salaryMin ?? '—'}€ - {offer.salaryMax ?? '—'}€
                    </span>
                  ) : (
                    <span>Pas de salaire indiqué</span>
                  )}
                  <span>{offer.status}</span>
                </div>
              </article>
            </Link>
          ))}
        </section>
      )}
    </main>
  )
}

export default Offers
