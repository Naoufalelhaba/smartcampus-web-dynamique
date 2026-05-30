import { useEffect, useState } from 'react'
import { api } from '../../api'
import { useAuth } from '../../auth/AuthContext'

export default function DashboardEnseignant() {
  const { utilisateur } = useAuth()
  const [cours, setCours] = useState(null)
  const [erreur, setErreur] = useState('')

  useEffect(() => {
    api.get('/cours.php?idEnseignant=' + utilisateur.idUser)
      .then(setCours)
      .catch((e) => setErreur(e.message))
  }, [utilisateur.idUser])

  if (erreur) return <div className="alerte">{erreur}</div>
  if (!cours) return <p>Chargement…</p>

  const totalInscrits = cours.reduce((s, c) => s + Number(c.nbInscrits), 0)

  return (
    <div>
      <h1>Bonjour {utilisateur.prenom} {utilisateur.nom}</h1>
      <p className="intro">Vue d'ensemble de vos enseignements.</p>

      <div className="grille-cartes">
        <div className="carte stat"><div className="valeur">{cours.length}</div><div className="libelle">Cours enseignés</div></div>
        <div className="carte stat"><div className="valeur">{totalInscrits}</div><div className="libelle">Étudiants au total</div></div>
      </div>

      <h2 style={{ marginTop: 28 }}>Mes cours</h2>
      <div className="cadre-tableau">
        <table className="tableau">
          <thead><tr><th>Cours</th><th>Promotion</th><th>Semestre</th><th>Inscrits</th></tr></thead>
          <tbody>
            {cours.map((c) => (
              <tr key={c.idCours}>
                <td>{c.nomCours}</td>
                <td><span className="pastille pastille-grise">{c.promotion}</span></td>
                <td>{c.semestre}</td>
                <td>{c.nbInscrits}/{c.capaciteMax}</td>
              </tr>
            ))}
            {cours.length === 0 && <tr><td colSpan="4" className="vide">Aucun cours assigné.</td></tr>}
          </tbody>
        </table>
      </div>
    </div>
  )
}
