# Implémentation – Vue d'ensemble des dossiers et fichiers

Ce document résume ce qui a été ajouté/modifié, où se trouve le code, et le rôle de chaque élément principal.

## 1) API REST (backend séparé)
- `config/routes/api.yaml`:
  - Rôle: charge automatiquement toutes les routes des contrôleurs API via les attributs (`#[Route]`).
- `src/Controller/Api/PostApiController.php`:
  - Rôle: endpoints JSON pour les articles (liste, populaires, pagination/sort).
- `src/Controller/Api/UserApiController.php`:
  - Rôle: endpoints JSON liés aux utilisateurs (ex: liste, profil protégé).
- `src/Controller/Api/CommentApiController.php`:
  - Rôle: endpoints JSON pour les commentaires (lecture/création).
- `src/Controller/Api/AuthApiController.php`:
  - Rôle: endpoints d’authentification basiques (placeholders si JWT futur).

Front de test API (statique):
- `public/api-test.html`:
  - Rôle: page de test interactive des endpoints API.
- `public/debug-api.html`, `public/api-simple-test.html`:
  - Rôle: pages utilitaires de test rapide.
- `public/api-client.js`:
  - Rôle: petit client JS pour consommer l’API depuis les pages HTML de test.

## 2) Sécurité
- `config/packages/security.yaml`:
  - Rôle: hachage mots de passe (auto: bcrypt/argon2), firewalls, access_control.
- `src/Security/AppAuthenticator.php`:
  - Rôle: authentification formulaire; vérification CSRF via `CsrfTokenBadge`.
- `src/Security/PostVoter.php`:
  - Rôle: autorisations fines sur les articles (ADMIN = tous droits; sinon auteur uniquement).
- `src/Entity/User.php`:
  - Rôle: entité utilisateur (rôles, mot de passe hashé via hasher à l’inscription).

## 3) Optimisation (cache + images)
- `src/Service/CacheService.php` (NOUVEAU):
  - Rôle: cache métier (articles populaires/récents, recherches, stats, etc.) + méthodes d’invalidation.
- `src/Service/ImageOptimizerService.php` (NOUVEAU):
  - Rôle: redimensionnement + compression d’images (JPEG/PNG/GIF/WebP/SVG) avec optimisation.
- `composer.json` → dépendance `spatie/image-optimizer`:
  - Rôle: bibliothèque d’optimisation d’images utilisée par le service.

## 4) Contrôleur Articles (intégration cache + optimisation images)
- `src/Controller/PostController.php`:
  - `index()` utilise `CacheService` pour lister (récents/recherche) avec cache.
  - `new()` et `edit()` appellent `ImageOptimizerService` après upload; invalident les caches.
  - `show()` incrémente les vues et invalide le cache ciblé + stats.
  - `delete()` invalide les caches après suppression.

## 5) Vues / UI
- `templates/stats/index.html.twig` (NOUVEAU):
  - Rôle: page stats (articles populaires/likés + indicateurs), alimentée par `CacheService` via `StatsController`.
- `src/Controller/StatsController.php` (NOUVEAU):
  - Rôle: rend la page `/stats/` et expose l’endpoint `/stats/api` (JSON) avec données mises en cache.

## 6) Configuration / Cache Framework
- `config/packages/cache.yaml`:
  - Rôle: configuration cache Symfony (backend par défaut fichier; prêt pour Redis/APCu si besoin).

## 7) Documentation
- `README_API.md` (mis à jour):
  - Rôle: guide d’utilisation de l’API, exemples (web/mobile), et section "Améliorations techniques".
- `VERIFICATION_AMELIORATIONS.md` (NOUVEAU):
  - Rôle: check-list des améliorations (sécurité, optimisation, API) et leur statut.
- `docs/IMPLEMENTATION_OVERVIEW.md` (ce fichier):
  - Rôle: cartographie rapide des fichiers/dossiers et de leur rôle.

## 8) Comment tester rapidement
- Lancer le serveur (au choix):
  - `symfony serve -d --port=8000` (Symfony CLI), ou
  - `php -S 127.0.0.1:8000 -t public`
- Ouvrir: `http://127.0.0.1:8000/api-test.html`
  - Vérifier: Posts, Popular, Register, Profile, Comments.
- Ouvrir: `http://127.0.0.1:8000/stats/`
  - Vérifier: stats, populaires, plus likés.

## 9) Points d’extension (facultatif)
- Activer Redis pour le cache applicatif dans `config/packages/cache.yaml`.
- Finaliser JWT (Lexik) si besoin d’auth stateless mobile.
- Ajouter rate limiting et documentation OpenAPI (Swagger) pour l’API.

---

Dernière mise à jour: générée automatiquement lors de l’intégration des optimisations et de l’API.