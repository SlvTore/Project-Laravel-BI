<!-- Edit Record Modal -->
<div class="modal fade" id="editRecordModal" tabindex="-1" aria-labelledby="editRecordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editRecordModalLabel">
                    <i class="bi bi-pencil me-2"></i>
                    Edit Data Record
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" id="editRecordForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_record_id" name="record_id">

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_value" class="form-label">Nilai</label>
                        <div class="input-group">
                            @if($businessMetric->unit == 'Rp')
                                <span class="input-group-text">Rp</span>
                            @endif
                            <input type="number" class="form-control" id="edit_value" name="value" step="0.01" min="0" required>
                            @if($businessMetric->unit == '%')
                                <span class="input-group-text">%</span>
                            @elseif($businessMetric->unit && $businessMetric->unit != 'Rp')
                                <span class="input-group-text">{{ $businessMetric->unit }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_notes" class="form-label">Catatan</label>
                        <textarea class="form-control" id="edit_notes" name="notes" rows="3"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check me-2"></i>
                        Update Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update form action when modal is shown
    document.getElementById('editRecordModal').addEventListener('show.bs.modal', function () {
        const recordId = document.getElementById('edit_record_id').value;
        const form = document.getElementById('editRecordForm');
        form.action = `{{ route('dashboard.metrics.records.update', ['businessMetric' => $businessMetric->id, 'record' => '__ID__']) }}`.replace('__ID__', recordId);
    });
});
</script>
