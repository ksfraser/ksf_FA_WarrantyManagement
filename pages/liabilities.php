<?php
/**
 * Warranty Liability List Page
 */

function warranty_liability_list()
{
    global $path_to_root, $Ajax;

    include_once $path_to_root . "/includes/ui/ui_lists.inc";
    include_once $path_to_root . "/includes/ui/ui_view.inc";

    $filter = $_POST['filter'] ?? 'All';

    $sql = "SELECT 
        l.id,
        l.sale_id,
        l.product_serial,
        l.activation_date,
        l.expiration_date,
        l.liability_amount,
        l.current_value,
        l.status,
        l.account_id,
        w.sku_id,
        w.provider_name
    FROM " . TB_PREF . "fa_wm_liability l
    LEFT JOIN " . TB_PREF . "fa_wm_products w ON l.warranty_product_id = w.id";

    if ($filter !== 'All') {
        $sql .= " WHERE l.status = " . db_escape($filter);
    }
    $sql .= " ORDER BY l.id DESC";

    $result = db_query($sql, "Could not get liabilities");

    $kvs = array();
    while ($row = db_fetch($result)) {
        $kvs[] = $row;
    }

    echo '<div class="widget_wrapper">';
    echo '<h3>' . _('Warranty Liabilities') . '</h3>';

    echo '<form method="post">';
    echo '<input type="hidden" name="filter" value="All">';
    echo '<select name="filter" onchange="this.form.submit()">';
    echo '<option value="All"' . ($filter === 'All' ? ' selected' : '') . '>All</option>';
    echo '<option value="Active"' . ($filter === 'Active' ? ' selected' : '') . '>Active</option>';
    echo '<option value="Expired"' . ($filter === 'Expired' ? ' selected' : '') . '>Expired</option>';
    echo '<option value="Claimed"' . ($filter === 'Claimed' ? ' selected' : '') . '>Claimed</option>';
    echo '</select>';
    echo '</form>';

    if (empty($kvs)) {
        echo '<p>' . _('No liabilities found') . '</p>';
        echo '</div>';
        return;
    }

    start_table(TABLE_STYLE);
    table_header([
        _('ID'),
        _('Sale'),
        _('Serial'),
        _('SKU'),
        _('Provider'),
        _('Activated'),
        _('Expires'),
        _('Amount'),
        _('Current Value'),
        _('Status'),
    ]);

    foreach ($kvs as $liab) {
        alttable_row_style(" onclick='Warranty.editLiability({$liab['id']})'");
        label_cell($liab['id']);
        label_cell($liab['sale_id'] ?? '');
        label_cell($liab['product_serial'] ?? '');
        label_cell($liab['sku_id'] ?? '');
        label_cell($liab['provider_name'] ?? '');
        label_cell(sql2date($liab['activation_date']));
        label_cell(sql2date($liab['expiration_date']));
        amount_cell($liab['liability_amount']);
        amount_cell($liab['current_value']);
        label_cell($liab['status']);
    }

    end_table();
    echo '</div>';
}