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

    // Get school ID from query parameter
    $collid    = filter_input(INPUT_GET, 'collid', FILTER_VALIDATE_INT);
    $success   = '';
    $pageError = '';
    $school    = null;

    // Validate school ID and load school data
    if (!$collid) 
    {
        $pageError = 'Invalid school identifier.';
    } 
    else 
    {
        try 
        {
            $stmt = $pdo->prepare("SELECT collid, collfullname, collshortname FROM colleges WHERE collid = ?");
            $stmt->execute([$collid]);
            $school = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$school) 
            {
                $pageError = 'School record not found.';
            }
        } 
        catch (PDOException $e) 
        {
            $pageError = 'Error loading school: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }

    // Handle form submission for deletion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$pageError) 
    {
        $postId = filter_input(INPUT_POST, 'collid', FILTER_VALIDATE_INT);

        if (!$postId) 
        {
            $pageError = 'Invalid school identifier.';
        } 
        else 
        {
            try 
            {
                // Check if school has departments linked to it
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM departments WHERE deptcollid = ?");
                $stmt->execute([$postId]);
                $deptCount = $stmt->fetchColumn();
                if ($deptCount > 0)
                {
                    $pageError = 'Cannot delete school. It still has ' . $deptCount . ' department(s) linked to it. Delete the departments first.';
                }
                else
                {
                    $stmt = $pdo->prepare("DELETE FROM colleges WHERE collid = ?");
                    $stmt->execute([$postId]);

                    if ($stmt->rowCount() > 0) 
                    {
                        $success = 'School deleted successfully.';
                        $school  = null;
                    } 
                    else 
                    {
                        $pageError = 'School not found or already deleted.';
                    }
                }
            } 
            catch (PDOException $e)
            {
                $pageError = 'Error deleting school: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            }
        }
    }

    // Helper function to display field error messages
    function field_error(string $msg): string
    {
        if ($msg === '') return '<span class="field-error"></span>';
        return '<span class="field-error">' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</span>';
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete School - USJ-R School Management System</title>
    <link rel="stylesheet" href="./SchoolDeletePage.css">
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
                <li class="schoolssidebar"><a href="../SchoolsPage.php">Schools</a></li>
                <li><a href="../../DepartmentsPage/DepartmentsPage.php">Departments</a></li>
                <li><a href="../../ProgramsPage/ProgramsPage.php">Programs</a></li>
                <li><a href="../../StudentsPage/StudentsPage.php">Students</a></li>
                <?php if ($isAdmin): ?>
                    <li><a href="../../UsersPage/UsersPage.php">Users</a></li>
                <?php endif; ?>            
            </ul>
        </nav>

        <main class="content">
            <section class="page-header">
                <h2>Delete School</h2>
            </section>

            <?php if ($pageError): ?>
                <div class="alert alert--error"><?php echo htmlspecialchars($pageError, ENT_QUOTES, 'UTF-8'); ?></div>
                <a href="../SchoolsPage.php" class="btn btn--secondary">Back to School List</a>

            <?php elseif ($success): ?>
                <div class="alert alert--success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
                <a href="../SchoolsPage.php" class="btn btn--secondary">Back to School List</a>

            <?php else: ?>
                <div class="alert alert--warning">Are you sure you want to delete this school entry?<br><br>This entry is part of a high-level relationship in the database. Deleting this entry may affect related data.</div>

                <div class="form-container">
                    <div class="school-details">
                        <p><strong>School ID:</strong> <?php echo htmlspecialchars($school['collid'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>School Full Name:</strong> <?php echo htmlspecialchars($school['collfullname'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>School Short Name:</strong> <?php echo htmlspecialchars($school['collshortname'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>

                    <form method="POST" action="?collid=<?php echo urlencode($school['collid']); ?>" class="school-form" novalidate>
                        <input type="hidden" name="collid" value="<?php echo htmlspecialchars($school['collid'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="form-actions">
                            <button type="submit" class="btn btn--danger">Confirm Delete</button>
                            <a href="../SchoolsPage.php" class="btn btn--secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>

</html>