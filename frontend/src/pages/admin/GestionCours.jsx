import { useEffect, useState } from 'react'
import { api } from '../../api'
import Modale from '../../components/Modale'

const FORM_VIDE = {
  nomCours: '', description: '', promotion: 'ING1', semestre: 'S1',
  departement: '', credits: 3, capaciteMax: 30, idEnseignant: '',
}

export default function GestionCours() {
  const [liste, setListe] = useState([])
  const [enseignants, setEnseignants] = useState([])
  const [etudiants, setEtudiants] = useState([])
  const [recherche, setRecherche] = useState('')
  const [promotion, setPromotion] = useState('')
  const [semestre, setSemestre] = useState('')
  const [tri, setTri] = useState('nom')
  const [ordre, setOrdre] = useState('ASC')
  const [erreur, setErreur] = useState('')

  const [modale, setModale] = useState(null)
  const [form, setForm] = useState(FORM_VIDE)
  const [idCourant, setIdCourant] = useState(null)
  const [erreurForm, setErreurForm] = useState('')
  const [vueInscrits, setVueInscrits] = useState(null)

  async function charger() {
    try {
      const p = new URLSearchParams()
      if (recherche) p.set('recherche', recherche)
      if (promotion) p.set('promotion', promotion)
      if (semestre) p.set('semestre', semestre)
      p.set('tri', tri); p.set('ordre', ordre)
      setListe(await api.get('/cours.php?' + p.toString()))
      setErreur('')
    } catch (err) { setErreur(err.message) }
  }
  useEffect(() => { charger() }, [recherche, promotion, semestre, tri, ordre])
  useEffect(() => {
    api.get('/enseignants.php').then(setEnseignants).catch(() => {})
    api.get('/etudiants.php').then(setEtudiants).catch(() => {})
  }, [])

  function trier(colonne) {
    if (tri === colonne) setOrdre(ordre === 'ASC' ? 'DESC' : 'ASC')
    else { setTri(colonne); setOrdre('ASC') }
  }
  const fleche = (col) => (tri === col ? (ordre === 'ASC' ? ' ▲' : ' ▼') : '')

  function ouvrirCreation() { setForm(FORM_VIDE); setIdCourant(null); setErreurForm(''); setModale('form') }
  function ouvrirModification(c) {
    setForm({
      nomCours: c.nomCours, description: c.description || '', promotion: c.promotion, semestre: c.semestre,
      departement: c.departement, credits: c.credits, capaciteMax: c.capaciteMax, idEnseignant: c.idEnseignant || '',
    })
    setIdCourant(c.idCours); setErreurForm(''); setModale('form')
  }
  async function enregistrer(ev) {
    ev.preventDefault(); setErreurForm('')
    try {
      if (idCourant) await api.put('/cours.php?id=' + idCourant, form)
      else await api.post('/cours.php', form)
      setModale(null); charger()
    } catch (err) { setErreurForm(err.message) }
  }
  async function supprimer(c) {
    if (!window.confirm(`Supprimer le cours « ${c.nomCours} » ?`)) return
    try { await api.delete('/cours.php?id=' + c.idCours); charger() } catch (err) { setErreur(err.message) }
  }

  async function ouvrirInscrits(c) {
    setModale('inscrits'); setVueInscrits({ cours: c, inscrits: null, ajout: '' })
    try {
      const inscrits = await api.get('/inscriptions.php?idCours=' + c.idCours)
      setVueInscrits((v) => ({ ...v, inscrits }))
    } catch (err) { setErreur(err.message) }
  }
  async function rafraichirInscrits() {
    const inscrits = await api.get('/inscriptions.php?idCours=' + vueInscrits.cours.idCours)
    setVueInscrits((v) => ({ ...v, inscrits, ajout: '' }))
    charger()
  }
  async function inscrire() {
    if (!vueInscrits.ajout) return
    try {
      await api.post('/inscriptions.php', { idEtudiant: Number(vueInscrits.ajout), idCours: vueInscrits.cours.idCours })
      rafraichirInscrits()
    } catch (err) { window.alert(err.message) }
  }
  async function desinscrire(idEtudiant) {
    try {
      await api.delete(`/inscriptions.php?idCours=${vueInscrits.cours.idCours}&idEtudiant=${idEtudiant}`)
      rafraichirInscrits()
    } catch (err) { window.alert(err.message) }
  }

  return (
    <div>
      <div className="entete-page">
        <h1>Gestion des cours</h1>
        <button className="btn-principal" onClick={ouvrirCreation}>+ Ajouter un cours</button>
      </div>

      <div className="barre-outils">
        <label>Recherche
          <input value={recherche} onChange={(e) => setRecherche(e.target.value)} placeholder="Nom du cours…" />
        </label>
        <label>Promotion
          <select value={promotion} onChange={(e) => setPromotion(e.target.value)}>
            <option value="">Toutes</option><option>ING1</option><option>ING2</option><option>ING3</option>
          </select>
        </label>
        <label>Semestre
          <select value={semestre} onChange={(e) => setSemestre(e.target.value)}>
            <option value="">Tous</option><option>S1</option><option>S2</option><option>S3</option><option>S4</option><option>S5</option><option>S6</option>
          </select>
        </label>
      </div>

      {erreur && <div className="alerte">{erreur}</div>}

      <div className="cadre-tableau">
        <table className="tableau">
          <thead>
            <tr>
              <th className="triable" onClick={() => trier('nom')}>Cours{fleche('nom')}</th>
              <th className="triable" onClick={() => trier('promotion')}>Promo{fleche('promotion')}</th>
              <th className="triable" onClick={() => trier('semestre')}>Sem.{fleche('semestre')}</th>
              <th className="triable" onClick={() => trier('departement')}>Département{fleche('departement')}</th>
              <th className="triable" onClick={() => trier('credits')}>Crédits{fleche('credits')}</th>
              <th className="triable" onClick={() => trier('inscrits')}>Inscrits{fleche('inscrits')}</th>
              <th>Enseignant</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            {liste.map((c) => (
              <tr key={c.idCours}>
                <td>{c.nomCours}</td>
                <td><span className="pastille pastille-grise">{c.promotion}</span></td>
                <td>{c.semestre}</td>
                <td>{c.departement}</td>
                <td>{c.credits}</td>
                <td>{c.nbInscrits}/{c.capaciteMax}</td>
                <td>{c.enseignant ?? '—'}</td>
                <td><div className="actions">
                  <button className="btn-secondaire btn-petit" onClick={() => ouvrirInscrits(c)}>Inscrits</button>
                  <button className="btn-secondaire btn-petit" onClick={() => ouvrirModification(c)}>Modifier</button>
                  <button className="btn-danger btn-petit" onClick={() => supprimer(c)}>Supprimer</button>
                </div></td>
              </tr>
            ))}
            {liste.length === 0 && <tr><td colSpan="8" className="vide">Aucun cours trouvé.</td></tr>}
          </tbody>
        </table>
      </div>

      {modale === 'form' && (
        <Modale titre={idCourant ? 'Modifier le cours' : 'Ajouter un cours'} onFermer={() => setModale(null)}>
          <form onSubmit={enregistrer}>
            {erreurForm && <div className="alerte">{erreurForm}</div>}
            <label>Nom du cours<input value={form.nomCours} onChange={(e) => setForm({ ...form, nomCours: e.target.value })} required /></label>
            <label>Description<input value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} /></label>
            <div className="ligne-champs">
              <label>Promotion
                <select value={form.promotion} onChange={(e) => setForm({ ...form, promotion: e.target.value })}>
                  <option>ING1</option><option>ING2</option><option>ING3</option>
                </select>
              </label>
              <label>Semestre
                <select value={form.semestre} onChange={(e) => setForm({ ...form, semestre: e.target.value })}>
                  <option>S1</option><option>S2</option><option>S3</option><option>S4</option><option>S5</option><option>S6</option>
                </select>
              </label>
            </div>
            <div className="ligne-champs">
              <label>Département<input value={form.departement} onChange={(e) => setForm({ ...form, departement: e.target.value })} required /></label>
              <label>Crédits<input type="number" min="1" max="30" value={form.credits} onChange={(e) => setForm({ ...form, credits: e.target.value })} required /></label>
            </div>
            <div className="ligne-champs">
              <label>Capacité max<input type="number" min="1" value={form.capaciteMax} onChange={(e) => setForm({ ...form, capaciteMax: e.target.value })} required /></label>
              <label>Enseignant
                <select value={form.idEnseignant} onChange={(e) => setForm({ ...form, idEnseignant: e.target.value })}>
                  <option value="">— Aucun —</option>
                  {enseignants.map((p) => <option key={p.idUser} value={p.idUser}>{p.prenom} {p.nom}</option>)}
                </select>
              </label>
            </div>
            <div className="modale-actions">
              <button type="button" className="btn-secondaire" onClick={() => setModale(null)}>Annuler</button>
              <button type="submit" className="btn-principal">Enregistrer</button>
            </div>
          </form>
        </Modale>
      )}

      {modale === 'inscrits' && vueInscrits && (
        <Modale titre={`Inscrits — ${vueInscrits.cours.nomCours}`} onFermer={() => setModale(null)}>
          <p className="intro">{vueInscrits.cours.nbInscrits}/{vueInscrits.cours.capaciteMax} places occupées</p>

          <div className="barre-outils">
            <label style={{ flex: 1 }}>Inscrire un étudiant
              <select value={vueInscrits.ajout} onChange={(e) => setVueInscrits({ ...vueInscrits, ajout: e.target.value })}>
                <option value="">Choisir un étudiant…</option>
                {etudiants.map((e) => <option key={e.idUser} value={e.idUser}>{e.prenom} {e.nom} ({e.promotion})</option>)}
              </select>
            </label>
            <button className="btn-principal" onClick={inscrire}>Inscrire</button>
          </div>

          {!vueInscrits.inscrits ? <p>Chargement…</p> : (
            <table className="tableau">
              <thead><tr><th>Nom</th><th>Promotion</th><th>Moyenne</th><th></th></tr></thead>
              <tbody>
                {vueInscrits.inscrits.map((e) => (
                  <tr key={e.idUser}>
                    <td>{e.prenom} {e.nom}</td>
                    <td>{e.promotion}</td>
                    <td>{e.moyenne ?? '—'}</td>
                    <td><button className="btn-danger btn-petit" onClick={() => desinscrire(e.idUser)}>Retirer</button></td>
                  </tr>
                ))}
                {vueInscrits.inscrits.length === 0 && <tr><td colSpan="4" className="vide">Aucun inscrit.</td></tr>}
              </tbody>
            </table>
          )}
        </Modale>
      )}
    </div>
  )
}
