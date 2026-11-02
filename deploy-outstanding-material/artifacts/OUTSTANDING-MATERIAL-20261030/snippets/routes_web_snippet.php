// =========================================================================
// Outstanding Material Module Routes
// Add this block inside the authenticated middleware group in routes/web.php
// =========================================================================

    // Outstanding Materials
    Route::prefix('outstanding-materials')->name('outstanding-materials.')->middleware([
        'auth',
        'role:outstanding_material',
    ])->group(function () {
        Route::get('/', [OutstandingMaterialController::class, 'index'])->name('index');
        Route::get('/data', [OutstandingMaterialController::class, 'data'])->name('data');
        Route::get('/create', [OutstandingMaterialController::class, 'create'])->name('create');
        Route::post('/bulk', [OutstandingMaterialController::class, 'storeBatch'])->name('bulk-store');
        Route::post('/', [OutstandingMaterialController::class, 'store'])->name('store');
        Route::get('/export', [OutstandingMaterialController::class, 'export'])->name('export');
        Route::post('/import', [OutstandingMaterialController::class, 'import'])->name('import');
        Route::get('/import/preview/{token}', [OutstandingMaterialController::class, 'importPreview'])->whereUuid('token')->name('import.preview');
        Route::post('/import/preview/{token}/execute', [OutstandingMaterialController::class, 'importExecute'])->whereUuid('token')->name('import.preview.execute');
        Route::get('/template', [OutstandingMaterialController::class, 'template'])->name('template');
        Route::get('/show-based-on-invoice', [OutstandingMaterialController::class, 'invoiceIndex'])->name('invoice.index');
        Route::get('/show-based-on-invoice/data', [OutstandingMaterialController::class, 'invoiceData'])->name('invoice.data');
        Route::get('/show-based-on-invoice/materials', [OutstandingMaterialController::class, 'invoiceMaterials'])->name('invoice.materials');
        Route::post('/show-based-on-invoice/update', [OutstandingMaterialController::class, 'updateInvoiceFields'])->name('invoice.update');
        Route::post('/show-based-on-invoice/documents', [OutstandingMaterialController::class, 'uploadInvoiceDocuments'])->name('invoice.documents.upload');
        Route::delete('/show-based-on-invoice/{outstandingMaterial}', [OutstandingMaterialController::class, 'destroyInvoice'])->name('invoice.destroy');
        Route::get('/show-based-on-invoice/{outstandingMaterial}/materials', [OutstandingMaterialController::class, 'invoiceMaterialsForAnchor'])->name('invoice.materials.scoped');
        Route::post('/show-based-on-invoice/{outstandingMaterial}/update', [OutstandingMaterialController::class, 'updateInvoiceFieldsForAnchor'])->name('invoice.update.scoped');
        Route::post('/show-based-on-invoice/{outstandingMaterial}/documents', [OutstandingMaterialController::class, 'uploadInvoiceDocumentsForAnchor'])->name('invoice.documents.upload.scoped');
        Route::post('/show-based-on-invoice/{outstandingMaterial}/archive', [OutstandingMaterialController::class, 'archiveInvoice'])->name('invoice.archive');
        Route::post('/materials/archive', [OutstandingMaterialController::class, 'archiveMaterials'])->name('materials.archive');
        Route::post('/materials/restore', [OutstandingMaterialController::class, 'restoreMaterials'])->name('materials.restore');
        Route::get('/archived-invoices', [OutstandingMaterialController::class, 'archivedInvoiceIndex'])->name('invoice.archived.index');
        Route::get('/archived-invoices/data', [OutstandingMaterialController::class, 'archivedInvoiceData'])->name('invoice.archived.data');
        Route::get('/archived-invoices/{outstandingMaterial}/materials', [OutstandingMaterialController::class, 'archivedInvoiceMaterialsForAnchor'])->name('invoice.archived.materials.scoped');
        Route::post('/archived-invoices/{outstandingMaterial}/restore', [OutstandingMaterialController::class, 'restoreInvoice'])->name('invoice.restore');
        Route::get('/archived-materials', [OutstandingMaterialController::class, 'archivedMaterialIndex'])->name('materials.archived.index');
        Route::get('/archived-materials/data', [OutstandingMaterialController::class, 'archivedMaterialData'])->name('materials.archived.data');
        Route::get('/{outstandingMaterial}/invoice-data', [OutstandingMaterialController::class, 'invoiceDetailData'])->name('invoice-detail.data');
        Route::get('/{outstandingMaterial}/export', [OutstandingMaterialController::class, 'invoiceDetailExport'])->name('invoice-detail.export');
        Route::get('/{outstandingMaterial}/attachment/{type?}', [OutstandingMaterialController::class, 'attachment'])
            ->whereIn('type', ['attachment', 'packing-list', 'mtc'])
            ->name('attachment');
        Route::get('/{outstandingMaterial}', [OutstandingMaterialController::class, 'show'])->name('show');
        Route::get('/{outstandingMaterial}/edit', [OutstandingMaterialController::class, 'edit'])->name('edit');
        Route::put('/{outstandingMaterial}', [OutstandingMaterialController::class, 'update'])->name('update');
        Route::delete('/{outstandingMaterial}', [OutstandingMaterialController::class, 'destroy'])->name('destroy');
    });