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

Le proxy Vite redirige `/api` vers `http://127.0.0.1:8000`. Une recherche sans
ville utilise les fixtures SQLite locales ; une recherche avec une ville utilise
Overpass. Les instances publiques Overpass peuvent être temporairement indisponibles ou
limiter les requêtes. FoodSpot met en cache les recherches pendant cinq minutes,
essaie plusieurs instances de secours et utilise les restaurants locaux filtrés
par ville si aucune instance distante ne répond.

## Fonctionnalités

- Recherche par ville, nom et catégorie.
- Fiches restaurants et favoris locaux.
- Attribution `© OpenStreetMap contributors`.
- Sessions de groupe avec code de partage.
- Participants, préférences, recommandations, votes et décision automatique ou
  roulette. Le créateur est le seul à pouvoir finaliser la décision ; son rôle
  est transféré au premier participant restant s'il quitte la session.
- Expiration des sessions après 24 heures.

## Validation

```powershell
cd frontend
npm run lint
npm run build

cd ..\backend
php bin/console doctrine:schema:validate --no-interaction
php bin/console lint:container --no-interaction
php bin/console doctrine:migrations:up-to-date --no-interaction
```

Les données de développement (`backend/var/`), les dépendances et les fichiers
`.env.*.local` sont ignorés par Git. Ne pas ajouter de secret dans les fichiers
versionnés.
