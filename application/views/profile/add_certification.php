<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h4 class="mb-0"><i class="fas fa-plus"></i> Add Certification</h4></div>
            <div class="card-body">
                <?php echo form_open('profile/certifications/add'); ?>
                    <div class="mb-3">
                        <label for="title" class="form-label">Certification Title *</label>
                        <input type="text" class="form-control <?php echo form_error('title') ? 'is-invalid' : ''; ?>" id="title" name="title" value="<?php echo set_value('title'); ?>" required>
                        <div class="invalid-feedback"><?php echo form_error('title'); ?></div>
                    </div>
                    <div class="mb-3">
                        <label for="issuer" class="form-label">Issuing Organization *</label>
                        <input type="text" class="form-control <?php echo form_error('issuer') ? 'is-invalid' : ''; ?>" id="issuer" name="issuer" value="<?php echo set_value('issuer'); ?>" required>
                        <div class="invalid-feedback"><?php echo form_error('issuer'); ?></div>
                    </div>
                    <div class="mb-3">
                        <label for="url" class="form-label">URL (to course page)</label>
                        <input type="url" class="form-control <?php echo form_error('url') ? 'is-invalid' : ''; ?>" id="url" name="url" value="<?php echo set_value('url'); ?>" placeholder="https://">
                        <div class="invalid-feedback"><?php echo form_error('url'); ?></div>
                    </div>
                    <div class="mb-3">
                        <label for="completion_date" class="form-label">Completion Date</label>
                        <input type="date" class="form-control" id="completion_date" name="completion_date" value="<?php echo set_value('completion_date'); ?>">
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="<?php echo site_url('profile/certifications'); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                    </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
