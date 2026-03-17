<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h4 class="mb-0"><i class="fas fa-plus"></i> Add Employment</h4></div>
            <div class="card-body">
                <?php echo form_open('profile/employment/add'); ?>
                    <div class="mb-3">
                        <label for="company" class="form-label">Company *</label>
                        <input type="text" class="form-control <?php echo form_error('company') ? 'is-invalid' : ''; ?>" id="company" name="company" value="<?php echo set_value('company'); ?>" required>
                        <div class="invalid-feedback"><?php echo form_error('company'); ?></div>
                    </div>
                    <div class="mb-3">
                        <label for="position" class="form-label">Position / Job Title *</label>
                        <input type="text" class="form-control <?php echo form_error('position') ? 'is-invalid' : ''; ?>" id="position" name="position" value="<?php echo set_value('position'); ?>" required>
                        <div class="invalid-feedback"><?php echo form_error('position'); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">Start Date *</label>
                            <input type="date" class="form-control <?php echo form_error('start_date') ? 'is-invalid' : ''; ?>" id="start_date" name="start_date" value="<?php echo set_value('start_date'); ?>" required>
                            <div class="invalid-feedback"><?php echo form_error('start_date'); ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label">End Date <small class="text-muted">(leave blank if current)</small></label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo set_value('end_date'); ?>">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="<?php echo site_url('profile/employment'); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                    </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
