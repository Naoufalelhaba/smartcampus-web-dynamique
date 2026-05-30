import { Routes, Route, Navigate } from 'react-router-dom'
import { useAuth } from './auth/AuthContext'
import { cheminAccueil } from './roles'
import ProtectedRoute from './components/ProtectedRoute'
import Layout from './components/Layout'
import Login from './pages/Login'
import DashboardAdmin from './pages/admin/DashboardAdmin'
import GestionEtudiants from './pages/admin/GestionEtudiants'
import GestionEnseignants from './pages/admin/GestionEnseignants'
import GestionCours from './pages/admin/GestionCours'
import GestionEDT from './pages/admin/GestionEDT'
import DashboardEnseignant from './pages/enseignant/DashboardEnseignant'
import CarnetNotes from './pages/enseignant/CarnetNotes'
import EmploiDuTempsEnseignant from './pages/enseignant/EmploiDuTempsEnseignant'
import DashboardEtudiant from './pages/etudiant/DashboardEtudiant'
import CatalogueEtudiant from './pages/etudiant/CatalogueEtudiant'
import MesNotes from './pages/etudiant/MesNotes'
import MonEmploiDuTemps from './pages/etudiant/MonEmploiDuTemps'

export default function App() {
  const { utilisateur, chargement } = useAuth()
  if (chargement) return <div className="centre">Chargement…</div>

  return (
    <Routes>
      <Route path="/connexion" element={<Login />} />

      <Route element={<ProtectedRoute roles={['admin']}><Layout /></ProtectedRoute>}>
        <Route path="/admin" element={<DashboardAdmin />} />
        <Route path="/admin/etudiants" element={<GestionEtudiants />} />
        <Route path="/admin/enseignants" element={<GestionEnseignants />} />
        <Route path="/admin/cours" element={<GestionCours />} />
        <Route path="/admin/edt" element={<GestionEDT />} />
      </Route>

      <Route element={<ProtectedRoute roles={['enseignant']}><Layout /></ProtectedRoute>}>
        <Route path="/enseignant" element={<DashboardEnseignant />} />
        <Route path="/enseignant/notes" element={<CarnetNotes />} />
        <Route path="/enseignant/edt" element={<EmploiDuTempsEnseignant />} />
      </Route>

      <Route element={<ProtectedRoute roles={['etudiant']}><Layout /></ProtectedRoute>}>
        <Route path="/etudiant" element={<DashboardEtudiant />} />
        <Route path="/etudiant/cours" element={<CatalogueEtudiant />} />
        <Route path="/etudiant/notes" element={<MesNotes />} />
        <Route path="/etudiant/edt" element={<MonEmploiDuTemps />} />
      </Route>

      <Route path="/" element={<Navigate to={utilisateur ? cheminAccueil(utilisateur.role) : '/connexion'} replace />} />
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  )
}
