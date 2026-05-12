<?php

require_once __DIR__ . '/../ins_repository.php';

/**
 * Persist AssureWallos insurance fields after Wallos saved the subscription.
 */
function assure_after_subscription_save(SQLite3 $db, int $subscriptionId, int $userId, array $post, array $files): void
{
    Ins_Repository::insSaveFromPost($db, $subscriptionId, $userId, $post, $files);
}

?>
