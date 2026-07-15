<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ServerDrivenController extends Controller
{
    /**
     * Handle an incoming login request and respond with controller-driven UI metadata.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('username', $credentials['username'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Username atau password salah.',
            ], 422);
        }

        Auth::login($user);

        $token = $user->createToken('android-client')->plainTextToken;
        $defaultRoute = 'profile';

        $drawer = $this->buildDrawer($user);
        $profilePage = $this->buildProfilePage($user);

        return response()->json([
            'token' => $token,
            'defaultRoute' => $defaultRoute,
            'profilePage' => $profilePage,
            'drawer' => $drawer,
        ]);
    }

    /**
     * Handle logout by revoking the current access token and ending the session.
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $token = $user->currentAccessToken();

            if ($token) {
                $token->delete();
            }

            Auth::logout();
        }

        return response()->json([
            'message' => 'Berhasil keluar dari aplikasi.',
        ]);
    }

    /**
     * Return the navigation drawer metadata for the logged-in user.
     */
    public function drawer(Request $request)
    {
        $user = $request->user();

        return response()->json($this->buildDrawer($user));
    }

    /**
     * Resolve and return the metadata for the requested page route.
     */
    public function page(Request $request, string $route)
    {
        $user = $request->user();
        $page = $this->resolvePage($user, $route, $request);

        if ($page === null) {
            return response()->json([
                'message' => 'Halaman tidak ditemukan.',
            ], 404);
        }

        return response()->json($page);
    }

    /**
     * Example handler for form submissions from server-driven layouts.
     */
    public function submit(Request $request, string $route)
    {
        $payload = $request->validate([
            'values' => ['nullable', 'array'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diterima untuk rute ' . $route,
            'values' => $payload['values'] ?? [],
        ]);
    }

    /**
     * Build controller-driven UI metadata for the profile page.
     */
    protected function buildProfilePage(User $user): array
    {
        $roles = method_exists($user, 'roles')
            ? $user->roles()->pluck('role')->implode(', ')
            : ($user->role ?? '-');

        $now = Carbon::now();
        $start = $now->copy()->subDays(6)->startOfDay();

        $registrationSummary = User::selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('created_at', '>=', $start)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $dates = collect(range(0, 6))->map(fn (int $offset) => $start->copy()->addDays($offset));

        $chartCategories = $dates->map(fn (Carbon $date) => $date->translatedFormat('d M'))->values();
        $chartValues = $dates->map(function (Carbon $date) use ($registrationSummary) {
            $record = $registrationSummary->get($date->toDateString());

            return $record ? (float) $record->total : 0.0;
        })->values();

        return [
            'topBar' => [
                'title' => 'Profil Pengguna',
            ],
            'body' => [
                [
                    'type' => 'text',
                    'props' => [
                        'value' => 'Selamat datang, ' . $user->name,
                        'style' => 'title',
                    ],
                ],
                [
                    'type' => 'table',
                    'props' => [
                        'title' => 'Informasi Akun',
                        'rows' => [
                            [
                                'field' => 'Nama Lengkap',
                                'value' => $user->name,
                            ],
                            [
                                'field' => 'Username',
                                'value' => $user->username,
                            ],
                            [
                                'field' => 'Email',
                                'value' => $user->email,
                            ],
                            [
                                'field' => 'Peran',
                                'value' => $roles ?: '-',
                            ],
                        ],
                    ],
                ],
                [
                    'type' => 'chart',
                    'props' => [
                        'title' => 'Registrasi Pengguna 7 Hari Terakhir',
                        'description' => 'Data berasal dari tabel users dan dapat disesuaikan melalui controller Laravel.',
                        'chartType' => 'bar',
                        'categories' => $chartCategories->toArray(),
                        'series' => [
                            [
                                'id' => 'registrations',
                                'label' => 'Registrasi',
                                'colorHex' => '#16A34A',
                                'values' => $chartValues->toArray(),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build the navigation drawer definition from the logged-in user's context.
     */
    protected function buildDrawer(?User $user): array
    {
        $definition = config('server_driven_navigation.sections', []);
        $sections = [];

        foreach ($definition as $item) {
            $transformed = $this->transformNavigationNode($user, $item);

            if ($transformed !== null) {
                $sections[] = $transformed;
            }
        }

        return ['sections' => $sections];
    }

    /**
     * Resolve a route name to a controller-generated layout.
     */
    protected function resolvePage(?User $user, string $route, Request $request): ?array
    {
        if ($route === 'profile') {
            return $this->buildProfilePage($user ?? new User([
                'name' => 'Pengguna',
                'username' => 'pengguna',
                'email' => 'pengguna@example.com',
            ]));
        }

        if ($route === 'dashboardTCPD') {
            return $this->buildDashboardTcpdPage($request);
        }

        $available = $this->navigationRouteLabels();

        if (! array_key_exists($route, $available)) {
            return null;
        }

        return [
            'topBar' => [
                'title' => $available[$route],
            ],
            'body' => [],
        ];
    }

    protected function buildDashboardTcpdPage(Request $request): array
    {
        $response = app(DashboardController::class)->tcpdOverview($request);
        $payload = $response->getData(true);
        $data = $payload['data'] ?? null;

        if (! ($payload['success'] ?? false) || ! is_array($data)) {
            $message = $payload['message'] ?? 'Data dashboard belum tersedia.';

            return [
                'topBar' => [
                    'title' => 'Dashboard TCPD',
                ],
                'body' => [
                    [
                        'type' => 'text',
                        'props' => [
                            'value' => $message,
                            'style' => 'body',
                        ],
                    ],
                ],
            ];
        }

        $filters = $data['filters'] ?? [];
        $companyOverview = $data['company_overview'] ?? [];
        $departmentSummaries = $data['department_summaries'] ?? [];
        $jobSummary = $data['job_summary'] ?? null;
        $prefetch = $data['prefetch_flags'] ?? [];

        $formatRange = static function ($from, $to, string $separator): string {
            $from = $from !== null && $from !== '' ? (string) $from : null;
            $to = $to !== null && $to !== '' ? (string) $to : null;

            if ($from && $to) {
                return $from . $separator . $to;
            }

            return $from ?? $to ?? '-';
        };

        $companyRange = $formatRange($filters['company_year_from'] ?? null, $filters['company_year_to'] ?? null, ' - ');
        $jobRange = $formatRange($filters['job_date_from'] ?? null, $filters['job_date_to'] ?? null, ' s/d ');

        $filterRows = [
            [
                'field' => 'Departemen',
                'value' => $filters['selected_department'] ?? '-',
            ],
            [
                'field' => 'Jabatan',
                'value' => $filters['selected_job_position_name'] ?? '-',
            ],
            [
                'field' => 'Periode Kompetensi',
                'value' => $jobRange,
            ],
            [
                'field' => 'Tahun Perbandingan',
                'value' => $companyRange,
            ],
        ];

        $companyRows = $companyOverview['chartRows'] ?? [];
        $companyCategories = [];
        $companyValues = [];

        foreach ($companyRows as $row) {
            $companyCategories[] = $row['label'] ?? 'Departemen';
            $value = $row['percentage'] ?? null;
            $companyValues[] = is_numeric($value) ? round((float) $value, 2) : null;
        }

        $companyChart = ! empty($companyCategories) ? [
            'type' => 'chart',
            'props' => [
                'title' => 'Rata-rata TCPD Per Departemen',
                'chartType' => 'bar',
                'categories' => $companyCategories,
                'description' => ($companyOverview['mode'] ?? null) === 'yearly'
                    ? 'Menampilkan performa per departemen per tahun.'
                    : 'Menampilkan rata-rata agregat per departemen.',
                'series' => [
                    [
                        'id' => 'tcpd-percentage',
                        'label' => 'Persentase',
                        'colorHex' => '#2563EB',
                        'values' => $companyValues,
                    ],
                ],
            ],
        ] : null;

        $departmentTableRows = [];
        foreach ($departmentSummaries as $summary) {
            $departmentTableRows[] = [
                'field' => $summary['department'] ?? '-',
                'value' => isset($summary['overall']) && $summary['overall'] !== null
                    ? number_format((float) $summary['overall'], 2) . '%'
                    : 'Tidak ada data',
            ];
        }

        $jobSummaryRows = [];
        if (is_array($jobSummary)) {
            $jobSummaryRows = [
                [
                    'field' => 'Total Pengguna',
                    'value' => (string) ($jobSummary['qty'] ?? 0),
                ],
                [
                    'field' => 'Rata-rata Total',
                    'value' => isset($jobSummary['total_percentage']) && $jobSummary['total_percentage'] !== null
                        ? number_format((float) $jobSummary['total_percentage'], 2) . '%'
                        : '-',
                ],
                [
                    'field' => 'Periode Penilaian',
                    'value' => $formatRange(
                        $jobSummary['date_range']['from'] ?? null,
                        $jobSummary['date_range']['to'] ?? null,
                        ' s/d '
                    ),
                ],
            ];
        }

        $userSummaries = is_array($jobSummary) ? ($jobSummary['user_summaries'] ?? []) : [];
        $userCategories = [];
        $tcValues = [];
        $skValues = [];
        $adValues = [];

        foreach ($userSummaries as $summary) {
            $userCategories[] = $summary['name'] ?? 'User';
            $tcValues[] = isset($summary['tc_percentage']) && $summary['tc_percentage'] !== null
                ? round((float) $summary['tc_percentage'], 2)
                : null;
            $skValues[] = isset($summary['sk_percentage']) && $summary['sk_percentage'] !== null
                ? round((float) $summary['sk_percentage'], 2)
                : null;
            $adValues[] = isset($summary['ad_percentage']) && $summary['ad_percentage'] !== null
                ? round((float) $summary['ad_percentage'], 2)
                : null;
        }

        $jobChart = ! empty($userCategories) ? [
            'type' => 'chart',
            'props' => [
                'title' => 'Pencapaian Pengguna',
                'chartType' => 'radar',
                'categories' => $userCategories,
                'series' => [
                    [
                        'id' => 'tc',
                        'label' => 'Technical',
                        'colorHex' => '#16A34A',
                        'values' => $tcValues,
                    ],
                    [
                        'id' => 'sk',
                        'label' => 'Soft Skill',
                        'colorHex' => '#DC2626',
                        'values' => $skValues,
                    ],
                    [
                        'id' => 'ad',
                        'label' => 'Additional',
                        'colorHex' => '#9333EA',
                        'values' => $adValues,
                    ],
                ],
            ],
        ] : null;

        $competencies = is_array($jobSummary) ? ($jobSummary['competencies'] ?? []) : [];
        $competencyTableRows = [];

        foreach (array_slice($competencies, 0, 10) as $row) {
            $average = $row['average'] ?? null;
            $standard = $row['standard'] ?? null;
            $belowCount = $row['qty'] ?? 0;

            $parts = [];
            $parts[] = 'Rata-rata: ' . ($average !== null ? number_format((float) $average, 2) : '-');
            $parts[] = 'Standar: ' . ($standard !== null ? number_format((float) $standard, 2) : '-');
            $parts[] = 'Di bawah standar: ' . (int) $belowCount;

            $competencyTableRows[] = [
                'field' => $row['name'] ?? 'Kompetensi',
                'value' => implode(' | ', $parts),
            ];
        }

        $body = [
            [
                'type' => 'table',
                'props' => [
                    'title' => 'Filter Aktif',
                    'rows' => $filterRows,
                ],
            ],
        ];

        if ($companyChart) {
            $body[] = $companyChart;
        } else {
            $body[] = [
                'type' => 'text',
                'props' => [
                    'value' => 'Data perusahaan belum tersedia.',
                    'style' => 'body',
                ],
            ];
        }

        if (! empty($departmentTableRows)) {
            $body[] = [
                'type' => 'table',
                'props' => [
                    'title' => 'Ringkasan Departemen',
                    'rows' => $departmentTableRows,
                ],
            ];
        }

        if (! empty($jobSummaryRows)) {
            $body[] = [
                'type' => 'table',
                'props' => [
                    'title' => 'Ringkasan Jabatan',
                    'rows' => $jobSummaryRows,
                ],
            ];
        }

        if ($jobChart) {
            $body[] = $jobChart;
        }

        if (! empty($competencyTableRows)) {
            $body[] = [
                'type' => 'table',
                'props' => [
                    'title' => 'Kompetensi Utama',
                    'rows' => $competencyTableRows,
                ],
            ];
        }

        return [
            'topBar' => [
                'title' => 'Dashboard TCPD',
            ],
            'body' => $body,
            'extras' => [
                'dataset' => $data,
                'filters' => $filters,
                'prefetch' => $prefetch,
            ],
        ];
    }

    protected function transformNavigationNode(?User $user, array $item): ?array
    {
        if (! $this->isNavigationItemVisible($user, $item)) {
            return null;
        }

        $node = [];

        if (isset($item['label'])) {
            $node['label'] = $item['label'];
        }

        if (isset($item['route'])) {
            $node['route'] = $item['route'];
        }

        if (isset($item['buttonLabel'])) {
            $node['buttonLabel'] = $item['buttonLabel'];
        }

        if (! empty($item['children']) && is_array($item['children'])) {
            $children = [];

            foreach ($item['children'] as $child) {
                $childNode = $this->transformNavigationNode($user, $child);

                if ($childNode !== null) {
                    $children[] = $childNode;
                }
            }

            if (empty($children)) {
                return isset($node['route']) ? $node : null;
            }

            $node['children'] = $children;
        }

        return $node ?: null;
    }

    protected function isNavigationItemVisible(?User $user, array $item): bool
    {
        if (! isset($item['permissions'])) {
            return true;
        }

        $permissions = $item['permissions'];

        if (isset($permissions['role_ids'])) {
            if (! $user || ! in_array((int) $user->role_id, array_map('intval', (array) $permissions['role_ids']), true)) {
                return false;
            }
        }

        if (isset($permissions['role_prefixes'])) {
            if (! $user) {
                return false;
            }

            $roleName = mb_strtoupper(trim((string) optional($user->roles)->role));
            $prefixes = array_map(fn($prefix) => mb_strtoupper(trim((string) $prefix)), (array) $permissions['role_prefixes']);
            $matchesPrefix = collect($prefixes)->contains(function ($prefix) use ($roleName) {
                return $roleName === $prefix || str_starts_with($roleName, $prefix . ' ');
            });

            if (! $matchesPrefix && isset($permissions['usernames'])) {
                $allowed = array_map('mb_strtoupper', (array) $permissions['usernames']);
                $current = mb_strtoupper((string) $user->name);
                $matchesPrefix = in_array($current, $allowed, true);
            }

            if (! $matchesPrefix) {
                return false;
            }
        }

        if (isset($permissions['usernames'])) {
            if (! $user) {
                return false;
            }

            if (isset($permissions['role_prefixes'])) {
                return true;
            }

            $allowed = array_map('mb_strtoupper', (array) $permissions['usernames']);
            $current = mb_strtoupper((string) $user->name);

            if (! in_array($current, $allowed, true)) {
                return false;
            }
        }

        return true;
    }

    protected function navigationRouteLabels(): array
    {
        static $labels = null;

        if ($labels !== null) {
            return $labels;
        }

        $labels = [];
        $definition = config('server_driven_navigation.sections', []);
        $this->collectNavigationRoutes($definition, $labels);

        return $labels;
    }

    protected function collectNavigationRoutes(array $items, array &$labels): void
    {
        foreach ($items as $item) {
            if (isset($item['route'], $item['label'])) {
                $labels[$item['route']] = $item['label'];
            }

            if (! empty($item['children']) && is_array($item['children'])) {
                $this->collectNavigationRoutes($item['children'], $labels);
            }
        }
    }
}
