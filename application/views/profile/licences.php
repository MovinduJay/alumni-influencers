<h3><i class="fas fa-id-card"></i> My Licences</h3>
<a href="<?php echo site_url('profile/licences/add'); ?>" class="btn btn-primary mb-3"><i class="fas fa-plus"></i> Add Licence</a>
<a href="<?php echo site_url('profile'); ?>" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Profile</a>

<?php if (!empty($licences)): ?>
<div class="table-responsive">
    <table class="table table-striped">
        <thead><tr><th>Title</th><th>Awarding Body</th><th>URL</th><th>Completion Date</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach ($licences as $l): ?>
            <tr>
                <td><?php echo htmlspecialchars($l->title, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($l->awarding_body, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo $l->url ? '<a href="' . htmlspecialchars($l->url, ENT_QUOTES, 'UTF-8') . '" target="_blank">Link</a>' : '-'; ?></td>
                <td><?php echo $l->completion_date ? htmlspecialchars($l->completion_date, ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                <td>
                    <a href="<?php echo site_url('profile/licences/edit/' . $l->id); ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                    <?php echo form_open('profile/licences/delete/' . $l->id, array('style' => 'display:inline')); ?>
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete?');"><i class="fas fa-trash"></i></button>
                    <?php echo form_close(); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="alert alert-info">No licences added yet.</div>
<?php endif; ?>
