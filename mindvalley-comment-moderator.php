<?php
/*
Plugin Name: Mindvalley Comments Moderator
Plugin URI: http://mindvalley.com/opensource
Description: Create a custom role that enables only Comment Moderation actions and pages.
Author: Mindvalley
Version: 1.0.3
*/

class MV_Comment_Moderator {
	function __construct(){
		$this->add_role();
		$this->add_cap();
		add_action( 'admin_bar_menu', array(&$this, 'wp_admin_bar_comments_menu'), 50 );
		add_action( 'admin_menu', array(&$this, 'admin_menu'));
		add_action( 'admin_init', array(&$this, 'role_edit'));
	}
	
	
	function admin_menu(){
		if(!current_user_can( 'mv_moderate_comments' )) {
			return;
		}
		
		$awaiting_mod = wp_count_comments();
		$awaiting_mod = $awaiting_mod->moderated;
		$awaiting_mod = $awaiting_mod ? "<span id='ab-awaiting-mod' class='pending-count'>" . number_format_i18n( $awaiting_mod ) . "</span>" : '';
	
		add_submenu_page( 'edit-comments.php', sprintf( __('Comments %s'), $awaiting_mod ), 'Comments' , 'mv_moderate_comments','edit-comments.php');
	
	}
	
	function role_edit(){
		// Only do this when capabilities = mv_moderate_comments
		if(current_user_can( 'mv_moderate_comments' )) {
			global $pagenow;
			
			// Only allow access on edit-comments.php
			if(	$pagenow == 'edit-comments.php' || 
				$pagenow == 'comment.php' || 
			   ($pagenow == 'admin-ajax.php' && (	$_POST['action'] == 'edit-comment' || 
													$_POST['action'] == 'delete-comment' || 
													$_POST['action'] == 'replyto-comment')) ){
				$role = get_role('mv_comment_moderator');
				$role->add_cap('edit_posts');
				$role->add_cap('edit_published_posts');
				$role->add_cap('edit_others_posts');
				
				// Flushing
				global $current_user;
				$current_user = '';
				get_currentuserinfo();
			}
		}
	}
	
	function wp_admin_bar_comments_menu(){
		if(!current_user_can( 'mv_moderate_comments' )) {
			return;
		}
		
		global $wp_admin_bar;
		
		$awaiting_mod = wp_count_comments();
		$awaiting_mod = $awaiting_mod->moderated;

		$awaiting_mod = $awaiting_mod ? "<span id='ab-awaiting-mod' class='pending-count'>" . number_format_i18n( $awaiting_mod ) . "</span>" : '';
		$wp_admin_bar->add_menu( array( 'id' => 'comments', 'title' => sprintf( __('Comments %s'), $awaiting_mod ), 'href' => admin_url('edit-comments.php') ) );
	}
	
	function add_role(){
		remove_role('mv_comment_moderator');
		add_role('mv_comment_moderator', 'Comments Moderator');
	}

	function add_cap(){
		$role = get_role('mv_comment_moderator');
		$role->add_cap('mv_moderate_comments');
		
		// Base Capabilities for comments moderation
		$role->add_cap('read');
		$role->add_cap('moderate_comments');
		$role->add_cap('edit_comment');
	}
}
new MV_Comment_Moderator();