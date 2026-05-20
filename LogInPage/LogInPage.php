<?php
    session_start();

    // Include database connection
    include '../db.php';

    $error = "";

    // Check if user is already logged in
    if (isset($_SESSION['username'])) 
    {
        header("Location: ../HomePage/HomePage.php");
        exit();
    }
    // Handle login form submission
    else if ($_SERVER['REQUEST_METHOD'] === 'POST')
    {
        // Sanitize username to prevent XSS when re-displaying it
        $username = htmlspecialchars(trim($_POST['username'] ?? ''), ENT_QUOTES, 'UTF-8');
        
        // Get the raw password input for authentication (do not sanitize as it may alter the password)
        $password = $_POST['password'] ?? '';

        // Validate that both fields are filled
        if (!empty($username) && !empty($password))
        {
            try 
            {
                // Prepare statement to fetch user record by username
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // If user exists, verify the provided password against the password in DB
                if ($user && $password === $user['password'])
                {
                    // Store essential user info in session variables
                    $_SESSION['userid'] = $user['userid'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['usertype'] = $user['usertype'];
                    $_SESSION['userrole'] = $user['userrole'];
                    
                    // Redirect to the home page
                    header("Location: ../HomePage/HomePage.php");
                    exit();
                }
                else 
                {
                    $error = "Invalid username or password.";
                }
            } 
            catch (PDOException $e) 
            {
                $error = "System error. Please try again later.";
            }
        }
        else
        {
            $error = "Please fill in all fields.";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USJ-R School Management System</title>
    <link rel="stylesheet" href="LogInPage.css">
</head>
<body>
    <header class="topbar">
        <div class="topbar__brand">USJ-R School Management System v1.01</div>

        <div class="topbar__login">
            <form method="POST" action="">
                <div class="login-inputs">
                    <label>
                        Username:
                        <input type="text" name="username">
                    </label>

                    <label>
                        Password:
                        <input type="password" name="password">
                    </label>

                    <button type="submit" class="btn-login">Login</button>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="login-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </header>

    <section class="hero">
        <h1>Welcome to USJ-R School Management System</h1>
        <p>Manage your school's operations efficiently.</p>
    </section>

    <main class="container">
        <div class="section-heading">Quick Access</div>

        <div class="cards">
            <article class="card">
                <div class="card__icon">
                    <img src="../assets/school.png" alt="Schools">
                </div>
                <h2>Schools</h2>
                <p>Manage school information and details.</p>
            </article>

            <article class="card">
                <div class="card__icon">
                    <img src="../assets/department.png" alt="Departments">
                </div>
                <h2>Departments</h2>
                <p>Organize departments within schools.</p>
            </article>

            <article class="card">
                <div class="card__icon">
                    <img src="../assets/program.png" alt="Programs">
                </div>
                <h2>Programs</h2>
                <p>Manage academic programs and courses.</p>
            </article>

            <article class="card">
                <div class="card__icon">
                    <img src="../assets/student.png" alt="Students">
                </div>
                <h2>Students</h2>
                <p>Manage student records and enrollment.</p>
            </article>
        </div>

        <section class="info-panel">
            <h3>Getting Started</h3>
            <ol>
                <li>Log in with your credentials.</li>
                <li>Navigate to any section using the sidebar menu.</li>
                <li>View, create, update, or delete records as needed.</li>
                <li>Contact administrator for access requests.</li>
            </ol>
        </section>
    </main>
</body>
</html>