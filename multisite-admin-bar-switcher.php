<?php
/*
	Plugin Name: Multisite Admin bar Switcher
	Plugin URI: http://www.flynsarmy.com
	Description: Replaces the built in 'My Sites' drop down with a better layed out one
	Version: 1.0.4
	Author: Flyn San
	Author URI: http://www.flynsarmy.com/
*/
?><?php
/*
	Copyright 2013  Flyn San  (email : flynsarmy@gmail.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
?><?php
add_action('admin_bar_menu', 'mabs', 40);

/**
 * Adds a blogs submenu items to the admin drop down menu.
 *
 * @param  string $blog_type 'site' or 'network'
 * @param  integer $id   site ID
 * @param  string $url  '<url>/wp-admin/'
 *
 * @return void
 */
function mabs_display_blog_pages( $user, $id, $admin_url )
{
	global $wp_admin_bar;
	if ( $id == 'network' )
		$pages = array(
			'dashboard' 	=> array('url' => 'index.php'),
			'sites' 		=> array('url' => 'sites.php'),
			'users' 		=> array('url' => 'users.php'),
			'themes' 		=> array('url' => 'themes.php'),
			'plugins' 		=> array('url' => 'plugins.php'),
			'settings' 		=> array('url' => 'settings.php'),
			'updates' 		=> array('url' => 'update-core.php'),
		);
	else
		$pages = array(
			'dashboard' 	=> array('url' => 'index.php'),
			'visit' 		=> array('url' => ''),
			'posts' 		=> array('url' => 'edit.php', 			'permission' => 'edit_posts'),
			'media' 		=> array('url' => 'media.php', 			'permission' => 'upload_files'),
			'links' 		=> array('url' => 'link-manager.php', 	'permission' => 'manage_links'),
			'pages' 		=> array('url' => 'edit.php?post_type=page', 'permission' => 'edit_pages'),
			'comments' 		=> array('url' => 'edit-comments.php', 	'permission' => 'edit_posts'),
			'appearance' 	=> array('url' => 'themes.php', 		'permission' => 'switch_themes'),
			'plugins' 		=> array('url' => 'plugins.php', 		'permission' => 'install_plugins'),
			'users' 		=> array('url' => 'users.php', 			'permission' => 'list_users'),
			'tools' 		=> array('url' => 'tools.php', 			'permission' => 'import'),
			'settings' 		=> array('url' => 'options-general.php','permission' => 'manage_options'),
		);

	foreach ( $pages as $key => $details )
	{
		if ( $key == "visit" )
			$wp_admin_bar->add_menu(array(
				'parent' => 'mabs_'.$id,
				'id' =>'mabs_'.$id.'_visit',
				'title'=>__('Visit Site'),
				'href'=>str_replace('wp-admin/','',$admin_url)
			));
		elseif ( empty($details['permission']) || user_can($user->ID, $details['permission']) )
			$wp_admin_bar->add_menu(array(
				'parent' => 'mabs_'.$id,
				'id' =>'mabs_'.$id.'_'.$key,
				'title'=>__(ucfirst($key)),
				'href' => $admin_url.$details['url']
			));
	}
}

/**
 * Add the blog list under their respective letters
 *
 * @param  stdClass $user A wordpress user
 *
 * @return void
 */
function mabs_display_blogs_for_user( $user )
{
	global $wp_admin_bar,$wpdb;

	$blogs = mabs_get_blog_list( $user );

	//Add letter submenus
	mabs_display_letters( $blogs );

	// add menu item for each blog
	$i = 1;
	foreach ( $blogs as $key => $blog )
	{
		$letter = substr($key, 0, 1);
		$site_parent = "mabs_".$letter."_letter";
		$admin_url = get_admin_url( $blog->userblog_id );

		//Add the site
		$wp_admin_bar->add_menu(array(
			'parent' => $site_parent,
			'id' => 'mabs_'.$letter.$i,
			'title' => $blog->blogname,
			'href' => $admin_url
		));

		//Add site submenu options
		mabs_display_blog_pages($user, $letter.$i, $admin_url);

		$i++;
	}
}

/**
 * Adds a letter submenu for each blog to sit in
 *
 * @param  array  $blogs List of blogs
 *
 * @return void
 */
function mabs_display_letters( array $blogs )
{
	global $wp_admin_bar;

	$letters = array();
	foreach ( $blogs as $key => $blog )
		$letters[ strtoupper(substr($key, 0, 1)) ] = '';

	foreach ( array_keys($letters) as $letter )
		$wp_admin_bar->add_menu(array(
			'parent' => 'mabs',
			'id' => 'mabs_'.$letter.'_letter',
			'title'=>__($letter)
		));
}

/**
 * Returns an alphabetically sorted array of blogs
 *
 * @param  stdClass $user Current user object
 *
 * @return array       Alphabetically sorted array of blogs
 *      stdClass Object
 *      (
 *          [userblog_id] => 1
 *          [blogname] => My Blog
 *          [domain] => myblog.localhost.com
 *          [path] => /
 *          [site_id] => 1
 *          [siteurl] => http://myblog.localhost.com
 *          [archived] => 0
 *          [spam] => 0
 *          [deleted] => 0
 *      )
 */
$mabs_user_blog_list = array();
function mabs_get_blog_list( $user )
{
	global $mabs_user_blog_list;

	// Cache
	if ( !isset($mabs_user_blog_list[$user->ID]))
		$mabs_user_blog_list[$user->ID] = get_blogs_of_user( $user->ID );

	$unsorted_list = $mabs_user_blog_list[$user->ID];
	$sorted = array();

	// Add blogname to key list. Also add a number so we
	// are certain keys are unique
	foreach ( $unsorted_list as $key => $blog )
		$sorted[ $blog->blogname . $key ] = $blog;

	ksort($sorted);

	return $sorted;
}

function mabs() {
	// No need to show MABS
	if ( !is_multisite() || !is_admin_bar_showing() )
		return;

	global $wp_admin_bar, $wpdb, $current_blog;

	$wp_admin_bar->remove_node('my-sites');
	$wp_admin_bar->remove_node('site-name');

	$current_user = wp_get_current_user();

	// current site path
	if ( is_network_admin() )
	{
		$blogname = __('Network');
		$url = get_home_url( $current_blog->blog_id );
	}
	elseif ( is_admin() )
	{
		$blogname = get_blog_option($current_blog->blog_id, "blogname");
		$url = get_home_url( $current_blog->blog_id );
	}
	else
	{
		$blogname = get_blog_option($current_blog->blog_id, "blogname");
		$url = get_admin_url( $current_blog->blog_id );
	}


	// Add top menu
	$wp_admin_bar->add_menu(array(
		'parent' => false,
		'id' => 'mabs',
		'title' => __('My Sites') . ': ' . $blogname,
		'href' => $url,
	));

	// Add 'Your Site'
	$url = get_admin_url( $current_blog->blog_id );
	$wp_admin_bar->add_menu(array(
		'parent' => 'mabs',
		'id' => 'mabs_yoursite',
		'title' =>__('Your Site'),
		'href' => str_replace('/wp-admin/', '', $url)
	));
	mabs_display_blog_pages($current_user, 'yoursite', $url);

	// Add 'Network'
	if ( current_user_can('manage_network') )
	{
		// add network menu
		$url = network_admin_url();
		$wp_admin_bar->add_menu(array(
			'parent' => 'mabs',
			'id' => 'mabs_network',
			'title' =>__('Network'),
			'href' => $url,
		));
		mabs_display_blog_pages($current_user, 'network', $url);
	}

	// Add users' blogs
	mabs_display_blogs_for_user( $current_user );
}

?>