<?php
    session_start();

    include '../db.php';

    // Check if user is logged in
    if (!isset($_SESSION['username']))
    {
        header("Location: ../LogInPage/LogInPage.php");
        exit();
    }

    // Sanitize username for output
    $username = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');

    $isAdmin  = (isset($_SESSION['usertype']) && $_SESSION['usertype'] === 'Administrator');

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
    <title>Users - USJ-R School Management System</title>
    <link rel="stylesheet" href="./UsersPage.css">
</head>
<body>
    <header class="topbar">
        <div class="topbar__brand">USJ-R School Management System v1.01</div>
        <div class="topbar__user">
            <span class="topbar__user-label">You are logged in as: <strong><?php echo $username; ?></strong></span>
            <span class="topbar__divider">|</span>
            <a href="?logout=1" class="btn-logout">Logout</a>
        </div>
    </header>

    <div class="layout">
        <nav class="sidebar">
            <ul>
                <li><a href="../HomePage/HomePage.php">Home</a></li>
                <li><a href="../SchoolsPage/SchoolsPage.php">Schools</a></li>
                <li><a href="../DepartmentsPage/DepartmentsPage.php">Departments</a></li>
                <li><a href="../ProgramsPage/ProgramsPage.php">Programs</a></li>
                <li><a href="../StudentsPage/StudentsPage.php">Students</a></li>
                <li class="userssidebar"><a href="UsersPage.php">Users</a></li>
            </ul>
        </nav>

        <main class="content">
            <section class="page-header">
                <h2>User Dashboard</h2>
            </section>

            <div class="dashboard-body">
                <p class="dashboard-desc">Welcome to the User Dashboard. Here you can manage user accounts and settings.</p>

                <div class="dashboard-actions">
                    <a href="UserListPage/UsersListPage.php" class="btn btn--manage">Manage Users</a>
                    <a href="UserCreatePage/UserCreatePage.php" class="btn btn--add">Add Users</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>