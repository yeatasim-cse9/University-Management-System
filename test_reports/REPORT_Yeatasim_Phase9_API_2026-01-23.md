# 🧪 Test Report

**Developer:** Yeatasim
**Date:** 2026-01-23
**Feature/Module:** Phase 9 - API & Scripts (Notification API)
**Related Commit ID:** Head (Local)

---

## 📄 Files Modified
- `api/mark_read.php`

## 🛠️ Verification Steps performed
1.  **Tested `mark_all` Action:**
    - Sent POST request to `/api/mark_read.php` with body `{"action": "mark_all"}`.
    - Verified JSON response `{"success": true}`.
2.  **Tested Single Read Action:**
    - Sent POST request with body `{"type": "notice", "id": 1}`.
    - Verified JSON response `{"success": true}`.
3.  **Tested Delete Action:**
    - Sent POST request with body `{"action": "delete", "type": "warning", "id": 5}`.
    - Verified success response.
4.  **Security Check:**
    - Attempted access without login session -> Received 401 Unauthorized.

## ✅ Test Results
| Test Case | Status | Notes |
| :--- | :--- | :--- |
| Mark All Read | [Pass] | |
| Mark Single Read | [Pass] | Checks type and ID correctly. |
| Delete Notification | [Pass] | |
| Authentication Guard | [Pass] | Returns 401 on missing session. |

## 📸 Evidence (Optional)
*(Verified via Postman and Browser Console)*

---

**Team Lead Review:** [x] Self-Reviewed
