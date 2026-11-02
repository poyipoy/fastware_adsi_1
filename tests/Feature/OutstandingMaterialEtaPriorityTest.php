<?php

namespace Tests\Feature;

use App\Http\Controllers\OutstandingMaterialController;
use App\Models\OutstandingMaterial;
use App\Models\OutstandingMaterialInvoice;
use App\Models\User;
use App\Services\OutstandingMaterialAccessService;
use App\Services\OutstandingMaterialBatchService;
use App\Services\OutstandingMaterialDocumentService;
use App\Services\OutstandingMaterialIdentityService;
use App\Services\OutstandingMaterialImportPreviewService;
use App\Services\OutstandingMaterialInvoiceService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class OutstandingMaterialEtaPriorityTest extends TestCase
{
    use DatabaseTransactions;

    private OutstandingMaterialIdentityService $identity;

    private OutstandingMaterialController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identity = new OutstandingMaterialIdentityService();
        $documents = new OutstandingMaterialDocumentService();
        $invoices = new OutstandingMaterialInvoiceService($this->identity);

        $this->controller = new OutstandingMaterialController(
            new TestEtaPriorityAccessService(),
            $documents,
            $this->identity,
            new OutstandingMaterialBatchService(
                $this->identity,
                $documents,
                $invoices,
            ),
            new OutstandingMaterialImportPreviewService($this->identity, $documents, $invoices),
            $invoices,
        );

        Auth::setUser(new User(['id' => 1, 'name' => 'ADMINISTRATOR']));
    }

    public function test_all_five_priority_tiers_ranked_in_exact_order(): void
    {
        $today = now('Asia/Jakarta')->toDateString();
        $past = now('Asia/Jakarta')->subDays(5)->toDateString();
        $future = now('Asia/Jakarta')->addDays(5)->toDateString();

        // Tier 1: Overdue unreceived (ETA passed, no delay ETA)
        $t1 = OutstandingMaterial::create([
            'supplier' => 'Supplier Priority',
            'number_invoice' => 'INV-T1',
            'invoice_identity_key' => $this->identity->invoiceKey('Supplier Priority', 'INV-T1'),
            'type' => 'Tier 1 Overdue',
            'status' => 'On Shipment',
            'estimasi_eta_warehouse' => $past,
            'estimasi_delay_eta_warehouse' => null,
        ]);

        // Tier 2: Rescheduled unreceived (ETA passed, future delay ETA)
        $t2 = OutstandingMaterial::create([
            'supplier' => 'Supplier Priority',
            'number_invoice' => 'INV-T2',
            'invoice_identity_key' => $this->identity->invoiceKey('Supplier Priority', 'INV-T2'),
            'type' => 'Tier 2 Rescheduled',
            'status' => 'On Shipment',
            'estimasi_eta_warehouse' => $past,
            'estimasi_delay_eta_warehouse' => $future,
        ]);

        // Tier 3: Upcoming unreceived (ETA in future)
        $t3 = OutstandingMaterial::create([
            'supplier' => 'Supplier Priority',
            'number_invoice' => 'INV-T3',
            'invoice_identity_key' => $this->identity->invoiceKey('Supplier Priority', 'INV-T3'),
            'type' => 'Tier 3 Upcoming',
            'status' => 'On Production',
            'estimasi_eta_warehouse' => $future,
            'estimasi_delay_eta_warehouse' => null,
        ]);

        // Tier 4: Pending missing/invalid ETA
        $t4 = OutstandingMaterial::create([
            'supplier' => 'Supplier Priority',
            'number_invoice' => 'INV-T4',
            'invoice_identity_key' => $this->identity->invoiceKey('Supplier Priority', 'INV-T4'),
            'type' => 'Tier 4 Unscheduled',
            'status' => 'On Production',
            'estimasi_eta_warehouse' => null,
            'estimasi_delay_eta_warehouse' => null,
        ]);

        // Tier 5: Received material (even with overdue date)
        $t5 = OutstandingMaterial::create([
            'supplier' => 'Supplier Priority',
            'number_invoice' => 'INV-T5',
            'invoice_identity_key' => $this->identity->invoiceKey('Supplier Priority', 'INV-T5'),
            'type' => 'Tier 5 Received',
            'status' => 'Received',
            'keterangan' => 'Closed',
            'estimasi_eta_warehouse' => $past,
            'estimasi_delay_eta_warehouse' => null,
        ]);

        $response = $this->controller->data(Request::create('/outstanding-materials/data', 'GET', [
            'start' => 0,
            'length' => 100,
        ]));
        $data = $response->getData(true)['data'];
        $ids = array_map(fn ($r) => (int) $r['id'], $data);

        $pos1 = array_search($t1->id, $ids);
        $pos2 = array_search($t2->id, $ids);
        $pos3 = array_search($t3->id, $ids);
        $pos4 = array_search($t4->id, $ids);
        $pos5 = array_search($t5->id, $ids);

        $this->assertNotFalse($pos1, 'Tier 1 must be present');
        $this->assertNotFalse($pos2, 'Tier 2 must be present');
        $this->assertNotFalse($pos3, 'Tier 3 must be present');
        $this->assertNotFalse($pos4, 'Tier 4 must be present');
        $this->assertNotFalse($pos5, 'Tier 5 must be present');

        $this->assertLessThan($pos2, $pos1, 'Tier 1 must rank before Tier 2');
        $this->assertLessThan($pos3, $pos2, 'Tier 2 must rank before Tier 3');
        $this->assertLessThan($pos4, $pos3, 'Tier 3 must rank before Tier 4');
        $this->assertLessThan($pos5, $pos4, 'Tier 4 must rank before Tier 5');
    }

    public function test_tier1_sorted_by_oldest_overdue_date_ascending(): void
    {
        $olderPast = now('Asia/Jakarta')->subDays(20)->toDateString();
        $recentPast = now('Asia/Jakarta')->subDays(3)->toDateString();

        $older = OutstandingMaterial::create([
            'supplier' => 'Supplier Older',
            'number_invoice' => 'INV-OLDER',
            'invoice_identity_key' => $this->identity->invoiceKey('Supplier Older', 'INV-OLDER'),
            'type' => 'Older Overdue',
            'status' => 'On Shipment',
            'estimasi_eta_warehouse' => $olderPast,
        ]);

        $recent = OutstandingMaterial::create([
            'supplier' => 'Supplier Recent',
            'number_invoice' => 'INV-RECENT',
            'invoice_identity_key' => $this->identity->invoiceKey('Supplier Recent', 'INV-RECENT'),
            'type' => 'Recent Overdue',
            'status' => 'On Shipment',
            'estimasi_eta_warehouse' => $recentPast,
        ]);

        $response = $this->controller->data(Request::create('/outstanding-materials/data', 'GET', [
            'start' => 0,
            'length' => 100,
        ]));
        $data = $response->getData(true)['data'];
        $ids = array_map(fn ($r) => (int) $r['id'], $data);

        $posOlder = array_search($older->id, $ids);
        $posRecent = array_search($recent->id, $ids);

        $this->assertLessThan($posRecent, $posOlder, 'Oldest overdue must appear first in Tier 1');
    }

    public function test_tier2_sorted_by_nearest_future_delay_eta_ascending(): void
    {
        $past = now('Asia/Jakarta')->subDays(5)->toDateString();
        $nearFuture = now('Asia/Jakarta')->addDays(2)->toDateString();
        $farFuture = now('Asia/Jakarta')->addDays(20)->toDateString();

        $nearDelay = OutstandingMaterial::create([
            'supplier' => 'Supplier Delay Near',
            'number_invoice' => 'INV-DELAY-NEAR',
            'invoice_identity_key' => $this->identity->invoiceKey('Supplier Delay Near', 'INV-DELAY-NEAR'),
            'type' => 'Near Delay',
            'status' => 'On Shipment',
            'estimasi_eta_warehouse' => $past,
            'estimasi_delay_eta_warehouse' => $nearFuture,
        ]);

        $farDelay = OutstandingMaterial::create([
            'supplier' => 'Supplier Delay Far',
            'number_invoice' => 'INV-DELAY-FAR',
            'invoice_identity_key' => $this->identity->invoiceKey('Supplier Delay Far', 'INV-DELAY-FAR'),
            'type' => 'Far Delay',
            'status' => 'On Shipment',
            'estimasi_eta_warehouse' => $past,
            'estimasi_delay_eta_warehouse' => $farFuture,
        ]);

        $response = $this->controller->data(Request::create('/outstanding-materials/data', 'GET', [
            'start' => 0,
            'length' => 100,
        ]));
        $data = $response->getData(true)['data'];
        $ids = array_map(fn ($r) => (int) $r['id'], $data);

        $posNear = array_search($nearDelay->id, $ids);
        $posFar = array_search($farDelay->id, $ids);

        $this->assertLessThan($posFar, $posNear, 'Nearest future delay arrival must appear first in Tier 2');
    }

    public function test_calendar_invalid_dates_fall_back_to_tier4_pending(): void
    {
        $future = now('Asia/Jakarta')->addDays(10)->toDateString();

        // Valid future (Tier 3)
        $t3 = OutstandingMaterial::create([
            'supplier' => 'Supplier Valid',
            'number_invoice' => 'INV-VALID-T3',
            'invoice_identity_key' => $this->identity->invoiceKey('Supplier Valid', 'INV-VALID-T3'),
            'type' => 'Valid T3',
            'status' => 'On Production',
            'estimasi_eta_warehouse' => $future,
        ]);

        // Malformed strings & invalid calendar dates (Tier 4)
        $invalidDates = ['2026-02-31', '2026-04-31', '2025-02-29', 'TBA', '2026-13-40'];
        $invalidModels = [];
        foreach ($invalidDates as $idx => $invDate) {
            $invalidModels[] = OutstandingMaterial::create([
                'supplier' => 'Supplier Invalid',
                'number_invoice' => 'INV-INV-'.$idx,
                'invoice_identity_key' => $this->identity->invoiceKey('Supplier Invalid', 'INV-INV-'.$idx),
                'type' => 'Invalid Date: '.$invDate,
                'status' => 'On Production',
                'estimasi_eta_warehouse' => $invDate,
            ]);
        }

        // Received (Tier 5)
        $t5 = OutstandingMaterial::create([
            'supplier' => 'Supplier Rcv',
            'number_invoice' => 'INV-RCV-T5',
            'invoice_identity_key' => $this->identity->invoiceKey('Supplier Rcv', 'INV-RCV-T5'),
            'type' => 'Rcv T5',
            'status' => 'Received',
            'estimasi_eta_warehouse' => $future,
        ]);

        $response = $this->controller->data(Request::create('/outstanding-materials/data', 'GET', [
            'start' => 0,
            'length' => 100,
        ]));
        $data = $response->getData(true)['data'];
        $ids = array_map(fn ($r) => (int) $r['id'], $data);

        $posT3 = array_search($t3->id, $ids);
        $posT5 = array_search($t5->id, $ids);

        foreach ($invalidModels as $model) {
            $pos = array_search($model->id, $ids);
            $this->assertNotFalse($pos, 'Invalid date item must be present');
            $this->assertGreaterThan($posT3, $pos, 'Invalid date must rank after valid upcoming (Tier 3)');
            $this->assertLessThan($posT5, $pos, 'Invalid date must rank before received (Tier 5)');
        }
    }

    public function test_leap_year_february_29_is_valid(): void
    {
        // 2024-02-29 is a valid leap year date
        $leap = OutstandingMaterial::create([
            'supplier' => 'Supplier Leap',
            'number_invoice' => 'INV-LEAP',
            'invoice_identity_key' => $this->identity->invoiceKey('Supplier Leap', 'INV-LEAP'),
            'type' => 'Leap Year',
            'status' => 'On Shipment',
            'estimasi_eta_warehouse' => '2024-02-29', // past date -> Tier 1
        ]);

        $unscheduled = OutstandingMaterial::create([
            'supplier' => 'Supplier Unscheduled',
            'number_invoice' => 'INV-UNSCHED',
            'invoice_identity_key' => $this->identity->invoiceKey('Supplier Unscheduled', 'INV-UNSCHED'),
            'type' => 'No Date',
            'status' => 'On Shipment',
            'estimasi_eta_warehouse' => null, // Tier 4
        ]);

        $response = $this->controller->data(Request::create('/outstanding-materials/data', 'GET', [
            'start' => 0,
            'length' => 100,
        ]));
        $data = $response->getData(true)['data'];
        $ids = array_map(fn ($r) => (int) $r['id'], $data);

        $posLeap = array_search($leap->id, $ids);
        $posUnsched = array_search($unscheduled->id, $ids);

        // Leap date 2024-02-29 is past -> Tier 1, so it must rank before Tier 4
        $this->assertLessThan($posUnsched, $posLeap);
    }

    public function test_manual_column_ordering_overrides_priority_default(): void
    {
        $matA = OutstandingMaterial::create([
            'supplier' => 'AAA Supplier',
            'number_invoice' => 'INV-AAA',
            'invoice_identity_key' => $this->identity->invoiceKey('AAA Supplier', 'INV-AAA'),
            'type' => 'Type A',
            'status' => 'Received', // Tier 5 by default
        ]);

        $matZ = OutstandingMaterial::create([
            'supplier' => 'ZZZ Supplier',
            'number_invoice' => 'INV-ZZZ',
            'invoice_identity_key' => $this->identity->invoiceKey('ZZZ Supplier', 'INV-ZZZ'),
            'type' => 'Type Z',
            'status' => 'On Shipment', // Overdue -> Tier 1 by default
            'estimasi_eta_warehouse' => now()->subDays(10)->toDateString(),
        ]);

        // Request with manual order on column 1 (supplier) ASC
        $request = Request::create('/outstanding-materials/data', 'GET', [
            'start' => 0,
            'length' => 100,
            'order' => [
                ['column' => 1, 'dir' => 'asc'],
            ],
        ]);

        $response = $this->controller->data($request);
        $data = $response->getData(true)['data'];
        $ids = array_map(fn ($r) => (int) $r['id'], $data);

        $posA = array_search($matA->id, $ids);
        $posZ = array_search($matZ->id, $ids);

        // When manually ordered by supplier ASC, AAA comes before ZZZ regardless of priority tier
        $this->assertLessThan($posZ, $posA);
    }

    public function test_invoice_group_priority_determined_by_most_urgent_pending_material(): void
    {
        $past = now('Asia/Jakarta')->subDays(7)->toDateString();
        $future = now('Asia/Jakarta')->addDays(14)->toDateString();

        // Invoice A (Mixed): 1 Received, 1 Overdue unreceived -> inherits Tier 1
        OutstandingMaterial::create([
            'supplier' => 'Supplier Mixed',
            'number_invoice' => 'INV-GRP-MIXED',
            'invoice_identity_key' => $this->identity->invoiceKey('Supplier Mixed', 'INV-GRP-MIXED'),
            'type' => 'Received Row',
            'status' => 'Received',
            'keterangan' => 'Closed',
            'estimasi_eta_warehouse' => $past,
        ]);
        OutstandingMaterial::create([
            'supplier' => 'Supplier Mixed',
            'number_invoice' => 'INV-GRP-MIXED',
            'invoice_identity_key' => $this->identity->invoiceKey('Supplier Mixed', 'INV-GRP-MIXED'),
            'type' => 'Overdue Row',
            'status' => 'On Shipment',
            'estimasi_eta_warehouse' => $past,
        ]);

        // Invoice B (Upcoming): 2 Upcoming rows -> Tier 3
        OutstandingMaterial::create([
            'supplier' => 'Supplier Upcoming',
            'number_invoice' => 'INV-GRP-UPCOMING',
            'invoice_identity_key' => $this->identity->invoiceKey('Supplier Upcoming', 'INV-GRP-UPCOMING'),
            'type' => 'Upcoming Row 1',
            'status' => 'On Production',
            'estimasi_eta_warehouse' => $future,
        ]);
        OutstandingMaterial::create([
            'supplier' => 'Supplier Upcoming',
            'number_invoice' => 'INV-GRP-UPCOMING',
            'invoice_identity_key' => $this->identity->invoiceKey('Supplier Upcoming', 'INV-GRP-UPCOMING'),
            'type' => 'Upcoming Row 2',
            'status' => 'On Production',
            'estimasi_eta_warehouse' => $future,
        ]);

        // Invoice C (All Received): 2 Received rows -> Tier 5
        OutstandingMaterial::create([
            'supplier' => 'Supplier Received',
            'number_invoice' => 'INV-GRP-RCV',
            'invoice_identity_key' => $this->identity->invoiceKey('Supplier Received', 'INV-GRP-RCV'),
            'type' => 'Rcv 1',
            'status' => 'Received',
            'keterangan' => 'Closed',
            'estimasi_eta_warehouse' => $past,
        ]);
        OutstandingMaterial::create([
            'supplier' => 'Supplier Received',
            'number_invoice' => 'INV-GRP-RCV',
            'invoice_identity_key' => $this->identity->invoiceKey('Supplier Received', 'INV-GRP-RCV'),
            'type' => 'Rcv 2',
            'status' => 'Received',
            'keterangan' => 'Closed',
            'estimasi_eta_warehouse' => $past,
        ]);

        $response = $this->controller->invoiceData(Request::create('/outstanding-materials/show-based-on-invoice/data', 'GET', [
            'start' => 0,
            'length' => 100,
        ]));
        $data = $response->getData(true)['data'];
        $invoices = array_map(fn ($r) => html_entity_decode(strip_tags((string) $r['number_invoice'])), $data);

        $posMixed = array_search('INV-GRP-MIXED', $invoices);
        $posUpcoming = array_search('INV-GRP-UPCOMING', $invoices);
        $posRcv = array_search('INV-GRP-RCV', $invoices);

        $this->assertNotFalse($posMixed, 'Mixed invoice must be present');
        $this->assertNotFalse($posUpcoming, 'Upcoming invoice must be present');
        $this->assertNotFalse($posRcv, 'Received invoice must be present');

        // Mixed (Tier 1) before Upcoming (Tier 3) before Received (Tier 5)
        $this->assertLessThan($posUpcoming, $posMixed, 'Mixed invoice with overdue row must inherit Tier 1 before Tier 3');
        $this->assertLessThan($posRcv, $posUpcoming, 'Upcoming invoice (Tier 3) must rank before Received invoice (Tier 5)');
    }

    public function test_grouped_invoice_tier1_sorted_by_oldest_overdue_date_asc(): void
    {
        $olderPast = now('Asia/Jakarta')->subDays(30)->toDateString();
        $recentPast = now('Asia/Jakarta')->subDays(2)->toDateString();

        OutstandingMaterial::create([
            'supplier' => 'Supplier Older GRP',
            'number_invoice' => 'INV-GRP-OLDER',
            'invoice_identity_key' => $this->identity->invoiceKey('Supplier Older GRP', 'INV-GRP-OLDER'),
            'type' => 'Older Overdue',
            'status' => 'On Shipment',
            'estimasi_eta_warehouse' => $olderPast,
        ]);

        OutstandingMaterial::create([
            'supplier' => 'Supplier Recent GRP',
            'number_invoice' => 'INV-GRP-RECENT',
            'invoice_identity_key' => $this->identity->invoiceKey('Supplier Recent GRP', 'INV-GRP-RECENT'),
            'type' => 'Recent Overdue',
            'status' => 'On Shipment',
            'estimasi_eta_warehouse' => $recentPast,
        ]);

        $response = $this->controller->invoiceData(Request::create('/outstanding-materials/show-based-on-invoice/data', 'GET', [
            'start' => 0,
            'length' => 100,
        ]));
        $data = $response->getData(true)['data'];
        $invoices = array_map(fn ($r) => html_entity_decode(strip_tags((string) $r['number_invoice'])), $data);

        $posOlder = array_search('INV-GRP-OLDER', $invoices);
        $posRecent = array_search('INV-GRP-RECENT', $invoices);

        $this->assertLessThan($posRecent, $posOlder, 'Oldest overdue invoice must appear before newer overdue in Tier 1');
    }

    public function test_grouped_invoice_tier2_sorted_by_nearest_delay_eta_warehouse_asc(): void
    {
        $past = now('Asia/Jakarta')->subDays(5)->toDateString();
        $nearDelay = now('Asia/Jakarta')->addDays(3)->toDateString();
        $farDelay = now('Asia/Jakarta')->addDays(25)->toDateString();

        OutstandingMaterial::create([
            'supplier' => 'Supplier Delay 1',
            'number_invoice' => 'INV-GRP-DELAY-NEAR',
            'invoice_identity_key' => $this->identity->invoiceKey('Supplier Delay 1', 'INV-GRP-DELAY-NEAR'),
            'type' => 'Near Delay Row',
            'status' => 'On Shipment',
            'estimasi_eta_warehouse' => $past,
            'estimasi_delay_eta_warehouse' => $nearDelay,
        ]);

        OutstandingMaterial::create([
            'supplier' => 'Supplier Delay 2',
            'number_invoice' => 'INV-GRP-DELAY-FAR',
            'invoice_identity_key' => $this->identity->invoiceKey('Supplier Delay 2', 'INV-GRP-DELAY-FAR'),
            'type' => 'Far Delay Row',
            'status' => 'On Shipment',
            'estimasi_eta_warehouse' => $past,
            'estimasi_delay_eta_warehouse' => $farDelay,
        ]);

        $response = $this->controller->invoiceData(Request::create('/outstanding-materials/show-based-on-invoice/data', 'GET', [
            'start' => 0,
            'length' => 100,
        ]));
        $data = $response->getData(true)['data'];
        $invoices = array_map(fn ($r) => html_entity_decode(strip_tags((string) $r['number_invoice'])), $data);

        $posNear = array_search('INV-GRP-DELAY-NEAR', $invoices);
        $posFar = array_search('INV-GRP-DELAY-FAR', $invoices);

        $this->assertLessThan($posFar, $posNear, 'Nearest future delay ETA invoice must appear first in Tier 2');
    }

    public function test_grouped_invoice_tier3_sorted_by_nearest_eta_warehouse_asc(): void
    {
        $nearEta = now('Asia/Jakarta')->addDays(4)->toDateString();
        $farEta = now('Asia/Jakarta')->addDays(30)->toDateString();

        OutstandingMaterial::create([
            'supplier' => 'Supplier Upcoming 1',
            'number_invoice' => 'INV-GRP-UPC-NEAR',
            'invoice_identity_key' => $this->identity->invoiceKey('Supplier Upcoming 1', 'INV-GRP-UPC-NEAR'),
            'type' => 'Near Upcoming Row',
            'status' => 'On Production',
            'estimasi_eta_warehouse' => $nearEta,
        ]);

        OutstandingMaterial::create([
            'supplier' => 'Supplier Upcoming 2',
            'number_invoice' => 'INV-GRP-UPC-FAR',
            'invoice_identity_key' => $this->identity->invoiceKey('Supplier Upcoming 2', 'INV-GRP-UPC-FAR'),
            'type' => 'Far Upcoming Row',
            'status' => 'On Production',
            'estimasi_eta_warehouse' => $farEta,
        ]);

        $response = $this->controller->invoiceData(Request::create('/outstanding-materials/show-based-on-invoice/data', 'GET', [
            'start' => 0,
            'length' => 100,
        ]));
        $data = $response->getData(true)['data'];
        $invoices = array_map(fn ($r) => html_entity_decode(strip_tags((string) $r['number_invoice'])), $data);

        $posNear = array_search('INV-GRP-UPC-NEAR', $invoices);
        $posFar = array_search('INV-GRP-UPC-FAR', $invoices);

        $this->assertLessThan($posFar, $posNear, 'Nearest upcoming ETA invoice must appear first in Tier 3');
    }

    public function test_grouped_invoice_archived_invoices_excluded_from_active_list(): void
    {
        $key = $this->identity->invoiceKey('Supplier Arch GRP', 'INV-ARCH-GRP');
        $header = OutstandingMaterialInvoice::create([
            'supplier' => 'Supplier Arch GRP',
            'number_invoice' => 'INV-ARCH-GRP',
            'invoice_identity_key' => $key,
            'archived_at' => now(),
        ]);

        OutstandingMaterial::create([
            'supplier' => 'Supplier Arch GRP',
            'number_invoice' => 'INV-ARCH-GRP',
            'invoice_identity_key' => $key,
            'invoice_id' => $header->id,
            'type' => 'Archived Row',
            'status' => 'Received',
            'keterangan' => 'Closed',
        ]);

        $response = $this->controller->invoiceData(Request::create('/outstanding-materials/show-based-on-invoice/data', 'GET', [
            'start' => 0,
            'length' => 100,
        ]));
        $data = $response->getData(true)['data'];
        $invoices = array_map(fn ($r) => html_entity_decode(strip_tags((string) $r['number_invoice'])), $data);

        $this->assertNotContains('INV-ARCH-GRP', $invoices, 'Archived invoice must not appear in active invoice list');
    }

    public function test_grouped_invoice_cross_supplier_isolation(): void
    {
        $past = now('Asia/Jakarta')->subDays(5)->toDateString();
        $future = now('Asia/Jakarta')->addDays(10)->toDateString();

        // Same invoice number, different suppliers
        OutstandingMaterial::create([
            'supplier' => 'Supplier Alpha',
            'number_invoice' => 'INV-SHARED-NUM',
            'invoice_identity_key' => $this->identity->invoiceKey('Supplier Alpha', 'INV-SHARED-NUM'),
            'type' => 'Alpha Overdue',
            'status' => 'On Shipment',
            'estimasi_eta_warehouse' => $past, // Tier 1
        ]);

        OutstandingMaterial::create([
            'supplier' => 'Supplier Beta',
            'number_invoice' => 'INV-SHARED-NUM',
            'invoice_identity_key' => $this->identity->invoiceKey('Supplier Beta', 'INV-SHARED-NUM'),
            'type' => 'Beta Upcoming',
            'status' => 'On Production',
            'estimasi_eta_warehouse' => $future, // Tier 3
        ]);

        $response = $this->controller->invoiceData(Request::create('/outstanding-materials/show-based-on-invoice/data', 'GET', [
            'start' => 0,
            'length' => 100,
        ]));
        $data = $response->getData(true)['data'];

        $alphaMatch = null;
        $betaMatch = null;
        foreach ($data as $index => $row) {
            $inv = html_entity_decode(strip_tags((string) $row['number_invoice']));
            $sup = html_entity_decode(strip_tags((string) $row['supplier']));
            if ($inv === 'INV-SHARED-NUM' && $sup === 'Supplier Alpha') {
                $alphaMatch = $index;
            } elseif ($inv === 'INV-SHARED-NUM' && $sup === 'Supplier Beta') {
                $betaMatch = $index;
            }
        }

        $this->assertNotNull($alphaMatch, 'Supplier Alpha invoice must exist as a separate group');
        $this->assertNotNull($betaMatch, 'Supplier Beta invoice must exist as a separate group');
        $this->assertLessThan($betaMatch, $alphaMatch, 'Supplier Alpha (Tier 1) must rank before Supplier Beta (Tier 3)');
    }
}

class TestEtaPriorityAccessService extends OutstandingMaterialAccessService
{
    public function canView(?User $user): bool
    {
        return true;
    }

    public function canManage(?User $user): bool
    {
        return true;
    }

    public function canExport(?User $user): bool
    {
        return true;
    }

    public function isSales(?User $user): bool
    {
        return false;
    }
}
