# Campus Found presentation content

Prepared for the selected Simple Dark Mode PowerPoint template. Template rendering and visual verification remain pending.

## 1 Campus Found

Smart lost-and-found and secure item recovery for the UPSA community.

Student name(s) and index number(s) to be supplied.

## 2 The problem we address

Lost and found reports can be scattered across communication channels. Students need a searchable place to find relevant reports. Security needs a reliable record of receipt and collection. This is a project hypothesis awaiting campus user research.

## 3 Who uses the system

Guests browse the board. Students report and claim items. Security verifies evidence and records handovers. Administrators provision authorised security accounts locally.

## 4 A complete recovery workflow

A finder reports a calculator and keeps a star-shaped scratch private. The owner reports the loss and receives a possible match. Security checks the owner's description, confirms custody and approves collection.

## 5 Matching students can understand

Candidates share the same category. Matching colour and location improve ranking. Dates within seven days add another reason. A suggestion requires human verification.

## 6 Private details support verification

The public board excludes ownership evidence. ID reports use neutral labels. Exact ID searches compare normalised number hashes. Private search limits reduce repeated guesses.

## 7 A recorded handover

Security must confirm physical receipt before approval. Approval reserves the item for one claimant. A random collection code supports handover. Successful collection retires the code and records the return.

## 8 Technical architecture

HTML and CSS present the interface. JavaScript Fetch sends JSON to PHP. PHP checks sessions, validates inputs and executes prepared PDO queries. MySQL stores relational records. The separate browser demo uses fictional data held in memory.

## 9 Relational database

Users link to reports and claims. Locations link to reports. Claims connect people to found reports. Events record transitions. Watches store student searches. A unique user/report claim constraint prevents duplicate submissions.

## 10 Code and security

Prepared statements receive SQL and values separately. Passwords use password_hash and password_verify. POST actions require CSRF tokens. Server-side role checks protect the security desk. MySQL transactions lock reports during approval and collection.

## 11 Validation and limits

Nine isolated PHP workflow test groups passed with PDO SQLite and a CLI request harness. JavaScript and PHP syntax checks passed. HTTP/browser and MySQL execution remain unverified because local network access was blocked. Sites deployment and GitHub upload are incomplete.

## 12 Live demonstration and intended benefit

Run the PHP app through XAMPP. Demonstrate reporting, matching, claim review and collection with two students and one security officer. The intended benefits are easier report discovery and traceable returns. Campus adoption and impact require user testing.
