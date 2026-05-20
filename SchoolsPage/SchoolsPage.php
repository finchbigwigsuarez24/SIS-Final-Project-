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

    // Fetch schools from database
    try 
    {
        $stmt = $pdo->query("SELECT collid, collfullname, collshortname FROM colleges ORDER BY collid");
        $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } 
    catch (PDOException $e) 
    {
        $schools = [];
        $dbError = "Error loading schools data: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USJ-R School Management System</title>
    <link rel="stylesheet" href="./SchoolsPage.css">
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
                <li><a href="../HomePage/HomePage.php">Home</a></li>
                <li class="schoolssidebar"><a href="../SchoolsPage/SchoolsPage.php">Schools</a></li>
                <li><a href="../DepartmentsPage/DepartmentsPage.php">Departments</a></li>
                <li><a href="../ProgramsPage/ProgramsPage.php">Programs</a></li>
                <li><a href="../StudentsPage/StudentsPage.php">Students</a></li>
                <?php if ($isAdmin): ?>
                    <li><a href="../UsersPage/UsersPage.php">Users</a></li>
                <?php endif; ?>            
            </ul>
        </nav>

    <main class="content">
        <section class="page-header">
            <h2>School List</h2>
        </section>

        <div class="page-actions">
            <a href="SchoolCreatePage/SchoolCreatePage.php" class="btn btn--primary">+ Create School Entry</a>
        </div>

        <?php if (!empty($dbError)): ?>
            <div class="alert">
                <?php echo $dbError; ?>
            </div>
        <?php endif; ?>

        <table class="data-table">
            <thead>
                <tr>
                    <th>School ID</th>
                    <th>School Full Name</th>
                    <th>School Short Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($schools as $school): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($school['collid'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($school['collfullname'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($school['collshortname'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="SchoolUpdatePage/SchoolUpdatePage.php?collid=<?php echo urlencode($school['collid']); ?>" class="btn-action btn-update" title="Update">✎ Update</a>
                                <a href="SchoolDeletePage/SchoolDeletePage.php?collid=<?php echo urlencode($school['collid']); ?>" class="btn-action btn-delete" title="Delete">🗑 Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="table-footer">
            Total of <?php echo count($schools); ?> schools in the database
        </div>
    </main>
    </div>
</body>

</html>