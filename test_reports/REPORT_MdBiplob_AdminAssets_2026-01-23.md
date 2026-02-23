# 🧪 Test Report

**Developer:** Md.-Biplob-Hossain
**Date:** 2026-01-23
**Feature/Module:** Admin Module - Academic Assets (Course Search)
**Related Commit ID:** Head (Local)

---

## 📄 Files Modified
- `modules/admin/courses.php`

## 🛠️ Verification Steps performed
1.  **Course Search:**
    - Logged in as Admin.
    - Went to `Admin > Inventory > Course Registry`.
    - Searched for "CS101" (Course Code).
    - Searched for "Algorithms" (Course Name partial match).
    - Confirmed list filtered correctly in both cases.
2.  **Empty State:**
    - Searched for "InvalidCourseCode".
    - Verified "No academic assets detected" message appeared.

## ✅ Test Results
| Test Case | Status | Notes |
| :--- | :--- | :--- |
| Search by Course Code | [Pass] | Exact and partial match working. |
| Search by Course Name | [Pass] | Case-insensitive search confirmed. |
| Header Layout | [Pass] | Search bar aligns correctly with "Initialize Asset" button. |

## 📸 Evidence (Optional)
*(Verified locally)*

---

**Team Lead Review:** [ ] Pending
