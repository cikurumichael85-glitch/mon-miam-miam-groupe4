# Recette des espaces administrateur, serveur et client

Mon Miam Miam · Groupe 4 · ULC-ICAM · RDC · CDF / FC
Date : 26 octobre 2026.

## 1. Résultat et portée

La recette automatique des trois rôles réussit. Le parcours administrateur → client → serveur → historique client a été vérifié via les API de test, pour une commande sur place et une livraison. Les connexions et déconnexions des trois rôles ont été testées avec des comptes fictifs, sans modifier les comptes applicatifs existants.

Une correction frontend a été apportée : le détail privé affiche désormais l'adresse, le téléphone et les indications de livraison déjà enregistrés. Sur place, il distingue la commande immédiate de l'arrivée planifiée, dont l'heure est affichée dans le fuseau du navigateur explicitement indiqué. Le serveur disposait auparavant du contenu et du total, sans afficher ces coordonnées dans son écran.

Le paiement réel reste hors recette, conformément à la décision du groupe. Une commande servie peut rester non réglée : le statut de préparation et le statut financier sont distincts. Aucune commande n'est marquée payée par le passage à « Servie ».

## 2. Résultats obtenus

| Contrôle exécuté | Résultat | Périmètre |
| --- | --- | --- |
| Backend SQLite en mémoire | 98 tests, 485 assertions, réussite | Suite complète, données fictives |
| Backend PostgreSQL | 98 tests, 485 assertions, réussite | Suite complète dans mon_miam_miam_test uniquement |
| Tests d'interface | 51 tests dans 10 fichiers, réussite | Rendu React et réponses HTTP simulées |
| Qualité backend | Pint réussi | Style PHP |
| Qualité frontend | Oxlint réussi | Contrôles configurés, sans règle désactivée |
| Compilation frontend | TypeScript et Vite réussis, 60 modules | Construction de l'application |
| Services locaux | /up, accueil et restaurants via proxy : HTTP 200 | Vérifications publiques en lecture seule |

Les appels externes non simulés sont interdits dans les tests backend. Aucune initialisation EasyPay, soumission de numéro, demande de débit ou vérification marchande réelle n'a été effectuée pendant cette recette. La configuration privée de paiement n'a pas été modifiée.

La première commande PostgreSQL via artisan a émis un avertissement de configuration dupliquée et terminé avec un code non nul malgré des tests affichés réussis. Le résultat retenu dans ce document provient de l'exécution directe de PHPUnit avec phpunit.pgsql.xml, terminée avec succès. Aucun échec n'a été masqué.

## 3. Cas de recette et preuves

| Identifiant | Scénario | Résultat et preuve automatique |
| --- | --- | --- |
| R01 | Connexion puis déconnexion administrateur, serveur et client | Réussite ; identité et rattachements conservés, accès privé refusé après déconnexion ; ThreeRoleWorkflowTest |
| R02 | Administrateur : créer un plat au prix saisi | Réussite API et formulaire simulé ; ThreeRoleWorkflowTest et StaffCatalogue.test.tsx |
| R03 | Administrateur : archiver un article sans effacer l'historique | Réussite ; article retiré de la carte publique, ligne historique conservée ; mêmes tests |
| R04 | Administrateur : changer le nom du restaurant | Réussite API, version contrôlée et autre restaurant préservé ; RestaurantAdministrationTest |
| R05 | Client : panier, devis et confirmation | Réussite ; calcul serveur, confirmation idempotente et panier vidé après confirmation ; Checkout.test.tsx et ThreeRoleWorkflowTest |
| R06 | Serveur : retrouver la commande du client | Réussite ; même identifiant, lignes et total ; ThreeRoleWorkflowTest |
| R07 | Serveur : préparer et servir sur place | Réussite ; pending → accepted → preparing → ready → completed ; ThreeRoleWorkflowTest |
| R08 | Serveur : préparer et livrer | Réussite ; ready → out_for_delivery → completed, coordonnées présentes ; ThreeRoleWorkflowTest et StaffOrders.test.tsx |
| R09 | Client : consulter le suivi après service | Réussite ; statut final, événements et prix conservés ; ThreeRoleWorkflowTest |
| R10 | Refuser les actions administrateur au client et au serveur | Réussite ; création, modification, archive et paramètres refusés côté API ; ThreeRoleWorkflowTest |
| R11 | Limiter les actions de l'écran serveur | Réussite ; pas d'édition de prix ni d'archive, disponibilité autorisée, refus/annulation réservés à la gestion ; tests React |
| R12 | Éviter d'écraser une commande modifiée par quelqu'un d'autre | Réussite ; mauvaise version refusée côté API et erreur visible dans l'interface ; OrderWorkflowTest et StaffOrders.test.tsx |

Ces scénarios sont des preuves automatiques ; ils ne constituent pas une démonstration navigateur effectuée avec les trois comptes réels du groupe. Le gérant conserve son rôle distinct : il peut gérer la carte et le service, mais les paramètres du restaurant sont réservés à l'administrateur.

## 4. État navigateur et limite restante

Le frontend local a été démarré sur http://127.0.0.1:5173/ après constat de l'absence d'écoute sur ce port. Le backend existant sur 8010 a été identifié comme celui de ce dossier et réutilisé. Le formulaire de connexion a été observé dans le navigateur intégré, sans saisie de mot de passe. Aucun serveur inconnu n'a été arrêté.

Les trois sessions réelles n'ont pas été ouvertes pendant cette étape. La présentation visuelle complète avec les comptes du groupe reste à effectuer par leurs titulaires, en suivant le scénario ci-dessous. Les comptes et commandes existants n'ont pas été modifiés par la recette automatique.

## 5. Démonstration manuelle, sans paiement réel

Utiliser des comptes autorisés et un restaurant de démonstration identifié. Saisir les mots de passe directement dans le navigateur. Ouvrir des profils de navigateur distincts pour maintenir les trois sessions, ou se déconnecter entre chaque rôle. Plusieurs onglets du même profil partagent la session.

1. **Administrateur** : se connecter, ouvrir « Mon restaurant », choisir le bon restaurant, puis « Carte ». Ajouter un article fictif reconnaissable. Vérifier son prix et sa présence côté client. Pour le nom, utiliser « Mon restaurant & horaires » ; noter la valeur initiale avant toute modification de démonstration.
2. **Client** : se connecter, choisir ce même restaurant, ajouter l'article au panier et vérifier la quantité. Choisir le service, demander le prix puis confirmer une seule fois. Noter le numéro de commande. S'arrêter avant les boutons EasyPay.
3. **Serveur** : se connecter, ouvrir « Mon service » et choisir le même restaurant. Actualiser si nécessaire ; la liste se rafraîchit aussi toutes les 15 secondes lorsque l'onglet est visible. Ouvrir le numéro noté, vérifier les articles et le total, puis confirmer chaque étape de préparation. Pour la livraison, vérifier les coordonnées avant « En livraison », puis confirmer la remise effective.
4. **Client** : revenir dans « Mes commandes », actualiser et ouvrir le même numéro. Vérifier le statut final et les événements. Le paiement reste non réglé ; ne pas le présenter comme payé.
5. **Administrateur** : archiver l'article fictif après la démonstration. Vérifier qu'il disparaît de la carte tout en restant dans la commande historique. Rétablir les paramètres de démonstration si nécessaire, sous le contrôle du titulaire.

Le scénario manuel n'a pas été exécuté sur la base applicative pendant ce travail. Les créations et changements décrits ici sont des actions futures volontaires, pas des opérations déjà effectuées.

## 6. Rejouer les contrôles dans PowerShell

Prérequis déjà présents lors de cette recette : Node/npm, dépendances frontend installées, PHP 8.5 isolé avec extensions, dépendances Composer et PostgreSQL avec base de test dédiée. Ne pas utiliser le PHP XAMPP 8.0 incompatible et ne pas remplacer les configurations privées.

Depuis le dossier frontend :

```powershell
Set-Location -LiteralPath 'C:\Users\J\Desktop\projet miam miam\frontend'
npm run lint
npm test
npm run build
```

Depuis le dossier backend, utiliser le PHP compatible constaté :

```powershell
Set-Location -LiteralPath 'C:\Users\J\Desktop\projet miam miam\backend'
$env:PHP_INI_SCAN_DIR = 'C:\Users\J\Desktop\projet miam miam\.local\php-conf'
$env:EASYPAY_ENABLED = 'false'
$rolesPhp = 'C:\Users\J\Documents\Codex\2026-10-20\referenced-chatgpt-conversation-this-is-an\work\runtime\php-8.5.12\php.exe'
& $rolesPhp vendor/bin/phpunit --configuration phpunit.xml
& $rolesPhp vendor/bin/phpunit --configuration phpunit.pgsql.xml
& $rolesPhp vendor/bin/pint --test
```

La variable EASYPAY_ENABLED ci-dessus concerne seulement le terminal de vérification ; elle ne modifie pas le fichier .env ni un serveur déjà lancé. Les configurations de test et le garde-fou de Tests/TestCase.php refusent la base applicative. Ne pas lancer plusieurs suites simultanément sur la même base de test PostgreSQL.

Pour démarrer ultérieurement le frontend, vérifier d'abord que 5173 est libre :

```powershell
Set-Location -LiteralPath 'C:\Users\J\Desktop\projet miam miam\frontend'
npm run dev -- --host 127.0.0.1 --port 5173 --strictPort
```

Ne pas lancer un second serveur si le site répond déjà. Pour le backend, suivre les instructions de démarrage du README existant.

## 7. Outils et livrables

Jira porte le suivi et les critères de recette ; la note peut servir de preuve à reporter ultérieurement dans les tâches existantes, après validation du groupe. Git conserve les versions et GitHub les futures PR. Aucun statut Jira, commit, push ou PR n'a été créé pendant cette étape.

Les responsabilités de prise en main restent celles du projet : interface au binôme Michael/Assek ; tests d'intégration API au binôme Ryan/Yedidia, avec nouveau scénario attribué à Ryan et relecture croisée attendue. Ces attributions ne prouvent pas une contribution Git ou une relecture déjà réalisée.

Le [bilan du paiement](../../output/Mon_Miam_Miam_Bilan_Paiement_26_10_2026.md) reste applicable. La recette des rôles n'annule pas la limite du paiement réel.
