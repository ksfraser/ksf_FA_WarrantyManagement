<?php
/**
 * Cross-Module Event Wiring - Warranty Side
 * Connects WarrantyManagement and SupportTickets via PSR-14 events
 */

if (!class_exists('Ksfraser\Event\EventManager')) {
    return;
}

use Ksfraser\Event\EventManager;

EventManager::listen('warranty.created', function($event) {
    $data = $event->getData();
    
    if (!empty($data['warranty_id'])) {
        $warrantyId = $data['warranty_id'];
        error_log("Event wiring: Warranty created {$warrantyId}");
        
        EventManager::dispatchEvent('ticket.can_create', [
            'warranty_id' => $warrantyId,
            'reason' => 'Warranty purchase',
        ]);
    }
    
    return $event;
});

EventManager::listen('warranty.expiration_check', function($event) {
    $data = $event->getData();
    
    if (!empty($data['warranty_id'])) {
        $warrantyId = $data['warranty_id'];
        
        global $db;
        $sql = "SELECT * FROM " . TB_PREF . "fa_wm_liability WHERE id = " . db_escape($warrantyId);
        $result = db_query($sql, "Could not get warranty");
        $liability = db_fetch_assoc($result);
        
        if ($liability && strtotime($liability['expiration_date']) < time()) {
            EventManager::dispatchEvent('warranty.expired', [
                'warranty_id' => $warrantyId,
                'expiration_date' => $liability['expiration_date'],
            ]);
        }
    }
    
    return $event;
});

EventManager::listen('rma.approved', function($event) {
    $data = $event->getData();
    
    if (!empty($data['rma_id'])) {
        $rmaId = $data['rma_id'];
        
        global $db;
        $sql = "SELECT ticket_id FROM " . TB_PREF . "fa_wm_rma WHERE id = " . db_escape($rmaId);
        $result = db_query($sql, "Could not get RMA");
        $rma = db_fetch_assoc($result);
        
        if ($rma && !empty($rma['ticket_id'])) {
            EventManager::dispatchEvent('ticket.update', [
                'ticket_id' => $rma['ticket_id'],
                'status' => 'InProgress',
                'message' => 'RMA approved',
            ]);
        }
    }
    
    return $event;
});

EventManager::listen('rma.completed', function($event) {
    $data = $event->getData();
    
    if (!empty($data['rma_id'])) {
        $rmaId = $data['rma_id'];
        
        global $db;
        $sql = "SELECT ticket_id, warranty_liability_id FROM " . TB_PREF . "fa_wm_rma WHERE id = " . db_escape($rmaId);
        $result = db_query($sql, "Could not get RMA");
        $rma = db_fetch_assoc($result);
        
        if ($rma && !empty($rma['ticket_id'])) {
            EventManager::dispatchEvent('ticket.resolved', [
                'ticket_id' => $rma['ticket_id'],
                'resolution' => 'RMA completed',
            ]);
        }
        
        if ($rma && !empty($rma['warranty_liability_id'])) {
            $sql = "UPDATE " . TB_PREF . "fa_wm_liability SET 
                status = 'Claimed',
                current_value = 0
                WHERE id = " . db_escape($rma['warranty_liability_id']);
            db_query($sql, "Could not update warranty status");
        }
    }
    
    return $event;
});

return true;