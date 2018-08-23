<?php
/*
When an Activity is received (i.e. POSTed) to an Actor's outbox, the server must:

  0. Make sure the request is authenticated
  1. Add the Activity to the Actor's outbox collection in the DB
  2. Deliver the Activity to the appropriate inboxes based on the received Activity
       This involves discovering all the inboxes, including nested ones if the target
       is a collection, deduplicating inboxes, and the POSTing the Activity to each
       target inbox.
  3. Perform side effects as necessary
*/
namespace outbox;

function create_outbox_table() {
    global $wpdb;
    $wpdb->Query(
        "
        CREATE TABLE IF NOT EXISTS activitypub_outbox (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            actor VARCHAR(128) NOT NULL,
            activity TEXT NOT NULL
        );
        "
    );
}
?>
