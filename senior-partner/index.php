<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['senior_partner_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Include database configuration
    $config_path = __DIR__ . '/../database/config.php';
    if (!file_exists($config_path)) {
        die("Database configuration file not found");
    }
    require_once $config_path;
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        $sql = "SELECT id, name, email, password FROM senior_partners WHERE email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password'])) {
                $_SESSION['senior_partner_id'] = $user['id'];
                $_SESSION['senior_partner_name'] = $user['name'];
                $_SESSION['senior_partner_email'] = $user['email'];
                
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid email or password";
            }
        } else {
            $error = "Invalid email or password";
        }
    } catch(PDOException $e) {
        $error = "Login failed. Please try again later.";
        error_log("Login error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Senior Partner Login - WaryChary</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Lottie Player -->
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #ffffff;
            --secondary-color: #2c3e50;
            --accent-color: #3498db;
            --text-color: #000000;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 1200px;
        }

        .login-row {
            min-height: 600px;
        }

        .animation-column {
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            padding: 3rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-column {
            padding: 3rem;
        }

        .form-floating {
            margin-bottom: 1rem;
        }

        .password-field {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            z-index: 10;
            background: none;
            border: none;
            color: var(--secondary-color);
        }

        .btn-login {
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            border: none;
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .error-message {
            color: #dc3545;
            margin-bottom: 1rem;
            padding: 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .animation-column {
                padding: 2rem;
                min-height: 300px;
            }

            .form-column {
                padding: 2rem;
            }

            .login-row {
                min-height: auto;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="row g-0 login-row">
                <div class="col-md-6 animation-column">
                    <lottie-player
                        src="../lottie-animations/login screen.json"
                        background="transparent"
                        speed="1"
                        style="width: 100%; height: 100%;"
                        loop
                        autoplay>
                    </lottie-player>
                </div>
                <div class="col-md-6 form-column">
                    <div class="text-center mb-4">
                        <h2 class="mb-3">Welcome Back!</h2>
                        <p class="text-muted">Please login to your senior partner account</p>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="error-message text-center">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="needs-validation" novalidate>
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
                            <label for="email">Email address</label>
                        </div>

                        <div class="form-floating mb-4 password-field">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                            <label for="password">Password</label>
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="bi bi-eye-slash" id="toggleIcon"></i>
                            </button>
                        </div>

                        <div class="d-grid gap-2 mb-4">
                            <button type="submit" class="btn btn-login">Login to Dashboard</button>
                        </div>

                        <div class="text-center">
                            <p class="mb-0 text-muted">Need help? Contact your administrator</p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Password visibility toggle
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            }
        }

        // Form validation
        (function () {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>