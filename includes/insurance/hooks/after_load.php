<?php

require_once __DIR__ . '/../ins_repository.php';

/**
 * Load AssureWallos insurance extension data for the subscription edit form.
 */
function assure_load_subscription_insurance(SQLite3 $db, int $subscriptionId, int $userId): array
{
    return Ins_Repository::insLoadForSubscription($db, $subscriptionId, $userId);
}

?>
