# Campus Found

A lost-and-found portal designed for a UPSA student project. This is an independent project, with illustrative campus location names.

## Included versions

- `public/` and `database/` contain the real PHP application with persistent MySQL records and student/security authentication.
- `dist/` contains the Sites interactive demonstration. Its fictional data lives only in memory and resets on reload. The role buttons are demonstration controls.

## Run the PHP application with XAMPP

1. Install XAMPP with PHP 8.x and start MySQL. Create an empty database called `campus_found` using phpMyAdmin.
2. Extract the project into a local folder. Copy `config.example.php` to `config.php` and enter your MySQL credentials. Keep `config.php` private.
3. From the project folder, run `C:\xampp\php\php.exe scripts\setup.php` once against the empty database.
4. Create the security account using `C:\xampp\php\php.exe scripts\create-security.php`. Enter the officer's name, email and a password of at least 12 characters.
5. Run `C:\xampp\php\php.exe -S 127.0.0.1:8080 -t public` and visit `http://127.0.0.1:8080`.
6. Register two student accounts through the interface. Use separate browser profiles or sign out between roles to demonstrate the workflow.

Serve only the `public` directory. For Apache, set the virtual host DocumentRoot to the project's `public` folder. The built-in PHP server is intended for the local class demonstration. Use HTTPS when deploying with real accounts.

## Demonstration walkthrough

1. Student A reports a found black calculator at the Library, keeping its star-shaped scratch private.
2. Student B reports a lost black calculator. Suggested matches rank same-category reports by colour, location and dates within seven days.
3. Student B claims the found report and describes the scratch.
4. Security records physical receipt, compares the private evidence and approves the claim with a review note.
5. Student B opens My activity to obtain the collection code.
6. Security enters that code and records the handover. The found report becomes Returned and the code cannot be reused.
7. Student B closes their separate lost report. The history of both reports is retained.

Additional features include category/colour watchlists, private exact ID search, hotspot counts, rejection notes and a public item timeline. Illustrations represent categories, not actual item photographs. Watchlist notifications are refreshed in the app, not sent by email or SMS.

## Database and access

`database/schema.sql` defines users, locations, reports, claims, events, watches and login attempts. It also seeds six illustrative location names. `database/sample-data.sql` adds fictional students and sample reports after schema setup. Sample accounts use an unusable password value. Create real accounts through registration or the security CLI.

Student registration always assigns the student role. Security accounts are provisioned only with the local CLI. The server enforces role checks and ownership, validates requests, uses PDO prepared statements, hashes passwords, regenerates sessions on login and checks CSRF tokens on POST requests. Public report responses omit private evidence and document hashes. ID reports have neutral public titles and descriptions. Collection/review operations lock the report in a transaction on MySQL to prevent conflicting approvals.

For an institutional rollout, agree collection procedures with campus security, verify account identities, establish retention and account-recovery procedures, and configure monitored hosting. Current accounts are self-registered and email ownership is not verified.

## Build the Sites demo

Run `node scripts/build-demo.mjs`. Serve `dist` with any static web server. Select Student demo, claim the calculator using “a small star scratched on the back”, switch to Security demo, approve, switch back to retrieve the code, then record collection at the security desk. For private ID search use `DEMO123`.

## Tests

`tests/integration.py` runs against a fresh local PHP server and an isolated database. It exercises registration, sign-in, authorisation, private-field exclusion, matching inputs, duplicate claims, custody, competing approvals, incorrect collection codes, collection replay, watchlists and ID privacy. SQLite can be used for local test execution with PHP's PDO SQLite extension. MySQL remains the assignment deployment target.

Set `PHP_BINARY` and `PHP_EXTENSION_DIR` to your local PHP paths. Set `CF_CLI=1` to run the same API workflow assertions through an isolated PHP input harness when local sockets are unavailable. The completed verification used that CLI harness and SQLite. HTTP/browser testing and MySQL execution remain unverified in this build environment.

## Delivery status

The Sites project was registered, but source publication failed because this environment could not resolve the Sites Git hostname. There is no verified live deployment. The connected GitHub account exposed no repositories and no repository-creation capability, so no GitHub upload was completed. The ZIP is ready to upload to a new private repository.

The requested document runtime could not load. `docs/report-content.md` and `docs/presentation-content.md` contain the prepared content for the selected Design Report and Simple Dark Mode templates. Finished Word and PowerPoint files remain pending template rendering and verification. Student names and index numbers are left for the project team to supply.
