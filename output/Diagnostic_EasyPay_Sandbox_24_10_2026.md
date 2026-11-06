# Diagnostic EasyPay — attente du paiement Mobile Money

> **Journal historique du 24 octobre 2026.** Le débogage est arrêté à la demande du groupe le 26 octobre. Lire en priorité le [bilan actuel du paiement](Mon_Miam_Miam_Bilan_Paiement_26_10_2026.md). Les instructions de préparation Network ci-dessous ne sont plus une action en attente. L'essai ultérieur dans le navigateur intégré est resté en attente sans journaux exploitables et le titulaire a confirmé n'avoir reçu aucune demande. Les parties sandbox ne décrivent pas la dernière tentative réelle ; aucun succès financier n'est établi.

## Point E en cours — réponse à la soumission du numéro

Observation directe de la page Edge déjà ouverte en production (v2), pour la commande de 14 000 CDF : En attente du payement. Ouverture du journal Console sans rechargement ni soumission : une entrée de journal sans texte exploitable, puis Uncaught SweetAlert: 2nd argument ('undefined') is invalid. Le script du guichet appelle alertError(response.error) après certaines réponses où code n'est pas 1 ; si error est absent, cette exception empêche le réaffichage du formulaire. Ce constat identifie une défaillance d'affichage chez le prestataire mais ne fournit pas le corps HTTP original ni la preuve d'envoi à l'opérateur.

Le panneau Network doit être préparé pour lire la réponse exacte de init-payment. Sélection automatique empêchée par la géométrie d'entrée indisponible, puis intervention utilisateur et fenêtre réduite ; aucune nouvelle demande de paiement déclenchée. Titulaire invité à remettre la fenêtre au premier plan et sélectionner Network sans recharger ni cliquer Continuer. Point E non validé ; F et H non commencés. Ne pas qualifier la réponse HTTP de vide à partir du seul journal Console sans texte.

## Point G résolu — statut INIT correctement reconnu

Sur signal du titulaire, correction et tests limités au point G. Pour INIT uniquement, les références de transaction concordantes autorisent un état pending sans exiger le reçu final de paiement. Les états finaux, montants/devises et contrôles de concurrence restent stricts. Résultats : 94 tests / 384 assertions sur SQLite et PostgreSQL, Pint réussi.

Une consultation réelle de la seule référence déjà existante réussit désormais : pending, date de vérification enregistrée, commande encore non réglée, aucune tentative supplémentaire. Aucun envoi de numéro ou demande de débit. Les observations d'échec de la lecture INIT ci-dessous sont donc historiques. Envoi opérateur E, réception F et confirmation financière H restent à traiter un à un, et ne sont pas certifiés par cette correction.

## Dernier constat : commande 6 réellement initialisée en production

Après les clics du titulaire, lecture locale : ancienne tentative 5 sandbox inactive, nouvelle tentative 6 v1 active et pending, montant 14 000 CDF et référence prestataire présente. La commande reste unrecorded ; verified_at absent et aucun événement de confirmation. La création et la conservation d'historique ont donc fonctionné réellement, sans nouvelle initialisation par l'assistant.

Consultation seule du statut de la référence courante le 24 octobre à 18 h 52 UTC : HTTP 200, payment.status INIT. transaction.order_ref et transaction.reference correspondent à la commande et à la référence initialisée, mais payment.reference ne correspond pas à cette référence. L'intégration exige actuellement cette dernière correspondance avant de traiter le statut : elle refuse donc la réponse et ne met pas verified_at à jour. Ce refus ne prouve pas un paiement reçu sur le téléphone ni un échec bancaire ; c'est une condition de lecture trop précoce à examiner pour INIT. La confirmation SUCCESS devra conserver ses contrôles stricts.

Étapes non prouvées sur cette dernière tentative : réception de la demande opérateur, validation sur le téléphone et confirmation financière. Il reste à observer la réponse exacte de la soumission du numéro chez EasyPay. Les anciens résultats HTTP 500 concernaient le sandbox et ne doivent pas être attribués à cette référence courante, dont le contrôle détaillé répond HTTP 200. Aucune nouvelle demande de paiement ou sur téléphone pendant cette consultation. Rapport privé sans référence exposée : .local/current-production-status-readonly.json.

## Complément : essai réel plafonné à 100 CDF

Après accord explicite du titulaire sur le numéro et un plafond de 100 CDF frais compris, un seul essai réel distinct a été préparé. Une référence d'initialisation a été reçue en mode v1 ; la page hébergée a redirigé vers v2. Total affiché avant la demande : 100 CDF, frais 0 CDF. Aucune configuration globale de production ni commande applicative modifiée. L'action de déclenchement financier a été laissée au titulaire ; la page est ensuite passée en attente. Le titulaire confirme ne rien avoir reçu sur son téléphone.

Contrôles serveur sur la seule référence existante : v2/checking-status répond HTTP 200, code 1, status 0 ; v1/checking-payment répond HTTP 200 avec payment.status INIT. Cette réponse réelle se distingue de l'erreur 500 observée auparavant en sandbox. Aucun statut SUCCESS observé et aucun débit confirmé. Les journaux du guichet contiennent `SweetAlert: 2nd argument ('undefined') is invalid`. Le script de la page appelle alertError(response.error) après certaines réponses d'échec, avant de réafficher le formulaire : une erreur dans cet affichage peut donc laisser l'animation visible. Le motif précis renvoyé par l'initialisation Mobile Money n'a pas été récupéré ; ni montant minimum, ni activation opérateur, ni insuffisance de solde ne sont établis par ces éléments.

Demande complémentaire au support : vérifier cette référence réelle dans le canal marchand privé, fournir le motif d'échec de l'appel Mobile Money et confirmer le montant minimal accepté ainsi que l'activation effective du réseau pour ce marchand. Corriger également l'affichage du guichet lorsqu'une réponse ne contient pas error. Ne pas relancer cette tentative ni augmenter le montant sans rapprochement et accord du titulaire. Aucune communication au support envoyée automatiquement. Référence conservée uniquement dans un rapport local privé.

Mon Miam Miam · Groupe 4 · ULC-ICAM · RDC

Date : 24 octobre 2026. Vérification de statut enregistrée à 12 h 35, heure de Kinshasa.

## Résultat constaté

La commande locale n° 6, d'un montant de 14 000 CDF, possède une référence EasyPay. Son état applicatif reste en attente ; aucun règlement confirmé. L'environnement utilisé est sandbox. Le chargement du guichet et l'initialisation réussis ne prouvent pas un paiement Mobile Money réussi.

## Vérifications effectuées

| Élément | Résultat |
| --- | --- |
| Connexion HTTPS PHP | Fonctionnelle après ajout des autorités de certification ; vérification TLS maintenue |
| Initialisation de la dernière tentative sandbox | Référence reçue ; même commande et même ligne de paiement conservées |
| Guichet hébergé EasyPay | HTTP 200 ; interface Mobile Money présente |
| POST sandbox/payment/[référence]/checking-status | HTTP 200, code 1, status 0 ; aucun statut textuel de paiement confirmé |
| POST sandbox/payment/[référence]/checking-payment | HTTP 500 ; aucune confirmation détaillée exploitable |
| Lecture du JavaScript servi par le guichet | Interrogation du statut toutes les 500 ms après une réponse d'initialisation Mobile Money code 1 ; boucle sans limite de temps visible dans ce traitement |

Le statut numérique n'est pas transformé en succès dans l'application. L'erreur 500 du contrôle détaillé et l'attente du guichet sont deux observations ; leur lien causal n'est pas établi. Aucune conclusion sur le débit réel d'un portefeuille ne peut être déduite de ces seules réponses.

Documentation consultée : https://developper.e-com-easypay.com/. Elle décrit sandbox comme mode de développement et la confirmation détaillée avec transaction.order_ref, les références concordantes et payment.status. Aucun numéro de test ou mécanisme de simulation Mobile Money n'a été identifié dans la page consultée. Le JavaScript du guichet a été examiné en lecture seule ; aucune requête d'initialisation Mobile Money ni de nouvelle demande de débit effectuée pendant ce diagnostic.

## Demande à transmettre au support marchand

Bonjour,

Nous intégrons EasyPay à notre application Mon Miam Miam en CDF. En sandbox, l'initialisation renvoie une référence et le guichet s'affiche. Après le parcours Mobile Money, il reste sur « En attente du payement ».

Pour la tentative concernée, checking-status répond HTTP 200 avec {"code":1,"status":0}, tandis que checking-payment répond HTTP 500. Contrôle effectué le 24 octobre 2026 à 11 h 35 UTC, pour un montant de 14 000 CDF.

Pouvez-vous vérifier cette tentative dans notre espace marchand, préciser si une demande opérateur a été émise et nous communiquer la procédure officielle de test Mobile Money sandbox : numéros de test, comportement attendu sur le téléphone, délais et résultat final attendu ? Merci de confirmer également les prérequis du compte pour Orange Money, M-Pesa et Airtel Money en CDF et le comportement attendu de checking-payment avant règlement.

Nous vous fournirons la référence de la transaction depuis notre espace marchand dans votre canal de support privé. Aucun token ni clé secrète ne sera transmis.

## Suite attendue

Conserver la tentative en attente sans recréer de paiement. Ne pas activer la production pour contourner le blocage. Après la réponse du prestataire, appliquer ses consignes documentées puis vérifier le paiement depuis le serveur avant de marquer la commande payée. Aucun message n'a été envoyé au support automatiquement.
