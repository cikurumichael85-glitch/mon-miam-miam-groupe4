# Mon Miam Miam — Bilan et limites du paiement en ligne

**ULC-ICAM · Groupe 4 · République démocratique du Congo**
**Date du bilan : 26 octobre 2026**
**Dernières observations techniques : 24 octobre 2026**
**Statut : intégration partielle ; paiement réel de bout en bout non validé.**

## 1. Objet et périmètre

Cette note présente l'état vérifié du parcours de paiement en CDF avec e-COM EasyPay. Elle distingue les contrôles applicatifs, les observations réelles et les limites externes encore non expliquées. Elle accompagne la présentation du projet ; elle ne constitue pas une attestation de paiement ou de déploiement en production de l'application.

Le 26 octobre 2026, le groupe a décidé d'arrêter le débogage EasyPay à ce stade et de poursuivre la recette des autres fonctionnalités. Aucun nouvel essai, contrôle distant ou changement de configuration n'a été effectué pour rédiger cette note. Les résultats ci-dessous sont ceux des vérifications antérieures.

## 2. Fonctionnement prévu

Le client confirme sa commande, ouvre le guichet EasyPay, choisit un moyen disponible et saisit son numéro Mobile Money. Une demande d'autorisation doit ensuite être reçue sur le téléphone selon le fonctionnement du prestataire et de l'opérateur. Le client valide lui-même dans leur parcours sécurisé. L'application doit vérifier le résultat auprès du prestataire avant de considérer la commande comme réglée.

Le montant est celui de la commande, exprimé en francs congolais, sans conversion. L'essai de commande décrit ici portait sur **14 000 FC**. L'ancien essai isolé de 100 CDF était distinct et ne remplaçait pas ce prix.

## 3. Bilan par étape

| Étape | Résultat vérifié | Limite et preuve |
| --- | --- | --- |
| Présentation des moyens | Orange Money, M-Pesa et Airtel Money sont proposés dans l'interface de l'application. | Leur affichage ne prouve pas le fonctionnement réel de chaque réseau. Le choix final dépend du guichet EasyPay. |
| Initialisation réelle | Une référence réelle a été créée pour la commande de 14 000 CDF. | Le mode API v1 a été utilisé ; la page hébergée était en v2. Cela ne prouve pas un encaissement. |
| Ouverture du guichet | Page EasyPay accessible ; montant de 14 000 CDF conservé ; logo marchand observé. | Vérification visuelle du parcours existant. |
| Saisie et soumission du numéro — point E | Après l'essai du titulaire, le formulaire a été remplacé par « En attente du payement… » avec une animation. | Réponse HTTP exacte de l'envoi Mobile Money non récupérée ; acceptation par l'opérateur inconnue. **Point non validé.** |
| Réception sur téléphone — point F | Le titulaire a déclaré n'avoir reçu aucune demande lors du dernier essai. | Réception non obtenue dans cet essai. Impossible de conclure sur tous les réseaux ou toutes les transactions. |
| Lecture du statut INIT — point G | Correction vérifiée : INIT est reconnu comme une attente lorsque les références de transaction correspondent. | Consultation réelle de la référence existante réussie ; aucun nouveau paiement créé par cette vérification. **Point validé sur ce périmètre.** |
| Autorisation et confirmation financière — point H | Aucun succès financier réel vérifié pendant la recette. | La dernière lecture applicative vérifiée restait pending et la commande unrecorded. Ce constat historique n'est pas une lecture bancaire actuelle. **Point non validé.** |

## 4. Localisation du blocage et incertitudes

Le blocage visible se produit sur le guichet EasyPay après la saisie du numéro : l'animation reste affichée et le titulaire ne reçoit pas de demande sur son téléphone. L'ouverture du guichet depuis l'application a donc été observée, mais la transmission effective au réseau Mobile Money n'a pas été prouvée.

Une observation antérieure dans Edge a montré l'erreur JavaScript `SweetAlert: 2nd argument ('undefined') is invalid`. Le code du guichet consulté pouvait appeler son affichage d'erreur avec une information absente, ce qui peut empêcher le réaffichage du formulaire. Cette erreur établit un défaut d'affichage observé ; elle n'établit pas la cause du défaut de notification.

Lors de l'essai ultérieur dans le navigateur intégré, les journaux accessibles étaient vides. Il ne faut donc pas attribuer automatiquement l'erreur précédente à ce nouvel essai, ni en déduire que la réponse HTTP était vide.

La cause exacte reste indéterminée : réponse EasyPay, configuration marchande, échange avec l'opérateur ou autre condition. Aucun élément ne permet d'attribuer la responsabilité au client, de certifier un défaut exclusif du prestataire, d'affirmer un refus bancaire ou de garantir l'absence de débit.

## 5. Contrôles applicatifs déjà réalisés

| Contrôle | Dernier résultat consigné | Portée |
| --- | --- | --- |
| Backend, après correction du point G | 94 tests / 384 assertions réussis sur SQLite puis PostgreSQL ; contrôle Pint réussi. | Cas métier et réponses externes simulées ; ces tests ne prouvent pas la réception sur téléphone. |
| Frontend, avant correction du point G | 44 tests dans 8 fichiers, lint et compilation réussis. | Navigation, gestion des états et redirections simulées. Aucun nouveau test frontend lors de G. |
| Vérification réelle de la référence existante | Statut pending reconnu ; date de vérification présente ; nombre de tentatives inchangé ; commande non réglée. | Lecture de statut uniquement, sans nouvel envoi de numéro. |

Ces résultats sont historiques. Aucun test n'a été relancé le 26 octobre pour la présente note. Les rapports techniques détaillés sont conservés localement et ne sont pas à publier avec des identifiants, références privées ou données de connexion.

## 6. Décision et règles conservées

1. Suspendre les nouveaux essais EasyPay et poursuivre les autres parties du projet, conformément à la décision du groupe.
2. Conserver l'historique des tentatives et la distinction entre test et production. Ne pas convertir une référence sandbox en référence réelle.
3. Ne jamais considérer INIT, l'animation, une redirection ou un retour navigateur comme une preuve de règlement.
4. Ne pas marquer manuellement la commande payée pour masquer le blocage ; ne pas déclencher une nouvelle tentative automatiquement.
5. Conserver les vérifications serveur des références, des états finaux et du montant/de la devise lorsqu'ils sont présents dans la réponse. Aucun PIN Mobile Money n'est demandé dans l'application.

Cette décision ne signifie pas que le service marchand a été désactivé techniquement. La rédaction de cette note n'a modifié ni la configuration privée ni les paiements existants. Le mode API production constaté antérieurement ne vaut pas validation d'une mise en service commerciale.

## 7. Présentation au professeur

> Le parcours de notre application ouvre le guichet EasyPay avec le montant de la commande. Pour l'essai réel de 14 000 FC, EasyPay est resté en attente après saisie du numéro et aucune demande n'a été reçue sur le téléphone. La lecture serveur du statut d'attente a été corrigée et testée, mais le paiement réel complet n'est pas validé. Nous avons documenté cette limite et suspendu le débogage pour poursuivre la recette des autres fonctionnalités.

Une démonstration doit s'arrêter avant toute nouvelle demande réelle non autorisée. Présenter séparément les tests simulés réussis et la limite constatée sur téléphone ; aucune simulation ne doit être annoncée comme un règlement réel.

## 8. Conditions d'une reprise ultérieure

Une reprise nécessitera de retrouver le statut marchand de la tentative existante avant toute relance, d'observer la réponse exacte à l'envoi du numéro et, si nécessaire, d'obtenir du support EasyPay une explication sur la transmission à l'opérateur. Toute nouvelle demande réelle devra être explicitement encadrée par le titulaire.

La validation complète exigera une demande effectivement reçue, une autorisation effectuée par le titulaire sur son téléphone et une confirmation financière vérifiée par le serveur sur la même commande et le même montant. Si l'un de ces éléments manque, la fonction restera partiellement validée.

## 9. Suivi du travail et pièces

- **Jira :** reporter ultérieurement la limite dans le ticket de paiement existant, avec cette note et les critères restant à vérifier ; ne pas clôturer ce ticket comme paiement complet. Aucune modification Jira effectuée pour cette étape.
- **Git et GitHub :** documentation préparée localement ; aucun commit, push ou dépôt effectué. Publication soumise à la consigne du groupe.
- **Pièce historique :** [journal du diagnostic du 24 octobre](Diagnostic_EasyPay_Sandbox_24_10_2026.md). Il contient des étapes sandbox antérieures ; la présente note fait référence pour le bilan arrêté au 26 octobre.
- **Contrôles détaillés :** [vérification backend](../backend/docs/VERIFICATION_LOT_1.md) et [parcours frontend](../frontend/docs/PARCOURS_COMMANDE_ROLES_PAIEMENT.md). Les mentions anciennes doivent être lues avec leur date et leur périmètre.

**Conclusion :** accès au guichet et lecture du statut d'attente vérifiés ; transmission opérateur, réception sur téléphone et confirmation financière réelle non validées. Point 1 documentaire terminé ; paiement complet non terminé.
