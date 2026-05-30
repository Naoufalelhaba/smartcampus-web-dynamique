const BASE = '/api'

async function requete(url, options = {}) {
  const reponse = await fetch(BASE + url, {
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    ...options,
  })

  let donnees = null
  try {
    donnees = await reponse.json()
  } catch {
    donnees = null
  }

  if (!reponse.ok) {
    const message = donnees && donnees.message ? donnees.message : 'Erreur réseau'
    throw new Error(message)
  }
  return donnees
}

export const api = {
  get: (url) => requete(url),
  post: (url, body) => requete(url, { method: 'POST', body: JSON.stringify(body ?? {}) }),
  put: (url, body) => requete(url, { method: 'PUT', body: JSON.stringify(body ?? {}) }),
  delete: (url) => requete(url, { method: 'DELETE' }),
}
