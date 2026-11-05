# Commander, travailler au restaurant et payer

## Débogage séquentiel : point G validé

La vérification serveur ne rejette plus INIT au seul motif que le reçu final ne concorde pas encore. Elle exige toujours les deux références de transaction exactes et conserve la commande non réglée. Tests backend : 94 / 384 assertions sur SQLite et PostgreSQL, Pint réussi ; contrôle de la référence réelle existante réussi en pending sans nouvelle tentative. Frontend inchangé, ses 44 tests précédents ne sont pas une preuve de réception d'un message. Prochaine étape E : examiner l'envoi du numéro chez EasyPay, puis F et H, séparément. Aucun envoi ou débit pendant le point G.

## Dernière correction : les boutons fonctionnent aussi sur la même ancienne commande

Après sauvegarde et migration autorisées du backend, la commande contenant un ancien essai sandbox admissible peut maintenant être payée en production sans être recréée. Ouvrir la commande, vérifier le montant puis cliquer Orange Money, M-Pesa, Airtel Money ou Accéder au paiement sécurisé. L'ancien essai est conservé et une nouvelle tentative réelle est préparée seulement au clic, puis le navigateur ouvre EasyPay. Le numéro et la validation se font chez le prestataire et l'opérateur ; aucun PIN dans l'application.

Une commande déjà payée ou une tentative réelle incertaine ne sont pas relancées. Les clics suivants reprennent la tentative réelle active, sans second encaissement. L'historique de test n'est ni effacé ni transformé. Les anciens conseils demandant systématiquement une nouvelle commande sont remplacés par ce parcours.

Résultats : 44 tests frontend, lint et build réussis ; 89 tests / 348 assertions sur SQLite et PostgreSQL, Pint réussi. Les trois boutons sont testés sur une ancienne commande sandbox avec préparation simulée d'une nouvelle URL v1 pour le même identifiant de commande. Aucun appel marchand, demande sur téléphone ou paiement réel déclenché pendant cette étape. La réception réelle reste à vérifier lors d'une recette financière distincte.

## Parcours actuel : boutons vers EasyPay

Contrôles finaux autorisés le 24 octobre : lint, 42 tests frontend et compilation réussis, 59 modules ; 85 tests / 320 assertions backend SQLite et Pint réussis. Les trois clics de réseaux et l'ouverture du guichet sont validés par simulation, pas par envoi d'une demande réelle. Aucun appel marchand ou nouveau paiement. Les mentions non exécutés plus bas décrivent l'état avant cette autorisation ; réception opérateur et succès financier restent à vérifier.

Le titulaire demande de remettre les moyens et de revenir au guichet EasyPay. Les trois moyens Orange Money, M-Pesa et Airtel Money sont visibles. Pour une commande sans tentative ou avec un paiement en attente du mode courant, un clic sur un réseau ou Accéder au paiement sécurisé appelle le serveur sans customer_phone, valide le lien HTTPS renvoyé et ouvre EasyPay. Le choix final du moyen et la saisie du numéro se font sur le guichet du prestataire. Les trois boutons utilisent le même guichet documenté ; aucun paramètre opérateur non documenté ajouté.

Une référence déjà en attente est réutilisée, sans nouvelle initialisation marchande. Après un échec de préparation, le bouton est bloqué et Actualiser la tentative sans la relancer propose une relecture. Le suivi serveur borné et la confirmation stricte sont conservés ; aucun clic ne signifie Payé. Une URL extérieure au domaine EasyPay est refusée. Si la navigation est empêchée, le lien Ouvrir le guichet EasyPay reste disponible.

Pour une ancienne tentative de test, les moyens restent visibles et désactivés avec la notice et le lien vers la carte. Ni remplacement de référence, ni création de commande ou débit automatique. L'API directe backend reste dans le code mais n'est plus appelée par le parcours principal du site.

Tests de redirection, visibilité, refus d'URL, absence de doublon et suivi mis à jour, NON exécutés pour cette restauration. Aucun nouveau paiement déclenché. Dans frontend : npm run lint, npm test, npm run build à lancer manuellement. Les descriptions du parcours direct ci-dessous sont historiques.

## Parcours direct demandé — version écrite, vérifications à exécuter

Diagnostic visuel complémentaire : la session client Edge affichait encore la commande n° 6, avec une tentative sandbox et des boutons désactivés. Cette ouverture ne constituait pas un essai de la nouvelle API directe. La zone de paiement affiche désormais Ancienne commande de test et un lien Ouvrir la carte pour une nouvelle commande, au lieu de boutons grisés accompagnés du message trompeur Vérifiez votre téléphone. Le lien ne crée ni commande ni paiement. Affichage observé dans la session existante après actualisation automatique ; tests automatisés et notification réelle toujours non validés.

Le 24 octobre, le parcours principal a été modifié pour saisir le numéro directement dans l'application puis demander la validation Mobile Money via l'API directe documentée EasyPay. Aucun nouveau test, lint, build, recette navigateur ou appel marchand effectué pour ce changement ; les preuves plus bas décrivent les versions antérieures.

1. Ouvrir une commande confirmée n'ayant pas encore de tentative de paiement. Une ancienne tentative de test ne peut pas être transformée en paiement réel.
2. Saisir le numéro RDC (exemple fictif : 0980123456 ou +243980123456). Les boutons de réseau conduisent au champ ; EasyPay détermine le réseau depuis le numéro.
3. Vérifier le montant affiché, puis cliquer une seule fois sur Recevoir la demande sur mon téléphone. Le numéro est transmis au prestataire. Ne pas communiquer son PIN à l'application.
4. Si EasyPay accepte l'initialisation, vérifier le téléphone. Une demande peut dépendre de l'activation marchand, du réseau, du solde et des conditions du prestataire. Aucun SMS reçu n'est promis à partir du seul code d'initialisation.
5. Contrôler le total et les frais affichés par l'opérateur avant validation sur le téléphone. Le site vérifie le statut auprès d'EasyPay, au maximum six fois pendant l'affichage, ou via Vérifier le paiement. Seule une réponse serveur concordante autorise Payé.
6. En cas d'erreur, son motif reste visible ; Actualiser la tentative sans la renvoyer relit l'état. Aucune nouvelle demande automatique. Une tentative incertaine requiert un rapprochement.

Le mode du poste est production depuis la demande explicite du titulaire. Aucun code modifié dans cette étape n'a été exécuté pour déclencher un nouveau débit. Commandes manuelles : dans frontend, `npm run lint`, `npm test`, `npm run build` ; dans backend, suivre API_ROLES_COMPTES.md avec PHP compatible et paiements externes neutralisés pour les tests. Les mêmes kits doivent être réunis ; ce changement n'est pas encore une preuve de livraison de notification réelle.

**Dépannage EasyPay du 24 octobre :** des boutons grisés accompagnés d'« Initialisation incertaine » indiquent une tentative sans référence, volontairement bloquée contre les doublons. Une erreur de certificats CA du PHP local a été diagnostiquée et corrigée en conservant la vérification HTTPS. La dernière tentative sandbox a été reprise une seule fois, avec trace privée ; une référence et un guichet hébergé HTTP 200 sont désormais disponibles. Recharger la page, ouvrir la dernière commande puis choisir le réseau. Les anciennes tentatives incertaines ne sont pas débloquées en bloc. Aucun règlement confirmé. Voir backend/README.md pour reproduire la configuration TLS sur un autre poste.

## Commande immédiate et vrais boutons de paiement — 24 octobre

Correction exécutée après autorisation le 24 octobre : **lint, 38 tests frontend et compilation réussis ; backend 78 tests / 287 assertions et Pint réussis**. Dans le panier, **Commander maintenant** est proposé par défaut : aucune heure à saisir, le serveur contrôle l'instant courant et les horaires du restaurant. Pour une venue future, choisir **Planifier mon arrivée**, puis une heure future. L'heure déjà écoulée de la capture causait le refus de validation ; elle n'est plus imposée pour une commande immédiate.

Cliquer Vérifier le prix puis **Confirmer ma commande** : le reçu du serveur reste dans la fenêtre et la commande est transmise au restaurant. Les boutons des réseaux y portent « Cliquer pour payer via EasyPay » et ouvrent le paiement après initialisation. Avant confirmation, les mêmes noms sont explicitement informatifs : « Disponible après confirmation de la commande ». Le paiement reste une étape distincte, contrôlée par le prestataire et vérifiée au serveur. Le choix final et le téléphone sont traités dans son guichet ; aucun routage fictif des opérateurs n'est simulé. Les commandes restent accessibles dans Mes commandes si la fenêtre est fermée.

Les horaires fermés, articles indisponibles et devis expirés restent refusés. Aucun changement des horaires ou de la configuration marchande n'a été effectué par cette correction. Commandes exactes de vérification et contrat : `backend/docs/API_ROLES_COMPTES.md`.

## Extension du 24 octobre — espaces et identifiants

Code préparé, nouveaux tests écrits, **pas de lint/test/compilation/recette exécuté pour cette extension**. Les résultats cités plus bas concernent la version précédente.

- Après connexion, un administrateur/gérant arrive dans son espace de gestion, un serveur dans son service, un client sur la carte avec son panier. L'accueil et la navigation s'adaptent au compte ; les droits réels restent contrôlés par Laravel.
- Direction : commandes, création/modification/archivage des plats, nom et horaires pour l'administrateur, équipe et autres outils autorisés. Aucun droit de plateforme ajouté aux comptes locaux.
- Service : prise en charge des commandes, préparation, départ en livraison et remise, selon les transitions métier. La liste se relit toutes les quinze secondes quand l'onglet est visible ; ce n'est pas une notification push ni une confirmation automatique. Les disponibilités temporaires, réclamations et statistiques hebdomadaires prévues par le CDC restent accessibles selon le rôle.
- Client : sélection d'un restaurant, panier, calcul du devis, confirmation volontaire, historique et paiement si activé. L'absence de fidélité ne bloque pas une commande sans points : laisser zéro.
- Mon compte → Modifier mon e-mail et mon mot de passe : saisir soi-même le mot de passe actuel et le nouveau, puis se reconnecter. Aucun mot de passe d'un tiers n'est affiché dans les écrans de gestion. Les mots de passe restent ceux du fichier privé tant que le titulaire ne les a pas modifiés.
- Minimum ajusté à cinq caractères à la demande explicite du titulaire, avec majuscule, minuscule et chiffre. La même politique s'applique à l'inscription, au changement privé et à la récupération. Connexion de l'administrateur et ouverture du formulaire observées dans le navigateur ; aucun nouveau secret saisi ou enregistré par l'assistant.
- Ajouter un restaurant : bouton réservé aux administrateurs locaux. Le créateur reçoit la gestion du nouvel établissement ; ni équipe, ni horaires, ni compte marchand ne sont recopiés. Le nouveau restaurant apparaît avec une carte vide ; ajouter plats et horaires avant commandes. Aucune création de restaurant réel effectuée pendant la préparation du code.

Les trois adresses locales ont été remplacées conformément à la demande du titulaire, sans envoi d'e-mail ni changement de mot de passe. Les identifiants actuels sont dans le fichier privé `.local/ACCES_DEMONSTRATION.md`, jamais dans les kits. Commandes et contrats : `backend/docs/API_ROLES_COMPTES.md`. Les sections suivantes décrivent les états et preuves antérieurs.

Mon Miam Miam · Groupe 4 · ULC-ICAM · RDC · 23 octobre 2026.

Contrôles actuels réussis : lint, 25 tests et compilation frontend ; backend 65 tests / 227 assertions et Pint. La recette complète des navigateurs et des paiements reste distincte.

**Dernier état EasyPay : sandbox activé pour le restaurant 1.** Une initialisation technique de 100 CDF de test et sa page hébergée ont été vérifiées, sans téléphone ni paiement. L’API locale annonce les trois réseaux en mode test. La confirmation serveur d’un paiement réussi et le mode réel restent non validés. Horaires demandés et enregistrés : **lundi à samedi 08:00–20:00 ; dimanche 13:00–20:00**, à Kinshasa. Devis de riz aux haricots à 2 500 FC calculé dans le navigateur, sans confirmer de commande ; panier de test vidé. Le détail du constat figure en tête de `backend/docs/COMPLEMENT_PARCOURS_ROLES_PAIEMENT.md` ; il remplace les mentions historiques de paiement désactivé plus bas.

## 1. Ouvrir la bonne application

Adresse locale : http://127.0.0.1:5174/.

Le mode par défaut est désormais « Restaurants connectés ». La démonstration reste proposée dans le sélecteur de catalogue : elle ne crée aucune commande, aucun compte et aucun paiement.

Les serveurs doivent être en marche sur le PC. Ce lien ne rend pas l’application accessible depuis un autre ordinateur ou un téléphone.

## 2. Une connexion personnelle, des droits différents

Tous utilisent « Se connecter » avec leur adresse e-mail et leur propre mot de passe. Il n’existe pas de mot de passe collectif du restaurant et aucun rôle privilégié ne se choisit à l’inscription.

| Utilisateur | Accès | Utilisation |
| --- | --- | --- |
| Client / étudiant | Inscription puis connexion | Carte, panier, confirmation, paiement si activé, historique et réclamations |
| Administrateur du restaurant | Connexion avec un compte rattaché comme administrateur | Carte, nom/adresse, horaires, livraison, accès des gérants, employés et supervision |
| Gérant | Connexion après rattachement par l’administrateur | Commandes et étapes de service, carte, employés, statistiques et réclamations |
| Employé | Connexion après rattachement | Opérations autorisées de service, disponibilité des articles et traitement des réclamations |

L’administrateur local et le responsable de plateforme sont deux rôles distincts. Les paramètres généraux et l’attribution des gérants sont réservés à l’administrateur local de ce restaurant ; le gérant conserve ses tâches opérationnelles.

Le compte administrateur précédemment attribué reste limité au restaurant n° 1 de Kinshasa. Aucun autre compte ou restaurant ne reçoit de droits supplémentaires dans ce complément.

## 3. Commander comme client

1. Choisir « Restaurants connectés », puis le restaurant souhaité.
2. Cliquer sur **Ajouter** sous les plats et boissons.
3. Ouvrir **Mon panier**, ou le bouton visible **Passer ma commande** en bas de page.
4. Se connecter si nécessaire. Après connexion, rouvrir le panier ; il appartient toujours au même restaurant tant que le mode et la session ne changent pas.
5. Choisir le service : sur place avec heure d’arrivée, ou livraison si elle est activée.
6. Vérifier les horaires du restaurant et les renseignements demandés. Les jours sans créneau sont fermés.
7. Cliquer sur **Vérifier le prix et continuer**. Le serveur recalcule le total en FC.
8. Cliquer sur **Confirmer ma commande** avant l’expiration du devis.
9. Dans **Mes commandes**, ouvrir **Voir / payer** pour consulter les étapes et accéder au règlement s’il est activé.

Confirmer la commande ne signifie pas payer. Aucune commande n’est marquée réglée par le seul clic sur une confirmation, un lien ou un retour de paiement.

Si aucun horaire n’est configuré, l’administrateur doit les enregistrer avant que les commandes puissent être confirmées. Si le menu ou les paramètres changent après le devis, demander un nouveau calcul.

## 4. Entrer dans son restaurant comme administrateur

1. Se connecter avec le compte déjà autorisé.
2. Ouvrir **Espace restaurant**.
3. Sélectionner le restaurant de travail si plusieurs accès ont été attribués.
4. Ouvrir **Carte** pour ajouter/modifier les plats, boissons, prix et disponibilités.
5. Ouvrir **Mon restaurant & horaires** pour gérer les informations générales.

### Changer le nom du restaurant

Dans **Espace restaurant → Mon restaurant & horaires**, le formulaire charge les valeurs actuelles. Modifier **Nom du restaurant**, puis cliquer sur **Enregistrer les informations du restaurant**. Le nom d’un autre restaurant n’est pas changé. La version des paramètres est gérée par l’application, sans valeur technique à saisir.

Le nom d’un restaurant connecté vient du backend. Les noms Mboka et L’Atelier du catalogue de démonstration restent des exemples distincts.

### Ouvrir les commandes avec les horaires

Dans le même écran, ajouter les créneaux des jours ouverts, choisir les heures locales et cliquer sur **Enregistrer les horaires**. Ajouter plusieurs créneaux pour une pause entre deux services. Les jours sans créneau restent fermés. Les horaires proposés lors de l’ajout d’une ligne ne sont pas enregistrés avant validation.

La livraison nécessite son activation, une adresse du restaurant et des frais en FC, éventuellement `0.00` si elle est gratuite. Aucun tarif ou horaire n’est activé arbitrairement dans la base par ce complément.

### Donner un accès au gérant

1. Le gérant s’inscrit lui-même comme tout utilisateur, avec son adresse et son mot de passe personnel.
2. L’administrateur ouvre **Espace restaurant → Équipe**.
3. Dans **Attribuer ou retirer un accès de gérant**, saisir l’adresse du compte existant.
4. Choisir **Autoriser comme gérant**, vérifier l’adresse et confirmer.
5. Le gérant se reconnecte puis ouvre **Espace restaurant**.

Le même formulaire peut retirer l’accès de gérant. Il ne permet pas de transformer un utilisateur en administrateur, de rétrograder un administrateur, de changer un mot de passe ou de donner des droits dans un autre restaurant.

## 5. Travail du gérant et des employés

Dans **Espace restaurant → Commandes**, consulter et actualiser la liste, ouvrir une commande, puis enregistrer son étape autorisée : acceptée, préparation, prête, livraison ou servie. Les annulations/refus demandent les droits de direction et un motif. Les écrans de réclamations et statistiques restent dans cet espace.

Les commandes et statistiques sont actualisées à la demande ; aucun suivi automatique en temps réel n’est revendiqué dans ce complément. La création d’un nouvel employé peut envoyer un lien de récupération seulement si le service d’e-mail du serveur est configuré. Aucun mot de passe d’un autre membre ne doit être partagé.

## 6. Orange Money, M-Pesa et Airtel Money

Le 23 octobre 2026, le titulaire indique avoir créé le compte marchand EasyPay et confirme Orange Money, M-Pesa et Airtel Money pour ce compte. Après connexion par le titulaire, les identifiants existants ont été renseignés uniquement dans la configuration privée du restaurant 1, en mode sandbox, avec le paiement désactivé. La recette reste à faire : divergence entre les adresses de vérification des deux documentations et jeton à durée limitée. Voir le constat détaillé dans `backend/docs/COMPLEMENT_PARCOURS_ROLES_PAIEMENT.md`. Aucun paiement n’est déclenché par cette préparation.

La [documentation officielle EasyPay](https://developper.e-com-easypay.com/), consultée le 23 octobre 2026 et affichant une mise à jour du 12 octobre 2021, cite les trois réseaux et la devise CDF. Elle distingue `sandbox` (développement) et `v1` (production). Les identifiants **CORRELATION ID** et **PUBLISHABLE KEY** sont accessibles dans **Espace Développeur** du [portail marchand](https://merchant.e-com-easypay.com/). La documentation publique ne révèle pas les identifiants ni les environnements autorisés pour un compte particulier.

Après activation et recette :

1. Le client confirme sa commande puis ouvre **Voir / payer**.
2. Il clique sur **Accéder au paiement sécurisé**.
3. Le serveur initialise la transaction du montant de la commande en CDF.
4. Le client ouvre **Choisir mon réseau et payer chez EasyPay** et choisit parmi les options réellement proposées par ce marchand.
5. Il suit les instructions de l’opérateur avec son téléphone. Aucun code PIN n’est demandé par Mon Miam Miam.
6. Après retour, il ouvre **Vérifier le paiement**. Le serveur interroge EasyPay avant toute confirmation.

Le parcours actuellement codé utilise la page hébergée pour choisir le moyen de paiement. La documentation propose également une API Mobile Money directe : `POST /MODE/mobile-money/payment`, avec le téléphone sans zéro initial ni indicatif pays. Cette seconde API n’est pas implémentée ici ; elle ne documente aucun paramètre explicite de sélection de l’opérateur. Une initialisation incertaine, un refus ou une annulation ne sont pas relancés automatiquement. Le succès de l’initialisation ne prouve jamais le règlement.

### Ce que le titulaire du restaurant doit préparer

- Ouvrir le compte marchand déjà créé, rubrique **Espace Développeur**, et consulter les accès de test disponibles.
- Utiliser les identifiants correspondant au mode `sandbox` ; si seul un accès réel est proposé, demander au prestataire les accès de test avant la recette.
- Renseigner les identifiants directement dans la configuration privée du backend ; ne pas les envoyer dans une conversation, GitHub, Jira ou le frontend.
- Faire une recette sandbox, notamment succès, refus, annulation, retour sans paiement et notification falsifiée.
- Préparer l’hébergement HTTPS et les adresses de retour/notification avant l’ouverture publique et tout paiement réel.

## 7. Contrôles manuels

Après l’autorisation « Fais le », contrôles exécutés le 23 octobre 2026 : frontend lint réussi, 25 tests / 5 fichiers réussis et compilation réussie ; backend 65 tests / 227 assertions sur SQLite en mémoire et Pint réussis. Deux tests d’administration ont été corrigés pour charger la version des paramètres depuis l’API ; ordre des imports des routes corrigé. Aucun appel marchand, paiement, migration, redémarrage de serveur ou recette navigateur complète effectué. Le paiement EasyPay reste désactivé. Les commandes ci-dessous permettent de répéter ces contrôles.

Frontend, dans PowerShell :

```powershell
Set-Location 'C:\Users\J\Desktop\projet miam miam\frontend'
npm run lint
npm test
npm run build
```

Backend, avec le PHP compatible du poste :

```powershell
Set-Location 'C:\Users\J\Desktop\projet miam miam\backend'
$phpMiamControle = 'C:\Users\J\Documents\Codex\2026-10-20\referenced-chatgpt-conversation-this-is-an\work\runtime\php-8.5.12\php.exe'
$env:Path = "$(Split-Path -Parent $phpMiamControle);$env:Path"
$env:PHP_INI_SCAN_DIR = 'C:\Users\J\Desktop\projet miam miam\.local\php-conf'
& $phpMiamControle vendor/bin/pint --test
& $phpMiamControle vendor/bin/phpunit
```

Le fichier phpunit.xml impose SQLite en mémoire ; ne pas retirer les garde-fous de base de test. En cas d’écarts de présentation, appliquer manuellement Pint/Prettier selon les guides puis refaire les contrôles. Si les serveurs sont arrêtés, suivre les README ; ne pas lancer de doublon ni arrêter un processus inconnu. Les mises à jour de variables VITE nécessitent une relance manuelle du serveur frontend.

Recette à faire avec des comptes et navigateurs distincts : client inscrit, administrateur local, gérant rattaché, utilisateur sans rôle, accès à un autre restaurant refusé, nom/horaires préremplis, commande hors horaires refusée, commande sans paiement marquée non réglée et paiement désactivé tant que la configuration marchande manque.

## 8. Présentation et limites

Jira : suivre les travaux et décisions du parcours commande, des rôles, des paramètres et du paiement ; aucune tâche n’est déclarée terminée avant contrôles et relecture. Git/GitHub : les sources et kits restent locaux, sans commit ni publication. Figma : la maquette sauvegardée reste distincte et n’est pas modifiée par ces écrans.

Le CDC prévoit commande, espaces par rôle, paramètres et agrégateur ; il présente aussi le paiement comme bonus. Le groupe a choisi EasyPay et les trois réseaux Mobile Money en RDC. La création du compte et la confirmation des réseaux ne constituent pas une recette réussie de l’intégration.

Les PDF antérieurs décrivent les versions précédentes. Ce guide est le complément actuel ; la recette du prestataire, les vérifications mobiles/accessibilité, les photos téléversables et les autres finitions déjà documentées restent distinctes.
