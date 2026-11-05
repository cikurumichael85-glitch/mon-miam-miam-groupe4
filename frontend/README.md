# Dernier changement — retour au guichet EasyPay

**Point 5, contrôles finaux locaux du 26 octobre 2026 : 78 tests frontend, lint et compilation réussis.** En-tête corrigé sur petit écran et icônes nommées ; erreurs HTTP usuelles clarifiées ; contexte partagé stabilisé pendant les mises à jour du code. 116 tests backend / 635 assertions SQLite, Pint et Composer réussis, 12 contrôles HTTP conformes. PostgreSQL : preuve du point 4 conservée, pas de nouvelle exécution. Voir le [compte rendu avec captures et limites](../output/Recette_Finale_26_10_2026/Compte_Rendu.md). Aucun paiement réel, compte personnel utilisé ou donnée métier existante modifiée.

**Point 4, règles métier : onglet Fidélité ajouté à la gestion, sans barème chiffré prérempli ni activation automatique.** Journal corrigé sur occurred_at ; code de parrainage lié au bon restaurant même après une réponse tardive ; actualisation des invitations disponible. 68 tests frontend, lint et compilation réussis ; backend 116 tests / 635 assertions SQLite et PostgreSQL. Voir la [recette métier](../backend/docs/RECETTE_REGLES_METIER.md) : barèmes commerciaux et changements de politique utilisée restent à valider.

**Point 3, plusieurs restaurants : 57 tests frontend, lint et compilation réussis le 26 octobre 2026.** Formulaire d'ajout, conservation des rattachements, erreurs de création et refus de mélange du panier vérifiés. Backend : 101 tests / 538 assertions sur SQLite et PostgreSQL dédiés. Aucun changement du code applicatif requis par cette recette. Voir le [rapport multi-restaurants](../backend/docs/RECETTE_MULTI_RESTAURANTS.md) pour le guide de présentation et les limites.

**Recette des trois rôles du 26 octobre 2026 : 51 tests frontend, lint et compilation réussis.** Le détail privé montre désormais les coordonnées de livraison, côté client et serveur. Backend : 98 tests / 485 assertions sur SQLite et PostgreSQL dédiés. Lire le [rapport des rôles et la démonstration manuelle](../backend/docs/RECETTE_TROIS_ROLES.md). Le navigateur a été vérifié jusqu'au formulaire de connexion ; les trois comptes personnels restent à présenter par leurs titulaires. Paiement réel exclu de cette recette.

**Bilan arrêté au 26 octobre 2026 : débogage EasyPay suspendu à la demande du groupe.** Redirection vers le guichet observée ; après saisie du numéro, attente affichée et aucune demande reçue selon le titulaire. Paiement réel complet non validé, cause exacte inconnue. Lire la [note de bilan et limites du paiement](../output/Mon_Miam_Miam_Bilan_Paiement_26_10_2026.md) avant les résultats historiques ci-dessous. Aucun nouvel essai effectué pour cette note.

**Débogage point G uniquement : backend corrigé et 94 tests / 384 assertions réussis sur SQLite et PostgreSQL, Pint réussi.** INIT est reconnu comme attente ; consultation de la référence existante réussie sans nouvelle demande de paiement. Frontend inchangé : ses 44 tests précédents n'ont pas été relancés. Les étapes E/F/H et la réception de notification réelle restent à vérifier séparément. Voir docs/PARCOURS_COMMANDE_ROLES_PAIEMENT.md.

**Les boutons sont désormais actifs aussi pour une ancienne tentative sandbox admissible sur une commande non réglée.** Le clic prépare une nouvelle tentative réelle pour cette même commande puis ouvre EasyPay ; l'ancien test reste dans l'historique, avec sa référence inchangée. Pas besoin de recommencer la commande. Migration backend requise et appliquée localement après sauvegarde. Contrôles finaux : **44 tests frontend, lint et build réussis ; 89 tests / 348 assertions backend sur SQLite et PostgreSQL, Pint réussi**. Aucun appel marchand ou débit pendant ces contrôles ; réception de notification réelle non validée. Les descriptions du blocage des anciennes commandes plus bas sont historiques.

**Contrôles autorisés du 24 octobre : lint réussi, 42 tests dans 8 fichiers réussis, compilation TypeScript/Vite réussie (59 modules).** Backend associé : 85 tests / 320 assertions SQLite et Pint réussis. Les trois boutons ont chacun un test de préparation et redirection simulée. Correction d'accessibilité du suivi avec output et attente du chargement des options dans quatre tests. Aucun contrôle désactivé, appel marchand, notification ou débit réel effectué. La réception sur téléphone et le succès financier restent non validés ; les mentions non exécuté ci-dessous décrivent l'état antérieur aux contrôles.

Sur demande du titulaire, Orange Money, M-Pesa et Airtel Money sont de nouveau visibles et ouvrent le guichet sécurisé EasyPay pour une tentative autorisée dans le mode courant. Le bouton Accéder au paiement sécurisé fait de même. Le numéro se renseigne chez EasyPay. Le champ de saisie directe dans le site a été retiré du parcours principal ; son API backend reste disponible. Aucune initialisation marchande, test, lint ou build effectué pour cette restauration. Les anciennes tentatives sandbox ne sont pas converties. Les résultats et descriptions suivants sont historiques ; voir docs/PARCOURS_COMMANDE_ROLES_PAIEMENT.md.

## Historique du parcours direct préparé

Numéro saisi dans l'application, bouton Recevoir la demande sur mon téléphone, statut confirmé côté serveur et contrôles bornés. Code et tests modifiés sans nouvelle exécution de lint, tests, build, recette ou appel marchand. Les preuves antérieures ne valident pas ce changement. Lire docs/PARCOURS_COMMANDE_ROLES_PAIEMENT.md et backend/docs/API_ROLES_COMPTES.md. Le mode du poste reste production ; aucun nouveau débit déclenché pendant ces modifications.

# Mon Miam Miam — Interface du Groupe 4

**Extension contrôlée le 24 octobre 2026 : lint, 38 tests dans 8 fichiers et compilation réussis (59 modules).** Espaces par rôle, identifiants, ajout de restaurants, Commander maintenant et boutons des trois réseaux couverts. Backend associé : 78 tests / 287 assertions SQLite et Pint réussis. Voir [le guide](docs/PARCOURS_COMMANDE_ROLES_PAIEMENT.md) et [les preuves et limites](../backend/docs/API_ROLES_COMPTES.md). Les paiements réels restent non validés ; les résultats plus bas sont historiques.

ULC-ICAM · République démocratique du Congo · CDF / FC.
Binôme B : **Michael Cikuru et Assek Arloni**.

## Ce qui est livré

Interface React/TypeScript avec accueil photographique, deux cartes de démonstration, catégories, recherche, panier par restaurant, authentification, parcours de commande, compte, fidélité et espaces de service.

**Contrôle actuel du 23 octobre 2026 : analyse du code, 25 tests dans 5 fichiers et compilation réussis.** Lire [le guide des parcours](docs/PARCOURS_COMMANDE_ROLES_PAIEMENT.md). Le compte marchand existe et les identifiants sont préparés uniquement côté backend ; le mode sandbox est actif pour le restaurant 1, avec initialisation et page hébergée vérifiées sans débit ; confirmation d’un paiement réussi encore à valider. La recette navigateur complète des rôles, horaires et commandes reste distincte des tests automatisés. Les résultats historiques sont conservés dans `docs/VERIFICATION_FRONTEND.md`.

Le cahier des charges cite React, Vue et Angular comme exemples et laisse le choix du framework. React est conservé conformément au choix antérieur du groupe ; TypeScript, Vite et les styles CSS sont des choix de réalisation, pas des langages imposés par le professeur.

## Installation et démarrage manuel

Prérequis : Node.js 24 avec npm, un navigateur récent et un éditeur comme VS Code. Le fichier package-lock.json du socle publié est conservé et package.json n’ajoute aucune dépendance. Ne pas installer de PHP pour compiler ce frontend ; PHP et PostgreSQL servent au backend séparé.

Dans PowerShell, dans ce dossier :

```powershell
Set-Location 'C:\Users\J\Desktop\projet miam miam\frontend'
node --version
npm --version
npm ci
npm run lint
npm test
npm run build
npm run dev -- --host 127.0.0.1 --port 5174 --strictPort
```

Exécuter les lignes dans l’ordre et corriger les éventuels échecs avant de présenter l’application comme validée. Ouvrir `http://127.0.0.1:5174/`. Garder le terminal ouvert. Si le port est occupé, vérifier le chemin du processus existant avant de relancer ou d’arrêter quoi que ce soit ; le mode strict évite un changement silencieux de port qui casserait CORS.

Le mode **Démonstration** fonctionne sans backend : les photos sont locales et les restaurants fictifs. Il ne simule ni connexion réussie, ni paiement, ni commande enregistrée.

Pour prévisualiser la compilation après sa réussite, arrêter uniquement son propre serveur puis utiliser :

```powershell
npm run preview -- --host 127.0.0.1 --port 4173 --strictPort
```

Utiliser 5174 pour la recette normale actuelle. Le port 5173 conserve l’ancienne copie du projet. En production, le serveur doit fournir une route /api vers Laravel ou VITE_API_BASE_URL doit désigner une API HTTPS avec cookies et CORS adaptés.

## Connexion au vrai backend

1. Vérifier que le backend de ce projet est disponible sur `http://127.0.0.1:8010/up`. Ne pas démarrer un doublon. Le guide `../backend/README.md` explique le PHP compatible et le lancement manuel.
2. Garder le `.env` backend existant privé. En développement, Vite relaie /api vers 8010 : le navigateur reste sur la même origine et la configuration CORS privée du backend n’est pas modifiée. Pour une connexion directe à une API distante, FRONTEND_URL doit autoriser exactement l’origine de l’interface. Ne pas mélanger localhost et 127.0.0.1.
3. La valeur publique par défaut de VITE_API_BASE_URL est `/api/v1`, relayée vers `http://127.0.0.1:8010` par Vite en développement. Pour la changer, copier `.env.example` vers `.env` seulement si ce fichier n’existe pas, modifier la valeur puis redémarrer Vite.
4. Choisir **Restaurants connectés** dans la barre supérieure. Ce changement vide le panier après accord. Une erreur API ne remplace jamais les données du serveur par les données fictives.
5. Créer son compte ou se connecter. Les rôles viennent du serveur. La session, les adresses et les commandes ne sont pas stockées dans localStorage.
6. Les restaurants actifs du serveur peuvent avoir une carte vide : créer les catégories/articles avec un compte autorisé et configurer les horaires. Le catalogue de démonstration n’est pas une insertion automatique dans PostgreSQL.

Aucune clé marchande ne doit apparaître dans une variable VITE_*. EasyPay est appelé par Laravel. Le retour `/payment-return` remet l’interface en mode connecté ; l’utilisateur ouvre ensuite sa commande pour faire vérifier le règlement par le serveur. Le paramètre de retour success ne marque jamais une commande comme payée.

## Dossiers et documents

```text
src/components/  Éléments partagés, formulaires, panier et devis
src/pages/       Écrans publics, compte, commandes et espace restaurant
src/services/    Transport Laravel et adaptation du catalogue
src/domain/      Contrats, calculs monétaires et règles du panier
src/state/       Session et état partagé, uniquement en mémoire
src/styles/      Charte, catalogue et espaces de gestion
public/images/  Photos, illustrations et manifeste de crédits
docs/           Livrable, répartition, recette et licences
```

Lire d’abord `docs/LIVRABLE_FRONTEND.md`, puis `docs/REPARTITION_BINOME_B.md` et `docs/RECETTE_MANUELLE.md`. Les archives individuelles ne sont pas deux applications autonomes : réunir les deux sélections dans une copie de travail à jour pour intégrer l’ensemble.

## Git, Jira et Figma

Rien n’est publié actuellement. Les clés Jira de la répartition sont des propositions locales ; aucune assignation ou clôture distante n’est modifiée. Utiliser Figma pour la comparaison visuelle, Jira pour les critères/preuves, Git pour les versions lorsque les commits seront autorisés, GitHub pour les futures PR vers develop. Conserver les contributions réellement réalisées et les vraies relectures ; aucun commit n’est fabriqué au nom d’un autre membre.

## Qualité et limites

La configuration Oxlint prévoit une complexité maximale de 10 et une profondeur de 3, plus les règles React et d’accessibilité. Le fichier Prettier fixe le style de mise en forme ; utiliser l’extension de l’éditeur selon les conventions de la copie d’intégration. Les contrôles ESLint/Prettier supplémentaires de la PR de qualité ne sont pas supposés intégrés à main : les conserver lors d’une future intégration sans écraser leur configuration.

Les photos sont téléchargées et leurs métadonnées/licences contrôlées ; l’archive est vérifiée par empreintes. Les contrôles TypeScript/Oxlint/Vitest sont maintenant réussis et une vérification visuelle de bureau est effectuée ; la recette complète sur comptes et appareils reste distincte. Les prix sont indicatifs, les allégations nutritionnelles et les avis fictifs ne sont pas inventés. Les mentions légales et les durées de conservation restent à valider avant production.
