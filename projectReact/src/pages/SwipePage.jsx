import { useState, useEffect, useCallback, useRef } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext.jsx';
import './SwipePage.css';

function SwipePage() {
    const { token, user } = useAuth();
    const navigate = useNavigate();

    const [queue, setQueue] = useState([]);
    const [animation, setAnimation] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [isDone, setIsDone] = useState(false);
    const [lastSwiped, setLastSwiped] = useState(null);
    const [isFetching, setIsFetching] = useState(false);
    const isAnimating = useRef(false);

    const isCompany = user?.roles?.includes('ROLE_COMPANY');

    useEffect(() => {
        if (!token) {
            navigate('/login');
            return;
        }
        if (!isCompany) {
            navigate('/');
        }
    }, [token, isCompany, navigate]);

    const fetchBatch = useCallback(async () => {
        if (isFetching) return;
        setIsFetching(true);
        try {
            const res = await fetch('/api/swipe/next?limit=10', {
                headers: { Authorization: `Bearer ${token}` },
            });
            const json = await res.json();
            if (json.success && Array.isArray(json.data) && json.data.length > 0) {
                setQueue(prev => {
                    const existingIds = new Set(prev.map(s => s.id));
                    const fresh = json.data.filter(s => !existingIds.has(s.id));
                    return [...prev, ...fresh];
                });
            } else {
                setQueue(prev => {
                    if (prev.length === 0) setIsDone(true);
                    return prev;
                });
            }
        } catch (e) {
            console.error('Swipe fetch error:', e);
        } finally {
            setIsFetching(false);
            setIsLoading(false);
        }
    }, [token, isFetching]);

    useEffect(() => {
        fetchBatch();
    }, []);

    useEffect(() => {
        if (!isLoading && queue.length === 0 && !isFetching) {
            setIsDone(true);
        }
        if (queue.length > 0 && queue.length < 3 && !isFetching) {
            fetchBatch();
        }
    }, [queue.length, isLoading, isFetching]);

    const recordSwipe = useCallback((studentId, action) => {
        fetch('/api/swipe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Authorization: `Bearer ${token}`,
            },
            body: JSON.stringify({ studentId, action }),
        }).catch(e => console.error('Swipe API error:', e));
    }, [token]);

    const handleAction = useCallback((action) => {
        if (isAnimating.current || queue.length === 0) return;
        isAnimating.current = true;

        const student = queue[0];
        setAnimation(action);
        recordSwipe(student.id, action);
        setLastSwiped({ student, action });

        setTimeout(() => {
            setQueue(prev => prev.slice(1));
            setAnimation(null);
            isAnimating.current = false;
        }, 420);
    }, [queue, recordSwipe]);

    const handleUndo = useCallback(() => {
        if (!lastSwiped || isAnimating.current) return;
        const { student, action } = lastSwiped;
        const reverseAction = action === 'like' ? 'pass' : 'like';
        recordSwipe(student.id, reverseAction);
        setQueue(prev => [student, ...prev]);
        setLastSwiped(null);
        setIsDone(false);
    }, [lastSwiped, recordSwipe]);

    useEffect(() => {
        const handleKey = (e) => {
            if (e.key === 'ArrowLeft') handleAction('pass');
            if (e.key === 'ArrowRight') handleAction('like');
            if (e.key === ' ') {
                e.preventDefault();
                const current = queue[0];
                if (current?.cvUrl) window.open(current.cvUrl, '_blank');
            }
        };
        window.addEventListener('keydown', handleKey);
        return () => window.removeEventListener('keydown', handleKey);
    }, [handleAction, queue]);

    if (!isCompany) return null;

    if (isLoading) {
        return (
            <div className="swipe-page">
                <div className="swipe-skeleton">
                    <div className="swipe-skeleton-avatar" />
                    <div className="swipe-skeleton-line" />
                    <div className="swipe-skeleton-line short" />
                </div>
            </div>
        );
    }

    if (isDone) {
        return (
            <div className="swipe-page">
                <div className="swipe-done">
                    <div className="swipe-done-icon">🎓</div>
                    <h2>Plus de profils disponibles !</h2>
                    <p>Vous avez vu tous les candidats actuellement disponibles.</p>
                    <Link to="/swipe/liked" className="swipe-btn-liked-link">
                        Voir mes CV sélectionnés →
                    </Link>
                </div>
            </div>
        );
    }

    const current = queue[0];
    const next = queue[1];

    return (
        <div className="swipe-page">
            <div className="swipe-header">
                <div className="swipe-counter">
                    {queue.length} profil{queue.length > 1 ? 's' : ''} restant{queue.length > 1 ? 's' : ''}
                </div>
                <Link to="/swipe/liked" className="swipe-liked-link">
                    Mes CV ♥
                </Link>
            </div>

            <div className="swipe-stack">
                {next && (
                    <div className="swipe-card swipe-card-behind" key={`behind-${next.id}`}>
                        <SwipeCard student={next} />
                    </div>
                )}
                {current && (
                    <div
                        className={`swipe-card swipe-card-front ${animation ? `swipe-anim-${animation}` : ''}`}
                        key={`front-${current.id}`}
                    >
                        {animation === 'like' && <div className="swipe-stamp like">LIKÉ ♥</div>}
                        {animation === 'pass' && <div className="swipe-stamp pass">PASSÉ ✕</div>}
                        <SwipeCard student={current} />
                    </div>
                )}
            </div>

            <div className="swipe-actions">
                <button
                    className="swipe-btn swipe-btn-pass"
                    onClick={() => handleAction('pass')}
                    title="Passer (←)"
                >
                    ✕
                </button>

                {lastSwiped && (
                    <button
                        className="swipe-btn swipe-btn-undo"
                        onClick={handleUndo}
                        title="Annuler le dernier swipe"
                    >
                        ↩
                    </button>
                )}

                <button
                    className="swipe-btn swipe-btn-like"
                    onClick={() => handleAction('like')}
                    title="Liker (→)"
                >
                    ♥
                </button>
            </div>

            <p className="swipe-hint">← Passer &nbsp;|&nbsp; Espace = CV &nbsp;|&nbsp; Liker →</p>
        </div>
    );
}

function SwipeCard({ student }) {
    const initials = `${student.firstName?.[0] ?? ''}${student.lastName?.[0] ?? ''}`.toUpperCase();

    return (
        <div className="swipe-card-inner">
            <div className="swipe-card-media">
                {student.avatarUrl ? (
                    <img
                        src={student.avatarUrl}
                        alt={`${student.firstName} ${student.lastName}`}
                        className="swipe-card-avatar"
                        onError={e => { e.target.onerror = null; e.target.style.display = 'none'; }}
                    />
                ) : (
                    <div className="swipe-card-initials">{initials}</div>
                )}
                <div className="swipe-card-overlay">
                    <h2 className="swipe-card-name">{student.firstName} {student.lastName}</h2>
                    <p className="swipe-card-meta">
                        {[student.school, student.domain, student.promotionYear].filter(Boolean).join(' · ')}
                    </p>
                </div>
            </div>

            <div className="swipe-card-body">
                {student.bio && (
                    <p className="swipe-card-bio">
                        {student.bio.length > 160 ? student.bio.slice(0, 160) + '…' : student.bio}
                    </p>
                )}

                {student.skills && student.skills.length > 0 && (
                    <div className="swipe-card-skills">
                        {student.skills.slice(0, 6).map((s, i) => (
                            <span key={i} className="swipe-skill-chip">{s.name}</span>
                        ))}
                        {student.skills.length > 6 && (
                            <span className="swipe-skill-chip swipe-skill-more">+{student.skills.length - 6}</span>
                        )}
                    </div>
                )}

                <div className="swipe-card-links">
                    {student.cvUrl && (
                        <a href={student.cvUrl} target="_blank" rel="noreferrer" className="swipe-link swipe-link-cv">
                            CV
                        </a>
                    )}
                    {student.githubUrl && (
                        <a href={student.githubUrl} target="_blank" rel="noreferrer" className="swipe-link">
                            GitHub
                        </a>
                    )}
                    {student.linkedinUrl && (
                        <a href={student.linkedinUrl} target="_blank" rel="noreferrer" className="swipe-link">
                            LinkedIn
                        </a>
                    )}
                    <Link to={`/eleves/${student.id}`} className="swipe-link">
                        Profil complet
                    </Link>
                </div>
            </div>
        </div>
    );
}

export default SwipePage;
