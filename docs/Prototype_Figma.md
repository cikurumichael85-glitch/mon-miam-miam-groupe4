# Mon Miam Miam - Guide du prototype interactif

**Groupe 4 - ULC-ICAM, République démocratique du Congo**
Mise à jour : 21 octobre 2026. Monnaie : franc congolais (CDF / FC).

Moise Balaga, Lucas Kandende, Michael Cikuru, Assek Arloni, Kabeya Ryan, Bacirhamya Yedidia et Prudent Kakesa.

## Accès

- [Ouvrir le prototype cliquable](https://www.figma.com/proto/PF49uaF9tgaDaFB08LaqGZ/Mon-Miam-Miam---Groupe-4---ULC-ICAM?node-id=1-419&scaling=scale-down&content-scaling=fixed&starting-point-node-id=1%3A419&show-proto-sidebar=1).
- [Modifier les écrans dans Figma](https://www.figma.com/design/PF49uaF9tgaDaFB08LaqGZ/Mon-Miam-Miam---Groupe-4---ULC-ICAM?node-id=1-418).
- [Suivre les maquettes dans Jira : MMM-9](https://legroupea.atlassian.net/browse/MMM-9).

L'accès dépend des droits existants du fichier Figma. Aucun nouveau partage public ni accès d'un tiers n'a été accordé. La page « Page 1 » conserve la planche initiale ; « Prototype interactif » contient les écrans séparés et les interactions. Ce guide remplace le statut « maquettes statiques uniquement » du dossier de départ.

## Présentation au professeur

### 1. Parcours ordinateur : restaurants A et B

Dans la liste des flux, sélectionner **Ordinateur - Restaurants A et B**.

1. Cliquer sur le texte « Voir le menu » du restaurant A.
2. Montrer le nom du restaurant dans le menu et le panier.
3. Cliquer sur « Voir mon panier », puis « Confirmer la commande ».
4. Montrer la référence fictive `#A-014`, le mode de service et le total illustratif.
5. Cliquer sur « Suivre ma commande », puis « Retour aux restaurants ».
6. Refaire le parcours avec le restaurant B : les écrans portent « Restaurant B » et la référence `#B-014`.

Le flux **Restaurant B - accès direct** ouvre directement son menu. Le restaurant C reste fermé dans cette démonstration.

### 2. Parcours téléphone : restaurant A

Sélectionner **Téléphone - Restaurant A**. Ce flux démarre sur le menu ; la flèche « Restaurants » permet de rejoindre d'abord la liste mobile.

1. Dans la liste, ouvrir le menu du restaurant A.
2. Ajouter la formule ou ouvrir le panier.
3. Confirmer, puis ouvrir le suivi.
4. Utiliser « Restaurants » en bas du suivi pour revenir à la liste.
5. Montrer également les retours depuis le panier et la confirmation.

Le restaurant B sur téléphone est encore une carte de maquette : sa déclinaison mobile complète reste à concevoir.

### 3. Traitement par le personnel

Dans le suivi du restaurant A sur ordinateur, utiliser **Démo : voir l'espace restaurant**. Cette entrée sert uniquement à la présentation ; elle ne représente pas une permission donnée à un étudiant dans l'application finale.

Cliquer sur **Accepter** pour la commande `#A-014`. L'écran suivant montre cette commande en préparation. Les autres commandes conservent leur état ; les compteurs passent de **2 à accepter / 1 en préparation / 1 prête** à **1 / 2 / 1**. Le rôle et les contrôles d'accès réels devront être implémentés dans Laravel.

## Si l'écran est coupé

Dans les options de présentation Figma, choisir **Ajuster la largeur et la hauteur**. La liste des flux peut être ouverte ou fermée depuis les contrôles de présentation. Cliquer sur les libellés des boutons ; certaines interactions sont portées par le calque de texte plutôt que par tout le rectangle du bouton. Les touches de navigation de Figma ne remplacent pas la vérification des boutons du parcours.

## Vérifications réalisées

| Scénario | Résultat observé en présentation |
|---|---|
| A sur ordinateur | Liste, menu A, panier A, confirmation A et suivi A ouverts au clic. |
| Retour depuis le suivi A | Retour à la liste des restaurants. |
| B sur ordinateur | Le choix de B ouvre son menu, son panier, sa confirmation et son suivi. |
| A sur téléphone | Liste, menu, panier, confirmation et suivi ouverts au clic. |
| Retour depuis le suivi mobile | Le libellé « Restaurants » revient à la liste mobile. |
| Personnel A | L'entrée de démonstration ouvre les commandes ; « Accepter » ouvre l'état corrigé. |
| Cohérence après acceptation | Une seule commande change d'état ; compteurs 1 / 2 / 1 et quatre commandes conservées. |

Ces observations vérifient les transitions du prototype Figma. Elles ne constituent pas des tests du code applicatif, de la base de données ou de la sécurité.

## Limites de cette version

- Le prototype simule une navigation entre états prédéfinis. Il ne crée pas de commande, ne calcule pas un panier et ne traite aucun paiement.
- Les prix, plats, restaurants et références sont fictifs. Aucun montant du cahier des charges n'a été converti. Le total de 8 000 FC sert à la présentation.
- Les vues ordinateur du menu et du panier représentent des états illustratifs distincts : le panier du menu contient un plat, celui de validation contient aussi une boisson. Ajouter un autre plat ne recalcule pas le contenu affiché.
- La recherche, les filtres, les quantités et les champs de saisie ne sont pas fonctionnels. Les contrôles sans lien de prototype restent visuels.
- L'authentification, les autres espaces du CDC, l'administration de plateforme, les erreurs et la confirmation de changement de restaurant restent à compléter.
- Les états « prête » et « servie » du personnel ne font pas encore l'objet d'une simulation complète.
- La revue par le second membre du binôme et la validation de l'encadrant restent à effectuer.

La tâche **MMM-9 reste En cours** : une première démonstration navigable est disponible, mais tous ses critères de fin ne sont pas encore remplis.

## Travail des binômes

| Binôme | Prochaine contribution et preuve |
|---|---|
| A : Moise + Lucas | Relire le parcours et les règles ; noter les décisions et la revue dans MMM-9. |
| B : Michael + Assek | Compléter authentification, autres espaces, erreurs et vues téléphone ; liens MMM-46, MMM-47, MMM-53. |
| C : Ryan + Yedidia | Relier les champs et actions au modèle de données, aux API et aux droits ; MMM-25, MMM-48, MMM-49. |
| D : Prudent + Moise | Exécuter la recette sur deux restaurants, consigner les anomalies et les corrections ; MMM-40, MMM-55. |

Moise participe à deux binômes, avec des créneaux à organiser. Ne pas déclarer une revue humaine sans qu'elle ait effectivement eu lieu. Git et GitHub serviront à versionner et relire les fichiers partagés ; aucun dépôt ni code applicatif n'a été créé par la réalisation de ce prototype.
