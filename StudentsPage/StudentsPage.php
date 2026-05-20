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
    if (isset($_GET['logout'])) {
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
    $departments    = [];
    $selectedSchool = $_POST['selected_school'] ?? $_GET['selected_school'] ?? '';

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

    // Fetch programs based on selected department
    $programs     = [];
    $selectedDept = $_POST['selected_dept'] ?? $_GET['selected_dept'] ?? '';

    if ($selectedDept !== '')
    {
        try
        {
            $stmt = $pdo->prepare("SELECT progid, progfullname FROM programs WHERE progcolldeptid = ? ORDER BY progfullname ASC");
            $stmt->execute([$selectedDept]);
            $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (PDOException $e)
        {
            $programs = [];
        }
    }

    // Fetch students based on selected program
    $students         = [];
    $selectedProg     = $_POST['selected_prog'] ?? $_GET['selected_prog'] ?? '';
    $selectedProgName = '';
    $dbError          = '';

    // Find the name of the selected program for display
    foreach ($programs as $prog)
    {
        if ($prog['progid'] == $selectedProg)
        {
            $selectedProgName = $prog['progfullname'];
            break;
        }
    }

    // Only load students if a program is selected
    if ($selectedProg !== '')
    {
        try
        {
            $stmt = $pdo->prepare("SELECT studid, studfirstname, studmidname, studlastname, studyear
                                FROM students
                                WHERE studprogid = ?
                                ORDER BY studlastname ASC, studfirstname ASC");
            $stmt->execute([$selectedProg]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (PDOException $e)
        {
            $dbError = "Error loading students: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students | USJ-R School Management</title>
    <link rel="stylesheet" href="./StudentsPage.css">
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
                <li><a href="../ProgramsPage/ProgramsPage.php">Programs</a></li>
                <li class="studentssidebar"><a href="StudentsPage.php">Students</a></li>
                <?php if ($isAdmin): ?>
                    <li><a href="../UsersPage/UsersPage.php">Users</a></li>
                <?php endif; ?>             
            </ul>
        </nav>

        <main class="content">
            <section class="page-header">
                <h2>Student List</h2>
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
                    <select class="filter-select" name="selected_dept" id="deptSelect"
                        <?php echo empty($departments) ? 'disabled' : ''; ?>>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept['deptid'], ENT_QUOTES, 'UTF-8'); ?>"
                                <?php echo ($selectedDept == $dept['deptid']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['deptfullname'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="action" value="select_dept"
                        class="btn-select <?php echo empty($departments) ? 'btn-select--disabled' : 'btn-select--active'; ?>"
                        <?php echo empty($departments) ? 'disabled' : ''; ?>>Select Department</button>
                </div>

                <div class="filter-row">
                    <select class="filter-select" name="selected_prog" id="progSelect"
                        <?php echo empty($programs) ? 'disabled' : ''; ?>>
                        <option value="">Select Program</option>
                        <?php foreach ($programs as $prog): ?>
                            <option value="<?php echo htmlspecialchars($prog['progid'], ENT_QUOTES, 'UTF-8'); ?>"
                                <?php echo ($selectedProg == $prog['progid']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($prog['progfullname'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="action" value="select_prog"
                        class="btn-select <?php echo empty($programs) ? 'btn-select--disabled' : 'btn-select--active'; ?>"
                        <?php echo empty($programs) ? 'disabled' : ''; ?>>Select Program</button>
                </div>

            </form>

            <?php if ($selectedProg !== ''): ?>

                <div class="page-actions">
                    <a href="StudentCreatePage/StudentCreatePage.php?progid=<?php echo urlencode($selectedProg); ?>" class="btn btn--primary">+ Create Student Entry</a>
                </div>

                <?php if (!empty($dbError)): ?>
                    <div class="alert"><?php echo $dbError; ?></div>
                <?php endif; ?>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Last Name</th>
                            <th>First Name</th>
                            <th>Middle Name</th>
                            <th>Year</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $stud): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($stud['studid'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($stud['studlastname'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($stud['studfirstname'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($stud['studmidname'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($stud['studyear'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="StudentUpdatePage/StudentUpdatePage.php?studid=<?php echo urlencode($stud['studid']); ?>" class="btn-action btn-update" title="Update">✎ Update</a>
                                        <a href="StudentDeletePage/StudentDeletePage.php?studid=<?php echo urlencode($stud['studid']); ?>" class="btn-action btn-delete" title="Delete">🗑 Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($students) && empty($dbError)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center; color: #aaa;">No students found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="table-footer">
                    Total of <?php echo count($students); ?> student(s) in <?php echo htmlspecialchars($selectedProgName, ENT_QUOTES, 'UTF-8'); ?>
                </div>

            <?php endif; ?>

        </main>
    </div>
</body>

</html>