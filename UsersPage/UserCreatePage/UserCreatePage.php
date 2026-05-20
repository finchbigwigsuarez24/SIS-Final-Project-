<?php
    session_start();

    include '../../db.php';

    // Check if user is logged in
    if (!isset($_SESSION['username']))
    {
        header("Location: ../../LogInPage/LogInPage.php");
        exit();
    }

    $username = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');
    $isAdmin  = (isset($_SESSION['usertype']) && $_SESSION['usertype'] === 'Administrator');

    // Handle logout
    if (isset($_GET['logout']))
    {
        session_destroy();
        header("Location: ../../LogInPage/LogInPage.php");
        exit();
    }

    $success       = '';
    $error         = '';
    $insertedCount = 0;
    $skippedCount  = 0;

    // Handle file upload and processing
    if ($_SERVER['REQUEST_METHOD'] === 'POST')
    {
        // Check if file was submitted 
        // Checks whether the upload field exists at all     //Checks whether the user submitted the form without selecting a file
        if (!isset($_FILES['user_file']) || $_FILES['user_file']['error'] === UPLOAD_ERR_NO_FILE)
        {
            $error = 'No file selected. Please choose a CSV file before uploading.';
        }
        // This checks if any upload error occurred.
        elseif ($_FILES['user_file']['error'] !== UPLOAD_ERR_OK)
        {
            $error = 'File upload error. Please try again.';
        }
        else
        {
            // Validate file type by extension (basic check)
            $file = $_FILES['user_file'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if ($ext !== 'csv')
            {
                $error = 'Invalid file type. Only CSV files are allowed.';
            }
            else
            {
                // Attempt to open the uploaded file for reading
                $handle = fopen($file['tmp_name'], 'r');

                if ($handle === false)
                {
                    $error = 'Could not read the uploaded file.';
                }
                else
                {
                    // Skip header row — escape parameter explicitly set to avoid deprecation warning
                    fgetcsv($handle, 0, ',', '"', '');

                    try
                    {
                        // Use a transaction to ensure all-or-nothing import
                        $pdo->beginTransaction();

                        // Read each row of the CSV file and insert into the database
                        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false)
                        {
                            // Clean any backticks or whitespace from values
                            $row = array_map(fn($v) => trim(str_replace('`', '', $v)), $row);

                            if (count($row) < 5)
                            {
                                $skippedCount++;
                                continue;
                            }

                            [$userid, $uname, $upassword, $utype, $urole] = $row;

                            // Skip empty or invalid rows
                            if ($userid === '' || $uname === '' || $upassword === '')
                            {
                                $skippedCount++;
                                continue;
                            }

                            // Check if userid or username already exists
                            $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE userid = ? OR username = ?");
                            $check->execute([$userid, $uname]);

                            if ($check->fetchColumn() > 0)
                            {
                                $skippedCount++;
                                continue;
                            }

                            $stmt = $pdo->prepare("INSERT INTO users (userid, username, password, usertype, userrole)
                                                   VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$userid, $uname, $upassword, $utype, $urole]);
                            $insertedCount++;
                        }

                        $pdo->commit();
                        fclose($handle);

                        if ($insertedCount > 0)
                        {
                            $success = $insertedCount . ' user(s) imported successfully.';
                            if ($skippedCount > 0)
                            {
                                $success .= ' ' . $skippedCount . ' row(s) skipped (duplicate or invalid).';
                            }
                        }
                        else
                        {
                            $error = 'No users were imported. All rows were either duplicates or invalid.';
                        }
                    }
                    catch (PDOException $e)
                    {
                        $pdo->rollBack();
                        fclose($handle);
                        $error = 'Database error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
                    }
                }
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Users From File - USJ-R School Management System</title>
    <link rel="stylesheet" href="./UserCreatePage.css">
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
                <li><a href="../../StudentsPage/StudentsPage.php">Students</a></li>
                <?php if ($isAdmin): ?>
                    <li class="userssidebar"><a href="../UsersPage.php">Users</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <main class="content">
            <section class="page-header">
                <h2>Add Users From File</h2>
            </section>

            <?php if ($error !== ''): ?>
                <div class="alert alert--error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <div class="alert alert--success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data" class="upload-form">

                <div class="upload-row">
                    <input type="file" name="user_file" id="user_file" accept=".csv" class="file-input">
                    <span class="instruction">Select a CSV file to upload</span>
                </div>

                <div class="action-row">
                    <button type="submit" class="btn btn--upload">Upload</button>
                    <a href="../UsersPage.php" class="btn btn--exit">Exit</a>
                </div>

            </form>
        </main>
    </div>
</body>
</html>