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

    $errors = ['progfullname' => '', 'progshortname' => ''];
    $values = ['progid' => '', 'progfullname' => '', 'progshortname' => '', 'progcolldeptid' => '', 'deptfullname' => '', 'collfullname' => ''];

    if (!$progid)
    {
        $pageError = 'Invalid program identifier.';
    }
    else
    {
        try
        {
            // Load program details for editing
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
            else
            {
                // Populate form values with existing program data
                $values = [
                    'progid'        => $prog['progid'],
                    'progfullname'  => $prog['progfullname'],
                    'progshortname' => $prog['progshortname'],
                    'progcolldeptid'=> $prog['progcolldeptid'],
                    'deptfullname'  => $prog['deptfullname'],
                    'collfullname'  => $prog['collfullname'],
                ];
            }
        }
        catch (PDOException $e)
        {
            $pageError = 'Error loading program: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }

    // Handle form submission for updates
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$pageError)
    {
        $values['progfullname']  = trim($_POST['progfullname']  ?? '');
        $values['progshortname'] = trim($_POST['progshortname'] ?? '');

        // Program Full Name validation
        if ($values['progfullname'] === '')
        {
            $errors['progfullname'] = 'Program Full Name cannot be empty';
        }
        elseif (!preg_match("/^[a-zA-Z\s]*$/", $values['progfullname'])) 
        {
            $errors['progfullname'] = 'Program Full Name must contain letters and spaces only';
        }

        // Program Short Name validation
        if ($values['progshortname'] === '')
        {
            $errors['progshortname'] = 'Program Short Name cannot be empty';
        }
        elseif (!preg_match("/^[a-zA-Z-]*$/", $values['progshortname'])) 
        {
            $errors['progshortname'] = 'Program Short Name must contain letters and hyphens only';
        }

        // Check duplicate full name (excluding current record)
        if ($errors['progfullname'] === '')
        {
            try
            {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM programs WHERE progfullname = ? AND progid != ?");
                $stmt->execute([$values['progfullname'], $values['progid']]);

                // If a duplicate is found, set an error message
                if ($stmt->fetchColumn() > 0)
                {
                    $errors['progfullname'] = 'That Program Full Name already exists. Please use a different name.';
                }
            }
            catch (PDOException $e)
            {
                $pageError = 'Error checking for duplicates: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            }
        }

        if (!$pageError && !array_filter($errors))
        {
            try
            {
                // Update program details in the database
                $stmt = $pdo->prepare("UPDATE programs SET progfullname = ?, progshortname = ? WHERE progid = ?");
                $stmt->execute([
                    $values['progfullname'],
                    $values['progshortname'],
                    $values['progid'],
                ]);

                $success = $stmt->rowCount() > 0 ? 'Program updated successfully.' : 'No changes were made.';
            }
            catch (PDOException $e)
            {
                $pageError = 'Error updating program: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
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
    <title>Update Program - USJ-R School Management System</title>
    <link rel="stylesheet" href="./ProgramUpdatePage.css">
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
                <?php endif; ?>              </ul>
        </nav>

        <main class="content">
            <section class="page-header">
                <h2>Update Program</h2>
            </section>

            <?php if ($pageError): ?>
                <div class="alert alert--error"><?php echo htmlspecialchars($pageError, ENT_QUOTES, 'UTF-8'); ?></div>
                <a href="../ProgramsPage.php" class="btn btn--secondary">Back to Program List</a>

            <?php else: ?>
                <?php if ($success): ?>
                    <div class="alert alert--success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <div class="form-container">
                    <form method="POST" action="?progid=<?php echo urlencode($values['progid']); ?>" class="program-form" novalidate>

                        <div class="form-group">
                            <label for="progid">Program ID:</label>
                            <input type="text" id="progid" value="<?php echo htmlspecialchars($values['progid'], ENT_QUOTES, 'UTF-8'); ?>" disabled>
                            <input type="hidden" name="progid" value="<?php echo htmlspecialchars($values['progid'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="progfullname">Program Full Name:</label>
                            <input type="text" id="progfullname" name="progfullname"
                                   value="<?php echo htmlspecialchars($values['progfullname'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo field_error($errors['progfullname']); ?>
                        </div>

                        <div class="form-group">
                            <label for="progshortname">Program Short Name:</label>
                            <input type="text" id="progshortname" name="progshortname"
                                   value="<?php echo htmlspecialchars($values['progshortname'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo field_error($errors['progshortname']); ?>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn--primary">Update Program Entry</button>
                            <a href="ProgramUpdatePage.php?progid=<?php echo urlencode($values['progid']); ?>" class="btn btn--secondary">Reset Form</a>
                            <a href="../ProgramsPage.php?selected_dept=<?php echo urlencode($values['progcolldeptid']); ?>" class="btn btn--danger">Exit</a>
                        </div>

                    </form>
                </div>
            <?php endif; ?>
        </main>
    </div>

</body>

</html>