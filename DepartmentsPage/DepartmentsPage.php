<?php
    session_start();

    // Include database connection
    include '../db.php';

    // Check if user is logged in
    if (!isset($_SESSION['username']))
    {
        header("Location: ../LogInPage/LogInPage.php");
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
        header("Location: ../LogInPage/LogInPage.php");
        exit();
    }

    // Fetch all schools for dropdown
    try 
    {
        $stmt    = $pdo->prepare("SELECT collid, collfullname FROM colleges ORDER BY collfullname ASC");
        $stmt->execute();
        $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } 
    catch (PDOException $e) 
    {
        $schools = [];
    }

    // Fetch departments based on selected school
    $departments        = [];
    $selectedSchool     = $_POST['selected_school'] ?? $_GET['selected_school'] ?? '';
    $selectedSchoolName = '';
    $dbError            = '';

    // Find the selected school's name for display
    foreach ($schools as $school) 
    {
        if ($school['collid'] == $selectedSchool) 
        {
            $selectedSchoolName = $school['collfullname'];
            break;
        }
    }

    // Load departments if a school is selected
    if ($selectedSchool !== '') 
    {
        try 
        {
            $stmt = $pdo->prepare("SELECT deptid, deptfullname, deptshortname FROM departments WHERE deptcollid = ? ORDER BY deptfullname ASC");
            $stmt->execute([$selectedSchool]);
            $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } 
        catch (PDOException $e) 
        {
            $dbError = "Error loading departments: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USJ-R School Management System</title>
    <link rel="stylesheet" href="./DepartmentsPage.css">
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
                <li><a href="../HomePage/HomePage.php">Home</a></li>
                <li><a href="../SchoolsPage/SchoolsPage.php">Schools</a></li>
                <li class="departmentssidebar"><a href="../DepartmentsPage/DepartmentsPage.php">Departments</a></li>
                <li><a href="../ProgramsPage/ProgramsPage.php">Programs</a></li>
                <li><a href="../StudentsPage/StudentsPage.php">Students</a></li>
                <?php if ($isAdmin): ?>
                    <li><a href="../UsersPage/UsersPage.php">Users</a></li>
                <?php endif; ?>            
            </ul>
        </nav>

        <main class="content">
            <section class="page-header">
                <h2>Department List</h2>
            </section>

            <form method="POST" action="" class="filter-form">
                <div class="filter-row">
                    <select class="filter-select" name="selected_school" id="schoolSelect">
                        <option value="">Select School</option>
                        <?php foreach ($schools as $school): ?>
                            <option value="<?php echo htmlspecialchars($school['collid'], ENT_QUOTES, 'UTF-8'); ?>"
                                <?php echo ($selectedSchool == $school['collid']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($school['collfullname'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="action" value="select_school" class="btn-select btn-select--active">Select School</button>
                </div>
            </form>

            <?php if ($selectedSchool !== ''): ?>

                <div class="page-actions">
                    <a href="DepartmentCreatePage/DepartmentCreatePage.php?collid=<?php echo urlencode($selectedSchool); ?>" class="btn btn--primary">+ Create Department Entry</a>
                </div>

                <?php if (!empty($dbError)): ?>
                    <div class="alert"><?php echo $dbError; ?></div>
                <?php endif; ?>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Department ID</th>
                            <th>Department Full Name</th>
                            <th>Department Short Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $dept): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($dept['deptid'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($dept['deptfullname'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($dept['deptshortname'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="DepartmentUpdatePage/DepartmentUpdatePage.php?deptid=<?php echo urlencode($dept['deptid']); ?>" class="btn-action btn-update" title="Update">✎ Update</a>
                                        <a href="DepartmentDeletePage/DepartmentDeletePage.php?deptid=<?php echo urlencode($dept['deptid']); ?>" class="btn-action btn-delete" title="Delete">🗑 Delete</a>
                                    </div>
                                </td>
                             </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="table-footer">
                    Total of <?php echo count($departments); ?> departments in <?php echo htmlspecialchars($selectedSchoolName, ENT_QUOTES, 'UTF-8'); ?>
                </div>

            <?php endif; ?>

        </main>
    </div>

</body>

</html>