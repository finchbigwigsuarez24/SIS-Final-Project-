<?php
    session_start();

    // Include database connection
    include '../../db.php';

    // Check if user is logged in
    if (!isset($_SESSION['username']))
    {
        header("Location: ../../LogInPage/LogInPage.php");
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
        header("Location: ../../LogInPage/LogInPage.php");
        exit();
    }

    // Get deptid from URL
    $deptid    = filter_input(INPUT_GET, 'deptid', FILTER_VALIDATE_INT);

    $success   = '';
    $pageError = '';
    $dept      = null;

    // Validate deptid
    if (!$deptid)
    {
        $pageError = 'Invalid department identifier.';
    }
    else
    {
        // Load department details for confirmation
        try
        {
            $stmt = $pdo->prepare("SELECT d.deptid, d.deptfullname, d.deptshortname, d.deptcollid, c.collfullname
                                   FROM departments d
                                   JOIN colleges c ON c.collid = d.deptcollid
                                   WHERE d.deptid = ?");
            $stmt->execute([$deptid]);
            $dept = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check if department exists
            if (!$dept)
            {
                $pageError = 'Department record not found.';
            }
        }
        catch (PDOException $e)
        {
            $pageError = 'Error loading department: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }

    // Handle form submission for deletion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$pageError)
    {
        $postId = filter_input(INPUT_POST, 'deptid', FILTER_VALIDATE_INT);

        if (!$postId)
        {
            $pageError = 'Invalid department identifier.';
        }
        else
        {
            try
            {
                // Import programs count to check for linked programs
                $stmt      = $pdo->prepare("SELECT COUNT(*) FROM programs WHERE progcolldeptid = ?");
                $stmt->execute([$postId]);
                $progCount = $stmt->fetchColumn();

                // Import students count to check for linked students
                $stmt      = $pdo->prepare("SELECT COUNT(*) FROM students WHERE studcolldeptid = ?");
                $stmt->execute([$postId]);
                $studCount = $stmt->fetchColumn();

                // Delete only if there are no linked programs or students
                if ($progCount > 0)
                {
                    $pageError = 'Cannot delete department. It still has ' . $progCount . ' program(s) linked to it. Delete the programs first.';
                }
                elseif ($studCount > 0)
                {
                    $pageError = 'Cannot delete department. It still has ' . $studCount . ' student(s) assigned to it. Reassign or delete the students first.';
                }
                else
                {
                    $stmt = $pdo->prepare("DELETE FROM departments WHERE deptid = ?");
                    $stmt->execute([$postId]);

                    if ($stmt->rowCount() > 0)
                    {
                        $success = 'Department deleted successfully.';
                        $dept    = null;
                    }
                    else
                    {
                        $pageError = 'Department not found or already deleted.';
                    }
                }
            }
            catch (PDOException $e)
            {
                $pageError = 'Error deleting department: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Department - USJ-R School Management System</title>
    <link rel="stylesheet" href="./DepartmentDeletePage.css">
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
                <li class="departmentssidebar"><a href="../DepartmentsPage.php">Departments</a></li>
                <li><a href="../../ProgramsPage/ProgramsPage.php">Programs</a></li>
                <li><a href="../../StudentsPage/StudentsPage.php">Students</a></li>
                <?php if ($isAdmin): ?>
                    <li><a href="../../UsersPage/UsersPage.php">Users</a></li>
                <?php endif; ?>             
            </ul>
        </nav>

        <main class="content">
            <section class="page-header">
                <h2>Delete Department</h2>
            </section>

            <?php if ($pageError): ?>
                <div class="alert alert--error"><?php echo htmlspecialchars($pageError, ENT_QUOTES, 'UTF-8'); ?></div>
                <a href="../DepartmentsPage.php" class="btn btn--secondary">Back to Department List</a>

            <?php elseif ($success): ?>
                <div class="alert alert--success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
                <a href="../DepartmentsPage.php" class="btn btn--secondary">Back to Department List</a>

            <?php else: ?>
                <div class="alert alert--warning">Are you sure you want to delete this department entry?<br><br>This entry is part of a high-level relationship in the database. Deleting this entry may affect related data.</div>

                <div class="form-container">
                    <div class="dept-details">
                        <p><strong>Department ID:</strong> <?php echo htmlspecialchars($dept['deptid'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>Department Full Name:</strong> <?php echo htmlspecialchars($dept['deptfullname'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>Department Short Name:</strong> <?php echo htmlspecialchars($dept['deptshortname'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>

                    <form method="POST" action="?deptid=<?php echo urlencode($dept['deptid']); ?>" class="dept-form" novalidate>
                        <input type="hidden" name="deptid" value="<?php echo htmlspecialchars($dept['deptid'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="form-actions">
                            <button type="submit" class="btn btn--danger">Confirm Delete</button>
                            <a href="../DepartmentsPage.php?selected_school=<?php echo urlencode($dept['deptcollid']); ?>" class="btn btn--secondary">Cancel</a>
                        </div>
                    </form>
                </div>

            <?php endif; ?>
        </main>
    </div>

</body>

</html>