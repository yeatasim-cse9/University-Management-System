# Team Academix - Migration & Collaboration Strategy

**Team Lead:** Yeatasim

## 👥 The Team
1.  **Yeatasim** (Lead): Core Architecture, Config, Database, Auth.
2.  **A.-B.-RAHAMAN**: Admin Module (People & Scheduling).
3.  **Md.-Biplob-Hossain**: Admin Module (Academic Assets & Exams & Scheduling).
4.  **NAYIM-SHEIKH**: Teacher Module & Scheduling.
5.  **RASEL-HOSSEN**: Student Module.
6.  **SURIA-HOSSAIN-BONNA**: Super Admin Module & Frontend Assets.

---

## 🚀 Workflow Rules (Strictly Follow)

1.  **One Branch Per Feature**: Do not push directly to `main` (except initial setup).
    *   Format: `feature/developer-name/component-name`
    *   Example: `feature/nayim/teacher-dashboard`
2.  **Granular Commits**: Do NOT commit 10 files at once. Commit 1 file at a time or 1 logical change.
    *   *Bad:* "Added all student files"
    *   *Good:* "Create student dashboard layout" -> "Implement student routine query" -> "Fix sidebar link"
3.  **Commit Message Format**:
    *   `[Module] Action - Detail`
    *   Example: `[Teacher] Feat - Add attendance marking logic`

---

## 📋 The Master Plan

When you are ready to code/push, find your name in this list and follow the instructions.

### Phase 1: Project Initialization (Assigned to: **Yeatasim**)
| ID | File(s) / Action | Commit Message |
| :--- | :--- | :--- |
| 1 | `.gitignore` (Create) | `[Setup] Chore - Add gitignore for uploads/config` |
| 2 | `README.md` (Create basics) | `[Setup] Docs - Initialize project documentation` |
| 3 | `assets/css/` (Folder structure) | `[Setup] Style - Create asset directory structure` |
| 4 | `assets/js/` (Folder structure) | `[Setup] Script - Initialize JS directory` |
| 5 | `uploads/` (Folder structure) | `[Setup] Storage - Create uploads directory with gitkeep` |

### Phase 2: Core Architecture (Assigned to: **Yeatasim**)
| ID | File(s) / Action | Commit Message |
| :--- | :--- | :--- |
| 6 | `config/settings.php` | `[Config] Feat - Define base URL and environment constants` |
| 7 | `config/database.php` | `[Config] Feat - Implement DB connection with error handling` |
| 8 | `includes/functions.php` (Basics) | `[Core] Feat - Add global helper functions` |
| 9 | `includes/session.php` | `[Core] Feat - Implement session management logic` |
| 10 | `includes/auth.php` | `[Core] Feat - Add role-based authentication middleware` |
| 11 | `index.php` (Login Page) | `[Auth] UI - Create login page layout` |
| 12 | `modules/auth/login.php` | `[Auth] Logic - Implement user login verification` |
| 13 | `includes/layouts/dashboard.php` | `[UI] Feat - Create master dashboard layout wrapper` |
| 14 | `includes/sidebar_menu.php` | `[UI] Feat - Implement dynamic role-based sidebar` |
| 15 | `includes/footer-nav.php` | `[UI] Feat - Add global footer component` |
| 16 | `functions.php` (Update) | `[Core] Refactor - Optimize sanitize_input function` |
| 17 | `database/full_setup_2026.sql` | `[DB] Schema - Add initial database schema` |
| 18 | `includes/components/PerformanceMetrics.php` | `[Core] Feat - Add PerformanceMetrics class` |

### Phase 3: Global Assets (Assigned to: **SURIA-HOSSAIN-BONNA**)
| ID | File(s) / Action | Commit Message |
| :--- | :--- | :--- |
| 19 | `assets/css/custom.css` (Base) | `[UI] Style - Define root variables and reset` |
| 20 | `assets/css/custom.css` (Components) | `[UI] Style - Add card and button classes` |
| 21 | `assets/css/custom.css` (Utils) | `[UI] Style - Add utility classes for layout` |
| 22 | `assets/js/main.js` | `[UI] Script - Add global toggle interactions` |
| 23 | `assets/js/global_modal.js` | `[UI] Script - Implement reusable modal logic` |
| 24 | `assets/uploads/` (Cleanup) | `[Assets] Chore - Clean up placeholder images` |

### Phase 4: Super Admin Module (Assigned to: **SURIA-HOSSAIN-BONNA**)
| ID | File(s) / Action | Commit Message |
| :--- | :--- | :--- |
| 25 | `modules/super_admin/dashboard.php` | `[SuperAdmin] UI - Create main dashboard view` |
| 26 | `modules/super_admin/_sidebar.php` | `[SuperAdmin] Nav - Define module-specific sidebar` |
| 27 | `modules/super_admin/users.php` (UI) | `[SuperAdmin] UI - Create user management table` |
| 28 | `modules/super_admin/users.php` (Logic) | `[SuperAdmin] Feat - Implement user CRUD operations` |
| 29 | `modules/super_admin/departments.php` | `[SuperAdmin] Feat - Add department management` |
| 30 | `modules/super_admin/courses.php` | `[SuperAdmin] Feat - Add global course registry` |
| 31 | `modules/super_admin/academic-years.php` | `[SuperAdmin] Feat - Manage academic years` |
| 32 | `modules/super_admin/settings.php` (Form) | `[SuperAdmin] UI - Create system settings form` |
| 33 | `modules/super_admin/settings.php` (Save) | `[SuperAdmin] Logic - Implement settings update` |
| 34 | `modules/super_admin/reports.php` | `[SuperAdmin] Feat - Add system-wide reporting` |
| 35 | `modules/super_admin/notices.php` | `[SuperAdmin] Feat - Implement global notice board` |
| 36 | `modules/super_admin/notifications.php` | `[SuperAdmin] Feat - Add notification center` |
| 37 | `modules/super_admin/events.php` | `[SuperAdmin] Feat - Manage academic calendar events` |

### Phase 5: Admin Module - People & Schedule (Assigned to: **A.-B.-RAHAMAN**)
| ID | File(s) / Action | Commit Message |
| :--- | :--- | :--- |
| 38 | `modules/admin/dashboard.php` | `[Admin] UI - Create department dashboard` |
| 39 | `modules/admin/_sidebar.php` | `[Admin] Nav - Define admin sidebar` |
| 40 | `modules/admin/students.php` (List) | `[Admin] UI - Create student roster view` |
| 41 | `modules/admin/students.php` (Create) | `[Admin] Logic - Implement student enrollment` |
| 42 | `modules/admin/students.php` (Search) | `[Admin] Feat - Add student search functionality` |
| 43 | `modules/admin/students.php` (Sort) | `[Admin] Feat - Add list sorting options` |
| 44 | `modules/admin/teachers.php` (List) | `[Admin] UI - Create faculty directory` |
| 45 | `modules/admin/teachers.php` (Logic) | `[Admin] Feat - Implement teacher onboarding` |
| 46 | `modules/admin/teachers.php` (Search) | `[Admin] Feat - Add faculty search filter` |
| 47 | `modules/admin/class-schedule.php` (View) | `[Admin] UI - Create routine management grid` |
| 48 | `modules/admin/class-schedule.php` (Add) | `[Admin] Logic - Add class scheduling logic` |
| 49 | `modules/admin/class-schedule.php` (Conflict) | `[Admin] Logic - Implement room conflict detection` |
| 50 | `modules/admin/teacher-assignments.php` | `[Admin] Feat - Manage teacher course loads` |
| 51 | `modules/admin/notifications.php` | `[Admin] Feat - Departmental notifications` |

### Phase 6: Admin Module - Assets & Exams (Assigned to: **Md.-Biplob-Hossain**)
| ID | File(s) / Action | Commit Message |
| :--- | :--- | :--- |
| 52 | `modules/admin/courses.php` (List) | `[Admin] UI - Display department course list` |
| 53 | `modules/admin/courses.php` (CRUD) | `[Admin] Logic - Manage course details (Add/Edit)` |
| 54 | `modules/admin/courses.php` (Search) | `[Admin] Feat - Add course search functionality` |
| 55 | `modules/admin/course-offerings.php` (List) | `[Admin] UI - View active semester offerings` |
| 56 | `modules/admin/course-offerings.php` (Logic) | `[Admin] Logic - Manage course deployment` |
| 57 | `modules/admin/course-offerings.php` (Search) | `[Admin] Feat - Add offering search` |
| 58 | `modules/admin/exam-eligibility.php` | `[Admin] Feat - Calculate exam eligibility` |
| 59 | `modules/admin/marks-verification.php` | `[Admin] Feat - Add marks verification interface` |
| 60 | `modules/admin/course_progress.php` | `[Admin] Feat - Track syllabus completion` |
| 61 | `modules/admin/student-performance.php` (View) | `[Admin] UI - Create student performance profile` |
| 62 | `modules/admin/student-performance.php` (Chart) | `[Admin] UI - Integrate chart.js for analytics` |
| 63 | `modules/admin/notices.php` | `[Admin] Feat - Departmental notice board` |

### Phase 7: Teacher Module (Assigned to: **NAYIM-SHEIKH**)
| ID | File(s) / Action | Commit Message |
| :--- | :--- | :--- |
| 64 | `modules/teacher/dashboard.php` | `[Teacher] UI - Create faculty dashboard` |
| 65 | `modules/teacher/_sidebar.php` | `[Teacher] Nav - Set up teacher navigation` |
| 66 | `modules/teacher/my-courses.php` (List) | `[Teacher] UI - View assigned courses` |
| 67 | `modules/teacher/my-courses.php` (Roster) | `[Teacher] Feat - View course student roster` |
| 68 | `modules/teacher/my-courses.php` (Search) | `[Teacher] Feat - Add student search in roster` |
| 69 | `modules/teacher/attendance.php` | `[Teacher] UI - Create attendance sheet` |
| 70 | `modules/teacher/attendance.php` (Logic) | `[Teacher] Logic - Save daily attendance` |
| 71 | `modules/teacher/assessment_types.php` | `[Teacher] Logic - Define assessment criteria` |
| 72 | `modules/teacher/marks-entry.php` (UI) | `[Teacher] UI - Create marks entry grid` |
| 73 | `modules/teacher/marks-entry.php` (Logic) | `[Teacher] Logic - Save student marks` |
| 74 | `modules/teacher/assignments.php` | `[Teacher] Feat - Manage assignment uploads` |
| 75 | `modules/teacher/course-materials.php` | `[Teacher] Feat - Share course resources` |
| 76 | `modules/teacher/routine.php` | `[Teacher] Feat - View personal class routine` |
| 77 | `modules/teacher/notices.php` | `[Teacher] Feat - View department notices` |
| 78 | `modules/teacher/notifications.php` | `[Teacher] Feat - Assignments and alerts` |
| 79 | `modules/teacher/student-performance.php` | `[Teacher] Feat - View individual student stats` |
| 80 | `modules/teacher/syllabus.php` | `[Teacher] Feat - Track course syllabus progress` |
| 81 | `api/teacher/schedule.php` | `[API] Feat - Teacher schedule management endpoint` |

### Phase 8: Student Module (Assigned to: **RASEL-HOSSEN**)
| ID | File(s) / Action | Commit Message |
| :--- | :--- | :--- |
| 81 | `modules/student/dashboard.php` | `[Student] UI - Create student portal dashboard` |
| 82 | `modules/student/_sidebar.php` | `[Student] Nav - Set up student navigation` |
| 83 | `modules/student/my-courses.php` | `[Student] Feat - View enrolled courses` |
| 84 | `modules/student/routine.php` | `[Student] Feat - View weekly class schedule` |
| 85 | `modules/student/attendance.php` | `[Student] Feat - Check attendance records` |
| 86 | `modules/student/results.php` | `[Student] Feat - View semester results` |
| 87 | `modules/student/performance.php` | `[Student] Feat - View academic analytics` |
| 88 | `modules/student/assignments.php` | `[Student] Feat - Submit course assignments` |
| 89 | `modules/student/syllabus.php` | `[Student] Feat - View course outlines` |
| 90 | `modules/student/notices.php` | `[Student] Feat - Browse academic notices` |
| 91 | `modules/student/notifications.php` | `[Student] Feat - Check alerts and updates` |
| 92 | `api/student/schedule.php` | `[API] Feat - Student schedule endpoint` |

### Phase 9: API & Scripts (Assigned to: **Yeatasim**)
| ID | File(s) / Action | Commit Message |
| :--- | :--- | :--- |
| 94 | `api/mark_read.php` | `[API] Feat - Notification read status endpoint` |
| 95 | `scripts/import_students.php` | `[Script] Tool - Add bulk student import utility` |
| 96 | `scripts/update_grading_scheme.php` | `[Script] Tool - Grading scheme update utility` |
| 97 | `database/migrations/001_initial.sql` | `[DB] Migration - Add initial database schema` |
| 98 | `database/migrations/002_reviews.sql` | `[DB] Migration - Add performance reviews` |
| 99 | `database/migrations/003_interaction.sql` | `[DB] Migration - Add notice interactions` |
| 100 | `assets/uploads/profiles/gitkeep` | `[Assets] Chore - Preserve upload directory` |


