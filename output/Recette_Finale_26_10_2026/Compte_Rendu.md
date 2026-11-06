# Compte rendu des contrôles finaux locaux

Mon Miam Miam · Groupe 4 · ULC-ICAM · RDC · CDF / FC
Date : 26 octobre 2026 · Point 5.

## 1. Résultat

Les contrôles finaux locaux réussissent sur le périmètre observé : pages publiques sur plusieurs largeurs, navigation mobile, panier visiteur, fenêtres de détail, erreurs HTTP et accès réservés. Trois défauts ont été corrigés : débordement de l'en-tête sur petit écran, noms accessibles absents de certaines icônes et instabilité de l'état partagé lors des mises à jour du code. La gestion des réponses API illisibles ou sans message a également été renforcée.

Aucun compte, restaurant, commande, horaire ou barème réel n'a été créé ou modifié. Un article a été ajouté puis retiré du panier en mémoire pour vérifier son affichage ; aucune confirmation de commande ni demande de paiement n'a été effectuée. Les serveurs déjà présents sur 5173 et 8010 ont été utilisés ; aucun lancement ou arrêt supplémentaire.

## 2. Tests et qualité

| Contrôle | Résultat retenu | Portée |
| --- | --- | --- |
| Tests frontend | 78 tests dans 16 fichiers, réussite | Suite complète après les dernières corrections |
| Tests backend SQLite | 116 tests / 635 assertions, réussite | Suite complète en mémoire, données fictives |
| Tests PostgreSQL | Preuve du point 4 conservée : 116 tests / 635 assertions | Pas de nouvelle exécution pendant le point 5, code backend inchangé |
| Oxlint | Réussite | Aucune règle désactivée |
| TypeScript et Vite | Compilation réussie, 62 modules | Construction finale frontend |
| Laravel Pint | Réussite | Contrôle backend |
| Composer | validate --strict et check-platform-reqs réussis | Métadonnées et compatibilité PHP 8.5.12 ; aucune installation |
| HTTP local | 12 contrôles conformes sur 12 | Services publics, refus d'accès invités et CSRF |

Les tests bloquent les appels externes non simulés. Aucun contrôle n'a contacté EasyPay pour initialiser ou vérifier une transaction réelle. Les tests locaux ne constituent pas une nouvelle CI GitHub et aucune publication n'a été effectuée.

## 3. Affichage vérifié

Les formats ci-dessous sont des tailles de fenêtre du navigateur intégré, et non des essais sur quatre appareils physiques. La largeur utile exclut la barre de défilement verticale du navigateur.

| Format demandé | Largeur utile | Largeur du contenu après correction | Observation |
| --- | --- | --- | --- |
| 320 × 740 | 305 px | 305 px | Accueil et connexion sans défilement horizontal ; actions de l'en-tête réparties sur deux lignes |
| 375 × 812 | 360 px | 360 px | Carte à deux colonnes, fiche et panier lisibles ; fenêtres sans débordement horizontal |
| 768 × 1024 | 753 px | 753 px | Carte à deux colonnes |
| 1440 × 900 | 1425 px | 1425 px | Accueil large et carte à quatre colonnes |

À 320 pixels, le contenu dépassait initialement la largeur utile : 318 pixels pour 305 disponibles. Le retour à la ligne des actions sur les écrans les plus étroits corrige ce débordement. Les icônes « Se connecter » et « Mon panier » possèdent désormais un nom accessible même lorsque le texte visible est masqué. Le bouton Menu identifie aussi la navigation qu'il commande.

Parcours observés dans le navigateur :

- Ouverture et fermeture du menu mobile, puis navigation vers la carte.
- Chargement des images visibles contrôlées sans image cassée détectée ; ce constat ne prétend pas vérifier tous les fichiers ou tous les chargements différés.
- Ouverture d'une fiche de plat : focus sur « Fermer », fermeture par Échap et retour du focus au bouton d'origine.
- Ajout d'un riz aux haricots à 2 500 FC au panier, affichage du panier, demande de connexion pour poursuivre, puis retrait de l'article de recette. Aucun devis ni commande créé.
- Affichage du formulaire de connexion sans saisie d'adresse ni de mot de passe.
- Visiteur naviguant vers administration, service, commandes, fidélité et ajout de restaurant : formulaire de connexion affiché sur chaque route.

La taille de fenêtre temporaire a été réinitialisée après les contrôles.

## 4. Messages d'erreur et stabilité

Le client HTTP présente en français les messages techniques usuels de connexion nécessaire, droits insuffisants, élément inaccessible, excès de demandes et erreur serveur. Les messages métier fournis par l'API restent conservés. Une réponse JSON nulle, mal formée ou sans texte explicatif ne provoque plus l'absence de message d'erreur. Une réponse HTML inattendue est signalée. Aucune de ces situations ne déclenche automatiquement une nouvelle requête de modification.

Neuf nouveaux tests couvrent ces erreurs HTTP et leurs réponses inhabituelles. Un test supplémentaire vérifie que l'expiration de session retire immédiatement l'identité et le panier privés conservés en mémoire. Cela porte la suite frontend de 68 à 78 tests.

Les journaux ont montré une erreur « Contexte de l'application absent » lors de mises à jour du code en développement. La définition du contexte et son accès ont été séparés dans AppState.ts, tandis que le fournisseur reste dans AppContext.tsx. Une actualisation initiale a rechargé la nouvelle organisation, puis une nouvelle modification de http.ts a permis de vérifier la mise à jour à chaud : accueil toujours visible et aucun nouveau message d'erreur ou d'avertissement remonté après le début de cette observation. Cette preuve est limitée à l'essai enregistré ; elle ne certifie pas tous les changements possibles du code.

## 5. Accès et contrôles HTTP

| Requête | Résultat attendu et obtenu |
| --- | --- |
| GET backend /up | 200 |
| GET accueil frontend, avec Accept: text/html | 200 |
| GET liste publique des restaurants et carte du restaurant 1 | 200 pour les deux requêtes |
| GET /me et /me/orders en invité | 401 pour les deux requêtes |
| GET commandes, paramètres, employés et programme de fidélité du restaurant en invité | 401 pour les quatre requêtes |
| GET statistiques plateforme en invité | 401 |
| POST connexion sans jeton CSRF et sans identifiants | 419, sans authentification ni modification métier |

Un premier contrôle de l'accueil demandait par erreur une réponse JSON au serveur frontend et recevait 404. Le contrôle a été corrigé pour demander du HTML, comme le navigateur ; il répond 200. Ce résultat initial ne justifiait pas de modifier le site.

Les refus entre clients, rôles et restaurants sont aussi couverts par la suite backend réexécutée. Cette recette n'est pas un audit de sécurité exhaustif. Les mots de passe, clés et références de paiement ne figurent dans aucune preuve jointe.

## 6. Pièces de preuve

- [Accueil sur petit écran](Accueil_Telephone_320.jpg).
- [Accueil sur ordinateur](Accueil_Ordinateur_1440.jpg).
- [Connexion sur téléphone, champs non renseignés](Connexion_Telephone_375.jpg).
- [Panier visiteur sur téléphone](Panier_Telephone_375.jpg).
- [Mesures d'affichage et routes invitées](Mesures_Affichage.json).
- [Observation de stabilité après mise à jour](Stabilite_Actualisation.json).
- [Synthèse des contrôles](Synthese_Controles.json).

Rapports techniques détaillés, conservés localement : .local/tests-controles-finaux-frontend.xml, tests-controles-finaux-sqlite.xml et verification-http-finale-20261026.json. Le rapport PostgreSQL précédent est tests-regles-metier-postgresql.xml. La synthèse partageable reprend uniquement les résultats nécessaires, sans configuration privée.

## 7. Limites restantes et suite

Les écrans privés des trois comptes personnels n'ont pas été parcourus visuellement pendant cette étape. Leurs droits et actions sont couverts par les tests automatiques, mais une démonstration navigateur avec les titulaires reste à faire. Les essais sur téléphone physique, Safari/Firefox et technologies d'assistance restent distincts ; aucune certification d'accessibilité n'est annoncée.

Les décisions de barèmes, leur révision après utilisation et la correction exceptionnelle des points restent celles de la [recette métier](../../backend/docs/RECETTE_REGLES_METIER.md). Le [paiement réel](../Mon_Miam_Miam_Bilan_Paiement_26_10_2026.md) reste non validé et son débogage suspendu. Ces limites empêchent d'annoncer une application intégralement validée en production.

Prochaine étape : actualiser les livrables globaux et préparer la présentation, en conservant les preuves et limites réelles. Jira porte les critères et les décisions à reporter ultérieurement ; Git et GitHub serviront aux versions et PR après autorisation de publication. Aucun statut Jira, commit, push ou PR n'a été créé pour cette recette.

Les prérequis et commandes manuelles sont dans la [recette des trois rôles](../../backend/docs/RECETTE_TROIS_ROLES.md). Pour refaire les contrôles frontend, ouvrir PowerShell dans C:\Users\J\Desktop\projet miam miam\frontend, puis exécuter séparément npm test, npm run lint et npm run build. Pour le backend, utiliser les commandes du guide avec le PHP 8.5 isolé ; ne pas lancer deux suites sur la même base PostgreSQL.
