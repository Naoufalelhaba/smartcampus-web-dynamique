import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../auth/AuthContext'
import { cheminAccueil } from '../roles'

export default function Login() {
  const { connexion } = useAuth()
  const navigate = useNavigate()

  const [email, setEmail] = useState('')
  const [motDePasse, setMotDePasse] = useState('')
  const [erreur, setErreur] = useState('')
  const [enCours, setEnCours] = useState(false)

  async function handleSubmit(e) {
    e.preventDefault()
    setErreur('')
    setEnCours(true)
    try {
      const u = await connexion(email, motDePasse)
      navigate(cheminAccueil(u.role))
    } catch (err) {
      setErreur(err.message)
    } finally {
      setEnCours(false)
    }
  }

  function remplir(mail, mdp) {
    setEmail(mail)
    setMotDePasse(mdp)
  }

  return (
    <div className="page-connexion">
      <form className="carte carte-connexion" onSubmit={handleSubmit}>
        <div className="logo logo-grand">Smart<span>Campus</span></div>
        <p className="sous-titre">La gestion académique de notre époque</p>

        {erreur && <div className="alerte">{erreur}</div>}

        <label>
          Email
          <input
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            placeholder="prenom.nom@smartcampus.fr"
            required
          />
        </label>

        <label>
          Mot de passe
          <input
            type="password"
            value={motDePasse}
            onChange={(e) => setMotDePasse(e.target.value)}
            placeholder="••••••••"
            required
          />
        </label>

        <button className="btn-principal" type="submit" disabled={enCours}>
          {enCours ? 'Connexion…' : 'Se connecter'}
        </button>

        <div className="comptes-demo">
          <span>Comptes de démo :</span>
          <button type="button" onClick={() => remplir('admin@smartcampus.fr', 'admin123')}>Admin</button>
          <button type="button" onClick={() => remplir('a.turing@smartcampus.fr', 'prof123')}>Enseignant</button>
          <button type="button" onClick={() => remplir('lea.durand@smartcampus.fr', 'etudiant123')}>Étudiant</button>
        </div>
      </form>
    </div>
  )
}
