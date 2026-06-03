import { useEffect, useState, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext.jsx';
import './AdminPage.css';

function adminFetch(path, token, opts = {}) {
    return fetch(`/api/admin${path}`, {
        ...opts,
        headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${token}`,
            ...(opts.headers ?? {}),
        },
    });
}

function formatDate(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('fr-FR');
}

// ── Dashboard ─────────────────────────────────────────────────────────────────

function DashboardPanel({ token }) {
    const [stats, setStats] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        adminFetch('/stats', token)
            .then(r => r.json())
            .then(d => setStats(d.data))
            .finally(() => setLoading(false));
    }, [token]);

    if (loading) return <div className="admin-loading">Chargement…</div>;
    if (!stats) return <div className="admin-empty">Erreur lors du chargement.</div>;

    return (
        <>
            <div className="admin-stats-grid">
                <div className="admin-stat-card">
                    <div className="admin-stat-value">{stats.users.total}</div>
                    <div className="admin-stat-label">Utilisateurs</div>
                    <div className="admin-stat-sub">{stats.users.staff} staff · {stats.users.students} étudiants · {stats.users.companies} entreprises</div>
                </div>
                <div className="admin-stat-card">
                    <div className="admin-stat-value">{stats.students.total}</div>
                    <div className="admin-stat-label">Profils étudiants</div>
                    <div className="admin-stat-sub">{stats.students.visible} visibles · {stats.students.hidden} masqués</div>
                </div>
                <div className="admin-stat-card">
                    <div className="admin-stat-value">{stats.companies.total}</div>
                    <div className="admin-stat-label">Entreprises</div>
                </div>
                <div className="admin-stat-card">
                    <div className="admin-stat-value">{stats.offers.total}</div>
                    <div className="admin-stat-label">Offres</div>
                    <div className="admin-stat-sub">{stats.offers.published} publiées · {stats.offers.draft} brouillons · {stats.offers.closed} fermées</div>
                </div>
                <div className="admin-stat-card">
                    <div className="admin-stat-value">{stats.swipes.total}</div>
                    <div className="admin-stat-label">Swipes totaux</div>
                    <div className="admin-stat-sub">{stats.swipes.likes} likes · {stats.swipes.passes} passes</div>
                </div>
            </div>
        </>
    );
}

// ── Users ─────────────────────────────────────────────────────────────────────

function UsersPanel({ token, currentUserEmail }) {
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [page, setPage] = useState(1);
    const [meta, setMeta] = useState(null);
    const [search, setSearch] = useState('');
    const [searchInput, setSearchInput] = useState('');

    const fetchUsers = useCallback(() => {
        setLoading(true);
        const params = new URLSearchParams({ page, limit: 25 });
        if (search) params.set('search', search);
        adminFetch(`/users?${params}`, token)
            .then(r => r.json())
            .then(d => { setUsers(d.data ?? []); setMeta(d.meta ?? null); })
            .finally(() => setLoading(false));
    }, [token, page, search]);

    useEffect(() => { fetchUsers(); }, [fetchUsers]);

    async function handleRoleChange(id, role) {
        const res = await adminFetch(`/users/${id}/role`, token, {
            method: 'PATCH',
            body: JSON.stringify({ role }),
        });
        if (res.ok) {
            const d = await res.json();
            setUsers(prev => prev.map(u => u.id === id ? d.data : u));
        }
    }

    async function handleVerifyToggle(id, current) {
        const res = await adminFetch(`/users/${id}/verify`, token, {
            method: 'PATCH',
            body: JSON.stringify({ isVerified: !current }),
        });
        if (res.ok) {
            const d = await res.json();
            setUsers(prev => prev.map(u => u.id === id ? d.data : u));
        }
    }

    async function handleDelete(id) {
        if (!window.confirm('Supprimer cet utilisateur ? Cette action est irréversible.')) return;
        const res = await adminFetch(`/users/${id}`, token, { method: 'DELETE' });
        if (res.ok) setUsers(prev => prev.filter(u => u.id !== id));
    }

    function handleSearch(e) {
        e.preventDefault();
        setSearch(searchInput);
        setPage(1);
    }

    return (
        <>
            <form className="admin-toolbar" onSubmit={handleSearch}>
                <input
                    className="admin-search-input"
                    placeholder="Rechercher par email…"
                    value={searchInput}
                    onChange={e => setSearchInput(e.target.value)}
                />
                <button type="submit" className="admin-btn admin-btn-primary">Rechercher</button>
            </form>
            <div className="admin-table-wrapper">
                <table className="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Rôles</th>
                            <th>Vérifié</th>
                            <th>Type</th>
                            <th>Créé</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            <tr><td colSpan={7} className="admin-loading">Chargement…</td></tr>
                        ) : users.length === 0 ? (
                            <tr><td colSpan={7} className="admin-empty">Aucun utilisateur.</td></tr>
                        ) : users.map(u => {
                            const isStaff = u.roles.includes('ROLE_STAFF');
                            const isSelf = u.email === currentUserEmail;
                            const storedRole = u.roles.find(r => r !== 'ROLE_USER') ?? 'ROLE_USER';

                            return (
                                <tr key={u.id}>
                                    <td>{u.id}</td>
                                    <td>{u.email}</td>
                                    <td>
                                        {u.roles.filter(r => r !== 'ROLE_USER').map(r => (
                                            <span key={r} className={`role-badge${r === 'ROLE_STAFF' ? ' staff' : ''}`}>
                                                {r.replace('ROLE_', '')}
                                            </span>
                                        ))}
                                    </td>
                                    <td>
                                        <span className={u.isVerified ? 'verified-yes' : 'verified-no'}>
                                            {u.isVerified ? '✓ Oui' : 'Non'}
                                        </span>
                                    </td>
                                    <td>{u.profileType}</td>
                                    <td>{formatDate(u.createdAt)}</td>
                                    <td>
                                        {!isStaff && (
                                            <select
                                                className="admin-select"
                                                value={storedRole}
                                                onChange={e => handleRoleChange(u.id, e.target.value)}
                                            >
                                                <option value="ROLE_STUDENT">STUDENT</option>
                                                <option value="ROLE_COMPANY">COMPANY</option>
                                            </select>
                                        )}
                                        {' '}
                                        {!isStaff && (
                                            <button
                                                className="admin-btn admin-btn-secondary"
                                                onClick={() => handleVerifyToggle(u.id, u.isVerified)}
                                            >
                                                {u.isVerified ? 'Dés-vérifier' : 'Vérifier'}
                                            </button>
                                        )}
                                        {' '}
                                        <button
                                            className="admin-btn admin-btn-danger"
                                            disabled={isSelf || isStaff}
                                            onClick={() => handleDelete(u.id)}
                                        >
                                            Supprimer
                                        </button>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
            <Paginator meta={meta} page={page} setPage={setPage} />
        </>
    );
}

// ── Students ──────────────────────────────────────────────────────────────────

function StudentsPanel({ token }) {
    const [students, setStudents] = useState([]);
    const [loading, setLoading] = useState(true);
    const [page, setPage] = useState(1);
    const [meta, setMeta] = useState(null);
    const [search, setSearch] = useState('');
    const [searchInput, setSearchInput] = useState('');
    const [editingScore, setEditingScore] = useState({});

    const fetchStudents = useCallback(() => {
        setLoading(true);
        const params = new URLSearchParams({ page, limit: 25 });
        if (search) params.set('search', search);
        adminFetch(`/students?${params}`, token)
            .then(r => r.json())
            .then(d => { setStudents(d.data ?? []); setMeta(d.meta ?? null); })
            .finally(() => setLoading(false));
    }, [token, page, search]);

    useEffect(() => { fetchStudents(); }, [fetchStudents]);

    async function handleVisibilityToggle(id, current) {
        const res = await adminFetch(`/students/${id}/visibility`, token, {
            method: 'PATCH',
            body: JSON.stringify({ isVisible: !current }),
        });
        if (res.ok) {
            setStudents(prev => prev.map(s => s.id === id ? { ...s, isVisible: !current } : s));
        }
    }

    async function handleScoreSubmit(id) {
        const score = editingScore[id];
        if (score === undefined) return;
        const res = await adminFetch(`/students/${id}/score`, token, {
            method: 'PATCH',
            body: JSON.stringify({ score: parseInt(score, 10) }),
        });
        if (res.ok) {
            setStudents(prev => prev.map(s => s.id === id ? { ...s, score: parseInt(score, 10) } : s));
            setEditingScore(prev => { const n = { ...prev }; delete n[id]; return n; });
        }
    }

    async function handleDelete(id) {
        if (!window.confirm('Supprimer ce profil étudiant ? Cette action est irréversible.')) return;
        const res = await adminFetch(`/students/${id}`, token, { method: 'DELETE' });
        if (res.ok) setStudents(prev => prev.filter(s => s.id !== id));
    }

    function handleSearch(e) {
        e.preventDefault();
        setSearch(searchInput);
        setPage(1);
    }

    return (
        <>
            <form className="admin-toolbar" onSubmit={handleSearch}>
                <input
                    className="admin-search-input"
                    placeholder="Rechercher par nom ou email…"
                    value={searchInput}
                    onChange={e => setSearchInput(e.target.value)}
                />
                <button type="submit" className="admin-btn admin-btn-primary">Rechercher</button>
            </form>
            <div className="admin-table-wrapper">
                <table className="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>École</th>
                            <th>Domaine</th>
                            <th>Promo</th>
                            <th>Score</th>
                            <th>Visible</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            <tr><td colSpan={9} className="admin-loading">Chargement…</td></tr>
                        ) : students.length === 0 ? (
                            <tr><td colSpan={9} className="admin-empty">Aucun étudiant.</td></tr>
                        ) : students.map(s => (
                            <tr key={s.id}>
                                <td>{s.id}</td>
                                <td>{s.firstName} {s.lastName}</td>
                                <td>{s.email}</td>
                                <td>{s.school ?? '—'}</td>
                                <td>{s.domain ?? '—'}</td>
                                <td>{s.promotionYear ?? '—'}</td>
                                <td>
                                    <input
                                        className="admin-score-input"
                                        type="number"
                                        min={0}
                                        value={editingScore[s.id] !== undefined ? editingScore[s.id] : s.score}
                                        onChange={e => setEditingScore(prev => ({ ...prev, [s.id]: e.target.value }))}
                                        onBlur={() => handleScoreSubmit(s.id)}
                                        onKeyDown={e => { if (e.key === 'Enter') handleScoreSubmit(s.id); }}
                                    />
                                </td>
                                <td>
                                    <button
                                        className={`admin-btn ${s.isVisible ? 'admin-btn-success' : 'admin-btn-secondary'}`}
                                        onClick={() => handleVisibilityToggle(s.id, s.isVisible)}
                                    >
                                        {s.isVisible ? 'Visible' : 'Masqué'}
                                    </button>
                                </td>
                                <td>
                                    <button className="admin-btn admin-btn-danger" onClick={() => handleDelete(s.id)}>
                                        Supprimer
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <Paginator meta={meta} page={page} setPage={setPage} />
        </>
    );
}

// ── Companies ─────────────────────────────────────────────────────────────────

function CompaniesPanel({ token }) {
    const [companies, setCompanies] = useState([]);
    const [loading, setLoading] = useState(true);
    const [page, setPage] = useState(1);
    const [meta, setMeta] = useState(null);
    const [search, setSearch] = useState('');
    const [searchInput, setSearchInput] = useState('');

    const fetchCompanies = useCallback(() => {
        setLoading(true);
        const params = new URLSearchParams({ page, limit: 25 });
        if (search) params.set('search', search);
        adminFetch(`/companies?${params}`, token)
            .then(r => r.json())
            .then(d => { setCompanies(d.data ?? []); setMeta(d.meta ?? null); })
            .finally(() => setLoading(false));
    }, [token, page, search]);

    useEffect(() => { fetchCompanies(); }, [fetchCompanies]);

    async function handleDelete(id) {
        if (!window.confirm("Supprimer cette entreprise et toutes ses offres ? Cette action est irréversible.")) return;
        const res = await adminFetch(`/companies/${id}`, token, { method: 'DELETE' });
        if (res.ok) setCompanies(prev => prev.filter(c => c.id !== id));
    }

    function handleSearch(e) {
        e.preventDefault();
        setSearch(searchInput);
        setPage(1);
    }

    return (
        <>
            <form className="admin-toolbar" onSubmit={handleSearch}>
                <input
                    className="admin-search-input"
                    placeholder="Rechercher par nom ou email…"
                    value={searchInput}
                    onChange={e => setSearchInput(e.target.value)}
                />
                <button type="submit" className="admin-btn admin-btn-primary">Rechercher</button>
            </form>
            <div className="admin-table-wrapper">
                <table className="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Site</th>
                            <th>Offres</th>
                            <th>Créée</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            <tr><td colSpan={7} className="admin-loading">Chargement…</td></tr>
                        ) : companies.length === 0 ? (
                            <tr><td colSpan={7} className="admin-empty">Aucune entreprise.</td></tr>
                        ) : companies.map(c => (
                            <tr key={c.id}>
                                <td>{c.id}</td>
                                <td>{c.name}</td>
                                <td>{c.email}</td>
                                <td>
                                    {c.website
                                        ? <a href={c.website} target="_blank" rel="noreferrer">Lien</a>
                                        : '—'}
                                </td>
                                <td>{c.offersCount}</td>
                                <td>{formatDate(c.createdAt)}</td>
                                <td>
                                    <button className="admin-btn admin-btn-danger" onClick={() => handleDelete(c.id)}>
                                        Supprimer
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <Paginator meta={meta} page={page} setPage={setPage} />
        </>
    );
}

// ── Offers ────────────────────────────────────────────────────────────────────

function OffersPanel({ token }) {
    const [offers, setOffers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [page, setPage] = useState(1);
    const [meta, setMeta] = useState(null);
    const [search, setSearch] = useState('');
    const [searchInput, setSearchInput] = useState('');
    const [statusFilter, setStatusFilter] = useState('');

    const fetchOffers = useCallback(() => {
        setLoading(true);
        const params = new URLSearchParams({ page, limit: 25 });
        if (search) params.set('search', search);
        if (statusFilter) params.set('status', statusFilter);
        adminFetch(`/offers?${params}`, token)
            .then(r => r.json())
            .then(d => { setOffers(d.data ?? []); setMeta(d.meta ?? null); })
            .finally(() => setLoading(false));
    }, [token, page, search, statusFilter]);

    useEffect(() => { fetchOffers(); }, [fetchOffers]);

    async function handleStatusChange(id, status) {
        const res = await adminFetch(`/offers/${id}/status`, token, {
            method: 'PATCH',
            body: JSON.stringify({ status }),
        });
        if (res.ok) {
            setOffers(prev => prev.map(o => o.id === id ? { ...o, status } : o));
        }
    }

    async function handleDelete(id) {
        if (!window.confirm('Supprimer cette offre ? Cette action est irréversible.')) return;
        const res = await adminFetch(`/offers/${id}`, token, { method: 'DELETE' });
        if (res.ok) setOffers(prev => prev.filter(o => o.id !== id));
    }

    function handleSearch(e) {
        e.preventDefault();
        setSearch(searchInput);
        setPage(1);
    }

    return (
        <>
            <form className="admin-toolbar" onSubmit={handleSearch}>
                <input
                    className="admin-search-input"
                    placeholder="Rechercher par titre ou entreprise…"
                    value={searchInput}
                    onChange={e => setSearchInput(e.target.value)}
                />
                <select
                    className="admin-status-filter"
                    value={statusFilter}
                    onChange={e => { setStatusFilter(e.target.value); setPage(1); }}
                >
                    <option value="">Tous les statuts</option>
                    <option value="published">Publiées</option>
                    <option value="draft">Brouillons</option>
                    <option value="closed">Fermées</option>
                </select>
                <button type="submit" className="admin-btn admin-btn-primary">Rechercher</button>
            </form>
            <div className="admin-table-wrapper">
                <table className="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Titre</th>
                            <th>Entreprise</th>
                            <th>Type</th>
                            <th>Statut</th>
                            <th>Lieu</th>
                            <th>Créée</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            <tr><td colSpan={8} className="admin-loading">Chargement…</td></tr>
                        ) : offers.length === 0 ? (
                            <tr><td colSpan={8} className="admin-empty">Aucune offre.</td></tr>
                        ) : offers.map(o => (
                            <tr key={o.id}>
                                <td>{o.id}</td>
                                <td>{o.title}</td>
                                <td>{o.company.name}</td>
                                <td>{o.type}</td>
                                <td>
                                    <select
                                        className="admin-select"
                                        value={o.status}
                                        onChange={e => handleStatusChange(o.id, e.target.value)}
                                    >
                                        <option value="published">Publiée</option>
                                        <option value="draft">Brouillon</option>
                                        <option value="closed">Fermée</option>
                                    </select>
                                </td>
                                <td>{o.location ?? '—'}</td>
                                <td>{formatDate(o.createdAt)}</td>
                                <td>
                                    <button className="admin-btn admin-btn-danger" onClick={() => handleDelete(o.id)}>
                                        Supprimer
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <Paginator meta={meta} page={page} setPage={setPage} />
        </>
    );
}

// ── Paginator ─────────────────────────────────────────────────────────────────

function Paginator({ meta, page, setPage }) {
    if (!meta || meta.pages <= 1) return null;
    return (
        <div className="admin-pagination">
            <button disabled={page <= 1} onClick={() => setPage(p => p - 1)}>← Préc.</button>
            <span>Page {page} / {meta.pages} ({meta.total} total)</span>
            <button disabled={page >= meta.pages} onClick={() => setPage(p => p + 1)}>Suiv. →</button>
        </div>
    );
}

// ── AdminPage ─────────────────────────────────────────────────────────────────

const TABS = [
    { id: 'dashboard', label: 'Tableau de bord' },
    { id: 'users', label: 'Utilisateurs' },
    { id: 'students', label: 'Étudiants' },
    { id: 'companies', label: 'Entreprises' },
    { id: 'offers', label: 'Offres' },
];

export default function AdminPage() {
    const { token, user } = useAuth();
    const navigate = useNavigate();
    const [activeTab, setActiveTab] = useState('dashboard');

    const isStaff = user?.roles?.includes('ROLE_STAFF');

    useEffect(() => {
        if (!token) { navigate('/login'); return; }
        if (!isStaff) { navigate('/'); }
    }, [token, isStaff, navigate]);

    if (!isStaff) return null;

    return (
        <div className="admin-page">
            <h1>Administration</h1>
            <div className="admin-tabs">
                {TABS.map(tab => (
                    <button
                        key={tab.id}
                        className={`admin-tab${activeTab === tab.id ? ' active' : ''}`}
                        onClick={() => setActiveTab(tab.id)}
                    >
                        {tab.label}
                    </button>
                ))}
            </div>

            {activeTab === 'dashboard' && <DashboardPanel token={token} />}
            {activeTab === 'users' && <UsersPanel token={token} currentUserEmail={user?.username} />}
            {activeTab === 'students' && <StudentsPanel token={token} />}
            {activeTab === 'companies' && <CompaniesPanel token={token} />}
            {activeTab === 'offers' && <OffersPanel token={token} />}
        </div>
    );
}
