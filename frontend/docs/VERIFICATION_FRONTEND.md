# État actuel — retour au guichet EasyPay, contrôles réussis

**Dernière correction des anciennes commandes : 44 tests / 8 fichiers, lint et build réussis, 59 modules.** La bascule d'un ancien essai sandbox admissible est maintenant testée pour chaque réseau vers une nouvelle tentative réelle du même numéro de commande. Backend associé : 89 tests / 348 assertions sur SQLite et PostgreSQL et Pint réussis. Le premier passage simultané a rencontré un dépassement de délai sur le catalogue ; la suite complète repassée seule réussit sans changer ou désactiver ce test. Aucun appel marchand. Rapports privés tests-active-payment-*.xml. Ces résultats remplacent les chiffres antérieurs ci-dessous ; notification réelle non validée.

Autorisation explicite du titulaire d'exécuter les contrôles sans paiement réel. Résultats finaux : lint réussi ; 42 tests / 8 fichiers réussis ; build TypeScript et Vite réussi, 59 modules. Rapport privé .local/tests-retour-easypay-frontend.xml. Backend : 85 tests / 320 assertions SQLite et Pint réussis. Aucune transaction marchande pendant ces contrôles.

Échecs initiaux corrigés : output sémantique au lieu de p role=status ; quatre tests cherchaient le bouton avant le chargement asynchrone des options, désormais attendu avec findByRole. Tests complets et compilation relancés après correction. Les boutons Orange Money/M-Pesa/Airtel sont contrôlés avec ouverture simulée de l'adresse EasyPay renvoyée par le serveur, pas avec une demande sur un vrai téléphone. Pas de recette financière ni de notification réelle validée. Les sections suivantes sont historiques.

Les boutons des trois réseaux sont restaurés vers le guichet hébergé, ainsi que Accéder au paiement sécurisé. Le numéro est saisi chez le prestataire. Tests adaptés, sans exécution de lint, test ou build ; aucun appel marchand pour cette restauration. Les contrôles et descriptions ci-dessous restent historiques. Le succès financier et la notification réelle ne sont pas validés.

## Historique du parcours direct

Numéro, demande directe, erreurs persistantes, absence de nouvel envoi après un échec et suivi de statut borné ajoutés ; tests mis à jour. Aucun lint, test, build ou parcours exécuté pour ces modifications. Les chiffres ci-dessous sont historiques. Voir PARCOURS_COMMANDE_ROLES_PAIEMENT.md pour la recette et les commandes manuelles.

# Vérifications courantes du 24 octobre 2026

Frontend : `npm run lint` réussi ; Vitest **38 tests / 8 fichiers réussis** ; `npm run build` réussi, TypeScript et Vite, 59 modules. Rapport privé `.local/tests-commande-immediate-frontend.xml`. Backend associé : **78 tests / 287 assertions SQLite et Pint réussis**. Les trois réseaux ont chacun un test d'initialisation et d'ouverture simulée de l'URL EasyPay, en plus du refus d'une URL étrangère et de la conservation du lien.

Correction de l'environnement JSDOM : état `open` du dialogue natif simulé pour que la régression de reçu après panier vidé soit réellement testée. Aucun test ou contrôle désactivé. La recette navigateur complète reste distincte ; la session client Edge a été identifiée sur localhost:5173, avec la même source et API que 127.0.0.1:5174. Aucun débit, appel marchand, nouvelle commande applicative ou changement de mot de passe effectué pendant cette vérification. Les sections suivantes sont historiques et ne remplacent pas ces résultats courants.

Après nouvelle autorisation « Fais le », le complément a été contrôlé le 23 octobre 2026 : `npm run lint` réussi, `npm test` réussi (5 fichiers, 25 tests), `npm run build` réussi (54 modules). Le backend associé passe 65 tests / 227 assertions sur SQLite en mémoire et Pint. Aucune recette navigateur complète ni requête marchande pendant cette exécution. Le paiement reste désactivé. Lire [PARCOURS_COMMANDE_ROLES_PAIEMENT.md](PARCOURS_COMMANDE_ROLES_PAIEMENT.md). Les preuves ci-dessous concernent les versions précédentes.

# Complément de carte — 23 octobre 2026

La sélection de Kinshasa est étendue à 24 articles avec des illustrations locales ; voir [CARTE_KINSHASA.md](CARTE_KINSHASA.md). Lecture du catalogue connecté et accès au formulaire administrateur observés. Les résultats de lint, tests et build ci-dessous sont historiques et précèdent cette modification ; aucun nouveau contrôle automatique du projet n’est exécuté pour ce complément. Le téléversement de photos dans l’espace restaurant reste absent.
# Vérification du frontend — 23 octobre 2026

Exécution autorisée explicitement par le groupe : « EXECUTE ». Aucun commit, push, publication GitHub ou changement Jira.

| Contrôle effectué | Résultat observé |
| --- | --- |
| npm ci | Dépendances du verrouillage installées ; aucune mise à jour globale |
| npm run lint | Réussi après corrections ; complexité maximale 10 et profondeur maximale 3 |
| npm test | 4 fichiers réussis, 22 tests réussis |
| npm run build | TypeScript et Vite 8.3.5 réussis ; 44 modules transformés |
| Mise en forme | Sources mises en forme avec Prettier ; dépendances du projet inchangées |
| Serveur de développement | Nouvelle copie disponible sur http://127.0.0.1:5174/ |
| Serveur antérieur | Ancienne interface sur 5173 conservée, aucun processus arrêté |
| Backend existant | Réutilisé sur 8010 ; aucune migration ni modification métier backend |

## Corrections effectuées

- Chargement des ressources : état de chargement dérivé de la clé et de la révision ; suppression de la mise à jour synchrone dans l’effet, annulation et masquage des anciennes réponses conservés.
- Messages de statut rendus avec des éléments output et restaurant doté d’un libellé accessible.
- Fonctions Commandes/Fidélité simplifiées ; aucune règle de qualité désactivée pour masquer une erreur.
- Initialisation de l’horloge du devis déplacée dans l’initialiseur paresseux de l’état.
- Proxy de développement /api vers le backend 8010. Le navigateur appelle /api/v1 sur son propre port ; cela permet de conserver l’ancien serveur 5173 et de ne pas changer la configuration CORS privée du backend.

## Contrôles observés dans le navigateur

- Accueil photographique et navigation affichés dans le navigateur intégré, à la taille du bureau.
- Information sur les cookies visible, choix « nécessaires uniquement » pris en compte.
- Poulet à la moambe ajouté au panier : une portion à 15 000 FC.
- Quantité portée à deux : sous-total affiché de 30 000 FC.
- Panier vidé et fenêtre fermée après le contrôle.
- Mode Restaurants connectés : les deux restaurants issus du backend sont lus ; leur carte est actuellement vide. Aucune donnée pédagogique n’est injectée dans PostgreSQL.
- Retour en Démonstration pour laisser la présentation gastronomique visible.

Une capture de l’accueil est conservée dans output/Frontend_Apercu_Accueil.png à la racine de la livraison locale.

## Contrôles HTTP effectués

| Route sur 127.0.0.1:5174 | Code attendu | Code reçu |
| --- | ---: | ---: |
| / | 200 | 200 |
| /images/moambe.webp | 200 | 200 |
| /images/credits.json | 200 | 200 |
| /api/v1/restaurants | 200 | 200 |
| /api/v1/auth/csrf-cookie | 200 | 200 |
| /api/v1/me/orders, sans connexion | 401 | 401 |

Le rapport des codes HTTP est conservé localement sans cookie ni jeton. Aucun compte utilisateur ou commande réelle créé pour ces contrôles.

## Limites de cette validation

Les 22 tests sont des tests de domaine, HTTP simulé et composants React ; ils ne constituent pas une recette complète du backend réel. Les connexions privées, rôles, créations de commande, paiements marchands EasyPay, navigateurs mobiles et tests de bout en bout restent à réaliser avec des comptes de test et une configuration adaptée. Les écrans avancés partiels recensés dans le livrable restent à terminer.

# État de l'extension du 24 octobre 2026

Les nouvelles interfaces par rôle, le changement des identifiants, l'ajout de restaurants et le rafraîchissement périodique du service sont écrits mais non vérifiés par exécution. Nouveaux tests ajoutés, sans lint, test, compilation ou recette navigateur de cette extension. Les résultats plus bas concernent le 23 octobre.

## Parcours commande vers EasyPay — correction du 26 octobre 2026

Le bouton de confirmation annonce maintenant explicitement l'ouverture d'EasyPay lorsque le restaurant possède un paiement disponible. Après création de la commande, le frontend demande le lien hébergé au serveur et redirige dans le même onglet. Le montant reste celui de la commande calculée côté serveur. Aucun montant n'est modifié ou converti par le frontend.

Si la préparation échoue, le reçu de commande reste affiché : « Commande enregistrée, paiement non confirmé ». Le panier est vidé après conservation du reçu ; le client retrouve sa commande dans Mes commandes. Aucune seconde commande ou tentative n'est créée automatiquement pour contourner une erreur. Le bouton de reprise des commandes existantes utilise la tentative conservée par le backend.

Le message « Votre commande a été payée » requiert un paiement déclaré paid par le serveur, une date de vérification et le mode de production v1. Une tentative en attente, incertaine, refusée, annulée ou de test n'est jamais présentée comme un règlement réel réussi. La confirmation d'une commande et son paiement sont deux événements distincts.

La notification est fournie dans le site. Aucun reçu par e-mail n'est envoyé par cette correction : l'hébergement utilise encore MAIL_MAILER=log. Un service d'envoi devra être configuré avant d'annoncer des e-mails effectivement délivrés.

Contrôles exécutés avec autorisation, sans appel marchand : 87 tests Vitest / 16 fichiers réussis ; contrôle ciblé final des deux fichiers de parcours, 24 tests réussis après mise en forme ; Oxlint et compilation TypeScript/Vite réussis. Aucune commande réelle créée, aucun paiement réel ni demande téléphonique déclenchés par ces contrôles. Backend inchangé, suites backend non relancées. La réussite du parcours marchand et de l'encaissement réels reste à valider séparément.

Pour reproduire les contrôles manuellement, installer Node.js 24 puis ouvrir PowerShell dans C:\Users\J\Desktop\projet miam miam\frontend et lancer, dans l'ordre :

```powershell
npm ci
npm run lint
npm test
npm run build
```

Ces commandes vérifient le code et ses réponses simulées ; elles ne valident pas le fonctionnement du prestataire.

Complément de validation : la redirection automatique respecte aussi la liste des réseaux activés et n'est pas proposée pour un total nul. Un test supplémentaire couvre le marchand sans réseau confirmé. Dernier résultat après ce garde-fou : 88 tests / 16 fichiers, Oxlint et compilation TypeScript/Vite réussis, toujours sans appel marchand.

## Corrections du 27 octobre 2026 : premier clic, mobile et restaurant

Le client relit le jeton CSRF avant chaque écriture, au lieu d'attendre le premier refus de session. Une écriture de commande, restaurant ou paiement n'est jamais rejouée automatiquement après erreur. Trois nouveaux tests couvrent le renouvellement de session, l'envoi unique et l'absence d'envoi en cas d'échec de préparation.

Le formulaire d'ajout affiche l'adresse du compte et relit la session avant la création : les onglets du même navigateur partagent leur connexion. Un compte client ou serveur connecté dans un autre onglet ne reçoit aucun droit administrateur. Le serveur reste l'autorité pour les rôles ; aucune permission n'est élargie.

Sur téléphone : champs à 16 px pour éviter le zoom automatique de Safari à la saisie, taille des titres adaptée à la largeur, panier et boutons sans chevauchement, fenêtre du panier adaptée à la hauteur disponible, carte en une colonne sur les plus petits écrans. Le zoom manuel reste disponible.

Résultats obtenus : 92 tests frontend / 16 fichiers réussis, Oxlint et compilation TypeScript/Vite réussis. Backend : 9 tests ciblés / 79 assertions sur PostgreSQL de test local (authentification, ajout et isolation multi-restaurants), sans changement du code backend. Aucun appel marchand, paiement réel ou création de restaurant en ligne pendant ces contrôles. Le refus 403 historique est observé dans Render ; le rôle actif d'admin@gmail.com et du restaurant 1 est confirmé en lecture seule dans Neon. La cause exacte de ce refus historique n'est pas établie par ces seules observations.

Commandes manuelles, avec Node.js 24, dans PowerShell :

```powershell
Set-Location -LiteralPath 'C:\Users\J\Desktop\projet miam miam\frontend'
npm run lint
npm test
npm run build
```

Recette titulaire : actualiser le site, ouvrir une session administrateur distincte de celle du client, vérifier l'adresse affichée, puis créer le restaurant souhaité. Pour les trois comptes simultanés, utiliser des profils de navigateur distincts. La réception téléphonique EasyPay ne fait pas partie des validations simulées.
