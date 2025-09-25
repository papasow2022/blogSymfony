# 🚀 API REST du Blog

## 🎯 Objectif

Cette API REST permet de **séparer complètement le backend du frontend**, facilitant ainsi :
- ✅ La création d'applications mobiles
- ✅ Le développement de clients web séparés
- ✅ L'intégration avec d'autres systèmes
- ✅ La scalabilité et la maintenance

## 🏗️ Architecture

```
┌─────────────────┐    HTTP/JSON    ┌─────────────────┐
│   Frontend Web  │ ◄─────────────► │   API REST      │
│   (Symfony)     │                 │   (Symfony)     │
└─────────────────┘                 └─────────────────┘
                                              │
                                              ▼
                                    ┌─────────────────┐
                                    │   Base de       │
                                    │   données       │
                                    │   (MySQL)       │
                                    └─────────────────┘

┌─────────────────┐    HTTP/JSON    ┌─────────────────┐
│   App Mobile    │ ◄─────────────► │   API REST      │
│   (React Native)│                 │   (Symfony)     │
└─────────────────┘                 └─────────────────┘
```

## 🚀 Démarrage rapide

### 1. Démarrer le serveur
```bash
symfony server:stop || true
symfony serve -d --port=8000
```

Ouvrez `http://127.0.0.1:8000/api-test.html` dans votre navigateur pour tester visuellement tous les endpoints.

> Important: restez sur le même port (8000) pour /login et api-test.html afin d’éviter les erreurs CSRF/session.

### 2. Tester l'API
Ouvrez votre navigateur et allez sur :
```
http://127.0.0.1:8000/api-test.html
```

### 3. Tester avec curl
```bash
# Récupérer tous les articles
curl "http://127.0.0.1:8000/api/posts"

# Récupérer un article spécifique
curl "http://127.0.0.1:8000/api/posts/1"

# Récupérer les articles populaires
curl "http://127.0.0.1:8000/api/posts/popular"
```

## 📱 Utilisation dans une application mobile

### React Native
```javascript
import { BlogApiClient } from './api-client';

const apiClient = new BlogApiClient('http://127.0.0.1:8000/api');

// Charger les articles
const loadPosts = async () => {
    try {
        const response = await apiClient.getPosts(1, 10);
        setPosts(response.data);
    } catch (error) {
        console.error('Erreur:', error);
    }
};

// Créer un commentaire
const submitComment = async (postId, content) => {
    try {
        await apiClient.createComment(postId, content);
        alert('Commentaire soumis !');
    } catch (error) {
        alert('Erreur: ' + error.message);
    }
};
```

### Flutter
```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

class BlogApiClient {
  final String baseUrl = 'http://127.0.0.1:8000/api';

  Future<List<Post>> getPosts({int page = 1, int limit = 10}) async {
    final response = await http.get(
      Uri.parse('$baseUrl/posts?page=$page&limit=$limit'),
    );

    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      return (data['data'] as List)
          .map((json) => Post.fromJson(json))
          .toList();
    } else {
      throw Exception('Échec du chargement des articles');
    }
  }
}
```

## 🌐 Utilisation dans un frontend web séparé

### Vue.js
```javascript
// main.js
import { createApp } from 'vue';
import { BlogApiClient } from './api-client';

const app = createApp(App);
app.config.globalProperties.$api = new BlogApiClient();

// Component.vue
export default {
  async mounted() {
    try {
      const response = await this.$api.getPopularPosts(5);
      this.popularPosts = response.data;
    } catch (error) {
      console.error('Erreur:', error);
    }
  }
}
```

### React
```javascript
import { BlogApiClient } from './api-client';

const apiClient = new BlogApiClient();

function BlogPosts() {
  const [posts, setPosts] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const loadPosts = async () => {
      try {
        const response = await apiClient.getPosts();
        setPosts(response.data);
      } catch (error) {
        console.error('Erreur:', error);
      } finally {
        setLoading(false);
      }
    };

    loadPosts();
  }, []);

  if (loading) return <div>Chargement...</div>;

  return (
    <div>
      {posts.map(post => (
        <div key={post.id}>
          <h2>{post.title}</h2>
          <p>{post.content}</p>
        </div>
      ))}
    </div>
  );
}
```

## 🔐 Authentification

L'API utilise actuellement le système d'authentification Symfony. Pour une utilisation mobile, il est recommandé d'implémenter JWT :

### Implémentation JWT (futur)
```bash
composer require lexik/jwt-authentication-bundle
```

```yaml
# config/packages/security.yaml
firewalls:
    api:
        pattern: ^/api
        stateless: true
        jwt: ~
```

## 📊 Endpoints disponibles

| Méthode | Endpoint | Description | Authentification |
|---------|----------|-------------|------------------|
| GET | `/api/posts` | Liste des articles | Non |
| GET | `/api/posts/{id}` | Article spécifique | Non |
| GET | `/api/posts/popular` | Articles populaires | Non |
| GET | `/api/posts/{postId}/comments` | Commentaires d'un article | Non |
| POST | `/api/posts/{postId}/comments` | Créer un commentaire | Oui (ROLE_USER) |
| GET | `/api/profile` | Profil utilisateur | Oui (ROLE_USER) |
| GET | `/api/users` | Liste des utilisateurs | Oui (ROLE_ADMIN) |
| POST | `/api/register` | Inscription | Non |

## 🛠️ Développement

### Ajouter un nouvel endpoint
1. Créer une méthode dans le contrôleur approprié
2. Ajouter la route avec l'annotation `#[Route]`
3. Tester avec l'interface de test

### Exemple d'ajout d'endpoint
```php
// Dans PostApiController.php
#[Route('/posts/{id}/like', name: 'api_posts_like', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
public function like(int $id, PostRepository $postRepository): JsonResponse
{
    // Logique de like
    return $this->json(['success' => true]);
}
```

## 🧪 Tests

### Tests automatiques
```bash
# Tester tous les endpoints
php bin/console debug:router | grep api

# Tester un endpoint spécifique
curl -X GET "http://127.0.0.1:8000/api/posts" -H "Accept: application/json"
```

### Interface de test
Ouvrez `http://127.0.0.1:8000/api-test.html` dans votre navigateur pour tester visuellement tous les endpoints.

## 📈 Avantages de cette approche

1. **Séparation des responsabilités** : Backend et frontend sont indépendants
2. **Réutilisabilité** : Une seule API pour plusieurs clients
3. **Scalabilité** : Le backend peut être optimisé séparément
4. **Maintenance** : Plus facile de maintenir et faire évoluer
5. **Mobile-first** : Prêt pour les applications mobiles
6. **API-first** : L'API est le cœur du système

## 🔮 Évolutions futures

- [x] Authentification JWT (configuré)
- [ ] Rate limiting
- [x] Cache intelligent (implémenté)
- [ ] Documentation Swagger/OpenAPI
- [ ] Tests automatisés
- [ ] Monitoring et métriques
- [ ] Webhooks pour les notifications

## ✅ **AMÉLIORATIONS TECHNIQUES IMPLÉMENTÉES**

### 🔒 **Sécurité**
- ✅ **Hachage des mots de passe** : bcrypt/argon2 automatique
- ✅ **Protection CSRF** : Active sur tous les formulaires
- ✅ **Vérification des rôles** : Seuls les admins peuvent supprimer les articles des autres

### ⚡ **Optimisation**
- ✅ **Cache intelligent** : 
  - Articles populaires (1h)
  - Derniers articles (30min)
  - Recherches (15min)
  - Statistiques (1h)
- ✅ **Compression automatique des images** :
  - Redimensionnement (max 1200x800px)
  - Compression JPEG à 85%, PNG à 65-80%
  - Suppression des métadonnées

### 🌐 **API REST**
- ✅ **Backend/Frontend séparés** : Architecture complètement découplée
- ✅ **Prêt pour mobile** : Documentation et exemples fournis

## 📚 Ressources

- [Documentation Symfony](https://symfony.com/doc/current/index.html)
- [Guide REST API](https://restfulapi.net/)
- [JWT Authentication](https://jwt.io/)
- [API Testing Tools](https://www.postman.com/)

---

**🎉 Votre blog est maintenant prêt pour l'ère mobile et la séparation backend/frontend !** 