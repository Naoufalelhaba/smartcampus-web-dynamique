import { useEffect, useState } from 'react'
import { api } from '../../api'
import { useAuth } from '../../auth/AuthContext'
import Modale from '../../components/Modale'

const TYPES = ['CC1', 'CC2', 'TP', 'Projet', 'Examen']
const FORM_VIDE = { idEtudiant: null, typeEvaluation: 'CC1', valeur: '', coefficient: 1 }

export default function CarnetNotes() {
  const { utilisateur } = useAuth()
  const [mesCours, setMesCours] = useState([])
  const [coursId, setCoursId] = useState('')
  const [carnet, setCarnet] = useState(null)
  const [erreur, setErreur] = useState('')

  const [modale, setModale] = useState(null)
  const [form, setForm] = useState(FORM_VIDE)
  const [noteCourante, setNoteCourante] = useState(null)
  const [erreurForm, setErreurForm] = useState('')

  useEffect(() => {
    api.get('/cours.php?idEnseignant=' + utilisateur.idUser)
      .then((cours) => {
        setMesCours(cours)
        if (cours.length > 0) setCoursId(String(cours[0].idCours))
      })
      .catch((err) => setErreur(err.message))
  }, [utilisateur.idUser])

  async function chargerCarnet() {
    if (!coursId) { setCarnet(null); return }
    try { setCarnet(await api.get('/notes.php?idCours=' + coursId)); setErreur('') }
    catch (err) { setErreur(err.message) }
  }
  useEffect(() => { chargerCarnet() }, [coursId])

  function ouvrirAjout(idEtudiant) { setForm({ ...FORM_VIDE, idEtudiant }); setNoteCourante(null); setErreurForm(''); setModale('ajout') }
  function ouvrirEdit(note) {
    setForm({ typeEvaluation: note.typeEvaluation, valeur: note.valeur, coefficient: note.coefficient })
    setNoteCourante(note); setErreurForm(''); setModale('edit')
  }

  async function enregistrer(ev) {
    ev.preventDefault(); setErreurForm('')
    try {
      if (modale === 'ajout') {
        await api.post('/notes.php', {
          idCours: Number(coursId), idEtudiant: form.idEtudiant,
          typeEvaluation: form.typeEvaluation, valeur: Number(form.valeur), coefficient: Number(form.coefficient),
        })
      } else {
        await api.put('/notes.php?id=' + noteCourante.idNote, {
          valeur: Number(form.valeur), typeEvaluation: form.typeEvaluation, coefficient: Number(form.coefficient),
        })
      }
      setModale(null); chargerCarnet()
    } catch (err) { setErreurForm(err.message) }
  }
  async function supprimerNote() {
    if (!window.confirm('Supprimer cette note ?')) return
    try { await api.delete('/notes.php?id=' + noteCourante.idNote); setModale(null); chargerCarnet() }
    catch (err) { setErreurForm(err.message) }
  }
  async function valider() {
    if (!window.confirm('Valider et verrouiller toutes les notes de ce cours ? Elles ne pourront plus être modifiées.')) return
    try { await api.post('/notes.php?action=valider', { idCours: Number(coursId) }); chargerCarnet() }
    catch (err) { setErreur(err.message) }
  }

  return (
    <div>
      <div className="entete-page">
        <h1>Carnet de notes</h1>
        {carnet && !carnet.verrouille && carnet.etudiants.length > 0 &&
          <button className="btn-principal" onClick={valider}>Valider &amp; verrouiller</button>}
      </div>

      <div className="barre-outils">
        <label>Cours
          <select value={coursId} onChange={(e) => setCoursId(e.target.value)}>
            {mesCours.length === 0 && <option value="">Aucun cours</option>}
            {mesCours.map((c) => <option key={c.idCours} value={c.idCours}>{c.nomCours} ({c.promotion})</option>)}
          </select>
        </label>
      </div>

      {erreur && <div className="alerte">{erreur}</div>}
      {carnet && carnet.verrouille && <div className="info">🔒 Notes validées — elles ne sont plus modifiables.</div>}

      {carnet && (
        <div className="cadre-tableau">
          <table className="tableau">
            <thead><tr><th>Étudiant</th><th>Notes</th><th>Moyenne</th>{!carnet.verrouille && <th></th>}</tr></thead>
            <tbody>
              {carnet.etudiants.map((e) => (
                <tr key={e.idUser}>
                  <td>{e.prenom} {e.nom}</td>
                  <td>
                    <div className="chips">
                      {e.notes.map((n) => (
                        <span
                          key={n.idNote}
                          className={'chip' + (carnet.verrouille ? '' : ' chip-clic')}
                          onClick={() => !carnet.verrouille && ouvrirEdit(n)}
                        >
                          {n.typeEvaluation} · {n.valeur}{Number(n.coefficient) !== 1 ? ` (×${Number(n.coefficient)})` : ''}
                        </span>
                      ))}
                      {e.notes.length === 0 && <span className="chip chip-grise">—</span>}
                    </div>
                  </td>
                  <td><strong>{e.moyenne ?? '—'}</strong></td>
                  {!carnet.verrouille && <td><button className="btn-secondaire btn-petit" onClick={() => ouvrirAjout(e.idUser)}>+ Note</button></td>}
                </tr>
              ))}
              {carnet.etudiants.length === 0 && <tr><td colSpan="4" className="vide">Aucun étudiant inscrit à ce cours.</td></tr>}
            </tbody>
          </table>
        </div>
      )}

      {modale && (
        <Modale titre={modale === 'ajout' ? 'Ajouter une note' : 'Modifier la note'} onFermer={() => setModale(null)}>
          <form onSubmit={enregistrer}>
            {erreurForm && <div className="alerte">{erreurForm}</div>}
            <div className="ligne-champs">
              <label>Type
                <select value={form.typeEvaluation} onChange={(e) => setForm({ ...form, typeEvaluation: e.target.value })}>
                  {TYPES.map((t) => <option key={t}>{t}</option>)}
                </select>
              </label>
              <label>Note /20<input type="number" step="0.01" min="0" max="20" value={form.valeur} onChange={(e) => setForm({ ...form, valeur: e.target.value })} required /></label>
            </div>
            <label>Coefficient<input type="number" step="0.5" min="0.5" value={form.coefficient} onChange={(e) => setForm({ ...form, coefficient: e.target.value })} required /></label>
            <div className="modale-actions">
              {modale === 'edit' && <button type="button" className="btn-danger" onClick={supprimerNote} style={{ marginRight: 'auto' }}>Supprimer</button>}
              <button type="button" className="btn-secondaire" onClick={() => setModale(null)}>Annuler</button>
              <button type="submit" className="btn-principal">Enregistrer</button>
            </div>
          </form>
        </Modale>
      )}
    </div>
  )
}
