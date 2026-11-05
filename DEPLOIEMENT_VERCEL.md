# Déploiement Vercel et backend hébergé

Mise en ligne effectuée le 26 octobre 2026. Les limites de recette et d'exploitation sont précisées ci-dessous.

## Architecture déployée

- Vercel : frontend React/Vite, dossier racine frontend, commande npm run build, résultat dist.
- Render : backend Laravel dans le conteneur backend/Dockerfile, dossier racine backend.
- Neon : nouvelle base PostgreSQL distincte des bases du PC. Ne pas utiliser le PostgreSQL gratuit Render pour une conservation durable : cette offre expire après 30 jours.

Les offres gratuites restent soumises à leurs conditions et quotas. Render met en veille les services gratuits inactifs et le redémarrage peut prendre environ une minute. Cette formule sert à la démonstration pédagogique ; elle ne constitue pas une exploitation commerciale validée. Aucun abonnement payant ne doit être engagé sans accord.

## État vérifié

Le site public est accessible à [mon-miam-miam-groupe4.vercel.app](https://mon-miam-miam-groupe4.vercel.app). Son API Laravel est hébergée à [mon-miam-miam-api-groupe4.onrender.com](https://mon-miam-miam-api-groupe4.onrender.com). La base PostgreSQL 18 dédiée est dans le nouveau projet Neon mon-miam-miam-groupe4, à Francfort, sur l'offre gratuite. Aucun abonnement payant engagé.

La publication est autorisée uniquement sur chore/MMM-42-deploiement-vercel. Cette branche est suivie par l'environnement Production de Vercel et par Render. main et develop restent inchangées : aucune fusion, PR ou mise à jour Jira n'a été réalisée. La première construction Vercel du socle main a ensuite été remplacée par la version complète de la branche dédiée, commit 4e7f6d4. Render conserve le backend du commit 537a798, inchangé par les commits suivants limités au frontend.

Docker local est bloqué par la virtualisation désactivée ; la construction a réussi sur Render. Dix migrations ont été appliquées, avec autorisation explicite, dans la nouvelle base seulement. DEPLOY_RUN_MIGRATIONS a ensuite été remis à false. Une APP_KEY propre au cloud et les identifiants Neon sont conservés uniquement dans les réglages privés Render ; aucune configuration privée locale n'a été publiée.

Le catalogue approuvé a été transféré : deux restaurants, quatre catégories, vingt-quatre articles et sept créneaux d'ouverture déjà configurés. Aucun utilisateur, rattachement professionnel, commande ou paiement du PC n'a été transféré. Les cartes et horaires non configurés localement restent à compléter dans l'application.

Vérifications publiques réussies : accueil 200, liste des restaurants via Vercel 200 avec deux restaurants, initialisation CSRF 200, deux cookies présents avec attributs Secure et HttpOnly, aucun domaine de cookie forcé sur Render. Un essai de connexion volontairement invalide retourne 422 avec session et jeton CSRF, sans créer de compte. Dans le navigateur, huit images d'accueil sont chargées ; un riz à 2 500 FC a été ajouté au panier visiteur, puis retiré. Aucune commande ni transaction n'a été créée par cette recette. Les tests métier historiques ne sont pas présentés comme réexécutés dans le cloud.

Les comptes en ligne doivent être créés séparément par leurs titulaires. La recette d'une connexion réussie, des rôles et d'une commande authentifiée en ligne reste à effectuer avec ces nouveaux comptes. MAIL_MAILER=log n'envoie aucun e-mail ; le planificateur et une procédure de sauvegarde d'exploitation restent à organiser. Le paiement est désactivé dans cet environnement.

Fichiers préparés : frontend/vercel.json, frontend/.vercelignore, backend/Dockerfile, backend/.dockerignore et backend/deploy/. Le conteneur utilise PHP 8.5, Apache et PostgreSQL. Il exige une configuration privée et n'active pas le paiement ni les migrations par défaut.

## Procédure de configuration et reprise

1. Créer et connecter les comptes gratuits Render et Neon dans le navigateur, sans transmettre de mot de passe dans la conversation.
2. Choisir la région et créer une nouvelle base Neon. Ses identifiants seront saisis dans les variables privées Render, jamais dans Vercel ni GitHub. Générer une APP_KEY propre au nouvel environnement ; ne pas réutiliser les secrets locaux.
3. Valider le canal de transmission du code. Render peut construire depuis un dépôt Git connecté ou une image de conteneur. La publication de la version complète sur la branche dédiée chore/MMM-42-deploiement-vercel a été expressément autorisée ; main et develop restent inchangées. Ne pas déployer automatiquement l'ancien socle distant à la place des sources locales actuelles.
4. Construire et vérifier le conteneur sur Render, la construction locale étant bloquée par le moteur Docker. Les contrôles métier antérieurs ne prouvent pas encore son bon démarrage.
5. Créer le backend Render, avec le modèle backend/deploy/render.env.example complété uniquement dans le service. Activer DEPLOY_RUN_MIGRATIONS=true seulement sur accord pour la nouvelle base cible ; remettre false après initialisation. Aucun seed de compte ou transfert de données locales n'est automatique.
6. Créer le projet Vercel depuis les sources frontend actuelles, sélectionner Node.js 24 et définir VITE_INITIAL_MODE=api, VITE_API_BASE_URL=/api/v1.
7. frontend/vercel.json réécrit /api/:path* vers https://mon-miam-miam-api-groupe4.onrender.com/api/:path*, avant les deux routes de retour. Les appels navigateur restent sur le domaine Vercel. Refaire la recette cookies/CSRF après tout changement de domaine ou de proxy.
8. Définir FRONTEND_URL sur l'adresse Vercel exacte côté backend, APP_URL sur son adresse HTTPS, cookies Secure et domaine de cookie non imposé. Garder les contrôles CSRF. Ne pas exposer les clés marchandes en variables VITE_*.
9. Vérifier accueil, API publique, initialisation CSRF, inscription/connexion d'un compte autorisé, rôles et commande de démonstration sans paiement. La configuration d'e-mails doit être validée séparément ; MAIL_MAILER=log ne livre aucun message à une boîte réelle.

## Conditions d'une mise en ligne complète

Vercel, l'API Laravel et Neon sont reliés et vérifiés pour le catalogue public et le transport des sessions/CSRF. Cela ne valide pas encore une recette complète des nouveaux comptes, des droits professionnels et d'une commande en ligne. Les comptes, commandes et paiements du PC restent locaux. Un planificateur et une procédure de sauvegarde doivent être définis pour l'exploitation ; le conteneur Apache seul ne les lance pas.

Le paiement restera désactivé dans le nouvel environnement tant que la recette réelle n'est pas validée. Cela ne modifie pas la configuration privée locale et ne reprend pas le débogage EasyPay suspendu.

## Références officielles consultées

- [Vite sur Vercel](https://vercel.com/docs/frameworks/frontend/vite)
- [Réécritures Vercel](https://vercel.com/docs/routing/rewrites)
- [Laravel et Docker sur Render](https://render.com/docs/deploy-php-laravel-docker)
- [Limites des offres gratuites Render](https://render.com/docs/free)
- [Offre Neon](https://neon.com/pricing)

Jira suivra les critères de déploiement après accord ; aucun changement de statut ni pièce jointe n'est publié. Cette préparation ne change pas le bilan historique des tests ni les archives livrées avant déploiement.
