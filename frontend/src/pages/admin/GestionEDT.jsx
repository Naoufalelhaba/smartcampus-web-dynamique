import { useEffect, useState } from 'react'
import { api } from '../../api'
import Modale from '../../components/Modale'
import EmploiDuTemps from '../../components/EmploiDuTemps'

const JOURS = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']
const FORM_VIDE = { idCours: '', jour: 'Lundi', heureDebut: '08:00', heureFin: '10:00', salle: '' }

export default function GestionEDT() {
  const [seances, setSeances] = useState([])
  const [cours, setCours] = useState([])
  const [promotion, setPromotion] = useState('')
  const [erreur, setErreur] = useState('')

  const [modale, setModale] = useState(false)
  const [form, setForm] = useState(FORM_VIDE)
  const [idCourant, setIdCourant] = useState(null)
  const [erreurForm, setErreurForm] = useState('')

  async function charger() {
    try {
      const p = new URLSearchParams()
      if (promotion) p.set('promotion', promotion)
      setSeances(await api.get('/seances.php?' + p.toString()))
      setErreur('')
    } catch (err) { setErreur(err.message) }
  }
  useEffect(() => { charger() }, [promotion])
  useEffect(() => { api.get('/cours.php').then(setCours).catch(() => {}) }, [])

  function ouvrirCreation() { setForm(FORM_VIDE); setIdCourant(null); setErreurForm(''); setModale(true) }
  function ouvrirModification(s) {
    setForm({ idCours: s.idCours, jour: s.jour, heureDebut: s.heureDebut.slice(0, 5), heureFin: s.heureFin.slice(0, 5), salle: s.salle })
    setIdCourant(s.idSeance); setErreurForm(''); setModale(true)
  }

  async function enregistrer(ev) {
    ev.preventDefault(); setErreurForm('')
    try {
      const corps = { ...form, idCours: Number(form.idCours) }
      if (idCourant) await api.put('/seances.php?id=' + idCourant, corps)
      else await api.post('/seances.php', corps)
      setModale(false); charger()
    } catch (err) { setErreurForm(err.message) }
  }
  async function supprimer(s) {
    if (!window.confirm('Supprimer cette séance ?')) return
    try { await api.delete('/seances.php?id=' + s.idSeance); charger() } catch (err) { setErreur(err.message) }
  }

  const seancesTriees = [...seances].sort(
    (a, b) => JOURS.indexOf(a.jour) - JOURS.indexOf(b.jour) || a.heureDebut.localeCompare(b.heureDebut)
  )

  return (
    <div>
      <div className="entete-page">
        <h1>Gestion de l'emploi du temps</h1>
        <button className="btn-principal" onClick={ouvrirCreation}>+ Ajouter une séance</button>
      </div>

      <div className="barre-outils">
        <label>Promotion
          <select value={promotion} onChange={(e) => setPromotion(e.target.value)}>
            <option value="">Toutes</option><option>ING1</option><option>ING2</option><option>ING3</option>
          </select>
        </label>
      </div>

      {erreur && <div className="alerte">{erreur}</div>}

      <EmploiDuTemps seances={seances} />

      <h2 style={{ marginTop: 28 }}>Séances</h2>
      <div className="cadre-tableau">
        <table className="tableau">
          <thead><tr><th>Jour</th><th>Horaire</th><th>Cours</th><th>Promo</th><th>Salle</th><th></th></tr></thead>
          <tbody>
            {seancesTriees.map((s) => (
              <tr key={s.idSeance}>
                <td>{s.jour}</td>
                <td>{s.heureDebut.slice(0, 5)}–{s.heureFin.slice(0, 5)}</td>
                <td>{s.nomCours}</td>
                <td>{s.promotion}</td>
                <td>{s.salle}</td>
                <td><div className="actions">
                  <button className="btn-secondaire btn-petit" onClick={() => ouvrirModification(s)}>Modifier</button>
                  <button className="btn-danger btn-petit" onClick={() => supprimer(s)}>Supprimer</button>
                </div></td>
              </tr>
            ))}
            {seances.length === 0 && <tr><td colSpan="6" className="vide">Aucune séance.</td></tr>}
          </tbody>
        </table>
      </div>

      {modale && (
        <Modale titre={idCourant ? 'Modifier la séance' : 'Ajouter une séance'} onFermer={() => setModale(false)}>
          <form onSubmit={enregistrer}>
            {erreurForm && <div className="alerte">{erreurForm}</div>}
            <label>Cours
              <select value={form.idCours} onChange={(e) => setForm({ ...form, idCours: e.target.value })} required>
                <option value="">Choisir un cours…</option>
                {cours.map((c) => <option key={c.idCours} value={c.idCours}>{c.nomCours} ({c.promotion})</option>)}
              </select>
            </label>
            <div className="ligne-champs">
              <label>Jour
                <select value={form.jour} onChange={(e) => setForm({ ...form, jour: e.target.value })}>
                  {JOURS.map((j) => <option key={j}>{j}</option>)}
                </select>
              </label>
              <label>Salle<input value={form.salle} onChange={(e) => setForm({ ...form, salle: e.target.value })} required /></label>
            </div>
            <div className="ligne-champs">
              <label>Début<input type="time" value={form.heureDebut} onChange={(e) => setForm({ ...form, heureDebut: e.target.value })} required /></label>
              <label>Fin<input type="time" value={form.heureFin} onChange={(e) => setForm({ ...form, heureFin: e.target.value })} required /></label>
            </div>
            <div className="modale-actions">
              <button type="button" className="btn-secondaire" onClick={() => setModale(false)}>Annuler</button>
              <button type="submit" className="btn-principal">Enregistrer</button>
            </div>
          </form>
        </Modale>
      )}
    </div>
  )
}
