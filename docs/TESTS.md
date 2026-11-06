# Tests automatiques et limites

## Contrôles GitHub Actions

À chaque push sur main, develop ou une branche de tâche, et à chaque pull request :

| Contrôle | Vérification |
|---|---|
| frontend | Installation verrouillée, analyse du code, tests Vitest/React Testing Library, compilation TypeScript/Vite |
| backend | Validation Composer, dépendances verrouillées, clé locale de test, tests PHPUnit/Laravel initiaux |
| documentation | Liens locaux Markdown, présence des documents de référence, cohérence du backlog et absence de fichiers secrets suivis |
| conventions | Sur PR : clé Jira et type dans le titre, lien Jira et sections de vérification ; branche cible conforme |

Le contrôle documentation exécute aussi huit cas de test de la politique des PR : tâche cohérente, mauvaise clé, mauvaise cible, livraison develop, lien Jira incorrect, titre incomplet, description incomplète et exception de démarrage MMM-39. Les noms des fichiers suivis sont contrôlés pour repérer les configurations privées évidentes ; il ne s'agit pas d'un scan exhaustif des secrets.

La CI ne déploie pas l'application et n'utilise aucun secret de production. Les PR de forks n'ont pas accès à des secrets du groupe ; aucun déclencheur pull_request_target n'est utilisé.

## Exécuter sur son poste

```sh
# frontend/
npm ci
npm run lint
npm test
npm run build
# backend/ — avec PHP compatible
composer validate --strict
php artisan test
# Racine du dépôt — Node 24
node .github/scripts/check-docs.mjs
```

Les tests frontend contrôlent le contexte français ULC-ICAM/RDC, l'affichage CDF/FC et la présentation honnête du socle. Les deux tests Laravel fournis vérifient uniquement le squelette. Aucun test de commande ou d'accès PostgreSQL n'est validé à ce stade.

## Tests métier à réaliser avec le code

| Domaine | Cas nécessaires | Suivi |
|---|---|---|
| Accès | Client limité à ses commandes ; personnel actif autorisé ; identifiant d'un autre restaurant refusé | MMM-26, MMM-49, MMM-55 |
| Commandes | Calcul serveur, prix conservés, disponibilité, doublons et transitions concurrentes | MMM-28, MMM-51 |
| Réclamations | Accès client et circuit d'approbation ; réponse non approuvée non visible | MMM-30 |
| Fidélité | Crédit et débit uniques ; récompense de parrainage ; annulations selon décision validée | MMM-32 |
| Parcours | Restaurant A puis B, sur place/livraison, écran mobile, erreurs et isolation | MMM-40, MMM-41, MMM-55 |

Les tests complets de bout en bout et la couverture métier seront ajoutés progressivement. Une CI verte sur le socle n'est pas une recette finale du cahier des charges.
