<?php
session_start();

// Automatic logout after 2 hours of inactivity
$timeout_duration = 7200; // 2 hours in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_duration)) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
$_SESSION['last_activity'] = time(); // Update last activity timestamp

// Redirect to login.php if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "task_management");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Update last login time when buttons are clicked
if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['create_task']) || isset($_POST['delete_task']) || isset($_POST['save_tasks']))) {
    $current_time = date('Y-m-d H:i:s');
    $user_id = $_SESSION['user_id'];
    $sql = "UPDATE users SET last_login='$current_time' WHERE id='$user_id'";
    $conn->query($sql);
    $_SESSION['last_login'] = $current_time;
}

// Fetch user's last login time
$user_id = $_SESSION['user_id'];
$user_result = $conn->query("SELECT last_login FROM users WHERE id='$user_id'");
$user_data = $user_result->fetch_assoc();
$last_login = isset($user_data['last_login']) ? new DateTime($user_data['last_login']) : new DateTime();
$now = new DateTime();
$days_since_login = $last_login->diff($now)->days;

// Calculate completion percentage
$total_tasks = $conn->query("SELECT COUNT(*) as total FROM `tasks` WHERE `user_id`='$user_id'")->fetch_assoc()['total'];
$completed_tasks = $conn->query("SELECT COUNT(*) as completed FROM `tasks` WHERE `user_id`='$user_id' AND `completed`=1")->fetch_assoc()['completed'];
$percentage = $total_tasks > 0 ? number_format(($completed_tasks / $total_tasks) * 100, 2) : "0.00";

// Check for tasks that are a day late
$alert_message = "";
$one_day_ago = (new DateTime())->modify('-1 day')->format('Y-m-d H:i:s');
$one_day_after = (new DateTime())->modify('+1 day')->format('Y-m-d H:i:s');
$current_time = (new DateTime())->format('Y-m-d H:i:s');
$late_tasks = $conn->query("SELECT * FROM `tasks` WHERE `user_id`='$user_id' AND `due_date` < '$one_day_ago' AND `completed`=0");
$due_tasks = $conn->query("SELECT * FROM `tasks` WHERE `user_id`='$user_id' AND `due_date` >= '$current_time' AND `due_date` < '$one_day_after' AND `completed`=0");
$late_task_count = $late_tasks->num_rows;
$due_task_count = $due_tasks->num_rows;
if ($late_task_count > 0) {
    $alert_message .= "  $late_task_count task(s) are overdue by more than a day!";
}

if ($late_task_count > 0 && $due_task_count > 0) {
    $alert_message .= "<br>";
}

if ($due_task_count > 0) {
    $alert_message .= "  $due_task_count task(s) are due within the next day! 🔥";
}

$first_trophy = '';
$first_trophy_alt = '';
if ($days_since_login > 50) {
    $first_trophy = 'gold2.jpg';
    $first_trophy_alt = 'Gold Trophy: Logged in for a total of 50 days!'."\n".'You deserve it!';
} elseif ($days_since_login > 20) {
    $first_trophy = 'silver2.jpg';
    $first_trophy_alt = 'Silver Trophy: Logged in for a total of 20 days!'."\n".'Keep pushing forward!';
} elseif ($days_since_login > 5) {
    $first_trophy = 'bronze2.jpg';
    $first_trophy_alt = 'Bronze Trophy: Logged in for a total of 5 days!'."\n".'Keep up the momentum!';
} else {
    $first_trophy = 'blank2.jpg';
    $first_trophy_alt = 'You need to log in for 5 days to earn your bronze trophy!'."\n".'You are on the right track!';
}

$second_trophy = '';
$second_trophy_alt = '';
if ($total_tasks > 250) {
    $second_trophy = 'gold2.jpg';
    $second_trophy_alt = 'Gold Trophy: Over 250 tasks created!'."\n".'Amazing accomplishment!';
} elseif ($total_tasks > 75) {
    $second_trophy = 'silver2.jpg';
    $second_trophy_alt = 'Silver Trophy: Over 75 tasks created!'."\n".'You are doing amazing!';
} elseif ($total_tasks > 10) {
    $second_trophy = 'bronze2.jpg';
    $second_trophy_alt = 'Bronze Trophy: Over 10 tasks created!'."\n".'Keep it up!';
} else {
    $second_trophy = 'blank2.jpg';
    $second_trophy_alt = 'You need to create just 10 tasks to earn your bronze trophy!'."\n".'You are almost there!';
}

$third_trophy = '';
$third_trophy_alt = '';
if ($completed_tasks > 200) {
    $third_trophy = 'gold2.jpg';
    $third_trophy_alt = 'Gold Trophy: Over 200 tasks completed!'."\n".'Incredible achievement!';
} elseif ($completed_tasks > 60) {
    $third_trophy = 'silver2.jpg';
    $third_trophy_alt = 'Silver Trophy: Over 60 tasks completed!'."\n".'Your hard work is paying off!';
} elseif ($completed_tasks > 10) {
    $third_trophy = 'bronze2.jpg';
    $third_trophy_alt = 'Bronze Trophy: Over 10 tasks completed!'."\n".'Stay motivated!';
} else {
    $third_trophy = 'blank2.jpg';
    $third_trophy_alt = 'You are just 10 tasks away from earning your bronze trophy!'."\n".'Keep up the great work!';
}

// Handle task creation with repeating
$create_message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_task'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $description = isset($_POST['description']) ? $conn->real_escape_string($_POST['description']) : NULL;
    $due_date = $conn->real_escape_string($_POST['due_date']);
    $priority = $conn->real_escape_string($_POST['priority']);
    $tags = $conn->real_escape_string($_POST['tags']);
    $repeat = $conn->real_escape_string($_POST['repeat']);
    $repeat_until = $conn->real_escape_string($_POST['repeat_until']);
    $user_id = $_SESSION['user_id'];

    $sql = "INSERT INTO `tasks` (`user_id`, `title`, `description`, `due_date`, `priority`, `tags`, `completed`) 
            VALUES ('$user_id', '$title', " . ($description ? "'$description'" : "NULL") . ", '$due_date', '$priority', '$tags', 0)";
    if ($conn->query($sql) === TRUE) {
        $task_id = $conn->insert_id;
        if ($repeat !== 'None' && $repeat_until) {
            $base_date = new DateTime($due_date);
            $end_date = new DateTime($repeat_until);
            $interval = $repeat === 'Daily' ? 'P1D' : 
                        ($repeat === 'Weekly' ? 'P1W' : 
                        ($repeat === 'Biweekly' ? 'P2W' : 
                        ($repeat === 'Monthly' ? 'P1M' : 'P3M')));
            $current_date = clone $base_date;
            $i = 1;
            while ($current_date <= $end_date) {
                $current_date->add(new DateInterval($interval));
                if ($current_date > $end_date) break;
                $new_due_date = $current_date->format('Y-m-d H:i:s');
                $new_title = "$title ($i)";
                $sql = "INSERT INTO `tasks` (`user_id`, `title`, `description`, `due_date`, `priority`, `tags`, `completed`) 
                        VALUES ('$user_id', '$new_title', " . ($description ? "'$description'" : "NULL") . ", '$new_due_date', '$priority', '$tags', 0)";
                $conn->query($sql);
                $i++;
            }
        }
        if (isset($_POST['ajax_create_task'])) {
            echo json_encode([
                'success' => true,
                'total_tasks' => $conn->query("SELECT COUNT(*) as total FROM `tasks` WHERE `user_id`='$user_id'")->fetch_assoc()['total'],
                'completed_tasks' => $conn->query("SELECT COUNT(*) as completed FROM `tasks` WHERE `user_id`='$user_id' AND `completed`=1")->fetch_assoc()['completed']
            ]);
            exit();
        }
        $create_message = "Task created successfully!";
    } else {
        if (isset($_POST['ajax_create_task'])) {
            echo json_encode(['success' => false, 'error' => $conn->error]);
            exit();
        }
        $create_message = "Error: " . $conn->error;
    }
}

// Handle task updates (bulk edit)
$edit_message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_tasks'])) {
    foreach ($_POST['tasks'] as $task_id => $task_data) {
        $title = $conn->real_escape_string($task_data['title']);
        $description = isset($task_data['description']) ? $conn->real_escape_string($task_data['description']) : NULL;
        $due_date = $conn->real_escape_string($task_data['due_date']);
        $priority = $conn->real_escape_string($task_data['priority']);
        $tags = $conn->real_escape_string($task_data['tags']); 
        $completed = isset($task_data['completed']) ? 1 : 0;

        $sql = "UPDATE `tasks` SET `title`='$title', `description`=" . ($description ? "'$description'" : "NULL") . ", 
                `due_date`='$due_date', `priority`='$priority', `tags`='$tags', `completed`='$completed' 
                WHERE `id`='$task_id' AND `user_id`='{$_SESSION['user_id']}'";
        $conn->query($sql);
    }
    $edit_message = "Tasks updated successfully!";
}

// Handle task deletion
$delete_message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_task'])) {
    $task_id = $conn->real_escape_string($_POST['task_id']);
    $sql = "DELETE FROM `tasks` WHERE `id`='$task_id' AND `user_id`='{$_SESSION['user_id']}'";
    if ($conn->query($sql) === TRUE) {
        if (isset($_POST['ajax_delete_task'])) {
            echo json_encode([
                'success' => true,
                'total_tasks' => $conn->query("SELECT COUNT(*) as total FROM `tasks` WHERE `user_id`='$user_id'")->fetch_assoc()['total'],
                'completed_tasks' => $conn->query("SELECT COUNT(*) as completed FROM `tasks` WHERE `user_id`='$user_id' AND `completed`=1")->fetch_assoc()['completed']
            ]);
            exit();
        }
        $delete_message = "Task deleted successfully!";
    } else {
        if (isset($_POST['ajax_delete_task'])) {
            echo json_encode(['success' => false, 'error' => $conn->error]);
            exit();
        }
        $delete_message = "Error: " . $conn->error;
    }
}

// Handle custom order saving
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['custom_order'])) {
    $order = explode(',', $_POST['custom_order']);
    foreach ($order as $index => $task_id) {
        $task_id = $conn->real_escape_string($task_id);
        $conn->query("UPDATE `tasks` SET `sort_order`='$index' WHERE `id`='$task_id' AND `user_id`='{$_SESSION['user_id']}'");
    }
}

// Handle AJAX checkbox update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax_update_completed'])) {
    $task_id = $conn->real_escape_string($_POST['task_id']);
    $completed = $conn->real_escape_string($_POST['completed']);
    $sql = "UPDATE `tasks` SET `completed`='$completed' WHERE `id`='$task_id' AND `user_id`='{$_SESSION['user_id']}'";
    if ($conn->query($sql) === TRUE) {
        $total_tasks = $conn->query("SELECT COUNT(*) as total FROM `tasks` WHERE `user_id`='$user_id'")->fetch_assoc()['total'];
        $completed_tasks = $conn->query("SELECT COUNT(*) as completed FROM `tasks` WHERE `user_id`='$user_id' AND `completed`=1")->fetch_assoc()['completed'];
        $response = [
            'success' => true,
            'total_tasks' => $total_tasks,
            'completed_tasks' => $completed_tasks
        ];
        if ($completed == 1) {
            $congrat_messages = [
                "Great job on completing that task! Keep it up! 🎉",
                "Well done! Another task checked off your list! ✅",
                "Awesome work! You're making great progress! 🚀",
                "Congratulations on finishing that task! You're a star! 🌟",
                "Fantastic effort! One more task down! 💪",
                "You're killing it! Task completed successfully! 🔥",
                "Superb! You've completed another task! 🏆",
                "Way to go! That task is done and dusted! 🎯",
                "Amazing! You're on a roll with that task completion! ⚡",
                "Nice one! Another task completed with success! 🥳"
            ];
            $response['congrat_message'] = $congrat_messages[array_rand($congrat_messages)];
        }
        echo json_encode($response);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit();
}

// Handle AJAX request for task counts
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax_get_counts'])) {
    $user_id = $_SESSION['user_id'];
    $total_tasks = $conn->query("SELECT COUNT(*) as total FROM `tasks` WHERE `user_id`='$user_id'")->fetch_assoc()['total'];
    $completed_tasks = $conn->query("SELECT COUNT(*) as completed FROM `tasks` WHERE `user_id`='$user_id' AND `completed`=1")->fetch_assoc()['completed'];
    echo json_encode([
        'success' => true,
        'total_tasks' => $total_tasks,
        'completed_tasks' => $completed_tasks
    ]);
    exit();
}

// Handle sorting and filtering
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'incomplete';
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$tag_filter = isset($_GET['tag']) ? $conn->real_escape_string($_GET['tag']) : '';
$date_range = isset($_GET['date_range']) ? $_GET['date_range'] : '';
$custom_start = isset($_GET['custom_start']) ? $conn->real_escape_string($_GET['custom_start']) : '';
$custom_end = isset($_GET['custom_end']) ? $conn->real_escape_string($_GET['custom_end']) : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'due_date';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'ASC';

$where_clause = "`user_id`='{$_SESSION['user_id']}'";
if ($filter === 'completed') {
    $where_clause .= " AND `completed`=1";
} elseif ($filter === 'incomplete') {
    $where_clause .= " AND `completed`=0";
}
if ($search) {
    $where_clause .= " AND `title` LIKE '%$search%'";
}
if ($tag_filter) {
    $where_clause .= " AND `tags` LIKE '%$tag_filter%'";
}
if ($date_range) {
    $today = date('Y-m-d');
    if ($date_range === 'week') {
        $week_end = date('Y-m-d', strtotime('+1 week'));
        $where_clause .= " AND `due_date` BETWEEN '$today 00:00:00' AND '$week_end 23:59:59'";
    } elseif ($date_range === 'month') {
        $month_start = date('Y-m-01');
        $month_end = date('Y-m-t');
        $where_clause .= " AND `due_date` BETWEEN '$month_start 00:00:00' AND '$month_end 23:59:59'";
    } elseif ($date_range === 'year') {
        $year_start = date('Y-01-01');
        $year_end = date('Y-12-31');
        $where_clause .= " AND `due_date` BETWEEN '$year_start 00:00:00' AND '$year_end 23:59:59'";
    } elseif ($date_range === 'custom' && $custom_start && $custom_end) {
        $where_clause .= " AND `due_date` BETWEEN '$custom_start 00:00:00' AND '$custom_end 23:59:59'";
    }
}

$order_by = "";
if ($sort_by === 'custom') {
    $order_by = "`sort_order` ASC";
} else {
    switch ($sort_by) {
        case 'id':
            $order_by = "`id` $sort_order";
            break;
        case 'name':
            $order_by = "`title` $sort_order";
            break;
        case 'due_date':
            $order_by = "`due_date` $sort_order";
            break;
        case 'priority':
            $order_by = "`priority` $sort_order";
            break;
        case 'tags':
            $order_by = "IFNULL(`tags`, '') $sort_order";
            break;
        default:
            $order_by = "`due_date` ASC";
    }
    // When filter is 'all', sort by completed first (incomplete on top, completed on bottom)
    if ($filter === 'all') {
        $order_by = "`completed` ASC, $order_by";
    } else {
        $order_by = "$order_by, `completed` ASC";
    }
}

$tasks = [];
$result = $conn->query("SELECT * FROM `tasks` WHERE $where_clause ORDER BY $order_by");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
    }
}

// Fetch unique tags (excluding empty tags) and tasks for deletion
$tags = [];
$all_tasks = [];
$result = $conn->query("SELECT DISTINCT `tags` FROM `tasks` WHERE `user_id`='{$_SESSION['user_id']}' AND `tags` IS NOT NULL AND TRIM(`tags`) != ''");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row['tags'];
    }
}
$result = $conn->query("SELECT `id`, `title` FROM `tasks` WHERE `user_id`='{$_SESSION['user_id']}'");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $all_tasks[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            transition: background-color 0.3s, color 0.3s;
        }
        body.dark-mode {
            background-color: #222;
            color: #fff;
        }
        .header {
            background-color: #333;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 55px;
            transition: background-color 0.3s;
        }
        .trophy-icons {
            display: flex;
            align-items: left;
        }
        .header.dark-mode {
            background-color: #333; /* Changed to light grey */
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .auth-links {
            display: flex;
            align-items: center;
        }
        .auth-links a, .calendar-button {
            color: white;
            text-decoration: none;
            font-size: 16px;
            margin-left: 15px;
            padding: 5px 10px;
            transition: background-color 0.3s;
        }
        .auth-links a:hover, .calendar-button:hover {
            background-color: #555;
        }
        .auth-links.dark-mode a, .calendar-button.dark-mode {
            color: #ddd;
        }
        .auth-links.dark-mode a:hover, .calendar-button.dark-mode:hover {
            background-color: #444;
        }
        .trophy-icons img {
            width: 50px;
            height: 50px;
            margin-left: 10px;
            pointer-events: auto; /* Ensure images can trigger hover events */
            position: relative;
            z-index: 10; /* Ensure images are above other elements */
        }
        /* Custom tooltip styling for trophy images */
        .trophy-icons img[title] {
            position: relative;
        }
        .trophy-icons img[title]:hover::after {
            content: attr(title);
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            background-color: #333;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 20;
            pointer-events: none;
        }
        .dark-mode .trophy-icons img[title]:hover::after {
            background-color: #555;
        }
        .mode-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            margin-left: 15px;
            cursor: pointer;
            padding: 5px 10px;
            transition: background-color 0.3s;
        }
        .mode-toggle:hover {
            background-color: #555;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
            transition: background-color 0.3s;
        }
        .container.dark-mode {
            background-color: #30363a;
        }
        .action-buttons {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
        }
        .action-buttons button {
            padding: 10px 15px;
            background-color: #333;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .action-buttons button:hover {
            background-color: #555;
        }
        .action-buttons.dark-mode button {
            background-color: #777; /* Changed to light grey */
        }
        .action-buttons.dark-mode button:hover {
            background-color: #888; /* Lighter grey on hover */
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow-y: auto;
            z-index: 1000;
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            width: 400px;
            border-radius: 5px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
            z-index: 10;
            transition: background-color 0.3s;
        }
        .modal-content.dark-mode {
            background-color: #333;
            color: #fff;
        }
        .modal-content label {
            display: block;
            margin: 10px 0 5px;
        }
        .modal-content input, .modal-content textarea, .modal-content select {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            transition: background-color 0.3s, color 0.3s;
        }
        .modal-content.dark-mode input, .modal-content.dark-mode textarea, .modal-content.dark-mode select {
            background-color: #444;
            color: #fff;
            border: 1px solid #666;
        }
        .modal-content button {
            margin-top: 10px;
            padding: 10px 15px;
            background-color: #333;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .modal-content button:hover {
            background-color: #555;
        }
        .modal-content.dark-mode button {
            background-color: #666; /* Changed to light grey */
        }
        .modal-content.dark-mode button:hover {
            background-color: #888; /* Lighter grey on hover */
        }
        .message {
            margin: 10px 0;
            color: green;
            text-align: center;
        }
        .error {
            color: red;
        }
        .message.dark-mode {
            color: #90ee90;
        }
        .error.dark-mode {
            color: #ff4040;
        }
        .sort-filter {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .sort-filter input[type="text"], .sort-filter input[type="date"] {
            padding: 8px;
            width: 150px;
            transition: background-color 0.3s, color 0.3s;
        }
        .sort-filter select {
            padding: 8px;
            transition: background-color 0.3s, color 0.3s;
        }
        .sort-filter.dark-mode input, .sort-filter.dark-mode select {
            background-color: #444;
            color: #fff;
            border: 1px solid #666;
        }
        .sort-filter button {
            padding: 8px 15px;
            background-color: #666;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .sort-filter button.active {
            background-color: #555; /* Darker grey when active */
        }
        .sort-filter button:hover {
            background-color: #888; /* Lighter grey on hover */
        }
        .sort-filter.dark-mode button {
            background-color: #666; /* Changed to light grey */
        }
        .sort-filter.dark-mode button.active {
            background-color: #555; /* Darker grey when active */
        }
        .sort-filter.dark-mode button:hover {
            background-color: #888; /* Lighter grey on hover */
        }
        .filter-buttons button {
            padding: 8px 15px;
            background-color: #666;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .filter-buttons button.active {
            background-color: #555; /* Darker grey when active */
        }
        .filter-buttons button:hover {
            background-color: #888; /* Lighter grey on hover */
        }
        .filter-buttons.dark-mode button {
            background-color: #666; /* Changed to light grey */
        }
        .filter-buttons.dark-mode button.active {
            background-color: #555; /* Darker grey when active */
        }
        .filter-buttons.dark-mode button:hover {
            background-color: #888; /* Lighter grey on hover */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border: 1.5px solid black;
            transition: background-color 0.3s, color 0.3s;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            transition: border-color 0.3s;
        }
        th {
            background-color: #f2f2f2;
            transition: background-color 0.3s;
        }
        table.dark-mode {
            border-color: #666;
        }
        table.dark-mode th, table.dark-mode td {
            border-color: #666;
        }
        table.dark-mode th {
            background-color: #444;
        }
        table.dark-mode td {
            background-color: #333;
        }
        tr.draggable {
            cursor: move;
        }
        tr.draggable:hover {
            background-color: #f0f0f0;
        }
        tr.draggable.dark-mode:hover {
            background-color: #444;
        }
        .edit-button, .save-button, .cancel-button {
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .edit-button:hover, .save-button:hover, .cancel-button:hover {
            background-color: #0056b3;
        }
        .editable input, .editable textarea, .editable select {
            width: 100%;
            padding: 5px;
            box-sizing: border-box;
            transition: background-color 0.3s, color 0.3s;
        }
        .editable.dark-mode input, .editable.dark-mode textarea, .editable.dark-mode select {
            background-color: #444;
            color: #fff;
            border: 1px solid #666;
        }
        .priority-box {
            width: 20px;
            height: 20px;
            border-radius: 3px;
            display: inline-block;
        }
        .priority-high .priority-box { background-color: #ff0000; }
        .priority-medium .priority-box { background-color: rgb(255, 204, 0); }
        .priority-low .priority-box { background-color: #00ff00; }
        .focus-tree {
            width: 200px;
            height: 200px;
            margin: 20px auto;
            position: relative;
        }
        .progress-circle {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            position: relative;
        }
        .inner-circle {
            position: absolute;
            top: 10%;
            left: 10%;
            width: 80%;
            height: 80%;
            background: #f4f4f4;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: background-color 0.3s;
        }
        .inner-circle.dark-mode {
            background: #333;
        }
        .percentage {
            font-size: 1.5em;
            color: #333;
            transition: color 0.3s;
        }
        .percentage.dark-mode {
            color: #fff;
        }
        .alert-box {
            background-color: #ff4444;
            color: white;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 10px;
            width: 300px;
            margin-left: auto;
            margin-right: auto;
        }
        .congrat-message {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #4CAF50;
            color: white;
            padding: 15px 30px;
            border-radius: 5px;
            z-index: 1000;
            display: none;
            font-size: 1.2em;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
        }
    </style>
    <script>
        let updateInterval;
        let pendingUpdates = {};
        let isEditing = false;

        function toggleDarkMode() {
            const body = document.body;
            const header = document.querySelector('.header');
            const authLinks = document.querySelector('.auth-links');
            const container = document.querySelector('.container');
            const actionButtons = document.querySelector('.action-buttons');
            const modalContents = document.querySelectorAll('.modal-content');
            const messages = document.querySelectorAll('.message');
            const errors = document.querySelectorAll('.error');
            const sortFilter = document.querySelector('.sort-filter');
            const filterButtons = document.querySelector('.filter-buttons');
            const table = document.querySelector('table');
            const draggables = document.querySelectorAll('tr.draggable');
            const editables = document.querySelectorAll('.editable');
            const innerCircle = document.querySelector('.inner-circle');
            const percentage = document.querySelector('.percentage');
            const modeToggle = document.getElementById('modeToggle');

            body.classList.toggle('dark-mode');
            header.classList.toggle('dark-mode');
            authLinks.classList.toggle('dark-mode');
            container.classList.toggle('dark-mode');
            actionButtons.classList.toggle('dark-mode');
            modalContents.forEach(modal => modal.classList.toggle('dark-mode'));
            messages.forEach(msg => msg.classList.toggle('dark-mode'));
            errors.forEach(err => err.classList.toggle('dark-mode'));
            sortFilter.classList.toggle('dark-mode');
            filterButtons.classList.toggle('dark-mode');
            table.classList.toggle('dark-mode');
            draggables.forEach(draggable => draggable.classList.toggle('dark-mode'));
            editables.forEach(editable => editable.classList.toggle('dark-mode'));
            innerCircle.classList.toggle('dark-mode');
            percentage.classList.toggle('dark-mode');

            if (body.classList.contains('dark-mode')) {
                modeToggle.innerHTML = '<i class="fa-solid fa-sun"></i>';
                localStorage.setItem('theme', 'dark');
            } else {
                modeToggle.innerHTML = '<i class="fa-solid fa-moon"></i>';
                localStorage.setItem('theme', 'light');
            }
        }

        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.getElementById('createMessage').style.display = 'none';
            document.getElementById('deleteMessage').style.display = 'none';
        }
        function toggleEdit() {
            if (!isEditing) {
                isEditing = true;
                document.querySelectorAll('.editable').forEach(el => el.style.display = 'block');
                document.querySelectorAll('.static').forEach(el => el.style.display = 'none');
                document.querySelector('.edit-button').style.display = 'none';
                document.querySelector('.save-button').style.display = 'inline-block';
                document.querySelector('.cancel-button').style.display = 'inline-block';
                clearInterval(updateInterval);
            }
        }
        function saveEdits() {
            if (confirm('Are you sure you want to save all changes?')) {
                document.getElementById('taskForm').submit();
                exitEditMode();
            }
        }
        function cancelEdit() {
            if (confirm('Are you sure you want to cancel? All unsaved changes will be lost.')) {
                exitEditMode();
                updateTasks();
            }
        }
        function exitEditMode() {
            isEditing = false;
            document.querySelectorAll('.editable').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.static').forEach(el => el.style.display = 'block');
            document.querySelector('.edit-button').style.display = 'inline-block';
            document.querySelector('.save-button').style.display = 'none';
            document.querySelector('.cancel-button').style.display = 'none';
            updateInterval = setInterval(updateTasks, 10000);
        }
        function showCongratMessage(message) {
            const congratDiv = document.getElementById('congratMessage');
            congratDiv.textContent = message;
            congratDiv.style.display = 'block';
            setTimeout(() => {
                congratDiv.style.display = 'none';
            }, 3000);
        }
        function updateCompleted(taskId, isChecked) {
            pendingUpdates[taskId] = isChecked;
            const checkbox = document.querySelector(`input[name="tasks[${taskId}][completed]"]`);
            const row = checkbox.closest('tr');
            checkbox.checked = isChecked;
            fetch('task.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `ajax_update_completed=1&task_id=${taskId}&completed=${isChecked ? 1 : 0}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log(`Task ${taskId} updated to ${isChecked ? 'completed' : 'incomplete'}`);
                    updateFocusTree(data.total_tasks, data.completed_tasks);
                    const currentFilter = '<?php echo $filter; ?>';
                    if (currentFilter === 'completed' && !isChecked) {
                        row.style.display = 'none';
                    } else if (currentFilter === 'incomplete' && isChecked) {
                        row.style.display = 'none';
                    }
                    if (isChecked && data.congrat_message) {
                        showCongratMessage(data.congrat_message);
                    }
                    updateTasks();
                } else {
                    console.error('Update failed:', data.error);
                    checkbox.checked = !isChecked;
                    delete pendingUpdates[taskId];
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                checkbox.checked = !isChecked;
                delete pendingUpdates[taskId];
            });
        }
        function updateFocusTree(totalTasks, completedTasks) {
            const percentage = totalTasks > 0 ? (completedTasks / totalTasks * 100).toFixed(2) : "0.00";
            const degrees = percentage * 3.6;
            const progressCircle = document.getElementById('progressCircle');
            const percentageText = document.getElementById('percentageText');
            
            progressCircle.style.background = `conic-gradient(#4CAF50 ${degrees}deg, #ddd ${degrees}deg 360deg)`;
            percentageText.textContent = `${percentage}%`;
        }
        function createTask() {
            const form = document.getElementById('createTaskForm');
            const formData = new FormData(form);
            formData.append('ajax_create_task', 1);
            fetch('task.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const message = document.getElementById('createMessage');
                if (data.success) {
                    message.textContent = 'Task created successfully!';
                    message.className = 'message';
                    message.style.display = 'block';
                    setTimeout(() => closeModal('createModal'), 1000);
                    updateTasks();
                    const totalTasks = data.total_tasks;
                    const completedTasks = data.completed_tasks;
                    updateFocusTree(totalTasks, completedTasks);
                } else {
                    message.textContent = 'Error: ' + data.error;
                    message.className = 'message error';
                    message.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                document.getElementById('createMessage').textContent = 'Error: Network issue';
                document.getElementById('createMessage').className = 'message error';
                document.getElementById('createMessage').style.display = 'block';
            });
        }
        function deleteTask() {
            if (confirm('Are you sure you want to delete this task?')) {
                const form = document.getElementById('deleteTaskForm');
                const formData = new FormData(form);
                formData.append('ajax_delete_task', 1);
                fetch('task.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    const message = document.getElementById('deleteMessage');
                    if (data.success) {
                        message.textContent = 'Task deleted successfully!';
                        message.className = 'message';
                        message.style.display = 'block';
                        setTimeout(() => closeModal('deleteModal'), 1000);
                        updateTasks();
                        const totalTasks = data.total_tasks;
                        const completedTasks = data.completed_tasks;
                        updateFocusTree(totalTasks, completedTasks);
                        const row = document.querySelector(`tr[data-id="${formData.get('task_id')}"]`);
                        if (row) row.remove();
                    } else {
                        message.textContent = 'Error: ' + data.error;
                        message.className = 'message error';
                        message.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    document.getElementById('deleteMessage').textContent = 'Error: Network issue';
                    document.getElementById('deleteMessage').className = 'message error';
                    document.getElementById('deleteMessage').style.display = 'block';
                });
            }
        }
        function updateTasks() {
            fetch('task.php?ajax=1&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>&tag=<?php echo urlencode($tag_filter); ?>&date_range=<?php echo $date_range; ?>&custom_start=<?php echo urlencode($custom_start); ?>&custom_end=<?php echo urlencode($custom_end); ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>')
                .then(response => response.text())
                .then(data => {
                    const tbody = document.querySelector('tbody');
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = '<table>' + data + '</table>';
                    const newTbody = tempDiv.querySelector('tbody');
                    tbody.innerHTML = newTbody.innerHTML;
                    const newCheckboxes = tbody.querySelectorAll('input[type="checkbox"]');
                    newCheckboxes.forEach(cb => {
                        const taskId = cb.name.match(/\d+/)[0];
                        if (pendingUpdates[taskId] !== undefined) {
                            cb.checked = pendingUpdates[taskId];
                        }
                        cb.addEventListener('change', function(e) {
                            e.preventDefault();
                            updateCompleted(taskId, this.checked);
                        });
                    });
                    reattachDragListeners();
                    fetch('task.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'ajax_get_counts=1'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateFocusTree(data.total_tasks, data.completed_tasks);
                        }
                    });
                    if (isEditing) {
                        document.querySelectorAll('.editable').forEach(el => el.style.display = 'block');
                        document.querySelectorAll('.static').forEach(el => el.style.display = 'none');
                        document.querySelector('.edit-button').style.display = 'none';
                        document.querySelector('.save-button').style.display = 'inline-block';
                        document.querySelector('.cancel-button').style.display = 'inline-block';
                    }
                    if (document.body.classList.contains('dark-mode')) {
                        const draggables = document.querySelectorAll('tr.draggable');
                        const editables = document.querySelectorAll('.editable');
                        draggables.forEach(draggable => draggable.classList.add('dark-mode'));
                        editables.forEach(editable => editable.classList.add('dark-mode'));
                        table.classList.add('dark-mode');
                    }
                });
        }
        function reattachDragListeners() {
            const tbody = document.querySelector('tbody');
            let dragged;

            tbody.addEventListener('dragstart', (e) => {
                if (!isEditing) {
                    dragged = e.target.closest('tr');
                    dragged.classList.add('dragging');
                }
            });

            tbody.addEventListener('dragend', (e) => {
                if (!isEditing) {
                    dragged.classList.remove('dragging');
                    const order = Array.from(tbody.querySelectorAll('tr')).map(tr => tr.dataset.id).join(',');
                    document.getElementById('customOrder').value = order;
                    fetch('task.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'custom_order=' + encodeURIComponent(order)
                    });
                }
            });

            tbody.addEventListener('dragover', (e) => {
                if (!isEditing) {
                    e.preventDefault();
                }
            });

            tbody.addEventListener('drop', (e) => {
                if (!isEditing) {
                    e.preventDefault();
                    const target = e.target.closest('tr');
                    if (target && dragged !== target) {
                        if (e.clientY > target.getBoundingClientRect().top + target.offsetHeight / 2) {
                            target.parentNode.insertBefore(dragged, target.nextSibling);
                        } else {
                            target.parentNode.insertBefore(dragged, target);
                        }
                    }
                }
            });
        }
        function enableCustomOrder() {
            if (!isEditing && document.getElementById('sort_by').value === 'custom') {
                reattachDragListeners();
            }
        }
        function searchTasks() {
            const searchValue = document.getElementById('search').value;
            window.location = 'task.php?filter=<?php echo $filter; ?>&search=' + encodeURIComponent(searchValue) + '&tag=<?php echo urlencode($tag_filter); ?>&date_range=<?php echo $date_range; ?>&custom_start=<?php echo urlencode($custom_start); ?>&custom_end=<?php echo urlencode($custom_end); ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>';
        }
        document.addEventListener('DOMContentLoaded', function() {
            updateInterval = setInterval(updateTasks, 10000);
            document.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                cb.addEventListener('change', function(e) {
                    e.preventDefault();
                    const taskId = this.name.match(/\d+/)[0];
                    updateCompleted(taskId, this.checked);
                });
            });
            reattachDragListeners();
            updateFocusTree(<?php echo $total_tasks; ?>, <?php echo $completed_tasks; ?>);

            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                toggleDarkMode();
            }
        });
        function updateCustomRange() {
            const start = document.getElementById('custom_start').value;
            const end = document.getElementById('custom_end').value;
            if (start && end) {
                window.location = 'task.php?filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>&tag=<?php echo urlencode($tag_filter); ?>&date_range=custom&custom_start=' + encodeURIComponent(start) + '&custom_end=' + encodeURIComponent(end) + '&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>';
            }
        }
    </script>
</head>
<body>
    <div class="header">
        <div style="display:flex;">
            <div><img src="logo.jpg" style="height:50px"></div>
            <div style="width:20px;"></div>
            <div class="trophy-icons">
            <?php if ($first_trophy) { ?>
                <img src="<?php echo $first_trophy; ?>" alt="<?php echo $first_trophy_alt; ?>" title="<?php echo $first_trophy_alt; ?>" tabindex="0">
            <?php } ?>
            <?php if ($second_trophy) { ?>
                <img src="<?php echo $second_trophy; ?>" alt="<?php echo $second_trophy_alt; ?>" title="<?php echo $second_trophy_alt; ?>" tabindex="0">
            <?php } ?>
            <?php if ($third_trophy) { ?>
                <img src="<?php echo $third_trophy; ?>" alt="<?php echo $third_trophy_alt; ?>" title="<?php echo $third_trophy_alt; ?>" tabindex="0">
            <?php } ?>
            </div>
        </div>
        <div class="auth-links">
            <button id="modeToggle" class="mode-toggle" onclick="toggleDarkMode()"><i class="fa-solid fa-moon"></i></button>
            <div style="width:5px"></div>
            <span style="font-size: 18px">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="calendar.php" class="calendar-button" style="font-size:18px">View Calendar</a>
            <a href="logout.php" id="logout" style="font-size:18px">Logout</a>
        </div>
    </div>
    <div class="container">
        <h2 style="margin-top:0px;margin-left:5px;font-size:30px">My Planner</h2>
        <?php if (!empty($alert_message)) { ?>
            <div class="alert-box" style="text-align: left;padding=5px;"><?php echo " ".$alert_message; ?></div>
        <?php } ?>
        <div class="focus-tree">
            <div class="progress-circle" id="progressCircle" style="background: conic-gradient(#4CAF50 <?php echo $percentage * 3.6; ?>deg, #ddd <?php echo $percentage * 3.6; ?>deg 360deg);">
                <div class="inner-circle">
                    <div class="percentage" id="percentageText"><?php echo $percentage; ?>%</div>
                </div>
            </div>
        </div>
        <div class="action-buttons">
            <button onclick="openModal('createModal')">Create Task ➕</button>
            <button onclick="openModal('deleteModal')">Delete Task ➖</button>
            <button class="edit-button" onclick="toggleEdit()">Edit ✏️</button>
            <button class="save-button" style="display: none;" onclick="saveEdits()">Save ✅</button>
            <button class="cancel-button" style="display: none;" onclick="cancelEdits()">Cancel ❌</button>
        </div>
        <div id="congratMessage" class="congrat-message"></div>
        <div id="createModal" class="modal">
            <div class="modal-content">
                <h2>Create Task</h2>
                <p id="createMessage" class="message" style="display: none;"></p>
                <?php if (!empty($create_message)) { ?>
                    <p class="message <?php echo strpos($create_message, 'Error') !== false ? 'error' : ''; ?>">
                        <?php echo $create_message; ?>
                    </p>
                <?php } ?>
                <form id="createTaskForm" action="task.php" method="POST">
                    <label>Task Name:</label>
                    <input type="text" name="title" required>
                    <label>Description:</label>
                    <textarea name="description" rows="3"></textarea>
                    <label>Due Date and Time:</label>
                    <input type="datetime-local" name="due_date" required>
                    <label>Priority:</label>
                    <select name="priority" required>
                        <option value="High">High</option>
                        <option value="Medium">Medium</option>
                        <option value="Low">Low</option>
                    </select>
                    <label>Tag:</label>
                    <input type="text" name="tags" placeholder="e.g., quiz, assignment">
                    <label>Repeat:</label>
                    <select name="repeat" required>
                        <option value="None">None</option>
                        <option value="Daily">Daily</option>
                        <option value="Weekly">Weekly</option>
                        <option value="Biweekly">Biweekly</option>
                        <option value="Monthly">Monthly</option>
                        <option value="Quarter">Quarter</option>
                    </select>
                    <label>Repeat Until:</label>
                    <input type="date" name="repeat_until">
                    <button type="submit" name="create_task">Add Task</button>
                    <button type="button" onclick="closeModal('createModal')">Cancel</button>
                </form>
            </div>
        </div>
        <div id="deleteModal" class="modal">
            <div class="modal-content">
                <h2>Delete Task - </h2>
                <p id="deleteMessage" class="message" style="display: none;"></p>
                <?php if (!empty($delete_message)) { ?>
                    <p class="message <?php echo strpos($delete_message, 'Error') !== false ? 'error' : ''; ?>">
                        <?php echo $delete_message; ?>
                    </p>
                <?php } ?>
                <form id="deleteTaskForm" action="task.php" method="POST">
                    <label>Select Task to Delete:</label>
                    <select name="task_id" required>
                        <option value="">-- Select Task --</option>
                        <?php foreach ($all_tasks as $task) { ?>
                            <option value="<?php echo $task['id']; ?>"><?php echo htmlspecialchars($task['title']); ?> (ID: <?php echo $task['id']; ?>)</option>
                        <?php } ?>
                    </select>
                    <button type="submit" name="delete_task" onclick="return confirm('Are you sure you want to delete this task?')">Delete</button>
                    <button type="button" onclick="closeModal('deleteModal')">Cancel</button>
                </form>
            </div>
        </div>
        <?php if (!empty($edit_message)) { ?>
            <p class="message <?php echo strpos($edit_message, 'Error') !== false ? 'error' : ''; ?>">
                <?php echo $edit_message; ?>
            </p>
        <?php } ?>
        <?php if (!empty($delete_message)) { ?>
            <p class="message <?php echo strpos($delete_message, 'Error') !== false ? 'error' : ''; ?>">
                <?php echo $delete_message; ?>
            </p>
        <?php } ?>
        <div class="sort-filter">
            <input type="text" id="search" placeholder="Search by task name" value="<?php echo htmlspecialchars($search); ?>">
            <button onclick="searchTasks()">Search</button>
            <span>Tag Filter:</span>
            <select onchange="window.location='task.php?filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>&tag='+encodeURIComponent(this.value)+'&date_range=<?php echo $date_range; ?>&custom_start=<?php echo urlencode($custom_start); ?>&custom_end=<?php echo urlencode($custom_end); ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>'">
                <option value="">All Tags</option>
                <?php foreach ($tags as $tag) { ?>
                    <option value="<?php echo htmlspecialchars($tag); ?>" <?php echo $tag_filter === $tag ? 'selected' : ''; ?>><?php echo htmlspecialchars($tag); ?></option>
                <?php } ?>
            </select>
            <span>Date Range:</span>
            <select id="date_range" onchange="if(this.value === 'custom') document.getElementById('custom_range').style.display = 'block'; else { document.getElementById('custom_range').style.display = 'none'; window.location='task.php?filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>&tag=<?php echo urlencode($tag_filter); ?>&date_range='+this.value+'&custom_start=<?php echo urlencode($custom_start); ?>&custom_end=<?php echo urlencode($custom_end); ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>'; }">
                <option value="">All Dates</option>
                <option value="week" <?php echo $date_range === 'week' ? 'selected' : ''; ?>>This Week</option>
                <option value="month" <?php echo $date_range === 'month' ? 'selected' : ''; ?>>This Month</option>
                <option value="year" <?php echo $date_range === 'year' ? 'selected' : ''; ?>>This Year</option>
                <option value="custom" <?php echo $date_range === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
            </select>
            <div id="custom_range" style="display: <?php echo $date_range === 'custom' ? 'block' : 'none'; ?>;">
                <input type="date" id="custom_start" value="<?php echo htmlspecialchars($custom_start); ?>" onchange="updateCustomRange()">
                <input type="date" id="custom_end" value="<?php echo htmlspecialchars($custom_end); ?>" onchange="updateCustomRange()">
            </div>
            <span>Sort By:</span>
            <select id="sort_by" onchange="window.location='task.php?filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>&tag=<?php echo urlencode($tag_filter); ?>&date_range=<?php echo $date_range; ?>&custom_start=<?php echo urlencode($custom_start); ?>&custom_end=<?php echo urlencode($custom_end); ?>&sort_by='+this.value+'&sort_order=<?php echo $sort_order; ?>'">
                <option value="id" <?php echo $sort_by === 'id' ? 'selected' : ''; ?>>ID</option>
                <option value="name" <?php echo $sort_by === 'name' ? 'selected' : ''; ?>>Name</option>
                <option value="due_date" <?php echo $sort_by === 'due_date' ? 'selected' : ''; ?>>Due Date</option>
                <option value="priority" <?php echo $sort_by === 'priority' ? 'selected' : ''; ?>>Priority</option>
                <option value="tags" <?php echo $sort_by === 'tags' ? 'selected' : ''; ?>>Tags</option>
                <option value="custom" <?php echo $sort_by === 'custom' ? 'selected' : ''; ?>>Custom Order</option>
            </select>
            <select onchange="window.location='task.php?filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>&tag=<?php echo urlencode($tag_filter); ?>&date_range=<?php echo $date_range; ?>&custom_start=<?php echo urlencode($custom_start); ?>&custom_end=<?php echo urlencode($custom_end); ?>&sort_by=<?php echo $sort_by; ?>&sort_order='+this.value">
                <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Descending</option>
            </select>
            <div class="filter-buttons">
                <button onclick="window.location='task.php?filter=all&search=<?php echo urlencode($search); ?>&tag=<?php echo urlencode($tag_filter); ?>&date_range=<?php echo $date_range; ?>&custom_start=<?php echo urlencode($custom_start); ?>&custom_end=<?php echo urlencode($custom_end); ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>'" class="<?php echo $filter === 'all' ? 'active' : ''; ?>">All Tasks</button>
                <button onclick="window.location='task.php?filter=completed&search=<?php echo urlencode($search); ?>&tag=<?php echo urlencode($tag_filter); ?>&date_range=<?php echo $date_range; ?>&custom_start=<?php echo urlencode($custom_start); ?>&custom_end=<?php echo urlencode($custom_end); ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>'" class="<?php echo $filter === 'completed' ? 'active' : ''; ?>">Completed</button>
                <button onclick="window.location='task.php?filter=incomplete&search=<?php echo urlencode($search); ?>&tag=<?php echo urlencode($tag_filter); ?>&date_range=<?php echo $date_range; ?>&custom_start=<?php echo urlencode($custom_start); ?>&custom_end=<?php echo urlencode($custom_end); ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>'" class="<?php echo $filter === 'incomplete' ? 'active' : ''; ?>">Not Completed</button>
            </div>
        </div>
        <form id="taskForm" action="task.php" method="POST">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Task Name</th>
                        <th>Tags</th>
                        <th>Completed</th>
                        <th>Priority</th>
                        <th>Due Date</th>
                        <th style="width:200px">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tasks)) { ?>
                        <tr><td colspan="7">No tasks match the current filter.</td></tr>
                    <?php } else { ?>
                        <?php foreach ($tasks as $task) { ?>
                            <tr class="draggable priority-<?php echo strtolower($task['priority']); ?>" data-id="<?php echo $task['id']; ?>" draggable="true">
                                <td><?php echo htmlspecialchars($task['id']); ?></td>
                                <td>
                                    <span class="static"><?php echo htmlspecialchars($task['title']); ?></span>
                                    <input class="editable" style="display: none;" type="text" name="tasks[<?php echo $task['id']; ?>][title]" value="<?php echo htmlspecialchars($task['title']); ?>">
                                </td>
                                <td>
                                    <span class="static"><?php echo htmlspecialchars($task['tags'] ?? ''); ?></span>
                                    <input class="editable" style="display: none;" type="text" name="tasks[<?php echo $task['id']; ?>][tags]" value="<?php echo htmlspecialchars($task['tags'] ?? ''); ?>">
                                </td>
                                <td>
                                    <input type="checkbox" name="tasks[<?php echo $task['id']; ?>][completed]" <?php echo $task['completed'] ? 'checked' : ''; ?>>
                                </td>
                                <td>
                                    <div class="priority-box"></div>
                                    <select class="editable" style="display: none;" name="tasks[<?php echo $task['id']; ?>][priority]">
                                        <option value="High" <?php echo $task['priority'] === 'High' ? 'selected' : ''; ?>>High</option>
                                        <option value="Medium" <?php echo $task['priority'] === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                                        <option value="Low" <?php echo $task['priority'] === 'Low' ? 'selected' : ''; ?>>Low</option>
                                    </select>
                                </td>
                                <td>
                                    <span class="static"><?php echo htmlspecialchars(substr($task['due_date'], 0, 16)); ?></span>
                                    <input class="editable" style="display: none;" type="datetime-local" name="tasks[<?php echo $task['id']; ?>][due_date]" value="<?php echo htmlspecialchars(substr(str_replace(' ', 'T', $task['due_date']), 0, 16)); ?>">
                                </td>
                                <td>
                                    <span class="static"><?php echo htmlspecialchars($task['description'] ?? ''); ?></span>
                                    <textarea class="editable" style="display: none;" name="tasks[<?php echo $task['id']; ?>][description]"><?php echo htmlspecialchars($task['description'] ?? ''); ?></textarea>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
            <input type="hidden" id="customOrder" name="custom_order">
            <input type="hidden" name="save_tasks">
        </form>
    </div>
    <?php if (isset($_GET['ajax'])) { ?>
        <?php ob_start(); ?>
        <?php if (empty($tasks)) { ?>
            <tr><td colspan="7">No tasks match the current filter.</td></tr>
        <?php } else { ?>
            <?php foreach ($tasks as $task) { ?>
                <tr class="draggable priority-<?php echo strtolower($task['priority']); ?>" data-id="<?php echo $task['id']; ?>" draggable="true">
                    <td><?php echo htmlspecialchars($task['id']); ?></td>
                    <td>
                        <span class="static"><?php echo htmlspecialchars($task['title']); ?></span>
                        <input class="editable" style="display: none;" type="text" name="tasks[<?php echo $task['id']; ?>][title]" value="<?php echo htmlspecialchars($task['title']); ?>">
                    </td>
                    <td>
                        <span class="static"><?php echo htmlspecialchars($task['tags'] ?? ''); ?></span>
                        <input class="editable" style="display: none;" type="text" name="tasks[<?php echo $task['id']; ?>][tags]" value="<?php echo htmlspecialchars($task['tags'] ?? ''); ?>">
                    </td>
                    <td>
                        <input type="checkbox" name="tasks[<?php echo $task['id']; ?>][completed]" <?php echo $task['completed'] ? 'checked' : ''; ?>>
                    </td>
                    <td>
                        <div class="priority-box"></div>
                        <select class="editable" style="display: none;" name="tasks[<?php echo $task['id']; ?>][priority]">
                            <option value="High" <?php echo $task['priority'] === 'High' ? 'selected' : ''; ?>>High</option>
                            <option value="Medium" <?php echo $task['priority'] === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="Low" <?php echo $task['priority'] === 'Low' ? 'selected' : ''; ?>>Low</option>
                        </select>
                    </td>
                    <td>
                        <span class="static"><?php echo htmlspecialchars(substr($task['due_date'], 0, 16)); ?></span>
                        <input class="editable" style="display: none;" type="datetime-local" name="tasks[<?php echo $task['id']; ?>][due_date]" value="<?php echo htmlspecialchars(substr(str_replace(' ', 'T', $task['due_date']), 0, 16)); ?>">
                    </td>
                    <td>
                        <span class="static"><?php echo htmlspecialchars($task['description'] ?? ''); ?></span>
                        <textarea class="editable" style="display: none;" name="tasks[<?php echo $task['id']; ?>][description]"><?php echo htmlspecialchars($task['description'] ?? ''); ?></textarea>
                    </td>
                </tr>
            <?php } ?>
        <?php } ?>
        <?php echo ob_get_clean(); exit(); ?>
    <?php } ?>
</body>
</html>