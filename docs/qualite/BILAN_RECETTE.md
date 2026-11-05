# Dossier de conception / de recette

Groupe 4 · ULC-ICAM · X2 · 28 octobre 2026

## Résultats des contrôles du code

Les campagnes successives sont présentées séparément. Leurs nombres ne s’additionnent pas pour fabriquer un total de tests uniques.

| Campagne | Résultat consigné | Portée |
| --- | --- | --- |
| Backend complet, 26/10 | 116 tests / 635 assertions réussis sur SQLite au point 5. | Régression métier complète de cette version. |
| PostgreSQL, 26/10 | 116 tests / 635 assertions au point 4. | Même total sur PostgreSQL ; pas de nouvelle exécution PostgreSQL au point 5. |
| Backend ciblé, 27/10 | 9 tests / 79 assertions réussis. | Authentification et multi-restaurants ; pas une nouvelle suite complète. |
| Frontend, 28/10 | 97 tests dans 17 fichiers réussis. | Cinq scénarios supplémentaires : correspondance photo, respect des données serveur, isolation, identifiants et photo personnalisée. |
| Qualité frontend | Oxlint et construction TypeScript / Vite réussis. | Compilation du 28/10 : 64 modules ; asset index-DCKiVX0B.js. Les suites Laravel ne sont pas relancées pour les photographies. |
| Style backend | Pint réussi dans les campagnes documentées. | Pas de nouvelle exécution pour la production de ce PDF. |

### 8.4 Ce que ces chiffres ne mesurent pas

Le nombre d’assertions n’est pas un pourcentage de couverture du code et ne garantit pas l’absence de défaut. Aucun taux de couverture non mesuré n’est attribué au projet. Les tests marchands utilisent des réponses simulées et ne démontrent pas la réception d’un SMS, le débit d’un portefeuille ou la livraison d’un e-mail.

Les commandes manuelles de vérification sont conservées dans les guides : npm run lint, npm test et npm run build côté frontend ; composer validate --strict, php vendor/bin/pint --test et php artisan test côté backend, avec une base de test dédiée et le PHP compatible.

## Charge locale : cohérence des commandes

L’essai local a vérifié 50 comptes fictifs et 500 requêtes, sans erreur inattendue dans le scénario retenu.

| Palier | Scénario | Requêtes | p95 |
| --- | --- | --- | --- |
| 5 clients | Connexion, carte, devis, double confirmation, historique. | 45 | 778 ms |
| 15 clients | Même parcours. | 135 | 1 627 ms |
| 30 clients | Même parcours. | 270 | 3 704 ms |
| 50 sessions | Lecture interdite de la commande du voisin. | 50 | 2 077 ms |

### 8.5 Contrôle d’intégrité

Chaque devis de 2 500 FC a reçu deux confirmations concurrentes avec une même clé. L’audit a retrouvé 50 commandes, pour 125 000,00 CDF au total et aucun paiement. Les cinquante refus de lecture du voisin sont des résultats de sécurité attendus. La médiane globale est de 1 209 ms, le p95 de 3 388 ms et le maximum de 4 677 ms.

Le pic d’écriture du scénario est de 30 clients en parallèle. Le palier de 50 porte sur des lectures protégées, pas sur 50 confirmations simultanées. Le banc utilisait PostgreSQL de test, quatre processus PHP, des sessions séparées et des adresses de boucle locale distinctes. Une classe partageant une même adresse IP peut rencontrer les limites de fréquence plus tôt.

### 8.6 Dimensionnement visuel

Vingt-cinq observations locales ont examiné des largeurs de 320 à 1 366 px, ainsi que le panier en paysage. Aucun débordement horizontal n’a été détecté. La coupure du mot Menu a été corrigée ; les champs examinés utilisent une taille de 16 px et les actions concernées une cible de 44 px. Le zoom manuel reste possible.

**Environnement distinct**

Ces résultats ne sont pas ceux du site hébergé. Ils établissent une preuve locale de cohérence et d’affichage, pas une capacité garantie en production.

## Site hébergé : résultats et plafond observé

L’essai public a permis de vérifier les lectures et les sessions, mais a révélé une dégradation du temps de réponse à mesure que la concurrence augmentait.

| Visiteurs du palier | p95 des appels API | Observation |
| --- | --- | --- |
| 5 | 3 298 ms | Palier de lecture terminé. |
| 10 | 5 646 ms | Temps de réponse en hausse. |
| 20 | 9 579 ms | Attente nettement perceptible. |
| 50 | 30 007 ms | Huit dépassements de 30 secondes ; montée en charge arrêtée. |

Le total mesuré est de 172 requêtes : 139 réponses 200, 25 refus 401 attendus et huit expirations côté banc. Au dernier palier, le HTML a répondu cinquante fois, avec un p95 de 263 ms, alors que l’API a eu des délais. Les huit expirations ne sont pas des erreurs HTTP 500 constatées. Les mesures incluent le réseau du PC, Vercel, le relais, Laravel et Neon ; elles n’isolent pas encore la cause précise.

### 8.7 Sessions réellement séparées

Dix comptes fictifs dédiés ont été testés en deux groupes de cinq connexions : identité correcte, historique vide, sessions distinctes et déconnexion suivie d’un refus 401. Les 71 requêtes de cette campagne ont donné 61 succès et dix refus attendus. Le p95 des connexions est voisin de dix secondes ; le fonctionnement ne signifie donc pas une expérience instantanée.

Les dix comptes fictifs ont ensuite été suspendus : aucun actif, aucune commande et aucun rôle professionnel. Neuf observations du site public et du panier à différentes dimensions n’ont pas détecté de débordement horizontal. Aucun paiement, commande réelle ou e-mail n’a été déclenché pendant cette recette.

**Décision technique**

Avant un nouveau palier, mesurer séparément le relais, les requêtes SQL, les ressources et les temps de reprise du serveur. Le niveau de 50 visiteurs confortables n’est pas validé ; aucune capacité de 100 ou 1 000 utilisateurs n’est promise.

## Risques, budget et décisions restantes

Les travaux restants sont hiérarchisés pour terminer la validation sans confondre fonctionnement démontré et promesse de service.

| Ordre | Sujet | Solution et preuve attendue |
| --- | --- | --- |
| 1 | Paiement réel | Rapprocher les références ; vérifier contrat, compte marchand et environnement avec le prestataire ; essai plafonné autorisé et état confirmé. |
| 2 | Performance publique | Instrumenter API / SQL / relais, corriger le facteur dominant puis reprendre les paliers bornés. |
| 3 | Ajout de restaurant public | Reproduire dans la session administrateur du titulaire ; conserver message, réponse API et résultat réel. |
| 4 | Exploitation | Activer et observer planificateur, envoi réel d’e-mails, sauvegardes et restauration. |
| 5 | Règles et confidentialité | Approuver barèmes, livraison, conservation, mentions et procédure d’effacement. |
| 6 | Recette et intégration | Vrais appareils, navigateurs, relectures croisées et rapprochement des branches / tickets. |

### 9.2 Cadre budgétaire

Le prototype a été préparé avec les offres disponibles des services. Aucun tarif actuel, gratuité durable ou dépense engagée n’est déduit de ce constat. Le budget à faire approuver doit distinguer : domaine, hébergement API, base, stockage, envoi d’e-mails, frais marchands, sauvegardes, maintenance et temps d’équipe. Les coûts variables doivent être estimés selon le trafic et les transactions réellement attendus. Aucun taux de conversion n’est ajouté.

### 9.3 Mesures de maîtrise

Les données de test sont séparées des données applicatives, les secrets exclus des archives et les écritures financières vérifiées. Un changement de configuration ou de capacité doit être traçable. La décision d’acceptation se fait sur les preuves, avec la liste des réserves, plutôt que sur une déclaration générale « tout marche ».
