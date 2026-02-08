@props(['inquiry', 'showEdit' => false, 'showForm' => false, 'showDelete' => false, 'showView' => true])

<div>
    @if($showEdit && (int) $inquiry->status === 1)
        <a class="btn btn-custom-edit m-1 btn-sm" title="Edit">
            <i class="bi bi-pencil-fill" onclick="openEditInquiryModal({{ $inquiry->id }})"></i>
        </a>
    @endif

    @if($showForm && (int) $inquiry->status === 1)
        <a class="btn btn-custom-form m-1 btn-sm"
           href="{{ route('formulirInquiry', ['id' => $inquiry->id]) }}"
           title="Formulir Inquiry">
            <i class="bi bi-file-earmark-arrow-up-fill"></i>
        </a>
    @endif

    @if($showDelete && (int) $inquiry->status === 1)
        <a class="btn btn-custom-delete m-1 btn-sm" title="Delete">
            <i class="bi bi-trash-fill" onclick="deleteInquiry({{ $inquiry->id }})"></i>
        </a>
    @endif

    @if($showView)
        <a class="btn btn-custom-view m-1 btn-sm" title="View Form"
           href="{{ route('showFormSS', $inquiry->id) }}">
            <i class="bi bi-eye-fill"></i>
        </a>
    @endif
</div>

