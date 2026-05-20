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

    $errors = ['deptfullname' => '', 'deptshortname' => ''];
    $values = ['deptid' => '', 'deptfullname' => '', 'deptshortname' => '', 'deptcollid' => '', 'collfullname' => ''];

    // Validate deptid
    if (!$deptid)
    {
        $pageError = 'Invalid department identifier.';
    }
    else
    {
        // Load department details for confirmation display
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
            else
            {
                $values = [
                    'deptid'       => $dept['deptid'],
                    'deptfullname' => $dept['deptfullname'],
                    'deptshortname'=> $dept['deptshortname'],
                    'deptcollid'   => $dept['deptcollid'],
                    'collfullname' => $dept['collfullname'],
                ];
            }
        }
        catch (PDOException $e)
        {
            $pageError = 'Error loading department: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$pageError)
    {
        $values['deptfullname']  = trim($_POST['deptfullname']  ?? '');
        $values['deptshortname'] = trim($_POST['deptshortname'] ?? '');

        // Validate Department Full Name
        if ($values['deptfullname'] === '')
        {
            $errors['deptfullname'] = 'Department Full Name cannot be empty';
        }
        elseif (!preg_match("/^[a-zA-Z\s]*$/", $values['deptfullname'])) 
        {
            $errors['deptfullname'] = 'Department Full Name must contain letters and spaces only';
        }

        // Validate Department Short Name
        if ($values['deptshortname'] !== '' && !preg_match("/^[a-zA-Z-]*$/", $values['deptshortname'])) 
        {
            $errors['deptshortname'] = 'Department Short Name must contain letters and hyphens only';
        }

        // Check duplicate full name
        if ($errors['deptfullname'] === '')
        {            
            try
            {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM departments WHERE deptfullname = ? AND deptid != ?");
                $stmt->execute([$values['deptfullname'], $values['deptid']]);

                if ($stmt->fetchColumn() > 0)
                {
                    $errors['deptfullname'] = 'That Department Full Name already exists. Please use a different name.';
                }
            }
            catch (PDOException $e)
            {
                $pageError = 'Error checking for duplicates: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            }
        }

        // If no errors, proceed to update the record
        if (!$pageError && !array_filter($errors))
        {
            try
            {
                $stmt = $pdo->prepare("UPDATE departments SET deptfullname = ?, deptshortname = ? WHERE deptid = ?");
                $stmt->execute([
                    $values['deptfullname'],
                    $values['deptshortname'],
                    $values['deptid'],
                ]);

                // Check if any row was actually updated
                $success = $stmt->rowCount() > 0 ? 'Department updated successfully.' : 'No changes were made.';
            }
            catch (PDOException $e)
            {
                $pageError = 'Error updating department: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            }
        }
    }

    // Helper function to display field errors
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
    <title>Update Department - USJ-R School Management System</title>
    <link rel="stylesheet" href="./DepartmentUpdatePage.css">
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
                <h2>Update Department</h2>
            </section>

            <?php if ($pageError): ?>
                <div class="alert alert--error"><?php echo htmlspecialchars($pageError, ENT_QUOTES, 'UTF-8'); ?></div>
                <a href="../DepartmentsPage.php" class="btn btn--secondary">Back to Department List</a>

            <?php else: ?>
                <?php if ($success): ?>
                    <div class="alert alert--success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <div class="form-container">
                    <form method="POST" action="?deptid=<?php echo urlencode($values['deptid']); ?>" class="department-form" novalidate>

                        <div class="form-group">
                            <label for="deptid">Department ID:</label>
                            <input type="text" id="deptid" value="<?php echo htmlspecialchars($values['deptid'], ENT_QUOTES, 'UTF-8'); ?>" disabled>
                            <input type="hidden" name="deptid" value="<?php echo htmlspecialchars($values['deptid'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="deptfullname">Department Full Name:</label>
                            <input type="text" id="deptfullname" name="deptfullname"
                                   value="<?php echo htmlspecialchars($values['deptfullname'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo field_error($errors['deptfullname']); ?>
                        </div>

                        <div class="form-group">
                            <label for="deptshortname">Department Short Name:</label>
                            <input type="text" id="deptshortname" name="deptshortname"
                                   value="<?php echo htmlspecialchars($values['deptshortname'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo field_error($errors['deptshortname']); ?>
                        </div>  

                        <div class="form-actions">
                            <button type="submit" class="btn btn--primary">Update Department Entry</button>
                            <a href="DepartmentUpdatePage.php?deptid=<?php echo urlencode($values['deptid']); ?>" class="btn btn--secondary">Reset Form</a>
                            <a href="../DepartmentsPage.php?selected_school=<?php echo urlencode($values['deptcollid']); ?>" class="btn btn--danger">Exit</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>