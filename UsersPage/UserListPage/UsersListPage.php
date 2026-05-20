<?php
    session_start();

    include '../../db.php';

    // Check if user is logged in
    if (!isset($_SESSION['username']))
    {
        header("Location: ../../LogInPage/LogInPage.php");
        exit();
    }

    // Sanitize username for output
    $username = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');

    $isAdmin  = (isset($_SESSION['usertype']) && $_SESSION['usertype'] === 'Administrator');

    if (isset($_GET['logout']))
    {
        session_destroy();
        header("Location: ../../LogInPage/LogInPage.php");
        exit();
    }

    // Fetch all users
    $users   = [];
    $dbError = '';

    try
    {
        $stmt  = $pdo->prepare("SELECT userid, username, usertype, userrole FROM users ORDER BY userid ASC");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    catch (PDOException $e)
    {
        $dbError = "Error loading users: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User List - USJ-R School Management System</title>
    <link rel="stylesheet" href="./UsersListPage.css">
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
                <li><a href="../../HomePage/HomePage.php">Home</a></li>
                <li><a href="../../SchoolsPage/SchoolsPage.php">Schools</a></li>
                <li><a href="../../DepartmentsPage/DepartmentsPage.php">Departments</a></li>
                <li><a href="../../ProgramsPage/ProgramsPage.php">Programs</a></li>
                <li><a href="../../StudentsPage/StudentsPage.php">Students</a></li>
                <li class="userssidebar"><a href="../UsersPage.php">Users</a></li>
            </ul>
        </nav>

        <main class="content">
            <section class="page-header">
                <h2>User List</h2>
            </section>

            <div class="page-actions">
                <a href="../../UsersPage/UsersPage.php" class="btn btn--primary">Back</a>
            </div>

            <?php if (!empty($dbError)): ?>
                <div class="alert"><?php echo $dbError; ?></div>
            <?php endif; ?>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>User Name</th>
                        <th>User Type</th>
                        <th>User Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['userid'],   ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($user['username'],  ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($user['usertype'],  ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($user['userrole'],  ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="UserUpdatePage/UserUpdatePage.php?userid=<?php echo urlencode($user['userid']); ?>" class="btn-action btn-update">✎ Update</a>
                                    <a href="UserDeletePage/UserDeletePage.php?userid=<?php echo urlencode($user['userid']); ?>" class="btn-action btn-delete">🗑 Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($users) && empty($dbError)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; color:#aaa;">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="table-footer">
                Total of <?php echo count($users); ?> user(s)
            </div>

        </main>
    </div>
</body>
</html>