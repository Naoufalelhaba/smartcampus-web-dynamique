import { useEffect, useState } from 'react'
import { api } from '../../api'
import { useAuth } from '../../auth/AuthContext'

const JOURS = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']

export default function DashboardEtudiant() {
  const { utilisateur } = useAuth()
  const [resultats, setResultats] = useState(null)
  const [seances, setSeances] = useState([])
  const [erreur, setErreur] = useState('')

  useEffect(() => {
    Promise.all([api.get('/notes.php'), api.get('/seances.php')])
      .then(([r, s]) => { setResultats(r); setSeances(s) })
      .catch((e) => setErreur(e.message))
  }, [])

  if (erreur) return <div className="alerte">{erreur}</div>
  if (!resultats) return <p>Chargement…</p>

  const toutesNotes = []
  resultats.cours.forEach((c) => c.notes.forEach((n) => toutesNotes.push({ ...n, cours: c.nomCours })))

  const seancesTriees = [...seances].sort(
    (a, b) => JOURS.indexOf(a.jour) - JOURS.indexOf(b.jour) || a.heureDebut.localeCompare(b.heureDebut)
  )

  return (
    <div>
      <h1>Bonjour {utilisateur.prenom}</h1>
      <p className="intro">Votre tableau de bord ({utilisateur.promotion}).</p>

      <div className="grille-cartes">
        <div className="carte stat"><div className="valeur">{resultats.moyenneGenerale ?? '—'}</div><div className="libelle">Moyenne générale</div></div>
        <div className="carte stat"><div className="valeur">{resultats.cours.length}</div><div className="libelle">Cours suivis</div></div>
        <div className="carte stat"><div className="valeur">{seances.length}</div><div className="libelle">Séances / semaine</div></div>
      </div>

      <div className="deux-colonnes">
        <div>
          <h2 style={{ marginTop: 28 }}>Prochaines séances</h2>
          <div className="cadre-tableau">
            <table className="tableau"><tbody>
              {seancesTriees.slice(0, 5).map((s) => (
                <tr key={s.idSeance}>
                  <td><strong>{s.jour}</strong> {s.heureDebut.slice(0, 5)}</td>
                  <td>{s.nomCours}</td>
                  <td>{s.salle}</td>
                </tr>
              ))}
              {seances.length === 0 && <tr><td className="vide">Aucune séance.</td></tr>}
            </tbody></table>
          </div>
        </div>
        <div>
          <h2 style={{ marginTop: 28 }}>Dernières notes</h2>
          <div className="cadre-tableau">
            <table className="tableau"><tbody>
              {toutesNotes.slice(-5).reverse().map((n) => (
                <tr key={n.idNote}><td>{n.cours}</td><td>{n.typeEvaluation}</td><td><strong>{n.valeur}</strong>/20</td></tr>
              ))}
              {toutesNotes.length === 0 && <tr><td className="vide">Aucune note.</td></tr>}
            </tbody></table>
          </div>
        </div>
      </div>
    </div>
  )
}
