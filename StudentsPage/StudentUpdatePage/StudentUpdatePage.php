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

    $errors = ['studfirstname' => '', 'studmidname' => '', 'studlastname' => '', 'studyear' => ''];
    $values = ['studid' => '', 'studfirstname' => '', 'studmidname' => '', 'studlastname' => '', 'studyear' => '', 'progid' => '', 'progfullname' => ''];

    if (!$studid)
    {
        $pageError = 'Invalid student identifier.';
    }
    else
    {
        try
        {
            // Load student details for form population
            $stmt = $pdo->prepare("SELECT s.studid, s.studfirstname, s.studmidname, s.studlastname,
                                          s.studyear, s.studprogid,
                                          p.progfullname
                                   FROM students s
                                   JOIN programs p ON p.progid = s.studprogid
                                   WHERE s.studid = ?");
            $stmt->execute([$studid]);
            $stud = $stmt->fetch(PDO::FETCH_ASSOC);

            // If student not found, show error
            if (!$stud)
            {
                $pageError = 'Student record not found.';
            }
            else
            {
                // Populate form values
                $values = [
                    'studid'        => $stud['studid'],
                    'studfirstname' => $stud['studfirstname'],
                    'studmidname'   => $stud['studmidname'] ?? '',
                    'studlastname'  => $stud['studlastname'],
                    'studyear'      => $stud['studyear'],
                    'progid'        => $stud['studprogid'],
                    'progfullname'  => $stud['progfullname'],
                ];
            }
        }
        catch (PDOException $e)
        {
            $pageError = 'Error loading student: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$pageError)
    {
        $values['studfirstname'] = trim($_POST['studfirstname'] ?? '');
        $values['studmidname']   = trim($_POST['studmidname']   ?? '');
        $values['studlastname']  = trim($_POST['studlastname']  ?? '');
        $values['studyear']      = trim($_POST['studyear']      ?? '');

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

        // If no validation errors, proceed to update
        if (!array_filter($errors))
        {
            try
            {
                // Perform update
                $stmt = $pdo->prepare("UPDATE students
                                       SET studfirstname = ?, studmidname = ?, studlastname = ?, studyear = ?
                                       WHERE studid = ?");
                $stmt->execute([
                    $values['studfirstname'],
                    $values['studmidname'] !== '' ? $values['studmidname'] : null,
                    $values['studlastname'],
                    $values['studyear'],
                    $values['studid'],
                ]);

                $success = $stmt->rowCount() > 0 ? 'Student updated successfully.' : 'No changes were made.';
            }
            catch (PDOException $e)
            {
                $pageError = 'Error updating student: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
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
    <title>Update Student - USJ-R School Management System</title>
    <link rel="stylesheet" href="./StudentUpdatePage.css">
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
                <h2>Student Update</h2>
            </section>

            <?php if ($pageError): ?>
                <div class="alert alert--error"><?php echo htmlspecialchars($pageError, ENT_QUOTES, 'UTF-8'); ?></div>
                <a href="../StudentsPage.php" class="btn btn--secondary">Back to Student List</a>

            <?php else: ?>
                <?php if ($success !== ''): ?>
                    <div class="alert alert--success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <div class="form-container">
                    <form method="POST" action="?studid=<?php echo urlencode($values['studid']); ?>" class="student-form" novalidate>

                        <div class="form-group">
                            <label for="studid">Student ID:</label>
                                <input type="text" id="studid" class="form-input"
                                    value="<?php echo htmlspecialchars($values['studid'], ENT_QUOTES, 'UTF-8'); ?>" disabled>
                                <input type="hidden" name="studid" value="<?php echo htmlspecialchars($values['studid'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="studfirstname">Student First Name:</label>
                                <input type="text" id="studfirstname" name="studfirstname" class="form-input"
                                    value="<?php echo htmlspecialchars($values['studfirstname'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo field_error($errors['studfirstname']); ?>
                        </div>

                        <div class="form-group">
                            <label for="studmidname">Student Middle Name:</label>
                                <input type="text" id="studmidname" name="studmidname" class="form-input"
                                    value="<?php echo htmlspecialchars($values['studmidname'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo field_error($errors['studmidname']); ?>
                        </div>

                        <div class="form-group">
                            <label for="studlastname">Student Last Name:</label>
                                <input type="text" id="studlastname" name="studlastname" class="form-input"
                                    value="<?php echo htmlspecialchars($values['studlastname'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo field_error($errors['studlastname']); ?>
                        </div>

                        <div class="form-group">
                            <label for="studyear">Student Year:</label>
                                <input type="text" id="studyear" name="studyear" class="form-input"
                                    value="<?php echo htmlspecialchars($values['studyear'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo field_error($errors['studyear']); ?>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn--primary">Update Student Entry</button>
                            <a href="StudentUpdatePage.php?studid=<?php echo urlencode($values['studid']); ?>" class="btn btn--secondary">Reset Form</a>
                            <a href="../StudentsPage.php?selected_prog=<?php echo urlencode($values['progid']); ?>" class="btn btn--danger">Exit</a>
                        </div>

                    </form>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>