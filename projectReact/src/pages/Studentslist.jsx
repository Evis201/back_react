import { useState, useEffect, useMemo } from "react";
import { Link } from "react-router-dom";
import "./Studentlist.css";

function Studentslist() {
    const [students, setStudents] = useState([]);
    const [search, setSearch] = useState("");
    const [activeFilter, setActiveFilter] = useState("all");

    const filterOptions = [
        { key: "all", label: "Tous" },
        { key: "promotionYear", label: "Année" },
        { key: "school", label: "Ecole" },
        { key: "domain", label: "Domaine" },
    ];

    const filterLabels = {
        promotionYear: "Année",
        school: "Ecole",
        domain: "Domaine",
    };

    useEffect(() => {
        fetch("/api/students")
            .then((res) => res.json())
            .then((result) => {
                setStudents(result.data);
            })
            .catch((error) => {
                console.error("Erreur :", error);
            });
    }, []);

    const filteredStudents = useMemo(() => {
        const term = search.trim().toLowerCase();
        if (!term) {
            return students;
        }

        return students.filter((student) => {
            if (activeFilter === "promotionYear") {
                return student.promotionYear?.toString().includes(term);
            }

            if (activeFilter === "school") {
                return student.school?.toLowerCase().includes(term);
            }

            if (activeFilter === "domain") {
                return student.domain?.toLowerCase().includes(term);
            }

            const fullName = `${student.firstName} ${student.lastName}`.toLowerCase();

            return (
                fullName.includes(term) ||
                student.school?.toLowerCase().includes(term) ||
                student.domain?.toLowerCase().includes(term) ||
                student.promotionYear?.toString().includes(term)
            );
        });
    }, [search, students, activeFilter]);

    return (
        <div className="container">

            <div className="filter-bar">
                <div className="filter-buttons">
                    {filterOptions.map((filter) => (
                        <button
                            key={filter.key}
                            type="button"
                            className={`filter-button ${activeFilter === filter.key ? "active" : ""}`}
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
                    className="student-search"
                    placeholder={
                        activeFilter === "all"
                            ? "Rechercher..."
                            : `Rechercher par ${filterLabels[activeFilter]}...`
                    }
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                />
            </div>

            <div className="students-grid">
                {filteredStudents.length > 0 ? (
                    filteredStudents.map((student) => (
                        <Link
                            key={student.id}
                            to={`/eleves/${student.id}`}
                            className="student-card-link"
                        >
                            <div className="student-card">
                                <img
                                    src={
                                        student.avatarUrl ||
                                        "https://via.placeholder.com/150x150?text=Etudiant"
                                    }
                                    alt={`${student.firstName} ${student.lastName}`}
                                    className="student-avatar"
                                />

                                <h2>
                                    {student.firstName} {student.lastName}
                                </h2>

                                <p>
                                    <strong>École :</strong> {student.school || "Non renseignée"}
                                </p>

                                <p>
                                    <strong>Domaine :</strong> {student.domain || "Non renseigné"}
                                </p>

                                <p>
                                    <strong>Promotion :</strong> {student.promotionYear || "—"}
                                </p>
                            </div>
                        </Link>
                    ))
                ) : (
                    <div className="no-results">Aucun étudiant trouvé pour cette recherche.</div>
                )}
            </div>
        </div>
    );
}

export default Studentslist;
