<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(0); // Tắt cảnh báo PHP để không làm hỏng JSON

$conn = new mysqli("localhost", "root", "", "parent_portal");
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database offline"]);
    exit;
}
mysqli_set_charset($conn, 'UTF8');

$parent_id = 1; 
$method = $_SERVER['REQUEST_METHOD'];

// ==========================================
// 1. THÊM HỌC SINH MỚI
// ==========================================
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['action']) && $input['action'] === 'add_student') {
        $name = $conn->real_escape_string($input['name']);
        $dob = $conn->real_escape_string($input['dob']);
        $class = $conn->real_escape_string($input['class']);
        $school = $conn->real_escape_string($input['school']);
        
        $name_parts = explode(' ', $name);
        $code = strtolower(substr(end($name_parts), 0, 4)) . rand(10,99);

        try {
            $sql = "INSERT INTO students (parent_id, student_code, full_name, class_name, school, dob) 
                    VALUES ($parent_id, '$code', '$name', '$class', '$school', '$dob')";
            if ($conn->query($sql) === TRUE) {
                echo json_encode(["status" => "success", "message" => "Đã thêm học sinh thành công!"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Lỗi: " . $conn->error]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => "Lỗi CSDL."]);
        }
        exit;
    }
}

// ==========================================
// 2. LẤY DỮ LIỆU ĐỔ RA WEB (100% TỪ DATABASE)
// ==========================================
$data = array();

try {
    $result_students = $conn->query("SELECT * FROM students WHERE parent_id = $parent_id");
    if ($result_students && $result_students->num_rows > 0) {
        while($student = $result_students->fetch_assoc()) {
            $sid = $student['id'];
            $code = $student['student_code'];
            
            // Thiết lập giá trị rỗng/null mặc định để JS biết là chưa có dữ liệu
            $data[$code] = array(
                "id" => $sid,
                "name" => $student['full_name'],
                "class" => $student['class_name'],
                "school" => isset($student['school']) ? $student['school'] : "Đang cập nhật",
                "dob" => isset($student['dob']) ? $student['dob'] : "Đang cập nhật",
                "gpa" => null, 
                "attendance" => null, 
                "monthlyScores" => [], 
                "subjects" => [], 
                "courses" => [], 
                "schedule" => [],
                "finance" => array("paid" => 0, "due" => 0, "history" => [])
            );

            // 1. Biểu đồ điểm tháng
            try {
                $res = $conn->query("SELECT score FROM monthly_scores WHERE student_id = $sid ORDER BY id ASC");
                if($res) { while($row = $res->fetch_assoc()) { $data[$code]["monthlyScores"][] = (float)$row['score']; } }
            } catch(Exception $e) {}

            // 2. Chuyên cần
            try {
                $res = $conn->query("SELECT (SUM(is_present)/COUNT(*))*100 as att FROM attendance WHERE student_id = $sid");
                if($res && $row = $res->fetch_assoc()) {
                    if($row['att'] !== null) $data[$code]["attendance"] = round($row['att']);
                }
            } catch(Exception $e) {}

            // 3. Điểm các môn (tính GPA thật)
            try {
                $res = $conn->query("SELECT * FROM subjects WHERE student_id = $sid");
                $total = 0; $count = 0;
                if($res) {
                    while($row = $res->fetch_assoc()){
                        $data[$code]["subjects"][] = $row;
                        $total += $row['current_score']; $count++;
                    }
                    if($count > 0) $data[$code]["gpa"] = round($total/$count, 1);
                }
            } catch(Exception $e) {}

            // 4. Khóa học
            try {
                $res = $conn->query("SELECT * FROM registered_courses WHERE student_id = $sid");
                if($res) {
                    while($c = $res->fetch_assoc()){
                        $cid = $c['id'];
                        $lessons = [];
                        try {
                            $l_res = $conn->query("SELECT * FROM course_contents WHERE course_id = $cid");
                            if($l_res) { while($l = $l_res->fetch_assoc()) $lessons[] = $l; }
                        } catch(Exception $e) {}
                        $c['lessons'] = $lessons;
                        $data[$code]["courses"][] = $c;
                    }
                }
            } catch(Exception $e) {}

            // 5. Lịch học
            try {
                $res = $conn->query("SELECT DISTINCT day_of_week, start_end_time, subject_name, slot_color FROM student_schedule WHERE student_id = $sid");
                if($res) { while($s = $res->fetch_assoc()) $data[$code]["schedule"][$s['day_of_week']][] = $s; }
            } catch(Exception $e) {}

            // 6. Tài chính
            try {
                $res = $conn->query("SELECT * FROM finance_history WHERE student_id = $sid");
                if($res) {
                    while($f = $res->fetch_assoc()){
                        $data[$code]["finance"]["history"][] = $f;
                        if($f['status']=='paid') $data[$code]["finance"]["paid"] += abs($f['amount']);
                        else $data[$code]["finance"]["due"] += abs($f['amount']);
                    }
                }
            } catch(Exception $e) {}
        }
    }
} catch(Exception $e) {}

// Lấy thông báo & Khóa học chung
$notifs = []; $avail = [];
try {
    $res1 = $conn->query("SELECT * FROM notifications WHERE parent_id = $parent_id ORDER BY id DESC");
    if($res1) { while($n = $res1->fetch_assoc()) $notifs[] = $n; }
    
    $res2 = $conn->query("SELECT * FROM available_courses");
    if($res2) { while($a = $res2->fetch_assoc()) $avail[] = $a; }
} catch(Exception $e) {}

echo json_encode(["students" => $data, "available_courses" => $avail, "notifications" => $notifs]);
$conn->close();
?>