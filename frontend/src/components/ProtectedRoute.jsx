import { Navigate } from 'react-router-dom'
import { useAuth } from '../auth/AuthContext'

export default function ProtectedRoute({ children, roles }) {
  const { utilisateur, chargement } = useAuth()

  if (chargement) return <div className="centre">Chargement…</div>
  if (!utilisateur) return <Navigate to="/connexion" replace />
  if (roles && !roles.includes(utilisateur.role)) return <Navigate to="/" replace />

  return children
}
