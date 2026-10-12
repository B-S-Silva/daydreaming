# Copilot Instructions for StudyAbroad PHP Login System

## Project Overview
This is a PHP/MySQL web platform for international study management, featuring user authentication, dashboards, forums, and test management. The codebase is organized for modularity and security, with clear separation between authentication, admin, and user-facing features.

## Key Architecture & Data Flow
- **Entry Point:** `index.php` redirects to `login.php`.
- **Authentication:** Handled via `login.php`, `cadastro.php`, `recuperar_senha.php`, and `logout.php`. Session management and password hashing use PHP's built-in functions.
- **Dashboard & Admin:** `dashboard.php` (user dashboard), `admin_forum.php`, `admin_questoes.php`, and `badges_manager.php` (admin tools).
- **Forum:** `forum.php` and related SQL files manage community discussions.
- **Test Management:** `simulador_provas.php`, `executar_teste.php`, `processar_teste.php`, and `historico_testes.php` handle test simulation and results.
- **Database:** SQL structure in `db_structure.sql`, `forum_structure.sql`, and `reset_database.sql`. Use these for setup and resets.
- **Config:** `config.php` manages DB connection, using environment variables for Docker or defaults for local dev.
- **Static Assets:** Images in `imagens/`, CSS in `public/css/style.css`, JS in `public/js/main.js`.

## Developer Workflows
- **Local Development:**
  - Start server: `php -S localhost:8080`
  - Access app: [http://localhost:8080](http://localhost:8080)
- **Docker:**
  - Start: `docker compose up -d`
  - Stop: `docker compose down`
  - Logs: `docker compose logs -f`
  - Reset DB: `docker compose down -v`
- **Database Setup:**
  - Import: `mysql -u root -p sistema_login < db_structure.sql`
  - Reset: `mysql -u root -p sistema_login < reset_database.sql`

## Project-Specific Patterns
- **Security:** Always use PDO prepared statements for DB access. Sanitize output with `htmlspecialchars()`. Passwords are hashed with `password_hash()`.
- **Session Handling:** All user pages check authentication via `verificar_auth.php`.
- **Admin Features:** Admin scripts are separate and check for admin privileges.
- **Custom CSS:** Use variables and modular styles in `public/css/style.css`.
- **JS Interactivity:** All client-side logic is in `public/js/main.js`.

## Integration Points
- **phpMyAdmin:** Available at [http://localhost:8081](http://localhost:8081) in Docker setup.
- **External Libraries:** Minimal; most logic is custom PHP/JS/CSS.

## Examples
- To add a new test type, update `db_structure.sql` and relevant PHP files in the root.
- To add a new admin feature, create a new `admin_*.php` file and link it from `dashboard.php`.

## References
- See `README.md` for setup, credentials, and file descriptions.
- See SQL files for DB structure and reset instructions.
- See `config.php` for environment variable usage.

---
**For AI agents:**
- Always check for session/auth before exposing user/admin features.
- Use project file structure and naming conventions for new features.
- Prefer custom logic over frameworks unless integrating with Docker or external services.

---
*Ask the user for feedback on unclear or missing sections to improve these instructions.*
