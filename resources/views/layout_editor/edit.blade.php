@extends('layout')

@section('content')
    <div class="layout-editor-wrapper container-xxl px-3 px-lg-5">
        <div class="row g-3 g-lg-4">
            <div class="col-12 col-xl-10 mx-auto">
                <div class="intro-card bg-gradient-blue text-white shadow-sm rounded-4 p-4 p-lg-4">
                    <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-2 gap-lg-3">
                        <div>
                            <span class="badge rounded-pill text-bg-light text-uppercase fw-semibold small mb-2">Layout
                                Tools</span>
                            <h1 class="h4 mb-1 text-uppercase fw-bold">Editor Layout Blade</h1>
                            <p class="text-white-50 mb-0 small">File aktif:
                                <code>resources/views/layout.blade.php</code>
                            </p>
                        </div>
                        <span class="badge bg-light text-dark fw-semibold px-3 py-2">
                            <i class="bi bi-lock-fill me-2 text-primary"></i>Hanya role admin
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-10 mx-auto">
                <form action="{{ route('layout-editor.update') }}" method="POST" id="layout-editor-form"
                    class="position-relative">
                    @csrf

                    @if (session('status'))
                        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                            {{ session('status') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @error('layout_content')
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                            {{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @enderror

                    @if ($errors->any() && !$errors->has('layout_content'))
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                            {{ $errors->first() }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-0 bg-dark text-light rounded-4">
                            <div class="bg-light text-dark px-4 px-md-5 py-4 border-bottom rounded-top-4">
                                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 gap-lg-4">
                                    <div>
                                        <h2 class="h5 mb-1 fw-semibold">Kode Layout</h2>
                                        <p class="mb-0 small text-muted">Modifikasi kode dengan hati-hati agar sintaks Blade dan HTML tetap valid.</p>
                                    </div>
                                    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2">
                                        <div class="input-group input-group-sm code-search">
                                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                            <input type="text" id="codeSearch" class="form-control border-start-0"
                                                placeholder="Cari teks (Ctrl+F)" autocomplete="off">
                                            <button type="button" class="btn btn-outline-secondary"
                                                id="codeSearchSubmit" title="Cari">
                                                <i class="bi bi-search"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary"
                                                id="codeSearchPrev" title="Sebelumnya (Shift+Enter)">
                                                <i class="bi bi-arrow-up"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary"
                                                id="codeSearchNext" title="Berikutnya (Enter)">
                                                <i class="bi bi-arrow-down"></i>
                                            </button>
                                        </div>
                                        <span class="badge rounded-pill text-bg-secondary">Ctrl + S untuk simpan</span>
                                    </div>
                                </div>
                            </div>
                            <div class="p-3 p-md-4 p-lg-5">
                                <textarea id="layout_content" name="layout_content" spellcheck="false"
                                    class="form-control code-editor">{{ old('layout_content', $content) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg layout-editor-save shadow">
                        Simpan Layout
                    </button>
                </form>
            </div>
        </div>
        </div>

        <style>
        .layout-editor-wrapper {
            margin-top: clamp(4.25rem, 5.5vw, 6rem);
            margin-bottom: clamp(4rem, 6vw, 6rem);
            padding-top: clamp(0.75rem, 2vw, 1.5rem);
            padding-bottom: clamp(1rem, 3vw, 2.5rem);
            scroll-margin-top: 6rem;
        }

        .layout-editor-wrapper .card {
            border-radius: 1rem;
        }

        .layout-editor-wrapper .code-editor {
            min-height: 65vh;
            font-family: 'Fira Code', 'Consolas', 'Courier New', monospace;
            font-size: 0.9rem;
            line-height: 1.6;
            background: #0f172a;
            color: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.25);
            border-radius: 0.85rem;
            box-shadow: inset 0 2px 6px rgba(15, 23, 42, 0.35);
            resize: vertical;
        }

        .layout-editor-wrapper .code-editor:focus {
            box-shadow: inset 0 2px 6px rgba(15, 23, 42, 0.35), 0 0 0 0.25rem rgba(59, 130, 246, 0.35);
            border-color: rgba(59, 130, 246, 0.6);
        }

        .code-search .form-control {
            min-width: 210px;
        }

        .layout-editor-save {
            position: fixed;
            bottom: 32px;
            right: 32px;
            padding: 0.85rem 2.75rem;
            border-radius: 999px;
            font-weight: 600;
            letter-spacing: 0.02em;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .layout-editor-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 24px 50px rgba(37, 99, 235, 0.35);
        }

        .bg-gradient-blue {
            background: linear-gradient(135deg, #2563eb 0%, #1e3a8a 100%);
        }

        @media (max-width: 992px) {
            .layout-editor-save {
                bottom: 20px;
                right: 20px;
                left: 20px;
                border-radius: 0.85rem;
                width: auto;
            }

            .layout-editor-wrapper {
                margin-bottom: 8rem;
            }
        }
    </style>
@endsection

@section('scripts')
    <script>
        const formEl = document.getElementById('layout-editor-form');
        const textareaEl = document.getElementById('layout_content');
        const searchInputEl = document.getElementById('codeSearch');
        const searchBtnEl = document.getElementById('codeSearchSubmit');
        const nextBtnEl = document.getElementById('codeSearchNext');
        const prevBtnEl = document.getElementById('codeSearchPrev');

        document.addEventListener('keydown', function(event) {
            const isMac = navigator.platform.toUpperCase().includes('MAC');
            const modifierActive = isMac ? event.metaKey : event.ctrlKey;

            if (!modifierActive) {
                return;
            }

            const key = event.key.toLowerCase();

            if (key === 's' && formEl) {
                event.preventDefault();
                formEl.requestSubmit();
                return;
            }

            if (key === 'f' && searchInputEl) {
                event.preventDefault();
                searchInputEl.focus();
                searchInputEl.select();
            }
        });

        (function(textarea, searchInput, searchButton, nextBtn, prevBtn) {
            if (!textarea || !searchInput) {
                return;
            }

            let lastIndex = -1;
            let searchTerm = '';
            let searchInitialized = false;

            const executeSearch = (direction = 1, reset = false) => {
                if (!searchTerm) {
                    return;
                }

                const value = textarea.value;
                const term = searchTerm;

                if (!value || !term) {
                    return;
                }

                if (reset) {
                    lastIndex = -1;
                }

                const selectionStart = textarea.selectionStart;
                const selectionEnd = textarea.selectionEnd;

                if (direction === 1) {
                    let startIndex;
                    if (lastIndex !== -1 && !reset) {
                        startIndex = lastIndex + term.length;
                    } else {
                        startIndex = selectionEnd;
                    }

                    if (startIndex >= value.length) {
                        startIndex = 0;
                    }

                    lastIndex = value.indexOf(term, startIndex);

                    if (lastIndex === -1 && startIndex > 0) {
                        lastIndex = value.indexOf(term, 0);
                    }
                } else {
                    let startIndex;
                    if (lastIndex !== -1 && !reset) {
                        startIndex = lastIndex - 1;
                    } else {
                        startIndex = selectionStart - 1;
                    }

                    if (startIndex < 0) {
                        startIndex = value.length;
                    }

                    lastIndex = value.lastIndexOf(term, startIndex);

                    if (lastIndex === -1 && startIndex < value.length) {
                        lastIndex = value.lastIndexOf(term, value.length);
                    }
                }

                if (lastIndex !== -1) {
                    textarea.focus();
                    textarea.setSelectionRange(lastIndex, lastIndex + term.length);
                    textarea.scrollTop = textarea.scrollHeight * (lastIndex / value.length);
                }
            };

            const initializeSearch = (direction = 1) => {
                const term = searchInput.value.trim();
                if (!term) {
                    return;
                }

                searchTerm = term;
                searchInitialized = true;
                executeSearch(direction, true);
            };

            searchInput.addEventListener('input', () => {
                searchTerm = searchInput.value.trim();
                searchInitialized = false;
                lastIndex = -1;
            });

            searchInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    initializeSearch(event.shiftKey ? -1 : 1);
                } else if (event.key === 'Escape') {
                    searchInput.value = '';
                    searchTerm = '';
                    searchInitialized = false;
                    lastIndex = -1;
                    textarea.focus();
                }
            });

            searchButton?.addEventListener('click', () => initializeSearch(1));

            nextBtn?.addEventListener('click', () => {
                if (!searchInitialized) {
                    initializeSearch(1);
                    return;
                }

                executeSearch(1);
            });

            prevBtn?.addEventListener('click', () => {
                if (!searchInitialized) {
                    initializeSearch(-1);
                    return;
                }

                executeSearch(-1);
            });
        })(textareaEl, searchInputEl, searchBtnEl, nextBtnEl, prevBtnEl);
    </script>
@endsection
