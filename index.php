<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_task'])) {
        
        $stmt = $conn->prepare("INSERT INTO tasks (title, description, due_date, priority, status) VALUES (:title, :description, :due_date, :priority, :status)");
        $stmt->execute([
            ':title' => $_POST['title'],
            ':description' => $_POST['description'],
            ':due_date' => $_POST['due_date'],
            ':priority' => $_POST['priority'],
            ':status' => $_POST['status'],
        ]);
        $message = "Task added successfully!";
    } elseif (isset($_POST['update_task'])) {
        
        $stmt = $conn->prepare("UPDATE tasks SET title = :title, description = :description, due_date = :due_date, priority = :priority, status = :status WHERE id = :id");
        $stmt->execute([
            ':title' => $_POST['title'],
            ':description' => $_POST['description'],
            ':due_date' => $_POST['due_date'],
            ':priority' => $_POST['priority'],
            ':status' => $_POST['status'],
            ':id' => $_POST['task_id'],
        ]);
        $message = "Task updated successfully!";
    }
}

if (isset($_GET['delete_id'])) {
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = :id");
    $stmt->execute([':id' => $_GET['delete_id']]);
    $message = "Task deleted successfully!";
}

$sort = $_GET['sort'] ?? 'due_date';
$filter_priority = $_GET['filter_priority'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';

$query = "SELECT * FROM tasks WHERE 1=1";
if ($filter_priority) {
    $query .= " AND priority = :priority";
}
if ($filter_status) {
    $query .= " AND status = :status";
}
$query .= " ORDER BY $sort";

$stmt = $conn->prepare($query);
if ($filter_priority) {
    $stmt->bindValue(':priority', $filter_priority);
}
if ($filter_status) {
    $stmt->bindValue(':status', $filter_status);
}
$stmt->execute();
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .modal-content {
            background: #f9f9f9;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Task Management System</h1>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#taskModal" onclick="resetForm()">
            Add New Task
        </button>

        <div class="mb-3">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="sort" class="form-label">Sort By</label>
                    <select class="form-select" id="sort" name="sort">
                        <option value="due_date" <?php echo ($sort === 'due_date') ? 'selected' : ''; ?>>Due Date</option>
                        <option value="priority" <?php echo ($sort === 'priority') ? 'selected' : ''; ?>>Priority</option>
                        <option value="status" <?php echo ($sort === 'status') ? 'selected' : ''; ?>>Status</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter_priority" class="form-label">Filter by Priority</label>
                    <select class="form-select" id="filter_priority" name="filter_priority">
                        <option value="">All</option>
                        <option value="Low" <?php echo ($filter_priority === 'Low') ? 'selected' : ''; ?>>Low</option>
                        <option value="Medium" <?php echo ($filter_priority === 'Medium') ? 'selected' : ''; ?>>Medium</option>
                        <option value="High" <?php echo ($filter_priority === 'High') ? 'selected' : ''; ?>>High</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter_status" class="form-label">Filter by Status</label>
                    <select class="form-select" id="filter_status" name="filter_status">
                        <option value="">All</option>
                        <option value="To Do" <?php echo ($filter_status === 'To Do') ? 'selected' : ''; ?>>To Do</option>
                        <option value="In Progress" <?php echo ($filter_status === 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                        <option value="Done" <?php echo ($filter_status === 'Done') ? 'selected' : ''; ?>>Done</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary mt-4">Apply</button>
                </div>
            </form>
        </div>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Due Date</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($task['title']); ?></td>
                        <td><?php echo htmlspecialchars($task['description']); ?></td>
                        <td><?php echo htmlspecialchars($task['due_date']); ?></td>
                        <td><?php echo htmlspecialchars($task['priority']); ?></td>
                        <td><?php echo htmlspecialchars($task['status']); ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#taskModal" onclick="editTask(<?php echo $task['id']; ?>)">Edit</button>
                            <a href="index.php?delete_id=<?php echo $task['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this task?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="modal fade" id="taskModal" tabindex="-1" aria-labelledby="taskModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="taskModalLabel">Add New Task</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" id="taskForm">
                            <input type="hidden" name="task_id" id="task_id">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" required>
                            </div>
                            <div class="mb-3">
                                <label for="priority" class="form-label">Priority</label>
                                <select class="form-select" id="priority" name="priority" required>
                                    <option value="Low">Low</option>
                                    <option value="Medium">Medium</option>
                                    <option value="High">High</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="To Do">To Do</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Done">Done</option>
                                </select>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" name="add_task" class="btn btn-primary">Add Task</button>
                                <button type="submit" name="update_task" class="btn btn-primary" style="display: none;">Update Task</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetForm() {
            document.getElementById('taskForm').reset();
            document.getElementById('taskModalLabel').innerText = 'Add New Task';
            document.getElementById('task_id').value = '';
            document.querySelector('button[name="add_task"]').style.display = 'inline-block';
            document.querySelector('button[name="update_task"]').style.display = 'none';
        }

        function editTask(taskId) {
            fetch(`get_task.php?id=${taskId}`)
                .then(response => response.json())
                .then(task => {
                    document.getElementById('taskModalLabel').innerText = 'Edit Task';
                    document.getElementById('task_id').value = task.id;
                    document.getElementById('title').value = task.title;
                    document.getElementById('description').value = task.description;
                    document.getElementById('due_date').value = task.due_date;
                    document.getElementById('priority').value = task.priority;
                    document.getElementById('status').value = task.status;

                    document.querySelector('button[name="add_task"]').style.display = 'none';
                    document.querySelector('button[name="update_task"]').style.display = 'inline-block';
                });
        }
    </script>
</body>
</html>