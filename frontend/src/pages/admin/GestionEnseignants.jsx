import { useEffect, useState } from 'react'
import { api } from '../../api'
import Modale from '../../components/Modale'

const FORM_VIDE = { nom: '', prenom: '', email: '', motDePasse: '' }

export default function GestionEnseignants() {
  const [liste, setListe] = useState([])
  const [recherche, setRecherche] = useState('')
  const [chargement, setChargement] = useState(true)
  const [erreur, setErreur] = useState('')

  const [modale, setModale] = useState(null)
  const [form, setForm] = useState(FORM_VIDE)
  const [idCourant, setIdCourant] = useState(null)
  const [erreurForm, setErreurForm] = useState('')
  const [detail, setDetail] = useState(null)

  async function charger() {
    setChargement(true)
    try {
      const p = new URLSearchParams()
      if (recherche) p.set('recherche', recherche)
      setListe(await api.get('/enseignants.php?' + p.toString()))
      setErreur('')
    } catch (err) {
      setErreur(err.message)
    } finally {
      setChargement(false)
    }
  }
  useEffect(() => { charger() }, [recherche])

  function ouvrirCreation() { setForm(FORM_VIDE); setIdCourant(null); setErreurForm(''); setModale('form') }
  function ouvrirModification(e) {
    setForm({ nom: e.nom, prenom: e.prenom, email: e.email, motDePasse: '' })
    setIdCourant(e.idUser); setErreurForm(''); setModale('form')
  }
  async function ouvrirCours(e) {
    setModale('cours'); setDetail(null)
    try { setDetail(await api.get('/enseignants.php?id=' + e.idUser)) } catch (err) { setErreurForm(err.message) }
  }

  async function enregistrer(ev) {
    ev.preventDefault(); setErreurForm('')
    try {
      if (idCourant) await api.put('/enseignants.php?id=' + idCourant, form)
      else await api.post('/enseignants.php', form)
      setModale(null); charger()
    } catch (err) { setErreurForm(err.message) }
  }
  async function supprimer(e) {
    if (!window.confirm(`Supprimer l'enseignant ${e.prenom} ${e.nom} ?`)) return
    try { await api.delete('/enseignants.php?id=' + e.idUser); charger() } catch (err) { setErreur(err.message) }
  }

  return (
    <div>
      <div className="entete-page">
        <h1>Gestion des enseignants</h1>
        <button className="btn-principal" onClick={ouvrirCreation}>+ Ajouter un enseignant</button>
      </div>

      <div className="barre-outils">
        <label>Recherche
          <input value={recherche} onChange={(e) => setRecherche(e.target.value)} placeholder="Nom, prénom, email…" />
        </label>
      </div>

      {erreur && <div className="alerte">{erreur}</div>}

      <div className="cadre-tableau">
        <table className="tableau">
          <thead><tr><th>Nom</th><th>Prénom</th><th>Email</th><th>Cours</th><th></th></tr></thead>
          <tbody>
            {liste.map((e) => (
              <tr key={e.idUser}>
                <td>{e.nom}</td>
                <td>{e.prenom}</td>
                <td>{e.email}</td>
                <td>{e.nbCours}</td>
                <td><div className="actions">
                  <button className="btn-secondaire btn-petit" onClick={() => ouvrirCours(e)}>Ses cours</button>
                  <button className="btn-secondaire btn-petit" onClick={() => ouvrirModification(e)}>Modifier</button>
                  <button className="btn-danger btn-petit" onClick={() => supprimer(e)}>Supprimer</button>
                </div></td>
              </tr>
            ))}
            {!chargement && liste.length === 0 && <tr><td colSpan="5" className="vide">Aucun enseignant trouvé.</td></tr>}
          </tbody>
        </table>
      </div>

      {modale === 'form' && (
        <Modale titre={idCourant ? "Modifier l'enseignant" : 'Ajouter un enseignant'} onFermer={() => setModale(null)}>
          <form onSubmit={enregistrer}>
            {erreurForm && <div className="alerte">{erreurForm}</div>}
            <div className="ligne-champs">
              <label>Nom<input value={form.nom} onChange={(e) => setForm({ ...form, nom: e.target.value })} required /></label>
              <label>Prénom<input value={form.prenom} onChange={(e) => setForm({ ...form, prenom: e.target.value })} required /></label>
            </div>
            <label>Email<input type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} required /></label>
            <label>Mot de passe {idCourant && <small>(vide = inchangé)</small>}
              <input type="password" value={form.motDePasse} onChange={(e) => setForm({ ...form, motDePasse: e.target.value })} required={!idCourant} />
            </label>
            <div className="modale-actions">
              <button type="button" className="btn-secondaire" onClick={() => setModale(null)}>Annuler</button>
              <button type="submit" className="btn-principal">Enregistrer</button>
            </div>
          </form>
        </Modale>
      )}

      {modale === 'cours' && (
        <Modale titre="Cours de l'enseignant" onFermer={() => setModale(null)}>
          {!detail ? <p>Chargement…</p> : (
            <div>
              <h3>{detail.enseignant.prenom} {detail.enseignant.nom}</h3>
              <p className="intro">{detail.enseignant.email}</p>
              <table className="tableau">
                <thead><tr><th>Cours</th><th>Promotion</th><th>Semestre</th><th>Inscrits</th></tr></thead>
                <tbody>
                  {detail.cours.map((c) => (
                    <tr key={c.idCours}><td>{c.nomCours}</td><td>{c.promotion}</td><td>{c.semestre}</td><td>{c.nbInscrits}/{c.capaciteMax}</td></tr>
                  ))}
                  {detail.cours.length === 0 && <tr><td colSpan="4" className="vide">Aucun cours assigné.</td></tr>}
                </tbody>
              </table>
            </div>
          )}
        </Modale>
      )}
    </div>
  )
}
