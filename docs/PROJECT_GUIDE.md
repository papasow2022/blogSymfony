# Guide Complet du Projet Blog

Ce guide explique chaque partie de l'application de blog pour que toute personne, même non technique, puisse comprendre le rôle des dossiers et fichiers, et comment tout fonctionne ensemble.

## 1) Vue d’ensemble
- L’application est un blog avec des articles, commentaires, likes, profils.
- Il y a une API (réponses JSON) pour être utilisée par des pages web ou une app mobile.
- Le site web classique (HTML) coexiste avec l’API.

## 2) Dossiers principaux
- `public/`: Point d’entrée web (fichiers accessibles par le navigateur).
  - `index.php`: démarre l’application.
  - `api-test.html`, `debug-api.html`, `api-simple-test.html`: pages pour tester l’API.
  - `api-client.js`: petit client JavaScript pour appeler l’API.
  - `uploads/`: fichiers uploadés (images d’articles, avatars).
- `src/`: Code PHP de l’application.
  - `Controller/`: Contrôleurs (logique des pages et de l’API).
    - `PostController.php`: pages d’articles (lister, créer, éditer, supprimer, afficher).
    - `ProfileController.php`: page de profil utilisateur.
    - `SecurityController.php`: login/logout web.
    - `AdminController.php`: pages d’administration (utilisateurs, commentaires, signalements, stats).
    - `Controller/Api/`: API REST (JSON).
      - `PostApiController.php`: liste d’articles, populaires, etc.
      - `CommentApiController.php`: commentaires via l’API.
      - `UserApiController.php`: gestion côté API (ex: profil admin).
      - `AuthApiController.php`: endpoints d’auth basiques.
  - `Entity/`: Modèles de données (tables en base).
    - `User.php`: utilisateur (email, rôles, mot de passe hashé, avatar…)
    - `Post.php`: article (titre, contenu, image, auteur, vues, likes…)
    - `Comment.php`: commentaire (contenu, auteur, article, validation…)
    - `Category.php`: catégorie d’article.
    - `PostLike.php`: like d’article.
    - `CommentReport.php`: signalement de commentaire.
  - `Repository/`: Accès aux données (requêtes).
    - `PostRepository.php`: recherche, populaires, stats globales.
    - `CommentRepository.php`, `UserRepository.php`, etc.
  - `Form/`: Formulaires pour les pages HTML.
    - `PostType.php`, `CommentType.php`, `ProfileFormType.php`, etc.
  - `Security/`: Sécurité et permissions.
    - `AppAuthenticator.php`: login (avec protection CSRF).
    - `PostVoter.php`: règles d’accès aux articles (auteur ou admin).
  - `Service/`: Services métiers.
    - `CacheService.php`: accélère l’appli en mettant en cache des listes/statistiques.
    - `ImageOptimizerService.php`: réduit la taille des images uploadées (qualité conservée).
  - `Command/`:
    - `CreateAdminCommand.php`: commande console pour créer un admin.
- `templates/`: Modèles HTML (Twig).
  - `post/`: pages d’articles (`index`, `new`, `edit`, `show`).
  - `admin/`: écrans d’administration.
  - `stats/index.html.twig`: page statistiques (populaires, plus likés, chiffres clés).
  - `base.html.twig`: layout commun.
- `config/`: Configuration.
  - `routes.yaml` + `routes/*.yaml`: activation des routes (web et API).
  - `packages/*.yaml`: configuration de Symfony (sécurité, cache, doctrine…).
  - `packages/security.yaml`: hachage mots de passe, firewalls, accès aux pages.
  - `packages/cache.yaml`: configuration du cache (prêt pour Redis/APCu si besoin).
- `migrations/`: Historique des changements de base de données.
- `docs/`: Documentation du projet.
  - `IMPLEMENTATION_OVERVIEW.md`: résumé des fichiers importants et leur rôle.
  - `PROJECT_GUIDE.md`: ce guide.
  - `VERIFICATION_AMELIORATIONS.md`: check-list des améliorations réalisées.

## 3) Les grandes fonctionnalités
- Articles: création, édition, suppression, affichage, image, likes, vues.
- Commentaires: fil de discussion, validation, signalement.
- Profil utilisateur: avatar, bio.
- Sécurité: login, rôles (utilisateur, admin), protection CSRF.
- API REST: endpoints JSON pour utiliser le blog depuis web/mobile.
- Optimisations: cache métier (listes/statistiques), optimisation d’images.

## 4) Sécurité (non technique)
- Les mots de passe ne sont jamais stockés en clair: ils sont hashés (méthode moderne).
- Les formulaires sont protégés contre les attaques CSRF (jeton caché vérifié côté serveur).
- Les permissions empêchent un utilisateur de modifier/supprimer les articles des autres, sauf admin.

## 5) Optimisations (non technique)
- Mise en cache: certaines listes/statistiques sont mémorisées pour aller plus vite.
- Images compressées: les images envoyées sont redimensionnées et allégées automatiquement.

## 6) API (non technique)
- L’API fournit les données du blog au format JSON (lisible par sites web/app mobiles).
- La structure est prête pour une application mobile qui consommerait les mêmes données.
- Une page de test simple existe pour vérifier que l’API répond: `public/api-test.html`.

## 7) Comment se passe un “flux” classique
- Un utilisateur se connecte (sécurité vérifiée).
- Il crée un article: l’image est compressée, le contenu enregistré.
- La liste des articles s’affiche plus vite grâce au cache.
- L’API permet de récupérer ces données pour d’autres clients (site, mobile…)

## 8) Où modifier quoi ?
- Changer la présentation: `templates/` (HTML/Twig) et `assets/styles`.
- Ajouter un endpoint API: `src/Controller/Api/…` + route (attribut `#[Route]`).
- Ajouter un formulaire: `src/Form/…` et la vue Twig associée.
- Nouveaux champs en base: créer une propriété dans `src/Entity/…`, générer une migration.
- Règles d’accès: `src/Security/PostVoter.php` ou `config/packages/security.yaml`.
- Ajuster le cache: `src/Service/CacheService.php`.
- Ajuster l’optimisation d’images: `src/Service/ImageOptimizerService.php`.

## 9) Mise en route rapide
1. Démarrer MySQL (XAMPP) et configurer `DATABASE_URL` dans `.env.local`.
2. `php bin/console doctrine:database:create`
3. `php bin/console doctrine:migrations:migrate -n`
4. Lancer le serveur: `php -S 127.0.0.1:8000 -t public`
5. Tester l’API: `http://127.0.0.1:8000/api-test.html`
6. Voir les stats: `http://127.0.0.1:8000/stats/`

## 10) Questions fréquentes
- Je vois une erreur 500 “connexion refusée”: la base MySQL n’est pas lancée.
- Je veux activer le cache Redis: ajuster `config/packages/cache.yaml`.
- Je veux JWT (tokens pour mobile): installer/configurer LexikJWT ultérieurement.

---

Si vous avez besoin d’un guide pas à pas (captures, vidéos), on peut l’ajouter dans `docs/` ultérieurement.