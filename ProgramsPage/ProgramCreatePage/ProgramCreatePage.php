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

    // Get deptid from URL
    $deptid = filter_input(INPUT_GET, 'deptid', FILTER_VALIDATE_INT);

    // If deptid is not valid, redirect back to ProgramsPage
    if (!$deptid)
    {
        header("Location: ../ProgramsPage.php");
        exit();
    }

    // Fetch department and school info for display
    $deptName = '';
    $collid   = '';

    try
    {
        $stmt = $pdo->prepare("SELECT d.deptid, d.deptfullname, d.deptcollid, c.collfullname
                               FROM departments d
                               JOIN colleges c ON c.collid = d.deptcollid
                               WHERE d.deptid = ?");
        $stmt->execute([$deptid]);
        $dept = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dept)
        {
            header("Location: ../ProgramsPage.php");
            exit();
        }

        $deptName = $dept['deptfullname'];
        $collid   = $dept['deptcollid'];
    }
    catch (PDOException $e)
    {
        header("Location: ../ProgramsPage.php");
        exit();
    }

    $success = '';
    $errors  = ['progid' => '', 'progfullname' => '', 'progshortname' => ''];
    $values  = ['progid' => '', 'progfullname' => '', 'progshortname' => ''];

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST')
    {
        $values['progid']        = htmlspecialchars(trim($_POST['progid']        ?? ''), ENT_QUOTES, 'UTF-8');
        $values['progfullname']  = htmlspecialchars(trim($_POST['progfullname']  ?? ''), ENT_QUOTES, 'UTF-8');
        $values['progshortname'] = htmlspecialchars(trim($_POST['progshortname'] ?? ''), ENT_QUOTES, 'UTF-8');


        // Validate Program ID
        $deptidStr      = (string)$deptid;
        $expectedLength = strlen($deptidStr) + 3;
        $expectedFormat = $deptidStr . '[0-9]{3}';

        if ($values['progid'] === '')
        {
            $errors['progid'] = 'Program ID cannot be empty';
        }
        elseif (!ctype_digit($values['progid']))
        {
            $errors['progid'] = 'Program ID must contain numbers only';
        }
        elseif (strlen($values['progid']) !== $expectedLength)
        {
            $errors['progid'] = 'Program ID must be ' . $expectedLength . ' digits: ' . $deptidStr . ' followed by exactly 3 digits (e.g. ' . $deptidStr . '001)';
        }
        elseif (!preg_match('/^' . $expectedFormat . '$/', $values['progid']))
        {
            $errors['progid'] = 'Program ID must start with ' . $deptidStr . ' followed by exactly 3 digits (e.g. ' . $deptidStr . '001)';
        }

        // Validate Program Full Name
        if ($values['progfullname'] === '')
        {
            $errors['progfullname'] = 'Program Full Name cannot be empty';
        }
        elseif (!preg_match("/^[a-zA-Z\s]*$/", $values['progfullname']))
        {
            $errors['progfullname'] = 'Program Full Name must contain letters and spaces only';
        }

        // Validate Program Short Name
        if ($values['progshortname'] === '')
        {
            $errors['progshortname'] = 'Program Short Name cannot be empty';
        }
        elseif ($values['progshortname'] !== '' && !preg_match("/^[a-zA-Z-]*$/", $values['progshortname']))
        {
            $errors['progshortname'] = 'Program Short Name must contain letters and hyphens only';
        }

        if (!array_filter($errors))
        {
            try
            {
                // Check if progid already exists
                $stmt = $pdo->prepare("SELECT progid FROM programs WHERE progid = ?");
                $stmt->execute([$values['progid']]);

                if ($stmt->fetch())
                {
                    $errors['progid'] = 'Program ID already exists';
                }
                else
                {
                    // Check duplicate full name
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM programs WHERE progfullname = ?");
                    $stmt->execute([$values['progfullname']]);

                    if ($stmt->fetchColumn() > 0)
                    {
                        $errors['progfullname'] = 'That Program Full Name already exists. Please use a different name.';
                    }
                    else
                    {
                        // Insert new program
                        $stmt = $pdo->prepare("INSERT INTO programs (progid, progfullname, progshortname, progcollid, progcolldeptid)
                                               VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $values['progid'],
                            $values['progfullname'],
                            $values['progshortname'],
                            $collid,
                            $deptid,
                        ]);

                        $success = 'Program created successfully!';
                        $values  = ['progid' => '', 'progfullname' => '', 'progshortname' => ''];
                    }
                }
            }
            catch (PDOException $e)
            {
                $errors['progid'] = 'Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
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
    <title>Create Program - USJ-R School Management System</title>
    <link rel="stylesheet" href="./ProgramCreatePage.css">
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
                <h2>Create Program</h2>
                <p>Required ID format: <?php echo htmlspecialchars($deptid, ENT_QUOTES, 'UTF-8'); ?>### </p>
            </section>

            <?php if ($success !== ''): ?>
                <div class="alert alert--success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" action="" class="program-form" novalidate>

                    <div class="form-group">
                        <label for="progid">Program ID:</label>
                        <div class="input-wrapper">
                            <input type="text" id="progid" name="progid"
                                   class="form-input <?php echo $errors['progid'] !== '' ? 'form-input--error' : ''; ?>"
                                   value="<?php echo $values['progid']; ?>">
                            <?php echo field_error($errors['progid']); ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="progfullname">Program Full Name:</label>
                        <div class="input-wrapper">
                            <input type="text" id="progfullname" name="progfullname"
                                   class="form-input <?php echo $errors['progfullname'] !== '' ? 'form-input--error' : ''; ?>"
                                   value="<?php echo $values['progfullname']; ?>">
                            <?php echo field_error($errors['progfullname']); ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="progshortname">Program Short Name:</label>
                        <div class="input-wrapper">
                            <input type="text" id="progshortname" name="progshortname"
                                   class="form-input <?php echo $errors['progshortname'] !== '' ? 'form-input--error' : ''; ?>"
                                   value="<?php echo $values['progshortname']; ?>">
                            <?php echo field_error($errors['progshortname']); ?>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn--primary">Save New Program Entry</button>
                        <button type="reset" class="btn btn--secondary">Reset Form</button>
                        <a href="../ProgramsPage.php?selected_dept=<?php echo urlencode($deptid); ?>" class="btn btn--danger">Exit</a>
                    </div>

                </form>
            </div>

        </main>
    </div>

</body>

</html>