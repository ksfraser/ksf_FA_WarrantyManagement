<?php
/**
 * FA_WarrantyManagement Module Hooks for FrontAccounting
 */

$module_name = 'FA_WarrantyManagement';
$module_version = '1.0.0';
$module_description = 'Warranty Management - SKU definitions, liability, RMA, claims';
$module_author = 'KSFII Development Team';
$module_category = 'CRM';

function fa_wm_install(): bool
{
    global $db;

    @include_once __DIR__ . '/vendor-src/Ksfraser/Common/ComposerDependencyManager.php';
    if (class_exists('Ksfraser\Common\ComposerDependencyManager')) {
        $composerMgr = new \Ksfraser\Common\ComposerDependencyManager(__DIR__);
        $composerMgr->ensureDependencies();
        @include_once $composerMgr->getAutoloadPath();
    }

    if (!fa_wm_create_tables()) return false;
    if (!fa_wm_insert_initial_data()) return false;
    return true;
}

function fa_wm_activate(): bool
{
    @include_once __DIR__ . '/vendor-src/Ksfraser/Common/ComposerDependencyManager.php';
    if (class_exists('Ksfraser\Common\ComposerDependencyManager')) {
        $composerMgr = new \Ksfraser\Common\ComposerDependencyManager(__DIR__);
        $composerMgr->ensureDependencies();
        @include_once $composerMgr->getAutoloadPath();
    }

    add_hook('warranty_created', 'fa_wm_on_warranty_created');
    add_hook('ticket_created', 'fa_wm_on_ticket_created');
    return true;
}

function fa_wm_deactivate(): bool { return true; }
function fa_wm_uninstall(): bool { return true; }

function fa_wm_create_tables(): bool
{
    global $db;

    $tables = [
        "CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_wm_products` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `sku_id` VARCHAR(30) NOT NULL,
            `provider_type` VARCHAR(20) DEFAULT 'Manufacturer',
            `provider_name` VARCHAR(100) DEFAULT NULL,
            `term_type` VARCHAR(20) DEFAULT 'Fixed',
            `term_months` INT(11) DEFAULT 12,
            `coverage_details` TEXT,
            `cost_to_provide` DECIMAL(15,2) DEFAULT 0,
            `max_claims` INT(11) DEFAULT 1,
            `max_value_per_claim` DECIMAL(15,2) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_sku_id` (`sku_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_wm_liability` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `sale_id` INT(11) DEFAULT NULL,
            `sale_item_id` INT(11) DEFAULT NULL,
            `product_serial` VARCHAR(50) DEFAULT NULL,
            `warranty_product_id` INT(11) DEFAULT NULL,
            `activation_date` DATE DEFAULT NULL,
            `expiration_date` DATE DEFAULT NULL,
            `liability_amount` DECIMAL(15,2) DEFAULT 0,
            `current_value` DECIMAL(15,2) DEFAULT 0,
            `status` VARCHAR(20) DEFAULT 'Active',
            `account_id` VARCHAR(20) DEFAULT NULL,
            `contact_id` INT(11) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_sale_id` (`sale_id`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_wm_rma` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `rma_number` VARCHAR(30) NOT NULL,
            `ticket_id` INT(11) DEFAULT NULL,
            `warranty_liability_id` INT(11) DEFAULT NULL,
            `return_type` VARCHAR(20) DEFAULT 'Repair',
            `authorization_status` VARCHAR(20) DEFAULT 'Pending',
            `authorization_date` DATETIME DEFAULT NULL,
            `authorized_by` VARCHAR(100) DEFAULT NULL,
            `resolution` TEXT,
            `credit_note_id` INT(11) DEFAULT NULL,
            `return_shipping_info` VARCHAR(255) DEFAULT NULL,
            `debit_gl` VARCHAR(20) DEFAULT NULL,
            `account_id` VARCHAR(20) DEFAULT NULL,
            `contact_id` INT(11) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_rma_number` (`rma_number`),
            KEY `idx_ticket_id` (`ticket_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];

    foreach ($tables as $sql) {
        if (!db_query($sql, "Could not create table")) return false;
    }
    return true;
}

function fa_wm_insert_initial_data(): bool
{
    $providers = ['Manufacturer', 'Wholesaler', 'Retailer'];
    foreach ($providers as $provider) {
        db_query("INSERT IGNORE INTO " . TB_PREF . "fa_wm_products 
            (sku_id, provider_type, term_type, term_months) 
            VALUES ('DEFAULT-" . strtolower($provider) . "', '" . $provider . "', 'Fixed', 12)");
    }
    return true;
}

function fa_wm_on_warranty_created($warrantyId) { error_log("Warranty created: $warrantyId"); }
function fa_wm_on_ticket_created($ticketId) { error_log("Ticket created: $ticketId"); }