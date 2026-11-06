# Répartition équilibrée — Binôme B

Michael Cikuru et Assek Arloni · Groupe 4 · ULC-ICAM.
Proposition locale du 23 octobre 2026. Les points expriment une charge relative, pas des heures, des notes ni une preuve de réalisation personnelle.

| Responsable | Jira | Périmètre de prise en main et de présentation | Points |
| --- | --- | --- | ---: |
| Michael | MMM-11 | Inscription, connexion, récupération et échanges CSRF | 8 |
| Michael | MMM-12 | Direction visuelle, accueil et catalogue photographique | 8 |
| Michael | MMM-13 | Panier, monnaie exacte, devis et confirmation idempotente | 13 |
| Michael | MMM-14 | Historique, suivi et écran de règlement EasyPay | 8 |
| Michael | MMM-20 | Publication d’un avis après service | 3 |
| Michael | MMM-46 | Choix du restaurant, catalogue démo/connecté et photos | 5 |
| Michael | MMM-53 | Contexte restaurant et absence de mélange des paniers | 3 |
| Michael | MMM-23 | Information sur les cookies, préférences et navigation | 2 |
| **Total Michael** | **8 tâches** | | **50** |
| Assek | MMM-15 | Espaces employé/direction, commandes et catalogue de gestion | 13 |
| Assek | MMM-16 | Réclamations client/personnel et approbation croisée | 8 |
| Assek | MMM-17 | Statistiques locales et distinction servi/payé | 5 |
| Assek | MMM-18 | Fidélité, journal des points et parrainage | 8 |
| Assek | MMM-19 | Affiches et préparation des paramètres ; finitions à suivre | 5 |
| Assek | MMM-21 | Classement public sur consentement | 3 |
| Assek | MMM-22 | Lecture des événements/quiz et participation ; création quiz restante | 3 |
| Assek | MMM-47 | Premiers écrans de plateforme ; administration avancée restante | 5 |
| **Total Assek** | **8 tâches** | | **50** |

La répartition distante publiée mettait MMM-23 chez Assek : le transfert vers Michael est proposé ici pour équilibrer le lot et n’a pas été reporté dans Jira/GitHub. Les autres interfaces partagées peuvent nécessiter une relecture commune, notamment avis et réclamations dans le détail de commande.

Michael porte les styles du catalogue et de la mise en page, les photos et les utilitaires panier/monnaie/transport. Assek porte les composants de formulaires, les écrans de compte et de gestion, les styles de gestion et le chargement des ressources. Le manifeste nominatif détaille chaque fichier, son responsable de prise en main et son empreinte.

## Organisation de l’intégration future

1. Réunir une copie complète du frontend et une copie à jour du dépôt ; comparer avant tout remplacement.
2. Chaque membre lit, explique, adapte si nécessaire et vérifie ses fichiers. Les kits préparés ne prouvent pas un travail personnel déjà réalisé.
3. Le groupe autorise la reprise des commits et de la publication avant toute action GitHub.
4. Partir de develop. Exemples : `feat/MMM-12-interface-catalogue` pour Michael, `feat/MMM-15-espace-restaurant` pour Assek.
5. Ne sélectionner que les sources de la tâche, pas une archive complète, ni .env, ni node_modules, ni AGENTS.md, ni les livrables locaux.
6. Exécuter les contrôles, créer les commits avec son propre compte et ouvrir une PR vers develop. Exemple : `MMM-12 feat: présenter le catalogue des restaurants`.
7. L’autre membre effectue une vraie relecture et consigne les preuves. La CI et la relecture précèdent l’intégration ; develop est ensuite livré vers main.
8. Reporter les liens dans Jira manuellement. Ne clôturer une tâche qu’après satisfaction de ses critères, pas parce que son fichier existe.

## Relecture croisée à expliquer

- Michael relit l’isolation des écrans de service, les droits locaux et l’approbation des réclamations.
- Assek relit les transitions démo/connecté, le panier, les centimes CDF, le CSRF et le risque de double confirmation.
- Prudent et Moise assurent la recette globale avec le binôme C. La responsabilité des écrans reste au binôme B.
