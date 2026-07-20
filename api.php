<?php
/**
 * Complete CRUD & Loan/Payment Management API
 * Configured specifically for school_db.sql schema
 */
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

// Update host, user, and pass according to your environment (XAMPP default: root / no password)
$host = "localhost";
$user = "root"; 
$pass = "rootpassword"; 
$dbname = "school_db";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_with_code(["error" => "Connection failed: " . $e->getMessage()], 500);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : null;

// Helper function to return JSON with dynamic HTTP status code
function json_with_code($data, $code) {
    http_response_code($code);
    return json_encode($data);
}

// -------------------------------------------------------------
// 1. LOANS MANAGEMENT ENDPOINTS
// -------------------------------------------------------------
if ($action === 'loans_list' && $method === 'GET') {
    $studentId = isset($_GET['studentId']) ? $_GET['studentId'] : null;
    if (empty($studentId)) {
        echo json_with_code(["error" => "studentId required"], 400);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, student_id, amount, loan_type, status, created_at FROM loans WHERE student_id = ? ORDER BY id DESC");
    $stmt->execute([$studentId]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($action === 'loans_create' && $method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (empty($data['student_id']) || empty($data['amount']) || empty($data['loan_type']) || empty($data['status'])) {
        echo json_with_code(["error" => "student_id, amount, loan_type, status are required"], 400);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO loans (student_id, amount, loan_type, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $data['student_id'],
        $data['amount'],
        $data['loan_type'],
        $data['status']
    ]);

    echo json_encode(["message" => "Loan added successfully!"]);
    exit;
}

// -------------------------------------------------------------
// 2. PAYMENTS MANAGEMENT ENDPOINTS
// -------------------------------------------------------------
if ($action === 'payments_list' && $method === 'GET') {
    $loanId = isset($_GET['loanId']) ? $_GET['loanId'] : null;
    if (empty($loanId)) {
        echo json_with_code(["error" => "loanId required"], 400);
        exit;
    }

    // Alias 'amount' as 'payment_amount' so loans.html JavaScript reads it correctly!
    $stmt = $pdo->prepare("SELECT id, loan_id, amount AS payment_amount, payment_date, payment_method, created_at FROM payments WHERE loan_id = ? ORDER BY id DESC");
    $stmt->execute([$loanId]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($action === 'payments_create' && $method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (empty($data['loan_id']) || empty($data['payment_amount']) || empty($data['payment_date']) || empty($data['payment_method'])) {
        echo json_with_code(["error" => "loan_id, payment_amount, payment_date, payment_method are required"], 400);
        exit;
    }

    // Maps payload key 'payment_amount' to SQL column 'amount'
    $stmt = $pdo->prepare("INSERT INTO payments (loan_id, amount, payment_date, payment_method) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $data['loan_id'],
        $data['payment_amount'],
        $data['payment_date'],
        $data['payment_method']
    ]);

    echo json_encode(["message" => "Payment recorded successfully!"]);
    exit;
}

// -------------------------------------------------------------
// 3. MAIN STUDENT CRUD ENDPOINTS
// -------------------------------------------------------------
switch ($method) {
    case 'GET':
        $stmt = $pdo->query("SELECT * FROM students ORDER BY id DESC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['name']) && !empty($data['email']) && !empty($data['course'])) {
            $stmt = $pdo->prepare("INSERT INTO students (name, email, course) VALUES (?, ?, ?)");
            $stmt->execute([
                $data['name'], 
                $data['email'], 
                $data['course']
            ]);
            echo json_encode(["message" => "Student added successfully!"]);
        } else {
            echo json_with_code(["error" => "All fields are required"], 400);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['id']) && !empty($data['name']) && !empty($data['email']) && !empty($data['course'])) {
            $stmt = $pdo->prepare("UPDATE students SET name = ?, email = ?, course = ? WHERE id = ?");
            $stmt->execute([
                $data['name'], 
                $data['email'], 
                $data['course'], 
                $data['id']
            ]);
            echo json_encode(["message" => "Student updated successfully!"]);
        } else {
            echo json_with_code(["error" => "Invalid data provided"], 400);
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['id'])) {
            $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$data['id']]);
            echo json_encode(["message" => "Student deleted successfully!"]);
        } else {
            echo json_with_code(["error" => "ID required"], 400);
        }
        break;

    default:
        echo json_with_code(["error" => "Method not allowed"], 405);
        break;
}
?>