<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}
/**
 * Plugin Name:     Authenticate Sponsorware Videos via GitHub
 * Description:     This plugin allows Wordpress users to put a video and description behind Github oauth prompt. It can optionally check for sponsorship of a given organization or user to allow access.
 * Version:         1.2.2
 * Author:          opensheetmusicdisplay, fredmeister77, ranacseruet, jeremyhixon
 * License:         GPL-2.0-or-later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     githubauthvideo
 *
 */

/**
 * Registers all block assets so that they can be enqueued through the block editor
 * in the corresponding context.
 *
 * @see https://developer.wordpress.org/block-editor/tutorials/block-tutorial/applying-styles-with-stylesheets/
 */

 //Improvements to make: Only check for auth once if multiple videos on page

include_once 'includes.php';

githubauthvideo_GithubAPIServiceFactory::registerGithubAPIService(new githubauthvideo_GithubAPIService(GITHUB_GRAPH_API_URL));
githubauthvideo_PlayerHtmlRenderingFactory::registerPlayerHtmlRenderingService(new githubauthvideo_PlayerHtmlRenderer());

//If we get more media utility functions like this, break out into it's own file.
//For now, sufficient to contain it here
function githubauthvideo_get_video_mime_type($location){
	$mimes = new \Mimey\MimeTypes;
	$mimeType = 'video/*';
	//try path info
	$pathInfo = pathinfo($location, PATHINFO_EXTENSION);
	if(isset($pathInfo) && $pathInfo != ''){
		$mimeType = $mimes->getMimeType($pathInfo);
	}
	return $mimeType;
}

function githubauthvideo_block_init() {
	$dir = dirname( __FILE__ );

	$script_asset_path = "$dir/build/index.asset.php";
	if ( ! file_exists( $script_asset_path ) ) {
		throw new Error(
			'You need to run `npm start` or `npm run build` for the "phonicscore/githubauthvideo" block first.'
		);
	}
	$index_js     = 'build/index.js';
	$script_asset = require( $script_asset_path );
	wp_register_script(
		'phonicscore-githubauthvideo-block-editor',
		plugins_url( $index_js, __FILE__ ),
		$script_asset['dependencies'],
		$script_asset['version']
	);
	wp_set_script_translations( 'phonicscore-githubauthvideo-block-editor', 'githubauthvideo' );

	$editor_css = 'build/index.css';
	wp_register_style(
		'phonicscore-githubauthvideo-block-editor',
		plugins_url( $editor_css, __FILE__ ),
		array(),
		filemtime( "$dir/$editor_css" )
	);

	$style_css = 'build/style-index.css';
	wp_register_style(
		'phonicscore-githubauthvideo-block',
		plugins_url( $style_css, __FILE__ ),
		array(),
		filemtime( "$dir/$style_css" )
	);

	register_block_type( 'phonicscore/githubauthvideo', array(
		'editor_script' => 'phonicscore-githubauthvideo-block-editor',
		'editor_style'  => 'phonicscore-githubauthvideo-block-editor',
		'style'         => 'phonicscore-githubauthvideo-block',
		'render_callback' => 'githubauthvideo_block_render_callback'
	) );
}

//Determines what's rendered in WP.
function githubauthvideo_block_render_callback($block_attributes, $content) {
	if(is_admin()){
		return '';
	}
	$videoId = -1;
	if(isset($block_attributes['videoId'])){
		$videoId = $block_attributes['videoId'];
	}
	$returnPath = SERVER_REQUEST_URI;
	$orgId = get_post_meta( $videoId, 'githubauthvideo_github-organization-slug', true );
	$renderer = githubauthvideo_PlayerHtmlRenderingFactory::getPlayerHtmlRenderingServiceServiceInstance();

	$main_settings_options = get_option( 'githubauthvideo_main_settings' );
	$SERVER_SIDE_RENDERING = FALSE;
	if($main_settings_options && array_key_exists("server_side_rendering_6", $main_settings_options)){
		$SERVER_SIDE_RENDERING = $main_settings_options['server_side_rendering_6'];
	}

	if($SERVER_SIDE_RENDERING){
		if($videoId == -1){
			return '<div>No video was selected.</div>';
		}
		$GithubApi = githubauthvideo_GithubAPIServiceFactory::getGithubAPIServiceInstance();
		if($GithubApi->is_token_valid()){
			$isUserSponsor = $GithubApi->is_viewer_sponsor_of_video($videoId);
			if(gettype($isUserSponsor) === 'string'){
				return '<div>' . $isUserSponsor . '</div>';
			}
			if($isUserSponsor){
				//Token seems to be valid, render actual video embed
				return $renderer->get_video_html($videoId);
			} else {
				//User auth'd correctly, but is not sponsor of specified organization
				return $renderer->get_sponsor_html($videoId, $orgId);
			}
		} else {
			//User is not auth'd properly
			return $renderer->get_auth_html($videoId, $returnPath);
		}
		
	} else {
		//If we aren't doing server-side rendering, render the placeholder for JS to take over 
		return $renderer->get_video_placeholder_html($videoId, $orgId);	
	}
}

function githubauthvideo_block_enqueue_js( ) {
	$main_settings_options = get_option( 'githubauthvideo_main_settings' );

	$SERVER_SIDE_RENDERING = FALSE;
	if($main_settings_options && array_key_exists("server_side_rendering_6", $main_settings_options)){
		$SERVER_SIDE_RENDERING = $main_settings_options['server_side_rendering_6'];
	}

	//Only enqueue player script if we don't have server-side rendering enabled
	if(!$SERVER_SIDE_RENDERING){
		//Can't do conditional enqueuing since the block could be embedded on any post
		wp_enqueue_script(
			'githubauthvideo-script',
			esc_url( plugins_url( 'build/player/player.min.js', __FILE__ ) ),
			array( ),
			'1.1.1',
			true
		);

		$Cookies = githubauthvideo_GithubAuthCookies::getCookiesInstance();
		$IGNORE_SPONSORSHIP = FALSE;
		if($main_settings_options && array_key_exists("ignore_sponsorship_4", $main_settings_options)){
			$IGNORE_SPONSORSHIP = $main_settings_options['ignore_sponsorship_4'];
		}
	
		wp_localize_script(
			'githubauthvideo-script',
			'githubauthvideo_player_js_data',
			array(
				'token_key' => $Cookies->get_token_key(),
				'token_type_key' => $Cookies->get_token_type_key(),
				'github_api_url' => GITHUB_GRAPH_API_URL,
				'video_html_url' => '/?githubauthvideo_video_html=1',
				'ignore_sponsorship' => $IGNORE_SPONSORSHIP
			)
		);
	}
}

function githubauthvideo_enqueue_admin_assets($hook){
	wp_localize_script(
		'phonicscore-githubauthvideo-block-editor',
		'js_data',
		array(
			'player_image' => plugins_url( 'images/editor-player.png', __FILE__ )
		)
	);
	//Include the show pw script on the settings page
	if('github-video_page_githubauthvideo_settings' == $hook){
		wp_enqueue_script(
			'githubauthvideo-admin-script',
			esc_url( plugins_url( 'build/admin/settings.min.js', __FILE__ ) ),
			array( ),
			'0.5.1',
			true
		);
	}
}

function githubauthvideo_setup_rewrite_rules(){
	$structure = get_option( 'permalink_structure' );
	//These do not work with the default permalink type.
	//Just using query params seems to work for all cases.
	//add_rewrite_rule( 'githubauthvideo_video_html[/]?$', 'index.php?githubauthvideo_video_html=1', 'top' );
	//add_rewrite_rule( 'githubauthvideo_video/([0-9]+)/(\S{10})$', 'index.php?githubauthvideo_video=$matches[1]&nonce=$matches[2]', 'top' );
	//add_rewrite_rule( 'githubauthvideo_auth/([1-2])[/]?(.*)$', 'index.php?githubauthvideo_auth=$matches[1]', 'top' );
	add_filter( 'query_vars', function( $query_vars ) {
		array_push($query_vars, 'githubauthvideo_video', 'githubauthvideo_auth', 'githubauthvideo_video_html', 'code', 'state', 'return_path', 'nonce');
		return $query_vars;
	} );

	add_action( 'template_include', function( $template ) {
		$url = SERVER_SCHEME . '://' . SERVER_HOST . SERVER_REQUEST_URI;
		$path = parse_url($url, PHP_URL_PATH);
		//Don't allow overriding any other path with these query params other than root
		if($path !== '/'){
			return $template;
		} else if ( get_query_var( 'githubauthvideo_video' ) != false && get_query_var( 'githubauthvideo_video' ) != '' ) {
			return plugin_dir_path( __FILE__ ) . 'authentication/serve-video.php';
		} else if ( get_query_var( 'githubauthvideo_auth' ) != false && get_query_var( 'githubauthvideo_auth' ) != '' ) {
			return plugin_dir_path( __FILE__ ) . 'authentication/auth.php';
		}  else if ( get_query_var( 'githubauthvideo_video_html' ) != false && get_query_var( 'githubauthvideo_video_html' ) != '' ) {
			return plugin_dir_path( __FILE__ ) . 'api/serve-player-html.php';
		}
		return $template;
	} );
}

function githubauthvideo_activate() {
    // Register rewrite rules
	githubauthvideo_setup_rewrite_rules();
    // reset permalinks
    flush_rewrite_rules(); 
}
register_activation_hook( __FILE__, 'githubauthvideo_activate' );

function githubauthvideo_deactivate(){
	unregister_post_type('github-sponsor-video');

	flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'githubauthvideo_deactivate' );

function githubauthvideo_uninstall(){
	delete_option('githubauthvideo_main_settings');
	$github_videos = get_posts( array( 'post_type' => 'github-sponsor-video', 'numberposts' => -1));
	foreach( $github_videos as $video ) {
		wp_delete_post( $video->ID, false);
	}
   githubauthvideo_deactivate();
}

register_uninstall_hook(__FILE__, 'githubauthvideo_uninstall');

function githubauthvideo_register_post_meta(){
	new githubauthvideo_VideoEntryFields;
}
function githubauthvideo_register_settings_page(){
	if ( is_admin() )
		$main_settings = new githubauthvideo_MainSettings();
}


function githubauthvideo_author_cap_filter( $allowedposttags ) {

		//Here put your conditions, depending your context

		if ( !current_user_can( 'publish_posts' ) )
		return $allowedposttags;

		// Here add tags and attributes you want to allow

		$allowedposttags['iframe']=array(

		'align' => true,
		'width' => true,
		'height' => true,
		'frameborder' => true,
		'name' => true,
		'src' => true,
		'id' => true,
		'class' => true,
		'style' => true,
		'scrolling' => true,
		'marginwidth' => true,
		'marginheight' => true,
		'allowfullscreen' => true, 
		'mozallowfullscreen' => true, 
		'webkitallowfullscreen' => true,


		);
		return $allowedposttags;

}

function githubauthvideo_activate_plugin(){
	add_filter( 'wp_kses_allowed_html', 'githubauthvideo_author_cap_filter',1,1 );
	add_action ('init', 'githubauthvideo_register_post_meta');
	add_action ('init', 'githubauthvideo_register_settings_page');
	add_action( 'init', 'githubauthvideo_block_init' );
	add_action( 'init',  'githubauthvideo_setup_rewrite_rules' );
	add_action( 'wp_enqueue_scripts', 'githubauthvideo_block_enqueue_js' );
	add_action('admin_enqueue_scripts', 'githubauthvideo_enqueue_admin_assets');
}

add_action('plugins_loaded', 'githubauthvideo_activate_plugin', 10);
?>