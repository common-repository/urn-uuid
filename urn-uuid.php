<?php
/*
Plugin Name: urn:uiuid as the_guid
Plugin URI:  https://www.ctrl.blog/topic/wordpress
Description: Use an urn:uuid:<uuid4> for the_guid rather than using the_permalink.
Version:     1.1
Author:      Geeky Software
Author URI:  https://www.ctrl.blog/topic/wordpress
License:     GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

if (!defined('ABSPATH')) {
  header('HTTP/1.1 403 Forbidden');
  exit(  'HTTP/1.1 403 Forbidden');
}

function urn_uuid_new_uuidv4_urn() {
  $uuid = wp_generate_uuid4();
  $uuid = "urn:uuid:${uuid}";

  return $uuid;
}

function urn_uuid_replace($post_id = 0, $post = NULL, $update = FALSE) {
  global $wpdb;

  // only overwrite the guid for new posts
  if ($update) {
    return FALSE;
  }

  // check if post already uses an uuid for guid
  $existing_id = get_the_guid($post_id);
  if (strpos($existing_id, 'urn:uuid:') === 0) {
    return FALSE;
  }

  $uuid = urn_uuid_new_uuidv4_urn();

  if ($uuid) {
    $wpdb->update($wpdb->posts, array('guid' => $uuid), array('ID' => $post_id));
    return TRUE;
  }

  return FALSE;
}

add_action('save_post_post', 'urn_uuid_replace', 10, 3);
add_action('urn_uuid_firstrunner', 'urn_uuid_replace', 10, 3);

if (is_admin()) {
  if (get_option('urn_uuid_firstrun', '0') == '0') {
    update_option('urn_uuid_firstrun', '1');

    $these_posts = get_posts(array(
      'offset' => 0,
      'orderby' => 'rand',
      'posts_per_page' => -1,
      'post_type' => 'post' ));

    $delay = time() + 30;
    foreach ($these_posts as $postkey => $post) {
      wp_schedule_single_event($delay, 'urn_uuid_firstrunner', array($post->ID, NULL, FALSE));
      $delay += 6;  // +6 seconds
  } }

  function urn_uuid_deactivate() {
    // unschedule every possible scheduled task.
    $these_posts = get_posts( array(
      'offset' => 0,
      'orderby' => 'rand',
      'posts_per_page' => -1,
      'post_type' => 'post' ));

    foreach ($these_posts as $postkey => $post) {
      wp_clear_scheduled_hook('urn_uuid_firstrunner', array( $post->ID, NULL, FALSE));
    }
    register_deactivation_hook(__FILE__, 'urn_uuid_deactivate');
} }
