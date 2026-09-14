<?php
$page_security = 'WM_VIEW';
$path_to_root = "../../..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/modules/FA_WarrantyManagement/includes/wm_db.inc");

page(_($help_context = "Warranty Products"));

simple_page_mode(true);

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM')
{
    if (strlen($_POST['sku_id']) == 0) {
        display_error(_("SKU ID cannot be empty."));
    } else {
        $data = [
            'sku_id' => $_POST['sku_id'],
            'provider_type' => $_POST['provider_type'],
            'provider_name' => $_POST['provider_name'],
            'term_type' => $_POST['term_type'],
            'term_months' => $_POST['term_months'],
            'cost_to_provide' => $_POST['cost_to_provide'],
            'max_claims' => $_POST['max_claims'],
            'max_value_per_claim' => $_POST['max_value_per_claim'],
        ];
        
        if ($selected_id != -1) {
            update_warranty_product($selected_id, $data);
            display_notification(_('Product updated'));
        } else {
            add_warranty_product($data);
            display_notification(_('Product added'));
        }
        $Mode = 'RESET';
    }
}

if ($Mode == 'Delete') {
    delete_warranty_product($selected_id);
    display_notification(_('Product deleted'));
    $Mode = 'RESET';
}

if ($Mode == 'EDIT_ITEM') {
    $myrow = get_warranty_product($selected_id);
    if ($myrow) $_POST = $myrow;
}

if ($Mode == 'RESET') {
    $_POST = ['sku_id' => '', 'provider_type' => 'Manufacturer', 'term_type' => 'Fixed', 
              'term_months' => 12, 'cost_to_provide' => 0, 'max_claims' => 1, 'max_value_per_claim' => 0];
}

$provider_types = ['Manufacturer' => 'Manufacturer', 'Wholesaler' => 'Wholesaler', 'Retailer' => 'Retailer'];
$term_types = ['Fixed' => 'Fixed', 'Amortized' => 'Amortized'];

start_form();
start_table(TABLESTYLE, "width=60%");

table_section_title($Mode == 'EDIT_ITEM' ? _("Edit Product") : _("New Warranty Product"));

text_row_ex(_("SKU ID:"), 'sku_id', 20);
select_row(_("Provider Type:"), 'provider_type', $_POST['provider_type'], $provider_types);
text_row_ex(_("Provider Name:"), 'provider_name', 30);
select_row(_("Term Type:"), 'term_type', $_POST['term_type'], $term_types);
smallint_row(_("Term (Months):"), 'term_months');
amount_row(_("Cost to Provide:"), 'cost_to_provide');
smallint_row(_("Max Claims:"), 'max_claims');
amount_row(_("Max Value Per Claim:"), 'max_value_per_claim');

end_table();
submit_center($Mode == 'EDIT_ITEM' ? _("Update") : _("Add Product"), true, '', true);

$sql = "SELECT * FROM " . TB_PREF . "fa_wm_products ORDER BY sku_id";
$result = db_query($sql, "Could not get products");

start_table(TABLESTYLE, "width=60%");
table_header(["ID", "SKU", "Provider", "Term", "Months", "Cost", "Actions"]);

while ($row = db_fetch_assoc($result)) {
    href_js_edit_link("?selected_id=" . $row['id'] . "&Mode=EDIT_ITEM", 'edit', _("Edit"));
    delete_button_center("?selected_id=" . $row['id'] . "&Mode=Delete", _("Delete"));
    end_row();
}

end_table();
end_form();
end_page();