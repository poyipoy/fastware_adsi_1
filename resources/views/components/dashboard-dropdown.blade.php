@if($visible)
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle font-si" href="#" id="{{ $dropdownId }}"
        role="button" data-bs-toggle="dropdown" aria-expanded="false">
        {{ $label }}
    </a>
    <ul class="dropdown-menu" aria-labelledby="{{ $dropdownId }}">
        @foreach($items as $item)
            @if($item['visible'] ?? false)
                <li>
                    <a class="dropdown-item" href="{{ route($item['route']) }}">
                        {{ $item['label'] }}
                    </a>
                </li>
            @endif
        @endforeach
    </ul>
</li>
@endif

