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

    // Get program ID from URL
    $progid = filter_input(INPUT_GET, 'progid', FILTER_VALIDATE_INT);

    if (!$progid)
    {
        header("Location: ../StudentsPage.php");
        exit();
    }

    $progName = '';
    $deptName = '';
    $deptid   = '';
    $collid   = '';

    try
    {
        $stmt = $pdo->prepare("SELECT p.progid, p.progfullname, p.progcolldeptid,
                                      d.deptfullname, d.deptcollid,
                                      c.collfullname
                               FROM programs p
                               JOIN departments d ON d.deptid = p.progcolldeptid
                               JOIN colleges c ON c.collid = d.deptcollid
                               WHERE p.progid = ?");
        $stmt->execute([$progid]);
        $prog = $stmt->fetch(PDO::FETCH_ASSOC);

        // If program not found, redirect back to students page
        if (!$prog)
        {
            header("Location: ../StudentsPage.php");
            exit();
        }

        // Extract program, department, and college info for use in the form and redirection
        $progName = $prog['progfullname'];
        $deptName = $prog['deptfullname'];
        $deptid   = $prog['progcolldeptid'];
        $collid   = $prog['deptcollid'];
    }
    catch (PDOException $e)
    {
        header("Location: ../StudentsPage.php");
        exit();
    }

    $success = '';
    $errors  = ['studid' => '', 'studfirstname' => '', 'studmidname' => '', 'studlastname' => '', 'studyear' => ''];
    $values  = ['studid' => '', 'studfirstname' => '', 'studmidname' => '', 'studlastname' => '', 'studyear' => ''];

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST')
    {
        $values['studid']        = trim($_POST['studid']        ?? '');
        $values['studfirstname'] = trim($_POST['studfirstname'] ?? '');
        $values['studmidname']   = trim($_POST['studmidname']   ?? '');
        $values['studlastname']  = trim($_POST['studlastname']  ?? '');
        $values['studyear']      = trim($_POST['studyear']      ?? '');

        // Validate Student ID
        if ($values['studid'] === '')
        {
            $errors['studid'] = 'Student ID cannot be empty';
        }
        elseif (!ctype_digit($values['studid']))
        {
            $errors['studid'] = 'Student ID must contain numbers only';
        }

        // Validate First Name
        if ($values['studfirstname'] === '')
        {
            $errors['studfirstname'] = 'Student First Name cannot be empty';
        }
        elseif (!preg_match("/^[a-zA-Z\s]*$/", $values['studfirstname']))
        {
            $errors['studfirstname'] = 'Only letters allowed';
        }

        // Validate Middle Name
        if ($values['studmidname'] !== '' && !preg_match("/^[a-zA-Z\s]*$/", $values['studmidname']))
        {
            $errors['studmidname'] = 'Only letters allowed';
        }

        // Validate Last Name
        if ($values['studlastname'] === '')
        {
            $errors['studlastname'] = 'Student Last Name cannot be empty';
        }
        elseif (!preg_match("/^[a-zA-Z\s]*$/", $values['studlastname']))
        {
            $errors['studlastname'] = 'Only letters allowed';
        }

        // Validate Year
        if ($values['studyear'] === '')
        {
            $errors['studyear'] = 'Student Year cannot be empty';
        }
        elseif (!ctype_digit($values['studyear']) || (int)$values['studyear'] < 1 || (int)$values['studyear'] > 4)
        {
            $errors['studyear'] = 'Student Year must be a number between 1 and 4';
        }

        // If no validation errors, proceed to insert the new student record
        if (!array_filter($errors))
        {
            try
            {
                // Check if Student ID already exists
                $stmt = $pdo->prepare("SELECT studid FROM students WHERE studid = ?");
                $stmt->execute([$values['studid']]);

                if ($stmt->fetch())
                {
                    $errors['studid'] = 'Student ID already exists';
                }
                else
                {
                    // Insert new student record
                    $stmt = $pdo->prepare("INSERT INTO students (studid, studfirstname, studmidname, studlastname, studcollid, studcolldeptid, studprogid, studyear)
                                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $values['studid'],
                        $values['studfirstname'],
                        $values['studmidname'] !== '' ? $values['studmidname'] : null,
                        $values['studlastname'],
                        $collid,
                        $deptid,
                        $progid,
                        $values['studyear'],
                    ]);

                    $success = 'Student created successfully!';
                    $values  = ['studid' => '', 'studfirstname' => '', 'studmidname' => '', 'studlastname' => '', 'studyear' => ''];
                }
            }
            catch (PDOException $e)
            {
                $errors['studid'] = 'Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
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
    <title>Create Student - USJ-R School Management System</title>
    <link rel="stylesheet" href="./StudentCreatePage.css">
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
                <h2>Create Student</h2>
            </section>

            <?php if ($success !== ''): ?>
                <div class="alert alert--success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" action="" class="student-form" novalidate>

                    <div class="form-group">
                        <label for="studid">Student ID:</label>
                        <div class="input-wrapper">
                            <input type="text" id="studid" name="studid" class="form-input"
                                value="<?php echo htmlspecialchars($values['studid'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo field_error($errors['studid']); ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="studfirstname">Student First Name:</label>
                        <div class="input-wrapper">
                            <input type="text" id="studfirstname" name="studfirstname" class="form-input"
                                value="<?php echo htmlspecialchars($values['studfirstname'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo field_error($errors['studfirstname']); ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="studmidname">Student Middle Name:</label>
                        <div class="input-wrapper">
                            <input type="text" id="studmidname" name="studmidname" class="form-input"
                                value="<?php echo htmlspecialchars($values['studmidname'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo field_error($errors['studmidname']); ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="studlastname">Student Last Name:</label>
                        <div class="input-wrapper">
                            <input type="text" id="studlastname" name="studlastname" class="form-input"
                                value="<?php echo htmlspecialchars($values['studlastname'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo field_error($errors['studlastname']); ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="studyear">Student Year:</label>
                        <div class="input-wrapper">
                            <input type="text" id="studyear" name="studyear" class="form-input"
                                value="<?php echo htmlspecialchars($values['studyear'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo field_error($errors['studyear']); ?>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn--primary">Save New Student Entry</button>
                        <button type="reset" class="btn btn--secondary">Reset Form</button>
                        <a href="../StudentsPage.php?selected_prog=<?php echo urlencode($progid); ?>" class="btn btn--danger">Exit</a>
                    </div>

                </form>
            </div>
        </main>
    </div>
</body>
</html>