# CLAUDE.md

## Documentation

`docs/` est la source de vérité, à alimenter au fil du développement :

- `docs/dev/` — conventions, architecture, schéma de base de données.
- `docs/product/` — description des fonctionnalités.

## Avant d'écrire du code

Lire `docs/dev/architecture/backend-architecture.md`. C'est la convention du projet :
layering `Infrastructure → UseCase → Domain`, `<Noun>DataModel` sous `Domain/DTO/DataModel/`
(le mot « entity » n'existe pas ici), accès données via Gateway, `OutputFactory` obligatoire,
validators qui accumulent les violations, `final readonly` par défaut, comparaisons Yoda.

Sa §13 est la checklist pour ajouter une feature, sa §15 la liste des anti-patterns.

## Commandes

Rien ne se lance depuis l'hôte : tout passe par `make`, qui entre dans le bon conteneur.

| Cible | Fait |
| --- | --- |
| `make setup` | installe les hooks git (`.git-hooks/`) |
| `make build` | rebuild complet, conteneurs up, migrations sur la base de dev |
| `make start` | `build` + `reset-db` + `load-fixtures` |
| `make stop` | arrête les conteneurs |
| `make reset-db` / `make reset-test-db` | drop + create + migrate (dev / test) |
| `make load-fixtures` | charge les fixtures Doctrine sur la base de dev |
| `make cs-fix` / `make stan` | PHP-CS-Fixer / PHPStan niveau 8 |
| `make test` | suite complète (migrations, puis unit, puis integration) |
| `make test-unit` / `make test-integration` | une suite, avec `file=`, `class=`, `debug=true`, `coverage=true` |
| `make pre-commit` | `cs-fix` + `stan` + `test-unit`, la séquence du hook |
| `make db-connect` | shell MySQL sur la base de dev |

Les fronts sont indépendants : `frontend/website` et `frontend/admin`, chacun avec
`npm install` puis `npm run dev`.
