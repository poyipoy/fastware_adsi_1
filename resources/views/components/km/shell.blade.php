@props(['active'])

<main id="main" class="main km-app">
    <a class="km-skip-link" href="#km-main-content">Lewati navigasi KM</a>

    <div class="km-workspace">
        <div class="km-sidebar-toggle-row">
            <div>
                <span class="km-sidebar-kicker">Knowledge Management</span>
                <span class="fw-semibold">Workspace KM</span>
            </div>
            <button class="btn btn-outline-primary btn-sm" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#kmWorkspaceNavigation"
                aria-controls="kmWorkspaceNavigation" aria-label="Buka navigasi Knowledge Management">
                <i class="bi bi-list" aria-hidden="true"></i>
                <span>Menu</span>
            </button>
        </div>

        <aside class="offcanvas offcanvas-start km-sidebar" tabindex="-1" id="kmWorkspaceNavigation"
            aria-labelledby="kmWorkspaceNavigationLabel">
            <div class="offcanvas-header">
                <div>
                    <span class="km-sidebar-kicker">Knowledge Management</span>
                    <h2 class="km-sidebar-title" id="kmWorkspaceNavigationLabel">Workspace KM</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup navigasi"></button>
            </div>
            <div class="offcanvas-body p-0">
                <div class="d-none d-lg-block px-4 pt-4 pb-2">
                    <span class="km-sidebar-kicker">Knowledge Management</span>
                    <p class="km-sidebar-title">Workspace KM</p>
                </div>
                <nav class="km-sidebar-nav" aria-label="Navigasi Knowledge Management">
                    @can('viewAny', \App\Models\KmPengajuan::class)
                        <a class="km-sidebar-link" href="{{ route('dsKnowlege') }}"
                            @if ($active === 'library') aria-current="page" @endif>
                            <i class="bi bi-collection" aria-hidden="true"></i>
                            <span>Library</span>
                        </a>
                    @endcan

                    @can('create', \App\Models\KmPengajuan::class)
                        <a class="km-sidebar-link" href="{{ route('pengajuanKM') }}"
                            @if ($active === 'submissions') aria-current="page" @endif>
                            <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                            <span>Pengajuan Saya</span>
                        </a>
                    @endcan

                    @can('bulkApprove', \App\Models\KmPengajuan::class)
                        <a class="km-sidebar-link" href="{{ route('persetujuanKM') }}"
                            @if ($active === 'approvals') aria-current="page" @endif>
                            <i class="bi bi-check2-square" aria-hidden="true"></i>
                            <span>Persetujuan</span>
                        </a>
                    @endcan

                    @can('viewPopularAnalytics', \App\Models\KmPengajuan::class)
                        <a class="km-sidebar-link" href="{{ route('km.analytics.popular') }}"
                            @if ($active === 'popular') aria-current="page" @endif>
                            <i class="bi bi-bar-chart" aria-hidden="true"></i>
                            <span>Materi Populer</span>
                        </a>
                    @endcan
                </nav>
            </div>
        </aside>

        <div class="km-main-panel" id="km-main-content" tabindex="-1">
            {{ $slot }}
        </div>
    </div>

    <x-km.feedback :dialogs="true" />
</main>

