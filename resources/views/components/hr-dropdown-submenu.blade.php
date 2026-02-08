@if($hasVisibleItems())
<li>
    <a class="dropdown-item dropdown-toggle" href="#" id="{{ $childDropdownId }}"
        role="button" data-bs-toggle="dropdown" aria-expanded="false">
        {{ $title }}
    </a>
    <ul class="dropdown-menu" aria-labelledby="{{ $childDropdownId }}">
        @foreach($items as $item)
            <li>
                <a class="dropdown-item" href="{{ route($item['route']) }}">
                    {{ $item['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</li>
@endif

