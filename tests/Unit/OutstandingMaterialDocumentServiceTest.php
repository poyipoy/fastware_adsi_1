<?php

namespace Tests\Unit;

use App\Services\OutstandingMaterialDocumentService;
use PHPUnit\Framework\TestCase;

class OutstandingMaterialDocumentServiceTest extends TestCase
{
    public function test_only_known_storage_prefixes_are_resolvable(): void
    {
        $service = new OutstandingMaterialDocumentService();

        $this->assertSame(
            ['disk' => 'local', 'path' => 'private/outstanding-materials/packing-list/file.pdf'],
            $service->resolvePath('private/outstanding-materials/packing-list/file.pdf'),
        );
        $this->assertSame(
            ['disk' => 'public', 'path' => 'outstanding-materials/legacy.pdf'],
            $service->resolvePath('outstanding-materials/legacy.pdf'),
        );
        $this->assertNull($service->resolvePath('../outside.pdf'));
        $this->assertNull($service->resolvePath('private/outstanding-materials/../outside.pdf'));
        $this->assertNull($service->resolvePath('storage/app/private.pdf'));
        $this->assertNull($service->resolvePath('private\\outstanding-materials\\file.pdf'));
    }
}
