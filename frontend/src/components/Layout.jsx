import { NavLink, useNavigate, Outlet } from 'react-router-dom'
import { useAuth } from '../auth/AuthContext'

const liensParRole = {
  admin: [
    { to: '/admin', label: 'Tableau de bord' },
    { to: '/admin/etudiants', label: 'Étudiants' },
    { to: '/admin/enseignants', label: 'Enseignants' },
    { to: '/admin/cours', label: 'Cours' },
    { to: '/admin/edt', label: 'Emploi du temps' },
  ],
  enseignant: [
    { to: '/enseignant', label: 'Tableau de bord' },
    { to: '/enseignant/notes', label: 'Carnet de notes' },
    { to: '/enseignant/edt', label: 'Emploi du temps' },
  ],
  etudiant: [
    { to: '/etudiant', label: 'Tableau de bord' },
    { to: '/etudiant/cours', label: 'Catalogue' },
    { to: '/etudiant/notes', label: 'Mes notes' },
    { to: '/etudiant/edt', label: 'Emploi du temps' },
  ],
}

export default function Layout() {
  const { utilisateur, deconnexion } = useAuth()
  const navigate = useNavigate()
  const liens = liensParRole[utilisateur.role] ?? []

  async function handleDeconnexion() {
    await deconnexion()
    navigate('/connexion')
  }

  return (
    <div className="app">
      <header className="barre">
        <div className="logo">Smart<span>Campus</span></div>

        <nav className="nav">
          {liens.map((l) => (
            <NavLink key={l.to} to={l.to} end className={({ isActive }) => (isActive ? 'actif' : '')}>
              {l.label}
            </NavLink>
          ))}
        </nav>

        <div className="utilisateur">
          <span className="badge-role">{utilisateur.role}</span>
          <span>{utilisateur.prenom} {utilisateur.nom}</span>
          <button className="btn-secondaire" onClick={handleDeconnexion}>Déconnexion</button>
        </div>
      </header>

      <main className="contenu">
        <Outlet />
      </main>
    </div>
  )
}
