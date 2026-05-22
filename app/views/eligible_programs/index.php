<div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
    <div>
        <h3 class="mb-1">
            <i class="fas fa-graduation-cap me-2" style="color:#427431"></i>
            Eligible Programs — Canada Loan
        </h3>
        <p class="text-muted mb-0" style="max-width:760px">
            Brochures uploaded through <strong>parrot_mis &rarr; Smart Brochure Sharing</strong>
            are automatically published to the public website under
            <code>/<?= htmlspecialchars(ltrim($menu_slug, '/')) ?></code>.
            Toggle visibility or feature programs you want to promote.
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="http://localhost/parrot_mis/marketing-brochures.php"
           target="_blank"
           class="btn btn-outline-primary">
            <i class="fas fa-upload me-1"></i> Upload via MIS
        </a>
        <a href="<?= baseUrl('api/eligible-programs') ?>"
           target="_blank"
           class="btn btn-outline-secondary">
            <i class="fas fa-code me-1"></i> View JSON API
        </a>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Published programs (<?= count($rows) ?>)</h5>
        <span class="badge bg-light text-dark">DB: parrot_visa_cms + mis_parrot.marketing_brochures</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($rows)): ?>
            <div class="text-center py-5">
                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-2">No brochures published yet.</p>
                <a href="http://localhost/parrot_mis/marketing-brochures.php"
                   target="_blank"
                   class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Upload your first brochure
                </a>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:48%">Program</th>
                        <th>Region</th>
                        <th>PDF</th>
                        <th>Views</th>
                        <th>Featured</th>
                        <th>Visible</th>
                        <th>Preview</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <?php $slug = (string) $r['slug']; ?>
                    <tr data-slug="<?= htmlspecialchars($slug) ?>">
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars((string) ($r['display_title'] ?? $r['title'])) ?></div>
                            <?php if (!empty($r['display_subtitle'])): ?>
                                <small class="text-muted d-block"><?= htmlspecialchars((string) $r['display_subtitle']) ?></small>
                            <?php elseif (!empty($r['description'])): ?>
                                <small class="text-muted d-block">
                                    <?= htmlspecialchars(mb_strimwidth((string) $r['description'], 0, 110, '…')) ?>
                                </small>
                            <?php endif; ?>
                            <small class="text-muted">
                                <code><?= htmlspecialchars($slug) ?></code> &middot;
                                <?= !empty($r['created_at']) ? date('M j, Y', strtotime((string) $r['created_at'])) : '' ?>
                            </small>
                        </td>
                        <td>
                            <span class="badge bg-success-subtle text-success"><?= htmlspecialchars((string) $r['region_name']) ?></span>
                        </td>
                        <td>
                            <a href="<?= htmlspecialchars((string) $r['pdf_url']) ?>" target="_blank" class="text-decoration-none">
                                <i class="fas fa-file-pdf text-danger me-1"></i>PDF
                            </a>
                            <?php if (!empty($r['pdf_size_human'])): ?>
                                <div><small class="text-muted"><?= htmlspecialchars((string) $r['pdf_size_human']) ?></small></div>
                            <?php endif; ?>
                        </td>
                        <td><?= (int) ($r['view_count'] ?? 0) ?></td>
                        <td>
                            <div class="form-check form-switch">
                                <input class="form-check-input toggle-featured"
                                       type="checkbox"
                                       data-slug="<?= htmlspecialchars($slug) ?>"
                                       <?= ((int) $r['is_featured'] === 1) ? 'checked' : '' ?>>
                            </div>
                        </td>
                        <td>
                            <div class="form-check form-switch">
                                <input class="form-check-input toggle-visible"
                                       type="checkbox"
                                       data-slug="<?= htmlspecialchars($slug) ?>"
                                       <?= ((int) $r['is_hidden'] === 1) ? '' : 'checked' ?>>
                            </div>
                        </td>
                        <td>
                            <a href="<?= htmlspecialchars((string) $r['view_url']) ?>"
                               target="_blank"
                               class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script>
(function(){
    const csrf = <?= json_encode($csrf_token) ?>;
    const base = <?= json_encode(rtrim(SITE_URL, '/')) ?>;

    function post(endpoint, body){
        return fetch(base + endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(Object.assign({ csrf_token: csrf }, body)).toString()
        }).then(r => r.json());
    }

    document.querySelectorAll('.toggle-visible').forEach(el => {
        el.addEventListener('change', () => {
            const slug = el.dataset.slug;
            const hidden = el.checked ? '0' : '1';
            post('/eligible-programs/toggle-hidden', { slug, hidden })
                .catch(() => { el.checked = !el.checked; alert('Failed to update visibility'); });
        });
    });

    document.querySelectorAll('.toggle-featured').forEach(el => {
        el.addEventListener('change', () => {
            const slug = el.dataset.slug;
            const featured = el.checked ? '1' : '0';
            post('/eligible-programs/toggle-featured', { slug, featured })
                .catch(() => { el.checked = !el.checked; alert('Failed to update featured flag'); });
        });
    });
})();
</script>
