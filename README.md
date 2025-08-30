# mixlink

mixlink est une plateforme innovante permettant de créer, partager et collaborer sur des playlists musicales. Ce projet est construit avec une architecture moderne comprenant un frontend en Vue.js et un backend en Symfony.

---

## Fonctionnalités principales

- **Authentification OAuth** : Connexions sécurisées via Spotify, SoundCloud et Google.
- **Système de souscription** : Abonnez-vous à des plans freemium via Stripe pour accéder à des fonctionnalités avancées.
- **Gestion des participants** : Ajoutez ou retirez des participants de la session.
- **Création de sessions** : Créez des sessions collaboratives où les participants et l'hôte peuvent ajouter des morceaux à une playlist.
- **Gestion des morceaux** : Ajoutez, retirez ou triez les morceaux de la playlist.
- **Export de playlists** : Exportez la playlist finale sur le service de streaming de l'utilisateur connecté (hôte).
- **Historique des playlists** : Consultez l'historique de toutes vos playlists exportées depuis votre page profil.
- **Formulaire de contact** : Page de contact intégrée pour envoyer des messages.
- **Interface utilisateur moderne** : Expérience fluide, réactive et intuitive, avec support du mode sombre, grâce à Vue.js et Element Plus.
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

## Limitations OAuth et configuration des APIs

### Spotify API - Mode développement

L'application Spotify est actuellement en **mode développement**, ce qui implique certaines restrictions importantes :

- **Limitation des utilisateurs** : Seuls 25 utilisateurs maximum peuvent être autorisés à se connecter via Spotify.
- **Liste d'autorisation obligatoire** : Pour qu'un utilisateur puisse utiliser l'authentification Spotify, son email doit être explicitement ajouté dans le tableau de bord développeur Spotify.
- **Accès refusé sans autorisation** : Si un utilisateur non autorisé tente de se connecter, il peut parfois réussir l'authentification, mais toutes les requêtes API renverront une erreur 403 (accès refusé).

#### Configuration requise

Pour chaque environnement (développement local, préprod, production), vous devez :

1. Accéder au [tableau de bord développeur Spotify](https://developer.spotify.com/dashboard)
2. Sélectionner votre application
3. Aller dans "Settings" puis "User Management"
4. Ajouter l'email de chaque utilisateur autorisé avec son nom

> **Note** : Le passage en mode étendu nécessite d'être une organisation enregistrée avec au moins 250 000 utilisateurs actifs mensuels. Pour plus d'informations, consultez la [documentation officielle](https://developer.spotify.com/documentation/web-api/concepts/quota-modes).

### Autres APIs

- **SoundCloud** : Aucune limitation particulière pour l'authentification.
- **Google/YouTube** : Aucune limitation, l'application étant validée par Google.

---

## Installation

### Étapes d'installation

1. **Clonez le dépôt :**
   ```sh
   git clone https://github.com/BaptisteLegat/mixlink.git
   cd mixlink
   ```

2. **Configurez les fichiers `.env`, `.env.test` et `.env.local` :**
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

### Stripe CLI pour le développement local

Pour que le système de souscription Stripe fonctionne correctement en environnement de développement, il est nécessaire d’installer [Stripe CLI](https://stripe.com/docs/stripe-cli) sur votre machine.

Ensuite, lancez la commande suivante dans un terminal pour rediriger les webhooks Stripe vers votre API locale :

```sh
stripe listen --forward-to localhost/api/webhook/stripe
```

Cela permet à Stripe d’envoyer les événements de paiement à votre backend local pour les tests et le développement.

---

## Commandes utiles

### Frontend

- **Construire pour la production :**
  ```sh
  docker-compose exec frontend npm run build
  ```
- **Voir la prévisualisation :**
  ```sh
  docker-compose exec frontend npm run preview
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

### Couverture de tests (Coverage)

Pour générer un rapport de couverture de code lors de l’exécution des tests, il faut utiliser Xdebug. Les rapports sont générés au format HTML et consultables dans un navigateur.

- **Tests fonctionnels** :
  ```sh
  XDEBUG_MODE=coverage vendor/bin/phpunit tests/functional --coverage-html=./coverage/functional
  ```
- **Tests unitaires** :
  ```sh
  XDEBUG_MODE=coverage vendor/bin/phpunit tests/unit --coverage-html=./coverage/unit
  ```

Après l’exécution de ces commandes, ouvrez le fichier `index.html` généré à la racine du dossier `coverage/functional` ou `coverage/unit` dans votre navigateur pour visualiser le rapport de couverture.

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
- Doctrine ORM
- Nelmio ApiDoc Bundle
- Redis

### Outils

- Docker
- PhpMyAdmin
- Vitest
- Cypress
- GrumPHP

### APIs

- Spotify API
- SoundCloud API
- Google API (YouTube)
- Stripe API (paiements et souscriptions)

---

## Documentation API Backend

La documentation de l'API backend est générée automatiquement à partir des annotations dans le code. Pour la visualiser, accédez à :
[http://localhost/api/doc](http://localhost/api/doc)

Pour accéder à l'url, il faut renseigner un nom d'utilisateur et un mot de passe. Par défaut, utilisez :
- **Nom d'utilisateur** : `admin_doc`

Pour le mot de passe, il faut ajouter la variable d'environnement `API_DOC_PASSWORD` dans le fichier `.env.local` du backend.
Vous devez haser le mot de passe avec la commande suivante :
```bash
docker compose exec php bin/console security:hash-password
```
Copiez le hash généré et collez-le dans le fichier `.env.local` :
```properties
API_DOC_PASSWORD='votre_hash_généré'
```

---

## Configuration des hooks Git

Dans VS Code, le dossier `.git` est masqué par défaut. Supprimez-le de la liste des dossiers cachés dans les paramètres de l'IDE.

Ajoutez les lignes suivantes dans les fichiers `commit-msg` et `pre-commit` du dossier `.git/hooks` :

```bash
export GRUMPHP_GIT_WORKING_DIR="$(git rev-parse --show-toplevel)"

(cd "./" && docker compose exec -T php vendor/bin/grumphp run)
```

---

## Roadmap

Consultez notre feuille de route pour connaître les prochaines fonctionnalités et améliorations :

- **[Roadmap complète](ROADMAP.md)** - Vision détaillée sur 12 mois
- **[Roadmap exécutif](ROADMAP_EXECUTIF.md)** - Plan d'action pour les 6 prochains mois

---

## Auteurs

- **Baptiste Legat** - Développeur
