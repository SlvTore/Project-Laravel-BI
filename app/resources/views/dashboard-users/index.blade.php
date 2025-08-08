@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Header Section -->
        <div class="col-12 mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Manajemen Pengguna</h1>
                    <p class="text-muted">Kelola tim dan akses pengguna dalam bisnis Anda</p>
                </div>
                @if(auth()->user()->isBusinessOwner())
                <div>
                    <button type="button" class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#businessCodesModal">
                        <i class="bi bi-key"></i> Lihat Kode Akses
                    </button>
                    <button type="button" class="btn btn-primary" onclick="refreshInvitationCode()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh Kode Staff
                    </button>
                </div>
                @endif
            </div>
        </div>

        <!-- Business Codes Card (for Business Owner) -->
        @if(auth()->user()->isBusinessOwner())
        <div class="col-12 mb-4">
            <div class="card border-left-primary shadow">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Kode Akses Bisnis</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800">
                                <span class="badge bg-primary me-2">ID Dashboard: <span id="display-public-id">Loading...</span></span>
                                <span class="badge bg-success">Kode Staff: <span id="display-invitation-code">Loading...</span></span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-shield-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Users Table -->
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Daftar Pengguna</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="usersTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Bergabung</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Business Codes Modal -->
@if(auth()->user()->isBusinessOwner())
<div class="modal fade" id="businessCodesModal" tabindex="-1" aria-labelledby="businessCodesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="businessCodesModalLabel">Kode Akses Bisnis</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">ID Dashboard Perusahaan</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="modal-public-id" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('modal-public-id')">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                    <small class="form-text text-muted">Bagikan ID ini kepada Staff dan Business Investigator</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Kode Undangan Staff</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="modal-invitation-code" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('modal-invitation-code')">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                    <small class="form-text text-muted">Kode rahasia khusus untuk Staff. Dapat di-refresh jika diperlukan.</small>
                </div>

                <div class="text-muted small">
                    <i class="bi bi-info-circle"></i> Kode undangan di-generate pada: <span id="modal-generated-at">-</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-warning" onclick="refreshInvitationCode()">
                    <i class="bi bi-arrow-clockwise"></i> Refresh Kode Staff
                </button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('styles')
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<style>
.card {
    border-radius: 0.35rem;
}

.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #5a5c69;
    font-size: 0.85rem;
}

.badge {
    font-size: 0.75rem;
}

#usersTable_wrapper .dataTables_filter {
    margin-bottom: 1rem;
}

#usersTable_wrapper .dataTables_length {
    margin-bottom: 1rem;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    const usersTable = $('#usersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("users.datatable") }}',
        columns: [
            {data: 'name', name: 'name'},
            {data: 'email', name: 'email'},
            {data: 'role_display', name: 'role_display', orderable: false},
            {data: 'joined_date', name: 'joined_at'},
            {data: 'status', name: 'is_active', orderable: false},
            {data: 'actions', name: 'actions', orderable: false, searchable: false}
        ],
        order: [[3, 'desc']], // Order by joined date
        pageLength: 25,
        language: {
            processing: "Memuat data...",
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data per halaman",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(difilter dari _MAX_ total data)",
            loadingRecords: "Memuat...",
            zeroRecords: "Tidak ada data yang ditemukan",
            emptyTable: "Tidak ada data tersedia",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        }
    });

    // Load business codes if user is business owner
    @if(auth()->user()->isBusinessOwner())
    loadBusinessCodes();
    @endif
});

@if(auth()->user()->isBusinessOwner())
function loadBusinessCodes() {
    fetch('{{ route("users.business-codes") }}')
        .then(response => response.json())
        .then(data => {
            document.getElementById('display-public-id').textContent = data.public_id || 'Not generated';
            document.getElementById('display-invitation-code').textContent = data.invitation_code || 'Not generated';
            
            document.getElementById('modal-public-id').value = data.public_id || '';
            document.getElementById('modal-invitation-code').value = data.invitation_code || '';
            document.getElementById('modal-generated-at').textContent = data.invitation_code_generated_at || 'Not generated';
        })
        .catch(error => {
            console.error('Error loading business codes:', error);
        });
}

function refreshInvitationCode() {
    if (!confirm('Apakah Anda yakin ingin me-refresh kode undangan staff? Kode lama akan tidak berlaku.')) {
        return;
    }

    fetch('{{ route("users.refresh-invitation-code") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadBusinessCodes();
            alert('Kode undangan staff berhasil di-refresh!');
        } else {
            alert('Gagal me-refresh kode: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error refreshing invitation code:', error);
        alert('Terjadi kesalahan saat me-refresh kode');
    });
}

function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    element.select();
    element.setSelectionRange(0, 99999);
    document.execCommand('copy');
    
    // Show feedback
    const button = element.nextElementSibling;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="bi bi-check"></i>';
    button.classList.remove('btn-outline-secondary');
    button.classList.add('btn-success');
    
    setTimeout(() => {
        button.innerHTML = originalText;
        button.classList.remove('btn-success');
        button.classList.add('btn-outline-secondary');
    }, 1000);
}
@endif

function promoteUser(userId, role) {
    if (!confirm(`Apakah Anda yakin ingin mempromosikan pengguna ini menjadi ${role}?`)) {
        return;
    }

    fetch(`{{ url('/users') }}/${userId}/promote`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({role: role})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#usersTable').DataTable().ajax.reload();
            alert(data.message);
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error promoting user:', error);
        alert('Terjadi kesalahan saat mempromosikan pengguna');
    });
}

function deleteUser(userId) {
    if (!confirm('Apakah Anda yakin ingin menghapus pengguna ini dari bisnis?')) {
        return;
    }

    fetch(`{{ url('/users') }}/${userId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#usersTable').DataTable().ajax.reload();
            alert(data.message);
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error deleting user:', error);
        alert('Terjadi kesalahan saat menghapus pengguna');
    });
}
</script>
@endpush