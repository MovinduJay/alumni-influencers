<h3><i class="fas fa-briefcase"></i> Employment History</h3>
<a href="<?php echo site_url('profile/employment/add'); ?>" class="btn btn-primary mb-3"><i class="fas fa-plus"></i> Add Employment</a>
<a href="<?php echo site_url('profile'); ?>" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Profile</a>

<?php if (!empty($employment)): ?>
<div class="table-responsive">
    <table class="table table-striped">
        <thead><tr><th>Position</th><th>Company</th><th>Start Date</th><th>End Date</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach ($employment as $e): ?>
            <tr>
                <td><?php echo htmlspecialchars($e->position, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($e->company, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($e->start_date, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo $e->end_date ? htmlspecialchars($e->end_date, ENT_QUOTES, 'UTF-8') : '<span class="badge bg-success">Present</span>'; ?></td>
                <td>
                    <a href="<?php echo site_url('profile/employment/edit/' . $e->id); ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                    <?php echo form_open('profile/employment/delete/' . $e->id, array('style' => 'display:inline')); ?>
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete?');"><i class="fas fa-trash"></i></button>
                    <?php echo form_close(); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="alert alert-info">No employment history added yet.</div>
<?php endif; ?>
