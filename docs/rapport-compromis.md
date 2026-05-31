# Rapport de compromis techniques et décisions de conception

**SmartCampus · Projet Web Dynamique 2026 · ING2 Groupe 5**
Naoufal EL HABA · Othmane HASSIB · Nahel EL HADDAOUI ALAMI

## Introduction

Ce document revient sur les choix qu'on a faits pendant le développement de SmartCampus :
ce qu'on a essayé, ce qu'on a écarté, où on a le plus bataillé, et ce qui reste perfectible.

On n'a pas cherché à rendre un projet « parfait ». L'idée, c'est plutôt de montrer comment on
a réfléchi : comprendre les contraintes, comparer plusieurs solutions, et assumer nos
arbitrages avec le temps qu'on avait.

## 1. Les grands choix d'architecture

| Décision | Ce qu'on avait envisagé | Ce qu'on a retenu | Le revers |
|---|---|---|---|
| Organisation générale | Une appli PHP avec des pages générées côté serveur | Une SPA React + une API REST PHP séparée | Deux serveurs à lancer, et des sessions plus délicates à gérer |
| Backend | Un framework (Laravel / Symfony) | Du PHP « pur » avec PDO | Plus de code à écrire nous-mêmes (routage, validation, sécurité) |
| Structure de l'API | Un routeur central | Un fichier par ressource, l'action dépend de la méthode HTTP | Un peu de répétition d'une ressource à l'autre |
| Modèle de données | Trois tables (étudiants, enseignants, admins) | Une seule table `Utilisateur`, le rôle est un `ENUM` | Quelques colonnes vides selon le rôle (la `promotion` ne sert qu'aux étudiants) |

Pourquoi cette architecture ? Le sujet demande une séparation nette entre le frontend et le
backend. Une appli React qui parle à une API REST PHP répond directement à cette contrainte,
et c'est aussi comme ça que les choses se font aujourd'hui. Le prix à payer, c'est qu'on se
retrouve avec deux serveurs et une gestion de session plus pénible (voir la section 3).

## 2. Ce qu'on a simplifié ou laissé de côté

**Le suivi des présences (et le QR code).** Envisagé, puis abandonné. C'est une fonctionnalité
optionnelle, et on a préféré bétonner l'obligatoire (inscriptions, notes, emploi du temps)
plutôt que d'éparpiller nos efforts. Notre base reste prête à l'accueillir : il suffirait
d'ajouter une table `Presence` reliée à `Seance`.

**La messagerie et les notifications.** Pareil : écartées faute de temps, gardées comme
évolution possible.

**Le calcul des moyennes.** Au départ on voulait gérer la compensation par UE, avec des règles
de validation par semestre. C'était trop lourd pour ce qu'on avait à montrer. On a tranché
pour une moyenne pondérée (par coefficient dans un cours, puis par crédits pour la moyenne
générale). C'est lisible, et on peut le vérifier à la main.

**Les confirmations de suppression.** On a utilisé les pop-ups natives du navigateur au lieu
de coder des fenêtres sur mesure partout, pour passer plus de temps sur la logique métier.

## 3. Les difficultés qu'on a vraiment rencontrées

### 3.1 Les sessions entre deux serveurs
C'est ce qui nous a coûté le plus de temps. Le frontend tourne sur le port 5173, l'API sur le
8000. Au début, le cookie de session PHP n'était jamais renvoyé : dès qu'on était connecté, on
se faisait jeter à la requête suivante. Le navigateur bloquait pour cause de CORS. La
solution : un proxy dans Vite qui renvoie tout ce qui commence par `/api` vers le serveur PHP.
Du coup, vu du navigateur, tout est sur la même adresse, et le cookie passe sans problème.

### 3.2 Le `GROUP BY` de MySQL 8
Nos premières requêtes de moyennes plantaient. MySQL 8 active par défaut un mode strict
(`ONLY_FULL_GROUP_BY`) qui refuse de mélanger des agrégats avec des colonnes non regroupées.
On aurait pu désactiver ce mode, mais c'est une mauvaise idée : ça revient à cacher le
problème. On a préféré réécrire ces requêtes avec des sous-requêtes. En prime, c'est plus
lisible.

### 3.3 Le tri et l'injection SQL
Petit piège : un nom de colonne ne peut pas être passé en paramètre préparé. Si on laisse
l'utilisateur choisir la colonne de tri et qu'on la colle telle quelle dans la requête, on
ouvre une faille d'injection. On a réglé ça avec une liste blanche : seules les colonnes
prévues sont acceptées.

### 3.4 Les suppressions en cascade
Il fallait éviter de laisser des données orphelines. On a donc réglé les clés étrangères en
conséquence. Supprimer un étudiant supprime ses inscriptions et ses notes (`ON DELETE
CASCADE`). Supprimer un enseignant, en revanche, ne supprime pas ses cours : le cours reste,
simplement sans prof (`ON DELETE SET NULL`).

### 3.5 Le verrouillage des notes
On a hésité : verrouiller au niveau du cours, ou de chaque note ? On a choisi un drapeau
`verrouille` par note, qu'on met à 1 d'un coup quand l'enseignant valide un cours. Comme ça on
peut geler un cours entier tout en raisonnant note par note.

## 4. Les compromis qu'on assume

**Mode développement plutôt que production.** On livre avec le serveur PHP intégré et le
serveur Vite. C'est plus simple à lancer et à faire évoluer pendant le projet, et on a même
fait un script `demarrer.bat`. En échange, il faut démarrer deux serveurs. Un vrai déploiement
(le build React servi par Apache) serait la suite logique.

**Une sécurité raisonnable, pas militaire.** On a mis en place les requêtes préparées, le
hachage des mots de passe, la régénération de l'identifiant de session, les cookies
`httpOnly` et le contrôle des rôles côté serveur. On n'a pas fait de protection CSRF avancée
ni de limitation de débit : ce n'était pas l'objectif du module, et on préfère être honnêtes
là-dessus.

**Pas de pagination.** Les listes sont renvoyées en entier. À notre échelle (quelques dizaines
de lignes), ça ne pose aucun souci. Sur de gros volumes, il faudrait la rajouter.

## 5. Ce qui reste perfectible

- En local, MySQL tourne en `root` sans mot de passe (le réglage par défaut de WAMP). À durcir
  en production.
- Pas de HTTPS en local : le mot de passe circule en clair sur le réseau (il est quand même
  haché en base). En production, HTTPS serait obligatoire.
- L'emploi du temps détecte les conflits quand on ajoute une séance, mais il ne propose pas
  encore de créneau libre à la place.
- Trois rôles fixes, sans rôle intermédiaire ni droits délégués.

## 6. Notre regard sur l'aide de l'IA

*(le détail est dans le journal d'assistance par IA)*

L'IA nous a clairement fait gagner du temps sur tout ce qui est répétitif : les endpoints
CRUD, les formulaires React, les squelettes de requêtes. Mais on a dû corriger plusieurs de
ses propositions :

- une première version gérait l'authentification entre les deux serveurs sans proxy, et les
  cookies de session ne passaient pas ; on a trouvé d'où ça venait et on est passés par le
  proxy Vite ;
- des requêtes générées utilisaient un `GROUP BY` incompatible avec MySQL 8, qu'on a réécrit
  en sous-requêtes ;
- du code généré oubliait parfois la validation côté serveur, qu'on a remise systématiquement.

Au final, l'IA accélère, mais c'est nous qui avons compris, testé et adapté chaque morceau à
notre projet. C'est ce travail de relecture et de décision qui fait notre vraie part du
travail.

## Conclusion

SmartCampus, c'est une suite de choix qu'on assume : une architecture découplée pour y voir
clair, un backend sans framework pour vraiment comprendre ce qu'on fait, et des règles métier
posées au plus près de la base pour que ce soit fiable. On connaît ses limites et on sait par
où on l'améliorerait. Ce qu'on retient surtout, c'est la démarche autant que le résultat.
