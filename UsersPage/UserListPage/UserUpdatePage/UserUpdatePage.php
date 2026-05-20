<?php
    session_start();

    // Include database connection
    include '../../../db.php';

    // Check if user is logged in
    if (!isset($_SESSION['username']))
    {
        header("Location: ../../../LogInPage/LogInPage.php");
        exit();
    }

    // Sanitize username for output
    $username = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');

    $isAdmin  = (isset($_SESSION['usertype']) && $_SESSION['usertype'] === 'Administrator');

    // Handle logout
    if (isset($_GET['logout']))
    {
        session_destroy();
        header("Location: ../../../LogInPage/LogInPage.php");
        exit();
    }

    $userid    = filter_input(INPUT_GET, 'userid', FILTER_VALIDATE_INT);
    $success   = '';
    $pageError = '';

    $errors = ['username' => '', 'password' => '', 'confirmpassword' => '', 'usertype' => '', 'userrole' => ''];
    $values = ['userid' => '', 'username' => '', 'usertype' => '', 'userrole' => ''];

    // Validate and load user record
    if (!$userid)
    {
        $pageError = 'Invalid user identifier.';
    }
    else
    {
        try
        {
            $stmt = $pdo->prepare("SELECT userid, username, usertype, userrole FROM users WHERE userid = ?");
            $stmt->execute([$userid]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user)
            {
                $pageError = 'User record not found.';
            }
            else
            {
                $values = [
                    'userid'   => $user['userid'],
                    'username' => $user['username'],
                    'usertype' => $user['usertype'],
                    'userrole' => $user['userrole'],
                ];
            }
        }
        catch (PDOException $e)
        {
            $pageError = 'Error loading user: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$pageError)
    {
        // Collect and validate form data
        $values['username'] = trim($_POST['username'] ?? '');
        $values['usertype'] = trim($_POST['usertype'] ?? '');
        $values['userrole'] = trim($_POST['userrole'] ?? '');
        $password           = trim($_POST['password']        ?? '');
        $confirmpassword    = trim($_POST['confirmpassword'] ?? '');

        // Validate username
        if ($values['username'] === '')
        {
            $errors['username'] = 'Username cannot be empty';
        }
        else
        {
            try
            {
                // Check for duplicate username (excluding current user)
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND userid != ?");
                $stmt->execute([$values['username'], $values['userid']]);

                if ($stmt->fetchColumn() > 0)
                {
                    $errors['username'] = 'That username already exists.';
                }
            }
            catch (PDOException $e)
            {
                $pageError = 'Error checking username: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            }
        }

        // Validate user type
        if ($values['usertype'] === '')
        {
            $errors['usertype'] = 'User Type cannot be empty';
        }

        // Validate user role
        if ($values['userrole'] === '')
        {
            $errors['userrole'] = 'User Role cannot be empty';
        }

        // Password optional on update
        if ($password !== '' && $confirmpassword === '')
        {
            $errors['confirmpassword'] = 'Please confirm the new password';
        }
        elseif ($password !== '' && $password !== $confirmpassword)
        {
            $errors['confirmpassword'] = 'Passwords do not match';
        }

        // If no validation errors, proceed to update
        if (!$pageError && !array_filter($errors))
        {
            try
            {
                // Check if password needs to be updated
                if ($password !== '')
                {
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, usertype = ?, userrole = ?, password = ? WHERE userid = ?");
                    $stmt->execute([$values['username'], $values['usertype'], $values['userrole'], $password, $values['userid']]);
                }
                else
                {
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, usertype = ?, userrole = ? WHERE userid = ?");
                    $stmt->execute([$values['username'], $values['usertype'], $values['userrole'], $values['userid']]);
                }

                $success = $stmt->rowCount() > 0 ? 'User updated successfully.' : 'No changes were made.';
            }
            catch (PDOException $e)
            {
                $pageError = 'Error updating user: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
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
    <title>Update User - USJ-R School Management System</title>
    <link rel="stylesheet" href="./UserUpdatePage.css">
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
                <li><a href="../../../HomePage/HomePage.php">Home</a></li>
                <li><a href="../../../SchoolsPage/SchoolsPage.php">Schools</a></li>
                <li><a href="../../../DepartmentsPage/DepartmentsPage.php">Departments</a></li>
                <li><a href="../../../ProgramsPage/ProgramsPage.php">Programs</a></li>
                <li><a href="../../../StudentsPage/StudentsPage.php">Students</a></li>
                <li class="userssidebar"><a href="../../UsersPage.php">Users</a></li>
            </ul>
        </nav>

        <main class="content">
            <section class="page-header">
                <h2>Update User</h2>
            </section>

            <?php if ($pageError): ?>
                <div class="alert alert--error"><?php echo htmlspecialchars($pageError, ENT_QUOTES, 'UTF-8'); ?></div>
                <a href="../UsersListPage.php" class="btn btn--secondary">Back to User List</a>

            <?php else: ?>
                <?php if ($success !== ''): ?>
                    <div class="alert alert--success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <div class="form-container">
                    <form method="POST" action="?userid=<?php echo urlencode($values['userid']); ?>" class="user-form" novalidate>

                        <div class="form-group">
                            <label for="userid">User ID:</label>
                            <div class="input-wrapper">
                                <input type="text" id="userid" class="form-input"
                                    value="<?php echo htmlspecialchars($values['userid'], ENT_QUOTES, 'UTF-8'); ?>" disabled>
                                <input type="hidden" name="userid" value="<?php echo htmlspecialchars($values['userid'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="username">Username:</label>
                            <div class="input-wrapper">
                                <input type="text" id="username" name="username" class="form-input"
                                    value="<?php echo htmlspecialchars($values['username'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo field_error($errors['username']); ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="usertype">User Type:</label>
                            <div class="input-wrapper">
                                <select id="usertype" name="usertype" class="form-input">
                                    <option value="Administrator" <?php echo $values['usertype'] === 'Administrator' ? 'selected' : ''; ?>>Administrator</option>
                                    <option value="User"          <?php echo $values['usertype'] === 'User'          ? 'selected' : ''; ?>>User</option>
                                </select>
                                <?php echo field_error($errors['usertype']); ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="userrole">User Role:</label>
                            <div class="input-wrapper">
                                <select id="userrole" name="userrole" class="form-input">
                                    <option value="Administrator" <?php echo $values['userrole'] === 'Administrator' ? 'selected' : ''; ?>>Administrator</option>
                                    <option value="Creator"       <?php echo $values['userrole'] === 'Creator'       ? 'selected' : ''; ?>>Creator</option>
                                    <option value="Viewer"        <?php echo $values['userrole'] === 'Viewer'        ? 'selected' : ''; ?>>Viewer</option>
                                    <option value="Updater"       <?php echo $values['userrole'] === 'Updater'       ? 'selected' : ''; ?>>Updater</option>
                                    <option value="Remover"       <?php echo $values['userrole'] === 'Remover'       ? 'selected' : ''; ?>>Remover</option>
                                </select>
                                <?php echo field_error($errors['userrole']); ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password">New Password: <span class="optional">(optional)</span></label>
                            <div class="input-wrapper">
                                <input type="password" id="password" name="password" class="form-input">
                                <?php echo field_error($errors['password']); ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirmpassword">Confirm Password:</label>
                            <div class="input-wrapper">
                                <input type="password" id="confirmpassword" name="confirmpassword" class="form-input">
                                <?php echo field_error($errors['confirmpassword']); ?>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn--primary">Update User</button>
                            <a href="UserUpdatePage.php?userid=<?php echo urlencode($values['userid']); ?>" class="btn btn--secondary">Reset Form</a>
                            <a href="../UsersListPage.php" class="btn btn--danger">Exit</a>
                        </div>

                    </form>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>