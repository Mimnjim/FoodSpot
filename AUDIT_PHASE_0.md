# FoodSpot — rapport d'audit, phase 0

Date de l'audit : 12 novembre 2026

## 1. Synthèse

FoodSpot est un prototype fonctionnel de découverte de restaurants basé sur une
API Symfony et un frontend React/Vite. Le parcours individuel principal existe :
liste, recherche textuelle, filtre par catégorie, détail et favoris locaux.

Le projet n'implémente pas encore le cahier des charges cible. Le catalogue
provient exclusivement de huit entités SQLite créées par fixtures, sans
OpenStreetMap/Overpass, sans cache externe et sans fonctionnalités de groupe.
La phase 1 devra donc préparer une migration progressive vers une architecture
de fournisseurs externes, sans supprimer prématurément le fonctionnement
actuel.

## 2. État des lieux du frontend

### Organisation

- React 19, Vite 8, React Router 8 et JavaScript sont déclarés dans
  [`frontend/package.json`](frontend/package.json).
- Le point d'entrée est [`frontend/src/main.jsx`](frontend/src/main.jsx).
- [`frontend/src/App.jsx`](frontend/src/App.jsx) monte le routeur, conserve les
  favoris dans `localStorage` sous `foodspot-favorites` et déclare les routes
  `/`, `/restaurant/:id` et `/favorites`.
- Les composants sont correctement séparés entre structure (`Header`, `Footer`,
  `Hero`), recherche (`SearchBar`, `CategoryFilter`), liste
  (`RestaurantList`, `RestaurantCard`) et mise en avant
  (`FeaturedRestaurant`).
- Les styles sont organisés par composant sous
  [`frontend/src/styles/`](frontend/src/styles/), avec une direction visuelle
  déjà cohérente (fond chaud, accent orange, typographies DM Serif/Manrope).

### Fonctionnement constaté

- [`frontend/src/pages/Home.jsx`](frontend/src/pages/Home.jsx) charge
  `/api/restaurants`, puis relance une requête filtrée après chaque recherche ou
  changement de catégorie.
- La saisie est temporisée de 400 ms et les requêtes de filtrage précédentes
  sont annulées avec `AbortController`.
- [`frontend/src/pages/RestaurantDetail.jsx`](frontend/src/pages/RestaurantDetail.jsx)
  charge le détail depuis Symfony.
- [`frontend/src/pages/Favorites.jsx`](frontend/src/pages/Favorites.jsx) filtre
  localement le catalogue chargé depuis l'API.
- [`frontend/src/data/restaurants.js`](frontend/src/data/restaurants.js) est un
  ancien catalogue statique qui n'est référencé par aucun composant actuel.
- [`frontend/src/pages/Restaurants.jsx`](frontend/src/pages/Restaurants.jsx)
  est vide et n'est pas routée.

### Points à conserver

- Le découpage des composants et les états de chargement/erreur.
- Le debounce et l'annulation des requêtes.
- Le stockage local des favoris pour le MVP individuel.
- Le proxy Vite `/api` vers `http://127.0.0.1:8000` dans
  [`frontend/vite.config.js`](frontend/vite.config.js).
- La direction artistique et les styles existants, à adapter plutôt qu'à
  réécrire.

### Limites frontend

- Le premier chargement effectue deux appels API : un appel général et un appel
  filtré initial, alors qu'un seul devrait suffire.
- L'état `filteredRestaurants` commence vide et dépend du second appel.
- Le modèle UI exige `image`, `rating`, `priceRange` et `description`, alors que
  ces informations sont souvent absentes d'OpenStreetMap.
- Les images référencées par les fixtures (`/images/*.jpg`) ne sont pas
  présentes dans `frontend/public/`.
- Plusieurs libellés restent en anglais, les liens `About` et `+ Add a
  restaurant` ne déclenchent aucune fonctionnalité, et `FeaturedRestaurant`
  affiche un bouton sans navigation.
- Aucun écran, composant ou route ne couvre les sessions de groupe, les
  invitations, les préférences, le vote ou les recommandations collectives.

## 3. État des lieux du backend

### Organisation

- Symfony 7.4.22, PHP 8.3.6, Doctrine ORM 3.7 et SQLite sont configurés dans
  [`backend/composer.json`](backend/composer.json).
- Les contrôleurs à attributs sont importés par
  [`backend/config/routes.yaml`](backend/config/routes.yaml).
- Le service générique de [`backend/config/services.yaml`](backend/config/services.yaml)
  autowire et autoconfigure automatiquement les classes de `src/`.
- [`backend/src/Controller/HelloController.php`](backend/src/Controller/HelloController.php)
  expose l'endpoint de démonstration `/api/hello`.
- [`backend/src/Controller/RestaurantController.php`](backend/src/Controller/RestaurantController.php)
  expose `/api/restaurants` et `/api/restaurants/{id}`.
- [`backend/src/Repository/RestaurantRepository.php`](backend/src/Repository/RestaurantRepository.php)
  réalise le filtre Doctrine par catégorie et recherche sur nom, catégorie ou
  ville.
- [`backend/src/Entity/Restaurant.php`](backend/src/Entity/Restaurant.php)
  représente le catalogue persistant.
- [`backend/src/DataFixtures/RestaurantFixtures.php`](backend/src/DataFixtures/RestaurantFixtures.php)
  insère huit restaurants fictifs.

### Base et migrations

- [`backend/migrations/Version20261105151720.php`](backend/migrations/Version20261105151720.php)
  crée uniquement la table `restaurant`.
- La migration est exécutée et à jour dans l'environnement `dev`.
- La base `backend/var/data_dev.db` contient huit restaurants.
- Les tables nécessaires aux sessions de groupe, participants, préférences,
  votes, résultats et favoris persistants n'existent pas.
- Le modèle actuel rend obligatoires `rating`, `price_range`, `description` et
  `image`, ce qui est incompatible avec des données OSM partielles.

### Vérifications effectuées

- `php bin/console debug:router` confirme les routes :
  `/api/hello`, `/api/restaurants` et `/api/restaurants/{id}`.
- `GET /api/restaurants` répond `200` et renvoie huit éléments.
- `GET /api/restaurants?search=Paris` répond `200` et renvoie quatre éléments.
- `GET /api/restaurants?category=Japonais` répond `200` et renvoie un élément.
- `php bin/console doctrine:migrations:status` confirme une migration exécutée
  et aucune migration en attente.

## 4. Fonctionnalités et état

| Fonctionnalité | État actuel | Évaluation |
|---|---|---|
| Afficher un catalogue | Fonctionnelle | Données locales de démonstration uniquement |
| Recherche nom/catégorie/ville | Fonctionnelle | Requête Doctrine, sans pagination ni source externe |
| Filtre catégorie | Fonctionnelle | Dépend des catégories du catalogue local |
| Fiche restaurant | Fonctionnelle | Schéma et identifiant locaux |
| Favoris | Partielle mais utilisable | `localStorage`, pas de persistance backend |
| Images, notes, prix, descriptions | Démonstration | Valeurs inventées par fixtures et images absentes |
| OpenStreetMap / Overpass | Absente | Aucun client, provider, normalizer ou attribution |
| Cache et limitation externe | Absente | Aucun cache applicatif dédié ni gestion 429/504/timeout |
| Sessions de groupe | Absente | Aucun modèle, endpoint ou écran |
| Participants/invitations | Absente | Aucun modèle ou endpoint |
| Préférences/recommandations/votes | Absente | Aucun modèle, service ou écran |
| Tests automatisés | Absents | Aucun `backend/tests` trouvé et aucun script de test frontend |
| Build frontend | Validé | `npm.cmd run lint` et `npm.cmd run build` passent |

## 5. Éléments obsolètes ou à clarifier

- Retirer ou remplacer progressivement
  [`frontend/src/data/restaurants.js`](frontend/src/data/restaurants.js) après
  confirmation qu'aucun usage futur n'est souhaité.
- Supprimer ou transformer `HelloController` une fois l'API réellement
  structurée ; ne pas le faire pendant la phase d'audit.
- Décider si la route/page `Restaurants.jsx` doit devenir la page de résultats
  ou rester supprimée.
- Les fixtures doivent rester disponibles pour les tests et le développement,
  mais ne doivent plus être la source de production après intégration du
  provider OSM.

## 6. Adaptation recommandée pour OpenStreetMap/Overpass

1. Introduire un contrat `RestaurantProviderInterface` et un provider
   `OverpassRestaurantProvider` isolant les appels HTTP, les timeouts, les
   réponses invalides et les erreurs 429/504.
2. Ajouter un normalizer vers un DTO ou tableau FoodSpot stable contenant au
   minimum `provider`, `providerId`, `name`, `address`, `city`, `location`,
   `category`, `website`, `phone` et `openingHours`, avec des valeurs nulles
   explicites pour les données absentes.
3. Ajouter un `RestaurantSearchService` côté Symfony pour la validation des
   critères, la limitation des résultats, le cache et l'orchestration du
   provider.
4. Faire évoluer `RestaurantController` pour consommer ce service, sans faire
   appeler Overpass directement par React.
5. Adapter le frontend aux champs optionnels et supprimer l'affichage de
   notes, prix ou images inventés. Ajouter l'attribution
   « © OpenStreetMap contributors ».
6. Conserver SQLite pour les données propres à FoodSpot (sessions, participants,
   préférences, votes, résultats et éventuellement favoris), pas comme
   catalogue OSM permanent.
7. Ajouter les migrations et services de groupe seulement après stabilisation
   du contrat de recherche.

## 7. Plan précis pour la phase 1

### Étape 1 — Stabiliser les contrats

- Définir le schéma de réponse FoodSpot et ses champs optionnels.
- Décider le format d'identifiant (`provider` + `providerId`) afin d'éviter de
  dépendre de l'auto-incrément SQLite.
- Définir les limites de recherche et le comportement lorsqu'Overpass est
  indisponible.

### Étape 2 — Préparer le backend

- Créer les namespaces `Provider`, `Service` et éventuellement `Dto`.
- Extraire la sérialisation des restaurants du contrôleur.
- Configurer un client HTTP Symfony, un timeout explicite et le cache.
- Garder temporairement un provider local de secours pour préserver le
  développement et les tests.

### Étape 3 — Préparer le frontend

- Centraliser les appels API dans un module dédié.
- Éliminer le double chargement initial.
- Rendre les cartes et le détail compatibles avec les données manquantes.
- Corriger les liens morts et décider du rôle de `Restaurants.jsx`.

### Étape 4 — Qualité

- Ajouter des tests backend du normalizer, du provider (réponses valides et
  erreurs) et des endpoints.
- Ajouter au minimum des tests de comportement pour recherche, détail et
  favoris.
- Documenter les commandes de démarrage, la configuration du proxy et
  l'attribution OSM.

### Étape 5 — Préparer le groupe

- Concevoir les entités et migrations `GroupSession`, `Participant`,
  `Preference`, `Vote` et `SessionResult`.
- Définir les endpoints et le calcul de recommandation avant de créer les
  écrans React.

## Conclusion

Le socle actuel est suffisant pour commencer une adaptation progressive, mais
il ne faut pas étendre directement l'entité `Restaurant` actuelle pour y
stocker les données Overpass : cela conserverait des champs obligatoires et un
couplage au catalogue fictif. La priorité de la phase 1 est donc de séparer la
source de restaurants du domaine FoodSpot, puis de préserver le parcours
individuel existant pendant l'introduction d'OpenStreetMap.
