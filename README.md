# FoodSpot

FoodSpot aide à trouver un restaurant seul ou à plusieurs. Le frontend React
communique exclusivement avec l'API Symfony ; le backend interroge OpenStreetMap
via Overpass pour les recherches par ville.

## Prérequis

- PHP 8.3 ou supérieur
- Composer
- Node.js et npm

## Démarrage local

Dans un terminal :

```powershell
cd backend
php bin/console doctrine:migrations:migrate --no-interaction
php -S 127.0.0.1:8000 -t public
```

Dans un second terminal :

```powershell
cd frontend
npm install
npm run dev
```

Le proxy Vite redirige `/api` vers `http://127.0.0.1:8000`. La ville choisie à
la première visite est mémorisée dans le navigateur. Les listes par ville utilisent
Nominatim, une source OpenStreetMap adaptée aux affichages rapides. Les filtres
précis utilisent Overpass en complément. Les instances publiques peuvent être
temporaires ou limiter les requêtes : FoodSpot cache les recherches une heure,
utilise des timeouts courts et conserve les restaurants locaux uniquement en
dernier recours. Les listes affichent jusqu'à 50 établissements réels, diversifiés
par type de cuisine.

## Architecture API frontend

Tous les appels HTTP vers Symfony passent par
`frontend/src/api/foodSpotApi.js`. Ce module centralise la sérialisation des
critères de recherche, les en-têtes JSON, la lecture des réponses et les erreurs
HTTP. Les pages React ne communiquent donc jamais directement avec OpenStreetMap
ou Overpass.

## Fonctionnalités

- Recherche par ville, nom et catégorie.
- Fiches restaurants et favoris locaux ou OpenStreetMap.
- Résultats réels mis en avant avec adresse, source et valeurs manquantes
  clairement identifiées.
- Images importées automatiquement uniquement lorsqu'une URL OSM ou Wikimedia
  Commons est réellement disponible; aucune image fictive n'est créée.
- Première visite avec choix obligatoire de la ville, mémorisé dans le navigateur.
- Attribution `© OpenStreetMap contributors`.
- Sessions de groupe avec code de partage.
- Participants, préférences, recommandations, votes et décision automatique ou
  roulette. Le créateur est le seul à pouvoir finaliser la décision ; son rôle
  est transféré au premier participant restant s'il quitte la session.
- Expiration des sessions après 24 heures.
- Progression visible des participants et résultat final ouvrant la fiche choisie.

## Validation

```powershell
cd frontend
npm run lint
npm run build
npm run test

cd ..\backend
php bin/phpunit
php bin/console doctrine:schema:validate --no-interaction
php bin/console lint:container --no-interaction
php bin/console doctrine:migrations:up-to-date --no-interaction
```

Les données de développement (`backend/var/`), les dépendances et les fichiers
`.env.*.local` sont ignorés par Git. Ne pas ajouter de secret dans les fichiers
versionnés.
