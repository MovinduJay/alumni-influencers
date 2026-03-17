<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h4 class="mb-0"><i class="fas fa-edit"></i> Edit Certification</h4></div>
            <div class="card-body">
                <?php echo form_open('profile/certifications/edit/' . $certification->id); ?>
                    <div class="mb-3">
                        <label for="title" class="form-label">Certification Title *</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo set_value('title', $certification->title); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="issuer" class="form-label">Issuing Organization *</label>
                        <input type="text" class="form-control" id="issuer" name="issuer" value="<?php echo set_value('issuer', $certification->issuer); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="url" class="form-label">URL</label>
                        <input type="url" class="form-control" id="url" name="url" value="<?php echo set_value('url', $certification->url); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="completion_date" class="form-label">Completion Date</label>
                        <input type="date" class="form-control" id="completion_date" name="completion_date" value="<?php echo set_value('completion_date', $certification->completion_date); ?>">
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="<?php echo site_url('profile/certifications'); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
                    </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
