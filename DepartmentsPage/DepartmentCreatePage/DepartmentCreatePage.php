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

    // Get collid from URL
    $collid = filter_input(INPUT_GET, 'collid', FILTER_VALIDATE_INT);

    if (!$collid)
    {
        header("Location: ../DepartmentsPage.php");
        exit();
    }

    // Fetch school name for display
    $schoolName = '';
    try
    {
        $stmt = $pdo->prepare("SELECT collfullname FROM colleges WHERE collid = ?");
        $stmt->execute([$collid]);
        $school = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$school)
        {
            header("Location: ../DepartmentsPage.php");
            exit();
        }

        $schoolName = $school['collfullname'];
    }
    catch (PDOException $e)
    {
        header("Location: ../DepartmentsPage.php");
        exit();
    }

    $success = '';
    $errors  = ['deptid' => '', 'deptfullname' => '', 'deptshortname' => ''];
    $values  = ['deptid' => '', 'deptfullname' => '', 'deptshortname' => ''];

    if ($_SERVER['REQUEST_METHOD'] === 'POST')
    {
        $values['deptid']        = htmlspecialchars(trim($_POST['deptid']        ?? ''), ENT_QUOTES, 'UTF-8');
        $values['deptfullname']  = htmlspecialchars(trim($_POST['deptfullname']  ?? ''), ENT_QUOTES, 'UTF-8');
        $values['deptshortname'] = htmlspecialchars(trim($_POST['deptshortname'] ?? ''), ENT_QUOTES, 'UTF-8');


        // Expected format for deptid
        $collidStr      = (string)$collid;
        $expectedLength = strlen($collidStr) + 3;
        $expectedFormat = $collidStr . '[0-9]{3}';

        // Validate Department ID
        if ($values['deptid'] === '')
        {
            $errors['deptid'] = 'Department ID cannot be empty';
        }
        elseif (!ctype_digit($values['deptid']))
        {
            $errors['deptid'] = 'Department ID must contain numbers only';
        }
        elseif (strlen($values['deptid']) !== $expectedLength)
        {
            $errors['deptid'] = 'Department ID must be ' . $expectedLength . ' digits: ' . $collidStr . ' followed by exactly 3 digits (e.g. ' . $collidStr . '001)';
        }
        elseif (!preg_match('/^' . $expectedFormat . '$/', $values['deptid']))
        {
            $errors['deptid'] = 'Department ID must start with ' . $collidStr . ' followed by exactly 3 digits (e.g. ' . $collidStr . '001)';
        }

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

        // Insert into database
        if (!array_filter($errors))
        {
            try
            {
                // Check for duplicate deptid
                $stmt = $pdo->prepare("SELECT deptid FROM departments WHERE deptid = ?");
                $stmt->execute([$values['deptid']]);
                if ($stmt->fetch())
                {
                    $errors['deptid'] = 'Department ID already exists';
                }
                else
                {
                    $stmt = $pdo->prepare("INSERT INTO departments (deptid, deptfullname, deptshortname, deptcollid) VALUES (?, ?, ?, ?)");
                    $stmt->execute([
                        $values['deptid'],
                        $values['deptfullname'],
                        $values['deptshortname'],
                        $collid
                    ]);

                    $success = 'Department created successfully!';
                    $values  = ['deptid' => '', 'deptfullname' => '', 'deptshortname' => ''];
                }
            }
            catch (PDOException $e)
            {
                $errors['deptid'] = 'Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
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
    <title>USJ-R School Management System</title>
    <link rel="stylesheet" href="./DepartmentCreatePage.css">
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
                <h2>Create Department</h2>
                <p>Required ID format: <?php echo htmlspecialchars($collid, ENT_QUOTES, 'UTF-8'); ?>### </p>
            </section>

            <?php if ($success !== ''): ?>
                <div class="alert alert--success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="form-card">
                <form method="POST" class="department-form" action="">
                    <div class="form-group">
                        <label for="deptid">Department ID:</label>
                        <div class="input-wrapper">
                            <input
                                type="text"
                                id="deptid"
                                name="deptid"
                                class="form-input"
                                value="<?php echo $values['deptid']; ?>"
                            >
                            <?php echo field_error($errors['deptid']); ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="deptfullname">Department Full Name:</label>
                        <div class="input-wrapper">
                            <input
                                type="text"
                                id="deptfullname"
                                name="deptfullname"
                                class="form-input"
                                value="<?php echo $values['deptfullname']; ?>"
                            >
                            <?php echo field_error($errors['deptfullname']); ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="deptshortname">Department Short Name:</label>
                        <div class="input-wrapper">
                            <input
                                type="text"
                                id="deptshortname"
                                name="deptshortname"
                                class="form-input"
                                value="<?php echo $values['deptshortname']; ?>"
                            >
                            <?php echo field_error($errors['deptshortname']); ?>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn--primary">Save New Department Entry</button>
                        <button type="reset" class="btn btn--secondary">Reset Form</button>
                        <a href="../DepartmentsPage.php?selected_school=<?php echo urlencode($collid); ?>" class="btn btn--danger">Exit</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>