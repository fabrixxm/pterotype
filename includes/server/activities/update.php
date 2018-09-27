<?php
namespace activities\update;

require_once plugin_dir_path( __FILE__ ) . '../objects.php';

function handle_outbox( $actor_slug, $activity ) {
    if ( !(array_key_exists( 'type', $activity ) && $activity['type'] === 'Update') ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expecting an Update activity', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    if ( !array_key_exists( 'object', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expecting an object', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $update_object = $activity['object'];
    if ( !array_key_exists( 'id', $update_object ) ) {
        return new \WP_Error(
            'invalid_object',
            __( 'Object must have an "id" parameter', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $existing_object = \objects\get_object_by_actvitypub_id( $update_object['id'] );
    if ( is_wp_error( $existing_object ) ) {
        return $existing_object;
    }
    $updated_object = array_merge( $existing_object, $update_object );
    $updated_object = \objects\update_object( $updated_object );
    if ( is_wp_error( $updated_object ) ) {
        return $updated_object;
    }
    return $activity;
}

function handle_inbox( $actor_slug, $activity ) {
    if ( !(array_key_exists( 'type', $activity ) && $activity['type'] === 'Update') ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expecting an Update activity', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    if ( !array_key_exists( 'id', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Activities must have an "id" field', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    if ( !array_key_exists( 'object', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expecting an object', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $object = $activity['object'];
    if ( !array_key_exists( 'id', $object ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Objects must have an "id" field', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $authorized = check_authorization( $activity );
    if ( is_wp_error( $authorized ) ) {
        return $authorized;
    }
    $object = \objects\upsert_object( $object );
    if ( is_wp_error( $object ) ) {
        return $object;
    }
    return $activity;
}

function check_authorization( $activity ) {
    $object = $activity['object'];
    $activity_origin = parse_url( $activity['id'] )['host'];
    $object_origin = parse_url( $object['id'] )['host'];
    if ( ( !$activity_origin || !$object_origin ) || $activity_origin !== $object_origin ) {
        return new \WP_Error(
            'unauthorized',
            __( 'Unauthorized Update activity', 'pterotype' ),
            array( 'status' => 403 )
        );
    }
    return true;
}
?>
