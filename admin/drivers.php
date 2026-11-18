<?php
$page_title = 'Driver Management';
$page_header = 'Tricycle Drivers CRUD & verification';
$active_menu = 'drivers';

require_once '../config.php';
require_login(['Admin']);

$error = '';
$success = '';

// Handle CRUD POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_GET['action']) ? sanitize($_GET['action']) : '';

    if ($action === 'create') {
        $fullname = sanitize($_POST['fullname']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $license_no = sanitize($_POST['license_no']);
        $vehicle_info = sanitize($_POST['vehicle_info']);
        $password = $_POST['password'];

        if (empty($fullname) || empty($email) || empty($phone) || empty($license_no) || empty($password)) {
            $error = "All required fields must be filled.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $check = mysqli_query($conn, "SELECT id FROM drivers WHERE email = '$email' OR phone = '$phone' OR license_no = '$license_no'");
            if (mysqli_num_rows($check) > 0) {
                $error = "Email, phone number, or driver license is already registered.";
            } else {
                $sql = "INSERT INTO drivers (fullname, email, phone, license_no, vehicle_info, password, status) VALUES (?, ?, ?, ?, ?, ?, 'Offline')";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssss", $fullname, $email, $phone, $license_no, $vehicle_info, $hashed);
                if ($stmt->execute()) {
                    $success = "Driver registered successfully!";
                } else {
                    $error = "Failed to add tricycle driver.";
                }
            }
        }
    } elseif ($action === 'update') {
        $id = intval($_POST['id']);
        $fullname = sanitize($_POST['fullname']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $license_no = sanitize($_POST['license_no']);
        $vehicle_info = sanitize($_POST['vehicle_info']);
        $password = $_POST['password'];

        if (empty($fullname) || empty($email) || empty($phone) || empty($license_no)) {
            $error = "Required fields cannot be empty.";
        } else {
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE drivers SET fullname=?, email=?, phone=?, license_no=?, vehicle_info=?, password=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssi", $fullname, $email, $phone, $license_no, $vehicle_info, $hashed, $id);
            } else {
                $sql = "UPDATE drivers SET fullname=?, email=?, phone=?, license_no=?, vehicle_info=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssi", $fullname, $email, $phone, $license_no, $vehicle_info, $id);
            }

            if ($stmt->execute()) {
                $success = "Driver records updated successfully!";
            } else {
                $error = "Failed to update driver details.";
            }
        }
    } elseif ($action === 'toggle_status') {
        $id = intval($_POST['id']);
        $new_status = sanitize($_POST['status']); // 'Suspended' or 'Offline' (reactivated)

        $sql = "UPDATE drivers SET status=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_status, $id);
        if ($stmt->execute()) {
            $success = "Driver status set to $new_status.";
        } else {
            $error = "Failed to toggle status.";
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $sql = "DELETE FROM drivers WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Driver account permanently removed.";
        } else {
            $error = "Could not delete driver account.";
        }
    }
}

// Fetch all drivers
$search_query = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = sanitize($_GET['search']);
    $search_query = "WHERE fullname LIKE '%$search%' OR email LIKE '%$search%' OR license_no LIKE '%$search%'";
}

$drivers = mysqli_query($conn, "SELECT * FROM drivers $search_query ORDER BY id DESC");
require_once 'header.php';
?>

<!-- Action controls & alerts -->
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
    <!-- Search -->
    <form action="drivers.php" method="GET" style="display:flex; gap:0.5rem; max-width:350px; width:100%;">
        <input type="text" name="search" class="form-control" placeholder="Search drivers..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        <button type="submit" class="btn btn-primary" style="width:auto;"><i class="fa-solid fa-magnifying-glass"></i></button>
        <?php if (isset($_GET['search'])): ?>
            <a href="drivers.php" class="btn btn-outline" style="width:auto;"><i class="fa-solid fa-xmark"></i></a>
        <?php endif; ?>
    </form>

    <!-- Add driver Button -->
    <button class="btn btn-primary" style="width:auto; padding: 0.6rem 1.5rem;" onclick="openAddModal()">
        <i class="fa-solid fa-plus-circle"></i> Add Driver
    </button>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<!-- Drivers List -->
<div class="admin-panel-card">
    <div class="admin-panel-header">
        <h3 class="admin-panel-title">Tricycle Drivers</h3>
    </div>
    <div class="admin-panel-body" style="padding:0;">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>License No.</th>
                        <th>Phone</th>
                        <th>Vehicle Info</th>
                        <th>Rating</th>
                        <th>Status</th>
                        <th style="width: 220px; text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($drivers) === 0): ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding:2rem;">No drivers found.</td>
                        </tr>
                    <?php else: ?>
                        <?php while($row = mysqli_fetch_assoc($drivers)): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['fullname']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['license_no']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td><?php echo htmlspecialchars($row['vehicle_info']); ?></td>
                                <td style="color:var(--accent-yellow); font-weight:700;">
                                    <i class="fa-solid fa-star"></i> <?php echo number_format($row['rating'], 1); ?>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'Suspended'): ?>
                                        <span class="badge badge-danger">Suspended</span>
                                    <?php elseif ($row['status'] === 'Online'): ?>
                                        <span class="badge badge-success">Online</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Offline</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:center;">
                                    <!-- Suspend / Reactivate Toggles -->
                                    <?php if ($row['status'] === 'Suspended'): ?>
                                        <form action="drivers.php?action=toggle_status" method="POST" style="display:inline;">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="status" value="Offline">
                                            <button type="submit" class="btn btn-secondary" style="width:85px; font-size:0.75rem; padding: 0.35rem 0.5rem; display:inline-flex; align-items:center; justify-content:center; margin-right:4px;">Reactivate</button>
                                        </form>
                                    <?php else: ?>
                                        <form action="drivers.php?action=toggle_status" method="POST" style="display:inline;">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="status" value="Suspended">
                                            <button type="submit" class="btn btn-outline" style="width:85px; font-size:0.75rem; padding: 0.35rem 0.5rem; border-color:var(--accent-red); color:var(--accent-red); display:inline-flex; align-items:center; justify-content:center; margin-right:4px;">Suspend</button>
                                        </form>
                                    <?php endif; ?>

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
            <h4>Add New Driver</h4>
            <button type="button" style="border:none; background:transparent; font-size:1.2rem; cursor:pointer;" onclick="closeAddModal()">&times;</button>
        </div>
        <form action="drivers.php?action=create" method="POST">
            <div class="admin-modal-body">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="fullname" class="form-control" placeholder="Enter driver full name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="Enter driver email" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" class="form-control" placeholder="Enter phone number" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Driver License No.</label>
                    <input type="text" name="license_no" class="form-control" placeholder="L-12345-67890" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Tricycle Description</label>
                    <input type="text" name="vehicle_info" class="form-control" placeholder="Green Tricycle" value="Green Tricycle">
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Create a password" required>
                </div>
            </div>
            <div class="admin-modal-footer">
                <button type="button" class="btn btn-outline" style="width:auto;" onclick="closeAddModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="width:auto;">Save Driver</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="admin-modal" id="edit-modal">
    <div class="admin-modal-content">
        <div class="admin-modal-header">
            <h4>Edit Driver</h4>
            <button type="button" style="border:none; background:transparent; font-size:1.2rem; cursor:pointer;" onclick="closeEditModal()">&times;</button>
        </div>
        <form action="drivers.php?action=update" method="POST">
            <input type="hidden" name="id" id="edit-driver-id">
            <div class="admin-modal-body">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="fullname" id="edit-driver-name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" id="edit-driver-email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" id="edit-driver-phone" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Driver License No.</label>
                    <input type="text" name="license_no" id="edit-driver-license" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Tricycle Description</label>
                    <input type="text" name="vehicle_info" id="edit-driver-vehicle" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password (Leave blank to keep same)</label>
                    <input type="password" name="password" class="form-control" placeholder="New password">
                </div>
            </div>
            <div class="admin-modal-footer">
                <button type="button" class="btn btn-outline" style="width:auto;" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="width:auto;">Update Driver</button>
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
        <form action="drivers.php?action=delete" method="POST">
            <input type="hidden" name="id" id="delete-driver-id">
            <div class="admin-modal-body" style="padding: 2rem 1.5rem;">
                <p style="font-weight:600; font-size:1rem; margin-bottom: 0.5rem;">Are you sure you want to delete this driver?</p>
                <p class="text-muted" style="font-size:0.8rem;">This will permanently erase driver details from the system logs.</p>
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
    function openEditModal(drv) {
        document.getElementById('edit-driver-id').value = drv.id;
        document.getElementById('edit-driver-name').value = drv.fullname;
        document.getElementById('edit-driver-email').value = drv.email;
        document.getElementById('edit-driver-phone').value = drv.phone;
        document.getElementById('edit-driver-license').value = drv.license_no;
        document.getElementById('edit-driver-vehicle').value = drv.vehicle_info;
        document.getElementById('edit-modal').classList.add('show');
    }
    function closeEditModal() {
        document.getElementById('edit-modal').classList.remove('show');
    }

    // Delete Modals
    function openDeleteModal(id) {
        document.getElementById('delete-driver-id').value = id;
        document.getElementById('delete-modal').classList.add('show');
    }
    function closeDeleteModal() {
        document.getElementById('delete-modal').classList.remove('show');
    }
</script>

<?php require_once 'footer.php'; ?>
