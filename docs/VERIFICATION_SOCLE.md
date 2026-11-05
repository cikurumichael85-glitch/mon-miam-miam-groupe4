# Vérification du socle — 22 octobre 2026

| Contrôle exécuté localement | Résultat |
|---|---|
| Empreinte SHA-256 du téléchargement PHP | Conforme à celle de php.net |
| PHP séparé | 8.5.12, extensions PostgreSQL présentes |
| Installation frontend | Dépendances installées, package-lock.json créé |
| `npm run lint` | Réussi |
| `npm run build` | Réussi, Vite 8.3.5 |
| Affichage navigateur local | Page française Mon Miam Miam visible, contexte ULC-ICAM / RDC et CDF / FC |
| Installation Laravel | Framework 13.38.0, composer.lock créé |
| `composer validate --strict` | Réussi après mise en cohérence du verrouillage |
| `composer check-platform-reqs` | Réussi sur PHP 8.5.12 |
| `php artisan test` | 2 tests du squelette, 2 assertions, réussis |
| Exclusion Git | .env, vendor, node_modules et dist ignorés |

Les deux tests fournis par Laravel sont des tests initiaux du squelette ; ils ne valident pas les fonctionnalités du cahier des charges. Aucun test de base métier, de connexion PostgreSQL ou d'isolation entre restaurants n'a été exécuté. Le moteur SQLite mentionné par le fichier PHPUnit généré ne sert pas de remplacement à PostgreSQL : aucune requête de base n'est effectuée par ces deux tests.

Le dépôt privé est publié sur `https://github.com/cikurumichael85-glitch/mon-miam-miam`. Le premier workflow GitHub Actions a réussi : [exécution 39747527807](https://github.com/cikurumichael85-glitch/mon-miam-miam/actions/runs/39747527807), commit `cfedc4a`. Les protections de branche ne sont pas confirmées. Les six invitations ont été envoyées ; cinq collaborateurs ont accepté au contrôle de l’organisation du dépôt, Assek reste en attente. Voir ACCES_GITHUB.md pour le détail. La tâche MMM-39 reste en cours jusqu'à vérification des accès et relecture du binôme.

PHP est installé séparément pour ce projet ; la configuration globale de l'ancien XAMPP reste disponible pour les exercices existants.
