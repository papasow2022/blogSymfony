/**
 * Client JavaScript pour l'API REST du blog
 * Peut être utilisé dans une application mobile ou un frontend séparé
 */

class BlogApiClient {
    constructor(baseUrl = 'http://127.0.0.1:8000/api') {
        this.baseUrl = baseUrl;
        this.token = localStorage.getItem('auth_token'); // Pour une future implémentation JWT
    }

    /**
     * Effectue une requête HTTP vers l'API
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        
        const config = {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        };

        // Ajouter le token d'authentification si disponible
        if (this.token) {
            config.headers['Authorization'] = `Bearer ${this.token}`;
        }

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || `HTTP ${response.status}`);
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    // ===== ARTICLES =====

    /**
     * Récupère la liste des articles
     */
    async getPosts(page = 1, limit = 10, category = null, search = null) {
        const params = new URLSearchParams({
            page: page.toString(),
            limit: limit.toString()
        });

        if (category) params.append('category', category);
        if (search) params.append('search', search);

        return this.request(`/posts?${params.toString()}`);
    }

    /**
     * Récupère un article par son ID
     */
    async getPost(id) {
        return this.request(`/posts/${id}`);
    }

    /**
     * Récupère les articles populaires
     */
    async getPopularPosts(limit = 5) {
        return this.request(`/posts/popular?limit=${limit}`);
    }

    // ===== COMMENTAIRES =====

    /**
     * Récupère les commentaires d'un article
     */
    async getComments(postId, page = 1, limit = 10) {
        const params = new URLSearchParams({
            page: page.toString(),
            limit: limit.toString()
        });

        return this.request(`/posts/${postId}/comments?${params.toString()}`);
    }

    /**
     * Crée un nouveau commentaire
     */
    async createComment(postId, content, parentId = null) {
        const formData = new FormData();
        formData.append('content', content);
        if (parentId) formData.append('parent_id', parentId);

        return this.request(`/posts/${postId}/comments`, {
            method: 'POST',
            body: formData,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        });
    }

    // ===== UTILISATEURS =====

    /**
     * Récupère le profil de l'utilisateur connecté
     */
    async getProfile() {
        return this.request('/profile');
    }

    /**
     * Récupère un utilisateur par son ID (admin uniquement)
     */
    async getUser(id) {
        return this.request(`/users/${id}`);
    }

    /**
     * Récupère la liste des utilisateurs (admin uniquement)
     */
    async getUsers(page = 1, limit = 20) {
        const params = new URLSearchParams({
            page: page.toString(),
            limit: limit.toString()
        });

        return this.request(`/users?${params.toString()}`);
    }

    // ===== AUTHENTIFICATION =====

    /**
     * Inscrit un nouvel utilisateur
     */
    async register(email, password, confirmPassword) {
        const formData = new FormData();
        formData.append('email', email);
        formData.append('password', password);
        formData.append('confirm_password', confirmPassword);

        return this.request('/register', {
            method: 'POST',
            body: formData,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        });
    }

    /**
     * Définit le token d'authentification
     */
    setToken(token) {
        this.token = token;
        localStorage.setItem('auth_token', token);
    }

    /**
     * Supprime le token d'authentification
     */
    clearToken() {
        this.token = null;
        localStorage.removeItem('auth_token');
    }
}

// ===== EXEMPLES D'UTILISATION =====

// Créer une instance du client
const apiClient = new BlogApiClient();

// Exemple : Récupérer les articles populaires
async function loadPopularPosts() {
    try {
        const response = await apiClient.getPopularPosts(3);
        console.log('Articles populaires:', response.data);
        
        // Afficher dans le DOM
        const container = document.getElementById('popular-posts');
        if (container) {
            container.innerHTML = response.data.map(post => `
                <div class="post-card">
                    <h3>${post.title}</h3>
                    <p>Vues: ${post.viewsCount} | Likes: ${post.likesCount}</p>
                    ${post.image ? `<img src="${post.image}" alt="${post.title}" style="max-width: 200px;">` : ''}
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Erreur lors du chargement des articles populaires:', error);
    }
}

// Exemple : Créer un commentaire
async function submitComment(postId, content) {
    try {
        const response = await apiClient.createComment(postId, content);
        console.log('Commentaire créé:', response.data);
        
        // Recharger les commentaires
        await loadComments(postId);
        
        // Afficher un message de succès
        alert('Commentaire soumis avec succès ! Il sera visible après approbation.');
    } catch (error) {
        console.error('Erreur lors de la création du commentaire:', error);
        alert('Erreur: ' + error.message);
    }
}

// Exemple : Charger les commentaires d'un article
async function loadComments(postId) {
    try {
        const response = await apiClient.getComments(postId);
        console.log('Commentaires:', response.data);
        
        // Afficher dans le DOM
        const container = document.getElementById('comments-container');
        if (container) {
            container.innerHTML = response.data.map(comment => `
                <div class="comment">
                    <div class="comment-header">
                        <strong>${comment.author.email}</strong>
                        <small>${comment.createdAt}</small>
                    </div>
                    <div class="comment-content">${comment.content}</div>
                    ${comment.replies && comment.replies.length > 0 ? `
                        <div class="replies">
                            ${comment.replies.map(reply => `
                                <div class="reply">
                                    <strong>${reply.author.email}</strong>: ${reply.content}
                                </div>
                            `).join('')}
                        </div>
                    ` : ''}
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Erreur lors du chargement des commentaires:', error);
    }
}

// Exemple : Inscription d'un utilisateur
async function registerUser(email, password, confirmPassword) {
    try {
        const response = await apiClient.register(email, password, confirmPassword);
        console.log('Utilisateur inscrit:', response.data);
        
        alert('Inscription réussie ! Vous pouvez maintenant vous connecter.');
    } catch (error) {
        console.error('Erreur lors de l\'inscription:', error);
        alert('Erreur: ' + error.message);
    }
}

// Exemple : Charger le profil utilisateur
async function loadUserProfile() {
    try {
        const response = await apiClient.getProfile();
        console.log('Profil utilisateur:', response.data);
        
        // Afficher dans le DOM
        const container = document.getElementById('user-profile');
        if (container) {
            container.innerHTML = `
                <h3>Profil de ${response.data.email}</h3>
                <p>ID: ${response.data.id}</p>
                <p>Rôles: ${response.data.roles.join(', ')}</p>
                <p>Compte bloqué: ${response.data.isBlocked ? 'Oui' : 'Non'}</p>
                ${response.data.avatar ? `<img src="${response.data.avatar}" alt="Avatar" style="max-width: 100px;">` : ''}
            `;
        }
    } catch (error) {
        console.error('Erreur lors du chargement du profil:', error);
        // Rediriger vers la page de connexion si non authentifié
        if (error.message.includes('401') || error.message.includes('403')) {
            window.location.href = '/login';
        }
    }
}

// Exporter pour utilisation dans d'autres modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BlogApiClient;
} else {
    // Exposer globalement pour utilisation dans le navigateur
    window.BlogApiClient = BlogApiClient;
    window.apiClient = apiClient;
} 