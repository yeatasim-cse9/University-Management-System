
# Academix - University Management System

A comprehensive University Management System designed to streamline academic and administrative processes for universities. Academix supports multi-role access, robust scheduling, performance analytics, and modular management for students, teachers, admins, and super admins.

---

## Table of Contents

- [Features](#features)
- [Project Structure](#project-structure)
- [Database Schema](#database-schema)
- [Setup Instructions](#setup-instructions)
- [Team & Workflow](#team--workflow)
- [Testing & Reporting](#testing--reporting)
- [License](#license)

---

## Features

- **Student Portal**: Course enrollment, results, attendance, assignments, and class routine.
- **Teacher Portal**: Class scheduling, grading, attendance, resource sharing, and student performance tracking.
- **Admin Dashboard**: Manage departments, courses, students, teachers, scheduling, and notifications.
- **Super Admin**: System-wide settings, user management, academic years, and reporting.
- **API Endpoints**: For schedule management, notifications, and more.
- **Performance Reviews**: Track and review student performance.
- **Access Control**: Role-based authentication and permissions.
- **Audit Logs**: Track system changes and user actions.

---

## Project Structure

```
academix/
│
├── api/                # API endpoints for students, teachers, notifications, etc.
├── assets/             # CSS, JS, uploads (assignments, profiles, materials)
├── config/             # Application and database configuration
├── database/           # SQL schema, migrations, and seed data
├── includes/           # Core PHP includes, components, layouts
├── modules/            # Feature modules for admin, student, teacher, super_admin
├── scripts/            # Utility scripts (import, migration, grading)
├── test_reports/       # Test reports and templates
├── uploads/            # Uploaded files (assignments, materials)
├── index.php           # Entry point (login)
├── README.md           # Project documentation
└── ...                 # Other supporting files
```

---

## Database Schema

- **Users & Profiles**: `users`, `user_profiles`
- **Departments & Admins**: `departments`, `department_admins`
- **Teachers & Students**: `teachers`, `students`
- **Academic Structure**: `academic_years`, `semesters`, `courses`, `course_offerings`
- **Scheduling**: `class_schedule`, `class_reschedules`
- **Attendance & Assessment**: `attendance`, `grading_scheme`, `assessment_components`, `assignments`, `assignment_submissions`, `student_marks`, `student_performance_reviews`
- **Communication**: `notices`, `events`, `documents`, `course_materials`, `notifications`, `notice_interactions`
- **System**: `system_settings`, `audit_logs`, `login_history`, `notification_preferences`

> See `database/full_setup_2026.sql` for full schema and seed data.

---

## Setup Instructions

1. **Clone the repository**
    ```sh
    git clone https://github.com/yeatasim-cse9/University-Management-System.git
    ```
2. **Import the Database**
    - Import `database/full_setup_2026.sql` into your MySQL server.
3. **Configure Application**
    - Edit `config/settings.php` and `config/database.php` for your environment.
4. **Set Up Web Server**
    - Serve the project via Apache/Nginx (DocumentRoot should point to the project root).
5. **File Permissions**
    - Ensure `uploads/` and `logs/` are writable by the web server.
6. **Default Credentials**
    - See seed users in the SQL file (e.g., `superadmin` / `123456`).

---

## Team & Workflow

- **Team Lead:** Yeatasim
- **Contributors:** A.-B.-RAHAMAN, Md.-Biplob-Hossain, NAYIM-SHEIKH, RASEL-HOSSEN, SURIA-HOSSAIN-BONNA

**Workflow Highlights:**
- One branch per feature (`feature/name/component`)
- Granular, logical commits (1 file/change per commit)
- Commit message format: `[Module] Action - Detail`

---

## Testing & Reporting

- Use the template in `TEST_REPORT_TEMPLATE.md` for all test reports.
- Place completed reports in `test_reports/`.
- Each feature/module must be tested and verified before merging.

---

## License

This project is for academic and demonstration purposes. For licensing or commercial use, contact the project maintainers: Yeatasim, A.-B.-RAHAMAN, Md.-Biplob-Hossain, NAYIM-SHEIKH, RASEL-HOSSEN, SURIA-HOSSAIN-BONNA.

---
