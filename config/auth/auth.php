<?php

session_start();

class Auth
{
    private $conn;

    public function __construct($connection)
    {
        $this->conn = $connection;
    }

    public function login($username, $password)
    {
        try {
            $stmt = $this->conn->prepare("SELECT id, username, email, password, role, status FROM users WHERE (username = ? OR email = ?) AND status = 'active'");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    // Set session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['login_time'] = time();

                    // Create session token for security
                    $this->createSessionToken($user['id']);

                    $stmt->close();
                    return ['success' => true, 'user' => $user];
                }
            }
            $stmt->close();
            return ['success' => false, 'message' => 'Username/Email atau Password salah'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function register($username, $email, $password, $role = 'mahasiswa')
    {
        try {
            // Check if username or email already exists
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $stmt->close();
                return ['success' => false, 'message' => 'Username atau Email sudah digunakan'];
            }
            $stmt->close();

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user
            $stmt = $this->conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $hashedPassword, $role);

            if ($stmt->execute()) {
                $userId = $this->conn->insert_id;
                $stmt->close();
                return ['success' => true, 'user_id' => $userId, 'message' => 'Registrasi berhasil'];
            } else {
                $stmt->close();
                return ['success' => false, 'message' => 'Gagal melakukan registrasi'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function logout()
    {
        if (isset($_SESSION['user_id'])) {
            $this->deleteSessionToken($_SESSION['user_id']);
        }

        session_unset();
        session_destroy();
        return true;
    }

    public function isLoggedIn()
    {
        return isset($_SESSION['user_id']) && isset($_SESSION['username']);
    }

    public function requireLogin()
    {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }

    public function requireRole($allowedRoles)
    {
        $this->requireLogin();
        if (!in_array($_SESSION['role'], $allowedRoles)) {
            header('Location: unauthorized.php');
            exit();
        }
    }

    public function getCurrentUser()
    {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'email' => $_SESSION['email'],
                'role' => $_SESSION['role']
            ];
        }
        return null;
    }

    private function createSessionToken($userId)
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];

        $stmt = $this->conn->prepare("INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $userId, $token, $ipAddress, $userAgent, $expiresAt);
        $stmt->execute();
        $stmt->close();

        $_SESSION['session_token'] = $token;
    }

    private function deleteSessionToken($userId)
    {
        $stmt = $this->conn->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function changePassword($userId, $currentPassword, $newPassword)
    {
        try {
            // Verify current password
            $stmt = $this->conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($currentPassword, $user['password'])) {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                    $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->bind_param("si", $hashedPassword, $userId);

                    if ($stmt->execute()) {
                        $stmt->close();
                        return ['success' => true, 'message' => 'Password berhasil diubah'];
                    }
                }
            }
            $stmt->close();
            return ['success' => false, 'message' => 'Password lama salah'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}

require_once 'db.php';
$auth = new Auth($conn);
