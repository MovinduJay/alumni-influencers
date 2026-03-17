<h3><i class="fas fa-graduation-cap"></i> My Degrees</h3>
<a href="<?php echo site_url('profile/degrees/add'); ?>" class="btn btn-primary mb-3"><i class="fas fa-plus"></i> Add Degree</a>
<a href="<?php echo site_url('profile'); ?>" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Profile</a>

<?php if (!empty($degrees)): ?>
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr><th>Title</th><th>Institution</th><th>URL</th><th>Completion Date</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($degrees as $d): ?>
            <tr>
                <td><?php echo htmlspecialchars($d->title, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($d->institution, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo $d->url ? '<a href="' . htmlspecialchars($d->url, ENT_QUOTES, 'UTF-8') . '" target="_blank">Link</a>' : '-'; ?></td>
                <td><?php echo $d->completion_date ? htmlspecialchars($d->completion_date, ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                <td>
                    <a href="<?php echo site_url('profile/degrees/edit/' . $d->id); ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                    <?php echo form_open('profile/degrees/delete/' . $d->id, array('style' => 'display:inline')); ?>
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this degree?');"><i class="fas fa-trash"></i></button>
                    <?php echo form_close(); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="alert alert-info">No degrees added yet. <a href="<?php echo site_url('profile/degrees/add'); ?>">Add your first degree</a>.</div>
<?php endif; ?>
