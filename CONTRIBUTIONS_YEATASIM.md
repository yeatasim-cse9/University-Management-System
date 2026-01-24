# Professional Contribution Report - Yeatasim
**Role:** Team Lead & Lead Backend Architect  
**Project:** Academix - University Management System

---

## 🚀 Executive Summary
As the Team Lead and Backend Architect, I engineered the foundational **Modular MVC Architecture** and implemented the core security, authentication, and complex scheduling logic. My work ensured a scalable codebase that supports distinct modules for Students, Teachers, and Admins while maintaining rigorous data integrity and security standards.

---

## 🛠️ 1. Core Architecture & System Design
I designed the system to be modular and scalable, moving away from flat PHP structures to a robust organized framework.
*   **Singleton Pattern Database Wrapper**: Implemented a resilient database connection in `config/database.php` using the Singleton pattern to prevent multiple active connections. added `PDO` error handling and enforced **UTF-8mb4** charset for full linguistic support.
    *   *Code Highlight*: `get_db_connection()` with static caching.
*   **Global Configuration Logic**: Centralized system constants (`BASE_URL`, `DB_HOST`, timezone settings) in `config/settings.php`, allowing for instant environment switching (Local vs Production).
*   **Dynamic Timezone Handling**: Enforced `SET time_zone = '+06:00'` at the session level to ensure all academic timestamps align with local time.

## � 2. Advanced Security & Authentication
I built the entire security layer from scratch, moving beyond simple login checks to enterprise-grade protection.
*   **Role-Wise Access Middleware**:
    *   Developed `require_role($roles)` middleware in `includes/auth.php` acting as a gatekeeper for every protected route.
    *   Implemented strict session hijacking prevention by regenerating session IDs on login.
*   **Brute Force Protection Strategy**:
    *   Engineered `check_login_attempts()` to track failed logins by IP/Username.
    *   Implemented an automated **Account Lockout Mechanism** that locks accounts for 15 minutes after 5 failed attempts, updating the `locked_until` timestamp in the database.
*   **CSRF & XSS Architecture**:
    *   Built `generate_csrf_token()` and `verify_csrf_token()` to immunize forms against Cross-Site Request Forgery.
    *   Created a global `sanitize_input()` wrapper to recursively clean all incoming request data, neutralizing XSS vectors.

## 📅 3. Complex Algorithmic Logic (The "Brain" of the App)
My most significant code contribution was the **Conflict Detection Engine** for the scheduling system.
*   **3-Way Conflict Detection Algorithm** (`check_schedule_conflicts` function):
    *   This is not a simple "is free?" check. I wrote a complex algorithm that simultaneously checks three dimensions of conflicts:
        1.  **Room Conflict**: Is the physical room occupied?
        2.  **Teacher Conflict**: Is the specific faculty member teaching elsewhere?
        3.  **Batch Conflict**: Is this specific student batch (Semester + Section) already in another class?
    *   *Technical Detail*: Utilized complex SQL `JOIN`s across `class_schedule`, `course_offerings`, and `teacher_courses` tables, while also cross-referencing the `class_reschedules` table to account for ad-hoc overrides.
*   **Reschedule Logic**: I wrote the logic that allows specific classes to be moved without breaking the rest of the schedule, automatically invalidating the old slot and validating availability for the new slot in real-time.

## 📊 4. Database Optimization & Data Integrity
I was responsible for the schema design and query optimization.
*   **Complex Union Queries**:
    *   In `get_user_notifications()`, I implemented an efficient `UNION ALL` query to merge strictly targeted "Departmental Notices" with "Personal Alerts", sorting them by time and limiting results for O(1) dashboard performance.
*   **Audit Logging**:
    *   Created `create_audit_log()` to track critical system changes (like grade updates), storing JSON snapshots of `old_values` vs `new_values` for full accountability.
*   **Transactional Integrity**: Used MySQL transactions (Commit/Rollback) for multi-step processes like "Course Enrollment" to ensure no partial data states exist.

## 🌟 5. Leadership & Team Workflow
*   **Git Workflow Enforcement**: Established the "Feature Branch" workflow, reviewing 100% of Pull Requests to ensure no broken code hit the `main` branch.
*   **Standardization**: Enforced code standards (e.g., naming conventions, directory structure) that allowed 5 other developers to work simultaneously without file conflicts.

---
**Technical Keywords**: Singleton Pattern, RBAC Middleware, CSRF Tokens, SQL Injection Prevention, Complex JOINs, JSON Data Storage, Recursive Sanitization.
