import React, { useEffect, useState } from "react";
import "./signup.css";
import { Link, useNavigate } from "react-router-dom";

function Signup() {
  useEffect(() => {
    document.body.classList.add("no-scroll");
    return () => document.body.classList.remove("no-scroll");
  }, []);

  const navigate = useNavigate();

  const [form, setForm] = useState({
    email: "",
    password: "",
    confirm: "",
    role: "student",
    firstName: "",
    lastName: "",
    companyName: "",
  });
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(false);

  function handleChange(e) {
    const { name, value } = e.target;
    setForm((f) => ({ ...f, [name]: value }));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError(null);

    if (form.password.length < 16) {
      setError("Le mot de passe doit contenir au moins 16 caractères (norme ANSSI).");
      return;
    }
    if (!/[A-Z]/.test(form.password) || !/[a-z]/.test(form.password) || !/[0-9]/.test(form.password) || !/[\W_]/.test(form.password)) {
      setError("Le mot de passe doit contenir majuscule, minuscule, chiffre et caractère spécial.");
      return;
    }
    if (form.password !== form.confirm) {
      setError("Les mots de passe ne correspondent pas.");
      return;
    }

    const body = {
      email: form.email,
      password: form.password,
      role: form.role,
    };

    if (form.role === "student") {
      body.firstName = form.firstName;
      body.lastName = form.lastName;
    } else {
      body.companyName = form.companyName;
    }

    setLoading(true);
    try {
      const res = await fetch("/api/auth/register", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body),
      });
      const data = await res.json();
      if (!res.ok) {
        const msg = data.details
          ? data.details.map((d) => d.message).join(' | ')
          : (data.error || "Erreur lors de l'inscription.");
        throw new Error(msg);
      }
      navigate("/login");
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <main className="signup-page">
      <div className="signup-container">
        <section className="signup-left">
          <h1 className="title">S'inscrire à</h1>
          <h2 className="subtitle">Elève Finder !</h2>

          <p className="signup-note">
            Vous avez déjà un compte ?
            <Link to="/login" className="signup-link"> Connectez vous !</Link>
          </p>
        </section>

        <section className="signup-right">
          <form className="signup-card" onSubmit={handleSubmit}>
            <h3 className="card-title">S'inscrire</h3>

            <div className="field">
              <select name="role" value={form.role} onChange={handleChange} className="input">
                <option value="student">Étudiant</option>
                <option value="company">Entreprise</option>
              </select>
            </div>

            <div className="field">
              <input name="email" type="email" value={form.email} onChange={handleChange} required className="input" placeholder="Email" />
            </div>

            {form.role === "student" ? (
              <>
                <div className="field">
                  <input name="firstName" value={form.firstName} onChange={handleChange} required className="input" placeholder="Prénom" />
                </div>
                <div className="field">
                  <input name="lastName" value={form.lastName} onChange={handleChange} required className="input" placeholder="Nom" />
                </div>
              </>
            ) : (
              <div className="field">
                <input name="companyName" value={form.companyName} onChange={handleChange} required className="input" placeholder="Nom de l'entreprise" />
              </div>
            )}

            <div className="field password-field">
              <input name="password" value={form.password} onChange={handleChange} required className="input" placeholder="Mot de passe (16 car. min.)" type={showPassword ? "text" : "password"} minLength={16} />
              <button type="button" className="toggle-show" onClick={() => setShowPassword(s => !s)}>{showPassword ? '⌣' : '👁️'}</button>
            </div>

            <div className="field password-field">
              <input name="confirm" value={form.confirm} onChange={handleChange} required className="input" placeholder="Confirmer mot de passe" type={showPassword ? "text" : "password"} />
              <button type="button" className="toggle-show" onClick={() => setShowPassword(s => !s)}>{showPassword ? '⌣' : '👁️'}</button>
            </div>

            {error && <p className="form-error">{error}</p>}
            <button type="submit" className="primary-btn" disabled={loading}>
              {loading ? "Inscription..." : "S'inscrire"}
            </button>
          </form>
        </section>
      </div>
    </main>
  );
}

export default Signup;
