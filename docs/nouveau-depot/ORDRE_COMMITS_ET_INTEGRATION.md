# Guide de reconstruction - Mon Miam Miam

ULC-ICAM, Groupe 4, X2. Professeur OSCAR KALOMBO DILU. 29 octobre 2026.

Nouveau dépôt privé : https://github.com/cikurumichael85-glitch/mon-miam-miam-groupe4
Source figée : https://github.com/cikurumichael85-glitch/mon-miam-miam/tree/c65a2066db0311a46824e8c8de56b26513d6b1a0

## 1. Ce que contient le dossier

Sept dossiers nominatifs, des lots CODE séparés, un guide personnel, un plan de commits, des commandes à lire et des manifestes SHA-256. Le PDF donne l'ordre global ; le CSV TOUS_LES_FICHIERS_ET_COMMITS.csv donne chaque fichier et son unique responsable.
Il s'agit d'un import coordonné du code existant, et non d'une réécriture de son historique. Les messages proposés décrivent un import. Chaque membre lit, vérifie et dépose personnellement ses lots avec son vrai compte.

## 2. Branches

Les sept branches contrib sont préparées depuis le même socle. La branche integration reçoit les PR ; main reçoit uniquement la livraison relue d'integration. Les premiers commits sont incomplets : une branche créée ne contient pas encore toute l'application.
La règle de protection reste une procédure tant que les règles serveur ne sont pas vérifiées et disponibles sur l'offre privée. Créer main et integration ne bloque pas techniquement un push direct. Ne jamais pousser directement sur ces deux branches.

## 3. Ordre global obligatoire pour la reconstruction

| Ordre | Responsable | Contenu | Fichiers |
| --- | --- | --- | --- |
| 01 | Moise Balaga | Cadrage et cahier des charges | 8 |
| 02 | Lucas Kandende | Conception et contrat API | 10 |
| 03 | Prudent Kakesa | Socle backend | 36 |
| 04 | Kabeya Ryan | Données et modèle commerce | 27 |
| 05 | Bacirhamya Yedidia | Comptes et sécurité | 10 |
| 06 | Kabeya Ryan | Commerce et paiement serveur | 24 |
| 07 | Bacirhamya Yedidia | Engagement et passerelle | 24 |
| 08 | Kabeya Ryan | Tests backend commerce | 12 |
| 09 | Bacirhamya Yedidia | Tests backend accès et engagement | 10 |
| 10 | Prudent Kakesa | Socle frontend | 13 |
| 11 | Michael Cikuru | Frontend client et ressources | 128 |
| 12 | Assek Arloni | Frontend professionnel et engagement | 23 |
| 13 | Michael Cikuru | Tests frontend client | 8 |
| 14 | Assek Arloni | Tests frontend professionnel | 9 |
| 15 | Prudent Kakesa | CI, recette et déploiement | 34 |

Chaque lot attend la fusion du précédent dans integration. Après cette fusion, le membre suivant récupère integration dans SA branche avant de copier le lot. C'est volontairement séquentiel pour la première reconstruction. Le travail futur pourra se faire en parallèle avec coordination des fichiers partagés.

## 4. Préparer sa copie UNE SEULE FOIS

Installer Git et VS Code. Utiliser son propre compte GitHub, accepter son invitation et cloner le nouveau dépôt dans un dossier neuf hors du projet local existant. Chaque membre garde son propre clone. Ne pas renommer l'ancien dossier .git ni réutiliser son origin.

```powershell
git clone https://github.com/cikurumichael85-glitch/mon-miam-miam-groupe4.git
Set-Location mon-miam-miam-groupe4
git remote -v
git config user.name "Votre nom réel"
git config user.email "Votre adresse GitHub vérifiée ou noreply"
git config core.autocrlf false
git fetch origin
git switch --track origin/contrib/votre-branche
```

Remplacer la dernière branche par celle de sa fiche. user.name ne constitue pas une preuve d'identité : la connexion GitHub et les contributions réelles restent personnelles. Ne pas copier ces valeurs entre membres.

## 5. Pour CHAQUE lot, dans cet ordre

1. Attendre que le lot précédent soit fusionné. Vérifier un répertoire de travail propre avec git status. S'il contient des changements, les examiner et les sauvegarder avant de poursuivre ; ne rien effacer ni cacher automatiquement.
2. Lire les commandes du lot : git fetch origin, git switch sa-branche, git merge --ff-only origin/sa-branche, puis git merge origin/integration. Aucun push forcé, reset destructeur ou rebase de l'historique partagé.
3. Copier uniquement le CONTENU de LOTS/NN/CODE à la racine du clone. Conserver les dossiers backend, frontend, docs, .github ; ne pas créer un dossier CODE dans GitHub.
4. Lire FICHIERS_ET_COMMITS.csv et sélectionner les chemins exacts des commandes. Relire git diff --cached --stat et git diff --cached. Aucun autre fichier ne doit être ajouté.
5. Exécuter les contrôles applicables au jalon, consigner leurs résultats réels, faire le commit proposé puis pousser sa branche. Ne jamais mettre de secret, .env privé, cache, base, vendor ou node_modules dans le commit.
6. Ouvrir une PR depuis sa branche vers integration. Indiquer numéro du lot, ticket principal et autres tickets concernés, provenance c65a206, fichiers, tests et limites. Utiliser le modèle des quatre sections ; ne pas clôturer un ticket sur un simple import.
7. Faire relire par une autre personne et fusionner avec Create a merge commit. Conserver la branche nominative pour son prochain lot. Copier le lien PR dans Jira ; pas de synchronisation automatique.

Ne pas coller toutes les commandes d'un membre d'un seul coup : ses lots peuvent être séparés par les lots d'un autre membre.

## 6. Jalons de dépendance et vérifications manuelles

Lots 1-2 : relire cadrage et modèles. Lots 3-7 : assembler le backend sans lancer l'application entre deux modules incomplets. Lot 9 terminé : backend complet avec tests ; dépendances à installer dans un environnement isolé de la production. Les classes des deux membres sont interdépendantes et ne sont pas des serveurs autonomes.
Lots 10-12 : assembler React ; App et les routes importent les pages des deux membres. Lot 14 terminé : frontend complet et tests. Lot 15 : activer la CI seulement lorsque les fichiers et la documentation sont réunis. Un contrôle vert de conventions ne valide pas les paiements ni les règles métier.

Dans backend : PHP compatible avec composer.lock (PHP 8.5 recommandé ici), Composer 2 et SQLite pour les tests. PostgreSQL distinct pour la recette d'intégration. Ne jamais pointer les tests sur Neon ou la base de production. Les tests de paiement utilisent les réponses simulées du prestataire.

```powershell
Set-Location backend
composer validate --strict
composer install --no-interaction --prefer-dist
# Sur un clone neuf seulement : vérifier que .env est absent avant la copie.
Copy-Item .env.example .env
php artisan key:generate
php vendor/bin/pint --test
php artisan test
```

Adapter le PATH au PHP compatible ; ne pas utiliser le PHP global XAMPP 8.0. Ne pas lancer de migration en production dans cette procédure. Le paiement doit rester désactivé et sans identifiants marchands dans l'environnement de recette.

```powershell
Set-Location frontend
npm ci
npm run lint
npm test
npm run build
```

Ces deux blocs se lancent chacun depuis la racine du nouveau clone en entrant dans le sous-dossier indiqué. À la racine, après le lot 15 : node .github/scripts/check-docs.mjs puis node --test .github/scripts/pr-policy.test.mjs. Ne pas utiliser npm start : le projet utilise le script dev de Vite.
Les contrôles de cette nouvelle reconstruction ne sont pas exécutés pendant la préparation des kits. Le workflow et ses tests adaptés doivent être exécutés et relus par Prudent avant publication du lot 15.

## 7. Si Git indique un conflit

Arrêter la copie et le commit. Lire git status ; demander au propriétaire du chemin dans le CSV de comparer avec integration. Ne pas prendre automatiquement ours/theirs et ne pas écraser une version récente avec le kit. Si l'opération de fusion n'est pas à conserver, git merge --abort annule cette fusion avant toute reprise. Une correction découverte après un lot fusionné passe dans un nouveau commit documenté ; elle ne réécrit pas les commits déjà déposés.
Chaque fichier n'appartient qu'à un lot pour cet import. Cette organisation réduit les conflits de contenu ; elle ne supprime pas les dépendances fonctionnelles ni les régressions. Toute nouvelle modification d'un fichier déjà importé exige une coordination avec son responsable.

## 8. Livraison vers main

Prudent vérifie les 15 lots, les résultats des tests, le démarrage et les parcours client/serveur/admin avec données fictives. Relecture croisée B/C puis PR integration vers main ; contrôles verts et vraie approbation avant merge commit. Les limites EasyPay doivent rester visibles : redirection et confirmation de paiement sont deux étapes différentes ; aucun SMS ni encaissement réel n'est prouvé par cette préparation.
Le site actuel reste lié à l'ancien dépôt. Le nouveau dépôt n'est relié ni à Vercel ni à Render et ne contient pas les variables privées. Une future bascule d'hébergement sera une opération distincte à préparer avec sauvegarde et retour arrière.

## 9. Périmètre et pièces de preuve

388 fichiers source vérifiés par empreinte Git. 376 fichiers à importer : 373 copies identiques et 3 adaptations des conventions/CI. Les exclusions et remplacements sont consignés dans PROVENANCE.json. Les fichiers inutilisés de l'ancien socle Blade/Vite et les instructions locales sont exclus ; les licences photo et les dépendances verrouillées sont conservées.
Les anciens documents importés conservent des liens, résultats et responsabilités historiques, parfois vers develop ou l'ancien dépôt. Ce guide et CONTRIBUTING.md font référence pour le nouveau flux. Ne pas présenter ces résultats historiques comme une nouvelle recette ni comme un statut Jira actuel.
Pour le professeur : ticket Jira, PR, auteur réel, diff, manifeste, résultat de tests, relecture et démonstration. La répartition des fichiers est une responsabilité de prise en main, pas la preuve que chaque ligne a été écrite auparavant par ce membre.
