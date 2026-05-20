<?php
    session_start();

    include '../../db.php';

    // Check if user is logged in
    if (!isset($_SESSION['username']))
    {
        header("Location: ../../LogInPage/LogInPage.php");
        exit();
    }

    // Sanitize username for display
    $username = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');

    $isAdmin = (isset($_SESSION['usertype']) && $_SESSION['usertype'] === 'Administrator');
    
    // Handle logout
    if (isset($_GET['logout']))
    {
        session_destroy();
        header("Location: ../../LogInPage/LogInPage.php");
        exit();
    }

    $progid    = filter_input(INPUT_GET, 'progid', FILTER_VALIDATE_INT);
    $success   = '';
    $pageError = '';
    $prog      = null;

    // Validate progid from URL
    if (!$progid)
    {
        $pageError = 'Invalid program identifier.';
    }
    else
    {
        try
        {
            // Load program details for confirmation
            $stmt = $pdo->prepare("SELECT p.progid, p.progfullname, p.progshortname, p.progcolldeptid,
                                          d.deptfullname, c.collfullname
                                   FROM programs p
                                   JOIN departments d ON d.deptid = p.progcolldeptid
                                   JOIN colleges c ON c.collid = d.deptcollid
                                   WHERE p.progid = ?");
            $stmt->execute([$progid]);
            $prog = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$prog)
            {
                $pageError = 'Program record not found.';
            }
        }
        catch (PDOException $e)
        {
            $pageError = 'Error loading program: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }

    // Handle form submission for deletion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$pageError)
    {
        $postId = filter_input(INPUT_POST, 'progid', FILTER_VALIDATE_INT);

        if (!$postId)
        {
            $pageError = 'Invalid program identifier.';
        }
        else
        {
            try
            {
                // Check if program has students linked to it
                $stmt      = $pdo->prepare("SELECT COUNT(*) FROM students WHERE studprogid = ?");
                $stmt->execute([$postId]);
                $studCount = $stmt->fetchColumn();

                if ($studCount > 0)
                {
                    $pageError = 'Cannot delete program. It still has ' . $studCount . ' student(s) assigned to it. Reassign or delete the students first.';
                }
                else
                {
                    // No linked students, safe to delete
                    $stmt = $pdo->prepare("DELETE FROM programs WHERE progid = ?");
                    $stmt->execute([$postId]);

                    if ($stmt->rowCount() > 0)
                    {
                        $success = 'Program deleted successfully.';
                        $prog    = null;
                    }
                    else
                    {
                        $pageError = 'Program not found or already deleted.';
                    }
                }
            }
            catch (PDOException $e)
            {
                $pageError = 'Error deleting program: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Program - USJ-R School Management System</title>
    <link rel="stylesheet" href="./ProgramDeletePage.css">
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
                <li class="programssidebar"><a href="../ProgramsPage.php">Programs</a></li>
                <li><a href="../../StudentsPage/StudentsPage.php">Students</a></li>
                <?php if ($isAdmin): ?>
                    <li><a href="../../UsersPage/UsersPage.php">Users</a></li>
                <?php endif; ?>             
            </ul>
        </nav>

        <main class="content">
            <section class="page-header">
                <h2>Delete Program</h2>
            </section>

            <?php if ($pageError): ?>
                <div class="alert alert--error"><?php echo htmlspecialchars($pageError, ENT_QUOTES, 'UTF-8'); ?></div>
                <a href="../ProgramsPage.php" class="btn btn--secondary">Back to Program List</a>

            <?php elseif ($success): ?>
                <div class="alert alert--success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
                <a href="../ProgramsPage.php" class="btn btn--secondary">Back to Program List</a>

            <?php else: ?>
                <div class="alert alert--warning">Are you sure you want to delete this program entry?<br><br>This entry is part of a high-level relationship in the database. Deleting this entry may affect related data.</div>

                <div class="form-container">
                    <div class="prog-details">
                        <p><strong>Program ID:</strong> <?php echo htmlspecialchars($prog['progid'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>Program Full Name:</strong> <?php echo htmlspecialchars($prog['progfullname'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>Program Short Name:</strong> <?php echo htmlspecialchars($prog['progshortname'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>

                    <form method="POST" action="?progid=<?php echo urlencode($prog['progid']); ?>" class="prog-form" novalidate>
                        <input type="hidden" name="progid" value="<?php echo htmlspecialchars($prog['progid'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="form-actions">
                            <button type="submit" class="btn btn--danger">Confirm Delete</button>
                            <a href="../ProgramsPage.php?selected_dept=<?php echo urlencode($prog['progcolldeptid']); ?>" class="btn btn--secondary">Cancel</a>
                        </div>
                    </form>
                </div>

            <?php endif; ?>
        </main>
    </div>

</body>

</html>