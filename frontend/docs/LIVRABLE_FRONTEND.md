# Complément actuel : commande, rôles et paiement

Ce document conserve la description historique du premier frontend. Les parcours et limites actuels sont précisés dans [PARCOURS_COMMANDE_ROLES_PAIEMENT.md](PARCOURS_COMMANDE_ROLES_PAIEMENT.md), dont les nouveaux écrans n’ont pas encore été exécutés ou testés. Le PDF antérieur n’est pas une validation de ce complément.

# Livrable frontend — Mon Miam Miam

**Groupe 4 · ULC-ICAM · République démocratique du Congo**

**Binôme B : Michael Cikuru et Assek Arloni**

Version de travail du 23 octobre 2026. Sources complètes de cette livraison et sélections individuelles préparées localement. Aucun commit, push, dépôt, pull request, ticket ou fichier distant modifié.

## 1. Objet et état de la livraison

Le frontend rend le service de restauration visible et utilisable depuis un navigateur. Il permet de découvrir plusieurs restaurants, consulter leur carte, préparer un panier et accéder aux espaces du client et du personnel. Il communique avec l’API Laravel du binôme C ; il ne remplace ni ses validations, ni ses calculs, ni ses contrôles de droits.

Cette livraison constitue un lot frontend substantiel exécuté après autorisation explicite du groupe. L’installation, l’analyse Oxlint, les 22 tests et la compilation TypeScript/Vite ont réussi. L’accueil de bureau, le panier et la lecture du backend ont été vérifiés dans le navigateur ; six contrôles HTTP sont conformes. La recette privée complète, l’accessibilité sur appareils et le paiement marchand restent à valider. Les résultats détaillés figurent dans VERIFICATION_FRONTEND.md.

Les sources regroupées sont le code complet de ce lot, pas une attestation que toutes les fonctionnalités finales du cahier des charges sont achevées. Les réglages avancés et l’administration plateforme sont encore partiels ; leurs limites figurent dans la section 12.

## 2. Exigences, demandes et choix de réalisation

| Origine | Éléments retenus |
| --- | --- |
| Cahier des charges | Application web, espaces étudiant/personnel/direction, authentification, carte, panier, sur place/livraison, réclamations, statistiques, fidélité, parrainage, avis, affiches, événements, classement et information sur les cookies |
| Cahier des charges | Technologies modernes ; React, Vue ou Angular cités comme exemples, avec liberté de choix ; API REST et échanges asynchrones ; charte #cfbd97 et #000000 |
| Corrections et demandes du groupe | ULC-ICAM, RDC, FC/CDF, plusieurs restaurants dès V1 ; photographies à thème africain, plats congolais et carte variée ; EasyPay choisi ; aucun dépôt actuellement |
| Choix de réalisation | React/TypeScript/Vite, CSS modulaire par domaine, client Fetch, contexte React et panier par réducteur ; photos locales et catalogue de démonstration séparé |
| Propositions à valider | Noms Mboka et L’Atelier, carte et tarifs de démonstration, points de charge de la répartition, détails de navigation, textes juridiques et identité des vrais partenaires |

Les montants historiques du cahier des charges ne sont ni convertis ni réinterprétés en paramètres approuvés. Le catalogue pédagogique est une nouvelle proposition de présentation, distincte des barèmes de fidélité mentionnés à titre d’exemple dans le document source.

## 3. Identité visuelle et intention gastronomique

La direction visuelle associe un fond ivoire, les couleurs demandées noir et doré, et un vert profond d’accompagnement. Les grands titres en Georgia rappellent une carte de restaurant ; la police système rend les formulaires et les prix lisibles sans téléchargement de police. Les photographies occupent une place importante dans l’accueil et les cartes. Les séparateurs et touches végétales évoquent les produits et le partage sans caricature culturelle.

L’accueil s’articule autour du message « Le goût d’ici. Le plaisir ensemble. ». Le plat mis à l’honneur est le poulet à la moambe ; le visuel représente réellement un assortiment moambe, pondu et chikwangue. Aucun faux avis, nombre de clients, label nutritionnel, restaurant ouvert en temps réel ou délai de livraison garanti n’est ajouté.

| Élément | Utilisation |
| --- | --- |
| #cfbd97 | Doré de la charte, accent et appel à l’action |
| #000000 | Noir de référence du cahier des charges |
| #283e30 | Vert d’accompagnement pour navigation et boutons |
| #faf8f2 | Fond ivoire et respiration visuelle |
| Georgia | Titres éditoriaux et noms de plats |
| Segoe UI / Helvetica / Arial | Commandes, formulaires, textes et montants |

Les grilles passent de quatre à deux colonnes pour les plats, puis les espaces de gestion passent en une colonne sur petit écran. La navigation mobile, les dialogues natifs, le focus visible et la réduction des animations sont prévus. Le rendu de bureau a été observé ; le comportement mobile et la navigation complète au clavier restent à vérifier sur appareils réels.

## 4. Restaurants, plats, boissons et prix

Deux restaurants fictifs permettent de présenter la séparation des cartes. Mboka met en avant les saveurs congolaises : poulet à la moambe, poisson et poulet à la braise, pondu/chikwangue et makemba. L’Atelier varie les envies avec burgers, pizza, grillades et poisson. Chaque carte comprend dix articles dont quatre boissons, soit vingt articles au total.

| Article de démonstration | Prix proposé |
| --- | ---: |
| Poulet à la moambe | 15 000 FC |
| Poisson à la braise | 18 000 FC |
| Poulet braisé | 12 000 FC |
| Pondu & chikwangue | 7 500 FC |
| Assiette de makemba | 3 500 FC |
| Burger maison | 12 000 FC |
| Pizza au fromage | 18 000 FC |
| Jus d’orange frais, 30 cl | 4 000 FC |
| Jus tropical, 30 cl | 4 500 FC |
| Eau plate, 50 cl | 1 500 FC |
| Soda, 30 cl | 2 500 FC |

Ces tarifs sont indicatifs, sans conversion depuis une autre monnaie. Des cartes publiques en RDC ont été consultées pour situer les ordres de grandeur ; elles ne fixent pas les prix des restaurants fictifs. Les restaurateurs doivent valider les prix réels, portions et disponibilités avant insertion dans Laravel.

Les photographies sont des illustrations de types de plats. L’assiette de pondu utilise l’assortiment moambe/pondu ; le jus tropical utilise une photo de jus d’orange. Ces correspondances sont explicites. L’eau et le soda disposent de dessins originaux sans marque commerciale. Pour une carte réelle, l’API ne possède pas encore de champ de photographie : une illustration neutre est utilisée, sans attribuer une photo précise à un article inconnu.

## 5. Photos et droits de réutilisation

Huit photographies sont récupérées depuis Wikimedia Commons. Leurs métadonnées de licence sont consultées et conservées avec le projet. Elles sont redimensionnées et converties en WebP pour limiter le poids ; le navigateur peut les recadrer dans les cartes. Les fichiers locaux évitent une dépendance à un hébergeur photographique pendant la navigation.

Les photos moambe et feuilles de pondu sont sous CC BY-SA 4.0. Les autres sélections portent une dédicace CC0 ou au domaine public selon leur fiche. Les noms des auteurs, liens sources, licences et modifications sont fournis dans CREDITS_PHOTOS.md et public/images/credits.json, ainsi que dans l’écran Crédits. Les adaptations CC BY-SA conservent cette licence propre ; cela n’attribue pas automatiquement cette licence à l’ensemble du code.

La photographie de feuilles de pondu n’est pas présentée comme un plat cuisiné. Les photos ne prouvent pas que les établissements fictifs existent, ni qu’un restaurant connecté a préparé les assiettes représentées. Les licences tierces et les attributions sont conservées.

## 6. Architecture du frontend

| Couche | Dossier | Responsabilité |
| --- | --- | --- |
| Entrée | main.tsx, App.tsx | Montage React, navigation par fragment, disposition et choix démo/API |
| Présentation | pages, components | Écrans, dialogues, formulaires et états chargement/erreur/vide |
| État | state/AppContext.tsx | Compte courant, restaurant sélectionné, panier et notifications en mémoire |
| Domaine | domain | Contrats TypeScript, montants exacts et réducteur de panier |
| Données | services, data | Fetch Laravel, pagination, adaptation des réponses et catalogue pédagogique |
| Graphisme | styles, public/images | Charte responsive, photos, dessins et crédits |
| Vérification | fichiers *.test.ts/tsx | Tests préparés, à exécuter avec Vitest et Testing Library |

Le flux est : interaction utilisateur, composant React, service Fetch, API Laravel, réponse affichée. Le code utilise des contrats typés ; le serveur demeure responsable de la validation réelle des données. Un type TypeScript n’est pas une autorisation et ne constitue pas à lui seul une validation des réponses réseau.

La navigation ne nécessite aucune dépendance supplémentaire. Les dépendances et leur verrouillage proviennent du socle publié ; aucune bibliothèque d’interface payante n’est ajoutée. Les sources actuelles de l’autre copie locale ne sont pas remplacées.

## 7. Parcours étudiant et commande

1. L’étudiant choisit un restaurant, explore les catégories ou cherche un plat.
2. Il ajoute les articles disponibles. Un panier contient les articles d’un seul restaurant, avec au plus cinquante lignes et quatre-vingt-dix-neuf unités par ligne.
3. En mode connecté, il crée son compte ou se connecte. Le rôle n’est jamais choisi dans le formulaire public.
4. Il renseigne une arrivée sur place ou une livraison, et éventuellement des points à utiliser.
5. Le frontend demande un devis. Laravel recalcule les prix, horaires, frais, disponibilités, versions et points.
6. L’étudiant lit le montant serveur puis confirme avant expiration. La confirmation réutilise une clé UUID tant que le devis reste dans la fenêtre ; une erreur réseau ne déclenche aucun rejeu automatique.
7. La commande apparaît dans l’historique. Le détail affiche les lignes, les événements de service et l’état du paiement. Le suivi s’actualise manuellement.
8. Un avis est proposé après une commande terminée ; une réclamation peut être envoyée puis suivie dans Mon compte.

Le sous-total du panier utilise des centimes entiers puis une représentation décimale ; il ne remplace pas le devis du serveur. Le changement d’article ou de quantité retire le devis de l’interface. Changer de catalogue démo/API réinitialise l’état local après accord lorsque le panier n’est pas vide.

Le mode démonstration ne crée aucun utilisateur, commande ou paiement simulé. Il sert uniquement à présenter la carte et le panier. Les fonctionnalités privées orientent vers la connexion réelle ; une panne API ne bascule pas silencieusement vers des données fictives.

## 8. Compte, fidélité et communauté

Le compte comprend la mise à jour du nom, du téléphone, de l’adresse et du choix d’apparition au classement public. L’export des données et la demande d’effacement nécessitent une confirmation par mot de passe auprès de Laravel. L’effacement demandé n’est jamais décrit comme une suppression immédiate de toutes les données et sauvegardes.

La fidélité affiche les comptes, leurs soldes et le journal paginé des mouvements. Le parrainage permet de demander un code pour un restaurant disposant d’un programme actif, d’associer un code reçu et de suivre anonymement les invitations. Les récompenses ne sont pas calculées à partir des exemples du cahier des charges dans le navigateur.

La carte connectée lit les affiches, les événements, les quiz, les avis et le classement. Les participations utilisent le compte authentifié ; les règles et les récompenses restent contrôlées côté serveur. Les listes publiques de communauté présentent leur première page ; une navigation complète de ces listes reste à ajouter. Les pages d’historique, réclamations et journal de fidélité disposent d’une pagination.

## 9. Personnel et direction

| Fonction | Employé | Direction locale |
| --- | --- | --- |
| Commandes du restaurant | Lire et faire progresser les étapes autorisées | Même accès ; refus/annulation selon les règles serveur |
| Catalogue | Disponibilité et plat du jour | Catégories, création, modification et archivage des articles |
| Réclamations | Soumettre une réponse | Soumettre et approuver la réponse d’une autre personne |
| Statistiques | Semaine | Jour, semaine et mois |
| Employés | Pas de gestion des accès | Création de rattachements et révocation/réactivation |
| Affiches et paramètres | Pas d’édition | Édition prévue avec les limites décrites plus loin |

Le contexte restaurant est visible dans l’espace de service. Les appartenances actives proviennent de /me ; chaque requête est ensuite autorisée par le backend. Les mises à jour d’articles et de commandes transmettent expected_version. Un refus serveur ou un conflit n’est pas présenté comme une réussite.

Les statistiques distinguent le montant des commandes servies et le montant des commandes servies effectivement payées. Elles affichent la date du calcul et un bouton d’actualisation ; l’interface ne prétend pas disposer d’un flux temps réel continu.

## 10. Sécurité, session et paiement

Le client utilise les sessions natives de Laravel, conformément au backend actuel. Il initialise le jeton via /auth/csrf-cookie, envoie les cookies avec credentials: include et le jeton dans X-CSRF-TOKEN pour les mutations. Il renouvelle ce jeton après connexion/déconnexion. Il n’introduit pas Sanctum comme dépendance déjà opérationnelle.

Les réponses 401 ferment la session affichée et vident le panier. Les erreurs réseau, 403, 409, 419, 422 et les erreurs métier restent visibles. Aucune mutation n’est rejouée automatiquement, notamment une confirmation de commande ou une initialisation de paiement. Les requêtes de lecture sont annulées lors du changement de contexte, et un résultat d’un ancien restaurant n’est pas réaffiché comme celui du nouveau.

Les mots de passe, cookies de session, clés et rôles ne sont jamais copiés dans localStorage. Seul le choix de la notice cookies y est conservé. Aucun traceur publicitaire ni mesure d’audience n’est chargé. Les textes reçus sont rendus comme texte par React, sans injection de HTML.

L’interface prépare le paiement à partir de la commande via Laravel, propose le lien EasyPay et fournit une vérification serveur. Le lien externe est limité à HTTPS sur www.e-com-easypay.com. Le retour navigateur success n’est pas utilisé comme preuve de paiement. Une configuration marchande absente produit une erreur visible ; aucune clé de paiement n’est envoyée dans VITE_*.

## 11. Prise en main et exécution manuelle

Les logiciels nécessaires sont VS Code ou un autre éditeur, Node.js 24/npm et un navigateur récent. Pour les commandes réelles, le backend Laravel et PostgreSQL fonctionnent séparément. Le PHP global XAMPP 8.0 n’est pas compatible avec le backend : suivre son README pour le PHP isolé du projet. Ne pas modifier silencieusement l’installation globale.

Depuis C:\Users\J\Desktop\projet miam miam\frontend, exécuter dans l’ordre : npm ci, npm run lint, npm test, npm run build, puis npm run dev -- --host 127.0.0.1 --port 5174 --strictPort. Ouvrir http://127.0.0.1:5174/. Le README contient les commandes PowerShell prêtes à copier et l’explication des ports.

La connexion locale utilise /api/v1 sur le port 5174 ; Vite relaie ces demandes vers le backend 8010. L’ancienne copie sur 5173 et la configuration CORS privée sont conservées. Pour une API distante, configurer une origine FRONTEND_URL et des cookies compatibles. Ne pas mélanger localhost et 127.0.0.1 ni lancer un doublon de serveur. Ne pas réinstaller ni migrer les bases existantes pour simplement ouvrir le frontend.

Sur un autre poste, préparer son propre environnement et sa propre configuration privée. Pour un déploiement, remplacer l’URL locale par une API HTTPS et organiser cookies/CORS sur des domaines compatibles. Déployer React sur Vercel ne déploie pas automatiquement Laravel, PostgreSQL, les e-mails et le paiement.

## 12. Vérifications à effectuer et finitions

| Élément | État de cette livraison | Acceptation nécessaire |
| --- | --- | --- |
| Sources React et tests | Installation, lint, 22 tests et build réussis | Conserver les preuves et poursuivre la recette métier |
| Présentation responsive | Accueil de bureau et panier observés | Vérification mobile, clavier et autres navigateurs |
| Parcours Laravel | Appels alignés sur les contrôleurs locaux | Recette réelle connexion, devis, commande et droits A/B |
| EasyPay | Préparation et vérification appelées via Laravel | Configuration marchande et sandbox sans fonds réels |
| Horaires et paramètres | Formulaire de paramètres partiel | Lecture API authentifiée des réglages, préremplissage et éditeur d’horaires |
| Barèmes de fidélité | Lecture client et usage des points | Écran d’approbation et modification de politique à compléter |
| Quiz | Lecture et participation prévues | Formulaire de création et règles de récompense à compléter |
| Plateforme | Création de restaurant inactif et attribution d’une direction | Liste privée, activation assistée, statistiques et procédures avancées |
| Avis et communauté | Lecture/première page et publication d’avis | Pagination publique complète et modération des avis côté direction |
| Données et mentions légales | Choix et demandes prévus | Responsable, contacts, durées et textes à approuver |

La liste publique reste minimale. Le complément actuel ajoute une lecture authentifiée des paramètres/horaires pour l’administrateur local et une lecture publique des options de commande. La version des paramètres est désormais gérée par le formulaire. Les sources backend et les kits associés sont actualisés, mais ces ajouts restent à tester ; voir le guide des parcours.

Les cas écrits couvrent la monnaie, l’isolation du panier, les erreurs et jetons HTTP simulés, et la sélection du catalogue React. Ils ne couvrent pas toute l’application, le paiement réel ou une concurrence de sessions. Le document RECETTE_MANUELLE.md fournit les étapes à consigner avec résultats observés, date et preuves.

## 13. Répartition et livraisons individuelles

La répartition proposée compte huit tâches par personne et cinquante points de charge relative chacune. Michael prend en main l’authentification, la direction visuelle, la carte, le panier, le devis, les commandes, les avis et la navigation/cookies. Assek prend en main les espaces de personnel, les réclamations, les statistiques, le compte, la fidélité, la communauté et les premiers écrans de plateforme.

Le dossier principal contient toutes les sources de ce lot. Les kits Michael et Assek sélectionnent les fichiers à prendre en main selon le manifeste ; ils ne sont pas deux applications autonomes. Leurs dépendances croisées sont normales : les écrans d’Assek utilisent les contrats et le client HTTP, et les écrans de Michael utilisent les formulaires et le chargement des ressources. Reconstituer le frontend complet avant de vérifier l’application.

Les noms des responsables indiquent une responsabilité de prise en main et de présentation. Ils ne créent ni faux auteurs Git, ni commits individuels, ni preuve d’une relecture déjà réalisée. Le détail des tickets, points et transferts proposés figure dans REPARTITION_BINOME_B.md.

## 14. Outils, preuves et suite du projet

À cette étape, Figma sert à comparer la maquette et l’interface ; Jira porte les critères, limites et anomalies ; les versions Git et futures PR GitHub seront utilisées après autorisation de reprise des dépôts. Aucune synchronisation automatique Jira/GitHub n’est supposée. Aucun statut distant n’est changé pendant la préparation des kits.

La présentation recommandée commence par le contexte RDC et le choix technique autorisé par le cahier des charges. Michael montre la sélection de restaurant, la carte et le panier. Assek montre les rôles, les réclamations et la fidélité sur des comptes de test préparés. Le binôme explique ensuite le calcul serveur, la séparation des restaurants et les limites restantes. La démonstration du catalogue et du panier est désormais vérifiée ; une démonstration de commande et de paiement réels attend leur recette complète.

Les prochaines actions sont : effectuer le parcours complet avec Ryan/Yedidia, compléter les écrans avancés, puis réaliser la recette globale avec Prudent/Moise. La publication et le déploiement restent des étapes séparées nécessitant l’accord du groupe.

## 15. Sources de référence

- Cahier de charge original Mon Miam Miam, V1.0.2 du 16 septembre 2024, fichier DOCX fourni au groupe.
- Backend local : routes/api.php, contrôleurs et backend/docs/API_LOT_1.md, consultés le 23 octobre 2026.
- Conventions publiées : https://github.com/cikurumichael85-glitch/mon-miam-miam/blob/main/CONTRIBUTING.md
- Figma : https://www.figma.com/design/PF49uaF9tgaDaFB08LaqGZ/Mon-Miam-Miam---Groupe-4---ULC-ICAM
- Repères publics de restauration RDC : https://bisofood.com/ et https://restaurantfn.com/ ; consultation le 23 octobre 2026. Tarifs pédagogiques proposés, sans garantie de prix commercial.
- Photos : sources et licences détaillées dans CREDITS_PHOTOS.md et public/images/credits.json.
- Règles Oxlint : https://oxc.rs/docs/guide/usage/linter/rules/eslint/complexity et https://oxc.rs/docs/guide/usage/linter/rules/eslint/max-depth.

Le présent dossier distingue les exigences, les choix du groupe, le code écrit et les validations encore attendues. Il sert de support professionnel de présentation au binôme B.
