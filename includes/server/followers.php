<?php
namespace followers;

require_once plugin_dir_path( __FILE__ ) . 'actors.php';
require_once plugin_dir_path( __FILE__ ) . 'objects.php';
require_once plugin_dir_path( __FILE__ ) . '../util.php';

function add_follower( $actor_slug, $follower ) {
    global $wpdb;
    $actor_id = \actors\get_actor_id( $actor_slug ); 
    if ( !$actor_id ) {
        return new \WP_Error(
            'not_found',
            __( 'Actor not found', 'pterotype' ),
            array( 'status' => 404 )
        );
    }
    $follower = \util\dereference_object( $follower );
    if ( !array_key_exists( 'id', $follower ) ) {
        return new \WP_Error(
            'invalid_object',
            __( 'Object must have an "id" field', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $object_id = \objects\get_object_id( $follower['id'] );
    if ( !$object_id ) {
        $row = \objects\upsert_object( $follower );
        $object_id = $row->id;
    }
    $wpdb->insert(
        $wpdb->prefix . 'pterotype_followers',
        array(
            'actor_id' => $actor_id,
            'object_id' => $object_id,
        )
    );
}

function remove_follower( $actor_slug, $follower ) {
    global $wpdb;
    $actor_id = \actors\get_actor_id( $actor_slug ); 
    if ( !$actor_id ) {
        return new \WP_Error(
            'not_found',
            __( 'Actor not found', 'pterotype' ),
            array( 'status' => 404 )
        );
    }
    $follower = \util\dereference_object( $follower );
    if ( !array_key_exists( 'id', $follower ) ) {
        return new \WP_Error(
            'invalid_object',
            __( 'Object must have an "id" field', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $object_id = \objects\get_object_id( $follower['id'] );
    if ( !$object_id ) {
        return new \WP_Error(
            'not_found',
            __( 'Object not found', 'pterotype' ),
            array( 'status' => 404 )
        );
    }
    $wpdb->delete(
        $wpdb->prefix . 'pterotype_followers',
        array(
            'actor_id' => $actor_id,
            'object_id' => $object_id,
        )
    );
}

function get_followers_collection( $actor_slug ) {
    global $wpdb;
    $actor_id = \actors\get_actor_id( $actor_slug ); 
    if ( !$actor_id ) {
        return new \WP_Error(
            'not_found',
            __( 'Actor not found', 'pterotype' ),
            array( 'status' => 404 )
        );
    }
    $followers = $wpdb->get_results(
        $wpdb->prepare(
            "
           SELECT object FROM {$wpdb->prefix}pterotype_followers
           JOIN {$wpdb->prefix}pterotype_objects 
               ON object_id = {$wpdb->prefix}pterotype_objects.id
           WHERE actor_id = %d
           ",
            $actor_id
        ),
        ARRAY_A
    );
    if ( !$followers ) {
        $followers = array();
    }
    $collection = \collections\make_ordered_collection( $followers );
    $collection['id'] = get_rest_url( null, sprintf(
        '/pterotype/v1/actor/%s/followers', $actor_slug
    ) );
    return $collection;
}
?>
