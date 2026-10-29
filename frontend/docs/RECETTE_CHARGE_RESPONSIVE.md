# Mon Miam Miam — Recette de charge et affichage mobile

Groupe 4 · ULC-ICAM · République démocratique du Congo  
Date : 27 octobre 2026 · Monnaie : CDF / FC

## 1. Résultat et portée

Le banc local a traité **50 comptes fictifs et 500 requêtes sans erreur inattendue**. Les passages de commandes ont été lancés par groupes de 5, 15 et 30 clients ; un dernier groupe de 50 sessions a testé simultanément l'accès interdit aux commandes d'un autre client. Ce résultat ne signifie pas que 50 commandes ont été confirmées exactement au même instant.

Les contrôles ont utilisé le code applicatif, PostgreSQL local dans `mon_miam_miam_test`, quatre processus PHP de développement, les sessions en base et le hachage bcrypt avec un coût de 12. Chaque client utilisait son propre cookie et une adresse loopback distincte. Aucun appel au domaine public, à Neon, à Render ou à EasyPay pendant la charge. Les bases applicatives locale et hébergée n'ont pas été modifiées.

## 2. Mesures obtenues

Les temps incluent le traitement et l'attente dans les quatre processus locaux. p95 signifie que 95 % des réponses du palier sont arrivées dans ce délai.

| Clients en parallèle | Parcours | Requêtes | Médiane | p95 | Maximum |
|---|---|---:|---:|---:|---:|
| 5 | Connexion, carte, devis, double confirmation et historique | 45 | 216 ms | 778 ms | 886 ms |
| 15 | Connexion, carte, devis, double confirmation et historique | 135 | 717 ms | 1627 ms | 1962 ms |
| 30 | Connexion, carte, devis, double confirmation et historique | 270 | 1522 ms | 3704 ms | 4677 ms |
| 50 | lecture interdite de la commande du voisin | 50 | 1170 ms | 2077 ms | 2230 ms |

Sur l'ensemble des 500 requêtes : médiane **1209 ms**, p95 **3388 ms**, maximum **4677 ms**. Les 50 réponses HTTP 404 sont attendues : elles refusent l'accès aux commandes d'un autre client. Aucun HTTP 500, aucune erreur réseau ou expiration du délai n'ont été observés dans ce scénario.

## 3. Intégrité et sécurité

- Les 50 clients ont réellement ouvert une session par le formulaire API, puis relu leur identité, sans mélange de sessions.
- Chaque devis de 2 500 FC a reçu deux confirmations concurrentes avec la même clé ; une seule commande a été conservée par client.
- Audit en base avant la recette visuelle : **50 commandes**, total **125 000,00 CDF**, **0 paiement**, aucune quantité de commandes incorrecte par client.
- La lecture de session depuis un autre processus PHP a réussi : les cookies ne dépendent pas d'un seul processus.
- Les 50 accès aux commandes du voisin ont été refusés.
- Contrôle supplémentaire séparé : cinq mots de passe erronés donnent HTTP 422, le sixième HTTP 429. Les protections de fréquence n'ont pas été supprimées. Les règles actuelles limitent aussi les connexions par adresse IP ; une connexion partagée par une classe ou un réseau Wi-Fi n'est donc pas équivalente à cinquante adresses distinctes.

## 4. Affichage et parcours dans le navigateur

**25 observations de dimensions**, sans débordement horizontal détecté. Largeurs examinées : 320, 375, 390, 768 et 1 366 pixels ; panier également examiné en paysage 844 × 390.

Écrans examinés : accueil et carte, ajout de restaurant, espace administrateur, espace serveur, espace client et panier. Les champs mobiles examinés utilisent 16 px pour éviter le zoom automatique à la saisie ; le zoom manuel reste possible.

Une anomalie visuelle a été trouvée et corrigée : le bouton Menu pouvait couper son texte sur deux lignes. Le bouton reste maintenant sur une ligne, avec une cible de 44 px ; les espacements du panier ont été ajustés. Oxlint et compilation TypeScript/Vite réussis après correction.

Recette locale réelle, données fictives uniquement : connexion administrateur, création réussie d'un deuxième restaurant avec attribution de son administrateur, déconnexion, connexion serveur avec son interface distincte, puis connexion client, ajout au panier et devis obtenu au premier clic. Aucun règlement n'a été initié ; les routes de paiement du banc sont bloquées et les appels HTTP externes sont interdits.

## 5. Limites à présenter au professeur

Ce test court ne certifie ni l'absence universelle de bugs ni la capacité de Render/Vercel/Neon en production. Il ne remplace pas un essai prolongé, un test sur une infrastructure de préproduction comparable, ni un essai sur de vrais téléphones iOS et Android avec leurs claviers, réseaux mobiles et navigateurs.

Le pic d'écriture mesuré est de 30 clients en parallèle et la lecture protégée de 50 sessions en parallèle. Les verrous SQL et le coût de connexion peuvent allonger les délais à une charge supérieure. Aucune capacité de 100, 1 000 ou davantage d'utilisateurs n'est annoncée. La latence croît nettement avec la concurrence dans ce banc.

Les réponses positives d'EasyPay, les SMS, les e-mails réels et les paiements ne sont pas validés par ces essais. Le refus administrateur historique sur le site public n'est pas reproduit dans cette base fictive et n'est pas présenté comme définitivement résolu pour le titulaire.

## 6. Rejouer le banc local

Prérequis : Node.js 24, PHP 8.5 compatible dans le chemin indiqué dans `backend/README.md`, PostgreSQL local actif, dépendances existantes, configuration privée de connexion déjà opérationnelle, ports 8170 à 8173 libres. Ne pas lancer PHPUnit ou une autre recette sur la base de test pendant cette charge.

**Attention : le banc réinitialise exclusivement `mon_miam_miam_test`.** Ses gardes refusent la base applicative, une base distante, un autre environnement et un paiement activé. Les comptes fictifs et les scripts privés restent dans `.local/load-test`, exclus de Git.

```powershell
Set-Location -LiteralPath 'C:\Users\J\Desktop\projet miam miam'
node .local/load-test/run.mjs
```

Le script arrête les quatre processus qu'il a lancés et écrit `result.json`. Ne pas modifier ses gardes pour viser le site public. Les réglages et les mots de passe réels ne sont jamais nécessaires dans le rapport.

## 7. Preuves et organisation de l'équipe

- Charge : `.local/load-test/result.json`.
- Audit : `.local/load-test/database-audit.json`.
- Protection des connexions : `.local/load-test/rate-limit.json`.
- Dimensions : `.local/load-test/responsive.json`.
- Captures : `.local/load-test/admin-mobile.png`, `server-mobile.png`, `client-mobile.png` et `client-panier-mobile.png`.

Binôme D : prise en main de la recette ; binômes B/C : relecture des observations d'interface et d'API. Ces rôles ne constituent pas des contributions Git ou des avis de relecture déjà réalisés. Jira conserve le lien vers ce rapport, Git/GitHub les versions sélectionnées et les contrôles ; aucune pièce jointe Jira ni clôture automatique de tâche pendant cette recette.
