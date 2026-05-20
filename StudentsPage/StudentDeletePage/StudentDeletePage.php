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

    $studid    = filter_input(INPUT_GET, 'studid', FILTER_VALIDATE_INT);
    $success   = '';
    $pageError = '';
    $stud      = null;

    if (!$studid)
    {
        $pageError = 'Invalid student identifier.';
    }
    else
    {
        try
        {
            // Load student details for confirmation
            $stmt = $pdo->prepare("SELECT s.studid, s.studfirstname, s.studmidname, s.studlastname,
                                          s.studyear, s.studprogid,
                                          p.progfullname, p.progcolldeptid
                                   FROM students s
                                   JOIN programs p ON p.progid = s.studprogid
                                   WHERE s.studid = ?");
            $stmt->execute([$studid]);
            $stud = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$stud)
            {
                $pageError = 'Student record not found.';
            }
        }
        catch (PDOException $e)
        {
            $pageError = 'Error loading student: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }

    // Handle form submission for deletion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$pageError)
    {
        $postId = filter_input(INPUT_POST, 'studid', FILTER_VALIDATE_INT);

        if (!$postId)
        {
            $pageError = 'Invalid student identifier.';
        }
        else
        {
            try
            {
                // Perform deletion
                $stmt = $pdo->prepare("DELETE FROM students WHERE studid = ?");
                $stmt->execute([$postId]);

                if ($stmt->rowCount() > 0)
                {
                    $success = 'Student deleted successfully.';
                    $stud    = null;
                }
                else
                {
                    $pageError = 'Student not found or already deleted.';
                }
            }
            catch (PDOException $e)
            {
                $pageError = 'Error deleting student: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Student - USJ-R School Management System</title>
    <link rel="stylesheet" href="./StudentDeletePage.css">
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
                <li class="studentssidebar"><a href="../StudentsPage.php">Students</a></li>
                <?php if ($isAdmin): ?>
                    <li><a href="../../UsersPage/UsersPage.php">Users</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <main class="content">
            <section class="page-header">
                <h2>Delete Student</h2>
            </section>

            <?php if ($pageError): ?>
                <div class="alert alert--error"><?php echo htmlspecialchars($pageError, ENT_QUOTES, 'UTF-8'); ?></div>
                <a href="../StudentsPage.php" class="btn btn--secondary">Back to Student List</a>

            <?php elseif ($success): ?>
                <div class="alert alert--success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
                <a href="../StudentsPage.php" class="btn btn--secondary">Back to Student List</a>

            <?php else: ?>
                <div class="alert alert--warning">Are you sure you want to delete this student entry?<br><br>This action is permanent and cannot be undone.</div>

                <div class="form-container">
                    <div class="stud-details">
                        <p><strong>Student ID:</strong> <?php echo htmlspecialchars($stud['studid'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>First Name:</strong> <?php echo htmlspecialchars($stud['studfirstname'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>Middle Name:</strong> <?php echo htmlspecialchars($stud['studmidname'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>Last Name:</strong> <?php echo htmlspecialchars($stud['studlastname'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>Year:</strong> <?php echo htmlspecialchars($stud['studyear'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>Program:</strong> <?php echo htmlspecialchars($stud['progfullname'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>

                    <form method="POST" action="?studid=<?php echo urlencode($stud['studid']); ?>" class="stud-form" novalidate>
                        <input type="hidden" name="studid" value="<?php echo htmlspecialchars($stud['studid'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="form-actions">
                            <button type="submit" class="btn btn--danger">Confirm Delete</button>
                            <a href="../StudentsPage.php?selected_prog=<?php echo urlencode($stud['studprogid']); ?>" class="btn btn--secondary">Cancel</a>
                        </div>
                    </form>
                </div>

            <?php endif; ?>
        </main>
    </div>

</body>

</html>