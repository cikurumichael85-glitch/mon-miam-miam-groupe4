# Recette du fonctionnement avec plusieurs restaurants

Mon Miam Miam · Groupe 4 · ULC-ICAM · RDC · CDF / FC
Date : 26 octobre 2026.

## 1. Conclusion et périmètre

La recette automatique du point 3 réussit : création d'un nouvel établissement, configuration indépendante, rattachement d'un compte existant, séparation des cartes et commandes, refus des accès croisés et révocation locale.

Cette capacité était déjà implémentée. Aucun défaut applicatif n'a été mis en évidence dans les scénarios vérifiés ; le travail de cette étape ajoute des tests d'intégration et d'interface ainsi que cette documentation. Il ne crée aucun restaurant, compte ou commande dans la base applicative réelle.

La prise en charge de plusieurs restaurants et la possibilité d'en ajouter un répondent aux demandes du groupe. La règle actuelle réservant la création à un administrateur de restaurant actif est un choix déjà implémenté dans le projet ; cette note ne l'attribue pas à une obligation du professeur. L'inscription publique ne donne pas de droits administrateur. Le premier administrateur doit disposer d'un accès attribué par le mécanisme d'administration prévu dans le projet.

## 2. Résultats exécutés

| Contrôle | Résultat obtenu |
| --- | --- |
| Suite backend SQLite en mémoire | 101 tests, 538 assertions, réussite |
| Suite backend PostgreSQL | 101 tests, 538 assertions, réussite dans mon_miam_miam_test |
| Suite frontend | 57 tests dans 11 fichiers, réussite |
| Qualité | Pint et Oxlint réussis |
| Compilation | TypeScript et Vite réussis, 60 modules |

Trois nouveaux tests d'intégration backend apportent 53 assertions. Cinq nouveaux tests du formulaire d'ajout et un test de panier multi-restaurants complètent la suite frontend. Les bases de test sont distinctes de la base applicative. Les appels HTTP externes non simulés sont interdits dans les tests ; aucun appel marchand réel n'a été effectué.

Ces preuves couvrent des comptes fictifs et des interfaces testées avec des réponses simulées. Elles ne constituent pas une recette visuelle complète avec les comptes personnels du groupe ni une certification exhaustive de sécurité.

## 3. Ce qui a été vérifié

| Cas | Attendu | Résultat |
| --- | --- | --- |
| Ajout par un administrateur actif | Création et rattachement administrateur au nouveau restaurant ; anciens rattachements conservés | Réussite API et interface simulée |
| Ajout par un invité, client, serveur ou gérant | Refus ; aucune création | Réussite API ; formulaire absent pour client, serveur et gérant |
| Erreur du serveur lors de la création | Message visible ; pas de sélection fictive d'un nouveau restaurant ni de rattachement ajouté dans l'interface | Réussite |
| Nouveau restaurant | Carte et horaires vides ; équipe limitée au créateur | Réussite |
| Commande avant configuration des horaires | Refus explicite | Réussite |
| Carte et horaires propres au nouvel établissement | Catégorie, plat et fuseau configurés indépendamment ; ancienne carte préservée | Réussite |
| Commande après configuration | Devis et commande au bon restaurant ; total calculé par le serveur | Réussite |
| Réutilisation d'un compte serveur existant | Rattachement local sans changement du nom ni du mot de passe du compte | Réussite |
| Doublon de rattachement | Refus sans écrasement du compte | Réussite |
| Consultation des commandes | Liste limitée au restaurant sélectionné et autorisé | Réussite |
| Révocation d'un accès | Refus immédiat dans cet établissement ; accès à l'autre établissement conservé | Réussite |
| Catégorie ou article étranger | Refus, même en utilisant l'URL d'un restaurant autorisé ; aucune modification de l'article étranger | Réussite |
| Commande ou devis étranger | Lecture, transition ou confirmation croisée refusée | Réussite |
| Panier multi-restaurants | Une seule origine ; choisir un autre restaurant ne mélange pas les articles du panier | Réussite |
| Paramètres marchands | Un restaurant non configuré n'hérite pas des identifiants d'un autre ; clés absentes des options publiques | Test existant CheckoutOptionsTest réexécuté, réussite sans appel marchand |

Les cartes des restaurants actifs restent intentionnellement publiques. La séparation concerne leur contenu, les modifications et les données privées ; elle n'interdit pas au client de consulter plusieurs cartes. Le client possède un compte personnel commun, tandis que les droits professionnels sont attachés à chaque restaurant.

## 4. Utilisation de l'application

### 4.1 Ajouter un restaurant

1. Se connecter avec un administrateur disposant d'un rattachement actif à un restaurant actif.
2. Dans « Mon restaurant », choisir « Ajouter un restaurant ».
3. Saisir le nom, l'adresse, le fuseau Kinshasa ou Lubumbashi et les options de livraison.
4. Cliquer une seule fois sur « Créer mon nouveau restaurant ».
5. Après réussite, l'interface sélectionne l'établissement créé et ouvre l'administration. Le créateur conserve ses anciens rattachements.

Le restaurant est créé actif et apparaît sur le site, avec une carte vide. Avant de recevoir des commandes, configurer les catégories, les plats disponibles et les horaires. Son équipe et ses paramètres marchands ne sont pas copiés automatiquement.

### 4.2 Rattacher un serveur ou un gérant

Dans l'espace d'administration, sélectionner le bon « Restaurant de travail » avant toute action. L'interface identifie actuellement les établissements par leur numéro dans ce sélecteur. Vérifier ce numéro, notamment lorsqu'un administrateur possède plusieurs restaurants.

Dans « Équipe », un administrateur ou un gérant peut rattacher un employé par son adresse e-mail. Si le compte existe, il garde son nom et son mot de passe. Pour un nouveau compte, un lien de récupération est prévu si l'envoi d'e-mails est configuré ; sa livraison réelle n'a pas été testée dans cette recette. Il est donc possible de commencer la démonstration avec un compte déjà inscrit.

Le rattachement d'un gérant est une action distincte réservée à l'administrateur. Révoquer un rattachement ne supprime pas le compte et ne révoque pas ses autres accès.

### 4.3 Commander dans plusieurs restaurants

Un client peut consulter les cartes de plusieurs établissements, mais une commande et son panier appartiennent à un seul restaurant. Pour commander dans un autre, terminer la première commande ou vider volontairement le panier, puis composer le nouveau panier. Aucun prix ni montant n'est converti.

## 5. Scénario de présentation au professeur

Préparer deux restaurants fictifs et des comptes autorisés dans un environnement de démonstration identifié. Les étapes suivantes sont un guide pour les titulaires ; elles n'ont pas été exécutées sur la base applicative pendant cette recette.

1. Montrer les deux restaurants côté client, puis leurs cartes distinctes.
2. Avec un administrateur, présenter « Ajouter un restaurant », puis créer un établissement uniquement si le groupe souhaite conserver cette donnée de démonstration.
3. Montrer sa carte et ses horaires initialement vides, puis configurer un plat et un créneau. Constater que la première carte n'a pas changé.
4. Sélectionner le nouvel établissement dans l'administration, rattacher un serveur déjà inscrit et vérifier qu'il peut accéder à ses commandes.
5. Montrer qu'un compte rattaché seulement au premier restaurant ne possède pas de droits professionnels sur le second.
6. Côté client, ajouter un plat du premier restaurant puis tenter un ajout du second : le panier doit conserver sa première origine.
7. Présenter les tests d'accès croisés réussis ; aucune manipulation de paiement n'est nécessaire.

Les tests automatiques couvrent les refus côté serveur, même si les boutons ne sont pas visibles. Une capture du sélecteur seule ne prouve pas l'isolation des données.

## 6. Preuves et commandes manuelles

Sources des nouveaux tests :

- [MultiRestaurantWorkflowTest.php](../tests/Feature/MultiRestaurantWorkflowTest.php) : création/configuration, équipe, commandes et accès croisés.
- [NewRestaurant.test.tsx](../../frontend/src/pages/NewRestaurant.test.tsx) : formulaire, droits, réussite et échec.
- [Explore.test.tsx](../../frontend/src/pages/Explore.test.tsx) : choix de restaurant et panier.

Les rapports JUnit privés sont conservés dans .local/tests-multi-restaurants-sqlite.xml, tests-multi-restaurants-postgresql.xml et tests-multi-restaurants-frontend.xml. Ils ne contiennent pas de preuves de paiement réel et ne sont pas à publier avec des données de connexion.

Pour rejouer uniquement les trois nouveaux tests backend, utiliser PowerShell :

```powershell
Set-Location -LiteralPath 'C:\Users\J\Desktop\projet miam miam\backend'
$env:PHP_INI_SCAN_DIR = 'C:\Users\J\Desktop\projet miam miam\.local\php-conf'
$env:EASYPAY_ENABLED = 'false'
$restaurantsPhp = 'C:\Users\J\Documents\Codex\2026-10-20\referenced-chatgpt-conversation-this-is-an\work\runtime\php-8.5.12\php.exe'
& $restaurantsPhp vendor/bin/phpunit --configuration phpunit.xml --filter MultiRestaurantWorkflowTest
& $restaurantsPhp vendor/bin/phpunit --configuration phpunit.pgsql.xml --filter MultiRestaurantWorkflowTest
```

Pour les tests frontend ciblés :

```powershell
Set-Location -LiteralPath 'C:\Users\J\Desktop\projet miam miam\frontend'
npm test -- src/pages/NewRestaurant.test.tsx src/pages/Explore.test.tsx
```

Les commandes de suites complètes, de qualité et de compilation et leurs prérequis sont décrits dans la [recette des trois rôles](RECETTE_TROIS_ROLES.md). Ne pas lancer deux suites en même temps sur la même base PostgreSQL. Les variables de vérification ci-dessus ne changent pas le fichier .env.

## 7. Suivi et limites

Jira doit conserver les critères et les preuves de recette dans les tâches existantes, sans annoncer une relecture humaine non réalisée. Git et GitHub porteront les futurs changements de version après autorisation de publication du groupe. Aucune écriture Jira, aucun commit, push ou PR pendant cette étape.

Les nouvelles preuves sont intégrées aux kits locaux cumulatifs. Les tests ne sont pas des contributions individuelles déjà déposées. La démonstration avec les comptes personnels reste à effectuer ; aucun e-mail réel d'invitation n'a été envoyé. La [limite de paiement EasyPay](../../output/Mon_Miam_Miam_Bilan_Paiement_26_10_2026.md) reste inchangée et son débogage n'a pas repris.
