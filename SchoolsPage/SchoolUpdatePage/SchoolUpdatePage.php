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

    // Initialize variables
    $collid = filter_input(INPUT_GET, 'collid', FILTER_VALIDATE_INT);
    $errors = ['collfullname' => '', 'collshortname' => ''];
    $values = ['collid' => '', 'collfullname' => '', 'collshortname' => ''];
    $success = '';
    $pageError = '';

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
            else 
            {
                $values = 
                [
                    'collid'       => $school['collid'],
                    'collfullname' => $school['collfullname'],
                    'collshortname'=> $school['collshortname'],
                ];
            }
        } 
        catch (PDOException $e) 
        {
            $pageError = 'Error loading school: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$pageError) 
    {
        $values['collfullname']  = trim($_POST['collfullname'] ?? '');
        $values['collshortname'] = trim($_POST['collshortname'] ?? '');

        // Validate School Full Name
        if ($values['collfullname'] === '') 
        {
            $errors['collfullname'] = 'School Full Name entry cannot be empty';
        }
        elseif (!preg_match("/^[a-zA-Z\s]*$/", $values['collfullname'])) 
        {
            $errors['collfullname'] = 'School Full Name must contain letters and spaces only';
        }

        // Validate School Short Name
        if ($values['collshortname'] === '')
        {
            $errors['collshortname'] = 'School Short Name entry cannot be empty';
        }
        elseif (!preg_match("/^[a-zA-Z-]*$/", $values['collshortname'])) 
        {
            $errors['collshortname'] = 'School Short Name must contain letters and hyphens only';
        }

        // Check for duplicate full name (excluding current record)
        if ($errors['collfullname'] === '')
        {
            try
            {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM colleges WHERE collfullname = ? AND collid != ?");
                $stmt->execute([$values['collfullname'], $values['collid']]);

                if ($stmt->fetchColumn() > 0)
                {
                    $errors['collfullname'] = 'That School Full Name already exists';
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
                $stmt = $pdo->prepare("UPDATE colleges SET collfullname = ?, collshortname = ? WHERE collid = ?");
                $stmt->execute([
                    $values['collfullname'],
                    $values['collshortname'],
                    $values['collid'],
                ]);

                $success = 'School updated successfully.';
            } 
            catch (PDOException $e) 
            {
                $pageError = 'Error updating school: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
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
    <title>Update School - USJ-R School Management System</title>
    <link rel="stylesheet" href="./SchoolUpdatePage.css">
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
                <h2>Update School</h2>
            </section>

            <?php if ($pageError): ?>
                <div class="alert alert--error"><?php echo htmlspecialchars($pageError, ENT_QUOTES, 'UTF-8'); ?></div>
                <a href="../SchoolsPage.php" class="btn btn--secondary" style="margin-top: 10px; display: inline-block;">Back to School List</a>
            <?php else: ?>
                
                <?php if ($success): ?>
                    <div class="alert alert--success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <div class="form-container">
                    <form method="POST" class="school-form" action="?collid=<?php echo urlencode($values['collid']); ?>" novalidate>

                        <div class="form-group">
                            <label for="collid">School ID:</label>
                            <div class="input-wrapper">
                                <input type="text" id="collid" class="form-input" value="<?php echo htmlspecialchars($values['collid'], ENT_QUOTES, 'UTF-8'); ?>" disabled>
                                <input type="hidden" name="collid" value="<?php echo htmlspecialchars($values['collid'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="collfullname">School Full Name:</label>
                            <div class="input-wrapper">
                                <input type="text" id="collfullname" name="collfullname" class="form-input" value="<?php echo htmlspecialchars($values['collfullname'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo field_error($errors['collfullname']); ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="collshortname">School Short Name:</label>
                            <div class="input-wrapper">
                                <input type="text" id="collshortname" name="collshortname" class="form-input" value="<?php echo htmlspecialchars($values['collshortname'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo field_error($errors['collshortname']); ?>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn--primary">Update School Entry</button>
                            <a href="SchoolUpdatePage.php?collid=<?php echo urlencode($values['collid']); ?>" class="btn btn--secondary">Reset Form</a>
                            <a href="../SchoolsPage.php" class="btn btn--danger">Exit</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>