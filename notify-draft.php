<?php

/*
Plugin Name: Notify on Draft Post
Plugin URI: http://www.gradin.com/2007/05/23/wp-plugin-notify-on-draft-post/
Description: Alerts WordPress moderator of impending post for review.  Allows poster (e.g. Contributor role) to decide when a draft is ready with a simple checkbox.  *No options*
Version: 1.0.1
Author: Olaf Gradin
Author URI: http://www.gradin.com/
*/

/*
    Copyright 2007  Olaf Gradin  (email : olaf.gradin@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/* nd_notify_moderator
   Notifies the moderator of the blog (usually the admin)
   about a post.  Formats email in similar fashion to wp_notify_moderator (hence the name).
*/
function nd_notify_moderator($id) {
	global $wpdb;

	$nd = $wpdb->prefix;

	$post = $wpdb->get_row("SELECT * FROM {$nd}posts, {$nd}users WHERE {$nd}posts.post_author = {$nd}users.ID AND {$nd}posts.ID = $id");

	$posts_waiting = $wpdb->get_var("SELECT count(ID) FROM {$nd}posts WHERE post_status = 'draft'");

	$notify_message  = sprintf( __('A new post #%1$s "%2$s" is waiting for your approval'), $id, $post->post_title ) . "\r\n";
	$notify_message .= get_permalink($id) . "\r\n\r\n";
	$notify_message .= sprintf( __('Author : %1$s'), $post->display_name ) . "\r\n";
	$notify_message .= sprintf( __('E-mail : %s'), $post->user_email ) . "\r\n";
	$notify_message .= __('Post: ') . "\r\n" . $post->post_content . "\r\n\r\n";
	$notify_message .= sprintf( __('To approve this post, visit: %s'),  get_settings('siteurl').'/wp-admin/post.php?action=edit&p='.$id."&post=$id" ) . "\r\n";
	$notify_message .= sprintf( __('Currently %s post(s) are waiting for approval.  Please visit the post page:'), $posts_waiting ) . "\r\n";
	$notify_message .= get_settings('siteurl') . "/wp-admin/post.php\r\n";

	$subject = sprintf( __('[%1$s] Please moderate: "%2$s"'), get_settings('blogname'), $post->post_title );

	/* To be replaced by an 'option page' where moderators can be defined */
	$admin_email = get_settings('admin_email');

	/* I saw this used in wp_notify_moderator, but I don't know the details about what it does - will wait for more information before implementing filters
	$notify_message = apply_filters('post_moderation_text', $notify_message);
	$subject = apply_filters('post_moderation_subject', $subject);
	*/

	@wp_mail($admin_email, $subject, $notify_message);

	return true;
}

/* output checkbox to signal moderator that post is ready for review (addition to the post form).  This form element is necessary for the nd_notify_moderator method to be called */
function add_notify_checkbox() {
	if ( !current_user_can('publish_posts') ) {
		/* borrowed the 'updated fade' class from WordPress for that nifty 'alert' look */
		echo '<div id="message" class="updated fade"><p align="right">Ready to post? <input name="notifyonpost" type="checkbox"> (subject to moderation)</p></div>';
	}
}

/* Inserted into the "save_post" action, this wil check for the appropriate notification flag and trigger nd_notify_moderator */
function check_notify($id) {
	if (isset($_POST['notifyonpost']) && $_POST['notifyonpost'] == "on") {
		nd_notify_moderator($id);
	}
}

/* I haven't actually tested all these forms out, but I presume that the appropriate checkbox will be inserted into all applicable methods for posting content.  It's also important that they get added first so that the checkbox is immediately below the "Save" button.  It would be non user-friendly to have this item in an unknown/unseen location on the post page. */
add_action('simple_edit_form', 'add_notify_checkbox',1);
add_action('edit_form_advanced', 'add_notify_checkbox',1);
add_action('edit_page_form', 'add_notify_checkbox',1);

add_action('save_post','check_notify');

?>