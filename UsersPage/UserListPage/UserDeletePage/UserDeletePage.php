<?php
    session_start();

    // Include database connection
    include '../../../db.php';

    if (!isset($_SESSION['username']))
    {
        header("Location: ../../../LogInPage/LogInPage.php");
        exit();
    }

    // Sanitize username for output
    $username = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');

    $isAdmin  = (isset($_SESSION['usertype']) && $_SESSION['usertype'] === 'Administrator');

    // Handle logout
    if (isset($_GET['logout']))
    {
        session_destroy();
        header("Location: ../../../LogInPage/LogInPage.php");
        exit();
    }

    $userid    = filter_input(INPUT_GET, 'userid', FILTER_VALIDATE_INT);
    $success   = '';
    $pageError = '';
    $user      = null;

    if (!$userid)
    {
        $pageError = 'Invalid user identifier.';
    }
    else
    {
        try
        {
            // Fetch user details for confirmation
            $stmt = $pdo->prepare("SELECT userid, username, usertype, userrole FROM users WHERE userid = ?");
            $stmt->execute([$userid]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user)
            {
                $pageError = 'User record not found.';
            }
        }
        catch (PDOException $e)
        {
            $pageError = 'Error loading user: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }

    // Handle deletion on POST request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$pageError)
    {
        $postId = filter_input(INPUT_POST, 'userid', FILTER_VALIDATE_INT);

        if (!$postId)
        {
            $pageError = 'Invalid user identifier.';
        }
        else
        {
            try
            {
                // Perform deletion
                $stmt = $pdo->prepare("DELETE FROM users WHERE userid = ?");
                $stmt->execute([$postId]);

                if ($stmt->rowCount() > 0)
                {
                    $success = 'User deleted successfully.';
                    $user    = null;
                }
                else
                {
                    $pageError = 'User not found or already deleted.';
                }
            }
            catch (PDOException $e)
            {
                $pageError = 'Error deleting user: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete User - USJ-R School Management System</title>
    <link rel="stylesheet" href="./UserDeletePage.css">
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
                <li><a href="../../../HomePage/HomePage.php">Home</a></li>
                <li><a href="../../../SchoolsPage/SchoolsPage.php">Schools</a></li>
                <li><a href="../../../DepartmentsPage/DepartmentsPage.php">Departments</a></li>
                <li><a href="../../../ProgramsPage/ProgramsPage.php">Programs</a></li>
                <li><a href="../../../StudentsPage/StudentsPage.php">Students</a></li>
                <li class="userssidebar"><a href="../../UsersPage.php">Users</a></li>
            </ul>
        </nav>

        <main class="content">
            <section class="page-header">
                <h2>Delete User</h2>
            </section>

            <?php if ($pageError): ?>
                <div class="alert alert--error"><?php echo htmlspecialchars($pageError, ENT_QUOTES, 'UTF-8'); ?></div>
                <a href="../UsersListPage.php" class="btn btn--secondary">Back to User List</a>

            <?php elseif ($success): ?>
                <div class="alert alert--success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
                <a href="../UsersListPage.php" class="btn btn--secondary">Back to User List</a>

            <?php else: ?>
                <div class="alert alert--warning">Are you sure you want to delete this user? This action is permanent and cannot be undone.</div>

                <div class="form-container">
                    <div class="user-details">
                        <p><strong>User ID:</strong>   <?php echo htmlspecialchars($user['userid'],   ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>Username:</strong>  <?php echo htmlspecialchars($user['username'],  ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>User Type:</strong> <?php echo htmlspecialchars($user['usertype'],  ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>User Role:</strong> <?php echo htmlspecialchars($user['userrole'],  ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>

                    <form method="POST" action="?userid=<?php echo urlencode($user['userid']); ?>" class="user-form" novalidate>
                        <input type="hidden" name="userid" value="<?php echo htmlspecialchars($user['userid'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="form-actions">
                            <button type="submit" class="btn btn--danger">Confirm Delete</button>
                            <a href="../UsersListPage.php" class="btn btn--secondary">Cancel</a>
                        </div>
                    </form>
                </div>

            <?php endif; ?>
        </main>
    </div>
</body>
</html>