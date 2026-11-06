# Backend Mon Miam Miam — livraison cumulative

**Point 5, contrôles finaux locaux du 26 octobre 2026 : 116 tests / 635 assertions SQLite, Pint, Composer validate --strict et check-platform-reqs réussis.** 78 tests frontend, lint et compilation réussis ; 12 contrôles HTTP conformes, accès privés invités refusés et CSRF vérifié. Code backend inchangé : preuve PostgreSQL du point 4 conservée sans nouvelle exécution. Lire le [rapport de recette finale locale](../output/Recette_Finale_26_10_2026/Compte_Rendu.md), qui distingue les vérifications obtenues et les validations restantes. Aucun paiement réel ni publication.

**Point 4, règles métier : 116 tests / 635 assertions réussis sur SQLite et PostgreSQL le 26 octobre 2026 ; Pint réussi.** Horaires, fidélité, parrainage et réclamations couverts ; route GET locale de lecture du programme ajoutée. Interface : 68 tests, lint et compilation réussis. Aucun barème réel activé ni paiement relancé. Lire la [recette métier et les décisions restantes](docs/RECETTE_REGLES_METIER.md).

**Point 3, plusieurs restaurants : recette automatique réussie le 26 octobre 2026.** 101 tests / 538 assertions SQLite et PostgreSQL, Pint réussi ; 57 tests frontend, lint et compilation réussis. Nouveaux scénarios : établissement indépendant, équipe et révocation locale, refus des articles/devis/commandes étrangers. Aucun restaurant réel créé ni paiement relancé. Voir la [recette multi-restaurants](docs/RECETTE_MULTI_RESTAURANTS.md).

**Recette des trois rôles du 26 octobre 2026 : réussie en automatique.** 98 tests / 485 assertions sur SQLite et PostgreSQL dédiés, Pint réussi ; 51 tests frontend, lint et compilation réussis. Parcours sur place/livraison et connexion/déconnexion de comptes fictifs vérifiés. La démonstration avec les trois comptes réels reste à effectuer par les titulaires. Lire le [compte rendu et scénario de présentation](docs/RECETTE_TROIS_ROLES.md). Aucun paiement réel ni changement des données applicatives existantes pendant cette recette.

**Bilan arrêté au 26 octobre 2026 : débogage EasyPay suspendu à la demande du groupe.** Guichet réel accessible à 14 000 CDF ; réception sur téléphone et confirmation financière non validées. La cause exacte reste inconnue. Lire la [note de bilan et limites du paiement](../output/Mon_Miam_Miam_Bilan_Paiement_26_10_2026.md), qui précise le périmètre des résultats historiques ci-dessous. Aucun contrôle ni changement de configuration effectué pour cette note.

**Point G validé : INIT est désormais lu comme attente sans exiger un reçu final.** Références de transaction, montants/devises et états finaux toujours contrôlés strictement. 94 tests / 384 assertions sur SQLite et PostgreSQL, Pint réussi. Vérification de la référence réelle existante réussie : pending, commande non réglée, aucune tentative créée. Aucun envoi de numéro ou demande de débit. Voir docs/API_ROLES_COMPTES.md. Les étapes E/F/H et la réception sur téléphone restent à traiter séparément.

**Dernière correction : paiement réel possible pour la même ancienne commande de test.** Migration `2026_10_24_000007_allow_payment_attempt_history` : historique conservé et une seule tentative active par commande. Sur un clic explicite en production, une tentative sandbox admissible est archivée et une nouvelle référence réelle est préparée, sans réutiliser la référence de test. Une tentative réelle incertaine ou une commande payée ne sont jamais relancées. Contrôles : **89 tests / 348 assertions réussis sur SQLite et PostgreSQL, 44 tests frontend, Pint, lint et compilation réussis**. Sauvegarde puis migration locale réalisées sur autorisation ; aucun paiement marchand déclenché. Dans une autre copie, appliquer cette migration avant de servir le code modifié. Voir docs/API_ROLES_COMPTES.md. Les résultats antérieurs ci-dessous sont historiques.

**Derniers contrôles autorisés : 85 tests / 320 assertions SQLite et Pint réussis le 24 octobre.** Frontend : lint, 42 tests / 8 fichiers et compilation réussis. L'initialisation et la redirection sont simulées pendant les tests ; aucun nouveau paiement ni notification réelle. Le parcours principal ouvre EasyPay ; l'API directe reste facultative. La configuration privée du poste reste production. Les mentions non exécuté ci-dessous concernent des étapes antérieures ; voir docs/API_ROLES_COMPTES.md pour l'état courant et les limites.

**Dernier changement : parcours Mobile Money direct écrit, non exécuté.** Saisie du numéro et API directe EasyPay, confirmation serveur stricte conservée, doublons bloqués ; tests supplémentaires préparés. Aucun nouveau paiement, test ou contrôle de code lancé pour ce changement. Les résultats chiffrés suivants sont historiques. Voir [le contrat actuel et les commandes manuelles](docs/API_ROLES_COMPTES.md).

**Configuration du poste après demande du 24 octobre : EasyPay en production (`v1`).** API locale contrôlée : paiement disponible et test_mode=false. Les anciennes références sandbox ne sont pas converties. Garde contre la réutilisation après changement de mode et deux tests ajoutés, non exécutés ; les résultats de tests ci-dessous sont antérieurs à cette garde. Lire [l'état et les commandes de vérification](docs/API_ROLES_COMPTES.md). Les kits excluent .env et conservent les valeurs prudentes par défaut ; une autre copie nécessite sa propre configuration marchande. Aucun nouveau paiement effectué pour cette bascule.

**Extension contrôlée le 24 octobre 2026 : 78 tests / 287 assertions sur SQLite en mémoire et Pint réussis.** Commande immédiate et parcours de paiement inclus ; frontend lint, 38 tests et compilation réussis. Lire [les preuves, limites et commandes](docs/API_ROLES_COMPTES.md). Paiement en sandbox ; aucun débit réel validé et aucune nouvelle vérification PostgreSQL. Les résultats du 23 octobre ci-dessous restent historiques.

Groupe 4 · ULC-ICAM · RDC · CDF / FC · 22 octobre 2026.

Le dossier `backend/` reste la source principale. Les kits de Ryan et Yedidia sont des copies sélectionnées de cette source ; ils ne sont pas deux applications indépendantes. Aucun commit, push, publication ou changement Jira n’a été effectué pendant cette livraison.

**Contrôle actuel du 23 octobre 2026 : 65 tests / 227 assertions réussis sur SQLite en mémoire et Pint réussi.** Deux tests d’administration ont été corrigés pour lire la version depuis l’API, comme le formulaire. Le compte marchand existe et sa configuration privée est active en sandbox pour le restaurant 1 : initialisation et page hébergée vérifiées sans débit ; confirmation de paiement réussie et production à valider. Lire [le complément](docs/COMPLEMENT_PARCOURS_ROLES_PAIEMENT.md). La validation PostgreSQL antérieure concernait 58 tests / 182 assertions ; elle n’a pas été répétée sur ce complément. Le port prévu reste 8010 ; vérifier le processus avant toute relance.

## 1. Fonctionnalités écrites

- Comptes, connexion par session Laravel, récupération, déconnexion, révocation des sessions après changement de mot de passe et contrôles de rôles.
- Restaurants, responsables de plateforme, personnel local, paramètres de livraison et horaires.
- Catégories, articles, disponibilité, plat du jour, archivage.
- Devis, prix calculés en CDF sans flottants, confirmation idempotente, historique et transitions de commande.
- Réclamations avec réponse soumise puis approuvée par une autre personne.
- Statistiques locales/globales, suivi des points et du parrainage, classement avec consentement.
- Fidélité à portée locale ou globale, lots de points, consommation, expiration, restitution après annulation et parrainage.
- Affiches promotionnelles, événements et mini-jeu de quiz à participation unique.
- Avis après service/livraison, consentements, export, instruction et anonymisation des données.
- EasyPay : initialisation, redirection, contrôle serveur, IPN, traitement des doublons et rapprochement d’une référence.

Voir [le contrat et les choix](docs/API_LOT_1.md). Le nom historique de ce document est conservé, mais son contenu est cumulatif.

## 2. Décisions techniques à relire

Les sessions natives Laravel assurent l’authentification de la SPA. Sanctum était proposé dans la conception ; il n’est pas installé, et aucun faux endpoint Sanctum n’est annoncé. Ce choix évite une nouvelle dépendance et conserve les cookies HTTP-only, la régénération de session et la protection CSRF. Une application mobile avec jetons nécessiterait un complément.

Aucun barème, tarif de livraison ou horaire n’est inventé dans les données applicatives. La direction configure les horaires avant les commandes ; l’absence d’horaires signifie fermé. Les programmes de points exigent une approbation explicite. La politique de conservation doit être validée avant anonymisation.

Les promotions sont des affiches informatives selon le CDC. Une réduction commerciale automatique en plus des points n’est pas implémentée. Le quiz est une proposition concrète de mini-jeu, à valider. Le temps réel repose sur des lectures périodiques de l’API, pas des WebSockets.

## 3. Logiciels nécessaires et préparation manuelle

L’éditeur permet de lire et modifier le code ; le lancement est assuré par PHP et la base de données. Aucun IDE particulier n’est imposé.

| Logiciel | Utilité | État constaté sur ce poste |
| --- | --- | --- |
| [Visual Studio Code](https://code.visualstudio.com/docs/setup/windows) | Éditeur conseillé ; ouvrir le dossier backend et son terminal intégré | Déjà installé |
| [PHP](https://www.php.net/manual/en/install.windows.php), version 8.5 utilisée ici | Exécute Laravel et les tests | PHP 8.5.12 isolé vérifié ; le PHP 8.0 de XAMPP est incompatible |
| [Composer 2](https://getcomposer.org/doc/00-intro.md) | Installe les dépendances verrouillées dans composer.lock | Déjà installé, dépendances présentes |
| [PostgreSQL](https://www.postgresql.org/download/windows/) | Serveur de base de données ; pgAdmin sert à l’administrer visuellement | PostgreSQL 18.3 en fonctionnement ; deux bases dédiées |
| Navigateur | Consulte l’API ; affichera l’interface une fois raccordée | Utiliser le navigateur habituel |
| Git | Versions du code et futures contributions ; pas nécessaire pour démarrer le serveur | Déjà installé, aucune publication effectuée |
| Node.js et npm | Nécessaires pour le frontend React, pas pour ce backend API | Déjà présents ; le frontend n’est pas dans ce dossier backend |

Laravel, PHPUnit et Pint sont des dépendances du projet : pas besoin de les installer globalement. Apache, MySQL et XAMPP ne sont pas nécessaires pour ce lancement local. Ne pas modifier les installations de cours.

### Démarrer seul sur ce poste déjà configuré

1. Ouvrir `C:\Users\J\Desktop\projet miam miam\backend` dans VS Code.
2. Ouvrir Terminal → Nouveau terminal, en PowerShell.
3. Vérifier que PostgreSQL est démarré. Conserver le fichier .env existant.
4. Si http://127.0.0.1:8010/up répond déjà, le backend tourne : ne pas démarrer un second serveur sur ce port.
5. Sinon, exécuter les lignes suivantes et garder le terminal ouvert :

```powershell
Set-Location 'C:\Users\J\Desktop\projet miam miam\backend'
$phpMiam = 'C:\Users\J\Documents\Codex\2026-10-20\referenced-chatgpt-conversation-this-is-an\work\runtime\php-8.5.12\php.exe'
$env:Path = "$(Split-Path -Parent $phpMiam);$env:Path"
$env:PHP_INI_SCAN_DIR = 'C:\Users\J\Desktop\projet miam miam\.local\php-conf'
& $phpMiam artisan serve --host=127.0.0.1 --port=8010
```

Ouvrir ensuite http://127.0.0.1:8010/api/v1/restaurants. Du JSON est normal : c’est une API. Utiliser Ctrl+C dans ce terminal pour arrêter uniquement le serveur que l’on vient de lancer. Ne pas arrêter un processus inconnu ou celui de l’autre copie du projet.

Les bases et dépendances sont déjà préparées sur ce poste ; il n’est pas nécessaire de refaire installation et migrations à chaque lancement. Sur le PC d’un autre membre, suivre la préparation complète ci-dessous avec son propre chemin PHP et ses propres paramètres .env.

### Certificats HTTPS nécessaires au paiement

Le 24 octobre, une connexion PHP à EasyPay échouait avec `cURL error 60` : aucun fichier d'autorités de certification n'était configuré. Correction locale : fichier CA provenant de https://curl.se/ca/cacert.pem, directives `curl.cainfo` et `openssl.cafile` dans `.local/php-conf/tls.ini`, puis redémarrage du seul backend du projet. La vérification TLS reste activée. Cette configuration locale et les diagnostics privés sont exclus des kits.

Sur un autre poste, télécharger ce fichier CA depuis sa source officielle, renseigner son chemin absolu dans les deux directives du fichier INI utilisé par PHP, puis redémarrer le serveur. Ne jamais contourner une erreur de certificat avec `verify=false`. Une lecture HTTPS réussie ne suffit pas à valider un règlement.

Après correction, la préparation sandbox de la dernière commande concernée a fourni une référence et son guichet hébergé répond HTTP 200 avec Mobile Money. Aucun règlement confirmé. Les anciennes tentatives `unknown` ne doivent pas être réinitialisées en bloc : conserver leurs traces et procéder au rapprochement avant une nouvelle tentative.

### Préparation complète et contrôles

Ouvrir PowerShell. Exécuter les lignes une par une et s’arrêter à la première erreur.

```powershell
Set-Location 'C:\Users\J\Desktop\projet miam miam\backend'
$phpMiam = 'C:\Users\J\Documents\Codex\2026-10-20\referenced-chatgpt-conversation-this-is-an\work\runtime\php-8.5.12\php.exe'
$composerMiam = 'C:\Users\J\composer\composer.phar'
$env:Path = "$(Split-Path -Parent $phpMiam);$env:Path"
$env:PHP_INI_SCAN_DIR = 'C:\Users\J\Desktop\projet miam miam\.local\php-conf'
& $phpMiam --version
& $phpMiam $composerMiam validate --strict
& $phpMiam $composerMiam check-platform-reqs
```

Ce poste possède déjà les dépendances du premier lot. Sur un autre poste ou dans l’archive complète, installer PHP compatible, Composer et PostgreSQL, puis exécuter `composer install` dans `backend/`. Conserver `composer.lock` ; ne pas faire de mise à jour générale.

**Ne pas écraser `.env` existant.** Dans une nouvelle copie seulement, copier `.env.example` vers `.env`, renseigner les paramètres privés, puis exécuter `php artisan key:generate`. Les extensions `pdo_pgsql` et `pdo_sqlite` sont nécessaires pour les deux bases de vérification.

Configurer `APP_URL=http://127.0.0.1:8010`, `FRONTEND_URL=http://127.0.0.1:5173`, `APP_LOCALE=fr`. Utiliser le même nom d’hôte côté frontend et backend ; ne pas mélanger `localhost` et `127.0.0.1`.

## 4. Contrôles manuels à réaliser avant migration applicative

```powershell
& $phpMiam artisan config:clear
& $phpMiam vendor/bin/pint --test
& $phpMiam vendor/bin/phpunit
& $phpMiam vendor/bin/phpunit --configuration phpunit.pgsql.xml
& $phpMiam artisan route:list --path=api/v1
```

Pint a corrigé les écarts de formatage et son dernier contrôle est réussi. Lors d’une modification future, s’il signale des écarts, exécuter `& $phpMiam vendor/bin/pint`, relire les modifications, puis refaire les contrôles. Les copies des kits devront alors être actualisées avant intégration.

SQLite utilise une base en mémoire ; PostgreSQL utilise exclusivement `mon_miam_miam_test` sur ce poste. **Les tests réinitialisent la base de test.** Un garde-fou refuse les autres noms et hôtes dans `tests/TestCase.php`. Ce garde-fou ne remplace pas la vérification de la configuration.

Les nouveaux tests utilisent des réponses HTTP simulées d’EasyPay et refusent les appels réseau non simulés. Ils ne déclenchent aucun paiement réel. Le test de deux devis vérifie un solde devenu insuffisant ; une recette de concurrence avec deux connexions PostgreSQL distinctes reste nécessaire.

## 5. Migration et administration initiale

Vérifier `DB_DATABASE=mon_miam_miam` et sauvegarder les données à conserver. Les nouvelles migrations sont 000004, 000005 et 000006 ; les migrations du premier lot n’ont pas été réécrites.

```powershell
& $phpMiam artisan migrate:status
& $phpMiam artisan migrate
```

Ne pas utiliser `migrate:fresh` sur la base applicative. Les restaurants fictifs déjà créés restent présents ; aucun compte ou mot de passe de démonstration n’est ajouté.

Créer un vrai compte via l’inscription, puis attribuer le rôle initial depuis le terminal du serveur :

```powershell
& $phpMiam artisan app:platform-access 'adresse-du-responsable@example.com'
```

Remplacer l’adresse d’exemple. Une confirmation du compte ciblé est demandée. Depuis les API de plateforme, attribuer ensuite les gérants ; depuis les API de direction, renseigner horaires, frais, catégories et articles. Aucun rôle privilégié ne peut être obtenu à l’inscription.

## 6. EasyPay et récupération de compte

L’intégration est fondée sur la [documentation officielle EasyPay](https://developper.e-com-easypay.com/), consultée le 22/10/2026 dans le navigateur. Le document affiche une mise à jour du 12/10/2021 : réaliser la recette marchand avant toute production.

Dans `.env`, après obtention des identifiants sandbox, activer `EASYPAY_ENABLED=true`, garder `EASYPAY_MODE=sandbox` et renseigner `EASYPAY_MERCHANTS`. Exemple de structure avec valeurs fictives :

```dotenv
EASYPAY_MERCHANTS='{"1":{"cid":"IDENTIFIANT_TEST","publishable_key":"CLE_TEST"}}'
```

La clé correspond à l’identifiant du restaurant dans votre base. Chaque restaurant utilise son propre compte marchand. Aucun encaissement commun suivi de reversements n’est présumé. Ne jamais publier cette configuration.

Déclarer chez EasyPay l’IPN HTTPS `/api/v1/payments/easypay/ipn`. Une adresse locale 127.0.0.1 n’est pas accessible au prestataire : utiliser un environnement de test HTTPS autorisé. Le frontend doit encore réaliser `/payment-return` et appeler la vérification serveur ; l’URL de retour ne prouve pas le paiement.

Après changement de configuration, exécuter `php artisan config:clear`. Ne passer à `v1` qu’après recette, validation du marchand et HTTPS. Aucun compte marchand, aucune clé et aucun paiement n’ont été créés ou utilisés pendant ce travail.

Configurer également SMTP : le mode `MAIL_MAILER=log` écrit les courriels localement, il ne les envoie pas. Ne pas partager les journaux contenant des liens de récupération.

## 7. Lancement et tâches planifiées, uniquement manuels

Le serveur existant de ce dossier, sur le port 8010, a répondu aux vérifications du 23/10/2026. Il a été réutilisé sans démarrer un doublon. Contrôler son état et les chemins des processus avant un prochain lancement ou arrêt. Le serveur de l’autre copie au port 8000 n’a pas été interrompu.

Si le port 8010 est libre :

```powershell
& $phpMiam artisan serve --host=127.0.0.1 --port=8010
```

Pour traiter les expirations ponctuellement : `php artisan app:expire-loyalty`. En production, le planificateur doit exécuter `php artisan schedule:run` chaque minute. Les soldes sont également actualisés à la lecture et avant consommation.

Le planificateur vérifie aussi au maximum 25 paiements en attente toutes les cinq minutes, seulement si EasyPay est activé. La commande `php artisan app:reconcile-payments` vérifie des paiements existants ; elle n’initie aucun encaissement. Les états unknown sans référence exigent un rapprochement marchand manuel.

## 8. Ce qui sépare cette source d’une livraison validée

1. Syntaxe, formatage et 58 tests sur deux moteurs ont été vérifiés localement ; conserver les rapports et relancer après toute modification.
2. Faire la recette PostgreSQL des conflits simultanés, puis connecter le frontend React à cette API.
3. Valider avec le groupe les politiques proposées dans le contrat, configurer horaires, barèmes, SMTP et conservation.
4. Réaliser la recette EasyPay sandbox : succès, refus, annulation, délai réseau, notifications répétées et retour navigateur abandonné.
5. Préparer le déploiement HTTPS, les sauvegardes, le planificateur et les preuves pour le professeur.

L’API officielle consultée ne documente pas les remboursements ni un montant/devise dans sa réponse de vérification. Aucun remboursement automatique n’est inventé. Une commande payée ne peut pas être annulée via ce flux ; un rapprochement et une procédure marchand sont nécessaires. La commande de rapprochement applicative récupère une référence connue, elle ne relance pas un paiement.

L’anonymisation porte sur les données applicatives ciblées ; sauvegardes, journaux, champs libres historiques et données du prestataire nécessitent une procédure de conservation et une revue séparées. Cette implémentation ne constitue pas une certification juridique.

## 9. Outils et preuves

Jira : présenter les critères et résultats réels par tâche, sans les déclarer terminés avant recette.
Git/GitHub : aucune publication maintenant. Les futures contributions passeront par une branche de tâche et une PR vers `develop`, avec vraie relecture. Un kit ne constitue pas une contribution déjà réalisée.
Figma : conserve les parcours ; le frontend doit maintenant les relier aux API.

Lire [le rapport historique et l’état des contrôles](docs/VERIFICATION_LOT_1.md).
