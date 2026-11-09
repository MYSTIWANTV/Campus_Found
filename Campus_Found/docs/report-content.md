# Campus Found system design report

Prepared content for the selected Design Report Word template. Formatting and visual verification remain pending because the document runtime was unavailable.

Student name(s) and index number(s) — to be supplied by the project team.

## Executive summary

Campus Found is a proposed lost-and-found portal for the UPSA community. It brings student reports, ownership claims and security handover records into one application. The project includes a PHP application with a MySQL schema and a separate interactive browser demonstration with fictional records.

The central workflow connects a found item to a claimant through private ownership evidence. Security must record physical custody before approving a claim. Approval reserves the item and produces a collection code. Recording collection retires that code and updates the item to Returned.

## Problem statement

Students may search multiple communication channels when they lose belongings, while finders may report the same items elsewhere. This project addresses the resulting difficulty in locating relevant reports and establishing a reliable return process. This is the project's problem hypothesis. No campus survey or measured loss statistics have been collected.

The proposed system gives students a shared report board and gives security a record of receipt, review and collection. Its objectives are to improve report discovery, protect identifying details during claims, and preserve a history of item returns.

## Users and scope

Guests browse public reports and filter by category, location or keywords. Registered students submit reports, keep identifying details private, claim found items, save watchlists and search privately for IDs. Security officers confirm receipt, compare ownership evidence, approve or reject claims, and record handovers. The local administrator creates security accounts using a command-line script.

Accounts use email and password. Email ownership and UPSA affiliation are not automatically verified. The project uses illustrative location names and does not claim official university endorsement.

## Application architecture

The browser displays HTML and CSS and handles interactions with JavaScript. It sends JSON requests using Fetch to public/api.php. PHP validates requests, checks session and role permissions, and executes prepared SQL statements through PDO. MySQL stores users, reports, claims and history. PHP returns JSON responses for rendering in the browser.

The Sites demonstration shares the interface but uses fictional records held in browser memory. Its role buttons support a classroom walkthrough and its data resets on reload. The PHP application supplies the real authentication and persistence required by the assignment.

## Database design

| Table | Main fields and types | Relationships |
| --- | --- | --- |
| users | id INTEGER primary key, name VARCHAR(80), email VARCHAR(190) unique, password VARCHAR(255), role VARCHAR(15) | One user has many reports, claims and watches |
| locations | id INTEGER primary key, name VARCHAR(80) unique | One location has many reports |
| reports | id INTEGER primary key, user_id INTEGER, location_id INTEGER, type VARCHAR(10), title VARCHAR(100), category VARCHAR(30), color VARCHAR(30), event_date VARCHAR(10), description TEXT, private_detail TEXT, document_hash VARCHAR(64), status VARCHAR(20), custody INTEGER | Foreign keys to users and locations |
| claims | id INTEGER primary key, report_id INTEGER, user_id INTEGER, evidence TEXT, status VARCHAR(20), collection_code VARCHAR(30), review_note TEXT, attempts INTEGER | Foreign keys to reports and users. Unique report_id and user_id pair |
| events | id INTEGER primary key, report_id INTEGER, actor_id INTEGER, label VARCHAR(150), created_at VARCHAR(25) | Foreign keys to reports and users |
| watches | id INTEGER primary key, user_id INTEGER, category VARCHAR(30), color VARCHAR(30) | Foreign key to users |
| login_attempts | identity_hash VARCHAR(64) primary key, attempts INTEGER, window_start INTEGER | Rate-limit counters keyed by hashed identity |

Dates use validated ISO strings. The SQL schema includes indexes for report filtering, claim queues and report history. The users table stores each person once and foreign keys link reports and claims to that record. Location names are maintained in their own table.

## Distinctive features

Matching first selects open found reports in the same category as a lost report. A matching colour, matching location or report date within seven days adds a reason that improves ranking. These rules provide explainable suggestions and leave ownership decisions with security.

Ownership evidence and the finder's identifying detail are absent from public report responses. The security workspace compares them before a decision. A report must have a custody record before approval. A transaction locks the report during review so conflicting approvals cannot reserve the same item.

ID reports automatically receive neutral public titles and descriptions. The document number is normalised and stored as a SHA-256 hash for exact matching. Signed-in users have a limited number of private searches per fifteen-minute period. A matching number is a lead and still requires verification.

Watchlists display matching category and colour reports in My activity. Notifications update when the application loads or refreshes. The hotspot dashboard counts lost reports by campus location and can support discussions about where to place reminders.

## Security and implementation

PDO prepared statements separate SQL from user values. The interface escapes text before inserting it into HTML. POST requests require a session CSRF token. Session cookies are HttpOnly and SameSite Strict, and use Secure when the application is served through HTTPS. Login regenerates the session identifier. Passwords use PHP password_hash and password_verify.

The server assigns every new account the student role. It checks security access before custody, review or collection actions and checks ownership before closing a lost report or removing a watchlist. Collection codes use cryptographically random bytes. Successful collection clears the code, and the server rejects repeated collection attempts.

An example from the implementation is `$q = $db->prepare($sql); $q->execute($args);`. The statement receives values separately from SQL structure. The review workflow begins a transaction, locks the report, verifies its current state and updates the claim and report before committing.

## Testing and validation

Nine API test groups passed using isolated PDO SQLite databases and a command-line request harness. They cover registration privilege restrictions, authentication and CSRF checks, public-field privacy, duplicate and self-claims, custody and conflicting claims, single-use collection codes, watchlist ownership, ID privacy and password-verified login. PHP and JavaScript syntax checks also passed.

Network restrictions prevented local HTTP and browser testing. MySQL execution was not verified in this environment. A classroom readiness check must therefore run the supplied setup scripts on MySQL and complete the walkthrough through the actual browser interface.

## Deployment and demonstration

Create the campus_found database in XAMPP, copy the example configuration, run the schema setup script, and create a security account through the supplied CLI. Serve only the public directory. Register two student accounts to demonstrate a finder and owner. The README contains exact commands.

The demonstration should show a calculator report, a suggested match, a private ownership claim, security custody, approval and collection. It should also show a rejected incorrect code and rejection of a reused collection code. The owner can close their separate lost report after collection.

## Limitations and next steps

An institutional rollout requires identity verification, an agreed security collection procedure, account recovery, retention rules and monitored hosting. The current project provides in-app watchlists and category illustrations. Photo uploads, email/SMS delivery and automatic identity verification are outside this implementation.

The Sites publication and GitHub upload were blocked in the build environment. The downloadable project and local browser demonstration are the available outputs. Complete MySQL and browser acceptance testing before submitting the application as a live demonstration.

## Source material

The project follows the user's End of Semester Project assignment guide and the Campus Found features agreed in this conversation. Implementation details are documented from the generated source. All demo people and reports are fictional. No survey findings or external impact statistics are asserted.
