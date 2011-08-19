<?php
/*
Plugin Name: Mindvalley Comments Moderator
Plugin URI: http://mindvalley.com
Description: Create a custom role that enables only Comment Moderation actions and pages.
Author: MindValley
Version: 1.1.2
*/ 

class MV_Comment_Moderator {
	function __construct(){
		$this->add_role();
		$this->add_cap();
		add_action( 'admin_bar_menu', array(&$this, 'wp_admin_bar_comments_menu'), 50 );
		

		global $wp_version;
		if(version_compare($wp_version,'3.2') == -1){
			add_action( 'admin_menu', array(&$this, 'admin_menu_31'));
			add_action( 'admin_init', array(&$this, 'role_edit_31'));
		}else{
			add_action( 'wp_dashboard_setup', array(&$this, 'wp_dashboard_setup'));
			add_action( 'admin_menu', array(&$this, 'admin_menu'));
			add_action( 'init', array(&$this, 'role_edit'));
		}
	}
	
	/* Backwards Compatibility */
	
	// For WP 3.1.x
	function admin_menu_31(){
		if(!current_user_can( 'mv_moderate_comments' )) {
			return;
		}
		
		$awaiting_mod = wp_count_comments();
		$awaiting_mod = $awaiting_mod->moderated;
		$awaiting_mod = $awaiting_mod ? "<span id='ab-awaiting-mod' class='pending-count'>" . number_format_i18n( $awaiting_mod ) . "</span>" : '';
		
		add_submenu_page( 'edit-comments.php', sprintf( __('Comments %s'), $awaiting_mod ), 'Comments' , 'mv_moderate_comments','edit-comments.php');
	}

	function role_edit_31(){
		
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
				$role->add_cap('edit_pages');
				$role->add_cap('edit_published_posts');
				$role->add_cap('edit_published_pages');
				$role->add_cap('edit_others_posts');
				$role->add_cap('edit_others_pages');
			}
			
			// Flushing
			global $current_user;
			$current_user = '';
			get_currentuserinfo();
			
		}
	}
	
	/* End Backwards Compatibility */

	function wp_dashboard_setup(){
		$screen = get_current_screen();
		
		remove_meta_box( 'dashboard_right_now', $screen->id, 'normal' );
		remove_meta_box( 'dashboard_quick_press', $screen->id, 'side' );
		remove_meta_box( 'dashboard_recent_drafts', $screen->id, 'side' );
	}

	function admin_menu(){
		if(!current_user_can( 'mv_moderate_comments' )) {
			return;
		}
		
		$awaiting_mod = wp_count_comments();
		$awaiting_mod = $awaiting_mod->moderated;
		$awaiting_mod = $awaiting_mod ? "<span id='ab-awaiting-mod' class='pending-count'>" . number_format_i18n( $awaiting_mod ) . "</span>" : '';
		
		remove_menu_page( 'edit.php' );
		remove_menu_page( 'tools.php' );
	}
	
	function remove_menus(){
		global $menu;
		$restricted = array(__('Posts'), __('Media'), __('Links'), __('Pages'), __('Appearance'), __('Tools'), __('Users'), __('Settings'), __('Plugins'));
		$post_types=get_post_types(null,'objects');
		foreach($post_types as $pt){
			if($pt->_builtin != 1){
				$restricted[] = __($pt->labels->name);
			}
		}
		end ($menu);
		while (prev($menu)){
			$value = explode(' ',$menu[key($menu)][0]);
			if(in_array($value[0] != NULL?$value[0]:"" , $restricted)){unset($menu[key($menu)]);}
		}
	}
	
	function remove_bar_menus(){
		global $wp_admin_bar;
		$wp_admin_bar->remove_menu('new-content');
	}
	
	function role_edit(){
		
		// Only do this when capabilities = mv_moderate_comments
		if(current_user_can( 'mv_moderate_comments' )) {
			global $pagenow;

			// Only allow access on edit-comments.php
			if(	$pagenow == 'index.php' ||
				$pagenow == 'profile.php' ||
				$pagenow == 'edit-comments.php' || 
				$pagenow == 'comment.php' || 
			   ($pagenow == 'admin-ajax.php' && (	$_POST['action'] == 'edit-comment' || 
													$_POST['action'] == 'delete-comment' || 
													$_POST['action'] == 'replyto-comment')) ){
				$role = get_role('mv_comment_moderator');
				$role->add_cap('edit_posts');
				$role->add_cap('edit_pages');
				$role->add_cap('edit_published_posts');
				$role->add_cap('edit_published_pages');
				$role->add_cap('edit_others_posts');
				$role->add_cap('edit_others_pages');
			}
			
			// Flushing
			global $current_user;
			$current_user = '';
			get_currentuserinfo();
			
			add_action( 'admin_menu', array(&$this, 'remove_menus'));
			add_action( 'wp_before_admin_bar_render', array(&$this, 'remove_bar_menus'));
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
?>