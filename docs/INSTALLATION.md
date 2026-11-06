# Installation et lancement

## Versions retenues pour ce poste

Contrôle du 22/10/2026 : Git 2.55.0, Node.js 24.19.0, npm 11.17.0, Composer 2.10.3 ; PostgreSQL 18.3 installé, service démarré. PHP actif global : 8.0.30 (XAMPP), incompatible avec Laravel 13. Un PHP **8.5.12 x64 NTS** séparé a été téléchargé depuis php.net avec vérification SHA-256. Son emplacement local se trouve dans le rapport d'environnement, hors du dépôt partagé. Aucun changement de PHP global n'est nécessaire.

PHP 8.3 minimum pour Laravel 13 ; utiliser PHP 8.5 pour s'aligner sur ce poste et le futur contrôle automatisé. Composer vérifie les exigences exactes du fichier de verrouillage. Extensions chargées : curl, fileinfo, mbstring, openssl, pdo_pgsql, pgsql et zip, en plus des extensions de base. Ne jamais désactiver la vérification TLS pour installer une dépendance.

## Serveur Laravel

Ouvrir un terminal dans `backend/` en utilisant le PHP compatible en tête du PATH pour cette session, puis :

```sh
composer install
```

Copier `.env.example` vers `.env` (sous PowerShell : `Copy-Item .env.example .env`), puis :

```sh
php artisan key:generate
php artisan serve --host=127.0.0.1 --port=8000
```

La route `/up` vérifie le démarrage de Laravel. Le démarrage du socle utilise des fichiers pour les sessions et le cache ; il ne prouve pas la connexion à PostgreSQL. Ne pas lancer `composer run setup` : le script généré par défaut est retiré car il ne correspond pas à notre frontend séparé et peut appliquer des migrations prématurément.

## Interface React

Dans un autre terminal, dossier `frontend/` :

```sh
npm ci
npm run dev -- --host 127.0.0.1
```

Ouvrir l'adresse indiquée, généralement `http://127.0.0.1:5173`. Il s'agit d'une page de démarrage du projet, sans commandes ni connexion métier opérationnelles.

## PostgreSQL : prochaine configuration

Créer une base dédiée `mon_miam_miam` et un utilisateur propre au projet avec les droits nécessaires uniquement sur cette base. Michael saisit lui-même les informations d'administration locales dans pgAdmin ; aucun mot de passe n'est demandé dans la conversation. Renseigner ensuite `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` dans `.env` local. La base, le rôle et la connexion applicative ne sont pas encore créés ni vérifiés par cette étape.

Relire le modèle MMM-25 avant les migrations métier MMM-48. Les migrations initiales fournies par Laravel sont conservées comme socle ; elles ne représentent pas les 15 tables proposées. Ne pas exécuter de migration sur une base existante de cours. Sanctum et les autorisations par restaurant seront configurés dans leurs tâches dédiées ; aucune règle CORS permissive n'est ajoutée au socle.

## Vérifications

```sh
# backend/
php artisan test
composer validate --strict
# frontend/
npm run lint
npm run build
```

Les résultats obtenus sur le poste sont consignés séparément ; GitHub Actions reste à exécuter après publication. Ces contrôles de démarrage ne remplacent pas les tests futurs de commandes, prix, rôles et séparation de deux restaurants.

Sources : https://laravel.com/framework/docs/13.x/installation ; https://www.php.net/downloads.php?os=windows
