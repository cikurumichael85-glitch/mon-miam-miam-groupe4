# Carte locale de Kinshasa — 23 octobre 2026

Cette sélection répond à une demande du groupe. Elle ne constitue ni une obligation du cahier des charges ni un relevé des prix pratiqués sur le marché.

## Carte enregistrée

Les 24 articles sont enregistrés dans le backend local, restaurant n° 1 « Démonstration A — Kinshasa ». Le second restaurant n’est pas modifié. Les mêmes propositions sont disponibles dans le catalogue de démonstration Mboka ; les deux modes restent distincts.

| Catégorie | Article | Prix FC |
| --- | --- | ---: |
| Plats congolais | Riz aux haricots | 2 500 |
| Plats congolais | Fufu, fumbwa et poisson | 6 000 |
| Plats congolais | Fufu, pondu et poisson | 5 000 |
| Plats congolais | Pondu et chikwangue | 3 000 |
| Plats congolais | Madesu et chikwangue | 2 500 |
| Plats congolais | Assiette de makemba | 2 000 |
| Plats congolais | Poulet à la moambe | 6 000 |
| Plats congolais | Poisson à la braise | 6 000 |
| Plats congolais | Poulet braisé et makemba | 6 000 |
| Fast-food | Shawarma au poulet | 6 000 |
| Fast-food | Tacos au poulet | 8 000 |
| Fast-food | Burger maison | 8 000 |
| Fast-food | Double burger fromage | 12 000 |
| Fast-food | Pizza au fromage | 12 000 |
| Fast-food | Pizza au poulet | 16 000 |
| Fast-food | Sandwich au poulet | 6 000 |
| Boissons sucrées | Coca-Cola | 1 500 |
| Boissons sucrées | Fanta orange | 1 500 |
| Boissons sucrées | Sprite | 1 500 |
| Boissons sucrées | Vital’o grenadine | 1 500 |
| Bières et cidres | Bavaria | 4 000 |
| Bières et cidres | Castel Beer | 4 000 |
| Bières et cidres | Heineken | 4 000 |
| Bières et cidres | Savanna Dry | 4 000 |

« Fufu fubwa passoin » est interprété comme fufu, fumbwa et poisson. Savanna Dry est un cidre ; Bavaria est ici une bière alcoolisée. Portions, formats et recettes proposés restent à confirmer par le restaurant. Aucune conversion monétaire.

## Gestion de la carte

Dans l’application, choisir « Restaurants connectés », se connecter avec le compte administrateur attribué, puis ouvrir **Espace restaurant → Carte**. Les commandes permettent d’ajouter une catégorie ou un article, de modifier nom, description et prix, de changer la disponibilité, de désigner un plat du jour et d’archiver un article.

Les droits du compte administrateur sont limités au restaurant n° 1. Le bouton « Administration plateforme » éventuellement visible ne confère aucun droit global.

## Photographies

Les images sont locales, avec auteurs, sources, licences et empreintes dans `public/images/credits.json` et [CREDITS_PHOTOS.md](CREDITS_PHOTOS.md). Les photos et variantes de cadrage conservent leur licence propre. Les marques ne signalent aucun partenariat.

Les photos sont illustratives : certaines ne montrent pas tous les accompagnements, le double steak ou le poulet de la pizza. Ces différences apparaissent sur les cartes et fiches. Vital’o utilise une photo générique de grenadine, explicitement identifiée ; aucune photo libre de sa bouteille n’a été retenue.

Le backend actuel ne possède pas de champ photo d’article. Les illustrations de cette sélection sont associées côté frontend aux noms exacts des articles du seul restaurant local n° 1. Elles n’écrasent jamais les prix ou disponibilités du serveur. Un nouvel article ou un article renommé utilise un visuel neutre tant qu’une illustration n’est pas ajoutée. Le téléversement de photos depuis l’espace administrateur n’est pas implémenté dans cette livraison.

## Vérifications et outils

Import local : 4 catégories et 24 articles créés dans une transaction ; aucun article préexistant à remplacer. Compte actif et rôle `restaurant_admin` du restaurant n° 1 vérifiés. Lecture du catalogue connecté et commandes administrateur observées dans le navigateur. Aucun nouveau compte de test, commande ou paiement créé.

Les tests, le lint et la compilation n’ont pas été relancés pour cette modification, conformément à la préférence d’exécution manuelle. Les résultats antérieurs concernent la version précédente. Pour vérifier manuellement, depuis le dossier `frontend` :

```powershell
npm run lint
npm test
npm run build
```

Les serveurs existants sont réutilisés ; aucun lancement supplémentaire n’est requis tant que l’application répond sur le port 5174. Rafraîchir la page puis sélectionner « Restaurants connectés » si nécessaire.

Jira : conserver cette demande comme décision de contenu et présenter la capture de la carte et ce tableau au professeur. Git/GitHub : sources et kits locaux seulement, aucune publication ni modification distante. Figma : la maquette sauvegardée reste distincte du catalogue React et n’est pas modifiée par cet ajout.

Le PDF frontend antérieur conserve son état historique ; ce document précise le complément de carte et ses limites.
