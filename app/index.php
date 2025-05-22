<?php
require 'header.php';

// Handle actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $user_id = $_POST['user_id'] ?? null;
    
    switch ($action) {
        case 'create_user':
            $username = trim($_POST['username']);
            $password = trim($_POST['password']);
            $expiry_date = $_POST['expiry_date'];
            
            if (!empty($username) && !empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("INSERT INTO users (username, password, expiry_date) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $username, $hashed_password, $expiry_date);
                $stmt->execute();
                $success = "User created successfully!";
            } else {
                $error = "Username and password are required!";
            }
            break;
            
        case 'ban_user':
            $stmt = $conn->prepare("UPDATE users SET is_banned = TRUE WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $success = "User banned successfully!";
            break;
            
        case 'unban_user':
            $stmt = $conn->prepare("UPDATE users SET is_banned = FALSE WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $success = "User unbanned successfully!";
            break;
            
        case 'extend_date':
            $new_date = $_POST['new_date'];
            $stmt = $conn->prepare("UPDATE users SET expiry_date = ? WHERE id = ?");
            $stmt->bind_param("si", $new_date, $user_id);
            $stmt->execute();
            $success = "Expiry date extended successfully!";
            break;
            
        case 'delete_user':
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $success = "User deleted successfully!";
            break;
            
        case 'reset_hwid':
            $stmt = $conn->prepare("UPDATE users SET hwid = NULL WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $success = "HWID reset successfully!";
            break;
    }
}

// Get all users
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>User Management System</h2>
                <a href="?logout" class="btn btn-danger">Logout</a>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php elseif (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Create New User</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="create_user">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="text" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="expiry_date" class="form-label">Expiry Date</label>
                                    <input type="datetime-local" class="form-control" id="expiry_date" name="expiry_date" required>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Create User</button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5>User List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>HWID</th>
                                    <th>Expiry Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = $users->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo $user['hwid'] ? substr($user['hwid'], 0, 10) . '...' : 'Not set'; ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($user['expiry_date'])); ?></td>
                                        <td>
                                            <?php if ($user['is_banned']): ?>
                                                <span class="badge bg-danger">Banned</span>
                                            <?php elseif (strtotime($user['expiry_date']) < time()): ?>
                                                <span class="badge bg-warning text-dark">Expired</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="actionsDropdown">
                                                    <?php if ($user['is_banned']): ?>
                                                        <li>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="action" value="unban_user">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <button type="submit" class="dropdown-item">Unban</button>
                                                            </form>
                                                        </li>
                                                    <?php else: ?>
                                                        <li>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="action" value="ban_user">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <button type="submit" class="dropdown-item">Ban</button>
                                                            </form>
                                                        </li>
                                                    <?php endif; ?>
                                                    
                                                    <li>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="delete_user">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <button type="submit" class="dropdown-item text-danger">Delete</button>
                                                        </form>
                                                    </li>
                                                    
                                                    <li>
                                                        <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#extendModal<?php echo $user['id']; ?>">Extend Date</button>
                                                    </li>
                                                    
                                                    <?php if ($user['hwid']): ?>
                                                        <li>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="action" value="reset_hwid">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <button type="submit" class="dropdown-item">Reset HWID</button>
                                                            </form>
                                                        </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                            
                                            <!-- Extend Date Modal -->
                                            <div class="modal fade" id="extendModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="extendModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="extendModalLabel">Extend Expiry Date</h5>
                                                            <button type="button" class="btn-close" data-bs-close="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="action" value="extend_date">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <div class="mb-3">
                                                                    <label for="new_date" class="form-label">New Expiry Date</label>
                                                                    <input type="datetime-local" class="form-control" id="new_date" name="new_date" required>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                <button type="submit" class="btn btn-primary">Save changes</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>