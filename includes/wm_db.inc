<?php
/**
 * Warranty Management Database Functions
 */

@include_once __DIR__ . '/../../vendor/ksfraser/event/src/Ksfraser/Event/EventManager.php';
$use_events = class_exists('Ksfraser\Event\EventManager');

function wm_dispatch_event(string $eventName, $eventData = null): void
{
    global $use_events;
    if ($use_events && class_exists('Ksfraser\Event\EventManager')) {
        try { \Ksfraser\Event\EventManager::dispatchEvent($eventName, $eventData); }
        catch (Exception $e) { error_log('WM Event Error: ' . $e->getMessage()); }
    }
    if (function_exists('add_hook')) {
        add_hook($eventName, function($data) use ($eventData) { return $eventData; });
    }
}

function add_warranty_product($data)
{
    $sql = "INSERT INTO " . TB_PREF . "fa_wm_products
        (sku_id, provider_type, provider_name, term_type, term_months, 
         coverage_details, cost_to_provide, max_claims, max_value_per_claim)
    VALUES (" . db_escape($data['sku_id']) . ",
            " . db_escape($data['provider_type']) . ",
            " . db_escape($data['provider_name']) . ",
            " . db_escape($data['term_type']) . ",
            " . (int)$data['term_months'] . ",
            " . db_escape($data['coverage_details'] ?? '') . ",
            " . db_escape($data['cost_to_provide']) . ",
            " . (int)$data['max_claims'] . ",
            " . db_escape($data['max_value_per_claim']) . ")";
    db_query($sql, "Could not add warranty product");
    $id = db_insert_id();
    wm_dispatch_event('warranty.product.created', ['id' => $id, 'data' => $data]);
    return $id;
}

function get_warranty_product($id)
{
    $sql = "SELECT * FROM " . TB_PREF . "fa_wm_products WHERE id = " . db_escape($id);
    $result = db_query($sql, "Could not get product");
    return db_fetch_assoc($result);
}

function update_warranty_product($id, $data)
{
    $sql = "UPDATE " . TB_PREF . "fa_wm_products SET
        sku_id = " . db_escape($data['sku_id']) . ",
        provider_type = " . db_escape($data['provider_type']) . ",
        provider_name = " . db_escape($data['provider_name']) . ",
        term_type = " . db_escape($data['term_type']) . ",
        term_months = " . (int)$data['term_months'] . ",
        cost_to_provide = " . db_escape($data['cost_to_provide']) . ",
        max_claims = " . (int)$data['max_claims'] . ",
        max_value_per_claim = " . db_escape($data['max_value_per_claim']) . "
    WHERE id = " . db_escape($id);
    db_query($sql, "Could not update product");
    wm_dispatch_event('warranty.product.updated', ['id' => $id, 'data' => $data]);
}

function delete_warranty_product($id)
{
    $old = get_warranty_product($id);
    $sql = "DELETE FROM " . TB_PREF . "fa_wm_products WHERE id = " . db_escape($id);
    db_query($sql, "Could not delete product");
    wm_dispatch_event('warranty.product.deleted', ['id' => $id, 'old_data' => $old]);
}

function add_warranty_liability($data)
{
    $product = get_warranty_product($data['warranty_product_id']);
    $term = $product['term_months'] ?? 12;
    
    $sql = "INSERT INTO " . TB_PREF . "fa_wm_liability
        (sale_id, sale_item_id, product_serial, warranty_product_id,
         activation_date, expiration_date, liability_amount, current_value, 
         status, account_id, contact_id)
    VALUES (" . db_escape($data['sale_id']) . ",
            " . db_escape($data['sale_item_id']) . ",
            " . db_escape($data['product_serial']) . ",
            " . db_escape($data['warranty_product_id']) . ",
            CURDATE(),
            DATE_ADD(CURDATE(), INTERVAL " . (int)$term . " MONTH),
            " . db_escape($data['cost_to_provide'] ?? $product['cost_to_provide']) . ",
            " . db_escape($data['cost_to_provide'] ?? $product['cost_to_provide']) . ",
            'Active',
            " . db_escape($data['account_id']) . ",
            " . db_escape($data['contact_id']) . ")";
    db_query($sql, "Could not add liability");
    $id = db_insert_id();
    wm_dispatch_event('warranty.created', ['id' => $id, 'data' => $data]);
    return $id;
}

function get_warranty_liability($id)
{
    $sql = "SELECT l.*, p.sku_id, p.provider_type, p.term_type
        FROM " . TB_PREF . "fa_wm_liability l
        LEFT JOIN " . TB_PREF . "fa_wm_products p ON l.warranty_product_id = p.id
        WHERE l.id = " . db_escape($id);
    $result = db_query($sql, "Could not get liability");
    return db_fetch_assoc($result);
}

function add_rma($data)
{
    $rma_number = 'RMA-' . date('Ymd') . '-' . str_pad(db_num_rows(db_query("SELECT id FROM " . TB_PREF . "fa_wm_rma")) + 1, 4, '0', STR_PAD_LEFT);
    
    $sql = "INSERT INTO " . TB_PREF . "fa_wm_rma
        (rma_number, ticket_id, warranty_liability_id, return_type, account_id, contact_id)
    VALUES (" . db_escape($rma_number) . ",
            " . db_escape($data['ticket_id']) . ",
            " . db_escape($data['warranty_liability_id']) . ",
            " . db_escape($data['return_type'] ?? 'Repair') . ",
            " . db_escape($data['account_id']) . ",
            " . db_escape($data['contact_id']) . ")";
    db_query($sql, "Could not add RMA");
    $id = db_insert_id();
    wm_dispatch_event('rma.created', ['id' => $id, 'data' => $data]);
    return $id;
}

function get_rma($id)
{
    $sql = "SELECT r.*, l.product_serial, rma.sku_id
        FROM " . TB_PREF . "fa_wm_rma r
        LEFT JOIN " . TB_PREF . "fa_wm_liability l ON r.warranty_liability_id = l.id
        WHERE r.id = " . db_escape($id);
    $result = db_query($sql, "Could not get RMA");
    return db_fetch_assoc($result);
}

function approve_rma($id, $authorized_by)
{
    $sql = "UPDATE " . TB_PREF . "fa_wm_rma SET
        authorization_status = 'Approved',
        authorization_date = NOW(),
        authorized_by = " . db_escape($authorized_by) . "
    WHERE id = " . db_escape($id);
    db_query($sql, "Could not approve RMA");
    wm_dispatch_event('rma.approved', ['id' => $id, 'authorized_by' => $authorized_by]);
    wm_dispatch_event('warranty.claim_approved', ['id' => $id]);
}

function reject_rma($id, $authorized_by, $reason = null)
{
    $sql = "UPDATE " . TB_PREF . "fa_wm_rma SET
        authorization_status = 'Rejected',
        authorization_date = NOW(),
        authorized_by = " . db_escape($authorized_by) . ",
        resolution = " . db_escape($reason) . "
    WHERE id = " . db_escape($id);
    db_query($sql, "Could not reject RMA");
    wm_dispatch_event('rma.rejected', ['id' => $id, 'authorized_by' => $authorized_by, 'reason' => $reason]);
}