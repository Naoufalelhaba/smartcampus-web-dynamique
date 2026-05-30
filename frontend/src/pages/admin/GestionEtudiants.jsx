import { useEffect, useState } from 'react'
import { api } from '../../api'
import Modale from '../../components/Modale'

const FORM_VIDE = { nom: '', prenom: '', email: '', motDePasse: '', promotion: 'ING1' }

export default function GestionEtudiants() {
  const [liste, setListe] = useState([])
  const [recherche, setRecherche] = useState('')
  const [promotion, setPromotion] = useState('')
  const [chargement, setChargement] = useState(true)
  const [erreur, setErreur] = useState('')

  const [modale, setModale] = useState(null)
  const [form, setForm] = useState(FORM_VIDE)
  const [idCourant, setIdCourant] = useState(null)
  const [erreurForm, setErreurForm] = useState('')
  const [profil, setProfil] = useState(null)

  async function charger() {
    setChargement(true)
    try {
      const p = new URLSearchParams()
      if (recherche) p.set('recherche', recherche)
      if (promotion) p.set('promotion', promotion)
      setListe(await api.get('/etudiants.php?' + p.toString()))
      setErreur('')
    } catch (err) {
      setErreur(err.message)
    } finally {
      setChargement(false)
    }
  }
  useEffect(() => { charger() }, [recherche, promotion])

  function ouvrirCreation() { setForm(FORM_VIDE); setIdCourant(null); setErreurForm(''); setModale('form') }
  function ouvrirModification(e) {
    setForm({ nom: e.nom, prenom: e.prenom, email: e.email, motDePasse: '', promotion: e.promotion })
    setIdCourant(e.idUser); setErreurForm(''); setModale('form')
  }
  async function ouvrirProfil(e) {
    setModale('profil'); setProfil(null)
    try { setProfil(await api.get('/etudiants.php?id=' + e.idUser)) } catch (err) { setErreurForm(err.message) }
  }

  async function enregistrer(ev) {
    ev.preventDefault(); setErreurForm('')
    try {
      if (idCourant) await api.put('/etudiants.php?id=' + idCourant, form)
      else await api.post('/etudiants.php', form)
      setModale(null); charger()
    } catch (err) { setErreurForm(err.message) }
  }
  async function supprimer(e) {
    if (!window.confirm(`Supprimer l'étudiant ${e.prenom} ${e.nom} ?`)) return
    try { await api.delete('/etudiants.php?id=' + e.idUser); charger() } catch (err) { setErreur(err.message) }
  }

  return (
    <div>
      <div className="entete-page">
        <h1>Gestion des étudiants</h1>
        <button className="btn-principal" onClick={ouvrirCreation}>+ Ajouter un étudiant</button>
      </div>

      <div className="barre-outils">
        <label>Recherche
          <input value={recherche} onChange={(e) => setRecherche(e.target.value)} placeholder="Nom, prénom, email…" />
        </label>
        <label>Promotion
          <select value={promotion} onChange={(e) => setPromotion(e.target.value)}>
            <option value="">Toutes</option><option>ING1</option><option>ING2</option><option>ING3</option>
          </select>
        </label>
      </div>

      {erreur && <div className="alerte">{erreur}</div>}

      <div className="cadre-tableau">
        <table className="tableau">
          <thead><tr><th>Nom</th><th>Prénom</th><th>Email</th><th>Promotion</th><th></th></tr></thead>
          <tbody>
            {liste.map((e) => (
              <tr key={e.idUser}>
                <td>{e.nom}</td>
                <td>{e.prenom}</td>
                <td>{e.email}</td>
                <td><span className="pastille pastille-grise">{e.promotion}</span></td>
                <td><div className="actions">
                  <button className="btn-secondaire btn-petit" onClick={() => ouvrirProfil(e)}>Profil</button>
                  <button className="btn-secondaire btn-petit" onClick={() => ouvrirModification(e)}>Modifier</button>
                  <button className="btn-danger btn-petit" onClick={() => supprimer(e)}>Supprimer</button>
                </div></td>
              </tr>
            ))}
            {!chargement && liste.length === 0 && <tr><td colSpan="5" className="vide">Aucun étudiant trouvé.</td></tr>}
          </tbody>
        </table>
      </div>

      {modale === 'form' && (
        <Modale titre={idCourant ? "Modifier l'étudiant" : 'Ajouter un étudiant'} onFermer={() => setModale(null)}>
          <form onSubmit={enregistrer}>
            {erreurForm && <div className="alerte">{erreurForm}</div>}
            <div className="ligne-champs">
              <label>Nom<input value={form.nom} onChange={(e) => setForm({ ...form, nom: e.target.value })} required /></label>
              <label>Prénom<input value={form.prenom} onChange={(e) => setForm({ ...form, prenom: e.target.value })} required /></label>
            </div>
            <label>Email<input type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} required /></label>
            <div className="ligne-champs">
              <label>Promotion
                <select value={form.promotion} onChange={(e) => setForm({ ...form, promotion: e.target.value })}>
                  <option>ING1</option><option>ING2</option><option>ING3</option>
                </select>
              </label>
              <label>Mot de passe {idCourant && <small>(vide = inchangé)</small>}
                <input type="password" value={form.motDePasse} onChange={(e) => setForm({ ...form, motDePasse: e.target.value })} required={!idCourant} />
              </label>
            </div>
            <div className="modale-actions">
              <button type="button" className="btn-secondaire" onClick={() => setModale(null)}>Annuler</button>
              <button type="submit" className="btn-principal">Enregistrer</button>
            </div>
          </form>
        </Modale>
      )}

      {modale === 'profil' && (
        <Modale titre="Profil de l'étudiant" onFermer={() => setModale(null)}>
          {!profil ? <p>Chargement…</p> : (
            <div>
              <h3>{profil.etudiant.prenom} {profil.etudiant.nom} <span className="pastille pastille-grise">{profil.etudiant.promotion}</span></h3>
              <p className="intro">{profil.etudiant.email}</p>
              <p><strong>Moyenne générale : </strong>{profil.moyenneGenerale ?? '—'}</p>
              <table className="tableau">
                <thead><tr><th>Cours</th><th>Semestre</th><th>Crédits</th><th>Moyenne</th></tr></thead>
                <tbody>
                  {profil.cours.map((c) => (
                    <tr key={c.idCours}><td>{c.nomCours}</td><td>{c.semestre}</td><td>{c.credits}</td><td>{c.moyenne ?? '—'}</td></tr>
                  ))}
                  {profil.cours.length === 0 && <tr><td colSpan="4" className="vide">Aucun cours.</td></tr>}
                </tbody>
              </table>
            </div>
          )}
        </Modale>
      )}
    </div>
  )
}
