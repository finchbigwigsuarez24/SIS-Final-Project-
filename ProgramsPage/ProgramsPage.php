<?php
    session_start();

    include '../db.php';

    // Check if user is logged in
    if (!isset($_SESSION['username']))
    {
        header("Location: ../LogInPage/LogInPage.php");
        exit();
    }

    // Sanitize username for display
    $username = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');

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
    $selectedDept       = $_POST['selected_dept']   ?? $_GET['selected_dept']   ?? '';
    $selectedSchoolName = '';
    $selectedDeptName   = '';

    // Find selected school name
    foreach ($schools as $school) 
    {
        if ($school['collid'] == $selectedSchool) 
        {
            $selectedSchoolName = $school['collfullname'];
            break;
        }
    }

    // Fetch departments for the selected school
    if ($selectedSchool !== '') 
    {
        try 
        {
            $stmt = $pdo->prepare("SELECT deptid, deptfullname FROM departments WHERE deptcollid = ? ORDER BY deptfullname ASC");
            $stmt->execute([$selectedSchool]);
            $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } 
        catch (PDOException $e) 
        {
            $departments = [];
        }
    }

    // Find selected department name
    foreach ($departments as $dept) 
    {
        if ($dept['deptid'] == $selectedDept) 
        {
            $selectedDeptName = $dept['deptfullname'];
            break;
        }
    }

    // Fetch programs based on selected department
    $programs = [];
    $dbError  = '';

    // Only fetch programs if a department is selected
    if ($selectedDept !== '') 
    {
        try 
        {
            $stmt = $pdo->prepare("SELECT progid, progfullname, progshortname FROM programs WHERE progcolldeptid = ? ORDER BY progfullname ASC");
            $stmt->execute([$selectedDept]);
            $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } 
        catch (PDOException $e) 
        {
            $dbError = "Error loading programs: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USJ-R School Management System</title>
    <link rel="stylesheet" href="./ProgramsPage.css">
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
                <li><a href="../DepartmentsPage/DepartmentsPage.php">Departments</a></li>
                <li class="programssidebar"><a href="ProgramsPage.php">Programs</a></li>
                <li><a href="../StudentsPage/StudentsPage.php">Students</a></li>
                <?php if ($isAdmin): ?>
                    <li><a href="../UsersPage/UsersPage.php">Users</a></li>
                <?php endif; ?>             
            </ul>
        </nav>

        <main class="content">
            <section class="page-header">
                <h2>Program List</h2>
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

                <div class="filter-row">
                    <select class="filter-select" name="selected_dept" id="departmentSelect"
                        <?php echo ($selectedSchool === '') ? 'disabled' : ''; ?>>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept['deptid'], ENT_QUOTES, 'UTF-8'); ?>"
                                <?php echo ($selectedDept == $dept['deptid']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['deptfullname'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="action" value="select_dept"
                        class="btn-select <?php echo ($selectedSchool === '') ? 'btn-select--disabled' : 'btn-select--active'; ?>"
                        <?php echo ($selectedSchool === '') ? 'disabled' : ''; ?>>
                        Select Department
                    </button>
                </div>
            </form>

            <?php if ($selectedDept !== ''): ?>

                <div class="page-actions">
                    <a href="ProgramCreatePage/ProgramCreatePage.php?deptid=<?php echo urlencode($selectedDept); ?>" class="btn btn--primary">+ Create Program Entry</a>
                </div>

                <?php if (!empty($dbError)): ?>
                    <div class="alert"><?php echo $dbError; ?></div>
                <?php endif; ?>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Program ID</th>
                            <th>Program Full Name</th>
                            <th>Program Short Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($programs) > 0): ?>
                            <?php foreach ($programs as $program): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($program['progid'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($program['progfullname'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($program['progshortname'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="ProgramUpdatePage/ProgramUpdatePage.php?progid=<?php echo urlencode($program['progid']); ?>" class="btn-action btn-update" title="Update">✎ Update</a>
                                            <a href="ProgramDeletePage/ProgramDeletePage.php?progid=<?php echo urlencode($program['progid']); ?>" class="btn-action btn-delete" title="Delete">🗑 Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No programs found for this department.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="table-footer">
                    Total of <?php echo count($programs); ?> programs in this department
                </div>

            <?php endif; ?>

        </main>
    </div>

</body>

</html>