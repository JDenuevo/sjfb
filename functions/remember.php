<?php
/**
 * Remember Me Token System
 * Works on both localhost and production
 */

// Set cookie parameters based on environment
function getCookieParams() {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                || $_SERVER['SERVER_PORT'] == 443;
    
    // Determine domain based on environment
    $domain = '';
    if ($_SERVER['HTTP_HOST'] !== 'localhost' && strpos($_SERVER['HTTP_HOST'], '127.0.0.1') === false) {
        // Production - use actual domain
        $domain = $_SERVER['HTTP_HOST'];
    }
    
    return [
        'expires' => time() + (30 * 24 * 60 * 60), // 30 days
        'path' => '/', // Use root path to work everywhere
        'domain' => $domain,
        'secure' => $isHttps, // Only send over HTTPS in production
        'httponly' => true, // Prevent JavaScript access
        'samesite' => 'Lax' // CSRF protection
    ];
}

/**
 * Generate and store remember me token
 */
function createRememberToken($conn, $account_id) {
    try {
        // Generate cryptographically secure random tokens
        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        
        // Hash the validator before storing
        $token_hash = password_hash($validator, PASSWORD_DEFAULT);
        
        // Calculate expiry (30 days from now)
        $expires = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60));
        
        // Delete any existing tokens for this user
        $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE account_id = ?");
        $stmt->bind_param("i", $account_id);
        $stmt->execute();
        $stmt->close();
        
        // Store new token
        $stmt = $conn->prepare("INSERT INTO remember_tokens (account_id, token_hash, selector, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $account_id, $token_hash, $selector, $expires);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Combine selector and validator for cookie value
            $cookieValue = $selector . ':' . $validator;
            
            // Set cookie with appropriate parameters
            $params = getCookieParams();
            setcookie(
                'remember_me',
                $cookieValue,
                [
                    'expires' => $params['expires'],
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite']
                ]
            );
            
            // Debug log
            error_log("Remember token created for account_id: $account_id");
            
            return true;
        }
        
        $stmt->close();
        return false;
    } catch (Exception $e) {
        error_log("Error creating remember token: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate remember me token and log user in
 */
function validateRememberToken($conn) {
    if (!isset($_COOKIE['remember_me'])) {
        return false;
    }
    
    $cookieParts = explode(':', $_COOKIE['remember_me']);
    
    if (count($cookieParts) !== 2) {
        clearRememberCookie();
        return false;
    }
    
    list($selector, $validator) = $cookieParts;
    
    // Fetch token from database
    $stmt = $conn->prepare("
        SELECT rt.token_hash, rt.account_id, rt.expires_at, 
               a.username, a.role 
        FROM remember_tokens rt
        JOIN accounts a ON rt.account_id = a.account_id
        WHERE rt.selector = ? AND rt.expires_at > NOW()
    ");
    $stmt->bind_param("s", $selector);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        
        // Verify the validator
        if (password_verify($validator, $row['token_hash'])) {
            // Token is valid - log user in
            $_SESSION['account_id'] = $row['account_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            
            // Set role-specific session flags
            switch ($row['role']) {
                case 'customer':
                    $_SESSION['loggedinasuser'] = true;
                    break;
                case 'admin':
                    $_SESSION['loggedinasadmin'] = true;
                    break;
                case 'super_admin':
                    $_SESSION['loggedinassupadmin'] = true;
                    break;
                case 'rider':
                    $_SESSION['loggedinasrider'] = true;
                    break;
            }
            
            // Regenerate token for security (token rotation)
            createRememberToken($conn, $row['account_id']);
            
            $stmt->close();
            
            error_log("User auto-logged in via remember token: " . $row['username']);
            
            return true;
        }
    }
    
    $stmt->close();
    
    // Invalid token - clear it
    clearRememberCookie();
    deleteRememberToken($conn, $selector);
    
    return false;
}

/**
 * Clear remember me cookie
 */
function clearRememberCookie() {
    if (isset($_COOKIE['remember_me'])) {
        $params = getCookieParams();
        setcookie(
            'remember_me',
            '',
            [
                'expires' => time() - 3600,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite']
            ]
        );
        unset($_COOKIE['remember_me']);
        
        error_log("Remember cookie cleared");
    }
}

/**
 * Delete remember token from database
 */
function deleteRememberToken($conn, $selector = null) {
    if ($selector) {
        $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE selector = ?");
        $stmt->bind_param("s", $selector);
    } else if (isset($_SESSION['account_id'])) {
        $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE account_id = ?");
        $stmt->bind_param("i", $_SESSION['account_id']);
    } else {
        return;
    }
    
    $stmt->execute();
    $stmt->close();
    
    error_log("Remember token deleted from database");
}

/**
 * Logout and clear remember me
 */
function logoutAndClearRemember($conn) {
    // Delete token from database
    if (isset($_SESSION['account_id'])) {
        deleteRememberToken($conn);
    }
    
    // Clear cookie
    clearRememberCookie();
    
    // Clear session variables
    $_SESSION = array();
    
    // Destroy session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Clean up expired tokens
 */
function cleanupExpiredTokens($conn) {
    $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE expires_at < NOW()");
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();
    
    if ($deleted > 0) {
        error_log("Cleaned up $deleted expired remember tokens");
    }
}
?>