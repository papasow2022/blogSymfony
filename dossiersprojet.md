# Blog Symfony 6.4 – Guide rapide (README)

Ce document explique simplement l’application, ses dossiers/fichiers, et comment l’utiliser.

## 1) Qu’est-ce que c’est ?
Un blog complet (articles, commentaires, likes, profil) avec:
- Pages web classiques (HTML/Twig)
- API REST (JSON) pour site/mobile
- Sécurité (login, rôles), cache, optimisation d’images

## 2) Structure des dossiers (idée simple)
- `public/` – Porte d’entrée web
  - `index.php`: démarre l’app
  - `api-test.html`, `debug-api.html`, `api-simple-test.html`: tester l’API dans le navigateur
  - `api-client.js`: petit client JavaScript pour l’API
  - `uploads/`: images (articles, avatars)
- `src/` – Le “cerveau” (code PHP)
  - `Controller/`: répond aux URL
    - `PostController.php`: pages d’articles (lister, créer, éditer, supprimer, voir)
    - `ProfileController.php`, `SecurityController.php`, `AdminController.php`
    - `Controller/Api/`: API JSON (ex: `PostApiController.php`)
  - `Entity/`: modèles de données (User, Post, Comment…)
  - `Repository/`: recherches/base de données (PostRepository…)
  - `Form/`: formulaires pour les pages HTML
  - `Security/`: authentification et permissions (`AppAuthenticator`, `PostVoter`)
  - `Service/`: services métiers
    - `CacheService`: accélère avec du cache
    - `ImageOptimizerService`: compresse/redimensionne les images
  - `Command/`: commandes console (ex: créer un admin)
- `templates/` – Pages HTML (Twig)
  - `base.html.twig`: layout commun
  - `post/`, `admin/`, `stats/…`
- `config/` – Réglages
  - `packages/`: sécurité, base de données, cache, twig, etc.
  - `routes.yaml` et `routes/*.yaml`: plan des URL
- `migrations/` – Historique des changements de base de données
- `var/` – Cache et logs (peut être vidé sans risque)
- `vendor/` – Bibliothèques installées (ne pas modifier)
- `assets/` – JS/CSS si AssetMapper (webapp)
- `docs/` – Documentation du projet
  - `PROJECT_GUIDE.md`, `IMPLEMENTATION_OVERVIEW.md`
- `README_API.md` – Mode d’emploi de l’API

## 3) Grandes fonctionnalités
- Articles: création/édition/suppression/affichage, image, likes, vues
- Commentaires: fil de discussion, validation, signalement
- Profil utilisateur (avatar, bio)
- Sécurité: login, rôles, protection CSRF
- API REST: endpoints JSON pour web/mobile
- Optimisations: cache métier + compression d’images

## 4) Comment ça marche (parcours)
1. Le navigateur appelle une URL
2. Les routes envoient vers un `Controller`
3. Le `Controller` récupère les données (via `Repository`) et applique les règles (sécurité/services)
4. Il renvoie:
   - une page HTML (via `templates/`), ou
   - du JSON (API) pour une app web/mobile

## 5) Démarrer en local
1. Configurer MySQL dans `.env.local`, ex:
   `DATABASE_URL="mysql://root:@127.0.0.1:3306/blog2?serverVersion=8.0&charset=utf8mb4"`
2. Créer la base et le schéma:
   - `php bin/console doctrine:database:create`
   - `php bin/console doctrine:migrations:migrate -n`
3. Lancer le serveur:
   - `php -S 127.0.0.1:8000 -t public` (ou `symfony serve -d --port=8000`)
4. Tester:
   - API: `http://127.0.0.1:8000/api-test.html`
   - Stats: `http://127.0.0.1:8000/stats/`

## 6) Où changer quoi ?
- Présentation: `templates/` et `assets/styles`
- Nouveau endpoint API: `src/Controller/Api/…` (+ attribut `#[Route]`)
- Formulaires: `src/Form/…`
- Nouvelles colonnes: `src/Entity/…` + migration
- Règles d’accès: `src/Security/PostVoter.php`, `config/packages/security.yaml`
- Cache: `src/Service/CacheService.php`
- Images: `src/Service/ImageOptimizerService.php`

## 7) Références utiles
- `README_API.md`: détails et exemples d’appels API
- `docs/PROJECT_GUIDE.md`: guide non technique complet
- `docs/IMPLEMENTATION_OVERVIEW.md`: résumé des fichiers clés et leur rôle

Bon usage et bonne lecture !