<?php
/**
 * Performance Metrics Helper
 * ACADEMIX - Academic Management System
 */

class PerformanceMetrics {
    private $db;
    private $student_id;

    public function __construct($db, $student_id) {
        $this->db = $db;
        $this->student_id = intval($student_id);
    }

    /**
     * Get overall CGPA
     */
    public function getCGPA() {
        // Placeholder logic - in a real system this would calculate from all completed semesters
        // For now, we'll calculate average from current enrollments with marks
        $query = "
            SELECT AVG(sm.marks_obtained / sm.total_marks * 100) as avg_percent
            FROM student_marks sm
            JOIN enrollments e ON sm.enrollment_id = e.id
            WHERE e.student_id = {$this->student_id} AND sm.status = 'verified'
        ";
        $result = $this->db->query($query)->fetch_assoc();
        $avg = $result['avg_percent'] ?? 0;
        
        // Convert to 4.0 scale (approximate)
        if ($avg >= 80) return 4.00;
        if ($avg >= 75) return 3.75;
        if ($avg >= 70) return 3.50;
        if ($avg >= 65) return 3.25;
        if ($avg >= 60) return 3.00;
        if ($avg >= 55) return 2.75;
        if ($avg >= 50) return 2.50;
        if ($avg >= 45) return 2.25;
        if ($avg >= 40) return 2.00;
        return 0.00;
    }

    /**
     * Get Attendance Statistics
     */
    public function getAttendanceStats() {
        $query = "
            SELECT 
                COUNT(*) as total_classes,
                SUM(CASE WHEN a.status IN ('present', 'late') THEN 1 ELSE 0 END) as present_classes
            FROM attendance a
            JOIN enrollments e ON a.enrollment_id = e.id
            WHERE e.student_id = {$this->student_id}
        ";
        $result = $this->db->query($query)->fetch_assoc();
        
        $total = intval($result['total_classes']);
        $present = intval($result['present_classes']);
        $percentage = $total > 0 ? round(($present / $total) * 100, 1) : 0;
        
        return [
            'total' => $total,
            'present' => $present,
            'percentage' => $percentage,
            'absent' => $total - $present
        ];
    }

    /**
     * Get Assignment Statistics
     */
    public function getAssignmentStats() {
        // Count total assignments for enrolled courses
        $query_total = "
            SELECT COUNT(DISTINCT a.id) as count
            FROM assignments a
            JOIN enrollments e ON a.course_offering_id = e.course_offering_id
            WHERE e.student_id = {$this->student_id} AND a.status = 'published'
        ";
        $total = $this->db->query($query_total)->fetch_assoc()['count'];

        // Count submissions
        $query_subs = "
            SELECT 
                COUNT(*) as submitted,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
            FROM assignment_submissions
            WHERE student_id = {$this->student_id}
        ";
        $subs = $this->db->query($query_subs)->fetch_assoc();
        
        return [
            'total' => $total,
            'submitted' => intval($subs['submitted']),
            'late' => intval($subs['late']),
            'missed' => $total - intval($subs['submitted'])
        ];
    }

    /**
     * Get Subject-wise Performance (for Radar Chart)
     */
    public function getSubjectPerformance() {
        $query = "
            SELECT 
                c.course_code,
                AVG(sm.marks_obtained / sm.total_marks * 100) as avg_percent
            FROM student_marks sm
            JOIN enrollments e ON sm.enrollment_id = e.id
            JOIN course_offerings co ON e.course_offering_id = co.id
            JOIN courses c ON co.course_id = c.id
            WHERE e.student_id = {$this->student_id} AND sm.status = 'verified'
            GROUP BY c.id
        ";
        $result = $this->db->query($query);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'subject' => $row['course_code'],
                'score' => round($row['avg_percent'], 1)
            ];
        }
        return $data;
    }

    /**
     * Get Performance Over Time (Line Chart)
     * Groups by 'month' or 'assessment'
     */
    public function getPerformanceTrend() {
        // We'll use assessment components marks
        $query = "
            SELECT 
                ac.component_name,
                AVG(sm.marks_obtained / sm.total_marks * 100) as avg_percent
            FROM student_marks sm
            JOIN enrollments e ON sm.enrollment_id = e.id
            JOIN assessment_components ac ON sm.assessment_component_id = ac.id
            WHERE e.student_id = {$this->student_id} AND sm.status = 'verified'
            GROUP BY ac.component_name
            ORDER BY FIELD(ac.component_name, 'Attendance', 'Assignment', 'Quiz', 'Mid Term', 'Final Exam')
        ";
        $result = $this->db->query($query);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'label' => $row['component_name'],
                'value' => round($row['avg_percent'], 1)
            ];
        }
        return $data;
    }

    /**
     * Get recent reviews/remarks
     */
    public function getReviews() {
        try {
            $query = "
                SELECT spr.*, u.username, up.first_name, up.last_name, u.role
                FROM student_performance_reviews spr
                JOIN users u ON spr.reviewer_id = u.id
                LEFT JOIN user_profiles up ON u.id = up.user_id
                WHERE spr.student_id = {$this->student_id}
                ORDER BY spr.created_at DESC
            ";
            $result = $this->db->query($query);
            if (!$result) return [];
            
            $reviews = [];
            while ($row = $result->fetch_assoc()) {
                $reviews[] = $row;
            }
            return $reviews;
        } catch (Exception $e) {
            return [];
        }
    }
}
