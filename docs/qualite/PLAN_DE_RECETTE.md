# Plan de recette

## Préparation

Identifier la version, l’environnement, le périmètre autorisé et les comptes fictifs. Utiliser une base de test distincte. Ne pas tester un paiement réel ou lancer une charge publique sans accord sur le scénario.

## Scénarios

1. Connexion, identité, déconnexion et refus de profil invité.
2. Rôles client / serveur / gérant / direction et rattachements révoqués.
3. Deux restaurants indépendants, cartes et commandes étrangères refusées.
4. Devis, prix CDF, heures et confirmation unique malgré une répétition.
5. Service sur place ou livraison, version concurrente et transitions autorisées.
6. Points, parrainage, réclamation et approbation par une autre personne.
7. Initialisation / redirection / vérification marchande simulées ; état incertain jamais payé.
8. Largeurs mobiles, paysages, formulaires, zoom et lisibilité.
9. Charge bornée et conditions d’arrêt ; intégrité des commandes et sessions.

## Contrôles manuels sur copie dédiée

Frontend : npm run lint ; npm test ; npm run build.
Backend, PHP compatible : composer validate --strict ; php vendor/bin/pint --test ; php artisan test.

Exécuter séparément, depuis le dossier concerné et seulement avec la base de test correctement configurée. Les commandes ne sont pas exécutées pour préparer ces documents.

Pour chaque résultat : date, version, données d’essai, étapes, attendu, obtenu, preuve, limite et anomalie. Les tests applicatifs écrits restent dans les kits B/C ; ne pas se les attribuer par une recopie.
