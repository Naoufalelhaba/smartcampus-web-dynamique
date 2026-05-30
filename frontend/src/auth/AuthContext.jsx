import { createContext, useContext, useState, useEffect } from 'react'
import { api } from '../api'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [utilisateur, setUtilisateur] = useState(null)
  const [chargement, setChargement] = useState(true)

  useEffect(() => {
    api.get('/auth/me.php')
      .then((d) => {
        if (d.authentifie) setUtilisateur(d.utilisateur)
      })
      .catch(() => {})
      .finally(() => setChargement(false))
  }, [])

  async function connexion(email, motDePasse) {
    const d = await api.post('/auth/login.php', { email, motDePasse })
    setUtilisateur(d.utilisateur)
    return d.utilisateur
  }

  async function deconnexion() {
    await api.post('/auth/logout.php')
    setUtilisateur(null)
  }

  return (
    <AuthContext.Provider value={{ utilisateur, chargement, connexion, deconnexion }}>
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth() {
  return useContext(AuthContext)
}
