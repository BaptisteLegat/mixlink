# mixlink

mixlink est une plateforme innovante permettant de créer, partager et collaborer sur des playlists musicales. Ce projet est construit avec une architecture moderne comprenant un frontend en Vue.js et un backend en Symfony.

---

## Fonctionnalités principales

- **Authentification OAuth** : Connexions sécurisées via Spotify et Google.
- **Système de souscription** : Abonnez-vous à des plans freemium via Stripe pour accéder à des fonctionnalités avancées.
- **Création de sessions** : Créez des sessions collaboratives où les participants peuvent ajouter des morceaux à une playlist.
- **Ajout de morceaux** : Ajoutez des morceaux à des playlists personnalisées en quelques clics.
- **Export de playlists** : Exportez la playlist finale sur le service de streaming de l'utilisateur connecté (hôte).
- **Formulaire de contact** : Page de contact intégrée pour envoyer des messages.
- **Interface utilisateur moderne** : Expérience fluide, responsive et intuitive grâce à Vue.js et Element Plus.
- **Support multilingue** : Disponible en français et en anglais.

---

## Structure du projet

- **Frontend** : Vue 3, Vite, Pinia, Vue Router, Element Plus.
- **Backend** : Symfony avec MySQL comme base de données.
- **Tests** : Tests unitaires avec Vitest (frontend) et PHPUnit (backend).
- **Conteneurisation** : Docker Compose pour une configuration simplifiée.
- **Outils supplémentaires** : PhpMyAdmin pour la gestion de la base de données, Nginx comme serveur web.

---

## Prérequis

- [Git](https://git-scm.com/)
- [Docker](https://www.docker.com/) et [Docker Compose](https://docs.docker.com/compose/) (V2)

---

## Installation

### Étapes d'installation

1. **Clonez le dépôt :**
   ```sh
   git clone https://github.com/BaptisteLegat/mixlink.git
   cd mixlink
   ```

2. **Configurez les fichiers `.env` :**
   - Frontend : `/front/.env`
   - Backend : `/back/.env`

3. **Lancez les conteneurs Docker :**
   ```sh
   docker-compose up -d --build
   ```

4. **Installez les dépendances PHP :**
   ```sh
   docker-compose exec php composer install
   ```

5. **Créez la base de données et appliquez les migrations :**
   ```sh
   docker-compose exec php bin/console doctrine:database:create
   docker-compose exec php bin/console doctrine:migrations:migrate
   ```

6. **Chargez les fixtures pour initialiser les données :**
   ```sh
   docker-compose exec php bin/console hautelook:fixture:load
   ```

7. **Répétez les étapes 5 et 6 pour l'environnement de test :**
   ```sh
   docker-compose exec php bin/console doctrine:database:create --env=test
   docker-compose exec php bin/console doctrine:migrations:migrate --env=test
   docker-compose exec php bin/console hautelook:fixture:load --env=test
   ```

8. **Installez les dépendances Node.js :**
   ```sh
   docker-compose exec frontend npm install
   ```

9. **Accédez à l'application :**
   - Frontend : [http://localhost:3000](http://localhost:3000)
   - Backend API : [http://localhost/api](http://localhost/api)
   - PhpMyAdmin : [http://localhost:8080](http://localhost:8080) (identifiant : `root`, mot de passe : `password`)

---

## Commandes utiles

### Frontend

- **Construire pour la production :**
  ```sh
  docker-compose exec frontend npm run build
  ```
- **Lancer les tests unitaires :**
  ```sh
  docker-compose exec frontend npm run test:unit
  ```
- **Lancer le lint :**
  ```sh
  docker-compose exec frontend npm run lint
  ```
- **Formater le code :**
  ```sh
  docker-compose exec frontend npm run format
  ```

### Backend

- **Lancer GrumPHP :**
  ```sh
  docker-compose exec php vendor/bin/grumphp run
  ```
  Si GrumPHP n'est pas configuré, initialisez-le avec :
  ```sh
  docker-compose exec php vendor/bin/grumphp git:init
  ```

- **Lancer les tests unitaires :**
  ```sh
  docker-compose exec php php bin/phpunit tests/unit
  ```
- **Lancer les tests fonctionnels :**
  ```sh
  docker-compose exec php php bin/phpunit tests/functional
  ```

- **Réinitialiser la base de données :**
  ```sh
  ./scripts/resetDb.sh
  ```
  Par défaut, l'environnement est `dev`. Pour spécifier un autre environnement :
  ```sh
  ./scripts/resetDb.sh --env=test
  ```

---

## Technologies utilisées

### Frontend

- Vue 3
- Vite
- Pinia
- Vue Router
- Element Plus
- SCSS

### Backend

- Symfony
- MySQL
- PHPUnit

### Outils

- Docker
- PhpMyAdmin
- Vitest
- Cypress
- GrumPHP

### APIs

- Spotify API
- Google API (YouTube)
- Stripe API (paiements et souscriptions)

---

## Documentation API Backend

> **TODO** : Ajouter la documentation de l'API backend.

---

## Configuration des hooks Git

Dans VS Code, le dossier `.git` est masqué par défaut. Supprimez-le de la liste des dossiers cachés dans les paramètres de l'IDE.

Ajoutez les lignes suivantes dans les fichiers `commit-msg` et `pre-commit` du dossier `.git/hooks` :

```bash
export GRUMPHP_GIT_WORKING_DIR="$(git rev-parse --show-toplevel)"

(cd "./" && docker compose exec -T php vendor/bin/grumphp run)
```

---

## Auteurs

- **Baptiste Legat** - Développeur
