# État actuel — contrôles du paiement autorisés et réussis

**Point G : 94 tests / 384 assertions réussis sur SQLite et PostgreSQL ; Pint réussi.** Cinq régressions supplémentaires couvrent la lecture non financière de INIT et le maintien des contrôles stricts pour les références, le montant et la devise. Vérification réelle unique du paiement actif existant : réussite en pending, verified_at enregistré, commande unrecorded, même nombre de tentatives. Aucun appel d'initialisation, envoi de numéro, nouveau paiement ou migration. Frontend inchangé, preuves antérieures 44 tests non répétées. Point E non démarré. Rapports privés tests-point-g-*.xml et point-g-existing-payment-result.json ; les résultats suivants sont antérieurs.

**Dernier lot : 89 tests / 348 assertions réussis sur SQLite puis PostgreSQL, Pint réussi ; frontend 44 tests, lint et build réussis.** Migration 000007 de suivi des tentatives testée sur les deux bases et appliquée localement après sauvegarde autorisée de 116 302 octets dont le catalogue a été relu par pg_restore. Six commandes et cinq paiements conservés, aucune tentative archivée à ce stade puisque l'assistant n'a pas cliqué pour initialiser un paiement. Index unique des tentatives actives présent. Aucun appel marchand ou débit ; réception opérateur non validée. Les résultats suivants sont antérieurs à cette correction.

Le 24 octobre, après autorisation explicite d'exécuter les contrôles sans paiement réel : 85 tests / 320 assertions réussis sur SQLite en mémoire ; Pint réussi. Paiements externes désactivés et identifiants marchands neutralisés pour le processus de tests, configuration privée réelle inchangée. Rapport privé .local/tests-retour-easypay-backend.xml. Frontend associé : lint, 42 tests / 8 fichiers et compilation réussis. Ni migration applicative ni paiement déclenché. Cette vérification simulée couvre les deux contrats d'initialisation et ne prouve pas la livraison d'une demande opérateur ou un règlement réel. Aucune nouvelle vérification PostgreSQL. Les sections suivantes sont historiques.

Le parcours direct, les tests correspondants et la garde contre le mélange des modes sont plus récents que les résultats ci-dessous. Aucun nouveau test, Pint, migration, serveur ou appel marchand exécuté pour ce changement. Les anciennes preuves ne valident pas la notification directe. Voir API_ROLES_COMPTES.md pour les commandes manuelles.

# Contrôle actuel du 24 octobre 2026

**78 tests / 287 assertions réussis sur SQLite en mémoire, Pint réussi.** Rapport `.local/tests-commande-immediate-sqlite.xml`. Inclut comptes, création de restaurants et commande immédiate. Frontend : lint, 38 tests et compilation réussis. Aucun appel HTTP marchand non simulé dans les tests, aucun débit ou migration applicative. PostgreSQL n'a pas été réexécuté ; les anciennes preuves plus bas concernent les versions antérieures. Détails courants dans `API_ROLES_COMPTES.md`.

**Extension du 24 octobre non exécutée :** les tests ci-dessous ne valident pas les nouveaux espaces, le changement des identifiants ou l'ajout de restaurants. Voir `API_ROLES_COMPTES.md` pour les nouveaux tests et commandes.

Après nouvelle autorisation : 65 tests / 227 assertions réussis sur SQLite en mémoire et Pint réussi. Les résultats PostgreSQL et de syntaxe de tous les fichiers cités plus bas restent historiques. Aucune nouvelle recette marchande ou navigateur complète. Voir [COMPLEMENT_PARCOURS_ROLES_PAIEMENT.md](COMPLEMENT_PARCOURS_ROLES_PAIEMENT.md).

# Vérification du backend cumulatif et historique du premier lot

Groupe 4 · ULC-ICAM · 22 octobre 2026.

## Exécution cumulative du 23 octobre 2026 — à lire en premier

Michael a explicitement autorisé les contrôles, corrections et lancement. Les instructions reproductibles et les logiciels nécessaires figurent dans [le README](../README.md).

| Contrôle | Résultat réellement obtenu |
| --- | --- |
| PHP | 8.5.12, extensions PostgreSQL et SQLite disponibles |
| Composer | validate --strict et check-platform-reqs réussis ; aucune mise à jour des dépendances |
| Syntaxe | 94 fichiers PHP sans erreur |
| Formatage | Écarts corrigés avec Pint, puis contrôle réussi |
| SQLite en mémoire | 58 tests réussis, 182 assertions |
| PostgreSQL 18.3 | 58 tests réussis, 182 assertions sur mon_miam_miam_test |
| Base applicative | Sauvegarde locale créée avant migration ; trois nouvelles migrations appliquées, neuf migrations au total |
| Serveur | Serveur existant du dossier backend au port 8010 réutilisé, aucun doublon lancé |
| HTTP public | /up, restaurants, menu, campagnes, avis et csrf-cookie : 200 |
| HTTP privé sans connexion | /api/v1/me/orders : 401 attendu |
| Connexion sans CSRF | 419 attendu |
| Connexion avec CSRF mais faux identifiants | 422 attendu |

Deux défauts ont été corrigés : une méthode auxiliaire de test nommée result entrait en conflit avec PHPUnit ; elle s’appelle maintenant providerResponse. La connexion PostgreSQL utilisait le fuseau du serveur alors que les instants Laravel sont en UTC, provoquant des devis prématurément expirés. La connexion applicative impose maintenant UTC ; le fuseau global de PostgreSQL n’a pas été modifié. Un test vérifie la conservation de l’instant d’expiration après stockage.

Les rapports JUnit SQLite/PostgreSQL, le relevé des routes et les neuf vérifications HTTP sont conservés dans le dossier local .local, exclu de Git et des ZIP. La sauvegarde avant migration y reste privée également. Aucun compte réel ou compte de démonstration n’a été créé dans la base applicative pendant cette vérification ; les données des tests sont isolées.

Les réponses EasyPay sont simulées dans les tests et les appels réseau non simulés y sont refusés. Aucun encaissement, appel marchand, courriel réel ou remboursement n’a été effectué. La recette sandbox avec identifiants marchands, la concurrence à deux connexions et le raccordement React restent à réaliser. Ces résultats sont des contrôles locaux, pas une CI distante ni une relecture humaine.

Les copies des kits sont actualisées après corrections. Leur comparaison d’empreintes prouve seulement leur identité avec la source principale ; les résultats applicatifs sont ceux du tableau ci-dessus. Aucun ticket Jira terminé, aucune approbation ou publication GitHub n’est revendiqué.

## Historique du premier lot uniquement

Exécution autorisée explicitement par Michael après la préparation du code.

Après ces vérifications, des commentaires pédagogiques et des dossiers individuels ont été ajoutés. Les résultats ci-dessous sont ceux de l’exécution précédente ; les tests et le formateur n’ont pas été relancés pour cette livraison commentée. Aucun changement de comportement n’est intentionnel dans cette préparation.

| Contrôle | Résultat constaté |
| --- | --- |
| PHP | 8.5.12 ; exigences Composer satisfaites |
| Dépendances | 108 paquets installés selon composer.lock, sans mise à jour des versions |
| Syntaxe PHP | 35 fichiers vérifiés, aucune erreur |
| Laravel Pint | Contrôle réussi, aucune correction nécessaire |
| Tests SQLite en mémoire | 31 tests réussis, 90 assertions |
| Tests PostgreSQL | 31 tests réussis, 90 assertions dans une base dédiée |
| Migrations applicatives | Six migrations appliquées, dont les trois migrations de ce lot |
| Démonstration | Deux restaurants fictifs, aucun compte de démonstration |
| Santé HTTP | GET /up : 200 |
| Liste des restaurants | GET /api/v1/restaurants : 200, deux restaurants, total = 2 |
| Pagination invalide | per_page=101 : 422 et code VALIDATION_ERROR |

## Environnement local

Base applicative : `mon_miam_miam`.
Base réservée aux tests : `mon_miam_miam_test`.
Rôle du projet : `mon_miam_miam`, propriétaire de ces bases, sans pouvoirs de superutilisateur, création de rôles ou création de bases.
Les paramètres privés et la clé applicative sont conservés dans `.env`, exclu de Git.
SQLite est activé uniquement pour les commandes de vérification, par une configuration PHP locale exclue de Git.

Serveur de ce dossier : `http://127.0.0.1:8010`.
L’ancien serveur sur le port 8000 n’a pas été interrompu.
Les rapports JUnit et la réponse HTTP de démonstration sont conservés dans `.local/`, dossier exclu de Git.

## Reproduire les tests

Dans `backend/`, avec PHP 8.5 et les extensions nécessaires :

```powershell
php artisan config:clear
php vendor/bin/pint --test
php artisan test
php vendor/bin/phpunit --configuration phpunit.pgsql.xml
```

Utiliser directement PHPUnit pour la configuration PostgreSQL : le passage de `--configuration` à `artisan test` a produit un avertissement de double option sur cette version. L’exécution directe a ensuite réussi sans cet avertissement.
La configuration PostgreSQL impose `mon_miam_miam_test` ; les tests réinitialisent cette base réservée.

## Portée

Les tests couvrent la liste publique, la pagination, les rôles locaux, les rattachements et leur révocation, les contraintes et le seeder.
Deux tests proviennent du socle initial ; 29 cas concernent le nouveau lot.
L’authentification, les menus, les commandes, les API privées et les autorisations sur leurs ressources restent à développer.
Aucune publication GitHub, validation humaine ou clôture Jira n’est déclarée pour ce lot.
