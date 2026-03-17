<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h4 class="mb-0"><i class="fas fa-edit"></i> Edit Degree</h4></div>
            <div class="card-body">
                <?php echo form_open('profile/degrees/edit/' . $degree->id); ?>
                    <div class="mb-3">
                        <label for="title" class="form-label">Degree Title *</label>
                        <input type="text" class="form-control <?php echo form_error('title') ? 'is-invalid' : ''; ?>" id="title" name="title" value="<?php echo set_value('title', $degree->title); ?>" required>
                        <div class="invalid-feedback"><?php echo form_error('title'); ?></div>
                    </div>
                    <div class="mb-3">
                        <label for="institution" class="form-label">Institution *</label>
                        <input type="text" class="form-control <?php echo form_error('institution') ? 'is-invalid' : ''; ?>" id="institution" name="institution" value="<?php echo set_value('institution', $degree->institution); ?>" required>
                        <div class="invalid-feedback"><?php echo form_error('institution'); ?></div>
                    </div>
                    <div class="mb-3">
                        <label for="url" class="form-label">URL</label>
                        <input type="url" class="form-control <?php echo form_error('url') ? 'is-invalid' : ''; ?>" id="url" name="url" value="<?php echo set_value('url', $degree->url); ?>">
                        <div class="invalid-feedback"><?php echo form_error('url'); ?></div>
                    </div>
                    <div class="mb-3">
                        <label for="completion_date" class="form-label">Completion Date</label>
                        <input type="date" class="form-control" id="completion_date" name="completion_date" value="<?php echo set_value('completion_date', $degree->completion_date); ?>">
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="<?php echo site_url('profile/degrees'); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Degree</button>
                    </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
