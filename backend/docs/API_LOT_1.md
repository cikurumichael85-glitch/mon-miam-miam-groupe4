# Contrat backend cumulatif — Mon Miam Miam

Groupe 4 · ULC-ICAM · RDC · 22 octobre 2026. Nom de fichier historique conservé.
**Résultats historiques avant le complément des parcours : 58 tests / 182 assertions réussis sur SQLite et PostgreSQL, neuf vérifications HTTP conformes.** Les nouvelles routes et restrictions de rôles ne sont pas encore exécutées ni testées. Lire [le complément actuel](COMPLEMENT_PARCOURS_ROLES_PAIEMENT.md). Le contrat OpenAPI de conception antérieur reste une proposition et n’est pas automatiquement synchronisé.

## 1. Origine des règles

| Origine | Contenu |
| --- | --- |
| Cahier des charges | Authentification, menu, commande, arrivée/livraison, rôles, réclamations approuvées, statistiques, fidélité/parrainage, avis, affiches, événements/jeux, classement, protection des données ; paiement mentionné aussi comme bonus |
| Demandes du groupe | ULC-ICAM/RDC, monnaie CDF sans conversion, plusieurs restaurants dès V1, EasyPay choisi, répartition locale équilibrée, aucune publication actuellement |
| Propositions d’implémentation | Routes ci-dessous, sessions natives, devis 10 minutes, maximum 50 lignes et 99 unités, maximum 999 999 999,99 FC par commande, rôles locaux, quiz, points en lots FIFO, règles d’annulation et consentement au classement |

## 2. Transport et authentification

Préfixe `/api/v1`. Envoyer `Accept: application/json`, `Content-Type: application/json` et les cookies de session. Initialiser avec `GET /auth/csrf-cookie`, puis envoyer le jeton retourné dans `X-CSRF-TOKEN` pour les mutations. Le cookie `XSRF-TOKEN` peut aussi être décodé et envoyé dans `X-XSRF-TOKEN`. Réactualiser après connexion/déconnexion, qui régénèrent la session.

Exemple navigateur (à adapter au frontend ; pas exécuté ici) :

```javascript
const base = "http://127.0.0.1:8010/api/v1";
const initial = await fetch(base + "/auth/csrf-cookie", { credentials: "include" });
const csrf = await initial.json();
const response = await fetch(base + "/auth/login", {
  method: "POST",
  credentials: "include",
  headers: {
    Accept: "application/json",
    "Content-Type": "application/json",
    "X-CSRF-TOKEN": csrf.csrf_token
  },
  body: JSON.stringify({ email, password })
});
```

Le mot de passe ci-dessus provient du formulaire local, jamais d’un fichier de configuration versionné. CORS autorise uniquement `FRONTEND_URL`, avec credentials. En production, frontend et API doivent être organisés sur un même site compatible avec les cookies SameSite ; une installation sur domaines sans lien exige une configuration et une recette supplémentaires.

Le modèle public de restaurant garde ses identifiants sous forme de chaînes. Les nouvelles réponses Eloquent utilisent des entiers pour leurs identifiants ; les UUID sont des chaînes. Le frontend doit suivre ce contrat local, pas supposer une stricte identité avec les exemples de conception.

La liste publique de restaurants conserve `{data,meta:{page,per_page,total}}`. Les nouvelles listes utilisent la pagination Laravel (`data,current_page,last_page,total,...`). Les réponses unitaires utilisent `{data:...}`, sauf messages. Les erreurs Laravel renvoient `message` et, pour les validations, `errors`. La première liste conserve son enveloppe historique `error.code/error.fields`.

Codes usuels : 401 non connecté ; 403 compte/rôle refusé ; 404 ressource absente ou autre propriétaire/restaurant ; 409 version, devis ou état incompatible ; 419 protection de requête ; 422 entrée invalide ; 429 limitation ; 502 réponse de paiement non confirmée ; 503 paiement non configuré.

## 3. Inventaire des routes

Le rôle indiqué dans l’action est contrôlé dans le contrôleur. Un administrateur local n’est pas un administrateur de plateforme.

| Méthode | Route après /api/v1 | Action |
| --- | --- | --- |
| GET | `/restaurants` | Liste publique |
| GET | `/restaurants/{restaurant}/menu` | Menu public |
| GET | `/restaurants/{restaurant}/checkout-options` | Horaires publics et disponibilité du paiement, sans identifiants marchands |
| GET | `/restaurants/{restaurant}/campaigns` | Affiches et événements actifs |
| GET | `/restaurants/{restaurant}/reviews` | Avis publiés |
| GET | `/restaurants/{restaurant}/ranking` | Classement sur consentement |
| GET | `/auth/csrf-cookie` | Initialiser la session et le jeton CSRF |
| POST | `/auth/register` | Inscription sans rôle |
| POST | `/auth/login` | Connexion |
| POST | `/auth/forgot-password` | Demande de récupération |
| POST | `/auth/reset-password` | Nouveau mot de passe |
| GET | `/me` | Profil et rattachements |
| POST | `/auth/logout` | Déconnexion |
| PATCH | `/me/profile` | Profil et consentements |
| POST | `/me/data-export` | Export personnel protégé par mot de passe |
| POST | `/me/erasure-requests` | Demander l’effacement |
| POST | `/restaurants/{restaurant}/order-quotes` | Créer un devis calculé par le serveur |
| POST | `/restaurants/{restaurant}/orders` | Confirmer avec idempotence |
| GET | `/me/orders` | Historique personnel |
| GET | `/me/orders/{order}` | Détail personnel |
| GET | `/restaurants/{restaurant}/orders` | Commandes du restaurant / personnel |
| GET | `/restaurants/{restaurant}/orders/{order}` | Détail / personnel local |
| POST | `/restaurants/{restaurant}/orders/{order}/transitions` | Transition contrôlée |
| POST | `/restaurants/{restaurant}/categories` | Créer catégorie / direction |
| POST | `/restaurants/{restaurant}/items` | Créer article / direction |
| PATCH | `/restaurants/{restaurant}/items/{item}` | Modifier article / direction |
| PATCH | `/restaurants/{restaurant}/items/{item}/availability` | Disponibilité / personnel |
| POST | `/restaurants/{restaurant}/items/{item}/archive` | Archiver article / direction |
| GET | `/restaurants/{restaurant}/settings` | Informations et horaires / administrateur local |
| PATCH | `/restaurants/{restaurant}/settings` | Paramètres / administrateur local |
| PATCH | `/restaurants/{restaurant}/hours` | Horaires / administrateur local |
| POST | `/restaurants/{restaurant}/managers` | Rattacher ou révoquer un gérant existant par e-mail / administrateur local |
| GET | `/restaurants/{restaurant}/employees` | Rattachements / direction |
| POST | `/restaurants/{restaurant}/employees` | Créer employé / direction |
| PATCH | `/restaurants/{restaurant}/employees/{user}` | Révoquer ou réactiver employé |
| POST | `/me/orders/{order}/claims` | Déposer réclamation |
| GET | `/me/claims` | Réclamations personnelles |
| GET | `/me/claims/{claim}` | Réponses approuvées |
| GET | `/restaurants/{restaurant}/claims` | Réclamations / personnel |
| GET | `/restaurants/{restaurant}/claims/{claim}` | Détail de traitement |
| POST | `/restaurants/{restaurant}/claims/{claim}/replies` | Soumettre réponse |
| POST | `/restaurants/{restaurant}/claims/{claim}/replies/{reply}/review` | Valider par une autre personne / direction |
| GET | `/restaurants/{restaurant}/statistics` | Statistiques locales |
| POST | `/restaurants/{restaurant}/loyalty-program` | Approuver barème local |
| GET | `/me/loyalty-accounts` | Soldes personnels |
| GET | `/me/loyalty-accounts/{account}/entries` | Journal personnel |
| POST | `/restaurants/{restaurant}/referral-code` | Code personnel |
| POST | `/me/referrals` | Rattacher un code |
| GET | `/me/referrals` | Suivi anonyme des filleuls |
| POST | `/restaurants/{restaurant}/campaigns` | Publier affiche / direction |
| POST | `/restaurants/{restaurant}/campaigns/{campaign}/deactivate` | Désactiver publication / direction |
| POST | `/restaurants/{restaurant}/campaigns/{campaign}/participate` | Participer à un événement ou quiz |
| POST | `/me/orders/{order}/review` | Avis après livraison ou service |
| POST | `/restaurants/{restaurant}/reviews/{review}/hide` | Modération / direction |
| POST | `/me/orders/{order}/payment` | Initialiser EasyPay |
| POST | `/me/orders/{order}/payment/verify` | Vérifier EasyPay côté serveur |
| POST | `/platform/restaurants` | Créer restaurant / plateforme |
| PATCH | `/platform/restaurants/{restaurant}` | Activer restaurant / plateforme |
| POST | `/platform/restaurants/{restaurant}/managers` | Attribuer direction / plateforme |
| GET | `/platform/statistics` | Statistiques globales / plateforme |
| POST | `/platform/loyalty-program` | Barème global / plateforme |
| GET | `/platform/privacy-requests` | Instruction des demandes / plateforme |
| PATCH | `/platform/privacy-requests/{privacyRequest}` | Instruction de la demande / plateforme |
| POST | `/platform/privacy-requests/{privacyRequest}/anonymize` | Anonymiser après instruction et durée approuvée |
| POST | `/platform/payments/{payment}/reconcile` | Rapprocher une référence connue |
| POST | `/payments/easypay/ipn` | Notification suivie d’une vérification serveur |

## 4. Configuration du restaurant et catalogue

Créer le restaurant via la plateforme : `name,address,timezone,delivery_enabled,delivery_fee`. Le statut initial est inactif. PATCH plateforme ajoute `status` et `expected_version`. PATCH paramètres locaux utilise les mêmes champs sauf statut. Horaires : `expected_version,hours:[{weekday,opens_at,closes_at}]`, lundi=1, dimanche=7, heures `HH:mm`. Les créneaux qui traversent minuit doivent être séparés. Les chevauchements sont refusés.

Catégorie : `name,position?`. Article : `category_id,name,description?,price,available?,is_daily?`. `price` est une chaîne décimale, par exemple `"2500.50"`. PATCH article est un remplacement de ces champs métier et exige `expected_version`. Disponibilité exige `available,expected_version` et accepte `is_daily`. Archivage exige seulement `expected_version`.

Le gérant et l’administrateur local gèrent le catalogue ; l’employé peut modifier disponibilité et plat du jour. Les catégories ne sont pas supprimées dans cette version : les articles sont archivés pour préserver l’historique.

## 5. Commandes

Devis immédiat sur place, sans date saisie par le client :

```json
{
  "items": [{"item_id": 1, "quantity": 2}],
  "service_mode": "on_site",
  "points": 0
}
```

Pour planifier une arrivée, ajouter `arrival_at` au format ISO avec décalage horaire, à une date future et au plus sept jours. L'absence de ce champ sur place signifie Commander maintenant : le serveur contrôle son heure courante dans le fuseau du restaurant. Pour livraison, ne pas transmettre `arrival_at`, ajouter `delivery: {address,phone,instructions?}` et utiliser `service_mode: "delivery"`. L’heure courante du restaurant doit être dans un créneau ouvert pour une commande immédiate ; une arrivée planifiée est contrôlée sur son propre créneau. Le tarif doit avoir été renseigné explicitement ; un tarif de zéro peut être choisi par la direction.

Réponse : UUID du devis, expiration et `payload` avec lignes, versions, sous-total, remise de points, frais et total. L’adresse de livraison est privée. Les quantités, prix et disponibilités sont contrôlés par le serveur.

Confirmation : `{quote_id,idempotency_key}`, tous deux UUID. La clé est propre au client et doit être conservée pour rejouer la même confirmation. Un même devis ne crée qu’une commande. La même clé avec un autre devis ou restaurant renvoie 409. Les clés ne sont pas purgées automatiquement : l’idempotence reste attachée à la commande.

Les prix, noms, montants et données de livraison sont figés à la commande. Le serveur reverrouille les données puis vérifie le devis, les points et les versions ; une erreur annule toute la transaction.

Transition : `{status,expected_version,reason?}`.
- Sur place : pending → accepted → preparing → ready → completed.
- Livraison : pending → accepted → preparing → ready → out_for_delivery → completed.
- Direction : pending → rejected ; pending/accepted → cancelled, avec motif.
- Aucun retour libre depuis un état final ; aucun passage direct de pending à completed.
- Un paiement en cours/incertain ou une commande payée bloque l’annulation tant que le rapprochement/remboursement n’est pas traité.

Le statut de service et le paiement sont indépendants. Servir une commande ne la marque pas automatiquement payée.

## 6. Réclamations, personnel, statistiques et avis

Réclamation personnelle : `subject,message`. Réponse du personnel : `body`. Décision du gérant : `decision: approved|rejected`. L’auteur d’une réponse ne peut pas l’approuver. Le client ne reçoit que les réponses approuvées.

Créer un employé : `name,email`. Le rôle est fixé à employee. Un compte existant garde son mot de passe ; un nouveau compte reçoit un lien de récupération si SMTP est configuré. Révocation/réactivation : `active`. Les gérants et administrateurs sont attribués par la plateforme : `user_id,role,active`.

Statistiques : `?period=day|week|month`. L’employé consulte week ; la direction consulte les trois. Les agrégats locaux suivent le fuseau du restaurant ; les agrégats de plateforme utilisent UTC. Les ventes concernent les commandes créées dans la période puis terminées ; le classement utilise la date de fin de service. Les totaux servis et les totaux servis payés sont distincts. Affichage en CDF, lectures périodiques possibles.

Avis : `rating` entre 1 et 5 et `comment`. Une seule publication par commande terminée, par son propriétaire. La direction peut masquer un avis avec un motif de modération ; les commentaires doivent être rendus comme texte dans React.

## 7. Fidélité et parrainage

Créer/approuver un programme : `active,approval:true,rules` avec les six champs :
`earn_amount` (chaîne CDF, au moins 1 FC), `redeem_points` (entier positif), `redeem_value` (chaîne CDF positive), `referral_points`, `expiry_days`, `require_paid` (booléen).
Aucun exemple de barème du CDC n’est activé automatiquement. La politique complète, dont les règles suivantes, doit être relue avant approbation :

- Programme local prioritaire sur le global ; une seule portée appliquée à une commande.
- Crédit après commande terminée, avec paiement confirmé également exigé si require_paid=true.
- Points calculés par division entière des articles après remise, hors livraison ; aucun arrondi supérieur.
- Utilisation par multiples de redeem_points, remise plafonnée par le sous-total ; aucun solde négatif.
- Consommation des lots par ordre de création (FIFO) ; expiration du reliquat uniquement.
- Annulation avant service : contre-écritures restituant les lots avec leur expiration originale.
- Un programme utilisé garde un barème immuable ; l’activation peut changer. Un changement de barème nécessite une migration de politique ultérieure.
- Le parrainage est rattaché avant toute commande de ce programme. Pas d’auto-parrainage, pas de changement de parrain après rattachement. Un bonus au premier succès éligible, jamais un bonus par notification.

L’approbation de programme local relève de la direction locale, le global relève de la plateforme. Les opérations sensibles verrouillent le programme puis le compte de points. Le journal et les allocations constituent les preuves ; un champ de solde modifiable par le client n’existe pas.

## 8. Affiches et jeu proposé

Publication : `kind: promotion|event|quiz,title,description,poster_url?,starts_at,ends_at,active`. Les dates incluent le décalage horaire. Le serveur stocke un lien HTTPS d’affiche, il ne télécharge pas le fichier.

Pour quiz, ajouter `rules:{question,choices,answer_index,reward_points,max_winners}`. La bonne réponse n’est jamais exposée dans le catalogue public. Une tentative par compte ; la première réponse reste définitive. Un plafond de gagnants est verrouillé pendant la participation. Un événement ordinaire enregistre une inscription sans prix automatique.

Une récompense en points exige un programme local approuvé. Les règles d’une publication sont figées ; pour corriger une affiche ou un quiz, désactiver l’ancien et créer un nouveau. C’est une proposition de fonctionnement à valider, pas un jeu imposé par le professeur.

## 9. EasyPay : contrat lu et limites

Source : [EasyPay — documentation officielle](https://developper.e-com-easypay.com/), lue dans le navigateur le 22/10/2026. La page annonce une mise à jour au 12/10/2021.

- Initialisation : POST `https://www.e-com-easypay.com/{sandbox|v1}/payment/initialization?cid=...&token=...`.
- Corps : order_ref généré côté serveur, amount décimal exact, currency=CDF, customer_name, description, language, channels et trois URL de retour. L’exemple PHP officiel utilise `channels` alors que son tableau indique `channel` : le code suit l’exemple et cette différence est à vérifier en sandbox.
- Réponse code=1 et reference : initialisation seulement.
- Redirection : GET même chemin avec `?reference=...`.
- Confirmation serveur : POST `.../payment/{reference}/checking-payment`.
- Le contrat retourne transaction.order_ref, transaction.reference, payment.reference et payment.status (SUCCESS, CANCELED ou DECLINED).
- L’IPN fourni par le prestataire déclenche la même vérification serveur. Aucune signature d’IPN n’est documentée sur cette page : aucune signature fictive n’est implémentée.

L’application conserve le montant CDF envoyé et la référence reçue, puis rapproche les références de la réponse HTTPS. Le contrat publié ne fournit pas montant et devise au contrôle : ils sont comparés s’ils sont présents, sinon l’association repose sur l’initialisation serveur immuable et les références rapprochées. **Faire confirmer ce contrat et réaliser la recette marchand avant production.**

Un délai réseau d’initialisation laisse un état unknown. Une nouvelle demande n’émet pas un second encaissement. La plateforme peut renseigner une référence retrouvée chez le marchand via `/platform/payments/{payment}/reconcile`, avec `reference` ; le serveur vérifie la correspondance avant de l’adopter.

Un succès reçu après une annulation est placé en review pour rapprochement, sans réouvrir la commande. Aucun remboursement automatique n’est codé faute de protocole documenté. Aucun paiement réel n’a été effectué pendant ce travail.

## 10. Données personnelles et limites

Profil : `name,phone,delivery_address?,ranking_opt_in,public_alias?,analytics_consent`. Le classement est désactivé par défaut. Le consentement analytique enregistré ne remplace pas la bannière de consentement et le blocage des scripts dans le frontend.

Export et demande d’effacement : champ `password`, vérifié contre le compte connecté. Un responsable de plateforme instruit la demande avec `status: in_review|rejected,resolution`. L’anonymisation demande `password,confirm:true`, un dossier in_review, une durée PRIVACY_RETENTION_DAYS explicitement approuvée et l’absence de commandes récentes/en cours ou paiements incertains.

La durée n’est pas une règle légale préremplie. La procédure masque les coordonnées, désactive les accès et anonymise les textes ciblés, tout en conservant les relations et montants. Les sauvegardes, journaux, historiques en texte libre et données déjà transmises au prestataire doivent faire l’objet d’une procédure distincte.

## 11. Écarts connus et recette

L’interface React n’a pas été raccordée à ces nouvelles routes. Les sessions natives remplacent la proposition Sanctum pour cette SPA. Des identifiants numériques et la pagination Laravel sont utilisés dans les nouvelles réponses. Les tables de fidélité regroupent règles de parrainage et règles de points dans un programme cohérent ; les comptes et journaux restent séparés.

Aucune charge concurrente ni aucun appel marchand réel n’a été exécuté. Les tests préparés ne prouvent donc pas encore l’absence de défaut. Tester notamment les restaurations de points expirés, les accès révoqués pendant une opération, deux connexions simultanées, un compte marchand modifié et les procédures de conservation.


## Complément du 26 octobre 2026 — lecture du programme de fidélité local

GET /api/v1/restaurants/{restaurant}/loyalty-program, sous session authentifiée, compte actif et droit local gérant ou administrateur. Réponse data=null si aucun programme local ; sinon active (booléen), version (entier), rules (objet décodé) et approved_at. Lecture sans création ni choix du programme global pour édition. Réponse privée no-store par le middleware existant.

POST sur la même URL conserve son contrat : active, approval acceptée et rules validées ; pas de barème activé par défaut. Une politique utilisée ne peut pas changer ses règles (409), mais peut être désactivée à règles identiques. Le frontend propose cet écran dans Fidélité de l'espace de gestion. Le journal des mouvements utilise occurred_at et non created_at. Voir RECETTE_REGLES_METIER.md pour les preuves et les décisions commerciales encore à valider.
