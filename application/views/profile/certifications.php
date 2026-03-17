<h3><i class="fas fa-certificate"></i> My Certifications</h3>
<a href="<?php echo site_url('profile/certifications/add'); ?>" class="btn btn-primary mb-3"><i class="fas fa-plus"></i> Add Certification</a>
<a href="<?php echo site_url('profile'); ?>" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Profile</a>

<?php if (!empty($certifications)): ?>
<div class="table-responsive">
    <table class="table table-striped">
        <thead><tr><th>Title</th><th>Issuer</th><th>URL</th><th>Completion Date</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach ($certifications as $c): ?>
            <tr>
                <td><?php echo htmlspecialchars($c->title, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($c->issuer, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo $c->url ? '<a href="' . htmlspecialchars($c->url, ENT_QUOTES, 'UTF-8') . '" target="_blank">Link</a>' : '-'; ?></td>
                <td><?php echo $c->completion_date ? htmlspecialchars($c->completion_date, ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                <td>
                    <a href="<?php echo site_url('profile/certifications/edit/' . $c->id); ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                    <?php echo form_open('profile/certifications/delete/' . $c->id, array('style' => 'display:inline')); ?>
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete?');"><i class="fas fa-trash"></i></button>
                    <?php echo form_close(); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="alert alert-info">No certifications added yet.</div>
<?php endif; ?>
