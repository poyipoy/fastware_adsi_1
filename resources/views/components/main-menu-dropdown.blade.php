@if($visible)
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle font-si" href="#" id="{{ $dropdownId }}"
        role="button" data-bs-toggle="dropdown" aria-expanded="false">
        {{ $label }}
    </a>
    <ul class="dropdown-menu" aria-labelledby="{{ $dropdownId }}">
        @foreach($items as $item)
            @if(isset($item['title']))
                {{-- This is a submenu --}}
                <x-menu-dropdown-submenu 
                    :title="$item['title']"
                    :items="$item['items']"
                    :childDropdownId="'submenu-' . $loop->index"
                />
            @elseif($item['visible'] ?? false)
                {{-- This is a direct menu item --}}
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

