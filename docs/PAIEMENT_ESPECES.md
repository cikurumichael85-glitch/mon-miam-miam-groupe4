# Paiement en espèces

Contribution de Michael Cikuru au projet Mon Miam Miam, Groupe 4.

Le client choisit **Espèces** avant de confirmer sa commande. Le serveur enregistre le montant calculé en francs congolais et garde le règlement en attente. Le personnel autorisé du restaurant ouvre la commande et confirme la réception du montant total. Le client retrouve ensuite l’état **Payé** dans son espace.

L’API vérifie le restaurant, le rôle de la personne qui confirme et le montant. Elle journalise la réception avec sa date et son auteur. Une nouvelle soumission de la même confirmation renvoie le règlement existant. Une tentative EasyPay réelle dont l’issue est incertaine doit être rapprochée avant de passer aux espèces.

L’administrateur retrouve le règlement dans la commande et les statistiques. Les totaux portent sur les commandes créées pendant la période sélectionnée et sur leur état de règlement actuel.

Les tests couvrent la sélection du moyen de paiement, le refus d’un accès hors restaurant, le contrôle du montant, la confirmation unique et les statistiques. La mise en production a été vérifiée séparément sur le [site Mon Miam Miam](https://mon-miam-miam-groupe4.vercel.app/).
