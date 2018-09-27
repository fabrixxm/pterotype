<?php
namespace api;

require_once plugin_dir_path( __FILE__ ) . 'actors.php';
require_once plugin_dir_path( __FILE__ ) . 'outbox.php';
require_once plugin_dir_path( __FILE__ ) . 'objects.php';
require_once plugin_dir_path( __FILE__ ) . 'activities.php';
require_once plugin_dir_path( __FILE__ ) . 'following.php';
require_once plugin_dir_path( __FILE__ ) . 'likes.php';
require_once plugin_dir_path( __FILE__ ) . 'shares.php';

function get_actor( $request ) {
    $actor = $request['actor'];
    return \actors\get_actor_by_slug( $actor );
}

function post_to_outbox( $request ) {
    $actor_slug = $request['actor'];
    $activity = json_decode( $request->get_body(), true );
    return \outbox\handle_activity( $actor_slug, $activity );
}

function get_outbox( $request ) {
    $actor_slug = $request['actor'];
    return \outbox\get_outbox( $actor_slug );
}

function get_object( $request ) {
    $id = $request['id'];
    return \objects\get_object( $id );
}

function get_activity( $request ) {
    $id = $request['id'];
    return \activities\get_activity( $id );
}

function get_following( $request ) {
    $actor_slug = $request['actor'];
    return \following\get_following_collection( $actor_slug );
}

function get_followers( $request ) {
    $actor_slug = $request['actor'];
    return \followers\get_followers_collection( $actor_slug );
}

function get_likes( $request ) {
    $object_id = $request['object'];
    return \likes\get_likes_collection( $object_id );
}

function get_shares( $request ) {
    $object_id = $request['object'];
    return \shares\get_shares_collection( $object_id );
}

function register_routes() {
    register_rest_route( 'pterotype/v1', '/actor/(?P<actor>[a-zA-Z0-9-]+)/outbox', array(
        'methods' => 'POST',
        'callback' => __NAMESPACE__ . '\post_to_outbox',
    ) );
    register_rest_route( 'pterotype/v1', '/actor/(?P<actor>[a-zA-Z0-9-]+/outbox', array(
        'methods' => 'POST',
        'callback' => __NAMESPACE__ . '\get_outbox',
    ) );
    register_rest_route( 'pterotype/v1', '/actor/(?P<actor>[a-zA-Z0-9-]+)', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\get_actor',
    ) );
    register_rest_route( 'pterotype/v1', '/object/(?P<id>[0-9]+)', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\get_object',
    ) );
    register_rest_route( 'pterotype/v1', '/activity/(?P<id>[0-9]+)', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\get_activity',
    ) );
    register_rest_route( 'pterotype/v1', '/actor/(?P<actor>[a-zA-Z0-9-]+)/following', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\get_following',
    ) );
    register_rest_route( 'pterotype/v1', '/actor/(?P<actor>[a-zA-Z0-9-]+)/followers', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\get_followers',
    ) );
    register_rest_route( 'pterotype/v1', '/object/(?P<object>[0-9]+)/likes', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\get_likes',
    ) );
    register_rest_route( 'pterotype/v1', '/object/(?P<object>[0-9]+)/shares', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\get_shares',
    ) );
}
?>
