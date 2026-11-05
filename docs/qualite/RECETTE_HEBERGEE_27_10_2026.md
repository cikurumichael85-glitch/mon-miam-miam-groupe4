# Mon Miam Miam — Recette du site hébergé

Groupe 4 · ULC-ICAM · RDC · 27 octobre 2026

Site : https://mon-miam-miam-groupe4.vercel.app/

## Résultat principal

**Le palier de cinquante visiteurs simultanés n'est pas validé dans cet essai.** Huit appels API ont dépassé le délai de trente secondes du banc. La montée en charge a été arrêtée. Les pages HTML et les API sont mesurées séparément.

## 1. Navigation publique en ligne

Chaque visiteur a demandé la page publique puis une lecture API : restaurants, carte ou historique invité. Le refus HTTP 401 de l'historique sans connexion est attendu et n'est pas une panne.

| Visiteurs en parallèle | Lectures API | Médiane API | p95 API | Délais dépassés |
|---|---:|---:|---:|---:|
| 5 | 5 | 2621 ms | 3298 ms | 0 |
| 10 | 10 | 5121 ms | 5646 ms | 0 |
| 20 | 20 | 6668 ms | 9579 ms | 0 |
| 50 | 50 | 26128 ms | 30007 ms | 8 |

Ce scénario compte **172 requêtes mesurées**. Au dernier palier : 27 réponses API HTTP 200, 15 refus 401 attendus et 8 expirations côté banc, sans HTTP 500 constaté. La page HTML a répondu dans tous les cas du palier 50, avec un p95 de 263 ms.

Le palier de vingt visiteurs n'a pas produit d'erreur inattendue, mais son p95 API de 9579 ms reste élevé. Il ne valide donc pas le confort d'utilisation.

Les temps incluent le réseau du PC, Vercel et son relais, Laravel et PostgreSQL hébergé. Ils ne suffisent pas à attribuer la lenteur à une seule composante. Ce test court ne détermine pas une capacité maximale stable.

## 2. Dix comptes clients fictifs dédiés

Le titulaire a explicitement autorisé leur création, sans commande ni paiement. Les mots de passe étaient aléatoires et hachés avec bcrypt, coût 12 ; aucun compte réel n'a été lu ou remplacé.

- Deux groupes de cinq connexions simultanées : dix succès.
- Dix sessions distinctes utilisées en parallèle : identités correctes et historiques personnels vides.
- Aucun mélange de session ni rôle professionnel accordé.
- Dix déconnexions réussies, suivies de dix refus 401 de la lecture du profil.
- 71 requêtes mesurées, sans erreur inattendue.

Les p95 des deux groupes de connexion étaient de 10325 ms et 10590 ms ; maximum du scénario : 10590 ms. Les connexions ont fonctionné, avec des délais perceptibles. La limite de dix connexions par minute et par IP n'a pas été relevée. Ce n'est pas un test de cinquante comptes se connectant exactement au même instant.

## 3. Dimensions du site public

9 observations, sans débordement horizontal détecté :

- Accueil : 320 × 740, 375 × 812, 390 × 844, 768 × 1024 et 1366 × 900.
- Panier invité : 320 × 740, 375 × 812, 768 × 1024 et paysage 844 × 390.

L'article ajouté est resté dans le panier du navigateur, ensuite vidé. Le panier demandait la connexion avant devis ou commande. Aucune commande n'a été confirmée. Ces dimensions simulées ne remplacent pas des essais sur de vrais appareils iOS/Android, leurs claviers et leurs réseaux mobiles.

## 4. Nettoyage vérifié dans Neon

| Contrôle ciblé | Résultat |
|---|---:|
| Comptes fictifs créés | 10 |
| Encore actifs | 0 |
| Désactivés, statut suspended | 10 |
| Commandes de ces comptes | 0 |
| Rôles professionnels | 0 |

Les dix comptes ont été désactivés après fermeture de leurs sessions. Ils ne sont pas présentés comme supprimés. Les fichiers locaux contenant les mots de passe et les empreintes de préparation ont été retirés ; ils ne figurent pas dans les preuves partageables. Aucun paiement, appel marchand, PIN ou e-mail de notification n'a été utilisé. Aucune commande ou permission existante n'a été modifiée.

## 5. Formulation pour la présentation

« La recette en ligne a confirmé le fonctionnement de dix connexions fictives et l'isolation de leurs sessions. Les paliers publics ont été testés jusqu'à cinquante visiteurs ; huit appels API ont alors dépassé trente secondes. Nous ne déclarons donc pas ce palier validé. L'accueil et le panier ont été examinés sur plusieurs dimensions de téléphone et tablette. »

Le code applicatif et les ressources d'hébergement n'ont pas été modifiés pendant cette recette. Les résultats locaux précédents restent distincts et ne remplacent pas ces mesures.

## 6. Prochaine étape fondée sur les mesures

1. Mesurer les temps du relais API, des requêtes/connexions PostgreSQL et les ressources du service à faible charge pour isoler la cause.
2. Corriger le facteur réellement constaté, sans désactiver les protections de connexion.
3. Rejouer d'abord 5/10/20 visiteurs avec un objectif de délai validé, puis seulement reprendre le palier 50.
4. Effectuer ensuite une recette prolongée avant toute annonce de capacité.

Ce bilan ne certifie ni l'absence universelle de bugs ni le paiement réel.

## 7. Preuves et équipe

Les JSON et captures du dossier local .local/hosted-test conservent les mesures, l'accueil mobile, le panier et les comptes désactivés, sans cookie ni mot de passe. L'archive de preuves contient uniquement cette sélection.

Binôme D : recette et présentation ; binômes B/C : relecture des interfaces et des API. Ces responsabilités ne constituent pas des commits individuels ou des relectures déjà réalisés. Le rapport peut servir de preuve Jira ; aucun ticket, statut ou fichier Jira n'a été modifié.

## 8. Reproduction manuelle du banc public après correction

Prérequis : Node.js 24, accès Internet, fenêtre de recette définie. Cette commande relance les paliers publics 5/10/20/50 et s'arrête après erreur ou forte latence ; elle n'envoie que des GET aux routes prévues. Elle ne constitue pas une correction du palier 50.

Dossier PowerShell : C:\Users\J\Desktop\projet miam miam
Commande : node .local/hosted-test/public.mjs
Résultat : .local/hosted-test/public-result.json

La recette authentifiée n'est pas relancée par cette commande. Ses identifiants fictifs ont été retirés et ses comptes désactivés ; une nouvelle recette demande une nouvelle préparation ciblée. Aucun serveur local, migration, installation ou compilation n'est nécessaire pour lire les preuves de cette livraison.
