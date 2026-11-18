<?php
$page_title = 'User Management';
$page_header = 'Passenger Management (CRUD)';
$active_menu = 'users';

require_once '../config.php';
require_login(['Admin']);

$error = '';
$success = '';

// Handle CRUD POST Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_GET['action']) ? sanitize($_GET['action']) : '';

    if ($action === 'create') {
        $fullname = sanitize($_POST['fullname']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $password = $_POST['password'];

        if (empty($fullname) || empty($email) || empty($phone) || empty($password)) {
            $error = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email address.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email' OR phone = '$phone'");
            
            if (mysqli_num_rows($check) > 0) {
                $error = "Email or phone number is already registered.";
            } else {
                $sql = "INSERT INTO users (fullname, email, phone, password) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssss", $fullname, $email, $phone, $hashed);
                if ($stmt->execute()) {
                    $success = "User registered successfully!";
                } else {
                    $error = "Failed to add passenger account.";
                }
            }
        }
    } elseif ($action === 'update') {
        $id = intval($_POST['id']);
        $fullname = sanitize($_POST['fullname']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $password = $_POST['password'];

        if (empty($fullname) || empty($email) || empty($phone)) {
            $error = "All fields except password are required.";
        } else {
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET fullname=?, email=?, phone=?, password=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssi", $fullname, $email, $phone, $hashed, $id);
            } else {
                $sql = "UPDATE users SET fullname=?, email=?, phone=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $fullname, $email, $phone, $id);
            }

            if ($stmt->execute()) {
                $success = "User records updated successfully!";
            } else {
                $error = "Failed to update user. Email or phone might be in use.";
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $sql = "DELETE FROM users WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success = "User account permanently removed.";
        } else {
            $error = "Could not delete user account.";
        }
    }
}

// Fetch all passenger records
$search_query = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = sanitize($_GET['search']);
    $search_query = "WHERE fullname LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%'";
}

$users = mysqli_query($conn, "SELECT * FROM users $search_query ORDER BY id DESC");
require_once 'header.php';
?>

<!-- Action controls & Alert displays -->
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
    <!-- Search -->
    <form action="users.php" method="GET" style="display:flex; gap:0.5rem; max-width:350px; width:100%;">
        <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        <button type="submit" class="btn btn-primary" style="width:auto;"><i class="fa-solid fa-magnifying-glass"></i></button>
        <?php if (isset($_GET['search'])): ?>
            <a href="users.php" class="btn btn-outline" style="width:auto;"><i class="fa-solid fa-xmark"></i></a>
        <?php endif; ?>
    </form>

    <!-- Add user Button -->
    <button class="btn btn-primary" style="width:auto; padding: 0.6rem 1.5rem;" onclick="openAddModal()">
        <i class="fa-solid fa-plus-circle"></i> Add User
    </button>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<!-- Users CRUD Table -->
<div class="admin-panel-card">
    <div class="admin-panel-header">
        <h3 class="admin-panel-title">Passenger Accounts</h3>
    </div>
    <div class="admin-panel-body" style="padding:0;">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email Address</th>
                        <th>Phone Number</th>
                        <th>Created At</th>
                        <th style="width: 150px; text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($users) === 0): ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding:2rem;">No passengers found.</td>
                        </tr>
                    <?php else: ?>
                        <?php while($row = mysqli_fetch_assoc($users)): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['fullname']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                <td style="text-align:center;">
                                    <!-- Edit Action -->
                                    <button class="btn btn-secondary" style="width:34px; height:34px; padding:0; display:inline-flex; align-items:center; justify-content:center; margin-right:4px;" 
                                            onclick='openEditModal(<?php echo json_encode($row); ?>)'>
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <!-- Delete Action -->
                                    <button class="btn btn-danger" style="width:34px; height:34px; padding:0; display:inline-flex; align-items:center; justify-content:center;" 
                                            onclick="openDeleteModal(<?php echo $row['id']; ?>)">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ==========================================================================
     CRUD MODALS
     ========================================================================== */ -->

<!-- Add Modal -->
<div class="admin-modal" id="add-modal">
    <div class="admin-modal-content">
        <div class="admin-modal-header">
            <h4>Add New User</h4>
            <button type="button" style="border:none; background:transparent; font-size:1.2rem; cursor:pointer;" onclick="closeAddModal()">&times;</button>
        </div>
        <form action="users.php?action=create" method="POST">
            <div class="admin-modal-body">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="fullname" class="form-control" placeholder="Enter full name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="Enter email" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" class="form-control" placeholder="Enter phone number" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Create a password" required>
                </div>
            </div>
            <div class="admin-modal-footer">
                <button type="button" class="btn btn-outline" style="width:auto;" onclick="closeAddModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="width:auto;">Save User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="admin-modal" id="edit-modal">
    <div class="admin-modal-content">
        <div class="admin-modal-header">
            <h4>Edit User</h4>
            <button type="button" style="border:none; background:transparent; font-size:1.2rem; cursor:pointer;" onclick="closeEditModal()">&times;</button>
        </div>
        <form action="users.php?action=update" method="POST">
            <input type="hidden" name="id" id="edit-user-id">
            <div class="admin-modal-body">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="fullname" id="edit-user-name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" id="edit-user-email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" id="edit-user-phone" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password (Leave blank to keep same)</label>
                    <input type="password" name="password" class="form-control" placeholder="New password">
                </div>
            </div>
            <div class="admin-modal-footer">
                <button type="button" class="btn btn-outline" style="width:auto;" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="width:auto;">Update User</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="admin-modal" id="delete-modal">
    <div class="admin-modal-content" style="max-width: 400px; text-align:center;">
        <div class="admin-modal-header" style="justify-content:center;">
            <h4 style="color:var(--accent-red);"><i class="fa-solid fa-triangle-exclamation"></i> Confirm Delete</h4>
        </div>
        <form action="users.php?action=delete" method="POST">
            <input type="hidden" name="id" id="delete-user-id">
            <div class="admin-modal-body" style="padding: 2rem 1.5rem;">
                <p style="font-weight:600; font-size:1rem; margin-bottom: 0.5rem;">Are you sure you want to delete this user?</p>
                <p class="text-muted" style="font-size:0.8rem;">This action cannot be undone and will permanently remove all bookings related to this user.</p>
            </div>
            <div class="admin-modal-footer" style="justify-content:center; gap:1.5rem;">
                <button type="button" class="btn btn-outline" style="width:100px;" onclick="closeDeleteModal()">Cancel</button>
                <button type="submit" class="btn btn-danger" style="width:100px;">Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Add Modals
    function openAddModal() {
        document.getElementById('add-modal').classList.add('show');
    }
    function closeAddModal() {
        document.getElementById('add-modal').classList.remove('show');
    }

    // Edit Modals
    function openEditModal(user) {
        document.getElementById('edit-user-id').value = user.id;
        document.getElementById('edit-user-name').value = user.fullname;
        document.getElementById('edit-user-email').value = user.email;
        document.getElementById('edit-user-phone').value = user.phone;
        document.getElementById('edit-modal').classList.add('show');
    }
    function closeEditModal() {
        document.getElementById('edit-modal').classList.remove('show');
    }

    // Delete Modals
    function openDeleteModal(id) {
        document.getElementById('delete-user-id').value = id;
        document.getElementById('delete-modal').classList.add('show');
    }
    function closeDeleteModal() {
        document.getElementById('delete-modal').classList.remove('show');
    }
</script>

<?php require_once 'footer.php'; ?>
