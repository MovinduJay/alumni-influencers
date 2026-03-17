<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h4 class="mb-0"><i class="fas fa-edit"></i> Edit Licence</h4></div>
            <div class="card-body">
                <?php echo form_open('profile/licences/edit/' . $licence->id); ?>
                    <div class="mb-3">
                        <label for="title" class="form-label">Licence Title *</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo set_value('title', $licence->title); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="awarding_body" class="form-label">Awarding Body *</label>
                        <input type="text" class="form-control" id="awarding_body" name="awarding_body" value="<?php echo set_value('awarding_body', $licence->awarding_body); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="url" class="form-label">URL</label>
                        <input type="url" class="form-control" id="url" name="url" value="<?php echo set_value('url', $licence->url); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="completion_date" class="form-label">Completion Date</label>
                        <input type="date" class="form-control" id="completion_date" name="completion_date" value="<?php echo set_value('completion_date', $licence->completion_date); ?>">
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="<?php echo site_url('profile/licences'); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
                    </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
