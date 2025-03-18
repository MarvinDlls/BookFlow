# 📚 Guide d'installation du projet Symfony BookFlow 🚀

## 📋 Description du projet
Ce projet est une application web développée avec Symfony. Il permet de gérer diverses fonctionnalités liées à la création d'un utilisateur pouvant réserver jusqu'à 5 livres sous 7 jours avec l'approbation d'un administrateur qui va lui accorder ou non la possibilité de réserver ce livre. Ce projet utilise une base de données MySQL et inclut des fixtures pour créer des utilisateurs de base, ainsi qu'un utilisateur admin.

## ⚙️ Étapes d'installation

### 1️⃣ Cloner le projet
Clonez le projet depuis le dépôt GitHub en utilisant la commande suivante :
```bash
git clone https://github.com/MarvinDlls/BookFlow.git
cd BookFlow
```

### 2️⃣ Installer les dépendances
Une fois dans le dossier du projet, installez les dépendances avec Composer :

```bash
composer install
```

### 3️⃣ Configurer la base de données
Créez un fichier .env.local à la racine du projet s'il n'existe pas, et ajoutez les informations de connexion à votre base de données :

```dotenv
DATABASE_URL="mysql://root@127.0.0.1:3308/nom_de_la_base?serverVersion=8.0.32&charset=utf8mb4"
```

### 4️⃣ Créer la base de données
Lancez la commande suivante pour créer la base de données :

```bash
symfony console d:d:c
```

### 5️⃣ Créer les migrations
Ensuite, générez les migrations pour la base de données :

```bash
symfony console make:migration
```

### 6️⃣ Exécuter les migrations
Exécutez les migrations pour mettre à jour votre base de données avec la commande suivante :

```bash
symfony console d:m:m
```

### 7️⃣ Charger les fixtures
Pour charger les données de test dans la base de données, utilisez la commande suivante pour insérer les fixtures :

```bash
symfony console d:f:l
```

Les fixtures se trouvent dans le dossier `src/DataFixtures`, et incluent la création d'un utilisateur admin ainsi qu'un utilisateur de base.

## 🎉 Conclusion
Une fois ces étapes terminées, votre application BookFlow sera prête à être utilisée. Vous pouvez maintenant la démarrer et l'explorer sur votre serveur local.

---

> 💡 **Astuce**: Pour lancer le serveur de développement Symfony, utilisez la commande `symfony server:start`