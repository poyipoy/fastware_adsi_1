<?php

// Load after vendor/autoload.php, before Laravel boots its providers.
// Safe whether or not Composer already includes the consolidated files.
require_once dirname(__DIR__).'/app/Enums/Warehouse/WarehouseEnums.php';
require_once dirname(__DIR__).'/app/Data/Warehouse/WarehouseStockData.php';
require_once dirname(__DIR__).'/app/Http/Requests/Warehouse/TransactionRequests.php';
require_once dirname(__DIR__).'/app/Http/Requests/Warehouse/ScanRequests.php';
require_once dirname(__DIR__).'/app/Http/Requests/Warehouse/ConsumableRequests.php';
require_once dirname(__DIR__).'/app/Services/Warehouse/WarehouseStockService.php';
