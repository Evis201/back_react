import React, { useEffect, useState } from "react";
import "./login.css";
import logo from "../assets/Logohexagone.png";
import { Link, useNavigate } from "react-router-dom";
import { useAuth } from "../context/AuthContext.jsx";

function Login() {
	useEffect(() => {
		document.body.classList.add("no-scroll");
		return () => document.body.classList.remove("no-scroll");
	}, []);

	const { login } = useAuth();
	const navigate = useNavigate();

	const [email, setEmail] = useState("");
	const [password, setPassword] = useState("");
	const [show, setShow] = useState(false);
	const [error, setError] = useState(null);
	const [loading, setLoading] = useState(false);

	async function handleSubmit(e) {
		e.preventDefault();
		setError(null);
		setLoading(true);
		try {
			const res = await fetch("/api/auth/login", {
				method: "POST",
				headers: { "Content-Type": "application/json" },
				body: JSON.stringify({ email, password }),
			});
			if (!res.ok) {
				const body = await res.json().catch(() => ({}));
				throw new Error(body.message || "Identifiants invalides.");
			}
			const { token } = await res.json();
			login(token);
			navigate("/");
		} catch (err) {
			setError(err.message);
		} finally {
			setLoading(false);
		}
	}

	return (
		<main className="login-page">
			<div className="login-container">
				<section className="login-left">
					<h1 className="title">Se connecter à</h1>
					<h2 className="subtitle">Elève Finder !</h2>

					<p className="signup">
						Vous n'avez pas de compte ?
						<Link to="/register" className="signup-link"> Créez un compte !</Link>
					</p>
				</section>

				<section className="login-right">
					<form className="login-card" onSubmit={handleSubmit}>
						<h3 className="card-title">Se connecter</h3>

						<div className="field">
							<input
								type="email"
								placeholder="Email"
								value={email}
								onChange={(e) => setEmail(e.target.value)}
								required
								className="input"
							/>
						</div>

						<div className="field password-field">
							<input
								type={show ? "text" : "password"}
								placeholder="Mot de passe"
								value={password}
								onChange={(e) => setPassword(e.target.value)}
								required
								className="input"
							/>
							<button
								type="button"
								className="toggle-show"
								onClick={() => setShow((s) => !s)}
								aria-label={show ? "Masquer le mot de passe" : "Afficher le mot de passe"}
							>
								{show ? (
									<span className="eye">⌣</span>
								) : (
									<span className="eye">👁️</span>
								)}
							</button>
						</div>

						<div className="forgot">Mot de passe oublié ?</div>

						{error && <p className="form-error">{error}</p>}
						<button type="submit" className="primary-btn" disabled={loading}>
							{loading ? "Connexion..." : "Se connecter"}
						</button>
					</form>
				</section>
			</div>
		</main>
	);
}

export default Login;