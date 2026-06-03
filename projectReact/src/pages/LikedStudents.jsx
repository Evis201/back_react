import { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext.jsx';
import './LikedStudents.css';

function LikedStudents() {
    const { token, user } = useAuth();
    const navigate = useNavigate();

    const [liked, setLiked] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [removingId, setRemovingId] = useState(null);

    const isCompany = user?.roles?.includes('ROLE_COMPANY');

    useEffect(() => {
        if (!token) {
            navigate('/login');
            return;
        }
        if (!isCompany) {
            navigate('/');
            return;
        }

        fetch('/api/swipe/liked', {
            headers: { Authorization: `Bearer ${token}` },
        })
            .then(res => res.json())
            .then(json => {
                if (json.success) setLiked(json.data);
            })
            .catch(e => console.error('Liked fetch error:', e))
            .finally(() => setIsLoading(false));
    }, [token, isCompany, navigate]);

    const handleUnlike = async (studentId) => {
        setRemovingId(studentId);
        try {
            await fetch(`/api/swipe/${studentId}`, {
                method: 'DELETE',
                headers: { Authorization: `Bearer ${token}` },
            });
            setLiked(prev => prev.filter(s => s.id !== studentId));
        } catch (e) {
            console.error('Unlike error:', e);
        } finally {
            setRemovingId(null);
        }
    };

    if (!isCompany) return null;

    return (
        <div className="liked-page">
            <div className="liked-header">
                <div>
                    <h1 className="liked-title">Mes CV</h1>
                    <p className="liked-subtitle">{liked.length} candidat{liked.length !== 1 ? 's' : ''} sélectionné{liked.length !== 1 ? 's' : ''}</p>
                </div>
                <Link to="/swipe" className="liked-swipe-btn">
                    ← CV Finder
                </Link>
            </div>

            {isLoading ? (
                <div className="liked-grid">
                    {[1, 2, 3].map(i => (
                        <div key={i} className="liked-skeleton" />
                    ))}
                </div>
            ) : liked.length === 0 ? (
                <div className="liked-empty">
                    <div className="liked-empty-icon">💼</div>
                    <h2>Aucun profil liké pour l'instant</h2>
                    <p>Swipez des CVs pour retrouver vos favoris ici.</p>
                    <Link to="/swipe" className="liked-cta">
                        Commencer à swiper →
                    </Link>
                </div>
            ) : (
                <div className="liked-grid">
                    {liked.map(student => (
                        <div key={student.id} className="liked-card">
                            <div className="liked-card-media">
                                {student.avatarUrl ? (
                                    <img
                                        src={student.avatarUrl}
                                        alt={`${student.firstName} ${student.lastName}`}
                                        className="liked-avatar"
                                        onError={e => { e.target.onerror = null; e.target.style.display = 'none'; }}
                                    />
                                ) : (
                                    <div className="liked-initials">
                                        {student.firstName?.[0]}{student.lastName?.[0]}
                                    </div>
                                )}
                            </div>

                            <div className="liked-card-body">
                                <h2 className="liked-name">
                                    {student.firstName} {student.lastName}
                                </h2>

                                {(student.school || student.domain) && (
                                    <p className="liked-meta">
                                        {[student.school, student.domain].filter(Boolean).join(' · ')}
                                    </p>
                                )}

                                {student.promotionYear && (
                                    <p className="liked-promo">Promo {student.promotionYear}</p>
                                )}

                                {student.skills && student.skills.length > 0 && (
                                    <div className="liked-skills">
                                        {student.skills.slice(0, 4).map((s, i) => (
                                            <span key={i} className="liked-skill-chip">{s.name}</span>
                                        ))}
                                        {student.skills.length > 4 && (
                                            <span className="liked-skill-chip liked-skill-more">+{student.skills.length - 4}</span>
                                        )}
                                    </div>
                                )}

                                <div className="liked-card-actions">
                                    <Link to={`/eleves/${student.id}`} className="liked-btn liked-btn-profile">
                                        Voir profil
                                    </Link>
                                    {student.cvUrl && (
                                        <a href={student.cvUrl} target="_blank" rel="noreferrer" className="liked-btn liked-btn-cv">
                                            CV
                                        </a>
                                    )}
                                    <button
                                        className="liked-btn liked-btn-remove"
                                        onClick={() => handleUnlike(student.id)}
                                        disabled={removingId === student.id}
                                    >
                                        {removingId === student.id ? '…' : 'Retirer'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

export default LikedStudents;
