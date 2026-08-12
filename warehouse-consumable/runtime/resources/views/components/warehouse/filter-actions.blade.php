@props(['reset' => null, 'submit' => 'Terapkan filter'])

<div class="warehouse-filter-actions">
    <button class="btn btn-primary" type="submit">{{ $submit }}</button>
    @if ($reset)
        <a class="btn btn-outline-secondary" href="{{ $reset }}">Atur ulang</a>
    @endif
</div>
