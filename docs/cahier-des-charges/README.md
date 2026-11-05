# Cahier des charges et contexte du Groupe 4

## Sources disponibles

- [Document original fourni, V1.0.2 du 16/09/2024](CDC_Original_Mon_Miam_Miam_V1_0_2.docx) : copie conservée sans modification.
- [Analyse du Groupe 4, v1.1 multi-restaurants](Analyse_CDC_Groupe_4_v1_1.pdf) : exigences, recommandations, ambiguïtés et décisions à valider.
- [Présentation synthétique](../PRESENTATION.md) · [Architecture proposée](../architecture/README.md).

L'analyse ne remplace pas l'original et ne constitue pas une validation de l'encadrant.

## Corrections explicitement demandées par le groupe

| Sujet | Référence de travail pour le Groupe 4 |
|---|---|
| Établissement et pays | ULC-ICAM, République démocratique du Congo |
| Monnaie | Franc congolais, CDF / FC ; conserver les montants sans conversion |
| Équipe | Groupe 4, sept membres nommés dans le README |
| Restaurants | Plusieurs restaurants dès la première version |
| Calendrier | À confirmer avec le professeur ; les dates et la taille de groupe historiques de l'original ne sont pas reprises comme planning actuel |

L'original mentionne UCAC-ICAM/Yansoki : il s'agit du contexte du document source, pas de celui de notre réalisation.

## Exigences et choix

| Origine | Éléments |
|---|---|
| Cahier des charges | Application web ; espaces étudiant/personnel/direction ; menus et disponibilités ; panier, commandes sur place ou livraison, historique et réclamations ; fidélité et parrainage ; statistiques, promotions et compléments ; MVC ou similaire ; API REST documentée ; AJAX ; GitHub, Jira, Figma, démonstration Vercel, tests et étape CI/CD ; couleurs #cfbd97 / #000000 |
| Indications techniques du CDC | React, Vue ou Angular cités en exemple ; PHP/Laravel cités pour le serveur |
| Choix proposés du groupe | React, TypeScript, Vite, Laravel 13, PostgreSQL, Sanctum et GitHub Actions ; serveur central modulaire et base partagée |
| Recommandations à valider | Panier par restaurant ; rôle plateforme ; contraintes et modèle de données ; portée de la fidélité/parrainage ; états de commande et règles d'annulation |

Le paiement apparaît comme objectif puis bonus : décision de périmètre à faire valider avant implémentation. Les exemples « 1000 FC = 1 point » et « 15 points = 1000 FC de réduction » conservent leurs valeurs, mais ne sont pas des paramètres approuvés. Les échéances, budget, règles de livraison, arrondis et politique de protection des données restent à confirmer dans Jira.
