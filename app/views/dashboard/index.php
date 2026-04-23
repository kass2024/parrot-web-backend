<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card">
            <div class="card-body">
                <div class="stats-number"><?= $admin_stats['total_admins'] ?></div>
                <div class="stats-label">Total Admins</div>
                <div class="progress mt-2" style="height: 4px;">
                    <div class="progress-bar bg-white" style="width: 75%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="card-body">
                <div class="stats-number"><?= $gallery_stats['total_images'] ?></div>
                <div class="stats-label">Gallery Images</div>
                <div class="progress mt-2" style="height: 4px;">
                    <div class="progress-bar bg-white" style="width: 60%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="card-body">
                <div class="stats-number"><?= $gallery_stats['active_images'] ?></div>
                <div class="stats-label">Active Images</div>
                <div class="progress mt-2" style="height: 4px;">
                    <div class="progress-bar bg-white" style="width: 85%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
            <div class="card-body">
                <div class="stats-number"><?= $gallery_stats['recent_uploads'] ?></div>
                <div class="stats-label">Recent Uploads</div>
                <div class="progress mt-2" style="height: 4px;">
                    <div class="progress-bar bg-white" style="width: 45%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>
                    Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="<?= baseUrl('gallery/create') ?>" class="btn btn-primary w-100">
                            <i class="fas fa-plus me-2"></i>
                            Add Gallery Image
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="<?= baseUrl('menus') ?>" class="btn btn-outline-primary w-100">
                            <i class="fas fa-bars me-2"></i>
                            Manage Menus
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="<?= baseUrl('content') ?>" class="btn btn-outline-primary w-100">
                            <i class="fas fa-edit me-2"></i>
                            Edit Content
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="<?= baseUrl('settings') ?>" class="btn btn-outline-primary w-100">
                            <i class="fas fa-cog me-2"></i>
                            Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity & System Info -->
<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>
                    Recent Activity
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_activities)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No recent activity found.</p>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($recent_activities, 0, 10) as $activity): ?>
                        <div class="activity-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong><?= htmlspecialchars($activity['username']) ?></strong>
                                    <span class="text-muted"> <?= htmlspecialchars($activity['action']) ?></span>
                                    <?php if (!empty($activity['details'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($activity['details']) ?></small>
                                    <?php endif; ?>
                                </div>
                                <small class="activity-time">
                                    <?= date('M j, Y H:i', strtotime($activity['timestamp'])) ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-server me-2"></i>
                    System Information
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted">PHP Version</small>
                    <div class="fw-bold"><?= $system_info['php_version'] ?></div>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Server Software</small>
                    <div class="fw-bold"><?= $system_info['server_software'] ?></div>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Database Version</small>
                    <div class="fw-bold"><?= $system_info['database_version'] ?></div>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Disk Space</small>
                    <div class="fw-bold">
                        <?= $system_info['disk_space']['used'] ?> / <?= $system_info['disk_space']['total'] ?>
                    </div>
                    <div class="progress mt-1" style="height: 4px;">
                        <?php 
                        $used_bytes = disk_total_space(__DIR__) - disk_free_space(__DIR__);
                        $total_bytes = disk_total_space(__DIR__);
                        $percentage = ($used_bytes / $total_bytes) * 100;
                        ?>
                        <div class="progress-bar <?= $percentage > 80 ? 'bg-danger' : ($percentage > 60 ? 'bg-warning' : 'bg-success') ?>" 
                             style="width: <?= $percentage ?>%"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Memory Usage</small>
                    <div class="fw-bold"><?= $system_info['memory_usage']['current'] ?></div>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Session Status</small>
                    <div class="fw-bold">
                        <span class="badge bg-<?= $system_info['session_status'] === 'Active' ? 'success' : 'danger' ?>">
                            <?= $system_info['session_status'] ?>
                        </span>
                    </div>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Max Upload Size</small>
                    <div class="fw-bold"><?= $system_info['upload_max_size'] ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Gallery Preview -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-images me-2"></i>
                    Recent Gallery Images
                    <a href="<?= baseUrl('gallery') ?>" class="btn btn-sm btn-outline-light float-end">
                        View All
                    </a>
                </h5>
            </div>
            <div class="card-body">
                <?php
                $gallery_model = new Gallery();
                $recent_images = $gallery_model->getAll(['is_active' => 1], 'created_at DESC', 6);
                ?>
                
                <?php if (empty($recent_images)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-images fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No gallery images found.</p>
                        <a href="<?= baseUrl('gallery/create') ?>" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>
                            Add First Image
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($recent_images as $image): ?>
                            <div class="col-md-2 col-sm-4 col-6 mb-3">
                                <div class="card h-100">
                                    <img src="<?= uploadUrl('gallery/' . $image['image_url']) ?>" 
                                         class="card-img-top" 
                                         alt="<?= htmlspecialchars($image['title']) ?>"
                                         style="height: 120px; object-fit: cover;"
                                         onerror="this.src='https://picsum.photos/seed/<?= urlencode($image['title']) ?>/200/120.jpg'">
                                    <div class="card-body p-2">
                                        <small class="text-truncate d-block"><?= htmlspecialchars($image['title']) ?></small>
                                        <div class="btn-group btn-group-sm w-100" role="group">
                                            <a href="<?= baseUrl('gallery/edit/' . $image['id']) ?>" 
                                               class="btn btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-outline-danger" 
                                                    onclick="deleteImage(<?= $image['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Delete image function
    function deleteImage(id) {
        if (confirm('Are you sure you want to delete this image?')) {
            $.ajax({
                url: window.baseUrl + '/gallery/delete/' + id,
                type: 'POST',
                data: {
                    csrf_token: window.csrfToken
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('An error occurred while deleting the image.');
                }
            });
        }
    }
    
    // Auto-refresh dashboard stats every 30 seconds
    setInterval(function() {
        $.ajax({
            url: window.baseUrl + '/dashboard/api/stats',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    // Update stats if needed
                    console.log('Stats refreshed');
                }
            }
        });
    }, 30000);
</script>
