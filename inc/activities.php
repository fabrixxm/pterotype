<?php
namespace activities;

function get_activity( $id ) {
    global $wpdb;
    $activity_json = $wpdb->get_var( $wpdb->prepare(
        'SELECT activity FROM activitypub_activities WHERE id = %d', $id
    ) );
    if ( is_null( $activity_json ) ) {
        return new \WP_Error(
            'not_found', __( 'Activity not found', 'activitypub' ), array( 'status' => 404 )
        );
    }
    $activity = json_decode( $activity_json, true );
    return $activity;
}

function get_activity_by_activitypub_id( $activitypub_id ) {
    global $wpdb;
    $activity_json = $wpdb->get_var( $wpdb->prepare(
        'SELECT activity FROM activitypub_activities WHERE id = %s', $activitypub_id
    ) );
    if ( is_null( $activity_json ) ) {
        return new \WP_Error(
            'not_found', __( 'Activity not found', 'activitypub' ), array( 'status' => 404 )
        );
    }
    $activity = json_decode( $activity_json, true );
    return $activity;
}

function strip_private_fields( $activity ) {
    if ( array_key_exists( 'bto', $activity ) ) {
        unset( $activity['bto'] );
    }
    if ( array_key_exists( 'bcc', $activity ) ) {
        unset( $activity['bcc'] );
    }
    return $activity;
}

function persist_activity( $activity ) {
    global $wpdb;
    if ( !array_key_exists( 'id', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Activity must have an "id" field', 'activitypub' ),
            array( 'status' => 400 )
        );
    }
    $activitypub_id = $activity['id'];
    $wpdb->insert( 'activitypub_activities', array(
            'activitypub_id' => $activitypub_id,
            'activity' => wp_json_encode( $activity )
    ) );
    return $activity;
}

function create_activities_table() {
    global $wpdb;
    $wpdb->query(
        "
        CREATE TABLE IF NOT EXISTS activitypub_activities (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            activitypub_id TEXT UNIQUE NOT NULL,
            activity TEXT NOT NULL
        );
        "
    );
    $wpdb->query(
        "
        CREATE UNIQUE INDEX ACTIVITYPUB_ID_INDEX
        ON activitypub_activities (activitypub_id);
        "
    );
}
?>
