<?php
// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header('Location: ../index.php');
    exit;
}

// Handle user deletion
if (isset($_POST['delete_user'])) {
    $user_id = intval($_POST['user_id']);
    $delete_query = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $success_message = "User deleted successfully!";
    } else {
        $error_message = "Error deleting user: " . $conn->error;
    }
    $stmt->close();
}

// Handle search functionality
$search_term = '';
$where_clause = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    $where_clause = "WHERE username LIKE '%$search_term%' OR phone_number LIKE '%$search_term%'";
}

// Pagination setup
$records_per_page = 10;
$current_page = isset($_GET['pg']) ? max(1, intval($_GET['pg'])) : 1;
$offset = ($current_page - 1) * $records_per_page;

// Get total number of users
$count_query = "SELECT COUNT(*) as total FROM users $where_clause";
$count_result = $conn->query($count_query);
$total_users = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_users / $records_per_page);

// Get users with pagination
$query = "SELECT id, username, phone_number, created_at FROM users $where_clause ORDER BY created_at DESC LIMIT $offset, $records_per_page";
$result = $conn->query($query);
?>

<div class="page-header">
    <h1><i class="fas fa-users"></i> User Management</h1>
    <p>Manage registered users on your platform</p>
</div>

<?php if (isset($success_message)): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
    </div>
<?php endif; ?>

<div class="content-box">
    <div class="box-header">
        <div class="header-left">
            <h2>All Users (<?php echo $total_users; ?>)</h2>
        </div>
        <div class="header-right">
            <form method="GET" class="search-form">
                <input type="hidden" name="page" value="users">
                <div class="search-box">
                    <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search_term); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Phone Number</th>
                    <th>Registration Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($user = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td>
                                <div class="user-info">
                                    <i class="fas fa-user-circle"></i>
                                    <span><?php echo htmlspecialchars($user['username']); ?></span>
                                </div>
                            </td>
                            <td>
                                <i class="fas fa-phone"></i>
                                <?php echo htmlspecialchars($user['phone_number']); ?>
                            </td>
                            <td>
                                <i class="fas fa-calendar"></i>
                                <?php echo date('M d, Y H:i', strtotime($user['created_at'])); ?>
                            </td>
                            <td class="actions">
                                <a href="index.php?page=view_user&id=<?php echo $user['id']; ?>" class="btn btn-view" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" class="btn btn-delete" title="Delete User">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="no-data">
                            <i class="fas fa-users"></i>
                            <p>No users found</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($current_page > 1): ?>
                <a href="?page=users&pg=<?php echo $current_page - 1; ?><?php echo $search_term ? '&search=' . urlencode($search_term) : ''; ?>" class="page-btn">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            <?php endif; ?>

            <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                <a href="?page=users&pg=<?php echo $i; ?><?php echo $search_term ? '&search=' . urlencode($search_term) : ''; ?>" 
                   class="page-btn <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($current_page < $total_pages): ?>
                <a href="?page=users&pg=<?php echo $current_page + 1; ?><?php echo $search_term ? '&search=' . urlencode($search_term) : ''; ?>" class="page-btn">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
        <p>Are you sure you want to delete user <strong id="userName"></strong>?</p>
        <p class="warning">This action cannot be undone.</p>
        <form id="deleteForm" method="POST">
            <input type="hidden" name="user_id" id="deleteUserId">
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" name="delete_user" class="btn btn-danger">Delete User</button>
            </div>
        </form>
    </div>
</div>

<style>
.page-header {
    margin-bottom: 30px;
}

.page-header h1 {
    color: #2c3e50;
    margin-bottom: 5px;
}

.page-header p {
    color: #7f8c8d;
    margin: 0;
}

.content-box {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.box-header {
    background: #f8f9fa;
    padding: 20px;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.box-header h2 {
    margin: 0;
    color: #2c3e50;
}

.search-form .search-box {
    display: flex;
    border: 1px solid #ddd;
    border-radius: 5px;
    overflow: hidden;
}

.search-form input {
    border: none;
    padding: 8px 12px;
    outline: none;
    min-width: 200px;
}

.search-form button {
    background: #3498db;
    color: white;
    border: none;
    padding: 8px 12px;
    cursor: pointer;
}

.search-form button:hover {
    background: #2980b9;
}

.table-container {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th,
.data-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.data-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #2c3e50;
}

.data-table tbody tr:hover {
    background: #f8f9fa;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 8px;
}

.user-info i {
    color: #3498db;
}

.actions {
    display: flex;
    gap: 8px;
}

.btn {
    padding: 6px 10px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    transition: all 0.3s ease;
}

.btn-view {
    background: #3498db;
    color: white;
}

.btn-view:hover {
    background: #2980b9;
}

.btn-delete {
    background: #e74c3c;
    color: white;
}

.btn-delete:hover {
    background: #c0392b;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.no-data {
    text-align: center;
    padding: 40px;
    color: #7f8c8d;
}

.no-data i {
    font-size: 48px;
    margin-bottom: 10px;
    display: block;
}

.pagination {
    padding: 20px;
    text-align: center;
    border-top: 1px solid #eee;
}

.page-btn {
    display: inline-block;
    padding: 8px 12px;
    margin: 0 2px;
    text-decoration: none;
    border: 1px solid #ddd;
    border-radius: 4px;
    color: #333;
}

.page-btn:hover,
.page-btn.active {
    background: #3498db;
    color: white;
    border-color: #3498db;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 15% auto;
    padding: 20px;
    border-radius: 10px;
    width: 80%;
    max-width: 500px;
    position: relative;
}

.close {
    position: absolute;
    right: 15px;
    top: 15px;
    font-size: 24px;
    cursor: pointer;
}

.modal h3 {
    margin-top: 0;
    color: #e74c3c;
}

.warning {
    color: #e74c3c;
    font-size: 14px;
}

.modal-actions {
    margin-top: 20px;
    text-align: right;
    gap: 10px;
    display: flex;
    justify-content: flex-end;
}
</style>

<script>
function deleteUser(userId, userName) {
    document.getElementById('deleteUserId').value = userId;
    document.getElementById('userName').textContent = userName;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Close modal when clicking outside of it
window.onclick = function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Close modal with escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
    }
});
</script>