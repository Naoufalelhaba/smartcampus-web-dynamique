export function cheminAccueil(role) {
  if (role === 'admin') return '/admin'
  if (role === 'enseignant') return '/enseignant'
  if (role === 'etudiant') return '/etudiant'
  return '/connexion'
}
