# BZU Online Grading System — Redesigned

## Database Management System

 
### Database schema (3rd Normal Form)

```
admins          — admin accounts (hashed passwords)
teachers        — teacher profiles
sessions        — session years lookup (2021-25, etc.)
subjects        — course catalog
teacher_assignments — links teacher ↔ subject ↔ session
students        — student profiles (linked to session)
marks           — all marks in one table (replaces 4 tables)
```

### DB Techniques Applied

1. **Normalization (3NF)** — no repeating groups, no transitive dependencies
2. **Referential Integrity** — `ON DELETE CASCADE` / `ON UPDATE CASCADE` foreign keys
3. **TRIGGERS** — `trg_marks_total_insert` and `trg_marks_total_update` auto-calculate `total_marks`
4. **Indexes** — on `roll_no`, `session_id`, `subject_id` for fast lookups
5. **UNIQUE constraints** — prevent duplicate assignments and roll numbers per session
6. **INSERT ... ON DUPLICATE KEY UPDATE** — upsert pattern for idempotent operations
7. **Prepared Statements** — all queries use `mysqli_prepare()` / `bind_param()`
8. **Aggregate queries** — admin dashboard uses `COUNT(*)` live stats
9. **Multi-table JOINs** — result page joins 4 tables in a single query
10. **Password hashing** — SHA2-256 via SQL `SHA2()` function

---

## Setup Instructions

### 1. Database
1. Open phpMyAdmin or MySQL CLI
2. Import `app/myproject.sql`
3. That's it — all tables, triggers, and sample data are included

### 2. Web Server (XAMPP/WAMP)
- Place the `app/` folder inside `htdocs/online-grading-system/`
- Access via: `http://localhost/online-grading-system/app/Index.php`

### 3. Default Credentials

**Admin Login:**
- Name: `aqib`
- Email: `aqib@gmail.com`
- Password: `123`
- Admin ID: `1`

**Teacher Login (sample):**
- Name: `Dr.Shahid`
- Subject: `DBMS`
- Course Code: `CPE-101`
- Session: `2023-27`

---

## File Structure

```
app/
├── myproject.sql          ← Import this first
├── Dbconnect.php          ← DB connection + helper functions
├── Index.php              ← Login page (admin + teacher tabs)
├── checkresult.php        ← Student result search form
├── result.php             ← Result display (JOIN query)
├── style.css              ← Main CSS
├── admin/
│   ├── Admin.php          ← Admin dashboard (live stats)
│   ├── assign.php         ← Assign subject to teacher
│   ├── allocated.php      ← Check allocation status
│   ├── update.php         ← Search student record
│   ├── updated.php        ← Edit student marks
│   ├── upteacher.php      ← Search teacher to update
│   ├── updated_teacher.php← Apply teacher update
│   └── admin.css
└── teacher/
    ├── teacher.php        ← Add marks + view class records
    └── teacher.css
```
