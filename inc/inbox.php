<?php
/*
When an Activity is received (i.e. POSTed) to an Actor's inbox, the server must:

  1. Forward the Activity as necessary
       See (https://www.w3.org/TR/activitypub/#x7-1-2-forwarding-from-inbox).
  2. Perform the side effects of receiving the Activity
  3. Persist the activity in the actor's inbox (and the attached object, if necessary)
*/
namespace inbox;

require_once plugin_dir_path( __FILE__ ) . '/activities.php';
require_once plugin_dir_path( __FILE__ ) . '/activities/create.php';
require_once plugin_dir_path( __FILE__ ) . '/activities/update.php';
require_once plugin_dir_path( __FILE__ ) . '/activities/delete.php';
require_once plugin_dir_path( __FILE__ ) . '/activities/follow.php';
require_once plugin_dir_path( __FILE__ ) . '/activities/accept.php';
require_once plugin_dir_path( __FILE__ ) . '/activities/reject.php';

function handle_activity( $actor_slug, $activity ) {
    if ( !array_key_exists( 'type', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Activity must have a type', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    forward_activity( $activity );
    switch ( $activity['type'] ) {
    case 'Create':
        $activity = \activities\create\handle_inbox( $actor_slug, $activity );
        break;
    case 'Update':
        $activity = \activities\update\handle_inbox( $actor_slug, $activity );
        break;
    case 'Delete':
        $activity = \activities\delete\handle_inbox( $actor_slug, $activity );
        break;
    case 'Follow':
        $activity = \activities\follow\handle_inbox( $actor_slug, $activity );
        break;
    case 'Accept':
        $activity = \activities\accept\handle_inbox( $actor_slug, $activity );
        break;
    case 'Reject':
        $activity = \activities\reject\handle_inbox( $actor_slug, $activity );
        break;
    case 'Add':
        return new \WP_Error(
            'not_implemented',
            __( 'The Add activity has not been implemented', 'pterotype' ),
            array( 'status' => 501 )
        );
        break;
    case 'Remove':
        return new \WP_Error(
            'not_implemented',
            __( 'The Remove activity has not been implemented', 'pterotype' ),
            array( 'status' => 501 )
        );
        break;
    case 'Announce':
        break;
    case 'Undo':
        // TODO
        break;
    }
    if ( is_wp_error( $activity ) ) {
        return $activity;
    }
    return persist_activity( $actor_slug, $activity );
}

function forward_activity( $activity ) {
    // TODO
}

function persist_activity( $actory_slug, $activity ) {
    global $wpdb;
    $activity = \activities\persist_activity( $activity );
    if ( is_wp_error( $activity ) ) {
        return $activity;
    }
    $activity_id = $wpdb->insert_id;
    $actor_id = \actors\get_actor_id( $actor_slug );
    $wpdb->insert( 'pterotype_inbox', array(
        'actor_id' => $actor_id,
        'activity_id' => $activity_id,
    ) );
    $response = new \WP_Rest_Response();
    return $response;
}
?>
