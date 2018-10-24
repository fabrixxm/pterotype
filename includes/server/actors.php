<?php
namespace pterotype\actors;

require_once plugin_dir_path( __FILE__ ) . '../pgp.php';
require_once plugin_dir_path( __FILE__ ) . 'objects.php';

function get_actor( $id ) {
    global $wpdb;
    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pterotype_actors WHERE id = %d", $id
    ) );
    return get_actor_from_row( $row );
}

function get_actor_by_slug ( $slug ) {
    global $wpdb;
    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pterotype_actors WHERE slug = %s", $slug
    ) );
    return get_actor_from_row( $row );
}

function get_actor_id( $slug ) {
    global $wpdb;
    return $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}pterotype_actors WHERE slug = %s", $slug
    ) );
}

function get_actor_from_row( $row ) {
    if ( !$row ) {
        return new \WP_Error(
            'not_found', __( 'Actor not found', 'pterotype' ), array( 'status' => 404 )
        );
    }
    switch ( $row->type ) {
    case 'blog':
        return get_blog_actor();
    case 'user':
        $user = get_user_by( 'slug', $row->slug );
        return get_user_actor( $user );
    case 'commenter':
        return get_commenter_actor( $row );
    }
}

function get_commenter_actor( $row ) {
    $slug = $row->slug;
    $actor_id = get_actor_id( $slug );
    $email_address = $row->email;
    $actor = array(
        '@context' => array(
            'https://www.w3.org/ns/activitystreams',
            'https://w3id.org/security/v1',
        ),
        'type' => 'Person',
        'id' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s', $slug )
        ),
        'following' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/following', $slug )
        ),
        'followers' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/followers', $slug )
        ),
        'liked' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/liked', $slug )
        ),
        'inbox' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/inbox', $slug )
        ),
        'outbox' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/outbox', $slug )
        ),
        'preferredUsername' => $slug,
        'publicKey' => array(
            'id' => get_rest_url(
                null, sprintf( '/pterotype/v1/actor/%s#publicKey', $slug )
            ),
            'owner' => get_rest_url(
                null, sprintf( '/pterotype/v1/actor/%s', $slug )
            ),
            'publicKeyPem' => \pterotype\pgp\get_public_key( $actor_id ),
        ),
    );
    if ( ! empty( $row->name ) ) {
        $actor['name'] = $row->name;
    } else {
        $actor['name'] = $row->email;
    }
    if ( ! empty( $row->url ) ) {
        $actor['url'] = $row->url;
    }
    if ( ! empty( $row->icon ) ) {
        $actor['icon'] = $row->icon;
    }
    return $actor;
}

function get_blog_actor() {
    $actor_id = get_actor_id( PTEROTYPE_BLOG_ACTOR_SLUG );
    $actor = array(
        '@context' => array(
            'https://www.w3.org/ns/activitystreams',
            'https://w3id.org/security/v1',
        ),
        'type' => 'Organization',
        'id' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s', PTEROTYPE_BLOG_ACTOR_SLUG )
        ),
        'following' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/following', PTEROTYPE_BLOG_ACTOR_SLUG )
        ),
        'followers' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/followers', PTEROTYPE_BLOG_ACTOR_SLUG )
        ),
        'liked' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/liked', PTEROTYPE_BLOG_ACTOR_SLUG )
        ),
        'inbox' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/inbox', PTEROTYPE_BLOG_ACTOR_SLUG )
        ),
        'outbox' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/outbox', PTEROTYPE_BLOG_ACTOR_SLUG )
        ),
        'name' => get_bloginfo( 'name' ),
        // TODO in the future, make this configurable, both here and in the Webfinger handler
        'preferredUsername' => PTEROTYPE_BLOG_ACTOR_USERNAME,
        'summary' => get_bloginfo( 'description' ),
        'url' => network_site_url( '/' ),
        'publicKey' => array(
            'id' => get_rest_url(
                null, sprintf( '/pterotype/v1/actor/%s#publicKey', PTEROTYPE_BLOG_ACTOR_SLUG )
            ),
            'owner' => get_rest_url(
                null, sprintf( '/pterotype/v1/actor/%s', PTEROTYPE_BLOG_ACTOR_SLUG )
            ),
            'publicKeyPem' => \pterotype\pgp\get_public_key( $actor_id ),
        ),
    );
    if ( has_custom_logo() ) {
        $actor['icon'] = wp_get_attachment_image_src( get_theme_mod( 'custom_logo' ) )[0];
    }
    return $actor;
}

function get_user_actor( $user ) {
    $handle = get_the_author_meta( 'user_nicename', $user->get('ID'));
    $actor_id = get_actor_id( $handle );
    $actor = array(
        '@context' => array(
            'https://www.w3.org/ns/activitystreams',
            'https://w3id.org/security/v1',
        ),
        'type' => 'Person',
        'id' => get_rest_url( null, sprintf( '/pterotype/v1/actor/%s', $handle ) ),
        'following' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/following', $handle ) ),
        'followers' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/followers', $handle ) ),
        'liked' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/liked', $handle ) ),
        'inbox' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/inbox', $handle ) ),
        'outbox' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/outbox', $handle ) ),
        'preferredUsername' => $handle,
        'name' => get_the_author_meta( 'display_name', $user->get('ID') ),
        'summary' => get_the_author_meta( 'description', $user->get('ID') ),
        'icon' => get_avatar_url( $user->get('ID') ),
        'url' => get_the_author_meta( 'user_url', $user->get('ID') ),
        'publicKey' => array(
            'id' => get_rest_url(
                null, sprintf( '/pterotype/v1/actor/%s#publicKey', $handle )
            ),
            'owner' => get_rest_url(
                null, sprintf( '/pterotype/v1/actor/%s', $handle )
            ),
            'publicKeyPem' => \pterotype\pgp\get_public_key( $actor_id ),
        ),
    );
    return $actor;
}

function initialize_actors() {
    global $wpdb;
    $user_slugs = $wpdb->get_col( 
        "SELECT user_nicename FROM {$wpdb->users};"
    );
    foreach ( $user_slugs as $user_slug ) {
        create_actor( $user_slug, 'user' );
        $actor_id = get_actor_id( $user_slug );
        $keys_created = \pterotype\pgp\get_public_key( $actor_id );
        if ( ! $keys_created ) {
            $keys = \pterotype\pgp\gen_key( $user_slug );
            \pterotype\pgp\persist_key( $actor_id, $keys['publickey'], $keys['privatekey'] );
        }
    }
    create_actor( PTEROTYPE_BLOG_ACTOR_SLUG, 'blog' );
    $blog_actor_id = get_actor_id( PTEROTYPE_BLOG_ACTOR_SLUG );
    $keys_created = \pterotype\pgp\get_public_key( $blog_actor_id );
    if ( ! $keys_created ) {
        $keys = \pterotype\pgp\gen_key( PTEROTYPE_BLOG_ACTOR_SLUG );
        \pterotype\pgp\persist_key( $blog_actor_id, $keys['publickey'], $keys['privatekey'] );
    }
}

function create_actor( $slug, $type, $email = null, $url = null, $name = null, $icon = null ) {
    global $wpdb;
    $res = $wpdb->query( get_create_actor_query( $slug, $type, $email, $url, $name, $icon ) );
    if ( $res === false ) {
        return new \WP_Error(
            'db_error',
            __( 'Error creating actor', 'pterotype' )
        );
    }
    $actor = get_actor_by_slug( $slug );
    $res = \pterotype\objects\upsert_object( $actor );
    if ( is_wp_error( $res ) ) {
        return $res;
    }
    return $res->object;
}

function get_create_actor_query( $slug, $type, $email = null, $url = null, $name = null, $icon = ull ) {
    global $wpdb;
    $query = "INSERT IGNORE INTO {$wpdb->prefix}pterotype_actors(slug, type";
    $args = array( $slug, $type );
    if ( $email ) {
        $query = $query . ", email";
        $args[] = $email;
    }
    if ( $url ) {
        $query = $query . ", url";
        $args[] = $url;
    }
    if ( $name ) {
        $query = $query . ", name";
        $args[] = $name;
    }
    if ( $icon ) {
        $query = $query . ", icon";
        $args[] = $icon;
    }
    $query = $query . ") VALUES (";
    $placeholders = join( ',', array_map( function( $el ) { return '%s'; }, $args ) );
    $query = $query . $placeholders . ")";
    return $wpdb->prepare( $query, $args );
}

function upsert_commenter_actor( $email_address, $url = null, $name = null, $icon = null ) {
    global $wpdb;
    $slug = email_address_to_slug( $email_address );
    $existing = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pterotype_actors WHERE slug = %s",
        $slug
    ) );
    if ( $existing !== null ) {
        return $slug;
    }
    create_actor( $slug, 'commenter', $email_address, $url, $name, $icon );
    $actor_id = get_actor_id( $slug );
    $keys_created = \pterotype\pgp\get_public_key( $actor_id );
    if ( ! $keys_created ) {
        $keys = \pterotype\pgp\gen_key( $slug );
        \pterotype\pgp\persist_key( $actor_id, $keys['publickey'], $keys['privatekey'] );
    }
    $actor = get_actor_by_slug( $slug );
    $res = \pterotype\objects\upsert_object( $actor );
    if ( is_wp_error( $res ) ) {
        return $res;
    }
    return $slug;
}

function email_address_to_slug( $email_address ) {
    $slug = str_replace( array( '@', '.'), '_', $email_address );
    return preg_replace( '/[^a-zA-Z0-9-_]/', '', $slug );
}
?>
