# API REST Documentation

## Vue d'ensemble
Cette API REST permet de séparer le backend du frontend, facilitant la création d'applications mobiles et de clients web séparés.

## Base URL
```
http://127.0.0.1:8000/api
```

## Endpoints

### Posts

#### GET /api/posts
Récupère la liste des articles avec pagination.

**Paramètres de requête :**
- `page` (optionnel) : Numéro de page (défaut: 1)
- `limit` (optionnel) : Nombre d'articles par page (défaut: 10)
- `category` (optionnel) : Filtrer par catégorie
- `search` (optionnel) : Rechercher dans le titre et contenu

**Exemple :**
```bash
curl "http://127.0.0.1:8000/api/posts?page=1&limit=5"
```

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Titre de l'article",
      "content": "Contenu de l'article...",
      "author": {
        "id": 1,
        "email": "user@example.com",
        "avatar": "/uploads/avatars/avatar.jpg"
      },
      "createdAt": "2024-01-01 12:00:00",
      "updatedAt": "2024-01-01 12:00:00",
      "likesCount": 5,
      "viewsCount": 100,
      "category": {
        "id": 1,
        "name": "Technologie"
      },
      "tags": ["php", "symfony"],
      "image": "/uploads/posts/image.jpg"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 5,
    "total": 1
  }
}
```

#### GET /api/posts/{id}
Récupère un article spécifique par son ID.

**Exemple :**
```bash
curl "http://127.0.0.1:8000/api/posts/1"
```

#### GET /api/posts/popular
Récupère les articles les plus populaires.

**Paramètres de requête :**
- `limit` (optionnel) : Nombre d'articles (défaut: 5)

**Exemple :**
```bash
curl "http://127.0.0.1:8000/api/posts/popular?limit=3"
```

### Commentaires

#### GET /api/posts/{postId}/comments
Récupère les commentaires d'un article.

**Paramètres de requête :**
- `page` (optionnel) : Numéro de page (défaut: 1)
- `limit` (optionnel) : Nombre de commentaires par page (défaut: 10)

**Exemple :**
```bash
curl "http://127.0.0.1:8000/api/posts/1/comments"
```

#### POST /api/posts/{postId}/comments
Crée un nouveau commentaire (authentification requise).

**Corps de la requête :**
```json
{
  "content": "Contenu du commentaire",
  "parent_id": null
}
```

**Exemple :**
```bash
curl -X POST "http://127.0.0.1:8000/api/posts/1/comments" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "content=Mon commentaire&parent_id="
```

### Utilisateurs

#### GET /api/users
Récupère la liste des utilisateurs (admin uniquement).

**Paramètres de requête :**
- `page` (optionnel) : Numéro de page (défaut: 1)
- `limit` (optionnel) : Nombre d'utilisateurs par page (défaut: 20)

#### GET /api/users/{id}
Récupère un utilisateur spécifique (soi-même ou admin).

#### GET /api/profile
Récupère le profil de l'utilisateur connecté.

### Authentification

#### POST /api/register
Inscrit un nouvel utilisateur.

**Corps de la requête :**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "confirm_password": "password123"
}
```

#### POST /api/login
**Note :** L'authentification se fait via l'interface web Symfony pour l'instant.

#### POST /api/logout
Déconnexion de l'utilisateur.

## Codes de statut HTTP

- `200 OK` : Requête réussie
- `201 Created` : Ressource créée avec succès
- `400 Bad Request` : Données de requête invalides
- `401 Unauthorized` : Authentification requise
- `403 Forbidden` : Accès refusé
- `404 Not Found` : Ressource non trouvée
- `409 Conflict` : Conflit (ex: email déjà utilisé)

## Authentification

L'API utilise le système d'authentification Symfony. Pour les endpoints protégés, l'utilisateur doit être connecté via l'interface web.

## Pagination

La plupart des endpoints de liste supportent la pagination avec les paramètres `page` et `limit`.

## Gestion des erreurs

Toutes les réponses d'erreur suivent ce format :

```json
{
  "success": false,
  "message": "Description de l'erreur"
}
```

## Exemples d'utilisation

### Récupérer les articles populaires
```bash
curl "http://127.0.0.1:8000/api/posts/popular"
```

### Créer un commentaire
```bash
curl -X POST "http://127.0.0.1:8000/api/posts/1/comments" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "content=Excellent article !"
```

### Récupérer le profil utilisateur
```bash
curl "http://127.0.0.1:8000/api/profile" \
  -H "Cookie: PHPSESSID=votre_session_id"
```

## Avantages de cette séparation

1. **Backend/Frontend séparés** : L'API peut être utilisée par différents clients
2. **Application mobile** : Possibilité de créer une app mobile avec la même base de données
3. **Scalabilité** : Le backend peut être optimisé indépendamment du frontend
4. **Réutilisabilité** : L'API peut servir plusieurs interfaces (web, mobile, desktop)
5. **Maintenance** : Plus facile de maintenir et faire évoluer séparément 