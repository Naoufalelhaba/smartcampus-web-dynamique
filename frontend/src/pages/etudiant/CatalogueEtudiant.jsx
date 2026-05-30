import { useEffect, useState } from 'react'
import { api } from '../../api'

export default function CatalogueEtudiant() {
  const [cours, setCours] = useState([])
  const [mesIds, setMesIds] = useState(new Set())
  const [recherche, setRecherche] = useState('')
  const [promotion, setPromotion] = useState('')
  const [erreur, setErreur] = useState('')
  const [message, setMessage] = useState('')

  async function charger() {
    try {
      const p = new URLSearchParams()
      if (recherche) p.set('recherche', recherche)
      if (promotion) p.set('promotion', promotion)
      const [tous, miens] = await Promise.all([
        api.get('/cours.php?' + p.toString()),
        api.get('/inscriptions.php'),
      ])
      setCours(tous)
      setMesIds(new Set(miens.map((c) => c.idCours)))
      setErreur('')
    } catch (err) { setErreur(err.message) }
  }
  useEffect(() => { charger() }, [recherche, promotion])

  async function inscrire(c) {
    setMessage('')
    try { await api.post('/inscriptions.php', { idCours: c.idCours }); charger() }
    catch (err) { setMessage(err.message) }
  }
  async function desinscrire(c) {
    setMessage('')
    try { await api.delete('/inscriptions.php?idCours=' + c.idCours); charger() }
    catch (err) { setMessage(err.message) }
  }

  return (
    <div>
      <h1>Catalogue des cours</h1>
      <p className="intro">Inscrivez-vous aux cours disponibles.</p>

      <div className="barre-outils">
        <label>Recherche<input value={recherche} onChange={(e) => setRecherche(e.target.value)} placeholder="Nom du cours…" /></label>
        <label>Promotion
          <select value={promotion} onChange={(e) => setPromotion(e.target.value)}>
            <option value="">Toutes</option><option>ING1</option><option>ING2</option><option>ING3</option>
          </select>
        </label>
      </div>

      {erreur && <div className="alerte">{erreur}</div>}
      {message && <div className="info">{message}</div>}

      <div className="catalogue">
        {cours.map((c) => {
          const inscrit = mesIds.has(c.idCours)
          const complet = Number(c.nbInscrits) >= Number(c.capaciteMax)
          return (
            <div key={c.idCours} className="carte carte-cours">
              <div className="titre-cours">{c.nomCours}</div>
              <div className="meta"><span className="pastille pastille-grise">{c.promotion}</span> · {c.semestre} · {c.departement} · {c.credits} crédits</div>
              <div className="desc">{c.description || 'Pas de description.'}</div>
              <div className="meta">Enseignant : {c.enseignant ?? '—'}</div>
              <div className="pied">
                <span className={complet && !inscrit ? 'pastille pastille-rouge' : 'pastille pastille-grise'}>
                  {c.nbInscrits}/{c.capaciteMax}{complet ? ' · Complet' : ''}
                </span>
                {inscrit
                  ? <button className="btn-danger btn-petit" onClick={() => desinscrire(c)}>Se désinscrire</button>
                  : <button className="btn-principal btn-petit" onClick={() => inscrire(c)} disabled={complet}>{complet ? 'Complet' : "S'inscrire"}</button>}
              </div>
            </div>
          )
        })}
        {cours.length === 0 && <p className="vide">Aucun cours.</p>}
      </div>
    </div>
  )
}
