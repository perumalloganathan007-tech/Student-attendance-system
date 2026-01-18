<?php
if (!defined('DBCON_INCLUDED')) {
define('DBCON_INCLUDED', true);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
		session_start();
	}

	// Security headers
	header('X-Frame-Options: DENY');
	header('X-XSS-Protection: 1; mode=block');
	header('X-Content-Type-Options: nosniff');

	$host = "localhost";
	$user = "root";
	$pass = "";
	$db = "attendancesystem";
	
	try {
		$conn = new mysqli($host, $user, $pass, $db);
		if($conn->connect_error){
			throw new Exception("Database Connection Failed: " . $conn->connect_error);
		}
		// Set charset to prevent injection issues
		$conn->set_charset("utf8");
	} catch (Exception $e) {
		die("Critical Error: " . $e->getMessage());
	}

	// Function to prevent SQL injection
	function sanitize_input($conn, $data) {
		$data = trim($data);
		$data = stripslashes($data);
		$data = htmlspecialchars($data);
		return $conn->real_escape_string($data);
	}

	// Function to generate CSRF token
	function generate_csrf_token() {
		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
		return $_SESSION['csrf_token'];
	}

	// Function to verify CSRF token
	function verify_csrf_token($token) {
		return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
	}

	// Function to regenerate session safely
	function regenerate_session() {
		$session_data = $_SESSION;
		session_regenerate_id(true);
		$_SESSION = $session_data;
	}

	// Function to validate session
	function validate_session($required_user_type = null) {
		if (!isset($_SESSION['userId'])) {
			header("Location: ../index.php");
			exit();
		}

		// Validate user type if specified
		if ($required_user_type !== null) {
			$user_type = isset($_SESSION['userType']) ? $_SESSION['userType'] : 
				(isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '');
			
			if ($user_type !== $required_user_type) {
				header("Location: ../index.php");
				exit();
			}
		}

		// Check session lifetime (30 minutes)
		$last_activity = isset($_SESSION['LAST_ACTIVITY']) ? $_SESSION['LAST_ACTIVITY'] : 
			(isset($_SESSION['last_login']) ? $_SESSION['last_login'] : time());
			
		if ((time() - $last_activity > 1800)) {
			session_unset();
			session_destroy();
			header("Location: ../index.php?timeout=1");
			exit();
		}

		// Update last activity time
		$_SESSION['last_login'] = time();
		$_SESSION['LAST_ACTIVITY'] = time();
	}

	// Function to hash passwords
	function hash_password($password) {
		return password_hash($password, PASSWORD_DEFAULT);
	}

	// Function to verify password
	function verify_password($password, $hash) {
		return password_verify($password, $hash);
	}

	// Function to handle login attempt with proper password verification
	function verify_login($conn, $table, $username_field, $username, $password) {
		$query = "SELECT * FROM $table WHERE $username_field = ?";
		$stmt = $conn->prepare($query);
		$stmt->bind_param("s", $username);
		$stmt->execute();
		$result = $stmt->get_result();
		$user = $result->fetch_assoc();
		
		if ($user && verify_password($password, $user['password'])) {
			return $user;
		}
		return false;
	}
} // end of include guard
?>