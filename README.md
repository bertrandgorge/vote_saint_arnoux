# Vote Saint-Arnoux — sélection des architectes

Outil de notation des dossiers de candidature d’architectes par le jury (PHP 8 / MySQL 8, sans dépendance).

## Fonctionnalités

- **Connexion par lien magique** (pas de mot de passe), puis cookie de session persistant (10 ans).
- **Accueil** : classement en direct + liste des dossiers dans un ordre aléatoire, identique pour tous.
- **Grille de notation** : 6 critères pondérés, échelle *Non évalué / 0 à 5*, enregistrement automatique.
- **Tableau de bord par architecte** (rafraîchi toutes les 3 s) : moyennes, écarts types colorés, une ligne par juré.
- **Réglages** (administrateurs) : architectes (dont lien Google Drive), import TSV, jury, liens de connexion.

## Règles de calcul

- Score sur 100 = Σ(poids × note) / Σ(poids des critères notés) × 20. Un critère *Non évalué* est exclu
  et les poids restants sont renormalisés.
- **Score officiel** d’un architecte : calculé sur la moyenne de chaque critère des seuls membres **votants**.
- La ligne « Moyenne du jury » et les **écarts types** incluent les membres **consultatifs**.
- Écart type (population) : vert < 0,75 ; orange 0,75–1,25 ; rouge > 1,25 (×20 pour la colonne score / 100).

## Développement

```bash
cp .env.example .env
docker compose up -d --build
cp config/jury.example.php config/jury.local.php   # puis y saisir le jury réel
docker compose exec web php scripts/setup.php   # tables + jury initial + import du TSV d’architectes/
```

| Service  | URL                    |
|----------|------------------------|
| Appli    | http://localhost:8080  |
| DbGate   | http://localhost:8081  |
| Mailpit  | http://localhost:8025 (e-mails de connexion) |

Pour se connecter sans passer par l’e-mail :

```bash
docker compose exec web php -r 'require "src/bootstrap.php"; echo create_login_link(2, 3600), "\n";'
```

## Déploiement sur O2Switch

1. Créer la base MySQL et son utilisateur dans cPanel.
2. Envoyer le dépôt (hors `.git`, `docker/`) sur l’hébergement, par exemple dans `~/vote/`.
3. Faire pointer le domaine ou sous-domaine sur **`~/vote/public`** (racine du document).
4. Copier `config/config.local.php.example` en `config/config.local.php` et le compléter
   (`app_url` en https, accès base, `mail_from` sur une adresse du domaine pour la délivrabilité).
5. Déposer `config/jury.local.php` et le TSV (non versionnés) puis initialiser : `php ~/vote/scripts/setup.php` (terminal cPanel / SSH), ou importer `sql/schema.sql`
   dans phpMyAdmin puis importer le TSV depuis la page Réglages.
6. Activer le certificat SSL (AutoSSL) : le cookie de session est alors marqué `Secure`.

## Structure

```
public/        racine web : index.php (routeur), assets/
src/           logique : auth, scores, import, réglages
templates/     vues PHP
config/        configuration (config.local.php non versionné)
sql/schema.sql schéma de la base
scripts/       setup.php (initialisation idempotente)
architectes/   fichier TSV source
```
