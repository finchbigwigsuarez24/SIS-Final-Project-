<?php
    session_start();

    // Include database connection
    include '../db.php';

    // Check if user is logged in
    if (!isset($_SESSION['username'])) 
    {
        header("Location: ../LogInPage/LogInPage.php");
        exit();
    }

    // Sanitize username for display
    $username = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');
    
    // Check if the user is an Administrator
    $isAdmin = (isset($_SESSION['usertype']) && $_SESSION['usertype'] === 'Administrator');

    // Handle logout
    if (isset($_GET['logout']))
    {
        session_destroy();
        header("Location: ../LogInPage/LogInPage.php");
        exit();
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USJ-R School Management System</title>
    <link rel="stylesheet" href="./HomePage.css">
</head>

<body>
    <header class="topbar">
        <div class="topbar__brand">USJ-R School Management System v1.01</div>

        <div class="topbar__user">
            <span class="topbar__user-label">
                You are logged in as: <strong><?php echo $username; ?></strong>
            </span>
            <span class="topbar__divider">|</span>
            <a href="?logout=1" class="btn-logout">Logout</a>
        </div>
    </header>

    <div class="layout">
        <nav class="sidebar">
            <ul>
                <li class="homesidebar"><a href="HomePage.php">Home</a></li>
                <li><a href="../SchoolsPage/SchoolsPage.php">Schools</a></li>
                <li><a href="../DepartmentsPage/DepartmentsPage.php">Departments</a></li>
                <li><a href="../ProgramsPage/ProgramsPage.php">Programs</a></li>
                <li><a href="../StudentsPage/StudentsPage.php">Students</a></li>
                <?php if ($isAdmin): ?>
                    <li><a href="../UsersPage/UsersPage.php">Users</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <main class="content">
            <section class="hero">
                <h1>Welcome to USJ-R School Management System</h1>
                <p>Hello, <strong><?php echo $username; ?></strong>!</p>
                <p class="hero__sub">Manage your school's operations efficiently.</p>
            </section>

            <section class="quick-access">
                <div class="section-heading">Quick Access</div>

                <div class="cards">
                    <article class="card">
                        <div class="card__icon">
                            <img src="../assets/school.png" alt="Schools">
                        </div>
                        <h2>Schools</h2>
                        <p>Manage school information and details.</p>
                        <a href="../SchoolsPage/SchoolsPage.php" class="btn btn--primary">View Schools</a>
                    </article>

                    <article class="card">
                        <div class="card__icon">
                            <img src="../assets/department.png" alt="Departments">
                        </div>
                        <h2>Departments</h2>
                        <p>Organize departments within schools.</p>
                        <a href="../DepartmentsPage/DepartmentsPage.php" class="btn btn--primary">View Departments</a>
                    </article>

                    <article class="card">
                        <div class="card__icon">
                            <img src="../assets/program.png" alt="Programs">
                        </div>
                        <h2>Programs</h2>
                        <p>Manage academic programs and courses.</p>
                        <a href="../ProgramsPage/ProgramsPage.php" class="btn btn--primary">View Programs</a>
                    </article>

                    <article class="card">
                        <div class="card__icon">
                            <img src="../assets/student.png" alt="Students">
                        </div>
                        <h2>Students</h2>
                        <p>Manage student records and enrollment.</p>
                        <a href="../StudentsPage/StudentsPage.php" class="btn btn--primary">View Students</a>
                    </article>

                    <?php if ($isAdmin): ?>
                        <article class="card card--admin">
                            <div class="card__icon">
                                <img src="../assets/cog.png" alt="User Management">
                            </div>
                            <h2>User Management</h2>
                            <p>Manage system users and permissions.</p>
                            <a href="../UsersPage/UsersPage.php" class="btn btn--admin">Manage Users</a>
                        </article>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
</body>

</html>