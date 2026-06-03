import { useState, useEffect } from "react";
import { useParams, Link } from "react-router-dom";
import "./Studentlist.css";

function StudentDetail() {
    const { id } = useParams();
    const [student, setStudent] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        setLoading(true);
        fetch(`/api/students/${id}`)
            .then((res) => {
                if (!res.ok) {
                    throw new Error("Impossible de récupérer l'étudiant.");
                }
                return res.json();
            })
            .then((result) => {
                setStudent(result.data);
                setError(null);
            })
            .catch((fetchError) => {
                setError(fetchError.message);
            })
            .finally(() => {
                setLoading(false);
            });
    }, [id]);

    if (loading) {
        return (
            <div className="container">
                <p>Chargement...</p>
            </div>
        );
    }

    if (error || !student) {
        return (
            <div className="container">
                <p>{error || "Étudiant introuvable."}</p>
                <Link to="/eleves" className="detail-back">
                    ← Retour à la liste
                </Link>
            </div>
        );
    }

    return (
        <div className="container student-detail-page">
            <Link to="/eleves" className="detail-back">
                ← Retour à la liste
            </Link>

            <div className="student-detail-card">
                <img
                    src={
                        student.avatarUrl ||
                        "https://via.placeholder.com/150x150?text=Etudiant"
                    }
                    alt={`${student.firstName} ${student.lastName}`}
                    className="student-avatar student-detail-avatar"
                />

                <div className="student-detail-content">
                    <h1>{student.firstName} {student.lastName}</h1>
                    <p>
                        <strong>École :</strong> {student.school || "Non renseignée"}
                    </p>
                    <p>
                        <strong>Domaine :</strong> {student.domain || "Non renseigné"}
                    </p>
                    <p>
                        <strong>Promotion :</strong> {student.promotionYear || "—"}
                    </p>
                    <p>
                        <strong>Bio :</strong> {student.bio || "Aucune bio renseignée."}
                    </p>

                    {student.skills?.length > 0 && (
                        <div className="student-skills">
                            <h2>Compétences</h2>
                            <div className="skill-list">
                                {student.skills.map((skill) => (
                                    <span key={skill.id ?? `${skill.name}-${skill.level}`} className="skill-chip">
                                        {skill.name} • {skill.level}
                                    </span>
                                ))}
                            </div>
                        </div>
                    )}

                    {student.githubUrl && (
                        <p>
                            <strong>GitHub :</strong>{" "}
                            <a href={student.githubUrl} target="_blank" rel="noreferrer">
                                {student.githubUrl}
                            </a>
                        </p>
                    )}
                    {student.linkedinUrl && (
                        <p>
                            <strong>LinkedIn :</strong>{" "}
                            <a href={student.linkedinUrl} target="_blank" rel="noreferrer">
                                {student.linkedinUrl}
                            </a>
                        </p>
                    )}
                    {student.cvUrl && (
                        <p>
                            <strong>CV :</strong>{" "}
                            <a href={student.cvUrl} target="_blank" rel="noreferrer">
                                Voir le CV
                            </a>
                        </p>
                    )}

                    {student.badges?.length > 0 && (
                        <div className="student-badges">
                            <h2>Badges</h2>
                            <div className="badge-list">
                                {student.badges.map((badge) => (
                                    <div key={badge.id} className="badge-item">
                                        <div className="badge-name">{badge.name}</div>
                                        <div className="badge-meta">
                                            <span>{badge.points} pts</span>
                                            {badge.awardedBy && <span>• {badge.awardedBy}</span>}
                                        </div>
                                        {badge.description && (
                                            <p className="badge-description">{badge.description}</p>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

export default StudentDetail;
