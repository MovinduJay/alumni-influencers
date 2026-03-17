<h3><i class="fas fa-calendar-check"></i> Alumni Event Participations</h3>
<a href="<?php echo site_url('bidding'); ?>" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Bidding</a>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Record Event Participation</h5></div>
    <div class="card-body">
        <p class="text-muted">Recording one university alumni event in the current month unlocks a 4th featured slot.</p>
        <?php echo form_open('bidding/events/add'); ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <input type="text" name="event_name" class="form-control" placeholder="Event name" required>
                </div>
                <div class="col-md-3">
                    <input type="date" name="event_date" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-success w-100"><i class="fas fa-plus"></i> Record Event</button>
                </div>
            </div>
        <?php echo form_close(); ?>
    </div>
</div>

<div class="alert alert-info">
    <strong>Current Monthly Feature Allowance:</strong> <?php echo (int) $max_wins; ?>
</div>

<?php if (!empty($events)): ?>
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Event</th>
                <th>Date</th>
                <th>Recorded</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($events as $event): ?>
            <tr>
                <td><?php echo htmlspecialchars($event->event_name, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($event->event_date, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($event->created_at, ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <?php echo form_open('bidding/events/delete/' . $event->id, array('onsubmit' => "return confirm('Delete this event participation?');")); ?>
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i> Delete</button>
                    <?php echo form_close(); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="alert alert-warning mb-0">No alumni event participations recorded yet.</div>
<?php endif; ?>
