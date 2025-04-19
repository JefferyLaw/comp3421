<?php
session_start(); // Start session to track login status

// Redirect to task.php if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: task.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "task_management");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            header("Location: task.php");
            exit();
        } else {
            $message = "Invalid password!";
        }
    } else {
        $message = "Email not found!";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management - Login</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            /* Subtle background image */
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
            background: rgba(0, 0, 0, 0.4); /* Dark overlay for contrast */
            z-index: 1;
        }

        .container {
            width: 500px;
            padding: 30px;
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 2;
        }

        .logo {
            text-align: center;
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
            margin-bottom: 10px;
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
            color: #dc3545;
            font-size: 14px;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
        }

        .register-link button {
            width: auto;
            background-color: #6c757d;
            padding: 10px 25px;
            font-size: 14px;
        }

        .register-link button:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        .extra-links {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
        }

        .extra-links a {
            color: #007bff;
            text-decoration: none;
            margin: 0 10px;
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
                <h1>Task Master</h1>
            </div>
            <div class="welcome-text">
                Welcome back! Log in to manage your tasks and boost productivity.
            </div>
            <h2>Login</h2>
            <?php if (!empty($message)): ?>
                <p class="message"><?php echo $message; ?></p>
            <?php endif; ?>
            <form action="login.php" method="POST">
                <label>Email:</label>
                <input type="text" name="email" required>
                <label>Password:</label>
                <input type="password" name="password" required>
                <button type="submit">Login</button>
            </form>
            <div class="register-link">
                <form action="register.php" method="GET">
                    <button type="submit">Register</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Add 'filled' class to inputs when they contain text
        document.querySelectorAll('.auth-form input').forEach(input => {
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
        });
    </script>
</body>
</html>