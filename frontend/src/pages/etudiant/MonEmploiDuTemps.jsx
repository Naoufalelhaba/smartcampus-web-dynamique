import { useEffect, useState } from 'react'
import { api } from '../../api'
import EmploiDuTemps from '../../components/EmploiDuTemps'

export default function MonEmploiDuTemps() {
  const [seances, setSeances] = useState(null)
  const [erreur, setErreur] = useState('')

  useEffect(() => {
    api.get('/seances.php').then(setSeances).catch((e) => setErreur(e.message))
  }, [])

  if (erreur) return <div className="alerte">{erreur}</div>

  return (
    <div>
      <h1>Mon emploi du temps</h1>
      <p className="intro">Vos séances de la semaine.</p>
      {!seances ? <p>Chargement…</p> : <EmploiDuTemps seances={seances} />}
    </div>
  )
}
