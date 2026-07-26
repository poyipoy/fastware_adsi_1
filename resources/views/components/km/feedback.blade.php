@props([
    'errors' => null,
    'success' => null,
    'status' => null,
    'errorTitle' => 'Periksa kembali data yang Anda masukkan.',
    'dialogs' => false,
])

@if ($success || session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="status">
        <i class="bi bi-check-circle me-2" aria-hidden="true"></i>
        {{ $success ?: session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup pesan"></button>
    </div>
@endif

@if ($status || session('status'))
    <div class="alert alert-info" role="status">
        <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
        {{ $status ?: session('status') }}
    </div>
@endif

@if ($errors?->any())
    <div class="alert alert-danger km-error-summary" id="km-error-summary" role="alert" tabindex="-1">
        <div class="d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-triangle mt-1" aria-hidden="true"></i>
            <div>
                <strong>{{ $errorTitle }}</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif

@if ($dialogs)
    <div class="modal fade" id="km-feedback-modal" tabindex="-1"
        aria-labelledby="km-feedback-modal-title" aria-describedby="km-feedback-modal-message" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="km-feedback-modal-title">Konfirmasi tindakan</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="km-modal-description mb-0" id="km-feedback-modal-message"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"
                        data-km-confirm-cancel>Batal</button>
                    <button type="button" class="btn btn-primary" data-km-confirm-accept>Lanjutkan</button>
                </div>
            </div>
        </div>
    </div>

    <div class="km-toast-region" id="km-toast-region" aria-live="polite" aria-atomic="false"></div>
@endif

