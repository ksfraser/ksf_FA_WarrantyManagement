<?php
/**
 * Warranty RMA Management Page
 */

function warranty_rma_list()
{
    global $path_to_root, $Ajax;

    include_once $path_to_root . "/includes/ui/ui_lists.inc";
    include_once $path_to_root . "/includes/ui/ui_view.inc";

    $filter = $_POST['filter'] ?? 'All';

    $sql = "SELECT 
        r.id,
        r.rma_number,
        r.ticket_id,
        r.return_type,
        r.authorization_status,
        r.authorization_date,
        r.authorized_by,
        r.resolution,
        r.created_at,
        l.product_serial,
        w.sku_id
    FROM " . TB_PREF . "fa_wm_rma r
    LEFT JOIN " . TB_PREF . "fa_wm_liability l ON r.warranty_liability_id = l.id
    LEFT JOIN " . TB_PREF . "fa_wm_products w ON l.warranty_product_id = w.id";

    if ($filter !== 'All') {
        if ($filter === 'Pending') {
            $sql .= " WHERE r.authorization_status IN ('Pending', 'Approved', 'Rejected')";
        } else {
            $sql .= " WHERE r.authorization_status = " . db_escape($filter);
        }
    }
    $sql .= " ORDER BY r.id DESC";

    $result = db_query($sql, "Could not get RMAs");

    $rmas = array();
    while ($row = db_fetch($result)) {
        $rmas[] = $row;
    }

    echo '<div class="widget_wrapper">';
    echo '<h3>' . _('RMA Management') . '</h3>';

    echo '<form method="post">';
    echo '<input type="hidden" name="filter" value="All">';
    echo '<select name="filter" onchange="this.form.submit()">';
    echo '<option value="All"' . ($filter === 'All' ? ' selected' : '') . '>All</option>';
    echo '<option value="Pending"' . ($filter === 'Pending' ? ' selected' : '') . '>Pending Review</option>';
    echo '<option value="Approved"' . ($filter === 'Approved' ? ' selected' : '') . '>Approved</option>';
    echo '<option value="Rejected"' . ($filter === 'Rejected' ? ' selected' : '') . '>Rejected</option>';
    echo '<option value="Completed"' . ($filter === 'Completed' ? ' selected' : '') . '>Completed</option>';
    echo '</select>';
    echo '</form>';

    if (empty($rmas)) {
        echo '<p>' . _('No RMAs found') . '</p>';
        echo '</div>';
        return;
    }

    start_table(TABLE_STYLE);
    table_header([
        _('RMA #'),
        _('Ticket'),
        _('SKU'),
        _('Serial'),
        _('Type'),
        _('Status'),
        _('Auth Date'),
        _('Authorized By'),
        _('Resolution'),
        _('Actions'),
    ]);

    foreach ($rmas as $rma) {
        $authStatus = $rma['authorization_status'];
        $statusClass = strtolower($authStatus);
        
        echo '<tr>';
        label_cell($rma['rma_number']);
        label_cell($rma['ticket_id'] ? '<a href="/support_tickets.php?ticket_id=' . $rma['ticket_id'] . '">' . $rma['ticket_id'] . '</a>' : '');
        label_cell($rma['sku_id'] ?? '');
        label_cell($rma['product_serial'] ?? '');
        label_cell($rma['return_type']);
        
        $statusLabel = $authStatus === 'Pending' ? '<span class="wm-status-pending">' . $authStatus . '</span>' : 
                      ($authStatus === 'Approved' ? '<span class="wm-status-approved">' . $authStatus . '</span>' :
                      ($authStatus === 'Rejected' ? '<span class="wm-status-rejected">' . $authStatus . '</span>' : $authStatus));
        label_cell($statusLabel);
        
        label_cell(sql2date($rma['authorization_date']));
        label_cell($rma['authorized_by'] ?? '');
        label_cell($rma['resolution'] ?? '');
        
        $actions = '';
        if ($authStatus === 'Pending') {
            $actions .= '<button onclick="Warranty.approveRMA(' . $rma['id'] . ')">' . _('Approve') . '</button> ';
            $actions .= '<button onclick="Warranty.rejectRMA(' . $rma['id'] . ')">' . _('Reject') . '</button>';
        } elseif ($authStatus === 'Approved') {
            $actions .= '<button onclick="Warranty.completeRMA(' . $rma['id'] . ')">' . _('Complete') . '</button>';
        }
        label_cell($actions);
        echo '</tr>';
    }

    end_table();
    echo '</div>';
}

function warranty_rma_edit($rma_id = 0)
{
    global $path_to_root;

    include_once $path_to_root . "/includes/ui/ui_view.inc";

    if ($rma_id > 0) {
        $sql = "SELECT * FROM " . TB_PREF . "fa_wm_rma WHERE id = " . db_escape($rma_id);
        $result = db_query($sql, "Could not get RMA");
        $rma = db_fetch($result);
    } else {
        $rma = [
            'id' => 0,
            'rma_number' => 'RMA-' . strtoupper(uniqid()),
            'ticket_id' => 0,
            'warranty_liability_id' => 0,
            'return_type' => 'Repair',
            'authorization_status' => 'Pending',
            'resolution' => '',
        ];
    }

    start_form();
    start_table(TABLE_STYLE);

    if ($rma_id > 0) {
        hidden_rowcells('id', $rma['id']);
    }
    
    if (!isset($rma['rma_number'])) {
        $rma['rma_number'] = 'RMA-' . strtoupper(uniqid());
    }

    text_row(_("RMA Number:"), 'rma_number', $rma['rma_number'] ?? '', 30, 30);
    text_row(_("Ticket ID:"), 'ticket_id', $rma['ticket_id'] ?? '', 30, 30);
    text_row(_("Liability ID:"), 'warranty_liability_id', $rma['warranty_liability_id'] ?? '', 30, 30);
    
    textarea_row(_("Return Type:"), 'return_type', $rma['return_type'] ?? 'Repair', 30, 3);
    textarea_row(_("Resolution:"), 'resolution', $rma['resolution'] ?? '', 30, 3);
    textarea_row(_("Shipping Info:"), 'return_shipping_info', $rma['return_shipping_info'] ?? '', 30, 3);

    end_table(1);
    
    submit_cells('save', _("Save"));
    end_form();
}