export default function Modale({ titre, children, onFermer }) {
  return (
    <div className="fond-modale" onClick={onFermer}>
      <div className="modale" onClick={(e) => e.stopPropagation()}>
        <h2>{titre}</h2>
        {children}
      </div>
    </div>
  )
}
