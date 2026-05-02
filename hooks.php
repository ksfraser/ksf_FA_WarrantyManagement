<?php
/**
 * FA_WarrantyManagement Module Hooks for FrontAccounting
 */

define('SS_WARRANTY', 142 << 8);

class hooks_fa_warrantymanagement extends hooks {
    var $module_name = 'fa_warrantymanagement';

    function install_options($app) {
        global $path_to_root;

        switch($app->id) {
            case 'CRM':
                $app->add_lapp_function(0, _("Warranty Products"),
                    $path_to_root."/modules/".$this->module_name."/products.php", 'SA_WARRANTYVIEW', MENU_ENTRY);
                $app->add_lapp_function(1, _("Liabilities"),
                    $path_to_root."/modules/".$this->module_name."/liability.php", 'SA_WARRANTYMANAGE', MENU_ENTRY);
                $app->add_lapp_function(2, _("RMA"),
                    $path_to_root."/modules/".$this->module_name."/rma.php", 'SA_WARRANTYMANAGE', MENU_ENTRY);
                break;
        }
    }

    function install_access() {
        $security_sections[SS_WARRANTY] = _("Warranty Management");
        $security_areas['SA_WARRANTYVIEW'] = array(SS_WARRANTY | 1, _("View Warranty"));
        $security_areas['SA_WARRANTYMANAGE'] = array(SS_WARRANTY | 2, _("Manage Warranty"));
        return array($security_areas, $security_sections);
    }

    function activate_extension($company, $check_only=true) {
        $updates = array('sql/update.sql' => array($this->module_name));
        $ok = $this->update_databases($company, $updates, $check_only);
        if ($check_only || !$ok) {
            return $ok;
        }
        $this->ensure_warranty_schema();
        return $ok;
    }

    private function table_exists($table) {
        $sql = "SHOW TABLES LIKE " . db_escape($table);
        $res = db_query($sql, 'Failed checking table existence');
        return db_num_rows($res) > 0;
    }

    private function ensure_warranty_schema() {
        $tables = array(
            TB_PREF . "fa_wm_products" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_wm_products` (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            TB_PREF . "fa_wm_liability" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_wm_liability` (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            TB_PREF . "fa_wm_rma" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_wm_rma` (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        foreach ($tables as $table_name => $sql) {
            db_query($sql, "Could not create Warranty Management table: $table_name");
        }

        $this->insert_initial_data();
    }

    private function insert_initial_data() {
        $providers = array('Manufacturer', 'Wholesaler', 'Retailer');
        foreach ($providers as $provider) {
            db_query("INSERT IGNORE INTO " . TB_PREF . "fa_wm_products 
                (sku_id, provider_type, term_type, term_months) 
                VALUES ('DEFAULT-" . strtolower($provider) . "', '$provider', 'Fixed', 12)");
        }
    }

    function db_prevoid($trans_type, $trans_no) {
        // Handle voiding if needed
    }
}
?>
