<?php
header('Content-Type: application/json');
require 'db_config.php';

function getHWID() {
    if (!empty($_SERVER['HTTP_X_HWID'])) {
        return $_SERVER['HTTP_X_HWID'];
    }
    return null;
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['action'])) {
        switch ($data['action']) {
            case 'login':
                if (empty($data['username']) || empty($data['password'])) {
                    $response['message'] = 'Username and password are required';
                    break;
                }
                
                $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->bind_param("s", $data['username']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows == 0) {
                    $response['message'] = 'User not found';
                    break;
                }
                
                $user = $result->fetch_assoc();
                
                if ($user['is_banned']) {
                    $response['message'] = 'Account is banned';
                    break;
                }
                
                if (!password_verify($data['password'], $user['password'])) {
                    $response['message'] = 'Invalid password';
                    break;
                }
                
                if (strtotime($user['expiry_date']) < time()) {
                    $response['message'] = 'Account has expired';
                    break;
                }
                
                $hwid = getHWID();
                if ($hwid) {
                    if ($user['hwid'] === null) {
                        // First login - set HWID
                        $stmt = $conn->prepare("UPDATE users SET hwid = ? WHERE id = ?");
                        $stmt->bind_param("si", $hwid, $user['id']);
                        $stmt->execute();
                        $response['success'] = true;
                        $response['message'] = 'Login successful (HWID set)';
                    } elseif ($user['hwid'] != $hwid) {
                        $response['message'] = 'Account is already registered to another device';
                        break;
                    } else {
                        $response['success'] = true;
                        $response['message'] = 'Login successful';
                    }
                } else {
                    $response['message'] = 'HWID not provided';
                }
                break;
                
            case 'check_status':
                if (empty($data['username'])) {
                    $response['message'] = 'Username is required';
                    break;
                }
                
                $stmt = $conn->prepare("SELECT is_banned, expiry_date FROM users WHERE username = ?");
                $stmt->bind_param("s", $data['username']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows == 0) {
                    $response['message'] = 'User not found';
                    break;
                }
                
                $user = $result->fetch_assoc();
                $response['success'] = true;
                $response['is_banned'] = (bool)$user['is_banned'];
                $response['expiry_date'] = $user['expiry_date'];
                $response['is_expired'] = strtotime($user['expiry_date']) < time();
                break;
                
            default:
                $response['message'] = 'Invalid action';
        }
    } else {
        $response['message'] = 'No action specified';
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
?>