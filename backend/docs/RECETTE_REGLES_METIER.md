# Recette des règles métier : horaires, fidélité, parrainage et réclamations

Mon Miam Miam · Groupe 4 · ULC-ICAM · RDC · CDF / FC
Date : 26 octobre 2026.

## 1. Objet et résultat

Cette étape confronte le code au cahier des charges original et vérifie les principales règles par des scénarios automatiques. Elle ajoute un écran de gestion de la fidélité et corrige l'affichage du journal de points et le contexte des codes de parrainage. Aucun horaire, programme, compte ou solde réel n'a été modifié ; aucune migration ni opération EasyPay réelle n'a été effectuée.

La validation technique des scénarios décrits ci-dessous ne vaut pas approbation d'un barème commercial. Les décisions restant au groupe sont listées en section 7. La démonstration avec les comptes personnels reste distincte des tests.

## 2. Cahier des charges et décisions du projet

Source relue : CAHIER DE CHARGE PROJET Mon Miam Miam.docx, rubriques Espace étudiant, Espace employé, Espace gérant, Modèle de système de fidélité et de parrainage, sections 2.1 à 2.5.

| Sujet | Exigence du cahier | Choix actuel ou paramètre à valider |
| --- | --- | --- |
| Horaires | Administrateur capable de configurer les heures d'ouverture ; commande sur place ou livraison | Groupe : lundi à samedi 08 h–20 h, dimanche 13 h–20 h. Tests reproduisant ces limites ; configuration réelle non relue pendant cette étape. |
| Fidélité | Accumulation, consultation, historique et utilisation des points pour une remise | Crédit après service terminé ; possibilité d'exiger aussi un paiement confirmé. Calcul sur les articles après remise, hors livraison, par tranches entières. Ces modalités sont les choix actuels du projet. |
| Barème | Conversion monétaire nécessaire ; le document donne un exemple de 1 000 F pour un point et de 15 points pour une remise de 1 000 | Exemples conservés numériquement et exprimés ici en FC selon le contexte du groupe. Aucun taux imposé ni activé par cette recette. |
| Expiration | Expiration configurable, avec 12 mois cités comme exemple | Configuration en jours ; durée réelle à approuver. Les tests utilisent des durées fictives, dont 30 jours, sans remplacer l'exemple du document. |
| Parrainage | Code unique, attribution au parrain après première commande réussie du filleul, suivi de récompense | Code par compte et programme, association avant première commande du programme, une seule récompense ; montant de points à valider. |
| Réclamations | Client dépose et suit ; employé répond ; gérant valide ou refuse les réponses proposées | Réponse soumise cachée au client, publication après approbation par une autre personne de la direction. Interdiction d'auto-approbation : garde-fou du projet. |
| Administration | Gestion des paramètres du programme, notamment conversion et validité des points | Écran ajouté. Un programme déjà utilisé conserve ses règles ; changement de barème historique par une procédure de versionnement encore à définir. |

Le document comporte des formulations générales de crédit « à chaque commande » et une notion de première commande « réussie ». Le projet retient le service terminé pour éviter de récompenser une commande abandonnée. Ce choix doit être présenté comme une règle du projet à confirmer, et non comme une citation imposée par le professeur.

## 3. Modifications réalisées

### 3.1 Gestion de la fidélité

L'espace de gestion possède désormais un onglet **Fidélité**, accessible au gérant ou à l'administrateur du restaurant. Le backend applique ces mêmes droits ; un client, un serveur ou un responsable d'un autre restaurant ne peut pas lire ou enregistrer les paramètres.

La nouvelle route GET /api/v1/restaurants/{restaurant}/loyalty-program lit uniquement le programme local. Elle renvoie data à null lorsqu'il n'existe pas, sinon active, version, rules et approved_at. Elle ne crée aucun programme et ne sélectionne pas le programme global pour l'éditer par défaut. L'enregistrement réutilise la route POST existante, avec approbation exigée côté serveur.

Le formulaire demande le montant nécessaire pour gagner un point, les points nécessaires par remise, sa valeur en FC, le bonus du parrain, la validité en jours et la condition de paiement confirmé. Un nouveau programme est présenté inactif, sans aucun chiffre de barème prérempli. La condition de paiement confirmé est proposée par défaut, mais reste visible et modifiable avant approbation. Une case obligatoire et une confirmation précèdent l'enregistrement volontaire.

Le programme utilisé est protégé contre la réécriture de ses règles. Désactiver son état actif en conservant ses règles est possible. Une révision du barème après utilisation n'est pas implémentée par cet écran ; aucune migration ou suppression d'historique n'a été inventée pour contourner cette limite.

### 3.2 Journal des points et codes

Le journal utilise désormais le champ occurred_at renvoyé par l'API, à la place d'un created_at absent qui pouvait afficher une date invalide. Le code de parrainage affiché est lié à l'identifiant du restaurant demandé : changer d'établissement le retire et une ancienne réponse réseau reçue tardivement ne le réaffiche pas dans le nouveau contexte. Un bouton permet d'actualiser les invitations sans créer de parrainage supplémentaire.

## 4. Cas vérifiés

| Domaine | Scénarios automatiques | Résultat |
| --- | --- | --- |
| Horaires | Avant ouverture refusé, ouverture exacte autorisée, dernière minute avant fermeture autorisée, fermeture exacte refusée ; samedi et dimanche | Réussite |
| Fuseau | Même instant UTC, deux restaurants aux fuseaux Kinshasa et Lubumbashi ; application de leurs heures locales respectives | Réussite |
| Confirmation | Commande immédiate sans date client, contrôle de l'ouverture encore appliqué lors de la confirmation | Tests existants réexécutés, réussite |
| Barèmes | Lecture et écriture réservées à la gestion ; approbation refusée si absente ou fausse ; autre établissement interdit | Réussite |
| Immutabilité | Réécriture d'un barème utilisé refusée ; désactivation à règles identiques possible | Réussite |
| Gain et utilisation | Commande servie à 15 000 FC avec barème fictif de 1 000 FC par point : 15 points ; utilisation de 15 points pour 1 000 FC de remise sur une autre commande | Réussite en base de test uniquement |
| Restitution | Annulation admissible restitue les points une seule fois ; nouvelle transition d'annulation refusée | Réussite |
| Solde et expiration | Double dépense depuis deux devis refusée ; seuls les points non dépensés expirent ; nouvelle expiration sans doublon | Tests existants réexécutés, réussite |
| Paiement exigé | Une commande servie mais non réglée ne gagne pas de points si require_paid est vrai | Réussite, sans essai financier |
| Parrainage | Même code retrouvé ; association répétée idempotente ; commande d'un autre programme sans récompense ; première commande admissible récompense une fois, suivante sans nouveau bonus | Réussite |
| Auto-parrainage | Association de son propre code refusée | Test existant réexécuté, réussite |
| Réclamations | Réponse soumise invisible ; serveur sans approbation ; gérant ne s'approuve pas lui-même ; refus reste privé ; nouvelle réponse approuvée résout la demande | Réussite |
| Réclamations privées | Autre client sans accès ; nouvelle réponse après résolution refusée | Réussite |
| Interface | Date de journal, code lié au restaurant même après réponse tardive, formulaire sans taux automatique, approbation, annulation de confirmation et droits d'approbation des réclamations | Réussite par requêtes simulées |

Les barèmes de test sont des données fictives de recette. Ils ne constituent ni une activation sur les restaurants ni une approbation commerciale. Les essais de dépenses concurrentes existants sont séquentiels sur deux devis ; une vraie concurrence sur deux connexions reste une recette distincte.

## 5. Résultats et limites des preuves

- Backend SQLite en mémoire et PostgreSQL dédié mon_miam_miam_test : **116 tests / 635 assertions réussis** sur chaque moteur.
- Frontend : **68 tests dans 15 fichiers réussis**.
- Pint, Oxlint, TypeScript et compilation Vite : réussis ; 61 modules.
- Aucun appel marchand réel, aucun changement de configuration privée, aucun envoi de message à un tiers.

Le premier contrôle Oxlint a détecté une complexité de 11 dans la préparation du formulaire de fidélité. Cette fonction a été simplifiée et le contrôle final a réussi, sans modifier la limite de dix ni désactiver une règle.

Les tests de cette étape ajoutent quinze cas backend et onze cas frontend aux résultats du point 3. Les comptes, programmes et commandes de recette sont créés exclusivement dans les bases de test. Aucun serveur local n'a été lancé ou arrêté pendant cette étape. Les résultats de tests ne prouvent pas la présentation avec les comptes personnels ni le rendu sur tous les téléphones.

## 6. Guide de présentation sans paiement

1. Montrer **Mon restaurant → Mon restaurant & horaires** : expliquer que les jours sans créneau sont fermés et que la fermeture exacte est exclue. Présenter les tests du dimanche et des fuseaux sans changer les horaires réels.
2. Montrer **Mon restaurant → Fidélité** avec un compte autorisé : expliquer les champs, la case d'approbation et l'absence de barème prérempli. Ne pas enregistrer de valeurs arbitraires pour la démonstration.
3. Montrer **Mes privilèges** côté client : solde et mouvements si un programme approuvé et des points existent. Sinon, présenter l'état vide et les résultats de recette ; ne pas fabriquer un solde réel.
4. Expliquer la génération d'un code pour un programme actif et l'association avant la première commande admissible. Utiliser une base de démonstration autorisée si le groupe souhaite jouer réellement ce scénario.
5. Présenter le circuit de réclamation : client → réponse du serveur → décision d'une autre personne de la direction → réponse approuvée visible au client. Les tests montrent les refus et les réponses cachées.
6. Préciser que si l'attribution exige un paiement confirmé, une commande seulement servie ne suffit pas. La suspension du débogage EasyPay ne transforme jamais une commande en paiement réussi.

## 7. Décisions restant à valider

| Décision | État |
| --- | --- |
| Montant pour gagner un point et règle de remise | À approuver par le groupe/responsable ; exemples du cahier non activés automatiquement |
| Bonus du parrain et validité des points | À approuver ; données de recette distinctes |
| Gain après service seul ou service et paiement confirmé | Paramètre disponible ; politique réelle à valider sans contourner le blocage du paiement |
| Programme local prioritaire et éventuel programme global | Fonctionnement existant à confirmer ; désactiver le local peut laisser s'appliquer un global actif, donc vérifier cette politique avant activation |
| Révision des barèmes déjà utilisés et traitement des anciens soldes | Procédure de versionnement/migration à définir ; hors activation effectuée pendant cette étape |
| Circuit d'approbation par une autre personne | Prévoir deux comptes de direction distincts pour la démonstration ; pas d'auto-approbation |

Le cahier évoque également la validation de réclamations concernant les points. Le circuit général de réclamation est disponible ; cette version n'ajoute pas de bouton permettant de créditer ou débiter arbitrairement le solde. Une correction exceptionnelle de points doit faire l'objet d'une règle et d'une procédure auditée à définir avant réalisation.

## 8. Rejouer et suivre le travail

Les prérequis et commandes complets sont dans la [recette des trois rôles](RECETTE_TROIS_ROLES.md). Pour les nouveaux scénarios backend, depuis backend avec le PHP compatible et PHP_INI_SCAN_DIR local :

```powershell
$env:EASYPAY_ENABLED = 'false'
& $rolesPhp vendor/bin/phpunit --configuration phpunit.xml --filter 'BusinessRulesWorkflowTest|ClaimsWorkflowTest|ImmediateOrderTest'
& $rolesPhp vendor/bin/phpunit --configuration phpunit.pgsql.xml --filter 'BusinessRulesWorkflowTest|ClaimsWorkflowTest|ImmediateOrderTest'
& $rolesPhp vendor/bin/pint --test
```

La variable rolesPhp est définie avec le chemin absolu dans le guide lié ; ne pas utiliser le PHP XAMPP 8.0 incompatible. Les variables du terminal de test ne modifient pas le fichier .env. Pour l'interface, depuis frontend :

```powershell
npm test
npm run lint
npm run build
```

Rapports privés : .local/tests-regles-metier-sqlite.xml, tests-regles-metier-postgresql.xml, tests-regles-metier-frontend.xml. Aucun de ces contrôles ne valide la réception Mobile Money.

Jira servira à reporter les preuves et décisions dans les tâches existantes après accord du groupe. Git et GitHub conservent leur rôle de versionnement et de relecture future. Aucun commit, push, PR, statut Jira ou pièce jointe distante n'a été créé. Les mêmes kits locaux sont actualisés ; les PDF antérieurs restent historiques.

**Bilan : scénarios techniques vérifiés et écran de fidélité ajouté ; barèmes commerciaux, révision d'une politique utilisée et procédure de correction exceptionnelle de points restent à décider.** Le [bilan EasyPay](../../output/Mon_Miam_Miam_Bilan_Paiement_26_10_2026.md) reste applicable.
