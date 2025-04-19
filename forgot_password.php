<?php
session_start(); // Start session for CSRF token and messages

// Redirect to task.php if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: task.php");
    exit();
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$conn = new mysqli("localhost", "root", "", "task_management");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "Invalid request. Please try again.";
    } else {
        $username = $conn->real_escape_string($_POST['username']);

        // Check if username exists
        $sql = "SELECT id, username FROM users WHERE username='$username'";
        $result = $conn->query($sql);

        if ($result->num_rows == 1) {
            // Placeholder for password reset logic
            // In a real app, generate a reset token, store it, and send an email
            $user = $result->fetch_assoc();
            $reset_token = bin2hex(random_bytes(16)); // Example token
            // Example: Store token in database with expiry
            // $conn->query("INSERT INTO password_resets (user_id, token, expiry) VALUES ('{$user['id']}', '$reset_token', DATE_ADD(NOW(), INTERVAL 1 HOUR))");

            // Placeholder: Simulate sending email
            // mail($user['email'], "Password Reset", "Reset link: http://yourdomain.com/reset_password.php?token=$reset_token");
            $message = "A password reset link has been sent to your registered email (simulated). Please check your inbox.";
        } else {
            $message = "Username not found!";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMaster - Forgot Password</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            /* Same background image as login.php */
            background-image: url('https://images.unsplash.com/photo-1507525428034-b723cf961d3e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        /* Overlay for readability */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1;
        }

        .container {
            width: 400px;
            padding: 30px;
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 2;
        }

        .logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo h1 {
            font-size: 28px;
            color: #333;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .welcome-text {
            text-align: center;
            color: #555;
            font-size: 16px;
            margin-bottom: 20px;
        }

        .auth-form h2 {
            margin: 0 0 10px;
            text-align: center;
            color: #333;
            font-size: 24px;
        }

        .auth-form label {
            display: block;
            margin: 12px 0 6px;
            color: #333;
            font-weight: 500;
        }

        .auth-form input {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        /* Focus animation */
        .auth-form input:focus {
            outline: none;
            transform: scale(1.02);
            box-shadow: 0 0 8px rgba(51, 51, 51, 0.3);
            border-color: #007bff;
        }

        /* Filled input animation */
        .auth-form input.filled {
            background-color: #e7f3ff;
            border-color: #28a745;
        }

        .auth-form button {
            width: 100%;
            margin-top: 15px;
            padding: 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .auth-form button:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .message {
            margin: 10px 0;
            text-align: center;
            font-size: 14px;
            animation: fadeIn 0.5s ease;
        }

        .message.success {
            color: #28a745;
        }

        .message.error {
            color: #dc3545;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .extra-links {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }

        .extra-links a {
            color: #007bff;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .extra-links a:hover {
            color: #0056b3;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-form">
            <div class="logo">
                <h1>TaskMaster</h1>
            </div>
            <div class="welcome-text">
                Forgot your password? Enter your username to receive a reset link.
            </div>
            <h2>Forgot Password</h2>
            <?php if (!empty($message)): ?>
                <p class="message <?php echo strpos($message, 'Invalid') !== false || strpos($message, 'not found') !== false ? 'error' : 'success'; ?>">
                    <?php echo $message; ?>
                </p>
            <?php endif; ?>
            <form action="forgot_password.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <label>Username:</label>
                <input type="text" name="username" required>
                <button type="submit">Send Reset Link</button>
            </form>
            <div class="extra-links">
                <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>

    <script>
        // Add 'filled' class to input when it contains text
        const input = document.querySelector('.auth-form input[name="username"]');
        // Check initial state
        if (input.value.trim() !== '') {
            input.classList.add('filled');
        }

        // Update on input change
        input.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                this.classList.add('filled');
            } else {
                this.classList.remove('filled');
            }
        });
    </script>
</body>
</html>