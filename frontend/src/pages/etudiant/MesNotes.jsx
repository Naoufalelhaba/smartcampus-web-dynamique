import { useEffect, useState } from 'react'
import { api } from '../../api'

export default function MesNotes() {
  const [resultats, setResultats] = useState(null)
  const [erreur, setErreur] = useState('')

  useEffect(() => {
    api.get('/notes.php').then(setResultats).catch((e) => setErreur(e.message))
  }, [])

  if (erreur) return <div className="alerte">{erreur}</div>
  if (!resultats) return <p>Chargement…</p>

  return (
    <div>
      <h1>Mes notes</h1>

      <div className="carte" style={{ marginBottom: 20, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <span>Moyenne générale (pondérée par les crédits)</span>
        <span style={{ fontSize: 28, fontWeight: 700, color: 'var(--primaire)' }}>{resultats.moyenneGenerale ?? '—'}</span>
      </div>

      {resultats.cours.map((c) => (
        <div key={c.idCours} className="carte" style={{ marginBottom: 14 }}>
          <div className="entete-page" style={{ marginBottom: 10 }}>
            <h3 style={{ margin: 0 }}>{c.nomCours} <small>({c.semestre} · {c.credits} crédits)</small></h3>
            <strong>Moyenne : {c.moyenne ?? '—'}</strong>
          </div>
          {c.notes.length === 0
            ? <p className="intro" style={{ margin: 0 }}>Aucune note pour ce cours.</p>
            : (
              <div className="chips">
                {c.notes.map((n) => (
                  <span key={n.idNote} className="chip">
                    {n.typeEvaluation} · {n.valeur}/20{Number(n.coefficient) !== 1 ? ` (×${Number(n.coefficient)})` : ''}
                  </span>
                ))}
              </div>
            )}
        </div>
      ))}
      {resultats.cours.length === 0 && <p className="vide">Vous n'êtes inscrit à aucun cours.</p>}
    </div>
  )
}
