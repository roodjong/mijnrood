# Export contract: `app:export-everything`

This document specifies the exact output format of the `\App\Command\ExportEverything`
console command (`php bin/console app:export-everything <output-directory>`), so that an
independent import command can be written in another project without access to this codebase.

Source of truth: `src/Command/ExportEverything.php` plus the Doctrine entities in
`src/Entity/`. Column order below was taken from a real export.

## 1. General shape

- The command dumps **every table in the MySQL database** to one CSV file per table,
  named `<table_name>.csv`, directly in the given output directory (no subdirectories).
- The `sessions` table and any table whose name ends in `migration_versions` (in
  production: `admin_migration_versions`) are skipped.
- The join table `division_member` is force-included even though it has no entity of its own.
- The export requires the `EXPORT_KEY` env var (Symfony parameter `export_key`) to be set;
  the same key is needed later to download document files (see §4).

## 2. CSV serialization rules

Rows are written with PHP's `fputcsv()` with the escape parameter disabled, producing
strict RFC 4180 output:

| Aspect | Value |
|---|---|
| Encoding | UTF-8 (whatever MySQL returns; the app uses utf8mb4) |
| Field delimiter | `,` (comma) |
| Enclosure | `"` — fields are quoted only when they contain delimiter, quote, `\n` or `\r`; embedded quotes are doubled (`""`); backslashes are literal (no escape character) |
| Record separator | `\n` (LF, no CRLF) |
| Header row | Always present, first line, even for empty tables (empty table ⇒ header-only file) |
| BOM | None |

Value formats (MySQL via Doctrine DBAL):

- **NULL** is written as the sentinel **`\N`** (unquoted, mysqldump convention); an empty
  field is a real **empty string**. A stored value that is itself the literal string `\N`
  would be ambiguous, so the export refuses to write it and fails instead — importers may
  unconditionally map `\N` to NULL.
- **datetime** columns: `YYYY-MM-DD HH:MM:SS` (no timezone; server-local time, in practice Europe/Amsterdam).
- **date** columns: `YYYY-MM-DD`.
- **boolean** columns: `0` / `1`.
- **json** columns: raw JSON string, e.g. `["ROLE_ADMIN"]`.
- Numeric columns: plain decimal integers.

**Row order is not guaranteed.** Tables are read with `SELECT * ... LIMIT 10000 OFFSET n`
without an `ORDER BY`, in batches of 10 000 appended to the same file. Do not rely on
ordering, and note that an export taken against a database receiving concurrent writes
could in theory duplicate or miss rows across batch boundaries.

## 3. Files and columns

Column order below is the exact header order in the CSV. `PK` = primary key
(auto-increment integer unless noted), `FK→x` = foreign key to file/table `x`, column `id`.
"null" means the column may be empty (NULL).

### admin_member.csv — current members (the core table)

| Column | Type / meaning |
|---|---|
| id | PK |
| division_id | FK→admin_division, null — the division ("groep") the member belongs to |
| first_name | string(50) |
| last_name | string(100) |
| email | string(100) |
| phone | string(20) |
| iban | string(34), null |
| address | string(200), null |
| city | string(100) |
| post_code | string(14) |
| registration_time | **date**, null (despite the name, no time part) |
| mollie_subscription_id | string, null — Mollie subscription (`sub_...`) |
| contribution_period | int enum: 0=monthly, 1=quarterly, 2=annually |
| contribution_per_period_in_cents | int |
| roles | JSON array of Symfony roles, e.g. `[]` or `["ROLE_ADMIN"]`. Known roles: `ROLE_ADMIN`, `ROLE_DIVISION_CONTACT` (`ROLE_USER` is implicit, never stored) |
| password_hash | string, null — Symfony "auto" hasher output in modular crypt format (bcrypt `$2y$...` or argon2id `$argon2id$...`) |
| new_password_token_generated_time | datetime, null (password-reset bookkeeping; safe to drop on import) |
| new_password_token | string(100), null (idem) |
| country | string(2), ISO 3166-1 alpha-2 |
| date_of_birth | date, null |
| accept_use_personal_information | boolean |
| mollie_customer_id | string, null — Mollie customer (`cst_...`) |
| create_subscription_after_payment | boolean |
| current_membership_status_id | FK→admin_membershipstatus, null |
| comments | text, null |
| middle_name | string(50), NOT NULL, default `''` ("tussenvoegsel") |

### admin_division.csv — local divisions/groups

`id` (PK), `name` string(50), `phone` null, `city` null, `address` null, `post_code` null,
`facebook` null, `instagram` null, `twitter` null (all free-form strings),
`email_id` (FK→admin_email, null — the division's contact email account),
`can_be_selected_on_application` boolean (default 1).

### division_member.csv — division *contacts* join table

| Column | Type / meaning |
|---|---|
| division_id | FK→admin_division |
| member_id | FK→admin_member |

⚠️ Semantics: this is **not** membership of a division (that is `admin_member.division_id`).
It links members who act as **contact person / manager** of a division
(`Division::$contacts` ↔ `Member::$managed_divisions`). Composite unique key on the pair.

### admin_contribution_payment.csv — contribution payment history

| Column | Type / meaning |
|---|---|
| id | PK |
| member_id | FK→admin_member, null |
| amount_in_cents | int |
| payment_time | datetime |
| status | int enum: 0=pending, 1=paid, 2=failed, 3=refunded |
| mollie_payment_id | string, null (`tr_...`) |
| period_year | smallint (e.g. 2026) |
| period_month_start | smallint 1–12 |
| period_month_end | smallint 1–12 — the payment covers period_month_start..period_month_end of period_year |

### admin_membership_application.csv — pending full-membership applications

`id` (PK), `first_name` string(50), `last_name` string(100), `email` string(100),
`phone` string(20), `iban` string(34) null, `address` string(200), `city` string(100),
`post_code` string(14), `country` string(2), `date_of_birth` date null,
`registration_time` date null, `contribution_period` int enum (same 0/1/2 as member),
`contribution_per_period_in_cents` int, `preferred_division_id` (FK→admin_division, null),
`mollie_customer_id` string null, `paid` boolean, `middle_name` string(50) default `''`,
`has_sent_initial_email` boolean.

### admin_support_member.csv — support ("steun") members

Same personal-data columns as membership application, then:
`mollie_customer_id` null, `mollie_subscription_id` null,
`contribution_period` int enum (0/1/2), `contribution_per_period_in_cents` int,
`original_id` int — the former `admin_member.id` if this record was converted from a full
member (0 = never was one), `original_registration_time` date null — the original
registration date as full member.

Header: `id,first_name,last_name,email,phone,iban,address,city,post_code,country,date_of_birth,registration_time,mollie_customer_id,mollie_subscription_id,contribution_period,contribution_per_period_in_cents,original_id,original_registration_time`

### admin_support_membership_application.csv — pending support-member applications

Identical to admin_support_member.csv minus `original_id` and `original_registration_time`.

### admin_member_revision.csv — audit trail of personal-detail changes

| Column | Type / meaning |
|---|---|
| id | PK |
| member_id | FK→admin_member, NOT NULL |
| own | boolean — 1 if the member edited their own details, 0 if an admin did |
| revision_time | datetime |
| first_name … date_of_birth | snapshot of the personal-data columns (same types as admin_member) |
| current_membership_status_id | FK→admin_membershipstatus, null |

Header: `id,member_id,own,revision_time,first_name,last_name,email,phone,iban,address,city,post_code,country,date_of_birth,current_membership_status_id`

### admin_membershipstatus.csv — membership status lookup

`id` (PK), `name` string(150), `allowed_access` boolean (whether members with this status may log in).

### admin_document.csv — uploaded documents (metadata only; see §4 for file contents)

| Column | Type / meaning |
|---|---|
| id | PK |
| folder_id | FK→admin_document_folder, null (null = root folder) |
| member_uploaded_id | FK→admin_member, null |
| name | string — display filename |
| size_in_bytes | int |
| upload_file_name | string — storage filename on the server's disk |
| date_uploaded | datetime |
| api_download_url | **synthetic column, not in the database** — absolute URL to fetch the file bytes (see §4). `\N` if the row somehow has no id. |

### admin_document_folder.csv — document folder tree

`id` (PK), `parent_id` (FK→admin_document_folder, null — self-referencing tree, null = root),
`member_created_id` (FK→admin_member, null), `name` string.

### admin_email.csv / admin_email_domain.csv — managed email accounts

admin_email: `id` (PK), `user` string(100) (local part), `domain_id` (FK→admin_email_domain,
NOT NULL), `manager_id` (FK→admin_member, null). The address is `user@domain`.
Unique on (user, domain).
admin_email_domain: `id` (PK), `domain` string(100), unique.

### admin_event.csv — events

`id` (PK, unsigned), `division_id` (FK→admin_division, null — null means national event),
`name` string(150), `description` string(2000), `time_start` datetime, `time_end` datetime.

## 4. Document file contents

The export contains only document **metadata**. The actual bytes must be fetched per row
from `api_download_url`, which looks like:

```
https://<host>/api/documenten/download/<document id>
```

- Method: `GET`
- Auth: HTTP header `Authorization: <EXPORT_KEY>` — the **raw key value**, no `Bearer `
  or other scheme prefix. Any other value returns `401 Unauthorized`.
- Response: raw file bytes, `Content-Type: application/octet-stream`,
  `Content-Disposition: attachment; filename="<urlencoded display name>"`.
- `404` if the document row no longer exists on the server.

## 5. Suggested import order (FK dependencies)

1. `admin_membershipstatus`, `admin_email_domain`
2. `admin_email` (needs domain; `manager_id` needs member — import with manager deferred, or import emails after members and divisions after emails)
3. `admin_division` (needs email via `email_id`)
4. `admin_member` (needs division, membershipstatus)
5. `division_member`, `admin_member_revision`, `admin_contribution_payment` (need member/division)
6. `admin_membership_application` (needs division), `admin_support_member`, `admin_support_membership_application`
7. `admin_document_folder` (self-referencing `parent_id` — insert parents before children or defer the FK), `admin_document`
8. `admin_event`

Circular note: `admin_email.manager_id → admin_member` while
`admin_member.division_id → admin_division → admin_email`. Break it by importing emails
without `manager_id` first and patching `manager_id` after members are in.

## 6. Known quirks checklist for importer authors

- NULL is the sentinel `\N`; an empty field is an empty string. Parse with the CSV reader's
  escape/doubling behavior set to RFC 4180 (e.g. Python `csv` defaults) and map `\N` → NULL
  after parsing.
- `registration_time` columns are DATE, not DATETIME, despite the name.
- Row order is arbitrary; don't assume `id` ordering.
- Header-only files are valid (empty tables).
- Only consume exports from runs that exited with code 0.
- `roles` is a JSON-encoded array in a single CSV field (it contains commas and quotes, so the field will be quoted).
- All `id` values are stable database PKs — preserve them (or maintain a mapping) so the FK columns keep working.
