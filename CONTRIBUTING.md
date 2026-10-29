# Conventions du nouveau dépôt

1. Accepter son invitation et utiliser son vrai compte GitHub et sa propre identité Git vérifiée.
2. Cloner le nouveau dépôt dans un dossier neuf. Lire docs/nouveau-depot/ORDRE_COMMITS_ET_INTEGRATION.md et sa fiche personnelle.
3. Respecter les 15 lots ordonnés ; un seul responsable par fichier. Avant chaque lot, vérifier git status, récupérer origin et fusionner origin/integration dans sa branche nominative. Ne jamais écraser un fichier modifié entre-temps avec une copie ancienne.
4. Sélectionner uniquement les fichiers du lot. Aucun git add global, secret, .env privé, dépendance installée, base ou mémoire locale. Les exemples de configuration restent sans identifiants réels.
5. Chaque message décrit l'import ou le changement réel : MMM-48 feat: importer les données commerce. Le ticket principal ne signifie pas que les autres tâches du lot sont terminées.
6. PR de contrib/nom vers integration, avec ticket Jira, provenance, numéro du lot, vérifications réalisées, limites et vrai relecteur. Attendre une relecture par un autre membre ; personne ne s'approuve lui-même.
7. Utiliser Create a merge commit pour conserver les commits. Garder les branches nominatives. Aucun push forcé ou réécriture de l'historique partagé.
8. Prudent coordonne la recette après assemblage et la PR integration vers main. Tests et compilation doivent réellement passer avant livraison ; pas de paiement marchand dans les contrôles automatiques.
9. Les règles sont une procédure de groupe tant que la protection serveur n'est pas vérifiée. Ne pas rendre le dépôt public ou souscrire un abonnement pour contourner une limite d'offre.
10. L'ancien hébergement reste en place. Toute bascule vers ce dépôt exige une préparation distincte. Aucune synchronisation automatique Jira/GitHub n'est présumée.

Les branches nominatives couvrent plusieurs tâches ; leurs PR doivent citer la clé Jira principale et les autres tickets couverts. Les conventions et tests de l'ancien dépôt utilisant develop sont remplacés dans le lot 15.
