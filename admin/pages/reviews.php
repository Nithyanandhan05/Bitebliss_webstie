<?php

// Fetch all reviews from the database
$sql = "SELECT * FROM reviews ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<div class="container">
    <div class="header-section">
        <h2>Manage Reviews</h2>
        <p>Approve or delete customer reviews before they appear on your homepage.</p>
    </div>
    
    <!-- Desktop Table View -->
    <div class="desktop-table">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Rating</th>
                    <th>Review</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td class="user-name"><?php echo htmlspecialchars($row['user_name']); ?></td>
                            <td>
                                <div class="star-rating-display">
                                    <?php for($i = 0; $i < $row['rating']; $i++): ?>
                                        <i class="fas fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                            </td>
                            <td class="review-text-cell"><?php echo htmlspecialchars($row['review_text']); ?></td>
                            <td class="date-cell"><?php echo date("M d, Y", strtotime($row['created_at'])); ?></td>
                            <td>
                                <span class="status <?php echo $row['is_approved'] ? 'status-approved' : 'status-pending'; ?>">
                                    <?php echo $row['is_approved'] ? 'Approved' : 'Pending'; ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <?php if (!$row['is_approved']): ?>
                                    <a href="pages/approve_review.php?id=<?php echo $row['id']; ?>" class="btn btn-success">
                                        <i class="fas fa-check"></i>
                                        <span class="btn-text">Approve</span>
                                    </a>
                                <?php endif; ?>
                                <a href="pages/delete_review.php?id=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this review?')">
                                    <i class="fas fa-trash"></i>
                                    <span class="btn-text">Delete</span>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="no-reviews">No reviews have been submitted yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Mobile Card View -->
    <div class="mobile-cards">
        <?php 
        // Reset result for mobile view
        $result = $conn->query($sql);
        if ($result->num_rows > 0): 
        ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="review-card">
                    <div class="card-header">
                        <div class="user-info">
                            <h4><?php echo htmlspecialchars($row['user_name']); ?></h4>
                            <span class="review-id">#<?php echo $row['id']; ?></span>
                        </div>
                        <div class="status-badge">
                            <span class="status <?php echo $row['is_approved'] ? 'status-approved' : 'status-pending'; ?>">
                                <?php echo $row['is_approved'] ? 'Approved' : 'Pending'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="card-content">
                        <div class="rating-section">
                            <div class="star-rating-display">
                                <?php for($i = 0; $i < $row['rating']; $i++): ?>
                                    <i class="fas fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="rating-text"><?php echo $row['rating']; ?>/5</span>
                        </div>
                        
                        <div class="review-text">
                            <?php echo htmlspecialchars($row['review_text']); ?>
                        </div>
                        
                        <div class="review-date">
                            <i class="fas fa-calendar"></i>
                            <?php echo date("M d, Y", strtotime($row['created_at'])); ?>
                        </div>
                    </div>
                    
                    <div class="card-actions">
                        <?php if (!$row['is_approved']): ?>
                            <a href="pages/approve_review.php?id=<?php echo $row['id']; ?>" class="btn btn-success btn-mobile">
                                <i class="fas fa-check"></i>
                                Approve
                            </a>
                        <?php endif; ?>
                        <a href="pages/delete_review.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-mobile" onclick="return confirm('Are you sure you want to delete this review?')">
                            <i class="fas fa-trash"></i>
                            Delete
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-reviews-card">
                <i class="fas fa-comments"></i>
                <p>No reviews have been submitted yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Base Styles */
.container {
    padding: 15px;
    max-width: 100%;
}

.header-section {
    margin-bottom: 20px;
    text-align: center;
}

.header-section h2 {
    margin-bottom: 10px;
    color: #333;
}

.header-section p {
    color: #666;
    font-size: 14px;
    margin: 0;
}

/* Desktop Table Styles */
.desktop-table {
    display: block;
    overflow-x: auto;
    white-space: nowrap;
}

.table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
}

.table th,
.table td {
    padding: 12px 8px;
    text-align: left;
    border-bottom: 1px solid #ddd;
    vertical-align: middle;
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #333;
}

.review-text-cell {
    max-width: 250px;
    white-space: normal;
    word-wrap: break-word;
    font-size: 14px;
}

.user-name {
    font-weight: 500;
    min-width: 100px;
}

.date-cell {
    min-width: 80px;
    font-size: 13px;
}

/* Star Rating */
.star-rating-display {
    display: flex;
    align-items: center;
    gap: 2px;
}

.star-rating-display .fa-star {
    color: #ffc107;
    font-size: 16px;
}

/* Status Badges */
.status {
    padding: 4px 8px;
    border-radius: 12px;
    color: white;
    font-weight: 500;
    font-size: 12px;
    display: inline-block;
}

.status-approved {
    background-color: #28a745;
}

.status-pending {
    background-color: #ffc107;
    color: #333;
}

/* Action Buttons */
.action-buttons {
    min-width: 120px;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 500;
    border: none;
    cursor: pointer;
    margin-right: 5px;
    margin-bottom: 5px;
}

.btn-success {
    background-color: #28a745;
    color: white;
}

.btn-success:hover {
    background-color: #218838;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-danger:hover {
    background-color: #c82333;
}

/* Mobile Cards - Hidden by default */
.mobile-cards {
    display: none;
}

.review-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 16px;
    overflow: hidden;
    border: 1px solid #e9ecef;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 16px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}

.user-info h4 {
    margin: 0;
    font-size: 16px;
    color: #333;
}

.review-id {
    font-size: 12px;
    color: #6c757d;
    font-weight: normal;
}

.status-badge {
    flex-shrink: 0;
}

.card-content {
    padding: 16px;
}

.rating-section {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
}

.rating-text {
    font-size: 14px;
    color: #6c757d;
    font-weight: 500;
}

.review-text {
    font-size: 14px;
    line-height: 1.5;
    color: #333;
    margin-bottom: 12px;
    padding: 12px;
    background-color: #f8f9fa;
    border-radius: 8px;
    border-left: 3px solid #007bff;
}

.review-date {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: #6c757d;
}

.card-actions {
    padding: 16px;
    background-color: #f8f9fa;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.btn-mobile {
    flex: 1;
    min-width: 120px;
    justify-content: center;
    padding: 10px 16px;
    font-size: 14px;
}

.no-reviews,
.no-reviews-card {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

.no-reviews-card i {
    font-size: 48px;
    color: #dee2e6;
    margin-bottom: 16px;
}

/* Responsive Design */
@media screen and (max-width: 1024px) {
    .review-text-cell {
        max-width: 200px;
    }
    
    .btn-text {
        display: none;
    }
    
    .btn {
        padding: 8px;
    }
}

@media screen and (max-width: 768px) {
    .container {
        padding: 10px;
    }
    
    .header-section {
        text-align: left;
        margin-bottom: 16px;
    }
    
    .header-section h2 {
        font-size: 20px;
    }
    
    .header-section p {
        font-size: 13px;
    }
    
    /* Hide desktop table on mobile */
    .desktop-table {
        display: none;
    }
    
    /* Show mobile cards */
    .mobile-cards {
        display: block;
    }
}

@media screen and (max-width: 480px) {
    .container {
        padding: 8px;
    }
    
    .review-card {
        margin-bottom: 12px;
        border-radius: 8px;
    }
    
    .card-header,
    .card-content,
    .card-actions {
        padding: 12px;
    }
    
    .user-info h4 {
        font-size: 15px;
    }
    
    .review-text {
        font-size: 13px;
        padding: 10px;
    }
    
    .btn-mobile {
        font-size: 13px;
        padding: 8px 12px;
        min-width: 100px;
    }
    
    .star-rating-display .fa-star {
        font-size: 14px;
    }
    
    .card-actions {
        flex-direction: column;
    }
    
    .btn-mobile {
        flex: none;
        width: 100%;
    }
}

@media screen and (max-width: 360px) {
    .container {
        padding: 5px;
    }
    
    .header-section h2 {
        font-size: 18px;
    }
    
    .review-text {
        font-size: 12px;
    }
    
    .user-info h4 {
        font-size: 14px;
    }
}

/* Landscape phone optimization */
@media screen and (max-width: 768px) and (orientation: landscape) {
    .desktop-table {
        display: block;
    }
    
    .mobile-cards {
        display: none;
    }
    
    .container {
        padding: 8px;
    }
    
    .table th,
    .table td {
        padding: 8px 6px;
        font-size: 12px;
    }
    
    .review-text-cell {
        max-width: 150px;
        font-size: 11px;
    }
    
    .btn {
        padding: 4px 8px;
        font-size: 11px;
    }
}

/* Print styles */
@media print {
    .action-buttons {
        display: none;
    }
    
    .mobile-cards {
        display: none;
    }
    
    .desktop-table {
        display: block;
    }
}
</style>