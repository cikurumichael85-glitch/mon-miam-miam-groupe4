# Conventions et intégration

Branche de tâche → PR vers develop → contrôles et relecture réelle → livraison vers main.

Nom de branche avec une vraie clé Jira ; message de commit décrivant le travail réel. Aucun push forcé ou commit au nom d’un autre membre. Ne pas ajouter .env, dépendances, bases, sauvegardes, journaux ou mémoire locale.

Les workflows déjà présents dans le dépôt doivent être relus sur leur version actuelle. Les premiers contrôles historiques ne valident pas tous les modules métier. Ce dossier ne remplace pas un workflow distant par une ancienne copie et n’invente pas une nouvelle CI exécutée.

La protection serveur de main avait été refusée par l’offre du dépôt privé ; les conventions de groupe ne sont pas présentées comme une protection technique active.

La version publique provient de chore/MMM-42-deploiement-vercel. Avant de réunir les contributions, rapprocher les différences avec develop sans remplacer les changements des autres membres. Aucun dépôt distant n’a été modifié pour ces kits.
