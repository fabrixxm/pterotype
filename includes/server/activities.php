<?php
namespace activities;

require_once plugin_dir_path( __FILE__ ) . '../util.php';

function get_activity( $id ) {
    global $wpdb;
    $activity_json = $wpdb->get_var( $wpdb->prepare(
        'SELECT activity FROM pterotype_activities WHERE id = %d', $id
    ) );
    if ( is_null( $activity_json ) ) {
        return new \WP_Error(
            'not_found', __( 'Activity not found', 'pterotype' ), array( 'status' => 404 )
        );
    }
    $activity = json_decode( $activity_json, true );
    return $activity;
}

function get_activity_by_activitypub_id( $activitypub_id ) {
    global $wpdb;
    $activity_json = $wpdb->get_var( $wpdb->prepare(
        'SELECT activity FROM pterotype_activities WHERE id = %s', $activitypub_id
    ) );
    if ( is_null( $activity_json ) ) {
        return new \WP_Error(
            'not_found', __( 'Activity not found', 'pterotype' ), array( 'status' => 404 )
        );
    }
    $activity = json_decode( $activity_json, true );
    return $activity;
}

function get_activity_id( $activitypub_id ) {
    global $wpdb;
    return $wpdb->get_var( $wpdb->prepare(
        'SELECT id FROM pterotype_activities WHERE activitypub_id = %s', $activitypub_id
    ) );
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
    $activity = \util\dereference_object( $activity );
    if ( !array_key_exists( 'id', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Activity must have an "id" field', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    if ( !array_key_exists( 'type', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Activity must have a "type" field', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $activitypub_id = $activity['id'];
    $type = $activity['type'];
    $row = $wpdb->get_row( $wpdb->prepare(
        'SELECT * FROM pterotype_activities WHERE activitypub_id = %s', $activitypub_id
    ) );
    $res = true;
    if ( $row === null ) {
        $res = $wpdb->insert(
            'pterotype_activities',
            array(
                'activitypub_id' => $activitypub_id,
                'type' => $type,
                'activity' => wp_json_encode( $activity )
            ),
            '%s'
        );
    } else {
        $res = $wpdb->update(
            'pterotype_activities',
            array(
                'activitypub_id' => $activitypub_id,
                'type' => $type,
                'activity' => wp_json_encode( $activity )
            ),
            array( 'id' => $row->id ),
            '%s',
            '%d'
        );
    }
    return $activity;
}

function create_local_activity( $activity ) {
    global $wpdb;
    $activity = \util\dereference_object( $activity );
    if ( !array_key_exists( 'type', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Activity must have a "type" field', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $type = $activity['type'];
    $res = $wpdb->insert( 'pterotype_activities', array(
        'activity' => wp_json_encode( $activity )
    ) );
    if ( !$res ) {
        return new \WP_Error(
            'db_error', __( 'Failed to insert activity row', 'pterotype' )
        );
    }
    $activity_id = $wpdb->insert_id;
    $activity_url = get_rest_url( null, sprintf( '/pterotype/v1/activity/%d', $activity_id ) );
    $activity['id'] = $activity_url;
    $res = $wpdb->update(
        'pterotype_activities',
        array(
            'activitypub_id' => $activity_url,
            'type' => $type,
            'activity' => wp_json_encode( $activity ),
        ),
        array( 'id' => $activity_id ),
        '%s',
        '%d'
    );
    if ( !$res ) {
        return new \WP_Error(
            'db_error', __( 'Failed to hydrate activity id', 'pterotype' )
        );
    }
    return $activity;
}
?>
