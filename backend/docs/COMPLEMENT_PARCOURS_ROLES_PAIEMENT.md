# Dernière correction — historique des tentatives et boutons actifs

**Point G validé ensuite : 94 tests / 384 assertions SQLite/PostgreSQL, Pint réussi.** INIT avec références de transaction exactes est une attente, pas un reçu final ni une preuve de paiement. Vérification de la référence réelle existante réussie ; aucun numéro transmis ou nouvel encaissement. Frontend inchangé, étapes E/F/H non traitées. Voir API_ROLES_COMPTES.md ; les nombres suivants sont antérieurs à G.

Migration 2026_10_24_000007 appliquée localement après sauvegarde sur autorisation : plusieurs tentatives historiques conservées, une seule active par commande. Au clic, un ancien test admissible est archivé et une nouvelle tentative en production est préparée pour la même commande. Anciennes références et événements conservés ; aucune relance d'un paiement réel incertain. Voir API_ROLES_COMPTES.md pour le contrat courant et la migration obligatoire sur une autre copie.

Contrôles réussis : 89 tests / 348 assertions sur SQLite puis PostgreSQL, Pint réussi ; frontend lint, 44 tests et compilation réussis. Aucun paiement réel déclenché par ces contrôles et aucune notification réelle confirmée. Les sections antérieures ci-dessous sont historiques.

# Complément API : rôles, paramètres et paiement

Groupe 4 · ULC-ICAM · RDC · 23 octobre 2026.
État actuel : 65 tests / 227 assertions SQLite et Pint réussis. EasyPay activé uniquement en sandbox après une initialisation technique acceptée ; aucune validation de paiement ni mise en production.

## Activation sandbox vérifiée — constat le plus récent

Le 23 octobre 2026, après demande explicite de prise en charge de la configuration :

- Identifiants existants de l’espace développeur comparés au fichier privé, jamais ajoutés aux kits.
- Une seule initialisation sandbox de **100 CDF de test**, sans téléphone, e-mail ni commande applicative : HTTP 200, `code: 1`, référence reçue. Aucun clic sur Continuer, notification opérateur, débit ou paiement réalisé. Ce montant technique ne remplace aucun prix du projet.
- Page hébergée consultée : montant et description du test visibles, formulaire Mobile Money et logos M-Pesa/Orange Money/Airtel Money présents. Téléphone laissé vide ; simple consultation de la page.
- Vérification de cette transaction non payée : `checking-status` répond HTTP 200, `code: 1`, `status: 0` ; `checking-payment` répond HTTP 500 avec un message. Pour une référence inexistante, cette dernière répond HTTP 404. Les deux routes existent ; la différence documentaire ne signifie pas une route introuvable. La signification exhaustive des codes numériques n’est pas documentée dans les pages consultées : aucun code numérique n’est interprété comme un paiement réussi.
- Le jeton a été accepté pour l’initialisation malgré son champ d’expiration passé. Cela ne prouve ni la validité de sa signature, ni sa durée de validité future. Ne pas renouveler automatiquement un secret ou contourner une erreur d’authentification.
- `backend/.env` réglé sur **EASYPAY_ENABLED=true**, **EASYPAY_MODE=sandbox**, restaurant 1 uniquement ; cache de configuration Laravel effacé. Lecture HTTP de `checkout-options` : `available=true`, `test_mode=true`, trois réseaux confirmés. Restaurant 2 non configuré.
- Après indication des horaires par le titulaire, sept créneaux enregistrés pour le restaurant 1 depuis son espace administrateur : lundi à samedi 08:00–20:00 ; dimanche 13:00–20:00, fuseau Africa/Kinshasa. Relecture HTTP des sept créneaux et réouverture du formulaire conformes. Un devis de riz aux haricots à 2 500 FC a été calculé par le serveur dans le navigateur, sans confirmation de commande ni paiement ; article de test retiré et panier laissé vide. Preuve locale `.local/verification-horaires-restaurant-1.json`. Aucun changement de la carte, des rôles, de la livraison ou du restaurant 2.

La confirmation détaillée côté serveur n’a pas été validée sur un succès. Le code conserve son contrôle strict des références et de l’état `SUCCESS` ; ni le retour navigateur, ni le simple `code: 1` de l’initialisation ou du statut numérique ne marque une commande payée. Ne pas passer en `v1` avant recette complète et clarification des réponses par le prestataire. La présente section remplace les mentions de préparation désactivée ci-dessous, conservées comme historique.

Preuves techniques privées sans secrets dans `.local/easypay-sandbox-initialization.json`, `.local/easypay-sandbox-check-results.json` et `.local/easypay-local-activation.json`. Ces fichiers restent locaux ; aucune référence de transaction ni clé dans le livrable. Capture de la page sans clés : `output/EasyPay_Test_Sandbox.png`.

## Nouvelles lectures et attribution locale

| Méthode | Route après /api/v1 | Permission |
| --- | --- | --- |
| GET | `/restaurants/{restaurant}/checkout-options` | Public, restaurant actif |
| GET | `/restaurants/{restaurant}/settings` | Administrateur local actif du restaurant |
| POST | `/restaurants/{restaurant}/managers` | Administrateur local actif du restaurant |

Les 63 routes précédentes sont conservées ; trois routes sont ajoutées. Aucune migration ni modification de la base applicative n’est nécessaire pour ce code.

`checkout-options` expose fuseau, créneaux, disponibilité et frais de livraison, puis `payment.available`, `payment.test_mode` et les codes des réseaux confirmés. Il ne transmet ni identifiant marchand ni clé, et ne contacte pas EasyPay. `available` indique une configuration locale cohérente, pas une certification du contrat ni un test réseau réussi.

`settings` lit une version cohérente des informations et horaires. Les écritures `PATCH settings` et `PATCH hours` conservent `expected_version` et l’erreur 409 ; cette version est gérée par le frontend. Elles sont désormais réservées à l’administrateur local, pour distinguer son rôle de celui du gérant.

`POST managers` reçoit `email` et `active`. Le compte cible doit déjà être inscrit et actif ; aucune création de compte, aucun e-mail et aucun changement de mot de passe. Le rôle attribué est toujours `manager`, quel que soit un éventuel champ de rôle supplémentaire envoyé par un client. Un administrateur ne peut pas être modifié par cette opération. Les droits d’un autre restaurant et ceux de la plateforme restent inchangés. Le rattachement est journalisé et peut être révoqué.

`GET /me` ajoute `platform_access`, calculé côté serveur, pour ne pas afficher l’administration de plateforme à un administrateur local. Ce champ d’interface ne remplace aucun contrôle serveur.

Le détail des commandes ajoute `payment` avec seulement `state` et `verified_at`, ou `null`. Les paramètres marchands et références externes n’y sont pas ajoutés.

## Mobile Money : configuration privée future

Le 23 octobre 2026, le titulaire déclare le compte EasyPay créé et confirme les trois réseaux pour ce compte. Les identifiants restent à renseigner localement. Ne pas activer `EASYPAY_ENABLED` avec des valeurs fictives, ne pas modifier une commande en `paid` et ne pas envoyer de code PIN depuis cette application.

Le prestataire retenu reste [e-COM EasyPay](https://www.e-com-easypay.com/), [documentation développeur](https://developper.e-com-easypay.com/) relue le 23 octobre 2026 (date affichée : 12 octobre 2021). Elle documente CDF, Orange Money, Airtel Money et Vodacom M-Pesa, ainsi que `sandbox` pour le développement et `v1` pour la production. Les champs **CORRELATION ID** et **PUBLISHABLE KEY** se trouvent dans **Espace Développeur** du portail marchand ; les droits de test propres au compte ne peuvent pas être déduits de la page publique.

Le code actuel utilise l’initialisation et la page hébergée, puis `checking-payment` côté serveur. Les exemples officiels emploient `channels` au pluriel, comme le code, malgré une ligne de tableau au singulier. La documentation décrit aussi `POST https://www.e-com-easypay.com/MODE/mobile-money/payment?cid=...&token=...` : corps avec `order_ref`, `currency`, `amount`, `customer_phone`, `customer_name` et `description`, e-mail facultatif. Le téléphone ne doit commencer ni par zéro ni par un indicatif pays. Cette API directe n’est pas implémentée dans cette livraison et ne documente pas de paramètre opérateur explicite. Une réponse `code: 1` signifie initialisation acceptée ; seule la vérification serveur permet de confirmer le paiement.

Après obtention des identifiants et confirmation du contrat, adapter uniquement le fichier privé `.env` existant, sans le remplacer :

```dotenv
EASYPAY_ENABLED=true
EASYPAY_MODE=sandbox
EASYPAY_MERCHANTS='{"1":{"cid":"IDENTIFIANT_A_RENSEIGNER_LOCALEMENT","publishable_key":"CLE_A_RENSEIGNER_LOCALEMENT","confirmed_networks":["orange_money","mpesa","airtel_money"]}}'
FRONTEND_URL=http://127.0.0.1:5174
```

Cet exemple est un schéma, pas une configuration opérationnelle. Ne déclarer dans `confirmed_networks` que les réseaux confirmés par le prestataire pour ce compte et la devise CDF ; laisser une liste vide sinon. Chaque restaurant conserve son compte marchand propre. Une clé VITE serait publique : aucun identifiant marchand ne doit y être placé.

Effacer manuellement le cache de configuration si nécessaire avec le PHP compatible (`artisan config:clear`) puis faire la recette sandbox avant toute bascule en mode réel `v1`. Production : HTTPS, retour et notifications accessibles au prestataire, configuration des cookies et du proxy à contrôler. Le navigateur n’est jamais une preuve de paiement ; le contrôle serveur reste obligatoire.

La page du frontend conserve désormais son lien hébergé après initialisation, sans recharger immédiatement le parent qui le faisait disparaître. Une initialisation incertaine, un paiement annulé ou refusé restent soumis au rapprochement ; aucun nouvel encaissement automatique.

## Contrôles du complément

Après autorisation « Fais le », exécution le 23 octobre 2026 : **65 tests / 227 assertions réussis sur SQLite en mémoire ; Pint réussi**. Rapport local `.local/tests-parcours-sqlite.xml`, exclu des archives. Deux premiers échecs venaient des tests utilisant une version non rechargée depuis les valeurs par défaut SQL : ils lisent désormais `data.settings_version` depuis l’API, comme le formulaire, puis vérifient la modification et le conflit sur version obsolète. Ordre des imports des routes corrigé. Les garde-fous de base de test et `Http::preventStrayRequests()` sont restés actifs ; aucun appel marchand ni modification de la base applicative.

Frontend associé : lint, 25 tests / 5 fichiers et compilation réussis. Pas de nouvelle validation PostgreSQL, ni de recette navigateur complète, ni de paiement sandbox ou réel. Les mentions « non exécuté » ci-dessous décrivent l’état de préparation antérieur à cette autorisation.

### Constat dans le compte marchand connecté — 23 octobre 2026

Le tableau de bord puis **Espace développeur** ont été ouverts après connexion directe par le titulaire. Les deux valeurs existantes ont été copiées uniquement dans `backend/.env` pour le restaurant 1. Aucun identifiant dans les guides, kits, captures ou archives. `EASYPAY_MODE=sandbox`, `FRONTEND_URL=http://127.0.0.1:5174` et les trois réseaux confirmés sont préparés ; **EASYPAY_ENABLED=false** est conservé. Aucun fichier de cache Laravel n’a été exécuté ou contrôlé : ne pas déduire l’état du processus de la seule modification du fichier.

Points à clarifier auprès du prestataire avant activation :

- L’espace marchand indique `checking-status`, tandis que la documentation publique indique `checking-payment`. Le code conserve `checking-payment` ; aucun essai marchand ne permet encore de trancher.
- Le lien « Voir la documentation officielle » de l’espace marchand ouvre actuellement un site de transport PCE-Core, sans documentation de l’API EasyPay. Aucun identifiant ni formulaire n’y a été envoyé.
- Le jeton affiché contient une expiration déclarée au 23 octobre 2026 à 17:21:56 UTC, soit 18:21:56 à Kinshasa. Ce décodage local ne vérifie pas sa signature ni sa validité auprès du prestataire. Obtenir la procédure officielle de renouvellement ou un identifiant d’intégration adapté ; ne pas le présenter comme une clé permanente.
- La page mentionne les modes Sandbox et Live, sans sélecteur ni identification distincte des accès de test du compte. Leur fonctionnement reste à confirmer.

Les références nouvellement générées ont été ramenées à 16 caractères alphanumériques conformément à la limite de 6 à 16 caractères indiquée dans l’espace marchand. Les références antérieures et les paiements existants ne sont pas modifiés. Une assertion de non-régression a été ajoutée au test d’initialisation, sans exécution.

Après clarification et renouvellement éventuel du jeton, renseigner les valeurs courantes localement et activer uniquement le mode sandbox pour la recette. Ne pas renseigner d’URL IPN locale dans le portail : le prestataire ne peut pas joindre `127.0.0.1`. En attendant une URL HTTPS publique, la vérification manuelle côté serveur reste indispensable. Aucun changement IPN, émission de nouvelle clé, requête marchande, commande, paiement ou publication réalisé pendant cette préparation.

Commandes manuelles, dans `C:\Users\J\Desktop\projet miam miam\backend`, après préparation du PHP compatible décrite dans le README :

```powershell
& $phpMiamControle vendor/bin/pint --test
& $phpMiamControle vendor/bin/phpunit --filter=PaymentWorkflowTest
& $phpMiamControle artisan config:clear
```

Les deux premières commandes vérifient le code avec un transport HTTP simulé ; la dernière recharge la configuration au prochain démarrage applicatif et ne constitue pas une validation marchande. Ne pas lancer de paiement réel pour tester la configuration.

- `RestaurantAdministrationTest` : lecture et modification réservées au bon administrateur, versions concurrentes, horaires, rôle de gérant limité, mot de passe inchangé, révocation et compte inconnu non créé.
- `CheckoutOptionsTest` : paiement désactivé explicite, absence d’appel marchand, non-divulgation des identifiants, réseaux filtrés et comptes de restaurants distincts.
- Frontend `PaymentActions.test.tsx` : conservation du lien, compte marchand absent et redirection étrangère refusée.

Les tests existants de paiement restent nécessaires, ainsi que la recette réelle avec le prestataire. Voir le README pour les commandes PHP compatibles et les bases de test dédiées. Aucune exécution ni publication pendant ce complément.
