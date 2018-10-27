<?php
namespace pterotype\api;

require_once plugin_dir_path( __FILE__ ) . 'actors.php';
require_once plugin_dir_path( __FILE__ ) . 'outbox.php';
require_once plugin_dir_path( __FILE__ ) . 'inbox.php';
require_once plugin_dir_path( __FILE__ ) . 'objects.php';
require_once plugin_dir_path( __FILE__ ) . 'following.php';
require_once plugin_dir_path( __FILE__ ) . 'likes.php';
require_once plugin_dir_path( __FILE__ ) . 'shares.php';

function get_actor( $request ) {
    $actor = $request->get_url_params()['actor'];
    return \pterotype\actors\get_actor_by_slug( $actor );
}

function post_to_outbox( $request ) {
    $actor_slug = $request->get_url_params()['actor'];
    $body = $request->get_body();
    $activity = $body;
    if ( is_string( $body ) ) {
        $activity = json_decode( $body, true );
    }
    return \pterotype\outbox\handle_activity( $actor_slug, $activity );
}

function get_outbox( $request ) {
    $actor_slug = $request->get_url_params()['actor'];
    return \pterotype\outbox\get_outbox( $actor_slug );
}

function post_to_inbox( $request ) {
    $actor_slug = $request->get_url_params()['actor'];
    $body = $request->get_body();
    $activity = $body;
    if ( is_string( $body ) ) {
        $activity = json_decode( $body, true );
    }
    return \pterotype\inbox\handle_activity( $actor_slug, $activity );
}

function get_inbox( $request ) {
    $actor_slug = $request->get_url_params()['actor'];
    return \pterotype\inbox\get_inbox( $actor_slug );
}

function get_object( $request ) {
    $id = $request->get_url_params()['id'];
    return \pterotype\objects\get_object( $id );
}

function get_following( $request ) {
    $actor_slug = $request->get_url_params()['actor'];
    return \pterotype\following\get_following_collection( $actor_slug );
}

function get_followers( $request ) {
    $actor_slug = $request->get_url_params()['actor'];
    return \pterotype\followers\get_followers_collection( $actor_slug );
}

function get_likes( $request ) {
    $object_id = $request->get_url_params()['object'];
    return \pterotype\likes\get_likes_collection( $object_id );
}

function get_shares( $request ) {
    $object_id = $request->get_url_params()['object'];
    return \pterotype\shares\get_shares_collection( $object_id );
}

function user_can_post_to_outbox( $request ) {
    $actor_slug = $request->get_url_params()['actor'];
    $actor_row = \pterotype\actors\get_actor_row_by_slug( $actor_slug );
    if ( ! $actor_row || is_wp_error( $actor_row ) ) {
        return false;
    }
    if ( $actor_row->type === 'blog' ) {
        return \current_user_can( 'publish_posts' );
    } else if ( $actor_row->type === 'user' ) {
        return \is_user_logged_in();
    }
    return true;
}

function register_routes() {
    register_rest_route( 'pterotype/v1', '/actor/(?P<actor>[a-zA-Z0-9-_]+)/outbox', array(
        'methods' => 'POST',
        'callback' => __NAMESPACE__ . '\post_to_outbox',
        'permission_callback' => __NAMESPACE__ . '\user_can_post_to_outbox',
    ) );
    register_rest_route( 'pterotype/v1', '/actor/(?P<actor>[a-zA-Z0-9-_]+)/outbox', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\get_outbox',
    ) );
    register_rest_route( 'pterotype/v1', '/actor/(?P<actor>[a-zA-Z0-9-_]+)/inbox', array(
        'methods' => 'POST',
        'callback' => __NAMESPACE__ . '\post_to_inbox',
    ) );
    register_rest_route( 'pterotype/v1', '/actor/(?P<actor>[a-zA-Z0-9-_]+)/inbox', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\get_inbox',
    ) );
    register_rest_route( 'pterotype/v1', '/actor/(?P<actor>[a-zA-Z0-9-_]+)', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\get_actor',
    ) );
    register_rest_route( 'pterotype/v1', '/object/(?P<id>[0-9]+)', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\get_object',
    ) );
    register_rest_route( 'pterotype/v1', '/actor/(?P<actor>[a-zA-Z0-9-_]+)/following', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\get_following',
    ) );
    register_rest_route( 'pterotype/v1', '/actor/(?P<actor>[a-zA-Z0-9-_]+)/followers', array(
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

function query_vars( $query_vars ) {
    $query_vars[] = 'pterotype_comment';
    return $query_vars;
}

function handle_non_api_requests() {
    global $wp;
    global $wp_query;
    $accept = $_SERVER['HTTP_ACCEPT'];
    if ( strpos( $accept, 'application/ld+json' ) !== false ) {
        $current_url = home_url( add_query_arg( $_GET, \trailingslashit( $wp->request ) ) );
        $objects = \pterotype\objects\get_objects_by( 'url', $current_url );
        if ( count( $objects ) > 0 ) {
            $object = $objects[0];
            header( 'Content-Type: application/activity+json', true );
            echo wp_json_encode( $object );
            exit;
        }
    } else if ( array_key_exists( 'pterotype_comment', $wp_query->query_vars ) ) {
        $comment_anchor = $wp_query->query_vars['pterotype_comment'];
        $current_url = \trailingslashit( home_url( $wp->request ) );
        $actual_url = $current_url . '#' . $comment_anchor;
        \wp_redirect( $actual_url );
        exit;
    }
}
?>
