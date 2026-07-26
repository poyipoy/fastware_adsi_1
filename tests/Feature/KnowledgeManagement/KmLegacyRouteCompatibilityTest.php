<?php

namespace Tests\Feature\KnowledgeManagement;

use App\Models\Role;
use App\Models\User;

/**
 * Regresi: memastikan route KM legacy (compatibility surface) tetap terdaftar
 * dan tidak menghasilkan 404/405 setelah perubahan Jangka Menengah.
 *
 * @group km
 * @group km-compat
 */
final class KmLegacyRouteCompatibilityTest extends KmTestCase
{
    private User $approver;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::query()->create(['role' => 'KM APPROVER']);
        $this->approver = User::factory()->create([
            'name' => 'MUGI PRAMONO',
            'role_id' => $role->id,
        ]);
    }

    public function test_pengajuanKM_route_exists(): void
    {
        $this->actingAs($this->approver)
            ->get(route('pengajuanKM'))
            ->assertSuccessful();
    }

    public function test_persetujuanKM_route_exists(): void
    {
        $this->actingAs($this->approver)
            ->get(route('persetujuanKM'))
            ->assertSuccessful();
    }

    public function test_dsKnowlege_route_exists(): void
    {
        $this->actingAs($this->approver)
            ->get(route('dsKnowlege'))
            ->assertSuccessful();
    }

    public function test_approveKM_route_name_preserved(): void
    {
        // Route harus masih terdaftar (tidak berganti nama)
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('approveKM'));
    }

    public function test_storeKM_route_name_preserved(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('storeKM'));
    }

    public function test_kirimKM_route_name_preserved(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('kirimKM'));
    }

    public function test_medium_routes_are_registered(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('km.bookmarks.store'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('km.bookmarks.destroy'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('km.documents.autosave'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('km.documents.thumbnail'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('km.co-authors.options'));
    }

    public function test_all_legacy_and_canonical_route_names_remain_registered(): void
    {
        foreach ([
            'pengajuanKM',
            'dsKnowlege',
            'persetujuanKM',
            'kmTransaksi.markAsRead',
            'kmTransaksi.saveTransaction',
            'storeKM',
            'updateKM',
            'editKM',
            'showPersetujuan',
            'approveKM',
            'updateStatusKM',
            'kirimKM',
            'kmSuka.like',
            'kmSuka.unlike',
            'insights.add',
            'km.documents.preview',
            'km.documents.download',
            'km.bookmarks.store',
            'km.bookmarks.destroy',
            'km.documents.autosave',
            'km.documents.thumbnail',
            'km.co-authors.options',
            'km.approvals.bulk',
            'km.analytics.popular',
            'km.analytics.popular.export.xlsx',
            'km.analytics.popular.export.pdf',
        ] as $routeName) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Route::has($routeName),
                "Route {$routeName} harus tetap terdaftar.",
            );
        }
    }

    public function test_long_term_routes_are_registered_and_pending_progress_route_remains_absent(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('km.progress.save'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('km.approvals.bulk'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('km.analytics.popular'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('km.analytics.popular.export.xlsx'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('km.analytics.popular.export.pdf'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('km.documents.preview'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('km.documents.download'));
    }

    public function test_unauthenticated_user_is_redirected_to_login_on_pengajuan_route(): void
    {
        $this->get(route('pengajuanKM'))->assertRedirect(route('login'));
    }

    public function test_unauthenticated_user_is_redirected_to_login_on_approval_route(): void
    {
        $this->get(route('persetujuanKM'))->assertRedirect(route('login'));
    }

    public function test_unauthenticated_user_is_redirected_to_login_on_dashboard_route(): void
    {
        $this->get(route('dsKnowlege'))->assertRedirect(route('login'));
    }
}
