import { useState, useEffect } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { useAuth } from '../context/AuthContext.jsx'
import './CreateProfile.css'

function CreateProfile() {
  const { token } = useAuth()
  const navigate = useNavigate()

  const [existingStudentId, setExistingStudentId] = useState(null)
  const [form, setForm] = useState({
    firstName: '',
    lastName: '',
    bio: '',
    avatarUrl: '',
    githubUrl: '',
    linkedinUrl: '',
    promotionYear: '',
    school: '',
    domain: '',
    studyYear: '',
  })
  const [cvFile, setCvFile] = useState(null)
  const [cvUrl, setCvUrl] = useState('')
  const [uploading, setUploading] = useState(false)
  const [loading, setLoading] = useState(false)
  const [fetchingProfile, setFetchingProfile] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
    if (!token) {
      setFetchingProfile(false)
      return
    }
    fetch('/api/students/me', {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
      .then((res) => {
        if (res.status === 404) return null
        if (!res.ok) throw new Error('Erreur lors du chargement du profil.')
        return res.json()
      })
      .then((json) => {
        if (!json) return
        const s = json.data
        setExistingStudentId(s.id)
        setForm({
          firstName: s.firstName ?? '',
          lastName: s.lastName ?? '',
          bio: s.bio ?? '',
          avatarUrl: s.avatarUrl ?? '',
          githubUrl: s.githubUrl ?? '',
          linkedinUrl: s.linkedinUrl ?? '',
          promotionYear: s.promotionYear != null ? String(s.promotionYear) : '',
          school: s.school ?? '',
          domain: s.domain ?? '',
          studyYear: s.studyYear != null ? String(s.studyYear) : '',
        })
        if (s.cvUrl) setCvUrl(s.cvUrl)
      })
      .catch((err) => setError(err.message))
      .finally(() => setFetchingProfile(false))
  }, [token])

  function handleChange(e) {
    const { name, value } = e.target
    setForm((f) => ({ ...f, [name]: value }))
  }

  async function handleCvChange(e) {
    const file = e.target.files[0]
    if (!file) return
    setCvFile(file)
    setUploading(true)
    setError(null)
    try {
      const data = new FormData()
      data.append('file', file)
      const res = await fetch('/api/upload/cv', {
        method: 'POST',
        headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
        body: data,
      })
      const json = await res.json()
      if (!res.ok) throw new Error(json.error || 'Erreur upload CV.')
      setCvUrl(json.url)
    } catch (err) {
      setError(err.message)
      setCvFile(null)
    } finally {
      setUploading(false)
    }
  }

  async function handleSubmit(e) {
    e.preventDefault()
    setError(null)
    setLoading(true)
    try {
      const body = {
        firstName: form.firstName,
        lastName: form.lastName,
        bio: form.bio || null,
        avatarUrl: form.avatarUrl || null,
        githubUrl: form.githubUrl || null,
        linkedinUrl: form.linkedinUrl || null,
        promotionYear: form.promotionYear ? parseInt(form.promotionYear) : null,
        school: form.school || null,
        domain: form.domain || null,
        studyYear: form.studyYear ? parseInt(form.studyYear) : null,
        cvUrl: cvUrl || null,
        skills: [],
        projects: [],
      }

      const isUpdate = existingStudentId !== null
      const url = isUpdate ? `/api/students/${existingStudentId}` : '/api/students'
      const method = isUpdate ? 'PATCH' : 'POST'

      const res = await fetch(url, {
        method,
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify(body),
      })
      const json = await res.json()
      if (!res.ok) {
        const msg = json.details
          ? json.details.map((d) => d.message).join(' | ')
          : (json.error || 'Erreur lors de la sauvegarde du profil.')
        throw new Error(msg)
      }
      const id = json.data?.id ?? existingStudentId
      navigate(id ? `/eleves/${id}` : '/eleves')
    } catch (err) {
      setError(err.message)
    } finally {
      setLoading(false)
    }
  }

  if (!token) {
    return (
      <main className="create-profile-page">
        <p>Vous devez être connecté pour créer un profil.</p>
        <Link to="/login">Se connecter</Link>
      </main>
    )
  }

  if (fetchingProfile) {
    return (
      <main className="create-profile-page">
        <p>Chargement...</p>
      </main>
    )
  }

  return (
    <main className="create-profile-page">
      <div className="create-profile-container">
        <h1>{existingStudentId ? 'Modifier mon profil' : 'Créer mon profil'}</h1>

        <form className="profile-form" onSubmit={handleSubmit}>
          <div className="form-section">
            <h2>Informations personnelles</h2>
            <div className="field-row">
              <div className="field">
                <label>Prénom *</label>
                <input name="firstName" value={form.firstName} onChange={handleChange} required className="input" placeholder="Prénom" />
              </div>
              <div className="field">
                <label>Nom *</label>
                <input name="lastName" value={form.lastName} onChange={handleChange} required className="input" placeholder="Nom" />
              </div>
            </div>
            <div className="field">
              <label>Bio</label>
              <textarea name="bio" value={form.bio} onChange={handleChange} className="input textarea" placeholder="Parlez de vous..." rows={4} />
            </div>
          </div>

          <div className="form-section">
            <h2>Formation</h2>
            <div className="field-row">
              <div className="field">
                <label>École</label>
                <input name="school" value={form.school} onChange={handleChange} className="input" placeholder="Ex: École Hexagone" />
              </div>
              <div className="field">
                <label>Domaine</label>
                <input name="domain" value={form.domain} onChange={handleChange} className="input" placeholder="Ex: Développement web" />
              </div>
            </div>
            <div className="field-row">
              <div className="field">
                <label>Année de promotion</label>
                <input name="promotionYear" type="number" value={form.promotionYear} onChange={handleChange} className="input" placeholder="Ex: 2025" min="2000" max="2100" />
              </div>
              <div className="field">
                <label>Année d'études</label>
                <select name="studyYear" value={form.studyYear} onChange={handleChange} className="input">
                  <option value="">— Choisir —</option>
                  <option value="1">1ère année</option>
                  <option value="2">2ème année</option>
                  <option value="3">3ème année</option>
                  <option value="4">4ème année</option>
                  <option value="5">5ème année</option>
                </select>
              </div>
            </div>
          </div>

          <div className="form-section">
            <h2>Liens & CV</h2>
            <div className="field">
              <label>Photo de profil (URL)</label>
              <input name="avatarUrl" type="url" value={form.avatarUrl} onChange={handleChange} className="input" placeholder="https://..." />
            </div>
            <div className="field-row">
              <div className="field">
                <label>GitHub</label>
                <input name="githubUrl" type="url" value={form.githubUrl} onChange={handleChange} className="input" placeholder="https://github.com/..." />
              </div>
              <div className="field">
                <label>LinkedIn</label>
                <input name="linkedinUrl" type="url" value={form.linkedinUrl} onChange={handleChange} className="input" placeholder="https://linkedin.com/in/..." />
              </div>
            </div>
            <div className="field">
              <label>CV (PDF ou Word, max 5 Mo)</label>
              <input type="file" accept=".pdf,.doc,.docx" onChange={handleCvChange} className="input file-input" />
              {uploading && <span className="upload-status">Envoi en cours...</span>}
              {cvUrl && !uploading && <span className="upload-status upload-ok">CV uploadé</span>}
            </div>
          </div>

          {error && <p className="form-error">{error}</p>}

          <button type="submit" className="primary-btn" disabled={loading || uploading}>
            {loading ? 'Sauvegarde...' : existingStudentId ? 'Mettre à jour' : 'Créer mon profil'}
          </button>
        </form>
      </div>
    </main>
  )
}

export default CreateProfile
