# CampusCare - Campus Maintenance Management System

Stack: HTML, CSS, JavaScript, PHP, MySQL/MariaDB

## Run with XAMPP (fresh install)
1. Start Apache and MySQL in the XAMPP control panel.
2. Delete any old `campus_maintenance` folder inside `C:\xampp\htdocs\`.
3. Copy this entire folder to `C:\xampp\htdocs\campus_maintenance` directly
   (files should sit at `C:\xampp\htdocs\campus_maintenance\admin.php`, NOT
   nested one level deeper).
4. In phpMyAdmin, drop any existing `campus_maintenance` database (this
   version changes the table structure, so a fresh import is required).
5. Open the "SQL" tab (or "Import") and run `database.sql`.
6. Open `http://localhost/campus_maintenance/`.

## Demo login
- Admin: `admin@campus.com` / `admin123`
- Student: `student@campus.com` / `student123`

There is no staff login anymore (see below).

## Staff is no longer a login role (this version)
- The `users` table's `role` column is now only `ENUM('student','admin')`.
  There is no `staff` role, no staff account, and therefore no staff login
  and no staff dashboard page of any kind.
- A brand new, separate `staff` table was added. It holds ONLY a name and a
  specialty (e.g. "Rahul Electrician" / "Electrical") — no email, no
  password, no login capability whatsoever. It comes pre-loaded with 4
  default technicians.
- `requests.assigned_to` now points to this `staff` table instead of the
  `users` table.
- On the Admin page, the **Add Staff** form and the **Remove** button are
  both gone. "Active Staff" is now a plain read-only list of the default
  technicians — nothing there can be added, edited, or removed from the UI.
- Admin can still **assign** an unassigned request to one of the default
  technicians, and the **Staff Workload** table still shows each
  technician's active task count. Both simply read from the new `staff`
  table now.
- Student pages (`new_request.php`, `requests.php`, `dashboard.php`,
  `request_view.php`, `delete_account.php`, `signup.php`) are unchanged in
  behavior — a student still only ever sees their own requests, and can
  still confirm/reopen/rate a resolved request. Admin's own request-control
  panel on `request_view.php` is unchanged too.
- If you want to change the 4 default technicians, do it directly in
  phpMyAdmin's `staff` table (Browse -> Edit/Insert row) since there is no
  admin-page UI for it by design.

## Account rules
- Public Sign Up creates **Student** accounts only.
- Only an Admin account exists besides students — created directly in the
  database (see demo login above), since there is no "add admin" UI either.

## What was fixed earlier and still applies
- Admin can now mark an assigned/in-progress task as **done** directly from
  `admin.php` (a new "Assigned / In-Progress Tasks" panel with a
  "Mark Work Done" button per task), in addition to the existing Assign
  action. This sets the request's status to `Resolved` and records it in the
  status history — the student can then confirm/reopen it as before. Full
  status control (every status option) is still also available from
  `request_view.php`'s Admin Request Control panel.
- `admin.php`'s Assign and Mark Work Done actions redirect after every POST
  (Post/Redirect/Get), so refreshing never resubmits a form or shows stale
  data.
- `config.php` turns on full PHP error display AND `mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT)`,
  so any real database error now throws a visible, readable error instead of
  failing silently with no message at all.
- `logout.php` calls `session_unset()` before `session_destroy()`.
- Empty tables show a friendly "nothing to show yet" row.
- Access-denied / not-found pages include the normal navigation bar.

## Security note
This is a college-project implementation. New student accounts use PHP
password hashing (`password_hash`/`password_verify`). The two demo accounts
use MD5 so the supplied demo data remains compatible — `login.php` accepts
either. For a real production deployment you would want to add CSRF tokens
to every form and move DB credentials out of `config.php` into an
environment variable.
