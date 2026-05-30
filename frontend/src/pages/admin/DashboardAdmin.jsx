import { useEffect, useState } from 'react'
import { api } from '../../api'

export default function DashboardAdmin() {
  const [stats, setStats] = useState(null)
  const [erreur, setErreur] = useState('')

  useEffect(() => {
    Promise.all([api.get('/etudiants.php'), api.get('/enseignants.php'), api.get('/cours.php')])
      .then(([etudiants, enseignants, cours]) =>
        setStats({ etudiants: etudiants.length, enseignants: enseignants.length, cours }))
      .catch((err) => setErreur(err.message))
  }, [])

  if (erreur) return <div className="alerte">{erreur}</div>
  if (!stats) return <p>Chargement…</p>

  return (
    <div>
      <h1>Tableau de bord — Administrateur</h1>
      <p className="intro">Vue d'ensemble de l'établissement.</p>

      <div className="grille-cartes">
        <div className="carte stat"><div className="valeur">{stats.etudiants}</div><div className="libelle">Étudiants</div></div>
        <div className="carte stat"><div className="valeur">{stats.enseignants}</div><div className="libelle">Enseignants</div></div>
        <div className="carte stat"><div className="valeur">{stats.cours.length}</div><div className="libelle">Cours</div></div>
      </div>

      <h2 style={{ marginTop: 28 }}>Aperçu des cours</h2>
      <div className="cadre-tableau">
        <table className="tableau">
          <thead><tr><th>Cours</th><th>Promotion</th><th>Enseignant</th><th>Remplissage</th></tr></thead>
          <tbody>
            {stats.cours.map((c) => (
              <tr key={c.idCours}>
                <td>{c.nomCours}</td>
                <td><span className="pastille pastille-grise">{c.promotion}</span></td>
                <td>{c.enseignant ?? '—'}</td>
                <td>{c.nbInscrits}/{c.capaciteMax}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
