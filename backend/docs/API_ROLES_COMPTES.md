# Comptes, espaces et nouveaux restaurants — 24 octobre 2026

Groupe 4 · ULC-ICAM · RDC · Binôme C : Ryan / Yedidia.

## État de cette extension

### Point G corrigé et vérifié — lecture de INIT

Débogage convenu un point à la fois : G avant E, F et H. INIT signifie uniquement initialisation : les deux références de transaction (order_ref et référence EasyPay) doivent correspondre, mais le reçu payment.reference n'est pas encore exigé pour ce seul statut. La vérification accepte alors une attente et met à jour verified_at. Aucun crédit, point de fidélité ou état payé n'est produit par INIT. SUCCESS, CANCELED, DECLINED et les autres statuts conservent le contrôle strict du reçu ; montant et devise restent contrôlés lorsqu'ils sont fournis. Les gardes d'activité et la seconde validation sous verrou sont conservées.

Résultats du point G : **94 tests / 384 assertions sur SQLite et PostgreSQL dédiés, Pint réussi**. Cinq tests ajoutés : INIT sans reçu final ou avec reçu provisoire ; mauvaises références de transaction ; références de reçu requises aux états finaux ; passage INIT puis SUCCESS concordant ; montant ou devise incohérents rejetés. Frontend inchangé : ses 44 tests/lint/build précédents n'ont pas été relancés pour ce changement backend.

Vérification réelle de la seule référence de production existante de la commande 6 : réussite, état pending, verified_at renseigné, commande toujours unrecorded et nombre de tentatives inchangé. Consultation de statut uniquement, aucun envoi de numéro, aucune initialisation ou demande de débit. Preuve privée .local/point-g-existing-payment-result.json ; suites .local/tests-point-g-sqlite.xml et tests-point-g-postgresql.xml. Le point E (demande opérateur), F (réception) et H (succès financier réel) restent non validés et n'ont pas été exécutés pendant cette étape. Les états de débogage ci-dessous sont historiques.

### Correction actuelle : nouvelle tentative réelle sur la même commande

Le titulaire autorise explicitement sauvegarde, migration et tests sans paiement réel. La migration `2026_10_24_000007_allow_payment_attempt_history` ajoute `payments.is_active` et `archived_at`, remplace l'unicité de order_id par un index unique partiel garantissant une seule tentative active par commande (PostgreSQL et SQLite). Aucun paiement ou événement n'est supprimé. Migration locale appliquée après sauvegarde privée vérifiée lisible : six commandes, cinq paiements et zéro événement conservés ; aucun appel marchand.

Lors du POST de paiement demandé par le client, les verrous restaurant puis commande sérialisent la préparation. Si la tentative active est sandbox, le mode courant v1 et l'état pending/unknown/declined/cancelled, l'ancien enregistrement devient inactif avec date d'archivage et événement d'audit ; une nouvelle tentative v1 est créée avec son propre identifiant et sa propre référence marchande. L'appel EasyPay est hors transaction SQL. Une commande déjà payée, une tentative de test paid/review/initializing ou une bascule inverse sont refusées. Une tentative v1 existante est reprise sans nouvelle initialisation ; unknown en production reste bloqué sans relance aveugle.

La notification IPN d'une tentative archivée est ignorée. Les vérifications revérifient is_active et les références dans la transaction finale pour empêcher qu'une réponse tardive règle la commande via l'ancien essai. Rapprochement, planificateur, annulation et anonymisation considèrent la tentative active. GET me/orders/{id} renvoie le paiement actif dans payment et la liste minimale des tentatives dans payment_attempts (id/mode/state/is_active/dates), sans identifiants marchands. Les anciennes références ne sont pas converties.

Frontend : boutons actifs pour l'ancien test admissible, notice Passer au paiement réel, même montant et même commande. Aucun clic effectué par l'assistant et aucun appel marchand pendant ces modifications. Le client choisit ensuite son moyen et son numéro chez EasyPay, puis valide sur son téléphone selon son opérateur. Une initialisation ne signifie toujours pas paiement réussi ni notification livrée.

Contrôles finaux : **89 tests / 348 assertions sur SQLite puis PostgreSQL dans la base dédiée mon_miam_miam_test ; Pint réussi ; frontend lint, 44 tests / 8 fichiers et compilation réussis, 59 modules**. Réponses marchandes simulées et appels non simulés interdits. Tests : conservation et sélection de la nouvelle tentative, réutilisation sans doublon, index unique actif, commande déjà réglée, paiement réel incertain non relancé, réponse tardive archivée non créditée, trois boutons de l'ancienne commande vers la nouvelle URL réelle. Un test catalogue a dépassé son délai pendant les contrôles simultanés ; la suite frontend entière a été relancée seule et a réussi sans changement ni assouplissement du test.

Pour une autre copie : sauvegarder sa base, appliquer la nouvelle migration avant de servir les nouveaux contrôleurs, puis exécuter les tests. Le rollback refuse un retour qui supprimerait des tentatives historiques ; ne pas supprimer l'historique ni restaurer une ancienne sauvegarde après des paiements nouveaux sans procédure de rapprochement approuvée.

```powershell
& $phpMiam artisan migrate --path=database/migrations/2026_10_24_000007_allow_payment_attempt_history.php
```

Rapports privés : .local/payment-attempts-migration-report.json, tests-active-payment-sqlite.xml, tests-active-payment-postgresql.xml et tests-active-payment-frontend.xml. Les limitations financières restent inchangées : aucun débit ni réception de message validé par ces contrôles. Les sections suivantes documentent les étapes antérieures.

**Contrôles finaux exécutés sur autorisation explicite : 85 tests / 320 assertions backend SQLite et Pint réussis ; frontend lint, 42 tests / 8 fichiers et compilation réussis (59 modules).** Rapports privés tests-retour-easypay-backend.xml et tests-retour-easypay-frontend.xml dans .local. Identifiants marchands neutralisés et paiements externes désactivés pour les seuls processus de tests ; .env de production conservé. Tests de redirection simulée pour chacun des trois réseaux, reprise de référence existante, refus d'URL étrangère, distinction des modes, erreurs persistantes et suivi borné réussis. Pas de nouvelle recette PostgreSQL, migration, appel marchand ou paiement. Aucun message sur téléphone confirmé. Ces résultats remplacent les mentions non exécuté des étapes précédentes décrites ci-dessous.

**Dernière demande du titulaire : retour au guichet hébergé depuis le frontend.** Les boutons de réseau et Accéder au paiement sécurisé appellent de nouveau POST me/orders/{order}/payment sans customer_phone ; l'API renvoie checkout_url et une tentative en attente est réutilisée. Le numéro est saisi chez EasyPay. L'API directe avec customer_phone reste implémentée mais ne constitue plus le parcours principal. Le mode privé production et la garde interdisant de réutiliser une référence sandbox en production sont conservés. Tests frontend adaptés, non exécutés ; aucun nouveau paiement ni appel marchand réalisé pendant la restauration. Les explications du parcours direct suivantes décrivent le contrat facultatif restant disponible.

### Nouveau parcours Mobile Money direct — écrit, non exécuté

Demande du titulaire : saisir son numéro et recevoir la validation du paiement sur le téléphone. Le parcours principal utilise désormais la section 4 de la documentation EasyPay : `POST /MODE/mobile-money/payment`. Aucun nouvel appel marchand, test, lint, compilation ou migration effectué pour ce changement. Le mode configuré reste production ; la réception réelle n'est pas encore prouvée. Les résultats automatiques plus bas sont historiques.

- API applicative existante `POST /api/v1/me/orders/{order}/payment` : corps facultatif `customer_phone`. Avec ce champ, validation RDC et normalisation en neuf chiffres sans zéro ni indicatif ; émission serveur vers l'API directe, prix de la commande lu côté serveur en décimal CDF. Le client ne fixe ni montant ni statut. Sans ce champ, le contrat hébergé antérieur reste disponible.
- Réponse directe : `data`, `notification_requested: true`, `checkout_url: null`. Ce booléen signifie que le prestataire a accepté l'initialisation ; il ne certifie ni la livraison d'un SMS ni un paiement. Pas de saisie, transmission ou conservation d'un PIN. Le numéro n'est pas ajouté à la base ou au stockage du navigateur ; pas d'e-mail client transmis à cette API.
- Une tentative déjà existante bloque une seconde demande directe (409), y compris après un délai dépassé. Une tentative sandbox ne devient pas réelle. Les anciens paiements restent à rapprocher ; ne pas modifier leur mode en base. En cas de refus code 0, le motif documenté du prestataire est affiché après retrait des identifiants/numéro, sans inventer un succès. Une initialisation incertaine reste unknown et ne se relance pas automatiquement.
- Frontend : numéro dans le reçu ou Mes commandes ; bouton Recevoir la demande sur mon téléphone, demande unique après action explicite. Les trois réseaux conduisent au champ ; le numéro détermine le réseau chez EasyPay. Le refus est conservé à l'écran et le bouton Actualiser la tentative sans la renvoyer relit le serveur. Six contrôles de statut au plus sont prévus pendant l'affichage ; arrêt au premier échec de vérification. Aucun contrôle n'initialise de nouvelle transaction. Le paiement n'est marqué payé qu'après la vérification stricte déjà existante (références concordantes et SUCCESS).
- Le détail de commande publie aussi le mode de la tentative pour signaler avant envoi un ancien paiement de test. Le guichet existant reste récupérable pour une tentative en attente du mode courant, avec validation du domaine HTTPS.
- Tests écrits : numéro normalisé/montant exact, refus de doublon et de numéro étranger, message de refus, délai dépassé sans nouvel envoi ; interface de saisie, absence de requête au simple choix d'un réseau, marchand absent, mélange de modes, erreur prestataire visible, six contrôles bornés. Non exécutés. La prise en charge et les frais réels dépendent du prestataire et de l'opérateur ; les vérifier sur le téléphone avant validation.

Après les vérifications backend déjà indiquées, contrôles frontend manuels dans `C:\Users\J\Desktop\projet miam miam\frontend` :

```powershell
npm run lint
npm test
npm run build
```

Pour la recette financière, ne pas utiliser les anciens essais. Vérifier d'abord les tests simulés, puis préparer une nouvelle commande et faire valider explicitement le montant total et les frais par le titulaire avant toute demande réelle. Le plafond de 100 FC autorisé précédemment concernait un essai isolé déjà initialisé, pas un nouvel essai automatique.

**Bascule demandée le 24 octobre :** configuration privée EasyPay passée à `v1` (production), sans changer APP_ENV, sans nouveau paiement ni modification d'une commande existante. Lecture effective via le proxy frontend : available=true, test_mode=false, trois réseaux annoncés. Aucun cache de configuration présent, pas de redémarrage nécessaire. Les références sandbox restent des références de test ; un contrôle dans PaymentService refuse leur réutilisation avec un mode différent (409), sans nouvelle initialisation. Deux tests de régression écrits pour cette bascule, non exécutés ; les résultats automatiques ci-dessous précèdent ce changement. La réception d'une notification et le succès financier ne sont pas validés par la bascule. L'essai réel isolé de 100 CDF antérieur reste à rapprocher séparément.

Contrôles manuels de ce changement, dans le dossier backend, avec le PHP compatible déjà indiqué dans le README :

```powershell
$env:EASYPAY_ENABLED = 'false'
$env:EASYPAY_MERCHANTS = '{}'
& $phpMiam vendor/bin/pint --test
& $phpMiam vendor/bin/phpunit --filter PaymentWorkflowTest
Remove-Item Env:EASYPAY_ENABLED
Remove-Item Env:EASYPAY_MERCHANTS
```

Les deux dernières lignes retirent uniquement les variables temporaires de ce terminal. Ne pas démarrer le serveur avec ces variables de test. Le fichier .env privé n'est pas modifié par ces commandes. Les requêtes de paiement sont simulées dans ces tests. Aucun changement de montant ou nouvel essai marchand nécessaire à ces contrôles.

**Diagnostic complémentaire du 24 octobre :** les boutons désactivés de la capture correspondaient à un paiement `unknown`, sans référence. Lecture HTTPS depuis PHP : erreur cURL 60, autorités CA non configurées. Ajout local du bundle officiel curl et des directives INI, sans désactivation TLS, puis redémarrage du backend du projet. Une seule reprise technique sandbox de la dernière commande concernée, même ligne de paiement et même référence marchande, a reçu une référence EasyPay ; le guichet hébergé répond 200 et contient Mobile Money. L'ancienne tentative reste tracée dans un rapport privé. Aucun paiement déclaré réussi, aucun débit et aucune reprise des anciennes tentatives incertaines. Les contrôles automatiques ci-dessous précèdent ce dépannage de configuration ; ils n'ont pas été relancés et le code applicatif n'a pas changé pendant celui-ci.

Après l'autorisation « vas-y règle ce problème », contrôles finaux du 24 octobre : **78 tests backend / 287 assertions réussis sur SQLite en mémoire, Pint réussi ; frontend lint, 38 tests dans 8 fichiers et compilation réussis (59 modules)**. Rapports privés `.local/tests-commande-immediate-sqlite.xml` et `.local/tests-commande-immediate-frontend.xml`. La simulation JSDOM des méthodes natives de dialogue a été complétée pour tester le maintien du reçu après vidage du panier ; seul l'ordre/format des blocs de RestaurantAdminController a été corrigé par Pint. Aucun contrôle qualité supprimé.

Lecture réelle de l'API locale : les trois réseaux sont annoncés disponibles en sandbox, avec sept horaires. Les serveurs frontend sur localhost:5173 et 127.0.0.1:5174 servent la même copie du dossier de ce projet, avec Commander maintenant présent dans le module servi. La session client est observée dans Edge ; le navigateur intégré conserve un administrateur. La recette visuelle complète n'a pas été achevée, la fenêtre Edge étant réduite ou reprise par le titulaire pendant l'inspection. Aucun paiement ou nouvelle commande applicative créé pendant ces contrôles, aucun appel marchand ; succès financier et IPN non validés. Pas de nouvelle exécution sur PostgreSQL.

Opération locale distincte expressément demandée : les trois adresses des comptes de démonstration ont été remplacées par celles fournies par le titulaire. Les rôles et mots de passe actuels sont conservés ; le titulaire a choisi de changer ses mots de passe dans l'application. Aucun e-mail envoyé ni compte Gmail créé. Aucun mot de passe dans ce document, les archives ou la mémoire.

Observation partielle antérieure : connexion du compte administrateur et redirection vers son espace de gestion vérifiées dans le navigateur ; formulaire de changement privé ouvert avec la règle de cinq caractères visible. Le titulaire doit saisir et enregistrer son nouveau secret lui-même. Aucun nouveau secret saisi par l'assistant ; les tests automatisés ci-dessus sont distincts d'un changement réel du titulaire.

## Contrats ajoutés

| Méthode et route | Autorisation | Comportement |
| --- | --- | --- |
| PATCH `/api/v1/me/credentials` | Compte actif connecté, mot de passe actuel obligatoire | Modifier son propre e-mail et/ou son mot de passe, jamais celui d'un tiers |
| POST `/api/v1/me/restaurants` | Administrateur actif d'au moins un restaurant actif | Créer un restaurant et rattacher son créateur comme administrateur local |

### Changement privé des accès

Champs : `current_password`, `email` facultatif mais obligatoire s'il est transmis, `password` facultatif, `password_confirmation` si changement du mot de passe. Au moins une modification doit être fournie. Une adresse est normalisée en minuscules et doit rester unique. À la demande explicite du titulaire du 24 octobre, le minimum commun à l'inscription, la récupération et au changement privé passe à **cinq caractères**, avec majuscule, minuscule et chiffre ; limite de 72 octets avant hachage conservée. Ce minimum est un choix du groupe, pas une valeur imposée par le cahier des charges.

La transaction verrouille uniquement le compte connecté. Les identifiants ou rôles supplémentaires du payload sont ignorés. Les droits de restaurant et de plateforme restent inchangés. Le mot de passe n'est jamais renvoyé ; l'audit ne conserve aucun secret. L'adresse n'est pas présentée comme vérifiée après changement. Les sessions du compte sont retirées du stockage database lorsqu'il est utilisé ; la session courante est invalidée après enregistrement. Se reconnecter avec les nouveaux accès. Les anciennes feuilles privées de démonstration ne se mettent pas automatiquement à jour après une saisie volontaire dans l'application.

### Ajout d'un restaurant

Les règles de nom, adresse, fuseau et frais sont partagées avec les paramètres existants via `RestaurantInput`. Le serveur relit un rattachement administrateur actif ; le rôle ne provient jamais d'un champ du formulaire. Le nouveau restaurant est actif et visible, avec une carte vide, aucun horaire, aucun employé et aucune configuration de paiement copiée. Son créateur reçoit uniquement un rattachement administrateur sur ce nouvel établissement. Les clients, serveurs et simples gérants sont refusés ; aucun droit sur les établissements tiers ni droit global n'est accordé.

Ce choix de création par un administrateur est la réalisation proposée pour la demande du groupe « ajouter un restaurant ». Le CDC demandait l'évolutivité, et le groupe a demandé plusieurs restaurants dès la première version. Ne pas présenter ce parcours d'inscription des établissements comme une obligation textuelle du professeur.

La réponse contient `data` pour le restaurant et `membership` pour son rattachement réellement créé en base. L'interface utilise cette réponse pour ouvrir le nouvel établissement : une seconde lecture du profil ne risque pas de transformer une création réussie en erreur de formulaire. Aucun rattachement n'est inventé dans le navigateur.

Créer des plats et horaires avant de prendre des commandes. Le restaurant peut être visible sans être prêt à commander ; les contrôles métier existants restent actifs. L'API de plateforme antérieure, réservée au responsable global, conserve son propre parcours de création inactive. Aucune attribution globale n'est réalisée pour contourner cette séparation.

## Parcours du service conservé

### Commande immédiate et paiement après confirmation — correction du 24 octobre

La capture du titulaire montre le refus d'une arrivée déjà passée (`after:now`), différent d'une fermeture du restaurant. Le panier propose maintenant « Commander maintenant » par défaut et « Planifier mon arrivée » en option. Pour une commande immédiate sur place, le client n'envoie pas `arrival_at` : le serveur utilise son instant courant dans le fuseau du restaurant. Aucun délai artificiel ou date de remplacement n'est imposé. Les horaires sont toujours contrôlés au devis et à la confirmation ; une commande ne force pas l'ouverture d'un restaurant fermé.

`POST restaurants/{restaurant}/order-quotes` accepte désormais l'absence de `arrival_at` pour le service sur place. Une date explicitement fournie doit rester valide, future et à moins de sept jours ; elle reste interdite en livraison. Le stockage existant représente le service immédiat par `orders.arrival_at = null`, avec la date de création de la commande conservée. Aucune migration requise. Les clients existants envoyant une arrivée future restent compatibles. Les messages d'arrivée passée sont en français et le transport frontend ne duplique plus la même erreur de validation.

Après confirmation volontaire, le panier est vidé mais la fenêtre conserve le reçu du serveur et les boutons de paiement. La commande est immédiatement consultable par le service, en état `pending`, jamais acceptée ou payée automatiquement. Le rafraîchissement de quinze secondes de l'espace de service est distinct de sa transmission en base.

Les boutons Orange Money, M-Pesa et Airtel Money, disponibles seulement après création de la commande et activation marchande correspondante, initialisent le paiement puis ouvrent l'URL HTTPS EasyPay validée. Tous utilisent le guichet hébergé ; aucun faux paramètre de routage d'opérateur n'est ajouté. Le client termine le parcours chez le prestataire. Avant confirmation, les moyens affichés sont informatifs. Les états incertains/refusés/annulés/réglés ne déclenchent pas une nouvelle initialisation automatique. Aucun paiement réel ou appel marchand lancé pendant cette modification du code.

Tests exécutés et réussis : ImmediateOrderTest (heure serveur, idempotence, visibilité au personnel, fermeture, date passée et recontrôle à confirmation) ; Checkout.test.tsx (absence de date imposée, erreur planifiée et maintien du reçu après vidage) ; complément PaymentActions.test.tsx (chacun des trois boutons et ouverture simulée de l'URL validée) ; complément http.test.ts (déduplication). La recette réelle d'un débit/succès de paiement reste à faire : les appels externes sont simulés dans les tests.

Les commandes passent par : reçue → acceptée → préparation → prête → servie sur place, ou prête → en livraison → remise. Le serveur confirme chaque étape ; il ne marque jamais un paiement réglé. L'annulation et le refus restent réservés à la direction, avec motif et contrôles de paiement. La livraison doit être activée et ses frais définis dans les paramètres avant qu'un client puisse la sélectionner.

## Vérification manuelle

Prérequis : dépendances déjà présentes, PHP compatible du poste et Node.js ; aucune migration ou installation requise par cette extension. Ne pas utiliser le PHP 8.0 global.

Dans PowerShell :

```powershell
Set-Location 'C:\Users\J\Desktop\projet miam miam\backend'
$phpMiamRoles = 'C:\Users\J\Documents\Codex\2026-10-20\referenced-chatgpt-conversation-this-is-an\work\runtime\php-8.5.12\php.exe'
$env:Path = (Split-Path -Parent $phpMiamRoles) + ';' + $env:Path
$env:PHP_INI_SCAN_DIR = 'C:\Users\J\Desktop\projet miam miam\.local\php-conf'
$env:EASYPAY_ENABLED = 'false'
$env:EASYPAY_MERCHANTS = '{}'
& $phpMiamRoles vendor/bin/pint --test
& $phpMiamRoles vendor/bin/phpunit
```

Les variables ci-dessus concernent uniquement le terminal de vérification et ne modifient pas le fichier privé. PHPUnit impose SQLite en mémoire ; conserver le garde-fou du TestCase et l'interdiction des requêtes HTTP non simulées. Ne pas réutiliser ce terminal pour démarrer l'application sans retirer ces deux variables temporaires ou ouvrir un autre terminal.

```powershell
Set-Location 'C:\Users\J\Desktop\projet miam miam\frontend'
npm run lint
npm test
npm run build
```

Les tests ajoutés couvrent les identifiants, l'unicité, le refus d'un mot de passe faible, la conservation des rôles, les sessions, le refus d'ajout de restaurant par un client/serveur/gérant et l'isolation des restaurants. La navigation frontend a ses propres tests d'affichage par rôle. Les contrôles doivent encore être exécutés puis les défauts éventuels corrigés avant validation.

Recette navigateur : trois profils distincts ; connexion avec les trois comptes locaux ; direction sur `#administration`, service sur `#service`, client sur `#carte`. Changer les mots de passe soi-même dans Mon compte ; vérifier la reconnexion. Ajouter un restaurant depuis un administrateur puis sa carte et ses horaires ; vérifier qu'un serveur d'un autre restaurant n'y accède pas. Préparer une commande de test puis confirmer volontairement les étapes, sans paiement réel.

Jira : suivre critères, décisions et preuves de cette extension ; aucun ticket clôturé ou modifié. Git/GitHub : sources et kits locaux, aucune publication. Figma : les espaces codés ne sont pas une modification automatique de la maquette.
