<?php
// Database connection parameters
$servername = "localhost"; // Replace with your actual database server
$username = "root";        // Replace with your database username
$password = "";            // Replace with your database password
$dbname = "todo_db";       // Replace with your database name

// Create connection
$conn = mysqli_connect($servername, $username, $password);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if database exists, if not create it
if (!mysqli_select_db($conn, $dbname)) {
    $sql = "CREATE DATABASE $dbname";
    if (mysqli_query($conn, $sql)) {
        mysqli_select_db($conn, $dbname);
    } else {
        die("Error creating database: " . mysqli_error($conn));
    }
}

// Create tasks table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS tasks (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    task VARCHAR(255) NOT NULL,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!mysqli_query($conn, $sql)) {
    die("Error creating table: " . mysqli_error($conn));
}

// Initialize variables
$id = 0;
$task = "";
$status = "pending";
$priority = "medium";
$update = false;
$error = "";
$success = "";

// Create task
if (isset($_POST['add'])) {
    $task = mysqli_real_escape_string($conn, $_POST['task']);
    $priority = mysqli_real_escape_string($conn, $_POST['priority']);
    
    if (empty($task)) {
        $error = "Task cannot be empty!";
    } else {
        $sql = "INSERT INTO tasks (task, priority) VALUES ('$task', '$priority')";
        if (mysqli_query($conn, $sql)) {
            $success = "Task added successfully!";
            $task = "";
            $priority = "medium";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}

// Read task (for edit)
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $update = true;
    $result = mysqli_query($conn, "SELECT * FROM tasks WHERE id=$id");
    
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_array($result);
        $task = $row['task'];
        $status = $row['status'];
        $priority = $row['priority'];
    }
}

// Update task
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $task = mysqli_real_escape_string($conn, $_POST['task']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $priority = mysqli_real_escape_string($conn, $_POST['priority']);
    
    if (empty($task)) {
        $error = "Task cannot be empty!";
    } else {
        $sql = "UPDATE tasks SET task='$task', status='$status', priority='$priority' WHERE id=$id";
        if (mysqli_query($conn, $sql)) {
            $success = "Task updated successfully!";
            $update = false;
            $task = "";
            $status = "pending";
            $priority = "medium";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}

// Delete task
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM tasks WHERE id=$id";
    if (mysqli_query($conn, $sql)) {
        $success = "Task deleted successfully!";
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}

// Toggle task status
if (isset($_GET['complete'])) {
    $id = $_GET['complete'];
    $sql = "UPDATE tasks SET status = CASE WHEN status = 'pending' THEN 'completed' ELSE 'pending' END WHERE id=$id";
    mysqli_query($conn, $sql);
}

// Filter tasks
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$filterSql = "";

if ($filter == 'pending') {
    $filterSql = "WHERE status = 'pending'";
} else if ($filter == 'completed') {
    $filterSql = "WHERE status = 'completed'";
}

// Sort tasks
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sortDirection = isset($_GET['direction']) ? $_GET['direction'] : 'DESC';

// Fetch all tasks
$sql = "SELECT * FROM tasks $filterSql ORDER BY $sort $sortDirection";
$result = mysqli_query($conn, $sql);
$tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get counts
$totalTasks = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tasks"));
$pendingTasks = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tasks WHERE status = 'pending'"));
$completedTasks = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tasks WHERE status = 'completed'"));

// Close connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMaster - Your Personal To-Do App</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --dark-color: #3a0ca3;
            --light-color: #f8f9fa;
            --danger-color: #ef476f;
            --success-color: #06d6a0;
            --warning-color: #ffd166;
            --info-color: #118ab2;
            --gray-color: #adb5bd;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fb;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(to right, var(--primary-color), var(--dark-color));
            color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .app-title {
            font-size: 2.5rem;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }

        .stats {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .stat-card {
            background: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            flex: 1;
            margin: 0 10px;
            min-width: 150px;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }

        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 5px;
            color: var(--primary-color);
        }

        .stat-card p {
            color: var(--gray-color);
            font-size: 0.9rem;
        }

        .task-form {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }

        input[type="text"],
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: var(--transition);
        }

        input[type="text"]:focus,
        select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.3);
        }

        .btn {
            padding: 12px 20px;
            cursor: pointer;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--dark-color);
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #d62e59;
        }

        .btn-warning {
            background-color: var(--warning-color);
            color: #333;
        }

        .btn-warning:hover {
            background-color: #e6bc5c;
        }

        .btn-sm {
            padding: 8px 12px;
            font-size: 0.9rem;
        }

        .filters {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .filter-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .filter-link {
            padding: 8px 15px;
            background-color: white;
            color: #555;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9rem;
            transition: var(--transition);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .filter-link:hover,
        .filter-link.active {
            background-color: var(--primary-color);
            color: white;
        }

        .task-list {
            list-style: none;
        }

        .task-item {
            background: white;
            margin-bottom: 15px;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .task-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }

        .task-item.completed {
            border-left: 5px solid var(--success-color);
        }

        .task-item.pending {
            border-left: 5px solid var(--warning-color);
        }

        .task-content {
            flex: 1;
            padding-right: 20px;
        }

        .task-title {
            font-size: 1.1rem;
            margin-bottom: 5px;
            color: #333;
        }

        .task-item.completed .task-title {
            text-decoration: line-through;
            color: var(--gray-color);
        }

        .task-meta {
            font-size: 0.8rem;
            color: var(--gray-color);
            display: flex;
            gap: 15px;
        }

        .task-priority {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .priority-high {
            background-color: rgba(239, 71, 111, 0.15);
            color: var(--danger-color);
        }

        .priority-medium {
            background-color: rgba(255, 209, 102, 0.15);
            color: #e6bc5c;
        }

        .priority-low {
            background-color: rgba(6, 214, 160, 0.15);
            color: var(--success-color);
        }

        .task-actions {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            transition: var(--transition);
        }

        .btn-complete {
            background-color: var(--success-color);
        }

        .btn-complete:hover {
            background-color: #05b989;
        }

        .btn-edit {
            background-color: var(--info-color);
        }

        .btn-edit:hover {
            background-color: #0e7a9b;
        }

        .btn-delete {
            background-color: var(--danger-color);
        }

        .btn-delete:hover {
            background-color: #d62e59;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .alert-success {
            background-color: rgba(6, 214, 160, 0.15);
            color: var(--success-color);
            border: 1px solid rgba(6, 214, 160, 0.3);
        }

        .alert-error {
            background-color: rgba(239, 71, 111, 0.15);
            color: var(--danger-color);
            border: 1px solid rgba(239, 71, 111, 0.3);
        }

        .no-tasks {
            text-align: center;
            margin: 50px 0;
            color: var(--gray-color);
        }

        footer {
            text-align: center;
            margin-top: 50px;
            padding: 20px;
            color: var(--gray-color);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }

            .form-row .form-group {
                width: 100%;
            }

            .task-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .task-actions {
                width: 100%;
                margin-top: 15px;
                justify-content: flex-end;
            }

            .filters {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1 class="app-title"><i class="fas fa-check-circle"></i> TaskMaster</h1>
            <p>Your personal task manager</p>
        </header>

        <div class="stats">
            <div class="stat-card">
                <h3><?php echo $totalTasks; ?></h3>
                <p>Total Tasks</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $pendingTasks; ?></h3>
                <p>Pending Tasks</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $completedTasks; ?></h3>
                <p>Completed Tasks</p>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if(!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if(!empty($error)): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Task Form -->
        <form class="task-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <h2><?php echo $update ? 'Update Task' : 'Add New Task'; ?></h2>
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            
            <div class="form-row">
                <div class="form-group" style="flex: 3;">
                    <label for="task">Task Description</label>
                    <input type="text" name="task" id="task" placeholder="Enter your task" value="<?php echo $task; ?>" required>
                </div>
                
                <div class="form-group" style="flex: 1;">
                    <label for="priority">Priority</label>
                    <select name="priority" id="priority">
                        <option value="low" <?php if($priority == 'low') echo 'selected'; ?>>Low</option>
                        <option value="medium" <?php if($priority == 'medium') echo 'selected'; ?>>Medium</option>
                        <option value="high" <?php if($priority == 'high') echo 'selected'; ?>>High</option>
                    </select>
                </div>
                
                <?php if($update): ?>
                <div class="form-group" style="flex: 1;">
                    <label for="status">Status</label>
                    <select name="status" id="status">
                        <option value="pending" <?php if($status == 'pending') echo 'selected'; ?>>Pending</option>
                        <option value="completed" <?php if($status == 'completed') echo 'selected'; ?>>Completed</option>
                    </select>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <?php if($update): ?>
                    <button type="submit" name="update" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Task
                    </button>
                    <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-warning">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                <?php else: ?>
                    <button type="submit" name="add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Task
                    </button>
                <?php endif; ?>
            </div>
        </form>

        <!-- Task Filters -->
        <div class="filters">
            <div class="filter-group">
                <strong>Status:</strong>
                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?filter=all" class="filter-link <?php if($filter == 'all') echo 'active'; ?>">All</a>
                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?filter=pending" class="filter-link <?php if($filter == 'pending') echo 'active'; ?>">Pending</a>
                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?filter=completed" class="filter-link <?php if($filter == 'completed') echo 'active'; ?>">Completed</a>
            </div>
            
            <div class="filter-group">
                <strong>Sort:</strong>
                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?filter=<?php echo $filter; ?>&sort=created_at&direction=DESC" class="filter-link <?php if($sort == 'created_at' && $sortDirection == 'DESC') echo 'active'; ?>">Newest</a>
                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?filter=<?php echo $filter; ?>&sort=created_at&direction=ASC" class="filter-link <?php if($sort == 'created_at' && $sortDirection == 'ASC') echo 'active'; ?>">Oldest</a>
                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?filter=<?php echo $filter; ?>&sort=priority&direction=DESC" class="filter-link <?php if($sort == 'priority' && $sortDirection == 'DESC') echo 'active'; ?>">Priority</a>
            </div>
        </div>

        <!-- Task List -->
        <?php if(count($tasks) > 0): ?>
            <ul class="task-list">
                <?php foreach($tasks as $task): ?>
                    <li class="task-item <?php echo $task['status']; ?>">
                        <div class="task-content">
                            <h3 class="task-title"><?php echo htmlspecialchars($task['task']); ?></h3>
                            <div class="task-meta">
                                <span><i class="far fa-calendar"></i> <?php echo date('M d, Y', strtotime($task['created_at'])); ?></span>
                                <span class="task-priority priority-<?php echo $task['priority']; ?>">
                                    <?php echo ucfirst($task['priority']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="task-actions">
                            <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?complete=<?php echo $task['id']; ?>" class="btn-action btn-complete">
                                <i class="fas <?php echo ($task['status'] == 'completed') ? 'fa-times' : 'fa-check'; ?>"></i>
                            </a>
                            <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?edit=<?php echo $task['id']; ?>" class="btn-action btn-edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?delete=<?php echo $task['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to delete this task?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="no-tasks">
                <i class="fas fa-tasks" style="font-size: 4rem; margin-bottom: 20px; color: #ddd;"></i>
                <h3>No tasks found</h3>
                <p>Start by adding a new task above</p>
            </div>
        <?php endif; ?>

        <footer>
            <p>TaskMaster &copy; <?php echo date('Y'); ?> - Your Personal To-Do App</p>
        </footer>
    </div>
</body>
</html>