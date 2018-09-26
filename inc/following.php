<?php
namespace following;

require_once plugin_dir_path( __FILE__ ) . '/collections.php';

define( 'PTEROTYPE_FOLLOW_PENDING', 'PENDING' );
define( 'PTEROTYPE_FOLLOW_FOLLOWING', 'FOLLOWING' );

function request_follow( $actor_id, $object_id ) {
    global $wpdb;
    return $wpdb->replace(
        'pterotype_following',
        array(
            'actor_id' => $actor_id,
            'object_id' => $object_id,
            'state' => PTEROTYPE_FOLLOW_PENDING
        )
    );
}

function accept_follow( $actor_id, $object_id ) {
    global $wpdb;
    return $wpdb->update(
        'pterotype_following',
        array( 'state' => PTEROTYPE_FOLLOW_FOLLOWING ),
        array( 'actor_id' => $actor_id, 'object_id' => $object_id ),
        array( '%s', '%d', '%d' )
    );
}

function reject_follow( $actor_id, $object_id ) {
    global $wpdb;
    return $wpdb->delete(
        'pterotype_following',
        array( 'actor_id' => $actor_id, 'object_id' => $object_id ),
        '%d'
    );
}

function get_following_collection( $actor_slug ) {
    global $wpdb;
    $actor_id = \actors\get_actor_id( $actor_slug );
    if ( !$actor_id ) {
        return new \WP_Error(
            'not_found', __( 'Actor not found', 'pterotype' ), array( 'status' => 404 )
        );
    }
    $objects = $wpdb->get_results(
        $wpdb->prepare(
            '
           SELECT object FROM pterotype_following
           JOIN pterotype_objects ON pterotype_following.object_id = pterotype_objects.id
           WHERE actor_id = %d
           AND state = %s;
           ',
            $actor_id, PTEROTYPE_FOLLOW_FOLLOWING
        ),
        ARRAY_A
    );
    if ( !$objects ) {
        $objects = array();
    }
    $collection = \collections\make_ordered_collection( $objects );
    $collection['id'] = get_rest_url( null, sprintf(
        '/pterotype/v1/actor/%s/following', $actor_slug
    ) );
    return $collection;
}
?>
