# Complément logique - Fidélité et parrainage

Groupe 4 - ULC-ICAM, RDC - 21 octobre 2026 - Proposition v0.1.

Ces huit tables complètent les 15 tables du socle. Elles préparent les critères de MMM-25 sans imposer les barèmes illustratifs du CDC ni décider silencieusement d'une portée locale ou globale. Elles ne constituent pas des migrations exécutées. Aucun programme n'est activé tant que sa politique n'est pas approuvée.

| Table | Champs et liens proposés | Contraintes et contrôles |
|---|---|---|
| loyalty_programs | id PK ; scope restaurant/platform ; restaurant_id FK nullable ; active ; policy_version ; rules JSON ; approved_by FK users nullable ; approved_at nullable | scope=restaurant exige un restaurant ; scope=platform exige restaurant nul et autorité globale. Un seul programme actif par portée. Aucun barème activé sans approbation. |
| loyalty_accounts | id PK ; program_id FK ; user_id FK ; cached_balance integer ; version integer | Unique (program_id,user_id). Solde de référence = somme du journal ; cache vérifiable, jamais modifiable par le client. Verrouiller le compte avant consommation. |
| loyalty_entries | id PK ; account_id FK ; order_id FK nullable ; kind earn/redeem/expire/reverse/manual ; delta integer signé ; source_event_key ; policy_version ; credit_expires_at nullable ; reversed_entry_id FK nullable ; actor_id FK nullable ; occurred_at | delta non nul ; unique (account_id,source_event_key). Une contre-écriture référence l'original, sans le modifier. Commande éligible, propriétaire et portée vérifiés. Pas de double crédit pour un même événement. |
| loyalty_allocations | id PK ; credit_entry_id FK entries ; debit_entry_id FK entries ; points integer positif | Unique (credit_entry_id,debit_entry_id) ; même compte ; crédit positif et débit négatif ; allocations limitées au reliquat du crédit. FIFO proposé, à valider. Permet de consommer/expirer chaque lot sans compter deux fois les mêmes points. |
| referral_programs | id PK ; scope restaurant/platform ; restaurant_id FK nullable ; target_loyalty_program_id FK ; active ; policy_version ; reward_rules JSON ; approved_by FK nullable ; approved_at nullable | Même règle de portée que loyalty_programs ; programme de points compatible avec la portée. Récompense et définition de première commande réussie à valider. |
| referral_codes | id PK ; program_id FK ; owner_id FK users ; code varchar unique global ; active | Unique (program_id,owner_id) proposé. Propriétaire et programme immuables après utilisation. Aucun code réattribué à une autre personne. |
| referrals | id PK ; program_id FK ; code_id FK ; referred_user_id FK users ; status pending/rewarded/cancelled ; qualifying_order_id FK nullable ; created_at | Unique (program_id,referred_user_id). Le parrain vient du propriétaire du code ; auto-parrainage interdit. Code actif et même programme. Éligibilité de la commande et première réussite vérifiées côté serveur avec verrouillage. |
| referral_rewards | id PK ; referral_id FK unique ; loyalty_entry_id FK unique ; qualifying_order_id FK ; points_snapshot integer positif ; policy_version ; rewarded_at | Une récompense par lien de parrainage ; compte de points appartenant au parrain ; commande du filleul, restaurant et portée compatibles. Récompense et écriture de points enregistrées dans la même transaction. |

## Mouvements et concurrence

Les règles approuvées définissent le déclencheur du crédit, la dépense admissible, les arrondis, le plafonnement, la durée d'expiration et le cumul des offres. L'exemple du CDC 1 000 FC dépensés = 1 point et 15 points = 1 000 FC de remise reste illustratif ; aucun taux de change n'est appliqué.

Le devis peut proposer une utilisation de points, mais ne garantit pas leur réservation. À la confirmation, verrouiller le compte et les lots concernés, revérifier le solde et enregistrer consommation, allocations et commande de façon atomique. Si le solde a changé, refaire approuver le devis. Un rejeu idempotent ne déduit pas les points à nouveau.

Un crédit de commande n'intervient qu'une fois au déclencheur approuvé. L'expiration ne débite que le reliquat des lots arrivés à échéance ; les points déjà utilisés ne sont pas expirés une seconde fois. La clé de l'événement d'expiration et les allocations empêchent les doublons.

Un remboursement/une annulation ne modifie jamais une écriture passée : créer les compensations décidées. Le traitement d'une récompense déjà dépensée et d'un solde insuffisant est un arbitrage explicite ; ne pas inventer une dette de points ni un solde négatif automatique. Les règles de rétablissement des lots et de leur date d'expiration doivent aussi être validées.

Le bonus de parrainage est créé une seule fois lorsque la première commande du filleul satisfait le programme. Une transition répétée, un événement reçu deux fois ou deux traitements simultanés ne créent pas deux récompenses. Le restaurant du filleul est contrôlé si la portée est locale. Le statut de paiement requis, le cas d'un paiement non intégré et le sort d'une récompense après remboursement restent à décider.

## API proposées pour un prochain complément OpenAPI

Ces opérations P1 ne sont pas encore incluses dans le contrat OpenAPI P0. Les identifiants de programme sont choisis dans la liste autorisée par le serveur ; un client ne peut ni créditer un compte ni s'attribuer une récompense.

| Opération | Entrée / sortie proposées | Autorisation |
|---|---|---|
| GET /me/loyalty-accounts | Liste des programmes accessibles, portée, solde et version ; aucun compte d'un autre utilisateur. | Propriétaire connecté. |
| GET /me/loyalty-accounts/{accountId}/entries | Journal paginé : type, delta, date et solde après opération si calculé. | Propriétaire du compte. |
| POST /restaurants/{restaurantId}/order-quotes, extension | loyalty_account_id et points_requested entiers ; retour de la remise proposée et des versions nécessaires. | Compte appartenant au client et programme compatible. |
| GET /me/referral-codes | Codes personnels par programme actif et portée, sans données privées des filleuls. | Propriétaire connecté. |
| POST /me/referrals | code, lors du moment de saisie approuvé ; retourne statut du lien sans attribuer de bonus immédiat. | Filleul connecté, premier rattachement éligible. |
| GET /me/referrals | Statistiques et statuts du parrain ; identité minimale des filleuls selon politique à approuver. | Parrain connecté. |
| POST /restaurants/{restaurantId}/loyalty-adjustments | account_id, delta signé, motif, expected_version et clé d'idempotence ; retourne écriture auditée. | Administrateur local, programme local de son restaurant uniquement. Le programme global exige une autorité de plateforme distincte. |

Erreurs attendues : 403/404 pour un programme ou compte interdit ; 409 pour une version, un lien de parrainage déjà fixé ou un solde modifié ; 422 pour une quantité invalide, un code non admissible ou une auto-invitation. Les messages ne révèlent pas de données privées du titulaire d'un code.

## Recette complémentaire à préparer

1. Une commande terminée deux fois ne produit qu'un crédit et une récompense de parrainage au maximum.
2. Deux consommations simultanées ne dépensent pas le même solde deux fois.
3. L'expiration ne débite que les points restant dans le lot concerné.
4. Un programme local A ne s'applique pas à une commande B ; un programme global n'est admis que s'il est explicitement actif et approuvé.
5. Un client ne lit pas le journal d'un autre et ne choisit pas librement le bénéficiaire d'une récompense.
6. Rejet du parrainage de soi-même ; une seule association par filleul et programme.
7. Compensation tracée après annulation/remboursement conformément à la règle approuvée.

## Paiement, s'il est retenu

Prévoir `payments(id, order_id, restaurant_id, provider, external_reference unique, amount, currency CDF, state, recorded_at)` et `payment_events(id, payment_id, provider_event_id unique, verified_at, payload_digest, outcome)`. Contrôler les références composées de restaurant, la correspondance du montant et de la commande, la signature/confirmation serveur et l'unicité des événements. Ne pas conserver les données de carte ni faire confiance à un simple retour du navigateur. Le prestataire, les états et le rapprochement seront spécifiés après l'arbitrage de périmètre.
