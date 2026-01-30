# 🚀 Guide de Développement - Authentication Microservice

## 📚 Architecture Docker - Explication des Stages

### **Stage 1: Builder (base)**
- **Rôle:** Image de base avec PHP 8.4 et toutes les extensions
- **Contenu:** 
  - Extensions PHP compilées (pdo_pgsql, intl, opcache, zip, bcmath)
  - Extensions PECL (amqp pour RabbitMQ, redis)
  - Composer installé
- **Taille:** ~200 MB
- **Utilisation:** Base pour les autres stages

### **Stage 2: Development**
- **Rôle:** Environnement de développement complet
- **Contenu:**
  - Hérite du stage "base"
  - Xdebug activé pour le debugging
  - Configuration Xdebug pour développement local
  - Toutes les dépendances (y compris dev)
- **Taille:** ~250 MB
- **Utilisation:** Pour coder, tester, débugger en local

### **Stage 3: Production**
- **Rôle:** Image optimisée pour la production
- **Contenu:**
  - Hérite du stage "base"
  - Dépendances optimisées (--no-dev)
  - Cache Symfony pré-compilé
  - Health check configuré
  - Permissions sécurisées
- **Taille:** ~150 MB
- **Utilisation:** Déploiement en production

---

## 🛠️ Installation et Démarrage (Mode Développement)

### **Prérequis**
- Docker et Docker Compose installés
- Git installé
- Ports disponibles: 8080 (API), 5432 (PostgreSQL), 6379 (Redis), 5672/15672 (RabbitMQ)

---

## 🎯 Démarrage Rapide avec Make

### **Commande Unique**
```bash
make dev
```

Cette commande exécute automatiquement toutes les étapes ci-dessous dans l'ordre.

---

## 📋 Étapes Détaillées (Exécutées par `make dev`)

### **Étape 1: Cloner le projet**
```bash
git clone https://github.com/missaouiabdou/TEEE.git
cd TEEE
```

### **Étape 2: Configurer l'environnement**
```bash
# Copier le fichier d'environnement
cp .env .env.local

# Les variables par défaut sont déjà configurées
# Vous pouvez les modifier si nécessaire
```

### **Étape 3: Générer les clés JWT**
```bash
# Rendre le script exécutable
chmod +x bin/generate-jwt-keys.sh

# Générer les clés RSA pour JWT
./bin/generate-jwt-keys.sh
```
**Résultat:** Clés générées dans `config/jwt/`

### **Étape 4: Construire les images Docker**
```bash
# Construire l'image PHP en mode développement
docker-compose build
```
**Durée:** 2-5 minutes (première fois)

### **Étape 5: Démarrer les conteneurs**
```bash
# Lancer tous les services en arrière-plan
docker-compose up -d
```
**Services démarrés:**
- PHP-FPM (port 9000)
- Nginx (port 8080)
- PostgreSQL (port 5432)
- Redis (port 6379)
- RabbitMQ (ports 5672, 15672)

### **Étape 6: Installer les dépendances**
```bash
# Installer les packages Composer
docker-compose exec php composer install
```
**Durée:** 1-2 minutes

### **Étape 7: Créer la base de données**
```bash
# Créer la base de données PostgreSQL
docker-compose exec php php bin/console doctrine:database:create
```
**Résultat:** Base de données `auth_db` créée

### **Étape 8: Exécuter les migrations**
```bash
# Créer les tables dans la base de données
docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction
```
**Résultat:** Tables `users` et `outbox_events` créées

### **Étape 9: Vérifier l'installation**
```bash
# Tester l'API
curl http://localhost:8080/api/health
```
**Résultat attendu:**
```json
{
  "status": "healthy",
  "timestamp": "2024-01-30T10:00:00+00:00"
}
```

---

## 🧪 Tester l'API

### **1. Enregistrer un utilisateur**
```bash
curl -X POST http://localhost:8080/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john.doe@example.com",
    "password": "SecurePass123!",
    "firstName": "John",
    "lastName": "Doe"
  }'
```

### **2. Se connecter**
```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john.doe@example.com",
    "password": "SecurePass123!"
  }'
```

**Résultat:** Vous recevrez un token JWT

### **3. Accéder à une route protégée**
```bash
curl http://localhost:8080/api/user/profile \
  -H "Authorization: Bearer VOTRE_TOKEN_JWT"
```

---

## 🔧 Commandes Utiles

### **Logs et Debugging**
```bash
# Voir les logs en temps réel
make logs

# Logs d'un service spécifique
docker-compose logs -f php
docker-compose logs -f postgres
```

### **Gestion des Services**
```bash
# Arrêter les services
make stop

# Redémarrer les services
make restart

# Supprimer tout (conteneurs + volumes)
make clean
```

### **Base de Données**
```bash
# Accéder à PostgreSQL
make db-shell

# Créer une nouvelle migration
docker-compose exec php php bin/console make:migration

# Réinitialiser la base de données
make db-reset
```

### **Cache Symfony**
```bash
# Vider le cache
make cache-clear

# Réchauffer le cache
docker-compose exec php php bin/console cache:warmup
```

---

## 📊 Interfaces Web Disponibles

| Service | URL | Credentials |
|---------|-----|-------------|
| **API** | http://localhost:8080 | - |
| **RabbitMQ Management** | http://localhost:15672 | guest / guest |
| **PostgreSQL** | localhost:5432 | auth_user / auth_pass |

---

## 🐛 Résolution de Problèmes

### **Problème: Port déjà utilisé**
```bash
# Vérifier les ports utilisés
sudo lsof -i :8080
sudo lsof -i :5432

# Modifier les ports dans docker-compose.yml
```

### **Problème: Erreur de permissions**
```bash
# Donner les permissions sur les dossiers
sudo chown -R $USER:$USER var/
sudo chmod -R 777 var/
```

### **Problème: Base de données non créée**
```bash
# Recréer la base de données
make db-reset
```

### **Problème: Clés JWT manquantes**
```bash
# Régénérer les clés JWT
./bin/generate-jwt-keys.sh
```

---

## 🚀 Passer en Production

Voir le fichier `GUIDE_PRODUCTION.md` pour les instructions de déploiement en production.

---

## 📞 Support

Pour toute question ou problème:
- Créer une issue sur GitHub: https://github.com/missaouiabdou/TEEE/issues
- Consulter la documentation Symfony: https://symfony.com/doc/current/index.html

---

**Bon développement ! 🎉**