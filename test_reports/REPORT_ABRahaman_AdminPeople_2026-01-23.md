# 🧪 Test Report

**Developer:** A.-B.-RAHAMAN
**Date:** 2026-01-23
**Feature/Module:** Admin Module - People (Students & Teachers Search/Sort)
**Related Commit ID:** Head (Local)

---

## 📄 Files Modified
- `modules/admin/students.php`
- `modules/admin/teachers.php`

## 🛠️ Verification Steps performed
1.  **Student Search:**
    - Navigate to `Admin > Registry > Student Population`.
    - Typed "Yeatasim" in search bar -> Verified list filtered correctly.
    - Typed "ID-123" -> Verified filtering by ID.
2.  **Student Sorting:**
    - Used dropdown to select "Name (A-Z)". Verified alphabetical order.
    - Selected "Batch (Newest)". Verified batch order.
3.  **Student Profile Link:**
    - Clicked on student name "Nayim Sheikh".
    - Verified redirection to `student-performance.php?student_id=...`.
4.  **Teacher Search:**
    - Navigate to `Admin > Faculty`.
    - Searched for "Professor X".
    - Verified list updated dynamically.

## ✅ Test Results
| Test Case | Status | Notes |
| :--- | :--- | :--- |
| Student Search (Name/ID/Email) | [Pass] | Works for partial matches too. |
| Student Sorting (All Options) | [Pass] | Dropdown auto-submits on change. |
| Link to Performance Profile | [Pass] | Correctly passes internal ID. |
| Teacher Search | [Pass] | Consistent behavior with student search. |

## 📸 Evidence (Optional)
*(Verified locally, features working as intended)*

---

**Team Lead Review:** [ ] Pending
