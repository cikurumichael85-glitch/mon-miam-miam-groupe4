# Présentation du projet

Groupe 4 · ULC-ICAM · RDC · Version de travail du 22 octobre 2026.

## 1. Problème

Les commandes prises par appel ou messagerie rendent leur traitement, le suivi des livraisons et la traçabilité des ventes difficiles. Les clients ont besoin d'un parcours lisible et le personnel d'une vue fiable des commandes.

## 2. Objectifs

Centraliser les menus et commandes ; suivre le service sur place et la livraison ; fournir des statistiques ; traiter les réclamations ; préparer fidélité, parrainage et promotions. Plusieurs restaurants doivent fonctionner dans la même application dès la V1, avec des données et droits correctement séparés.

## 3. Acteurs

Étudiant/client ; employé ; gérant ; administrateur de restaurant selon le cahier des charges. Le rôle d'administrateur de plateforme est une recommandation pour administrer plusieurs restaurants ; sa matrice de droits reste à valider. Aucun espace de livreur distinct n'est imposé par les documents disponibles.

## 4. Parcours cible

Choisir un restaurant → consulter son menu et ses disponibilités → préparer un panier → choisir sur place ou livraison → obtenir le récapitulatif calculé par le serveur → confirmer → suivre la commande → consulter l'historique et, si besoin, déposer une réclamation.

Le serveur contrôle les montants et les autorisations. Le client ne peut ni imposer le total ni accéder aux commandes d'une autre personne ou aux données d'un restaurant non autorisé.

## 5. Périmètre proposé

| Priorité proposée | Contenu |
|---|---|
| P0 | Comptes, restaurants, menus, commandes, droits, suivi, première gestion des réclamations, tests et documentation |
| P1 | Fidélité, parrainage, promotions et enrichissement des statistiques/réclamations |
| P2 | Commentaires, classement, mini-jeux et paiement si retenu |

Ces niveaux organisent le travail ; ils ne suppriment pas les exigences du cahier des charges. Le paiement est décrit dans la portée mais marqué bonus dans les livrables : le professeur doit arbitrer. Les barèmes illustratifs de points ne sont pas activés comme des règles validées.

## 6. Technologies et avancement

React/TypeScript/Vite pour les interfaces, Laravel 13/PHP pour le serveur, PostgreSQL proposé pour les données. GitHub versionne les sources et les revues, Jira suit les tâches et Figma fournit les maquettes. Les fichiers de conception sont préparés ; seul le socle de développement est actuellement exécutable.

## 7. Présentation au professeur

Montrer le cahier des charges et les arbitrages, les maquettes, une tâche Jira liée à son issue GitHub, la branche et ses commits, la pull request et les résultats des tests. Distinguer systématiquement la démonstration Figma, le socle exécuté et les fonctionnalités encore à développer.

## 8. Membres

Moise Balaga, Lucas Kandende, Michael Cikuru, Assek Arloni, Kabeya Ryan, Bacirhamya Yedidia et Prudent Kakesa. Voir [les responsabilités](RESPONSABILITES.md).
