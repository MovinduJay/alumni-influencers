<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h4 class="mb-0"><i class="fas fa-edit"></i> Edit Employment</h4></div>
            <div class="card-body">
                <?php echo form_open('profile/employment/edit/' . $employment->id); ?>
                    <div class="mb-3">
                        <label for="company" class="form-label">Company *</label>
                        <input type="text" class="form-control" id="company" name="company" value="<?php echo set_value('company', $employment->company); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="position" class="form-label">Position *</label>
                        <input type="text" class="form-control" id="position" name="position" value="<?php echo set_value('position', $employment->position); ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">Start Date *</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo set_value('start_date', $employment->start_date); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo set_value('end_date', $employment->end_date); ?>">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="<?php echo site_url('profile/employment'); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
                    </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
