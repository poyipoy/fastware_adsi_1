{{-- =========================================================================
     Outstanding Material Navigation Menu Item
     Add inside Procurement dropdown menu in resources/views/layout.blade.php
     ========================================================================= --}}
@if (app(\App\Services\OutstandingMaterialAccessService::class)->canView(Auth::user()))
    <li><a class="dropdown-item" href="{{ route('outstanding-materials.index') }}">Outstanding Material</a></li>
@endif