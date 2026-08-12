<main id="main" class="main km-app">
    <a class="km-skip-link" href="#km-main-content">Lewati ke konten KM</a>

    <div class="km-workspace">
        <div class="km-main-panel" id="km-main-content" tabindex="-1">
            {{ $slot }}
        </div>
    </div>

    <x-km.feedback :dialogs="true" />
</main>

<script>
window.kmShellConfig = {
    csrfToken: @js(csrf_token()),
    indexUrl: @js(route('km.notifications.index')),
    readUrlTemplate: @js(route('km.notifications.read', ['notification' => '__KM_NOTIFICATION__'])),
    readAllUrl: @js(route('km.notifications.read-all')),
    documentUrlTemplate: @js(route('dsKnowlege', ['document' => '__KM_ID__'])),
};
</script>

@push('scripts')
    @vite('resources/js/km/shell.js')
@endpush
