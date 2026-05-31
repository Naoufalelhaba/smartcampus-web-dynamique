# Journal d'assistance par IA

**SmartCampus · Projet Web Dynamique 2026 · ING2 Groupe 5**
Naoufal EL HABA · Othmane HASSIB · Nahel EL HADDAOUI ALAMI

## Pourquoi ce journal

Le sujet autorise les outils d'IA générative, à condition de dire comment on s'en est servi et
avec quel recul. C'est l'objet de ce document : sur quelles tâches on a utilisé l'IA, ce qui a
marché, ce qui était faux, et ce qu'on a dû reprendre derrière. Notre ligne de conduite tient
en une phrase : l'IA propose, on décide, on vérifie et on adapte.

## 1. Les outils

| Outil | Ce qu'on en a fait |
|---|---|
| Un assistant conversationnel (type Claude / ChatGPT) | Conception, squelettes de code, explications, débogage |
| La complétion dans l'éditeur (type GitHub Copilot) | Suggestions pendant qu'on tape |

On les a utilisés en appui, pas en pilote automatique. Tout ce qui sortait a été relu, testé
(essais à la main avec `curl`, compilation du frontend) et adapté avant d'entrer dans le
projet.

## 2. Tâche par tâche

| Tâche | Ce que l'IA a donné | Notre verdict | Ce qu'on a changé |
|---|---|---|---|
| Schéma de la base | Des tables et des contraintes de départ | Bonne base | Ajout de `semestre`, `credits`, `verrouille`, `coefficient` ; on a choisi nous-mêmes les `ON DELETE CASCADE` / `SET NULL` |
| Endpoints CRUD PHP | Des squelettes par ressource | Utile | On a ajouté partout la validation côté serveur et le contrôle des rôles |
| Requêtes de moyennes | Des requêtes avec `GROUP BY` | Faux sur MySQL 8 | Réécrites en sous-requêtes |
| Authentification / sessions | Des appels API entre deux serveurs sans proxy | Faux : cookies perdus | On a mis en place le proxy Vite |
| Tri des cours | Le nom de colonne inséré dans la requête | Faille d'injection | Validation par liste blanche |
| Composants React | Formulaires, modales, tableaux | Utile | Gestion de l'état et des erreurs, mise au format de notre charte |
| Conflits d'emploi du temps | Une logique de chevauchement | Incomplète | On a ajouté les cas salle / promotion / enseignant et des messages clairs |
| Mise en page (CSS) | Une base de styles | Utile | Adaptée à nos couleurs, rendue responsive |

## 3. Les fois où l'IA s'est plantée

### Cas 1 : les sessions qui sautent
Symptôme : une fois connecté, la requête suivante nous renvoyait « non authentifié ». En
creusant, on a vu que la suggestion de départ faisait dialoguer directement le front (5173) et
l'API (8000), et que le cookie de session ne suivait pas. On a réglé ça avec le proxy Vite,
qui ramène tout sur la même adresse.

### Cas 2 : le `GROUP BY` refusé
Symptôme : erreur SQL au moment de calculer les moyennes. Le code généré n'était pas
compatible avec le mode strict de MySQL 8. L'IA nous a même suggéré de désactiver ce mode. On
a dit non, parce que ça revient à cacher le problème. On a réécrit en sous-requêtes.

### Cas 3 : la faille sur le tri
Là, pas de bug visible, mais un trou de sécurité. Le nom de la colonne de tri venait de
l'utilisateur et finissait directement dans la requête. On a mis une liste blanche de colonnes
autorisées.

### Cas 4 : la validation oubliée
Plusieurs bouts de code généré se contentaient de vérifier les données côté navigateur. On a
remis la validation côté serveur partout, parce qu'on ne fait jamais confiance au client.

## 4. Comment on s'en est servi

- Relire et tester ce qui sort (`curl` sur l'API, build du front).
- Refuser les raccourcis douteux, comme désactiver un contrôle de MySQL ou écrire des requêtes
  non préparées.
- Adapter au contexte réel : nos ports, WAMP, nos noms de variables en français, notre
  arborescence.
- Garder une trace, avec ce journal et le rapport de compromis.

## 5. Les limites qu'on a remarquées

- L'IA propose parfois quelque chose qui marche mais qui ouvre une faille ou qui est une
  mauvaise pratique.
- Elle ne connaît pas notre config exacte (les ports, la version de MySQL, WAMP), donc il y a
  toujours des ajustements à faire.
- Elle écrit parfois plus de code que nécessaire.
- Et surtout, elle ne remplace pas les tests : c'est en testant qu'on a trouvé les vrais
  problèmes, les sessions et le `GROUP BY` en tête.

## Conclusion

L'IA nous a fait gagner du temps, surtout sur les parties répétitives. Mais ce qu'on apporte,
nous, c'est la conception, la relecture, le débogage, la sécurité et la compréhension de ce
qu'on rend. C'est nous qui avons repéré les erreurs, refusé les mauvaises pistes et recollé
chaque proposition à notre projet. C'est exactement ce qu'on attend d'un ingénieur quand ces
outils sont partout.
