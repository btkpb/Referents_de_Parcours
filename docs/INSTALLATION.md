# E2C MENAET V2 — installation

## Pré-requis
- PHP 8.4+ avec `pdo_mysql`
- MySQL/MariaDB
- Composer 2.x
- Symfony CLI facultatif

## Installation Windows
```powershell
cd C:\chemin\e2c-mtfpa-v2-complete
& "C:\PHP85\php.exe" "C:\ProgramData\ComposerSetup\bin\composer.phar" install
& "C:\PHP85\php.exe" bin\console doctrine:database:create --if-not-exists
& "C:\PHP85\php.exe" bin\console doctrine:migrations:migrate --no-interaction
& "C:\PHP85\php.exe" bin\console cache:clear
```

Si la base est déjà créée, ne pas recréer. Vérifier `.env` puis lancer les migrations.

## Compte administrateur
Créer un utilisateur avec un hash Symfony via :
```powershell
& "C:\PHP85\php.exe" bin\console security:hash-password
```
Puis insérer l'utilisateur dans `app_user` avec le hash obtenu et `roles` JSON `["ROLE_ADMIN"]`.

## Lancer
```powershell
& "C:\PHP85\php.exe" -S 127.0.0.1:8000 -t public
```
Ouvrir `http://127.0.0.1:8000/login`.

## Modules V2
Dashboard, campagnes, inscription candidat, contrôle d'éligibilité, pièces justificatives, planification des entretiens, évaluation jury /40, visites médicales, admission, annuaire, chantiers, suivi pédagogique/psycho-social, suivi dans l'emploi, sécurité et journalisation.

> Cette version est une base de production structurée. Pour un déploiement public, configurer HTTPS, SMTP/SMS réel, sauvegardes, stockage privé des documents et secrets d'environnement.
