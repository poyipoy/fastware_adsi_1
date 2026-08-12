<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\OutstandingMaterialAccessService;
use PHPUnit\Framework\TestCase;

class OutstandingMaterialAccessServiceTest extends TestCase
{
    public function test_legacy_viewer_keeps_view_access(): void
    {
        $user = new User(['name' => 'Jessica Paune']);

        $this->assertTrue((new StubOutstandingMaterialAccessService())->canView($user));
    }

    public function test_active_sales_user_can_view_and_download_but_not_manage(): void
    {
        $user = new User(['name' => 'Active Sales User']);
        $user->id = 501;

        $service = new StubOutstandingMaterialAccessService([501]);

        $this->assertTrue($service->isSales($user));
        $this->assertTrue($service->canView($user));
        $this->assertTrue($service->canExport($user));
        $this->assertTrue($service->canDownloadInvoiceDocuments($user));
        $this->assertFalse($service->canManage($user));
    }

    public function test_non_sales_non_viewer_is_denied(): void
    {
        $user = new User(['name' => 'Regular User']);
        $user->id = 502;

        $service = new StubOutstandingMaterialAccessService();

        $this->assertFalse($service->canView($user));
        $this->assertFalse($service->canExport($user));
        $this->assertFalse($service->canDownloadInvoiceDocuments($user));
    }

    public function test_legacy_manager_rule_is_preserved_without_document_escalation(): void
    {
        $manager = new User(['name' => 'ADMINISTRATOR']);
        $admin = new User(['name' => 'Some Administrator', 'role_id' => 1]);
        $ilyas = new User(['name' => 'ILYAS NOOR FIRDAUS']);

        $service = new StubOutstandingMaterialAccessService();

        $this->assertTrue($service->canManage($manager));
        $this->assertTrue($service->canManage($admin));
        $this->assertTrue($service->canManage($ilyas));
        $this->assertFalse($service->canUploadInvoiceDocuments($manager));
        $this->assertFalse($service->canUploadInvoiceDocuments($admin));
        $this->assertTrue($service->canUploadInvoiceDocuments($ilyas));
        $this->assertFalse($service->canDownloadInvoiceDocuments($manager));
    }
}

class StubOutstandingMaterialAccessService extends OutstandingMaterialAccessService
{
    /** @param list<int> $salesUserIds */
    public function __construct(private readonly array $salesUserIds = [])
    {
    }

    public function isSales(?User $user): bool
    {
        return $user !== null && in_array((int) $user->getKey(), $this->salesUserIds, true);
    }
}
