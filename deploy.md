# Guide de déploiement - Mon Blog Symfony

## 🚀 Déploiement sur Heroku (Recommandé)

### 1. Prérequis
- Compte GitHub
- Compte Heroku (gratuit)
- Git installé sur votre machine

### 2. Préparation du projet

#### A. Créer un repository GitHub
```bash
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin https://github.com/votre-username/votre-blog.git
git push -u origin main
```

#### B. Créer l'application Heroku
1. Aller sur [Heroku](https://heroku.com)
2. Se connecter et cliquer "New" > "Create new app"
3. Choisir un nom unique (ex: mon-blog-symfony-2024)
4. Choisir la région (Europe)

#### C. Connecter GitHub à Heroku
1. Dans l'onglet "Deploy" de votre app Heroku
2. Choisir "GitHub" comme méthode de déploiement
3. Connecter votre repository GitHub
4. Activer "Automatic deploys"

### 3. Configuration de la base de données

#### A. Ajouter ClearDB (MySQL gratuit)
```bash
heroku addons:create cleardb:ignite
```

#### B. Récupérer l'URL de la base de données
```bash
heroku config:get CLEARDB_DATABASE_URL
```

### 4. Configuration des variables d'environnement

Dans l'onglet "Settings" > "Config Vars" de votre app Heroku :

```
APP_ENV = prod
APP_SECRET = [généré automatiquement]
DATABASE_URL = [URL de ClearDB]
SYMFONY_ENV = prod
```

### 5. Déploiement

#### A. Déployer depuis GitHub
1. Dans l'onglet "Deploy"
2. Cliquer "Deploy Branch" sur la branche main

#### B. Exécuter les migrations
```bash
heroku run php bin/console doctrine:migrations:migrate
```

#### C. Créer un utilisateur admin
```bash
heroku run php bin/console app:create-admin
```

### 6. Configuration finale

#### A. Vérifier l'URL
Votre blog sera accessible à : `https://votre-app-name.herokuapp.com`

#### B. Tester les fonctionnalités
- Créer un compte utilisateur
- Créer un article
- Tester la responsivité

## 🔧 Alternatives d'hébergement

### 1. Railway (Gratuit)
- Plus moderne que Heroku
- Base de données MySQL incluse
- Déploiement automatique depuis GitHub

### 2. Render (Gratuit)
- Service similaire à Heroku
- Base de données PostgreSQL gratuite
- SSL automatique

### 3. DigitalOcean (Payant - 5$/mois)
- VPS complet
- Plus de contrôle
- Meilleure performance

## 📝 Notes importantes

- **Sécurité** : Changez APP_SECRET en production
- **Base de données** : Sauvegardez régulièrement vos données
- **Domaine personnalisé** : Possible avec les plans payants
- **SSL** : Inclus gratuitement sur Heroku

## 🆘 Support

En cas de problème :
1. Vérifier les logs : `heroku logs --tail`
2. Vérifier la configuration des variables d'environnement
3. S'assurer que toutes les migrations sont exécutées
