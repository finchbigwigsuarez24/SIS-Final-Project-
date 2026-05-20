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

    $success = "";
    $errors  = ['collid' => '', 'collfullname' => '', 'collshortname' => ''];
    $values  = ['collid' => '', 'collfullname' => '', 'collshortname' => ''];

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") 
    {
        // Store input values
        $values['collid']        = trim($_POST['collid'] ?? '');
        $values['collfullname']  = trim($_POST['collfullname'] ?? '');
        $values['collshortname'] = trim($_POST['collshortname'] ?? '');

        // Validate School ID
        if ($values['collid'] === '') 
        {
            $errors['collid'] = "School ID entry cannot be empty";
        } 
        elseif (!ctype_digit($values['collid'])) 
        {
            $errors['collid'] = "School ID must be a number";
        }

        // Validate School Full Name
        if ($values['collfullname'] === '') 
        {
            $errors['collfullname'] = "School Full Name entry cannot be empty";
        }
        elseif (!preg_match("/^[a-zA-Z\s]*$/", $values['collfullname'])) 
        {
            $errors['collfullname'] = "School Full Name must contain letters and spaces only";
        }

        // Validate School Short Name
        if ($values['collshortname'] === '') 
        {
            $errors['collshortname'] = "School Short Name entry cannot be empty";
        }
        elseif (!preg_match("/^[a-zA-Z-]*$/", $values['collshortname'])) 
        {
            $errors['collshortname'] = "School Short Name must contain letters and hyphens only";
        }

        // If no errors, proceed to database check
        if (!array_filter($errors)) 
        {
            try 
            {
                // Check for duplicate collid
                $stmt = $pdo->prepare("SELECT collid FROM colleges WHERE collid = ?");
                $stmt->execute([$values['collid']]);
                if ($stmt->fetch())
                {
                    $errors['collid'] = "School ID already exists.";
                } 
                else 
                {
                    // Insert new school record
                    $stmt = $pdo->prepare("INSERT INTO colleges (collid, collfullname, collshortname) VALUES (?, ?, ?)");
                    $stmt->execute([$values['collid'], $values['collfullname'], $values['collshortname']]);

                    $success = "School created successfully!";

                    // Clear values on success
                    $values  = ['collid' => '', 'collfullname' => '', 'collshortname' => ''];
                }
            } 
            catch (PDOException $e) 
            {
                $errors['collid'] = "Error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
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
    <title>Create School - USJ-R School Management System</title>
    <link rel="stylesheet" href="./SchoolCreatePage.css">
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
                <h2>Create School</h2>
            </section>

            <?php if (!empty($success)): ?>
                <div class="alert alert--success">
                    <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" action="" class="school-form" novalidate>

                    <div class="form-group">
                        <label for="collid">School ID:</label>
                        <div class="input-wrapper">
                            <input type="text" id="collid" name="collid" class="form-input"
                                value="<?php echo htmlspecialchars($values['collid'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo field_error($errors['collid']); ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="collfullname">School Full Name:</label>
                        <div class="input-wrapper">
                            <input type="text" id="collfullname" name="collfullname" class="form-input"
                                value="<?php echo htmlspecialchars($values['collfullname'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo field_error($errors['collfullname']); ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="collshortname">School Short Name:</label>
                        <div class="input-wrapper">
                            <input type="text" id="collshortname" name="collshortname" class="form-input"
                                value="<?php echo htmlspecialchars($values['collshortname'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo field_error($errors['collshortname']); ?>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn--primary">Save New School Entry</button>
                        <button type="reset" class="btn btn--secondary">Reset Form</button>
                        <a href="../SchoolsPage.php" class="btn btn--danger">Exit</a>   
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>

</html>