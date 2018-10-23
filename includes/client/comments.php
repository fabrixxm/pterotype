<?php
namespace pterotype\comments;

require_once plugin_dir_path( __FILE__ ) . '../server/activities/create.php';
require_once plugin_dir_path( __FILE__ ) . '../server/activities/update.php';
require_once plugin_dir_path( __FILE__ ) . '../server/activities/delete.php';
require_once plugin_dir_path( __FILE__ ) . '../server/objects.php';

function handle_comment_post( $comment_id, $comment_approved ) {
    xdebug_break();
    if ( $comment_approved ) {
        $comment = \get_comment( $comment_id );
        handle_transition_comment_status( 'approve', 'nonexistent', $comment );
    }
}

function handle_edit_comment( $comment_id ) {
    $comment = \get_comment( $comment_id );
    if ( $comment->comment_approved ) {
        handle_transition_comment_status( 'approve', 'approve', $comment );
    }
}

function handle_transition_comment_status( $new_status, $old_status, $comment ) {
    xdebug_break();
    // This creates a new commenter actor if necessary
    $actor_slug = get_comment_actor_slug( $comment );
    $actor_outbox = get_rest_url(
        null, sprintf( 'pterotype/v1/actor/%s/outbox', $actor_slug )
    );
    $comment_object = comment_to_object( $comment, $actor_slug );
    $activity = null;
    if ( $new_status == 'approve' && $old_status != 'approve' ) {
        // Create
        $activity = \pterotype\activities\create\make_create( $actor_slug, $comment_object );
    } else if ( $new_status == 'approve' && $old_status == 'approve' ) {
        // Update
        $activity = \pterotype\activities\update\make_update( $actor_slug, $comment_object );
    } else if ( $new_status == 'trash' && $old_status != 'trash' ) {
        // Delete
        $activity = \pterotype\activities\delete\make_delete( $actor_slug, $comment_object );
    }
    if ( $activity && ! is_wp_error( $activity ) ) {
        $followers = \pterotype\followers\get_followers_collection( $actor_slug );
        $activity['to'] = get_comment_to( $comment_object, $followers['id'] );
        $server = rest_get_server();
        $request = \WP_REST_Request::from_url( $actor_outbox );
        $request->set_method( 'POST' );
        $request->set_body( wp_json_encode( $activity ) );
        $request->add_header( 'Content-Type', 'application/ld+json' );
        $server->dispatch( $request );
    }
}

function get_comment_actor_slug( $comment ) {
    if ( $comment->user_id !== 0 ) {
        return get_comment_user_actor_slug( $comment->user_id );
    } else {
        return get_comment_email_actor_slug( $comment->comment_author_email );
    }
}

function get_comment_user_actor_slug( $user_id ) {
    if ( \user_can( $user_id, 'publish_posts' ) ) {
        return PTEROTYPE_BLOG_ACTOR_SLUG;
    } else {
        $user = \get_userdata( $user_id );
        return $user->user_nicename;
    }
}

function get_comment_email_actor_slug( $email_address ) {
    $slug = \pterotype\actors\upsert_commenter_actor( $email_address );
    return $slug;
}

function comment_to_object( $comment, $actor_slug ) {
    $post = \get_post( $comment->comment_post_ID );
    \setup_postdata( $post );
    $post_permalink = \get_permalink( $post );
    $post_object = \pterotype\objects\get_object_by( 'url', $post_permalink );
    $inReplyTo = $post_object['id'];
    if ( $comment->comment_parent !== '0' ) {
        $parent_comment = \get_comment( $comment->comment_parent );
        $parent_object = \pterotype\objects\get_object_by(
            'url', \get_comment_link( $parent_comment )
        );
        if ( $parent_object ) {
            $inReplyTo = $parent_object['id'];
        }
    }
    $link = \get_comment_link( $comment );
    $object = array(
        '@context' => array( 'https://www.w3.org/ns/activitystreams' ),
        'type' => 'Note',
        'content' => $comment->comment_content,
        'attributedTo' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s', $actor_slug )
        ),
        'url' => $link,
        'inReplyTo' => $inReplyTo,
    );
    $existing = \pterotype\objects\get_object_by( 'url', $link );
    if ( $existing ) {
        $object['id'] = $existing['id'];
    }
    return $object;
}

function get_comment_to( $comment, $followers_id ) {
    $to = array(
        'https://www.w3.org/ns/activitystreams#Public',
        $followers_id,
    );
    $to = array_unique( array_merge( $to, traverse_reply_chain( $comment ) ) );
    return $to;
}

function traverse_reply_chain( $comment ) {
    return traverse_reply_chain_helper( $comment, 0, array() );
}

function traverse_reply_chain_helper( $object, $depth, $acc ) {
    if ( $depth === 50 ) {
        return $acc;
    }
    if ( array_key_exists( 'inReplyTo', $object ) ) {
        return $acc;
    }
    $parent = \pterotype\util\dereference_object( $object['inReplyTo'] );
    $recipients = array();
    foreach( array( 'to', 'bto', 'cc', 'bcc', 'audience' ) as $field ) {
        if ( array_key_exists( $field, $parent ) ) {
            $new_recipients = $parent[$field];
            if ( ! is_array( $new_recipients ) ) {
                $new_recipients = array( $new_recipients );
            }
            $recipients = array_unique( array_merge( $recipients, $new_recipients ) );
        }
    }
    return traverse_reply_chain_helper(
        $parent, $depth + 1, array_unique( array_merge( $acc, $recipients ) )
    );
}
?>
