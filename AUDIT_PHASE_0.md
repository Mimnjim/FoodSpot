# FoodSpot — rapport d'audit actualise, phase 0

Date de l'audit : 21 novembre 2026

## 1. Synthese

Le depot est deja nettement plus avance que le prototype décrit dans le
premier audit. L'application comprend maintenant une recherche via
OpenStreetMap/Overpass, un parcours individuel avec favoris locaux, des
sessions de groupe, des participants, des preferences, des recommandations,
des votes et une decision finale.

Le repository est propre avant toute modification. Les migrations sont toutes
executees et a jour. Le frontend produit un build valide. Les validations
Symfony des routes, du conteneur, du mapping Doctrine et du schema passent.

## 2. Etat du frontend

### Organisation

- React 19, Vite 8, React Router 8 et JavaScript sont utilises dans
  `frontend/package.json`.
- `frontend/src/main.jsx` initialise l'application.
- `frontend/src/App.jsx` declare les routes `/`, `/restaurant/:id`,
  `/favorites`, `/group`, `/group/:code` et `/about`.
- Les composants sont separes entre navigation, recherche, cartes, details,
  favoris et sessions de groupe.
- Les styles restent organises sous `frontend/src/styles/`.

### Parcours disponibles

- La page d'accueil charge les restaurants locaux ou Overpass via le backend.
- La recherche utilise un debounce de 400 ms pour la saisie textuelle.
- Le filtrage precedent est annule avec `AbortController`.
- La fiche restaurant charge les donnees normalisees depuis Symfony.
- Les favoris sont conserves dans `localStorage` sous
  `foodspot-favorites`.
- La page de groupe permet la creation, la jointure, les preferences, les
  recommandations, les votes et la decision.
- Le pied de page affiche l'attribution OpenStreetMap obligatoire.

### Elements a conserver

- Le routage React Router et la separation des pages/composants.
- Le debounce et l'annulation des requetes.
- Le stockage local des favoris tant qu'aucun compte utilisateur n'est requis.
- La proxyfication Vite `/api` vers Symfony.
- La direction artistique chaude et orange deja presente.
- Les etats de chargement, d'erreur et de liste vide.

### Limites du frontend

- Il n'y a pas de tests automatises frontend ni de script de test dans
  `frontend/package.json`.
- La gestion du chargement initial peut encore etre centralisee davantage.
- Les informations OSM absentes doivent continuer a rester optionnelles dans
  tous les affichages.
- Le parcours de groupe doit etre teste reellement avec plusieurs navigateurs
  ou participants.

## 3. Etat du backend

### Architecture actuelle

- Symfony 7.4, PHP, Doctrine ORM 3.7 et SQLite sont utilises.
- `RestaurantController` expose la recherche et le detail via
  `/api/restaurants`.
- `GroupSessionController` expose les routes de session de groupe.
- `RestaurantSearchService` orchestre le provider Overpass et le catalogue local
  de secours.
- `OverpassRestaurantProvider` interroge plusieurs instances publiques
  Overpass avec timeout et gestion des reponses invalides.
- `RestaurantNormalizer` transforme les elements OSM en schema FoodSpot.
- `RestaurantProviderInterface` definit le contrat externe.
- `GroupSessionService` gere le cycle de vie, les preferences, les scores, les
  votes, le transfert du role de createur et la decision.

### Recherche et donnees

- Une recherche avec ville utilise Overpass.
- Une recherche sans ville utilise les fixtures SQLite locales.
- Les recherches Overpass sont mises en cache cinq minutes.
- Trois instances Overpass sont essayees successivement.
- Si elles sont indisponibles, les donnees locales correspondant a la ville,
  lorsque presentes, servent de secours.
- Le normalizer renvoie explicitement des valeurs nulles pour les champs OSM
  absents.
- Les identifiants distinguent `osm-*` et `local-*`.
- Les champs inventes comme note, prix ou image ne sont pas fabriques pour les
  resultats OSM.

### Entites et migrations

- `Restaurant` conserve le catalogue local de demonstration.
- `GroupSession` conserve les participants, preferences, votes et resultat
  sous forme de donnees JSON SQLite.
- Six migrations sont disponibles et executees.
- Le schema Doctrine est synchronise avec les mappings.
- La base locale reste dans `backend/var/`, qui est ignoree par Git.

## 4. Fonctionnalites existantes

| Fonctionnalite | Etat | Evaluation |
|---|---|---|
| Catalogue individuel | Implementee | Local sans ville, Overpass avec ville |
| Recherche nom/categorie/ville | Implementee | Backend Symfony |
| Fiche restaurant | Implementee | Champs normalises |
| Favoris | Implementee | Persistance locale navigateur |
| Source Overpass | Implementee | Multi-instance, cache cinq minutes, fallback local |
| Attribution OSM | Implementee | Affichee dans le footer |
| Sessions de groupe | Implementee | Code de partage et expiration 24 heures |
| Participants | Implementee | Ajout, retrait et transfert du createur |
| Preferences | Implementee | Validation backend |
| Recommandations | Implementee | Scores derives des preferences |
| Votes | Implementee | Un vote par participant |
| Decision | Implementee | Vote majoritaire ou roulette |
| Tests automatises | Incomplet | Aucun test backend ou frontend trouve |
| Page historique | Absente | Hors MVP |

## 5. Elements a conserver

- La separation entre `RestaurantSearchService`, provider Overpass, normalizer
  et catalogue local.
- Le schema normalise avec identifiant stable `provider + providerId`.
- Les fixtures locales comme donnees de development et fallback.
- Les valeurs optionnelles pour les informations manquantes.
- Les favoris dans `localStorage`.
- Les migrations et l'architecture Doctrine existantes.
- L'expiration des sessions apres 24 heures.
- Le footer indiquant `© OpenStreetMap contributors`.

## 6. Elements a adapter

- Remplacer les donnees locales comme source principale dans les environnements
  ou voyages necessitant une vraie ville.
- Ajouter des tests pour la normalisation, le fallback, les endpoints, les
  sessions et les decisions.
- Casser les dependances internes inutiles et renforcer les validations API
  avec des tests et des limites claires.
- Examiner les risques liee a des donnees JSON dans `GroupSession` :
  fonctionnalite utile pour un MVP, mais schemas normalises a envisager si les
  volumes ou les exigences evoluent.
- Ajouter une gestion HTTP Symfony propre pour les appels externes si le
  provider reste base sur `file_get_contents`.
- Completer l'isolation des secrets et des variables d'environnement pour les
  environnements de deploiement.

## 7. Problemes techniques identifies

1. Aucun test automatique n'est present.
2. `OverpassRestaurantProvider` utilise des flux HTTP natifs plutot qu'un client
   HTTP Symfony, ce qui rend les erreurs HTTP et les retries moins
   structurees.
3. Les resultats Overpass peuvent avoir une ville absente dans `addr:city`,
   meme lorsque la ville de recherche est connue.
4. Le provider ne peut pas garantir des informations telles que description,
   image, note ou prix.
5. Le catalogue local contient encore des champs demonstration, notamment des
   valeurs synthetiques de rating, prix et image.
6. Les sessions stockent plusieurs domaines metier dans des champs JSON au lieu
   d'entites relationnelles; cela doit etre accepte pour le MVP ou migre avec
   des tests avant evolution.

## 8. Plan precis pour la prochaine phase

### Etape 1 — Qualifier le socle actuel

- Ajouter les tests du normalizer et du provider.
- Ajouter les tests des endpoints restaurants et groupes.
- Tester les chemins success, fallback, 503, ville vide et identifiants invalides.

### Etape 2 — Fiabiliser les recherches externes

- Migrer les appels Overpass vers un client HTTP Symfony si cela simplifie
  timeout, status HTTP, retries et logs.
- Ajouter une politique explicite de concurrence et de limitation.
- Ajouter des logs distincts pour 429, 504, timeout et reponse invalide.
- Conservers les caches courts et le fallback local.

### Etape 3 — Stabiliser le contrat API

- Documenter le format complet des resultats.
- Rendre tous les champs OSM optionnels uniformes.
- Conserver `osm-*` et `local-*` sans exposer directement les ids SQLite.
- Eliminer les champs demo de l'affichage principal.

### Etape 4 — Verifier le parcours frontend

- Centraliser les appels API dans un module frontend.
- Ajouter les tests des recherches, details, favoris et groupe.
- Re-verifier les parcours principaux sur mobile et navigateur.

### Etape 5 — Preparer la stabilisation finale

- Ajouter les scripts documentés de test backend et frontend.
- Vérifier les variables d'environnement de developpement.
- Relancer le build, les validations Doctrine et les routes.
- Proposer un commit uniquement apres validation complete.

## 9. Verification effectuee

- `git status` : repository propre.
- `npm.cmd run lint` : passe.
- `npm.cmd run build` : passe.
- `php bin/console debug:router` : 11 routes applicatives, dont les 8 routes API.
- `php bin/console doctrine:migrations:status --no-interaction` : 6 migrations
  executees, aucune migration en attente.
- `php bin/console doctrine:schema:validate --no-interaction` : schema synchronise.
- `php bin/console lint:container --no-interaction` : conteneur valide.

## Conclusion

Phase 0 terminee : l'état réel du projet est documenté. Le socle frontend,
backend, OpenStreetMap, Overpass et groupe existe deja. La prochaine étape
doit commencer par la qualification automatisée du code present, puis la
fiabilisation des recherches externes, avant toute nouvelle fonctionnalite.
