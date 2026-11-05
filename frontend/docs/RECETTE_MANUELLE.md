# Recette manuelle — à exécuter et consigner

**Correction du 24 octobre : nouveaux contrôles à faire.** Les cases cochées plus bas correspondent aux versions antérieures ; elles ne valident pas la nouvelle commande immédiate ou les boutons de paiement.

- [ ] Commander maintenant, sans date, à une heure d'ouverture : devis puis commande visible au service.
- [ ] Confirmer deux minutes après le devis immédiat, sans erreur d'arrivée passée et sans double commande.
- [ ] Planifier une arrivée passée : explication française unique ; future : validation selon horaires.
- [ ] Restaurant fermé : commande immédiate refusée, sans ouverture artificielle.
- [ ] Après confirmation et panier vidé : reçu conservé et boutons de paiement accessibles.
- [ ] Bouton d'un réseau actif : initialisation puis guichet HTTPS EasyPay ; aucun paiement déclaré réussi au seul clic.
- [ ] Compte marchand absent, paiement incertain ou annulé : pas de nouvelle initialisation automatique.

État : **contrôles de code et vérification de base effectués après autorisation**. Seules les cases appuyées par un résultat observé sont cochées ; voir VERIFICATION_FRONTEND.md. Quatre fichiers de tests sont préparés : monnaie, panier, HTTP simulé et catalogue React. Les appels HTTP des tests sont simulés ; ils ne prouvent pas une intégration réelle avec Laravel.

## Contrôles de code

- [x] Installer avec npm ci depuis frontend/.
- [x] npm run lint : aucune erreur de règles, de complexité ni de profondeur.
- [x] npm test : exécuter les cas et conserver le rapport réel.
- [x] npm run build : TypeScript et compilation Vite réussis.
- [ ] Vérifier les règles de la copie d’intégration avec ESLint/Prettier si configurées à la racine.

## Catalogue et ergonomie

- [ ] Accueil, navigation et photos corrects à 360, 390, 768 et 1440 pixels.
- [ ] Chrome, Firefox et Edge ; Safari sur un appareil disponible.
- [ ] Navigation au clavier, focus visible, dialogue fermé par Échap et retour du focus.
- [ ] Texte lisible avec zoom 200 %, absence de débordement horizontal.
- [ ] Mode de démonstration explicite, aucun avis ou classement inventé.
- [ ] Changer Mboka → L’Atelier met à jour les catégories et articles.
- [ ] Chercher « moambe », afficher uniquement les boissons, afficher un résultat vide.
- [ ] Ajouter un plat, augmenter/diminuer, retirer et vider le panier.
- [ ] Un plat d’un autre restaurant ne peut pas entrer dans le panier courant.
- [ ] Chaque prix est en FC ; comparer 3 × 2500.50 avec 7501.50 FC.
- [ ] Photos servies depuis public/images ; licences accessibles depuis Crédits.

## Compte et connexion réelle

- [x] Backend sur 127.0.0.1:8010 et frontend sur 127.0.0.1:5174 via proxy local /api.
- [ ] CORS et cookies vérifiés sans mélanger localhost et 127.0.0.1.
- [ ] Inscription valide ; erreur de confirmation du mot de passe ; e-mail déjà utilisé.
- [ ] Connexion valide, faux mot de passe et compte désactivé.
- [ ] Aucun mot de passe ni rôle enregistré dans localStorage ou les journaux.
- [ ] Erreur 419 visible, sans renvoi automatique d’une mutation.
- [ ] Session expirée : écrans privés et panier vidés.
- [ ] Récupération de mot de passe avec SMTP configuré et lien expiré.
- [ ] Le choix démo/connecté vide le panier après accord ; aucune erreur API ne crée un faux catalogue réel.

## Devis, commande et paiement

- [ ] Configurer un restaurant actif, ses catégories/articles et ses horaires dans Laravel.
- [ ] Tester une arrivée future valide, passée, au-delà de sept jours et hors horaires.
- [ ] Livraison autorisée/interdite, adresse/téléphone manquants, frais serveur exacts.
- [ ] Demander un devis, changer la quantité : ancien devis retiré de l’interface.
- [ ] Laisser expirer le devis : confirmation désactivée, nouveau calcul possible.
- [ ] Confirmer, interrompre la réponse réseau puis rejouer le même devis : pas de double commande.
- [ ] Prix/disponibilité modifié sur le serveur après devis : erreur 409 expliquée.
- [ ] Historique et détail cohérents ; statut mis à jour après actualisation manuelle.
- [ ] EasyPay désactivé : afficher l’erreur 503, sans prétendre qu’un paiement a réussi.
- [ ] Sandbox marchand configurée : ouvrir le domaine EasyPay autorisé et faire vérifier par Laravel.
- [ ] Le retour avec result=success ne change jamais à lui seul le statut payé.
- [ ] Un avis n’est autorisé qu’après service et une seule fois par commande.

## Rôles et séparation des restaurants

- [ ] Compte étudiant : aucune opération de personnel autorisée côté serveur.
- [ ] Employé A : liste et détail de commandes A, pas celles de B.
- [ ] Personnel A qui change un identifiant dans une requête vers B : accès refusé.
- [ ] Employé : disponibilité/plat du jour, statistiques de semaine ; direction : gestion étendue.
- [ ] Deux directions concurrentes modifient un article : version obsolète refusée.
- [ ] Réclamation client → réponse employé → approbation par une autre personne.
- [ ] L’auteur d’une réponse ne peut pas approuver sa propre réponse.
- [ ] Réponse soumise/refusée non visible dans l’espace client.
- [ ] Révocation d’un employé effective dans les nouvelles requêtes.
- [ ] Gérant local sans droit plateforme : requêtes de plateforme refusées.

## Fidélité, communauté et données

- [ ] Aucun programme approuvé : absence de solde/récompense expliquée.
- [ ] Soldes et journal chargés depuis Laravel ; consommation des points vérifiée au devis.
- [ ] Parrainage valide, auto-parrainage et code après première commande refusés.
- [ ] Classement limité aux pseudonymes des clients ayant donné leur accord.
- [ ] Événement/quiz actif, participation valide et tentative répétée.
- [ ] Export personnel avec mot de passe valide ; demande d’effacement sans promesse de suppression immédiate.
- [ ] Réglages avancés et formulaires incomplets recensés dans le livrable avant acceptation.

## Preuves à remettre au professeur

Pour chaque cas : date, compte de test fictif, restaurant, étapes, résultat observé, capture sans donnée sensible et anomalie éventuelle. Joindre les preuves à la tâche Jira selon l’accord du groupe ; aucune pièce jointe ou publication n’est effectuée automatiquement.
