const JOURS = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']

export default function EmploiDuTemps({ seances }) {
  return (
    <div className="edt">
      {JOURS.map((jour) => {
        const duJour = seances
          .filter((s) => s.jour === jour)
          .sort((a, b) => a.heureDebut.localeCompare(b.heureDebut))

        return (
          <div key={jour} className="edt-jour">
            <h3>{jour}</h3>
            {duJour.length === 0 ? (
              <p className="edt-vide">—</p>
            ) : (
              duJour.map((s) => (
                <div key={s.idSeance} className="edt-seance">
                  <div className="edt-heure">{s.heureDebut.slice(0, 5)} – {s.heureFin.slice(0, 5)}</div>
                  <div className="edt-cours">{s.nomCours}</div>
                  <div className="edt-info">
                    {s.salle}
                    {s.enseignant ? ' · ' + s.enseignant : ''}
                    {s.promotion ? ' · ' + s.promotion : ''}
                  </div>
                </div>
              ))
            )}
          </div>
        )
      })}
    </div>
  )
}
