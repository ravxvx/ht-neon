<?php
/**
 * Plugin Name: HT Neon
 * Description: Ciemny, fioletowo-różowy wygląd holistictherapists.ie nakładany na motyw ListingPro: własny nagłówek z logo, nowa strona główna (baner z wyszukiwarką, kategorie, zachęta do rejestracji, liczniki) i odświeżone podstrony. Tryb podglądu pokazuje zmiany tylko administratorom. Wyłączenie wtyczki przywraca poprzedni wygląd.
 * Version: 3.14.0
 * Author: Holistic Therapists Ireland
 * License: GPL-2.0-or-later
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: ht-neon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HTN_VERSION', '3.14.0' );
define( 'HTN_OPTION', 'htn_settings' );
define( 'HTN_URL', plugin_dir_url( __FILE__ ) );
define( 'HTN_PATH', plugin_dir_path( __FILE__ ) );

/* ------------------------------------------------------------------ *
 * Settings
 * ------------------------------------------------------------------ */

function htn_defaults() {
	return array(
		'mode'       => 'preview', // off | preview | live
		'theme'      => 'natural', // neon | natural
		'home'       => 1,
		'header'     => 1,
		'footer'     => 1,
		'dark'       => 1,
		'events'     => 1,
		'blog'       => 1,
		'favicon'    => 1,
		'cta_band'   => 1,
		'cta_bar'    => 0,
		'stats'      => 1,
		'join_url'   => '/pricing-plan/',
		'join_label' => 'Join as a Therapist',
		'hero_image' => '',
	);
}

function htn_settings() {
	$saved = get_option( HTN_OPTION, array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	return wp_parse_args( $saved, htn_defaults() );
}

function htn_theme() {
	$s = htn_settings();
	return 'neon' === $s['theme'] ? 'neon' : 'natural';
}

function htn_is_natural() {
	return 'natural' === htn_theme();
}

function htn_is_listingpro() {
	return 'listingpro' === get_template();
}

/** Should the new look be shown for the current request? */
function htn_is_active() {
	if ( is_admin() || ! htn_is_listingpro() ) {
		return false;
	}
	$s = htn_settings();
	if ( 'live' === $s['mode'] ) {
		return true;
	}
	if ( 'preview' === $s['mode'] ) {
		if ( isset( $_GET['ht_preview'] ) && '1' === $_GET['ht_preview'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			return true;
		}
		return is_user_logged_in() && current_user_can( 'manage_options' );
	}
	return false;
}

function htn_is_dashboard() {
	return is_page_template( 'template-dashboard.php' );
}

function htn_use_header() {
	$s = htn_settings();
	return htn_is_active() && ! empty( $s['header'] ) && ! htn_is_dashboard();
}

function htn_use_home() {
	$s = htn_settings();
	return htn_is_active() && ! empty( $s['home'] ) && is_front_page();
}

function htn_use_footer() {
	$s = htn_settings();
	if ( ! htn_is_active() || empty( $s['footer'] ) || htn_is_dashboard() ) {
		return false;
	}
	return ! htn_is_map_archive();
}

/** ListingPro drops its footer on half-map archive layouts; follow the same rule. */
function htn_is_map_archive() {
	if ( is_tax( array( 'location', 'listing-category', 'list-tags', 'features' ) ) || is_search() ) {
		$style = function_exists( 'lp_theme_option' ) ? (string) lp_theme_option( 'listing_style' ) : '';
		if ( isset( $_GET['list-style'] ) && '' !== $_GET['list-style'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$style = sanitize_text_field( wp_unslash( $_GET['list-style'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}
		return in_array( $style, array( '2', '3', '5', '6' ), true );
	}
	return false;
}

function htn_use_dark() {
	$s = htn_settings();
	return htn_is_active() && ! htn_is_natural() && ! empty( $s['dark'] ) && ! htn_is_dashboard();
}

function htn_url( $path ) {
	$path = (string) $path;
	return ( 0 === strpos( $path, '/' ) ) ? home_url( $path ) : $path;
}

function htn_join_url() {
	$s      = htn_settings();
	$target = htn_url( $s['join_url'] );
	if ( is_user_logged_in() || ! get_option( 'users_can_register' ) ) {
		return $target;
	}
	// Guests see the plans first; the pricing page has its own "Get started" (registration) buttons.
	$pricing = get_page_by_path( 'pricing-plan' );
	if ( $pricing && 'publish' === $pricing->post_status ) {
		return get_permalink( $pricing );
	}
	return add_query_arg( 'redirect_to', rawurlencode( $target ), wp_registration_url() );
}

function htn_login_url() {
	$dash = get_page_by_path( 'listing-author' );
	$to   = ( $dash && 'publish' === $dash->post_status ) ? get_permalink( $dash ) : home_url( '/' );
	return wp_login_url( $to );
}

function htn_search_url() {
	return add_query_arg(
		array(
			's'         => 'home',
			'post_type' => 'listing',
		),
		home_url( '/' )
	);
}

/* ------------------------------------------------------------------ *
 * Assets + body classes
 * ------------------------------------------------------------------ */

add_action( 'wp_enqueue_scripts', 'htn_enqueue', 9999 );
function htn_enqueue() {
	if ( ! htn_is_active() ) {
		return;
	}
	if ( htn_is_natural() ) {
		wp_enqueue_style( 'htn-fonts', 'https://fonts.googleapis.com/css2?family=Cabin:wght@400;500;600;700&family=Lora:ital,wght@0,400;0,500;0,600;1,400&display=swap', array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters
		wp_enqueue_style( 'htn-colors', HTN_URL . 'assets/css/colors.css', array(), HTN_VERSION );
		wp_enqueue_style( 'htn-skin', HTN_URL . 'assets/css/skin.css', array( 'htn-colors' ), HTN_VERSION );
		wp_enqueue_style( 'htn-neon', HTN_URL . 'assets/css/neon.css', array( 'htn-skin' ), HTN_VERSION );
		wp_enqueue_style( 'htn-natural', HTN_URL . 'assets/css/natural.css', array( 'htn-neon' ), HTN_VERSION );
		wp_enqueue_style( 'htn-natural-pages', HTN_URL . 'assets/css/natural-pages.css', array( 'htn-natural' ), HTN_VERSION );
		if ( is_singular( 'listing' ) ) {
			wp_enqueue_script( 'htn-listing', HTN_URL . 'assets/js/listing.js', array(), HTN_VERSION, true );
			wp_add_inline_script(
				'htn-listing',
				'window.htnListing=' . wp_json_encode(
					array(
						'loggedIn'    => is_user_logged_in(),
						'loginUrl'    => wp_login_url( get_permalink() . '#htn-booking' ),
						'registerUrl' => add_query_arg( 'redirect_to', rawurlencode( get_permalink() . '#htn-booking' ), wp_registration_url() ),
					)
				) . ';',
				'before'
			);
		}
		if ( htn_is_dashboard() ) {
			wp_enqueue_style( 'htn-dashboard', HTN_URL . 'assets/css/dashboard.css', array( 'htn-natural' ), HTN_VERSION );
			wp_enqueue_style( 'htn-natural-dashboard', HTN_URL . 'assets/css/natural-dashboard.css', array( 'htn-dashboard' ), HTN_VERSION );
			wp_enqueue_script( 'htn-dashboard', HTN_URL . 'assets/js/dashboard.js', array(), HTN_VERSION, true );
			wp_add_inline_script( 'htn-dashboard', 'document.body.setAttribute("data-htn-home",' . wp_json_encode( home_url( '/' ) ) . ');', 'before' );
		}
		wp_enqueue_script( 'htn-neon', HTN_URL . 'assets/js/neon.js', array(), HTN_VERSION, true );
		wp_enqueue_script( 'htn-preloader', HTN_URL . 'assets/js/preloader.js', array(), HTN_VERSION, false );
		return;
	}
	wp_enqueue_style( 'htn-fonts', 'https://fonts.googleapis.com/css2?family=Cabin:wght@400;500;600;700&family=Lora:wght@400;500;600&display=swap', array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters
	wp_enqueue_style( 'htn-colors', HTN_URL . 'assets/css/colors.css', array(), HTN_VERSION );
	$skin_deps = array( 'htn-colors' );
	if ( htn_use_dark() ) {
		wp_enqueue_style( 'htn-dark-auto', HTN_URL . 'assets/css/dark-auto.css', array( 'htn-colors' ), HTN_VERSION );
		$skin_deps[] = 'htn-dark-auto';
	}
	wp_enqueue_style( 'htn-skin', HTN_URL . 'assets/css/skin.css', $skin_deps, HTN_VERSION );
	if ( htn_use_dark() ) {
		wp_enqueue_style( 'htn-dark', HTN_URL . 'assets/css/dark.css', array( 'htn-skin' ), HTN_VERSION );
		wp_enqueue_style( 'htn-pages', HTN_URL . 'assets/css/pages.css', array( 'htn-dark' ), HTN_VERSION );
	}
	wp_enqueue_style( 'htn-neon', HTN_URL . 'assets/css/neon.css', array( 'htn-skin' ), HTN_VERSION );
	if ( htn_is_dashboard() ) {
		wp_enqueue_style( 'htn-dashboard', HTN_URL . 'assets/css/dashboard.css', array( 'htn-neon' ), HTN_VERSION );
		wp_enqueue_script( 'htn-dashboard', HTN_URL . 'assets/js/dashboard.js', array(), HTN_VERSION, true );
		wp_add_inline_script( 'htn-dashboard', 'document.body.setAttribute("data-htn-home",' . wp_json_encode( home_url( '/' ) ) . ');', 'before' );
	}
	wp_enqueue_script( 'htn-neon', HTN_URL . 'assets/js/neon.js', array(), HTN_VERSION, true );
	wp_enqueue_script( 'htn-preloader', HTN_URL . 'assets/js/preloader.js', array(), HTN_VERSION, false );
}

add_filter( 'body_class', 'htn_body_class' );
function htn_body_class( $classes ) {
	if ( htn_is_active() ) {
		$classes[] = 'htn';
		$classes[] = htn_is_natural() ? 'htn-natural' : 'htn-neon';
		if ( htn_use_header() ) {
			$classes[] = 'htn-has-header';
		}
		if ( htn_use_home() ) {
			$classes[] = 'htn-home';
		}
		if ( htn_use_footer() ) {
			$classes[] = 'htn-has-footer';
		}
		if ( htn_use_dark() ) {
			$classes[] = 'htn-dark';
		}
		if ( ! empty( $GLOBALS['htn_ev_template'] ) ) {
			$classes[] = 'htn-evtpl';
		}
	}
	return $classes;
}

/* ------------------------------------------------------------------ *
 * Logo + icons (inline SVG)
 * ------------------------------------------------------------------ */

function htn_lotus_svg( $size = 56 ) {
	$id = 'htn-lg-' . wp_rand( 1000, 9999 );
	return '<svg class="htn-lotus" width="' . (int) $size . '" height="' . (int) round( $size * 0.72 ) . '" viewBox="0 0 100 72" fill="none" aria-hidden="true" focusable="false">'
		. '<defs><linearGradient id="' . $id . '" x1="0" y1="0" x2="100" y2="72" gradientUnits="userSpaceOnUse"><stop stop-color="#ff5cc4"/><stop offset="1" stop-color="#e21a93"/></linearGradient></defs>'
		. '<g stroke="url(#' . $id . ')" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">'
		. '<path d="M50 4c-9 10-13 22-13 32 0 11 5 20 13 27 8-7 13-16 13-27 0-10-4-22-13-32z"/>'
		. '<path d="M38 24c-8-5-16-7-22-7-1 13 3 25 11 33 6 6 14 10 23 13"/>'
		. '<path d="M62 24c8-5 16-7 22-7 1 13-3 25-11 33-6 6-14 10-23 13"/>'
		. '<path d="M19 38c-6-1-11 0-16 2 4 10 12 17 22 21 8 3 17 3 25 2"/>'
		. '<path d="M81 38c6-1 11 0 16 2-4 10-12 17-22 21-8 3-17 3-25 2"/>'
		. '<path d="M30 69c13 2 27 2 40 0"/>'
		. '</g></svg>';
}

function htn_natural_lotus_svg( $size = 64 ) {
	$id = 'htn-nl-' . wp_rand( 1000, 9999 );
	return '<svg class="htn-lotus htn-lotus--natural" width="' . (int) $size . '" height="' . (int) round( $size * 0.78 ) . '" viewBox="0 0 100 78" fill="none" aria-hidden="true" focusable="false">'
		. '<defs><linearGradient id="' . $id . '" x1="20" y1="6" x2="80" y2="60" gradientUnits="userSpaceOnUse"><stop stop-color="#e2b45a"/><stop offset="1" stop-color="#b8862c"/></linearGradient></defs>'
		. '<g stroke="url(#' . $id . ')" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">'
		. '<path d="M50 6c-8 9-12 19-12 29 0 10 5 18 12 24 7-6 12-14 12-24 0-10-4-20-12-29z"/>'
		. '<path d="M39 25c-7-4-14-6-20-6 0 12 4 22 11 29 5 5 12 9 20 11"/>'
		. '<path d="M61 25c7-4 14-6 20-6 0 12-4 22-11 29-5 5-12 9-20 11"/>'
		. '<path d="M22 40c-6-1-11 0-16 2 4 9 11 15 20 19 7 3 15 3 24 2"/>'
		. '<path d="M78 40c6-1 11 0 16 2-4 9-11 15-20 19-7 3-15 3-24 2"/>'
		. '</g>'
		. '<path d="M30 68c6 6 14 8 20 8s14-2 20-8c-6-4-14-6-20-6s-14 2-20 6z" fill="#3f7a3a"/>'
		. '<path d="M30 68c12 2 28 2 40 0" stroke="#2f5f2c" stroke-width="1.6" stroke-linecap="round"/>'
		. '</svg>';
}

function htn_natural_logo() {
	return '<a class="htn-logo htn-logo--natural" href="' . esc_url( home_url( '/' ) ) . '" aria-label="' . esc_attr( get_bloginfo( 'name' ) ) . '">'
		. htn_natural_lotus_svg( 64 )
		. '<span class="htn-logo__text"><span class="htn-logo__name">Holistic Therapists<br>Ireland</span><i class="htn-logo__rule"></i></span>'
		. '</a>';
}

function htn_logo() {
	return '<a class="htn-logo" href="' . esc_url( home_url( '/' ) ) . '" aria-label="' . esc_attr( get_bloginfo( 'name' ) ) . '">'
		. htn_lotus_svg( 78 )
		. '<span class="htn-logo__text"><span class="htn-logo__name">Holistic Therapists</span><span class="htn-logo__sub"><i></i>Ireland<i></i></span></span>'
		. '</a>';
}

function htn_icon( $name ) {
	$paths = array(
		'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.6-3.6"/>',
		'pin'    => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
		'shield' => '<path d="M12 3 4.5 6v5.5c0 4.6 3.1 8.2 7.5 9.5 4.4-1.300 7.5-4.900 7.500-9.500V6L12 3z"/><path d="m8.800 12 2.300 2.300 4.200-4.500"/>',
		'leaf'   => '<path d="M5 19c0-8 5-13.500 15-14-.5 10-6 15-14 15"/><path d="M5 19c2.500-5 6-8 10.500-10"/><path d="M3 21l2-2"/>',
		'heart'  => '<path d="M12 20.500S3.500 15.300 3.500 9.300A4.600 4.600 0 0 1 12 6.800a4.600 4.600 0 0 1 8.500 2.500c0 6-8.500 11.200-8.500 11.200z"/>',
		'users'  => '<circle cx="9" cy="8" r="3.300"/><path d="M2.800 19.500c.4-3.400 3-5.500 6.200-5.500s5.800 2.100 6.200 5.500z"/><circle cx="17.200" cy="8.800" r="2.600"/><path d="M17.500 14c2.300.2 3.900 1.900 4.200 4.500h-3"/>',
		'spark'  => '<path d="M12 3c.6 4.800 2.200 7.400 9 9-6.800 1.600-8.400 4.200-9 9-.6-4.800-2.200-7.400-9-9 6.800-1.600 8.400-4.200 9-9z"/>',
		'user'   => '<circle cx="12" cy="8.500" r="3.500"/><path d="M5 20c.5-3.800 3.300-6 7-6s6.500 2.200 7 6"/>',
		'arrow'  => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
		'chev'   => '<path d="m6 9 6 6 6-6"/>',
		'menu'   => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'close'  => '<path d="m6 6 12 12M18 6 6 18"/>',
		'calendar' => '<rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
		'leaf2'    => '<path d="M4 20c0-9 5-14 16-15-.5 10-6 15-16 15z"/><path d="M4 20c3-5 7-8 11-10"/>',
		'lotus'    => '<path d="M12 3.5c-2.5 2.6-3.6 5.4-3.6 8 0 2.8 1.4 5 3.6 6.6 2.2-1.6 3.6-3.8 3.6-6.6 0-2.6-1.1-5.4-3.6-8z"/><path d="M8.8 9.2C6.6 8 4.6 7.6 3 7.6c0 3.4 1.2 6.4 3.2 8.3 1.6 1.5 3.6 2.4 6 3"/><path d="M15.2 9.2c2.2-1.2 4.2-1.6 5.8-1.6 0 3.4-1.2 6.4-3.2 8.3-1.6 1.5-3.6 2.4-6 3"/>',
		'stones'   => '<ellipse cx="12" cy="18" rx="8" ry="2.6"/><ellipse cx="12" cy="13" rx="6" ry="2.3"/><ellipse cx="12" cy="8.5" rx="4" ry="1.9"/>',
		'infinity' => '<path d="M8 12c0-2.2 1.5-3.5 3-3.5 2.2 0 3 2.5 4 3.5 1 1 1.8 2 3 2 1.8 0 3-1.5 3-3.5S19.8 7 18 7c-1.2 0-2 1-3 2-1 1-1.8 3.5-4 3.5-1.5 0-3-1.3-3-3.5z"/>',
		'compass'  => '<circle cx="12" cy="12" r="8.5"/><path d="m15.5 8.5-2 5-5 2 2-5z"/>',
		'yoga'     => '<circle cx="12" cy="5" r="2"/><path d="M12 8v5m0 0-5 4m5-4 5 4M5 13h14"/>',
		'mountain' => '<path d="m3 18 6-9 4 6 2-3 6 6z"/><path d="M3 18h18"/>',
		'grid'     => '<circle cx="6" cy="6" r="1.3"/><circle cx="12" cy="6" r="1.3"/><circle cx="18" cy="6" r="1.3"/><circle cx="6" cy="12" r="1.3"/><circle cx="12" cy="12" r="1.3"/><circle cx="18" cy="12" r="1.3"/><circle cx="6" cy="18" r="1.3"/><circle cx="12" cy="18" r="1.3"/><circle cx="18" cy="18" r="1.3"/>',
		'hands'    => '<path d="M7 11V6.5a1.5 1.5 0 0 1 3 0V11m0-2V5a1.5 1.5 0 0 1 3 0v4m0-1.5V6a1.5 1.5 0 0 1 3 0v7a6 6 0 0 1-6 6h-.5a5.5 5.5 0 0 1-5.5-5.5V12a1.5 1.5 0 0 1 3 0"/>',
		'arrowl'   => '<path d="M19 12H5"/><path d="m11 6-6 6 6 6"/>',
		'chevl'    => '<path d="m15 6-6 6 6 6"/>',
		'chevr'    => '<path d="m9 6 6 6-6 6"/>',
		'heart2'   => '<path d="M12 20.5S3.5 15.3 3.5 9.3A4.6 4.6 0 0 1 12 6.8a4.6 4.6 0 0 1 8.5 2.5c0 6-8.5 11.2-8.5 11.2z"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return '<svg class="htn-ico htn-ico--' . esc_attr( $name ) . '" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
}

/* ------------------------------------------------------------------ *
 * Site icon (favicon)
 * ------------------------------------------------------------------ */

function htn_use_favicon() {
	$s = htn_settings();
	return ! empty( $s['favicon'] ) && 'off' !== $s['mode'];
}

function htn_icon_file( $size ) {
	$size = (int) $size;
	$dir  = htn_is_natural() ? 'assets/img/natural/icon-' : 'assets/img/icon-';
	foreach ( array( 32, 180, 192, 270, 512 ) as $available ) {
		if ( $size <= $available ) {
			return HTN_URL . $dir . $available . '.png?v=' . HTN_VERSION;
		}
	}
	return HTN_URL . $dir . '512.png?v=' . HTN_VERSION;
}

add_filter( 'get_site_icon_url', 'htn_site_icon_url', 20, 2 );
function htn_site_icon_url( $url, $size ) {
	return htn_use_favicon() ? htn_icon_file( $size ) : $url;
}

// Print the icon tags ourselves when no site icon is set in WordPress.
add_action( 'wp_head', 'htn_favicon_tags', 99 );
add_action( 'admin_head', 'htn_favicon_tags', 99 );
add_action( 'login_head', 'htn_favicon_tags', 99 );
function htn_favicon_tags() {
	if ( ! htn_use_favicon() || get_option( 'site_icon' ) ) {
		return;
	}
	echo '<link rel="icon" href="' . esc_url( htn_icon_file( 32 ) ) . '" sizes="32x32">' . "\n";
	echo '<link rel="icon" href="' . esc_url( htn_icon_file( 192 ) ) . '" sizes="192x192">' . "\n";
	echo '<link rel="apple-touch-icon" href="' . esc_url( htn_icon_file( 180 ) ) . '">' . "\n";
}

/* ------------------------------------------------------------------ *
 * Header
 * ------------------------------------------------------------------ */

add_action( 'after_setup_theme', 'htn_register_menu', 20 );
function htn_register_menu() {
	register_nav_menu( 'htn_header', 'HT Neon – menu w nagłówku' );
}

/** Top-level listing categories with listings, most used first. */
function htn_top_categories( $limit = 8 ) {
	$terms = get_terms(
		array(
			'taxonomy'   => 'listing-category',
			'hide_empty' => true,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => 40,
		)
	);
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return array();
	}
	// Skip the generic umbrella category so the dropdown lists actual therapies.
	$out = array();
	foreach ( $terms as $t ) {
		if ( 'therapist' === $t->slug ) {
			continue;
		}
		$out[] = $t;
		if ( count( $out ) >= $limit ) {
			break;
		}
	}
	return $out;
}

function htn_default_nav() {
	$html  = '<ul class="htn-nav__list">';
	$html .= '<li class="' . ( is_front_page() ? 'current-menu-item' : '' ) . '"><a href="' . esc_url( home_url( '/' ) ) . '">Home</a></li>';
	$html .= '<li><a href="' . esc_url( htn_search_url() ) . '">Find a Therapist</a></li>';

	$cats = htn_top_categories( 10 );
	if ( $cats ) {
		$html .= '<li class="menu-item-has-children"><a href="' . esc_url( htn_search_url() ) . '">Categories</a><ul class="sub-menu">';
		foreach ( $cats as $t ) {
			$link = get_term_link( $t );
			if ( is_wp_error( $link ) ) {
				continue;
			}
			$html .= '<li><a href="' . esc_url( $link ) . '">' . esc_html( $t->name ) . '</a></li>';
		}
		$html .= '</ul></li>';
	}

	foreach ( array( 'events' => 'Events' ) as $slug => $label ) {
		$p = get_page_by_path( $slug );
		if ( $p && 'publish' === $p->post_status ) {
			$html .= '<li class="' . ( is_page( $p->ID ) ? 'current-menu-item' : '' ) . '"><a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( $label ) . '</a></li>';
		}
	}
	$blog = (int) get_option( 'page_for_posts' );
	if ( $blog ) {
		$html .= '<li class="' . ( is_home() ? 'current-menu-item' : '' ) . '"><a href="' . esc_url( get_permalink( $blog ) ) . '">Blog</a></li>';
	}
	foreach ( array( 'about' => 'About', 'contact' => 'Contact' ) as $slug => $label ) {
		$p = get_page_by_path( $slug );
		if ( $p && 'publish' === $p->post_status ) {
			$html .= '<li class="' . ( is_page( $p->ID ) ? 'current-menu-item' : '' ) . '"><a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( $label ) . '</a></li>';
		}
	}
	$html .= '</ul>';
	return $html;
}

/*
 * The header is placed right after the opening <body> tag by filtering the page output.
 * The wp_body_open hook is not used: on this site it runs at the end of the page.
 */
function htn_natural_nav() {
	$find   = htn_search_url();
	$events = get_page_by_path( 'events' );
	$blog   = (int) get_option( 'page_for_posts' );
	$html   = '<ul class="htn-nav__list">';
	$html  .= '<li class="menu-item-has-children"><a href="' . esc_url( $find ) . '">Find a Therapist</a><ul class="sub-menu">';
	foreach ( htn_top_categories( 10 ) as $t ) {
		$link = get_term_link( $t );
		if ( ! is_wp_error( $link ) ) {
			$html .= '<li><a href="' . esc_url( $link ) . '">' . esc_html( $t->name ) . '</a></li>';
		}
	}
	$html .= '</ul></li>';
	if ( $events && 'publish' === $events->post_status ) {
		$html .= '<li class="menu-item-has-children ' . ( is_page( $events->ID ) ? 'current-menu-item' : '' ) . '"><a href="' . esc_url( get_permalink( $events ) ) . '">Events</a><ul class="sub-menu"><li><a href="' . esc_url( get_permalink( $events ) ) . '">Upcoming events</a></li><li><a href="' . esc_url( htn_join_url() ) . '">List your event</a></li></ul></li>';
	}
	$html .= '<li class="menu-item-has-children"><a href="' . esc_url( htn_join_url() ) . '">For Practitioners</a><ul class="sub-menu"><li><a href="' . esc_url( home_url( '/pricing-plan/' ) ) . '">Pricing &amp; plans</a></li><li><a href="' . esc_url( htn_join_url() ) . '">List your service</a></li><li><a href="' . esc_url( is_user_logged_in() ? home_url( '/submit-listing/' ) : home_url( '/pricing-plan/' ) ) . '">Add a listing</a></li><li><a href="' . esc_url( home_url( '/submit-your-articles/' ) ) . '">Submit an article</a></li></ul></li>';
	$res = '';
	if ( $blog ) {
		$res .= '<li><a href="' . esc_url( get_permalink( $blog ) ) . '">Blog</a></li>';
	}
	$nl = get_page_by_path( 'newsletter-signup' );
	if ( $nl && 'publish' === $nl->post_status ) {
		$res .= '<li><a href="' . esc_url( get_permalink( $nl ) ) . '">Newsletter</a></li>';
	}
	if ( $res ) {
		$html .= '<li class="menu-item-has-children ' . ( is_home() ? 'current-menu-item' : '' ) . '"><a href="' . esc_url( $blog ? get_permalink( $blog ) : home_url( '/' ) ) . '">Resources</a><ul class="sub-menu">' . $res . '</ul></li>';
	}
	foreach ( array( 'about' => 'About', 'contact' => 'Contact' ) as $slug => $label ) {
		$p = get_page_by_path( $slug );
		if ( $p && 'publish' === $p->post_status ) {
			$html .= '<li class="' . ( is_page( $p->ID ) ? 'current-menu-item' : '' ) . '"><a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( $label ) . '</a></li>';
		}
	}
	return $html . '</ul>';
}

add_action( 'template_redirect', 'htn_start_buffer', 0 );

/* Single listing on phones: use the (responsive) desktop template instead of ListingPro's stripped "app view",
   so bookings, lead form, gallery and reviews are all there and get the Natural styling. */
function htn_listing_desktop_template_on_mobile() {
	if ( htn_is_active() && is_singular( 'listing' ) ) {
		add_filter( 'wp_is_mobile', '__return_false', 99 );
	}
}
add_action( 'template_redirect', 'htn_listing_desktop_template_on_mobile', 1 );

function htn_start_buffer() {
	if ( htn_use_header() && ! is_feed() && ! is_robots() ) {
		ob_start( 'htn_inject_header' );
	}
}

add_action( 'wp_head', 'htn_prepare_listing_schema', 2 );
function htn_prepare_listing_schema() {
	if ( ! htn_is_active() || ! is_singular( 'listing' ) ) {
		return;
	}
	$id  = get_the_ID();
	$get = function ( $key ) use ( $id ) {
		$v = function_exists( 'listing_get_metabox_by_ID' ) ? listing_get_metabox_by_ID( $key, $id ) : '';
		return is_string( $v ) ? trim( wp_strip_all_tags( $v ) ) : $v;
	};
	$desc = has_excerpt( $id ) ? get_the_excerpt( $id ) : wp_trim_words( wp_strip_all_tags( strip_shortcodes( get_post_field( 'post_content', $id ) ) ), 40, '…' );
	$data = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'LocalBusiness',
		'@id'         => get_permalink( $id ) . '#business',
		'name'        => html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ),
		'url'         => get_permalink( $id ),
		'description' => html_entity_decode( $desc, ENT_QUOTES, 'UTF-8' ),
	);
	$img = get_the_post_thumbnail_url( $id, 'large' );
	if ( ! $img && ! empty( $GLOBALS['htn_listing_logo'] ) ) {
		$img = $GLOBALS['htn_listing_logo'];
	}
	if ( $img ) {
		$data['image'] = $img;
	}
	$address = $get( 'gAddress' );
	if ( ! empty( $address ) && is_string( $address ) ) {
		$data['address'] = array(
			'@type'         => 'PostalAddress',
			'streetAddress' => $address,
		);
	}
	$lat = $get( 'latitude' );
	$lng = $get( 'longitude' );
	if ( is_numeric( $lat ) && is_numeric( $lng ) && ( 0.0 !== (float) $lat || 0.0 !== (float) $lng ) ) {
		$data['geo'] = array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => (float) $lat,
			'longitude' => (float) $lng,
		);
	}
	$phone = $get( 'phone' );
	if ( ! empty( $phone ) && is_string( $phone ) ) {
		$data['telephone'] = $phone;
	}
	$site = $get( 'website' );
	if ( ! empty( $site ) && is_string( $site ) && filter_var( $site, FILTER_VALIDATE_URL ) ) {
		$data['sameAs'] = array( $site );
	}
	$locs = get_the_terms( $id, 'location' );
	if ( $locs && ! is_wp_error( $locs ) ) {
		$data['areaServed'] = wp_list_pluck( $locs, 'name' );
	}
	$GLOBALS['htn_listing_schema'] = wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG );
}

add_action( 'wp_head', 'htn_prepare_header', 1 );
function htn_prepare_header() {
	if ( ! htn_use_header() ) {
		return;
	}
	ob_start();
	htn_render_header();
	$GLOBALS['htn_header_html'] = ob_get_clean();
	if ( is_singular( 'listing' ) && htn_is_natural() ) {
		$logo = function_exists( 'listing_get_metabox_by_ID' ) ? listing_get_metabox_by_ID( 'business_logo', get_the_ID() ) : '';
		if ( is_numeric( $logo ) ) {
			$logo = wp_get_attachment_image_url( (int) $logo, 'thumbnail' );
		}
		if ( empty( $logo ) ) {
			// Same fallback as the theme's own mobile view: the default business logo from ListingPro options.
			$opts = isset( $GLOBALS['listingpro_options'] ) ? $GLOBALS['listingpro_options'] : array();
			if ( ! empty( $opts['business_logo_default']['url'] ) ) {
				$logo = $opts['business_logo_default']['url'];
			}
		}
		if ( empty( $logo ) ) {
			$logo = get_the_post_thumbnail_url( get_the_ID(), 'medium' );
		}
		if ( ! empty( $logo ) ) {
			$GLOBALS['htn_listing_logo'] = $logo;
		}
	}
}

function htn_inject_header( $html ) {
	if ( ! empty( $GLOBALS['htn_listing_logo'] ) && false === strpos( $html, 'lp-listing-logo' ) ) {
		$logo_html = '<div class="lp-listing-title htn-listing-logo"><div class="lp-listing-logo"><img src="' . esc_url( $GLOBALS['htn_listing_logo'] ) . '" alt=""></div></div>';
		$html      = preg_replace( '/(<div class="post-meta-left-box[^"]*">)(.*?)(<h1)/s', '$1$2' . str_replace( '$', '\$', $logo_html ) . '$3', $html, 1 );
	}
	// Listings: a visible share bar under the description (ListingPro keeps its share menu in a small dropdown).
	if ( is_singular( 'listing' ) && false === strpos( $html, 'htn-share--listing' ) ) {
		$anchor = strpos( $html, '<div id="submitreview"' );
		if ( false === $anchor ) {
			$anchor = strpos( $html, '<div class="review-form"' );
		}
		if ( false !== $anchor ) {
			$lid  = get_queried_object_id();
			$html = substr_replace( $html, htn_share_bar( get_permalink( $lid ), get_the_title( $lid ), 'Share this practitioner', 'htn-share--listing' ), $anchor, 0 );
		}
	}

	// Footer social icons with no address (href="#"): Instagram, YouTube/Twitter, Tumblr.
	if ( false !== strpos( $html, 'icons8-' ) ) {
		$clean = preg_replace( '#<li>\s*<a href="\#"[^>]*>\s*<img class="icon icons8-[a-z0-9_-]+"[^>]*>\s*</a>\s*</li>#i', '', $html );
		if ( null !== $clean ) {
			$html = $clean;
		}
	}
	// Leftover Wix "Widget Didn't Load" snippets pasted into listing descriptions.
	if ( false !== strpos( $html, 'Widget Didn' ) ) {
		$html = preg_replace( '/(<p[^>]*>\s*)?Widget Didn(?:\'|&#8217;|’)t Load.*?Refresh this page\s*(<\/a>)?(\s*<\/p>)?/su', '', $html );
	}
	// SEO: the home page keeps one H1 (our hero title); H1s printed by the theme or widgets (e.g. "Explore Your city") become H2.
	if ( ! empty( $GLOBALS['htn_home_template'] ) ) {
		$demoted = preg_replace_callback(
			'/<h1\b([^>]*)>(.*?)<\/h1>/is',
			function ( $m ) {
				if ( false !== strpos( $m[1], 'htn-nhero__title' ) || false !== strpos( $m[1], 'htn-hero__title' ) || false !== strpos( $m[1], 'htn-h1-keep' ) ) {
					return $m[0];
				}
				return '<h2' . $m[1] . '>' . $m[2] . '</h2>';
			},
			$html
		);
		if ( null !== $demoted ) {
			$html = $demoted;
		}
	}
	// Pricing page: intro header before the Elementor content, social-media perk per plan, featured plan.
	if ( ! empty( $GLOBALS['htn_pricing'] ) && false === strpos( $html, 'htn-price-hero' ) && preg_match( '/<div[^>]*data-elementor-type="wp-page"/', $html, $pm, PREG_OFFSET_CAPTURE ) ) {
		$html = substr_replace( $html, htn_pricing_intro_html(), $pm[0][1], 0 );
	}
	if ( ! empty( $GLOBALS['htn_pricing'] ) && ! is_user_logged_in() && false === strpos( $html, 'price-view-default' ) && null !== ( $fp = htn_footer_pos( $html ) ) ) {
		$block = ( false === strpos( $html, 'htn-price-hero' ) ? htn_pricing_intro_html() : '' ) . htn_guest_plans_html();
		$html  = substr_replace( $html, $block, $fp, 0 );
	}
	if ( ! empty( $GLOBALS['htn_pricing'] ) && false !== strpos( $html, 'price-view-default' ) && false === strpos( $html, 'htn-perk' ) ) {
		$html = htn_pricing_cards( $html );
	}
	if ( ! empty( $GLOBALS['htn_pricing'] ) && false === strpos( $html, 'htn-pricing-js' ) ) {
		$html = str_ireplace( '</body>', htn_pricing_js() . '</body>', $html );
	}
	// SEO: LocalBusiness schema on single listings, only when nothing else (e.g. Rank Math) printed JSON-LD.
	if ( ! empty( $GLOBALS['htn_listing_schema'] ) && false === stripos( $html, 'application/ld+json' ) ) {
		$script = '<script type="application/ld+json" class="htn-schema">' . $GLOBALS['htn_listing_schema'] . '</script>' . "\n";
		$pos    = stripos( $html, '</head>' );
		if ( false !== $pos ) {
			$html = substr_replace( $html, $script, $pos, 0 );
		}
	}
	if ( empty( $GLOBALS['htn_header_html'] ) || false !== strpos( $html, 'id="htn-header"' ) ) {
		return $html;
	}
	$header = $GLOBALS['htn_header_html'];
	$out    = preg_replace_callback(
		'/<body\b[^>]*>/i',
		function ( $m ) use ( $header ) {
			return $m[0] . "\n" . $header;
		},
		$html,
		1
	);
	return null === $out ? $html : $out;
}

function htn_render_header() {
	$s = htn_settings();
	?>
	<div class="htn-header<?php echo htn_is_natural() ? ' htn-header--natural' : ''; ?>" id="htn-header" role="banner">
		<div class="htn-header__inner">
			<?php echo htn_is_natural() ? htn_natural_logo() : htn_logo(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<button type="button" class="htn-burger" id="htn-burger" aria-expanded="false" aria-controls="htn-nav" aria-label="Menu">
				<?php echo htn_icon( 'menu' ) . htn_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</button>
			<nav class="htn-nav" id="htn-nav" aria-label="Main">
				<?php
				if ( has_nav_menu( 'htn_header' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'htn_header',
							'container'      => false,
							'menu_class'     => 'htn-nav__list',
							'depth'          => 2,
							'fallback_cb'    => false,
						)
					);
				} else {
					echo htn_is_natural() ? htn_natural_nav() : htn_default_nav(); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
				<div class="htn-actions">
					<?php if ( is_user_logged_in() ) : ?>
						<?php $dash = get_page_by_path( 'listing-author' ); ?>
						<?php if ( $dash ) : ?>
							<a class="htn-btn htn-btn--ghost" href="<?php echo esc_url( get_permalink( $dash ) ); ?>"><?php echo htn_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Dashboard</a>
						<?php endif; ?>
						<a class="htn-btn htn-btn--pink" href="<?php echo esc_url( home_url( '/submit-listing/' ) ); ?>">Add a Listing</a>
					<?php else : ?>
						<a class="htn-btn htn-btn--ghost htn-login" href="<?php echo esc_url( htn_login_url() ); ?>"><?php echo htn_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Log In</a>
						<a class="htn-btn htn-btn--pink" href="<?php echo esc_url( htn_join_url() ); ?>"><?php echo esc_html( htn_is_natural() && 'Join as a Therapist' === $s['join_label'] ? 'List Your Service' : $s['join_label'] ); ?></a>
					<?php endif; ?>
				</div>
			</nav>
		</div>
	</div>
	<?php
}

/* ------------------------------------------------------------------ *
 * Home page template
 * ------------------------------------------------------------------ */

add_filter( 'template_include', 'htn_template_include', 999 );
function htn_template_include( $template ) {
	if ( htn_use_home() ) {
		$file = HTN_PATH . ( htn_is_natural() ? 'templates/home-natural.php' : 'templates/home.php' );
		if ( file_exists( $file ) ) {
			$GLOBALS['htn_home_template'] = true;
			return $file;
		}
	}
	// Single blog post: our own article layout (Natural style only).
	$bs = htn_settings();
	if ( htn_is_active() && htn_is_natural() && ! empty( $bs['blog'] ) && is_singular( 'post' ) ) {
		$file = HTN_PATH . 'templates/post-natural.php';
		if ( file_exists( $file ) ) {
			$GLOBALS['htn_home_template'] = true; // keep a single H1 (ours) – also fixes posts that have an H1 in the content
			$GLOBALS['htn_ev_template']   = true; // hide the theme's page title band
			return $file;
		}
	}
	// Single event: our own page (Natural style only).
	if ( htn_use_event_template() ) {
		$file = HTN_PATH . 'templates/event-natural.php';
		if ( file_exists( $file ) ) {
			$GLOBALS['htn_home_template'] = true; // keep a single H1 (ours)
			$GLOBALS['htn_ev_template']   = true; // hide the theme's page title band
			return $file;
		}
	}
	// Events page: our own list instead of the ListingPro calendar (Natural style only).
	$s = htn_settings();
	if ( htn_is_active() && htn_is_natural() && ! empty( $s['events'] ) && is_page( 'events' ) ) {
		$file = HTN_PATH . 'templates/events-natural.php';
		if ( file_exists( $file ) ) {
			$GLOBALS['htn_home_template'] = true; // keep a single H1 (ours)
			$GLOBALS['htn_ev_template']   = true; // hide the theme's page title band
			return $file;
		}
	}
	return $template;
}

/** Category tiles for the home page: label, link and image. */
function htn_home_tiles() {
	$wanted = array(
		'massage'     => array( 'Massage', 'Therapy' ),
		'reiki'       => array( 'Reiki &', 'Energy Healing' ),
		'counselling' => array( 'Counselling', '& Mental Health' ),
		'yoga'        => array( 'Yoga &', 'Movement' ),
		'acupressure' => array( 'Acupressure', '& Bodywork' ),
		'naturopathy' => array( 'Naturopathy', '& Nutrition' ),
	);
	$tiles = array();
	foreach ( $wanted as $slug => $label ) {
		$term = get_term_by( 'slug', $slug, 'listing-category' );
		$link = '';
		if ( $term && ! is_wp_error( $term ) ) {
			$l = get_term_link( $term );
			if ( ! is_wp_error( $l ) ) {
				$link = $l;
			}
		}
		if ( ! $link ) {
			$link = add_query_arg( 'select', rawurlencode( $slug ), htn_search_url() );
		}
		$tiles[] = array(
			'label' => $label,
			'link'  => $link,
			'image' => HTN_URL . 'assets/img/cat-' . $slug . '.jpg',
		);
	}
	return apply_filters( 'htn_home_tiles', $tiles );
}

function htn_stats() {
	$counts   = wp_count_posts( 'listing' );
	$listings = isset( $counts->publish ) ? (int) $counts->publish : 0;
	$cats     = wp_count_terms(
		array(
			'taxonomy'   => 'listing-category',
			'hide_empty' => true,
		)
	);
	$cats = is_wp_error( $cats ) ? 0 : (int) $cats;
	return array(
		'listings'   => $listings,
		'categories' => $cats,
	);
}

/* ------------------------------------------------------------------ *
 * Login / registration page (wp-login.php) in the plugin's style
 * ------------------------------------------------------------------ */

function htn_login_styled() {
	$s = htn_settings();
	return 'off' !== $s['mode'];
}

function htn_login_assets() {
	if ( ! htn_login_styled() ) {
		return;
	}
	$natural = htn_is_natural();
	wp_enqueue_style( 'htn-fonts', $natural ? 'https://fonts.googleapis.com/css2?family=Cabin:wght@400;500;600;700&family=Lora:ital,wght@0,400;0,500;0,600;1,400&display=swap' : 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap', array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	wp_enqueue_style( 'htn-login', HTN_URL . 'assets/css/login.css', array(), HTN_VERSION );
	$hero = $natural ? HTN_URL . 'assets/img/natural/hero.jpg' : '';
	if ( $hero ) {
		wp_add_inline_style( 'htn-login', 'body.login{--htn-login-img:url(' . esc_url( $hero ) . ')}' );
	}
}
add_action( 'login_enqueue_scripts', 'htn_login_assets' );

function htn_login_body_class( $classes ) {
	if ( htn_login_styled() ) {
		$classes[] = 'htn-login';
		$classes[] = htn_is_natural() ? 'htn-natural' : 'htn-neon';
		$action    = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : 'login'; // phpcs:ignore WordPress.Security.NonceVerification
		$classes[] = 'htn-login--' . $action;
	}
	return $classes;
}
add_filter( 'login_body_class', 'htn_login_body_class' );

function htn_login_header_url() {
	return home_url( '/' );
}
add_filter( 'login_headerurl', 'htn_login_header_url' );

function htn_login_header_text() {
	return get_bloginfo( 'name' );
}
add_filter( 'login_headertext', 'htn_login_header_text' );

function htn_login_header_markup() {
	if ( ! htn_login_styled() ) {
		return;
	}
	$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : 'login'; // phpcs:ignore WordPress.Security.NonceVerification
	$titles = array(
		'login'        => array( 'Welcome back', 'Log in to manage your profile, listings and events.' ),
		'register'     => array( 'Create your free account', 'Join Ireland\'s holistic wellbeing directory. It takes a minute — then you can add your listing or event.' ),
		'lostpassword' => array( 'Forgot your password?', 'Enter your username or email and we\'ll send you a reset link.' ),
		'rp'           => array( 'Choose a new password', 'Pick something strong that only you know.' ),
		'resetpass'    => array( 'Choose a new password', 'Pick something strong that only you know.' ),
	);
	$t = isset( $titles[ $action ] ) ? $titles[ $action ] : $titles['login'];
	echo '<div class="htn-login__brand">' . ( htn_is_natural() ? htn_natural_logo() : htn_logo() ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<div class="htn-login__intro"><h2>' . esc_html( $t[0] ) . '</h2><p>' . esc_html( $t[1] ) . '</p></div>';
	if ( 'register' === $action ) {
		echo '<ul class="htn-login__perks"><li>' . htn_icon( 'search' ) . ' Be found by clients searching by therapy and county</li><li>' . htn_icon( 'calendar' ) . ' Promote your workshops, circles and retreats</li><li>' . htn_icon( 'heart2' ) . ' A profile with photos, reviews and booking details</li></ul>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
add_action( 'login_header', 'htn_login_header_markup' );

function htn_login_footer_markup() {
	if ( ! htn_login_styled() ) {
		return;
	}
	$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : 'login'; // phpcs:ignore WordPress.Security.NonceVerification
	if ( 'login' === $action && get_option( 'users_can_register' ) ) {
		echo '<p class="htn-login__switch">New here? <a href="' . esc_url( htn_join_url() ) . '">Create a free account</a></p>';
	} elseif ( 'register' === $action ) {
		echo '<p class="htn-login__switch">Already have an account? <a href="' . esc_url( htn_login_url() ) . '">Log in</a></p>';
	}
}
add_action( 'login_footer', 'htn_login_footer_markup' );

/* After login, regular users go to their dashboard – never to wp-admin. */
function htn_user_dashboard_url() {
	$dash = get_page_by_path( 'listing-author' );
	return ( $dash && 'publish' === $dash->post_status ) ? get_permalink( $dash ) : home_url( '/' );
}

function htn_login_redirect( $redirect_to, $requested, $user ) {
	if ( ! $user || is_wp_error( $user ) || user_can( $user, 'manage_options' ) ) {
		return $redirect_to;
	}
	$to = $requested ? $requested : $redirect_to;
	if ( ! $to || false !== strpos( $to, 'wp-admin' ) || false !== strpos( $to, 'wp-login.php' ) ) {
		$to = htn_user_dashboard_url();
	}
	return $to;
}
add_filter( 'login_redirect', 'htn_login_redirect', 999, 3 );

function htn_block_wp_admin() {
	if ( wp_doing_ajax() || ! is_user_logged_in() || current_user_can( 'edit_pages' ) || current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return;
	}
	$script = isset( $_SERVER['SCRIPT_NAME'] ) ? basename( sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) ) : '';
	if ( in_array( $script, array( 'admin-ajax.php', 'admin-post.php', 'async-upload.php', 'media-upload.php' ), true ) ) {
		return;
	}
	wp_safe_redirect( htn_user_dashboard_url() );
	exit;
}
add_action( 'admin_init', 'htn_block_wp_admin', 1 );
function htn_block_wp_admin_early() {
	if ( is_admin() ) {
		htn_block_wp_admin();
	}
}
add_action( 'init', 'htn_block_wp_admin_early', 0 );

function htn_hide_admin_bar( $show ) {
	if ( is_user_logged_in() && ! current_user_can( 'edit_posts' ) ) {
		return false;
	}
	return $show;
}
add_filter( 'show_admin_bar', 'htn_hide_admin_bar' );

/* Registration with a password: the new user is logged in straight away and sent to redirect_to. */
function htn_register_fields() {
	if ( ! htn_login_styled() ) {
		return;
	}
	?>
	<p class="htn-reg-pass">
		<label for="htn_pass1">Password<br><input type="password" name="htn_pass1" id="htn_pass1" class="input" size="20" autocomplete="new-password" required></label>
	</p>
	<p class="htn-reg-pass">
		<label for="htn_pass2">Confirm password<br><input type="password" name="htn_pass2" id="htn_pass2" class="input" size="20" autocomplete="new-password" required></label>
	</p>
	<p class="htn-reg-note">By creating an account you agree to our <a href="<?php echo esc_url( get_privacy_policy_url() ? get_privacy_policy_url() : home_url( '/privacy-policy/' ) ); ?>">privacy policy</a>.</p>
	<?php
}
add_action( 'register_form', 'htn_register_fields' );

function htn_register_validate( $errors ) {
	if ( ! htn_login_styled() ) {
		return $errors;
	}
	$p1 = isset( $_POST['htn_pass1'] ) ? (string) wp_unslash( $_POST['htn_pass1'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput
	$p2 = isset( $_POST['htn_pass2'] ) ? (string) wp_unslash( $_POST['htn_pass2'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput
	if ( '' === $p1 ) {
		$errors->add( 'htn_pass', '<strong>Error:</strong> Please choose a password.' );
	} elseif ( strlen( $p1 ) < 8 ) {
		$errors->add( 'htn_pass', '<strong>Error:</strong> The password must be at least 8 characters long.' );
	} elseif ( $p1 !== $p2 ) {
		$errors->add( 'htn_pass', '<strong>Error:</strong> The passwords do not match.' );
	}
	return $errors;
}
add_filter( 'registration_errors', 'htn_register_validate', 20 );

/* Security plugins replace registration errors with a generic "Invalid login details." – show the real reason instead. */
function htn_register_keep_errors( $errors ) {
	if ( is_wp_error( $errors ) && $errors->has_errors() ) {
		$GLOBALS['htn_reg_errors'] = $errors->get_error_messages();
	}
	return $errors;
}
add_filter( 'registration_errors', 'htn_register_keep_errors', PHP_INT_MAX );

function htn_register_show_errors( $message ) {
	$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( in_array( $action, array( 'register', 'lostpassword', 'retrievepassword' ), true ) && ! empty( $GLOBALS['htn_reg_errors'] ) ) {
		return implode( '<br>', array_map( 'wp_kses_post', $GLOBALS['htn_reg_errors'] ) );
	}
	if ( in_array( $action, array( 'lostpassword', 'retrievepassword' ), true ) && isset( $GLOBALS['htn_mail_error'] ) ) {
		return '<strong>Error:</strong> The reset e-mail could not be sent (mail delivery problem on the site: ' . esc_html( $GLOBALS['htn_mail_error'] ) . '). Please contact us and we will reset it for you.';
	}
	return $message;
}
add_filter( 'lostpassword_errors', 'htn_register_keep_errors', PHP_INT_MAX );
add_filter( 'login_errors', 'htn_register_show_errors', PHP_INT_MAX );

/* Keep the real reason when the reset / confirmation e-mail cannot be sent. */
function htn_mail_failed( $error ) {
	$GLOBALS['htn_mail_error'] = is_wp_error( $error ) ? $error->get_error_message() : '';
}
add_action( 'wp_mail_failed', 'htn_mail_failed' );

function htn_register_done( $user_id ) {
	if ( ! htn_login_styled() || empty( $_POST['htn_pass1'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$pass = (string) wp_unslash( $_POST['htn_pass1'] ); // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput
	wp_set_password( $pass, $user_id );
	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id, true );
	$to = isset( $_REQUEST['redirect_to'] ) ? wp_unslash( $_REQUEST['redirect_to'] ) : htn_url( htn_settings()['join_url'] ); // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput
	wp_safe_redirect( $to ? $to : home_url( '/' ) );
	exit;
}
add_action( 'user_register', 'htn_register_done', 99 );

/* ------------------------------------------------------------------ *
 * Upcoming events (ListingPro "events" post type)
 * ------------------------------------------------------------------ */

function htn_upcoming_events( $limit = 4 ) {
	if ( ! post_type_exists( 'events' ) ) {
		return array();
	}
	$since = strtotime( '-1 day' );
	$q     = new WP_Query(
		array(
			'post_type'           => 'events',
			'post_status'         => 'publish',
			'posts_per_page'      => (int) $limit,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'meta_key'            => 'event-date', // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'             => 'meta_value_num',
			'order'               => 'ASC',
			'meta_query'          => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				'relation' => 'OR',
				array(
					'key'     => 'event-date',
					'value'   => $since,
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
				array(
					'key'     => 'event-date-e',
					'value'   => $since,
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
			),
		)
	);
	$out = array();
	foreach ( $q->posts as $p ) {
		$start = get_post_meta( $p->ID, 'event-date', true );
		$img   = get_the_post_thumbnail_url( $p->ID, 'medium_large' );
		if ( ! $img ) {
			$img = get_post_meta( $p->ID, 'event-img', true );
		}
		$out[] = array(
			'title' => get_the_title( $p ),
			'link'  => get_permalink( $p ),
			'date'  => is_numeric( $start ) ? (int) $start : 0,
			'time'  => (string) get_post_meta( $p->ID, 'event-time', true ),
			'loc'   => (string) get_post_meta( $p->ID, 'event-loc', true ),
			'img'   => is_string( $img ) ? $img : '',
		);
	}
	return $out;
}

/* ------------------------------------------------------------------ *
 * Single event: data, SEO title and Event schema
 * ------------------------------------------------------------------ */

function htn_event_data( $id ) {
	$tz    = new DateTimeZone( 'Europe/Dublin' );
	$meta  = get_post_meta( $id );
	$get   = function ( $key ) use ( $meta ) {
		return isset( $meta[ $key ][0] ) ? trim( (string) maybe_unserialize( $meta[ $key ][0] ) ) : '';
	};
	$mk    = function ( $ts, $time ) use ( $tz ) {
		if ( ! is_numeric( $ts ) || ! $ts ) {
			return null;
		}
		$d = new DateTime( gmdate( 'Y-m-d', (int) $ts ), $tz );
		if ( $time && false !== strtotime( $time ) ) {
			$t = new DateTime( $time, $tz );
			$d->setTime( (int) $t->format( 'G' ), (int) $t->format( 'i' ) );
		}
		return $d;
	};
	$time  = $get( 'event-time' );
	$etime = $get( 'event-time-e' );
	$start = $mk( $get( 'event-date' ), $time );
	$end   = $mk( $get( 'event-date-e' ), $etime );
	if ( ! $end && $start && $etime ) {
		$end = $mk( $get( 'event-date' ), $etime );
	}
	$place = $get( 'event-loc' );
	$place = is_numeric( $place ) ? '' : $place;

	/* Coordinates and a ticket / info link, whatever the importer called them. */
	$lat    = '';
	$lng    = '';
	$ticket = '';
	$links  = array();
	foreach ( $meta as $k => $v ) {
		$val = isset( $v[0] ) && is_string( $v[0] ) ? trim( $v[0] ) : '';
		if ( '' === $val ) {
			continue;
		}
		if ( '' === $lat && preg_match( '/(^|[-_])lat(itude)?$/i', $k ) && is_numeric( $val ) ) {
			$lat = $val;
		} elseif ( '' === $lng && preg_match( '/(^|[-_])(lng|lon|long|longitude)$/i', $k ) && is_numeric( $val ) ) {
			$lng = $val;
		} elseif ( preg_match( '#^https?://#i', $val ) && ! preg_match( '/\.(jpe?g|png|gif|webp|svg)(\?|$)/i', $val ) && preg_match( '/tic|ticket|link|url|website|source|info/i', $k ) && false === strpos( $val, wp_parse_url( home_url(), PHP_URL_HOST ) ) ) {
			$links[ preg_match( '/tic/i', $k ) ? 0 : count( $links ) + 1 ] = $val;
		}
	}
	if ( $links ) {
		ksort( $links );
		$ticket = reset( $links );
	}

	$img = get_the_post_thumbnail_url( $id, 'large' );
	if ( ! $img ) {
		$img = $get( 'event-img' );
	}
	$where = ( $lat && $lng ) ? $lat . ',' . $lng : $place;

	$time_label = '';
	if ( $start && $time ) {
		$time_label = strtolower( $start->format( 'g:i a' ) );
		if ( $end && $etime ) {
			$time_label .= ' – ' . strtolower( $end->format( 'g:i a' ) );
		}
	}

	$gcal = '';
	if ( $start ) {
		$utc = new DateTimeZone( 'UTC' );
		if ( $time ) {
			$e    = $end && $end > $start ? clone $end : ( clone $start )->modify( '+2 hours' );
			$s1   = ( clone $start )->setTimezone( $utc )->format( 'Ymd\THis\Z' );
			$dates = $s1 . '/' . $e->setTimezone( $utc )->format( 'Ymd\THis\Z' );
		} else {
			$dates = $start->format( 'Ymd' ) . '/' . ( clone $start )->modify( '+1 day' )->format( 'Ymd' );
		}
		$gcal = add_query_arg(
			array(
				'action'   => 'TEMPLATE',
				'text'     => rawurlencode( html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ) ),
				'dates'    => $dates,
				'location' => rawurlencode( $place ),
				'details'  => rawurlencode( get_permalink( $id ) ),
			),
			'https://calendar.google.com/calendar/render'
		);
	}

	$last = $end ? $end : ( $start ? ( clone $start )->setTime( 23, 59 ) : null );

	return array(
		'title'      => html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ),
		'url'        => get_permalink( $id ),
		'start'      => $start,
		'end'        => $end,
		'has_time'   => (bool) $time,
		'time_label' => $time_label,
		'place'      => $place,
		'lat'        => $lat,
		'lng'        => $lng,
		'ticket'     => $ticket,
		'img'        => is_string( $img ) ? $img : '',
		'directions' => $where ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $where ) : '',
		'map'        => $where ? 'https://maps.google.com/maps?q=' . rawurlencode( $where ) . '&z=15&output=embed' : '',
		'gcal'       => $gcal,
		'past'       => $last && $last->getTimestamp() < time(),
	);
}

/* Listing categories mentioned in a post (title or content), for "Find a practitioner" links. */
function htn_post_therapy_links( $id, $max = 4 ) {
	$terms = get_terms(
		array(
			'taxonomy'   => 'listing-category',
			'hide_empty' => true,
		)
	);
	if ( is_wp_error( $terms ) || ! $terms ) {
		return array();
	}
	$skip = array( 'therapist', 'health', 'shops', 'freelance', 'gym', 'spa', 'beauty-spa', 'wellness-centers' );
	$hay  = ' ' . wp_strip_all_tags( get_the_title( $id ) . ' ' . get_post_field( 'post_content', $id ) ) . ' ';
	$out  = array();
	foreach ( $terms as $t ) {
		if ( in_array( $t->slug, $skip, true ) ) {
			continue;
		}
		$name = html_entity_decode( $t->name, ENT_QUOTES, 'UTF-8' );
		if ( preg_match( '/\b' . preg_quote( $name, '/' ) . '/iu', $hay ) ) {
			$link = get_term_link( $t );
			if ( ! is_wp_error( $link ) ) {
				$out[ $t->count * 1000 + count( $out ) ] = array( 'name' => $name . ' practitioners', 'url' => $link );
			}
		}
	}
	krsort( $out );
	return array_slice( array_values( $out ), 0, $max );
}

function htn_related_posts( $id, $limit = 3 ) {
	$cats = array_diff( wp_get_post_categories( $id ), array( (int) get_option( 'default_category' ) ) );
	$args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $limit,
		'post__not_in'        => array( $id ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'meta_key'            => '_thumbnail_id', // phpcs:ignore WordPress.DB.SlowDBQuery
	);
	if ( $cats ) {
		$args['category__in'] = array_values( $cats );
	}
	$posts = get_posts( $args );
	if ( count( $posts ) < $limit ) {
		unset( $args['category__in'] );
		$args['post__not_in']   = array_merge( array( $id ), wp_list_pluck( $posts, 'ID' ) );
		$args['posts_per_page'] = $limit - count( $posts );
		$posts                  = array_merge( $posts, get_posts( $args ) );
	}
	return $posts;
}

/* Pricing page (ListingPro "pricing-plans" widget): our intro header, one H1, no dark title band. */
add_action( 'template_redirect', 'htn_pricing_flags' );
function htn_pricing_flags() {
	if ( htn_is_active() && htn_is_natural() && is_page( 'pricing-plan' ) ) {
		$GLOBALS['htn_home_template'] = true;
		$GLOBALS['htn_ev_template']   = true;
		$GLOBALS['htn_pricing']       = true;
	}
}

/*
 * ListingPro sends signed-out visitors from /pricing-plan/ to the home page, so practitioners
 * and Google never see the prices. Cancel only that redirect; choosing a plan still asks for login.
 */
add_filter( 'wp_redirect', 'htn_pricing_keep_public', 1, 2 );
function htn_pricing_keep_public( $location, $status ) {
	if ( is_user_logged_in() || is_admin() || wp_doing_ajax() || 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		return $location;
	}
	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
	if ( 'pricing-plan' !== $path || ! htn_is_active() ) {
		return $location;
	}
	$to = trim( (string) wp_parse_url( $location, PHP_URL_PATH ), '/' );
	return '' === $to ? false : $location;
}

/*
 * Guest view of the plans. ListingPro prints no plans for signed-out visitors, so HT Neon builds
 * the cards from the published price plans, with the same markup the styles above expect.
 */
function htn_footer_pos( $html ) {
	$pos = array();
	foreach ( array( '<footer', '<div class="htn-footer"' ) as $needle ) {
		$p = strpos( $html, $needle );
		if ( false !== $p ) {
			$pos[] = $p;
		}
	}
	return $pos ? min( $pos ) : null;
}

function htn_guest_plan_rows() {
	return array(
		'map_show'                   => 'Map Display',
		'contact_show'               => 'Contact Display',
		'gallery_show'               => 'Image Gallery',
		'video_show'                 => 'Video',
		'listingproc_tagline'        => 'Business Tagline',
		'listingproc_social'         => 'Social Media Links',
		'listingproc_faq'            => 'FAQs',
		'listingproc_bhours'         => 'Business Hours',
		'lp_eventsplan'              => 'Events',
		'listingproc_bookings'       => 'Bookings',
		'listingproc_leadform'       => 'Lead Form',
		'listingproc_plan_deals'     => 'Deals & Offers',
		'lp_hidegooglead'            => 'No Competitor Ads',
	);
}

function htn_guest_plan_card( $p ) {
	$m      = function ( $k ) use ( $p ) {
		return (string) get_post_meta( $p->ID, $k, true );
	};
	$price  = (float) $m( 'plan_price' );
	$days   = (int) $m( 'plan_time' );
	$yearly = 'yearly' === $m( 'plan_duration_type' );
	$pack   = 'Package' === $m( 'plan_package_type' );
	$per    = $pack ? 'Per Package' : 'Per Listing';
	$fmt    = function ( $n ) {
		return '€' . ( floor( $n ) == $n ? number_format( $n, 0 ) : number_format( $n, 2 ) );
	};
	if ( $price <= 0 ) {
		$head = '<p>Free</p><span class="package-type">' . esc_html( $per ) . '</span>';
	} elseif ( $yearly ) {
		$head = '<p>' . esc_html( $fmt( round( $price / 12, 2 ) ) ) . ' <small>Per Month</small></p><span class="package-type">' . esc_html( $fmt( $price ) . ' billed annually ' . strtolower( $per ) ) . '</span>';
	} else {
		$head = '<p>' . esc_html( $fmt( $price ) ) . '</p><span class="package-type">' . esc_html( $per ) . '</span>';
	}
	$yes  = '<span class="icon-text"><img class="icons8-Checked" src="data:image/gif;base64,R0lGODlhAQABAAAAACw=" alt=""></span>';
	$no   = '<span class="icon-text"><img class="icons8-Cancel" src="data:image/gif;base64,R0lGODlhAQABAAAAACw=" alt=""></span>';
	$rows = '<li>' . $yes . '<span>Duration : ' . (int) $days . ' days</span></li>';
	if ( $pack ) {
		$max   = (int) $m( 'plan_text' );
		$rows .= '<li>' . $yes . '<span>Max. Listings : ' . ( $max ? $max : 'unlimited' ) . '</span></li>';
	}
	foreach ( htn_guest_plan_rows() as $key => $label ) {
		$on = 'true' === $m( $key );
		if ( 'gallery_show' === $key && $on && 1 === (int) $m( 'plan_no_of_img' ) ) {
			$label = '1 Photo';
		}
		$rows .= '<li>' . ( $on ? $yes : $no ) . '<span>' . esc_html( $label ) . '</span></li>';
	}
	$opts = get_post_meta( $p->ID, 'lp_listingpro_options', true );
	if ( is_array( $opts ) && ! empty( $opts['lp_price_plan_addmore'] ) && is_array( $opts['lp_price_plan_addmore'] ) ) {
		foreach ( $opts['lp_price_plan_addmore'] as $extra ) {
			if ( '' !== trim( (string) $extra ) ) {
				$rows .= '<li>' . $yes . '<span>' . esc_html( $extra ) . '</span></li>';
			}
		}
	}
	$register = add_query_arg(
		array(
			'action'      => 'register',
			'redirect_to' => rawurlencode( home_url( '/pricing-plan/' ) ),
		),
		wp_login_url()
	);
	return '<div class="col-md-4 price-view-default ' . (int) $p->ID . '"><div class="lp-price-main">'
		. '<div class="lp-title"><div class="lp-plane-top-wrape"><a>' . esc_html( get_the_title( $p ) ) . '</a>' . $head . '</div></div>'
		. '<div class="lp-price-list"><ul class="lp-listprc">' . $rows . '</ul>'
		. '<form class="price-plan-button" method="get" action="' . esc_url( $register ) . '" onsubmit="location.href=this.action;return false;"><input type="submit" value="Get started"></form>'
		. '</div></div></div>';
}

function htn_guest_plans_html() {
	$plans = get_posts(
		array(
			'post_type'   => 'price_plan',
			'post_status' => 'publish',
			'numberposts' => 20,
		)
	);
	$sets  = array( 'monthly' => '', 'yearly' => '' );
	foreach ( $plans as $p ) {
		$opts = get_post_meta( $p->ID, 'lp_listingpro_options', true );
		if ( is_array( $opts ) && ! empty( $opts['plan_for'] ) && 'listingonly' !== $opts['plan_for'] ) {
			continue;
		}
		$type          = 'yearly' === get_post_meta( $p->ID, 'plan_duration_type', true ) ? 'yearly' : 'monthly';
		$sets[ $type ] .= htn_guest_plan_card( $p );
	}
	if ( '' === $sets['monthly'] && '' === $sets['yearly'] ) {
		return '';
	}
	$login  = wp_login_url( home_url( '/pricing-plan/' ) );
	$toggle = '' !== $sets['yearly'] && '' !== $sets['monthly']
		? '<div class="htn-gtoggle" role="tablist"><button type="button" class="is-on" data-set="monthly">Monthly</button><button type="button" data-set="yearly">Annually <em>2 months free</em></button></div>'
		: '';
	return '<section class="htn-guest-plans elementor-widget-pricing-plans"><div class="col-md-10 col-md-offset-1">' . $toggle
		. '<div class="page-inner-container htn-gset" data-set="monthly">' . $sets['monthly'] . '</div>'
		. ( '' !== $sets['yearly'] ? '<div class="page-inner-container htn-gset" data-set="yearly" hidden>' . $sets['yearly'] . '</div>' : '' )
		. '<p class="htn-guest-plans__login">Already listed? <a href="' . esc_url( $login ) . '">Log in to manage your listing</a></p>'
		. '</div></section>'
		. '<script>(function(){var t=document.querySelector(".htn-gtoggle");if(!t)return;t.addEventListener("click",function(e){var b=e.target.closest("button");if(!b)return;t.querySelectorAll("button").forEach(function(x){x.classList.toggle("is-on",x===b);});document.querySelectorAll(".htn-gset").forEach(function(s){s.hidden=s.getAttribute("data-set")!==b.getAttribute("data-set");});});})();</script>';
}

/* Signed-out visitors: render none of the pricing page's Elementor content (ListingPro stops the page there). */
add_filter( 'elementor/frontend/builder_content_data', 'htn_pricing_guest_no_elementor', 10, 2 );
function htn_pricing_guest_no_elementor( $data, $post_id ) {
	if ( ! is_user_logged_in() && ! empty( $GLOBALS['htn_pricing'] ) && (int) $post_id === (int) get_queried_object_id() ) {
		return array();
	}
	return $data;
}

/* Signed-out visitors: skip ListingPro's pricing widget (it redirects and stops the page); HT Neon prints the guest plans instead. */
add_filter( 'elementor/frontend/widget/should_render', 'htn_pricing_skip_lp_widget', 10, 2 );
function htn_pricing_skip_lp_widget( $should, $widget ) {
	if ( $should && ! is_user_logged_in() && ! empty( $GLOBALS['htn_pricing'] ) && is_object( $widget ) && method_exists( $widget, 'get_name' ) && false !== strpos( $widget->get_name(), 'pricing' ) ) {
		return false;
	}
	return $should;
}

function htn_pricing_intro_html() {
	$items = array(
		'Get found by clients searching for therapists across Ireland',
		'Your own profile page with photos, contact details and map',
		'Promote workshops and retreats on our Events page',
	);
	$li = '';
	foreach ( $items as $it ) {
		$li .= '<li>' . htn_icon( 'leaf' ) . ' ' . esc_html( $it ) . '</li>';
	}
	return '<header class="htn-price-hero"><div class="htn-wrap">'
		. '<p class="htn-nkicker">For practitioners</p>'
		. '<h1 class="htn-price-hero__title htn-h1-keep">List your practice on <span>Holistic Therapists Ireland</span></h1>'
		. '<p class="htn-price-hero__lead">Choose a plan that suits you. Simple monthly pricing, no long contracts – cancel any time.</p>'
		. '<ul class="htn-price-hero__list">' . $li . '</ul>'
		. '</div></header>';
}

/* Plan perks shown as an extra first row on each pricing card, matched by plan name. */
function htn_plan_perks() {
	return array(
		'free'     => 'Welcome post on our Facebook &amp; LinkedIn',
		'standard' => 'Your events shared on our Facebook &amp; LinkedIn',
		'premium'  => 'Monthly featured post on Facebook, LinkedIn &amp; Instagram',
	);
}

/* Plans loaded by the Monthly/Annually switch (AJAX) get the same perk rows, featured card and price order. */
function htn_pricing_js() {
	$cfg = array(
		'perks' => htn_plan_perks(),
		'icon'  => htn_icon( 'spark' ),
	);
	return '<script id="htn-pricing-js">(function(){var C=' . wp_json_encode( $cfg ) . ';'
		. 'function nm(c){var a=c.querySelector(".lp-plane-top-wrape a");return a?a.textContent.trim().toLowerCase():"";}'
		. 'function pr(c){var t=c.querySelector(".lp-plane-top-wrape");t=t?t.textContent:"";var m=t.match(/€\s*([\d.,]+)\s*billed/i)||t.match(/€\s*([\d.,]+)/);return m?parseFloat(m[1].replace(",","")):0;}'
		. 'function run(){var bx=document.querySelectorAll(".elementor-widget-pricing-plans .lp_plan_result_section,.elementor-widget-pricing-plans .page-inner-container");'
		. 'bx.forEach(function(b){var cs=[].slice.call(b.querySelectorAll(":scope > .price-view-default"));if(!cs.length)return;'
		. 'var names=cs.map(nm),fk="";["standard","premium"].forEach(function(w){if(!fk&&names.some(function(n){return n.indexOf(w)>-1;}))fk=w;});'
		. 'cs.forEach(function(c,i){var n=names[i];if(!c.querySelector(".htn-perk")){for(var k in C.perks){if(n.indexOf(k)>-1){var ul=c.querySelector("ul.lp-listprc");if(ul){var li=document.createElement("li");li.className="htn-perk";li.innerHTML="<span class=\"htn-perk__ico\">"+C.icon+"</span><span>"+C.perks[k]+"</span>";ul.insertBefore(li,ul.firstChild);}break;}}}'
		. 'if(fk&&n.indexOf(fk)>-1)c.classList.add("htn-plan-featured");});'
		. 'var s=cs.slice().sort(function(x,y){return pr(x)-pr(y);});if(s.some(function(c,i){return c!==cs[i];}))s.forEach(function(c){b.appendChild(c);});});}'
		. 'run();new MutationObserver(function(){clearTimeout(window.__htnP);window.__htnP=setTimeout(run,60);}).observe(document.body,{childList:true,subtree:true});})();</script>';
}

function htn_pricing_cards( $html ) {
	$parts = preg_split( '/(?=<div class="[^"]*\bprice-view-default\b)/', $html );
	if ( count( $parts ) < 2 ) {
		return $html;
	}
	$names = array();
	foreach ( $parts as $i => $part ) {
		if ( 0 === $i ) {
			continue;
		}
		$names[ $i ] = preg_match( '/lp-plane-top-wrape[^>]*>\s*<a[^>]*>([^<]+)</', $part, $m ) ? strtolower( trim( $m[1] ) ) : '';
	}
	/* Featured: every Standard card (monthly and annual); without one, every Premium card. */
	$featured_key = '';
	foreach ( array( 'standard', 'premium' ) as $want ) {
		foreach ( $names as $n ) {
			if ( ! $featured_key && false !== strpos( $n, $want ) ) {
				$featured_key = $want;
			}
		}
	}
	foreach ( $names as $i => $n ) {
		$perk = '';
		foreach ( htn_plan_perks() as $key => $text ) {
			if ( false !== strpos( $n, $key ) ) {
				$perk = $text;
				break;
			}
		}
		if ( $perk ) {
			$li          = '<li class="htn-perk"><span class="htn-perk__ico">' . htn_icon( 'spark' ) . '</span><span>' . $perk . '</span></li>';
			$parts[ $i ] = preg_replace( '/(<ul class="lp-listprc"[^>]*>)/', '$1' . $li, $parts[ $i ], 1 );
		}
		if ( $featured_key && false !== strpos( $n, $featured_key ) ) {
			$parts[ $i ] = preg_replace( '/^<div class="/', '<div class="htn-plan-featured ', $parts[ $i ], 1 );
		}
	}
	return implode( '', $parts );
}

function htn_use_event_template() {
	$s = htn_settings();
	return htn_is_active() && htn_is_natural() && ! empty( $s['events'] ) && is_singular( 'events' );
}

/* Short SEO title for events: "Title · 27 Nov" (no site name), unless Rank Math has a custom title. */
add_filter( 'rank_math/frontend/title', 'htn_event_seo_title', 20 );
function htn_event_seo_title( $title ) {
	if ( ! is_singular( 'events' ) || ! htn_is_active() ) {
		return $title;
	}
	$id = get_queried_object_id();
	if ( get_post_meta( $id, 'rank_math_title', true ) ) {
		return $title;
	}
	$ev   = htn_event_data( $id );
	$name = $ev['title'];
	if ( $ev['start'] ) {
		$with = $name . ' · ' . $ev['start']->format( 'j M' );
		if ( function_exists( 'mb_strlen' ) ? mb_strlen( $with ) <= 60 : strlen( $with ) <= 60 ) {
			return $with;
		}
	}
	return $name;
}

add_action( 'wp_head', 'htn_prepare_event_schema', 2 );
function htn_prepare_event_schema() {
	if ( ! htn_is_active() || ! is_singular( 'events' ) ) {
		return;
	}
	$id = get_queried_object_id();
	$ev = htn_event_data( $id );
	if ( ! $ev['start'] ) {
		return;
	}
	$desc = has_excerpt( $id ) ? get_the_excerpt( $id ) : wp_trim_words( wp_strip_all_tags( strip_shortcodes( get_post_field( 'post_content', $id ) ) ), 40, '…' );
	$data = array(
		'@context'            => 'https://schema.org',
		'@type'               => 'Event',
		'name'                => $ev['title'],
		'url'                 => $ev['url'],
		'description'         => html_entity_decode( $desc, ENT_QUOTES, 'UTF-8' ),
		'startDate'           => $ev['has_time'] ? $ev['start']->format( 'c' ) : $ev['start']->format( 'Y-m-d' ),
		'eventStatus'         => 'https://schema.org/EventScheduled',
		'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
	);
	if ( $ev['end'] ) {
		$data['endDate'] = $ev['has_time'] ? $ev['end']->format( 'c' ) : $ev['end']->format( 'Y-m-d' );
	}
	if ( $ev['place'] ) {
		$parts            = array_map( 'trim', explode( ',', $ev['place'] ) );
		$data['location'] = array(
			'@type'   => 'Place',
			'name'    => $parts[0],
			'address' => $ev['place'],
		);
		if ( $ev['lat'] && $ev['lng'] ) {
			$data['location']['geo'] = array(
				'@type'     => 'GeoCoordinates',
				'latitude'  => (float) $ev['lat'],
				'longitude' => (float) $ev['lng'],
			);
		}
	}
	if ( $ev['img'] ) {
		$data['image'] = array( $ev['img'] );
	}
	$GLOBALS['htn_listing_schema'] = wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG );
}

/* ------------------------------------------------------------------ *
 * Footer
 * ------------------------------------------------------------------ */

function htn_theme_option( $key ) {
	if ( function_exists( 'lp_theme_option' ) ) {
		$v = lp_theme_option( $key );
		return is_string( $v ) ? trim( $v ) : '';
	}
	return '';
}

function htn_page_link( $slug, $label ) {
	$p = get_page_by_path( $slug );
	if ( ! $p || 'publish' !== $p->post_status ) {
		return '';
	}
	return '<li><a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( $label ) . '</a></li>';
}

add_action( 'get_footer', 'htn_render_footer', 20 );
function htn_render_footer() {
	static $done = false;
	if ( $done || ! htn_use_footer() ) {
		return;
	}
	$done = true;
	$s    = htn_settings();

	$social = array(
		'fb'           => 'Facebook',
		'tw'           => 'X',
		'insta'        => 'Instagram',
		'f-yout'       => 'YouTube',
		'f-linked'     => 'LinkedIn',
		'f-pintereset' => 'Pinterest',
		'tumb'         => 'Tumblr',
	);
	$blog   = (int) get_option( 'page_for_posts' );
	$addr   = htn_theme_option( 'footer_address' );
	$phone  = htn_theme_option( 'phone_number' );
	?>
	<div class="htn-footer" role="contentinfo">
		<div class="htn-footer__inner">
			<div class="htn-footer__brand">
				<?php echo htn_is_natural() ? htn_natural_logo() : htn_logo(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<p>Ireland's holistic wellness directory. Find therapists, practitioners and wellbeing events near you.</p>
				<ul class="htn-footer__social">
					<?php foreach ( $social as $key => $label ) : ?>
						<?php $url = htn_theme_option( $key ); ?>
						<?php if ( $url && '#' !== $url ) : ?>
							<li><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $label ); ?></a></li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="htn-footer__col">
				<h2>Explore</h2>
				<ul>
					<li><a href="<?php echo esc_url( htn_search_url() ); ?>">Find a Therapist</a></li>
					<?php echo htn_page_link( 'events', 'Events' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php if ( $blog ) : ?>
						<li><a href="<?php echo esc_url( get_permalink( $blog ) ); ?>">Blog</a></li>
					<?php endif; ?>
					<?php echo htn_page_link( 'newsletter-signup', 'Newsletter' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php echo htn_page_link( 'contact', 'Contact' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</ul>
			</div>
			<div class="htn-footer__col">
				<h2>Popular Therapies</h2>
				<ul>
					<?php foreach ( htn_top_categories( 5 ) as $t ) : ?>
						<?php $link = get_term_link( $t ); ?>
						<?php if ( ! is_wp_error( $link ) ) : ?>
							<li><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $t->name ); ?></a></li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="htn-footer__col">
				<h2>For Therapists</h2>
				<ul>
					<?php echo htn_page_link( 'pricing-plan', 'Pricing & Plans' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<li><a href="<?php echo esc_url( htn_join_url() ); ?>"><?php echo esc_html( $s['join_label'] ); ?></a></li>
					<?php if ( is_user_logged_in() ) { echo htn_page_link( 'submit-listing', 'Add a Listing' ); } // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php echo htn_page_link( 'submit-your-articles', 'Submit an Article' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php echo htn_page_link( 'affiliate-area', 'Affiliate Area' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</ul>
			</div>
		</div>
		<div class="htn-footer__bottom">
			<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?><?php echo $addr ? ' &middot; ' . esc_html( wp_strip_all_tags( $addr ) ) : ''; ?><?php echo $phone ? ' &middot; ' . esc_html( wp_strip_all_tags( $phone ) ) : ''; ?></p>
			<ul>
				<?php echo htn_page_link( 'privacy-policy', 'Privacy Policy' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</ul>
		</div>
	</div>
	<?php
}

/* ------------------------------------------------------------------ *
 * Registration prompts on inner pages
 * ------------------------------------------------------------------ */

function htn_show_cta() {
	if ( ! htn_is_active() || htn_is_dashboard() || htn_is_map_archive() ) {
		return false;
	}
	if ( is_user_logged_in() && ! current_user_can( 'manage_options' ) ) {
		return false;
	}
	if ( is_page( array( 'submit-listing', 'pricing-plan', 'payment-checkout', 'payment-success', 'payment-fail', 'edit-listing', 'listing-author' ) ) ) {
		return false;
	}
	return true;
}

add_action( 'get_footer', 'htn_cta_band' );
function htn_cta_band() {
	static $done = false;
	$s = htn_settings();
	if ( $done || empty( $s['cta_band'] ) || ! htn_show_cta() || htn_use_home() ) {
		return;
	}
	$done = true;
	?>
	<section class="htn-band" aria-label="Join as a therapist">
		<div class="htn-band__inner">
			<div class="htn-band__copy">
				<h2 class="htn-band__title">Are You a Holistic Therapist?</h2>
				<p class="htn-band__text">Join Ireland's growing wellness community, showcase your services and reach new clients.</p>
			</div>
			<a class="htn-btn htn-btn--pink htn-btn--lg" href="<?php echo esc_url( htn_join_url() ); ?>">Register Your Practice Today <?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
		</div>
	</section>
	<?php
}

add_action( 'wp_footer', 'htn_cta_bar', 50 );
function htn_cta_bar() {
	$s = htn_settings();
	if ( empty( $s['cta_bar'] ) || ! htn_show_cta() ) {
		return;
	}
	?>
	<div class="htn-bar" id="htn-bar" hidden>
		<p class="htn-bar__text">Therapist or practitioner? Get listed and reach new clients.</p>
		<a class="htn-btn htn-btn--pink" href="<?php echo esc_url( htn_join_url() ); ?>"><?php echo esc_html( htn_is_natural() && 'Join as a Therapist' === $s['join_label'] ? 'List Your Service' : $s['join_label'] ); ?></a>
		<button type="button" class="htn-bar__close" id="htn-bar-close" aria-label="Close"><?php echo htn_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
	</div>
	<?php
}

/* ------------------------------------------------------------------ *
 * Admin
 * ------------------------------------------------------------------ */

add_action( 'admin_menu', 'htn_admin_menu' );
function htn_admin_menu() {
	add_theme_page( 'HT Neon', 'HT Neon', 'manage_options', 'ht-neon', 'htn_render_page' );
}

add_action( 'admin_init', 'htn_register' );
function htn_register() {
	register_setting( 'htn_group', HTN_OPTION, array( 'sanitize_callback' => 'htn_sanitize' ) );
}

function htn_sanitize( $in ) {
	$d   = htn_defaults();
	$out = $d;
	if ( ! is_array( $in ) ) {
		return $out;
	}
	if ( isset( $in['mode'] ) && in_array( $in['mode'], array( 'off', 'preview', 'live' ), true ) ) {
		$out['mode'] = $in['mode'];
	}
	if ( isset( $in['theme'] ) && in_array( $in['theme'], array( 'neon', 'natural' ), true ) ) {
		$out['theme'] = $in['theme'];
	}
	foreach ( array( 'home', 'header', 'footer', 'dark', 'events', 'favicon', 'cta_band', 'cta_bar', 'stats' ) as $k ) {
		$out[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
	}
	if ( isset( $in['join_label'] ) ) {
		$v                 = sanitize_text_field( $in['join_label'] );
		$out['join_label'] = '' !== $v ? $v : $d['join_label'];
	}
	if ( isset( $in['join_url'] ) ) {
		$u               = trim( (string) $in['join_url'] );
		$u               = ( 0 === strpos( $u, '/' ) ) ? '/' . ltrim( sanitize_text_field( $u ), '/' ) : esc_url_raw( $u );
		$out['join_url'] = '' !== $u ? $u : $d['join_url'];
	}
	$out['hero_image'] = isset( $in['hero_image'] ) ? esc_url_raw( trim( (string) $in['hero_image'] ) ) : '';
	return $out;
}

function htn_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s = htn_settings();
	$d = htn_defaults();
	$o = HTN_OPTION;
	?>
	<div class="wrap">
		<h1>HT Neon – wygląd strony</h1>
		<p>Wtyczka nakłada nowy wygląd na motyw ListingPro. Niczego nie zmienia w samym motywie, wizytówkach ani treści stron — po wyłączeniu wszystko wraca do poprzedniego stanu.</p>
		<?php if ( ! htn_is_listingpro() ) : ?>
			<div class="notice notice-warning"><p>Aktywny motyw to nie ListingPro, więc wtyczka nic nie zmienia.</p></div>
		<?php endif; ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'htn_group' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Tryb</th>
					<td>
						<fieldset>
							<label><input type="radio" name="<?php echo esc_attr( $o ); ?>[mode]" value="preview" <?php checked( $s['mode'], 'preview' ); ?>> <strong>Podgląd</strong> — nowy wygląd widzą tylko zalogowani administratorzy</label><br>
							<label><input type="radio" name="<?php echo esc_attr( $o ); ?>[mode]" value="live" <?php checked( $s['mode'], 'live' ); ?>> <strong>Włączony</strong> — nowy wygląd widzą wszyscy odwiedzający</label><br>
							<label><input type="radio" name="<?php echo esc_attr( $o ); ?>[mode]" value="off" <?php checked( $s['mode'], 'off' ); ?>> <strong>Wyłączony</strong> — stary wygląd dla wszystkich</label>
						</fieldset>
						<p class="description">W trybie podglądu nowy wygląd działa też pod adresem: <a href="<?php echo esc_url( add_query_arg( 'ht_preview', '1', home_url( '/' ) ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( add_query_arg( 'ht_preview', '1', home_url( '/' ) ) ); ?></a></p>
					</td>
				</tr>
				<tr>
					<th scope="row">Styl</th>
					<td>
						<fieldset>
							<label><input type="radio" name="<?php echo esc_attr( $o ); ?>[theme]" value="natural" <?php checked( $s['theme'], 'natural' ); ?>> <strong>Natural</strong> — jasny, zieleń i złoto, zdjęcia Irlandii</label><br>
							<label><input type="radio" name="<?php echo esc_attr( $o ); ?>[theme]" value="neon" <?php checked( $s['theme'], 'neon' ); ?>> <strong>Neon</strong> — ciemny fiolet z różowym akcentem</label>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row">Elementy</th>
					<td>
						<label><input type="checkbox" name="<?php echo esc_attr( $o ); ?>[header]" value="1" <?php checked( ! empty( $s['header'] ) ); ?>> Nowy nagłówek z logo (zastępuje nagłówek motywu)</label><br>
						<label><input type="checkbox" name="<?php echo esc_attr( $o ); ?>[home]" value="1" <?php checked( ! empty( $s['home'] ) ); ?>> Nowa strona główna (obecna strona z Elementora zostaje nietknięta i wraca po odznaczeniu)</label><br>
						<label><input type="checkbox" name="<?php echo esc_attr( $o ); ?>[footer]" value="1" <?php checked( ! empty( $s['footer'] ) ); ?>> Nowa stopka z logo i linkami (zastępuje stopkę motywu)</label><br>
						<label><input type="checkbox" name="<?php echo esc_attr( $o ); ?>[dark]" value="1" <?php checked( ! empty( $s['dark'] ) ); ?>> Ciemny wygląd podstron (odznacz, jeśli gdzieś tekst jest nieczytelny — podstrony wrócą do jasnego tła)</label><br>
						<label><input type="checkbox" name="<?php echo esc_attr( $o ); ?>[events]" value="1" <?php checked( ! empty( $s['events'] ) ); ?>> Nadchodzące wydarzenia na stronie głównej</label><br>
						<label><input type="checkbox" name="<?php echo esc_attr( $o ); ?>[favicon]" value="1" <?php checked( ! empty( $s['favicon'] ) ); ?>> Ikona strony z lotosem (karta przeglądarki, pasek administratora)</label><br>
						<label><input type="checkbox" name="<?php echo esc_attr( $o ); ?>[stats]" value="1" <?php checked( ! empty( $s['stats'] ) ); ?>> Liczniki na stronie głównej (prawdziwe liczby wizytówek i kategorii)</label><br>
						<label><input type="checkbox" name="<?php echo esc_attr( $o ); ?>[cta_band]" value="1" <?php checked( ! empty( $s['cta_band'] ) ); ?>> Baner „Are You a Holistic Therapist?” przed stopką na podstronach</label><br>
						<label><input type="checkbox" name="<?php echo esc_attr( $o ); ?>[cta_bar]" value="1" <?php checked( ! empty( $s['cta_bar'] ) ); ?>> Pasek z zachętą do rejestracji na dole ekranu</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="htn-join-label">Przycisk rejestracji</label></th>
					<td>
						<input id="htn-join-label" class="regular-text" type="text" name="<?php echo esc_attr( $o ); ?>[join_label]" value="<?php echo esc_attr( $s['join_label'] ); ?>">
						<p class="description">Napis na różowym przycisku w nagłówku.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="htn-join-url">Adres rejestracji</label></th>
					<td>
						<input id="htn-join-url" class="regular-text" type="text" name="<?php echo esc_attr( $o ); ?>[join_url]" value="<?php echo esc_attr( $s['join_url'] ); ?>">
						<p class="description">Dokąd prowadzą przyciski rejestracji. Domyślnie <?php echo esc_html( $d['join_url'] ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="htn-hero">Zdjęcie w banerze</label></th>
					<td>
						<input id="htn-hero" class="large-text" type="url" name="<?php echo esc_attr( $o ); ?>[hero_image]" value="<?php echo esc_attr( $s['hero_image'] ); ?>" placeholder="https://">
						<p class="description">Adres własnego zdjęcia z biblioteki mediów (najlepiej ciemne, min. 1600 px szerokości). Puste pole = zdjęcie lotosu z wtyczki.</p>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Zapisz' ); ?>
		</form>
		<p>Własne menu w nagłówku ustawisz w <a href="<?php echo esc_url( admin_url( 'nav-menus.php?action=locations' ) ); ?>">Wygląd → Menu → Zarządzaj położeniem</a>, pozycja „HT Neon – menu w nagłówku”. Bez tego nagłówek pokazuje menu domyślne.</p>
	</div>
	<?php
}

add_action( 'admin_bar_menu', 'htn_admin_bar', 100 );
function htn_admin_bar( $bar ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s      = htn_settings();
	$labels = array(
		'preview' => 'HT Neon: PODGLĄD',
		'live'    => 'HT Neon: włączony',
		'off'     => 'HT Neon: wyłączony',
	);
	$bar->add_node(
		array(
			'id'    => 'htn',
			'title' => $labels[ $s['mode'] ],
			'href'  => admin_url( 'themes.php?page=ht-neon' ),
		)
	);
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'htn_action_links' );
function htn_action_links( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'themes.php?page=ht-neon' ) ) . '">Ustawienia</a>' );
	return $links;
}

/* Social share images: no Rank Math "play" overlay (the site has no videos behind those images). */
add_filter( 'get_post_metadata', 'htn_no_og_overlay', 10, 3 );
function htn_no_og_overlay( $value, $object_id, $meta_key ) {
	if ( 'rank_math_facebook_enable_image_overlay' === $meta_key || 'rank_math_twitter_enable_image_overlay' === $meta_key ) {
		return array( 'off' );
	}
	return $value;
}

/*
 * Blog post content (shown on the post and used by SEO audits):
 * - images with no alt text get one built from the focus keyword and title;
 * - an H1 written inside the text becomes H2 (the page title is the only H1);
 * - when the text has no internal link or no subheading with the focus keyword,
 *   a short "More on <keyword>" section links to matching practitioners, the directory and related posts.
 */
add_filter( 'the_content', 'htn_post_content_seo', 30 );
function htn_post_content_seo( $html ) {
	$post = get_post();
	if ( ! $post || 'post' !== $post->post_type || is_feed() || ! htn_is_active() || false !== strpos( $html, 'htn-more-on' ) ) {
		return $html;
	}
	if ( ! is_singular( 'post' ) && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) && ! is_admin() && ! wp_doing_cron() ) {
		return $html;
	}
	$title = str_replace( array( '’', '‘' ), "'", html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ) );
	$kw    = str_replace( array( '’', '‘' ), "'", trim( explode( ',', (string) get_post_meta( $post->ID, 'rank_math_focus_keyword', true ) )[0] ) );
	$kw_uc = $kw ? mb_strtoupper( mb_substr( $kw, 0, 1 ) ) . mb_substr( $kw, 1 ) : '';

	// 0. No image in the text: the featured image opens the article (the template then skips its separate hero image).
	if ( false === stripos( $html, '<img' ) && has_post_thumbnail( $post ) ) {
		$tid  = get_post_thumbnail_id( $post );
		$alt0 = (string) get_post_meta( $tid, '_wp_attachment_image_alt', true );
		$alt0 = '' !== trim( $alt0 ) ? $alt0 : $title;
		if ( $kw && false === mb_stripos( $alt0, $kw ) ) {
			$alt0 = $kw_uc . ': ' . $alt0;
		}
		$html = '<figure class="htn-post-lead-img">' . wp_get_attachment_image( $tid, 'large', false, array( 'alt' => $alt0, 'loading' => 'eager' ) ) . '</figure>' . $html;
	}

	// 1. Alt text.
	$has_kw_alt = false;
	$html       = preg_replace_callback(
		'/<img\b[^>]*>/i',
		function ( $m ) use ( $kw, $kw_uc, $title, &$has_kw_alt ) {
			$tag = $m[0];
			if ( preg_match( '/\balt\s*=\s*(["\'])(.*?)\1/is', $tag, $a ) && '' !== trim( $a[2] ) ) {
				if ( $kw && false !== mb_stripos( $a[2], $kw ) ) {
					$has_kw_alt = true;
				}
				return $tag;
			}
			$alt = ( $kw && false === mb_stripos( $title, $kw ) ) ? $kw_uc . ': ' . $title : $title;
			if ( $kw && false !== mb_stripos( $alt, $kw ) ) {
				$has_kw_alt = true;
			}
			$tag = preg_replace( '/\salt\s*=\s*(["\'])\s*\1/i', '', $tag );
			return preg_replace( '/^<img\b/i', '<img alt="' . esc_attr( $alt ) . '"', $tag );
		},
		$html
	);
	if ( $kw && ! $has_kw_alt ) {
		// No image mentions the keyword yet: add it to the first image's alt text.
		$html = preg_replace_callback(
			'/<img\b[^>]*\balt\s*=\s*(["\'])(.*?)\1[^>]*>/is',
			function ( $m ) use ( $kw_uc ) {
				$alt = html_entity_decode( wp_strip_all_tags( $m[2] ), ENT_QUOTES, 'UTF-8' ) . ' – ' . $kw_uc;
				return preg_replace( '/\balt\s*=\s*(["\']).*?\1/is', 'alt="' . esc_attr( $alt ) . '"', $m[0], 1 );
			},
			$html,
			1
		);
	}

	// 2. H1 inside the text.
	$html = preg_replace( '#<h1(\s[^>]*)?>(.*?)</h1>#is', '<h2$1>$2</h2>', $html );

	// 3. "More on <keyword>" section.
	$host         = preg_quote( preg_replace( '/^www\./', '', (string) wp_parse_url( home_url(), PHP_URL_HOST ) ), '#' );
	$has_internal = (bool) preg_match( '#<a\b[^>]*href=["\'](?:/(?!/)|https?://(?:www\.)?' . $host . ')#i', $html );
	$kw_heading   = $kw && preg_match( '#<h[2-6][^>]*>(?:(?!</h).)*' . preg_quote( $kw, '#' ) . '#isu', $html );
	if ( $kw && ( ! $has_internal || ! $kw_heading ) ) {
		$links = array();
		if ( function_exists( 'htn_post_therapy_links' ) ) {
			foreach ( htn_post_therapy_links( $post->ID, 2 ) as $l ) {
				$links[] = '<a href="' . esc_url( $l['url'] ) . '">' . esc_html( $l['name'] ) . '</a>';
			}
		}
		$find = '<a href="' . esc_url( htn_search_url() ) . '">find a holistic therapist near you</a>';
		$what = $links ? 'browse ' . implode( ' and ', $links ) . ' or ' . $find : $find;
		$p    = 'If you would like support with ' . esc_html( $kw ) . ', ' . $what . ' on Holistic Therapists Ireland. You can also see <a href="' . esc_url( home_url( '/events/' ) ) . '">upcoming holistic events</a> near you.';
		$html .= '<section class="htn-more-on"><h2>More on ' . esc_html( $kw_uc ) . '</h2><p>' . $p . '</p></section>';
	}
	return $html;
}

/*
 * One share bar for blog posts, events and listings: LinkedIn, Facebook, X, WhatsApp, Email, Copy link.
 * Icons from WP Share Bar (ravxvx/wp-share-bar, GPL); colours from the Natural look. No tracking, no external scripts.
 */
function htn_share_icon( $name ) {
	$i = array(
		'linkedin' => '<svg class="f" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="9" width="4" height="12"/><circle cx="5" cy="5" r="2.2"/><path d="M10 9h3.8v1.8c.7-1.3 2.1-2.1 3.9-2.1 3.4 0 4.3 2.2 4.3 5.3V21h-4v-6c0-1.4-.4-2.4-1.8-2.4-1.5 0-2.2 1-2.2 2.5V21h-4z"/></svg>',
		'facebook' => '<svg class="f" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 8V6.5c0-.8.3-1.2 1.3-1.2H17V2h-2.7C11.5 2 10 3.7 10 6.2V8H7.5v3.3H10V22h4V11.3h2.7L17 8z"/></svg>',
		'x'        => '<svg class="f" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.8 3h3.1l-6.8 7.8L22 21h-6.3l-4.9-6.4L5.2 21H2.1l7.3-8.3L1.8 3h6.4l4.4 5.9zM16.7 19.2h1.7L7.2 4.7H5.4z"/></svg>',
		'whatsapp' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-12.4 7.4L3 21l2-5.4A8.5 8.5 0 1 1 21 11.5z"/><path d="M9 9c0 3 3 6 6 6l1.2-1.5-2-1-1 .7c-.8-.4-1.5-1.1-1.9-1.9l.7-1-1-2z"/></svg>',
		'email'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>',
		'link'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/></svg>',
	);
	return isset( $i[ $name ] ) ? $i[ $name ] : '';
}

function htn_share_bar( $url, $title, $label = 'Share', $extra_class = '' ) {
	$u     = rawurlencode( $url );
	$t     = rawurlencode( html_entity_decode( wp_strip_all_tags( $title ), ENT_QUOTES, 'UTF-8' ) );
	$links = array(
		array( 'li', 'LinkedIn', 'https://www.linkedin.com/sharing/share-offsite/?url=' . $u, 'linkedin', true ),
		array( 'fb', 'Facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . $u, 'facebook', true ),
		array( 'xx', 'X', 'https://x.com/intent/post?url=' . $u . '&text=' . $t, 'x', true ),
		array( 'wa', 'WhatsApp', 'https://wa.me/?text=' . $t . '%20' . $u, 'whatsapp', true ),
		array( 'em', 'Email', 'mailto:?subject=' . $t . '&body=' . $u, 'email', false ),
	);
	$out = '<div class="htn-share ' . esc_attr( $extra_class ) . '" data-ss-share><span class="htn-share__lbl">' . esc_html( $label ) . '</span><div class="htn-share__btns">';
	foreach ( $links as $l ) {
		$out .= '<a class="htn-share__' . $l[0] . '" href="' . esc_url( $l[2] ) . '"' . ( $l[4] ? ' target="_blank" rel="noopener noreferrer"' : '' ) . ' aria-label="Share on ' . esc_attr( $l[1] ) . '" title="' . esc_attr( $l[1] ) . '">' . htn_share_icon( $l[3] ) . '</a>';
	}
	$out .= '<button class="htn-share__cp" type="button" data-htn-copy="' . esc_url( $url ) . '" aria-label="Copy link" title="Copy link">' . htn_share_icon( 'link' ) . '</button>';
	$out .= '</div><span class="htn-share__msg" aria-live="polite"></span></div>';
	static $js = false;
	if ( ! $js ) {
		$js   = true;
		$out .= '<script>(function(){document.addEventListener("click",function(e){var b=e.target.closest("[data-htn-copy]");if(!b)return;var u=b.getAttribute("data-htn-copy"),m=b.closest(".htn-share").querySelector(".htn-share__msg");var ok=function(){if(m){m.textContent="Link copied";setTimeout(function(){m.textContent=""},2200);}};if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(u).then(ok,function(){window.prompt("Copy this link",u);});}else{window.prompt("Copy this link",u);}});})();</script>';
	}
	return $out;
}

/* TEMP diagnostics for e-mail templates (no secrets). Remove after. */
add_action( 'template_redirect', function () {
	if ( ! isset( $_GET['htn_maildiag'] ) || 'q9x4' !== $_GET['htn_maildiag'] ) {
		return;
	}
	$cb = function ( $hook ) {
		global $wp_filter;
		$out = array();
		if ( isset( $wp_filter[ $hook ] ) ) {
			foreach ( $wp_filter[ $hook ]->callbacks as $prio => $fns ) {
				foreach ( $fns as $f ) {
					$fn = $f['function'];
					if ( is_array( $fn ) ) {
						$fn = ( is_object( $fn[0] ) ? get_class( $fn[0] ) : $fn[0] ) . '::' . $fn[1];
					} elseif ( $fn instanceof Closure ) {
						$r  = new ReflectionFunction( $fn );
						$fn = 'closure@' . basename( dirname( $r->getFileName() ) ) . '/' . basename( $r->getFileName() ) . ':' . $r->getStartLine();
					}
					$out[] = $prio . ' ' . $fn;
				}
			}
		}
		return $out;
	};
	$pick = function ( $arr, $keys ) {
		$o = array();
		foreach ( (array) $arr as $k => $v ) {
			foreach ( $keys as $want ) {
				if ( false !== stripos( (string) $k, $want ) && ! preg_match( '/key|pass|secret|token|api/i', (string) $k ) ) {
					$o[ $k ] = is_scalar( $v ) ? ( is_string( $v ) && strpos( $v, '@' ) ? preg_replace( '/^[^@]+/', '***', $v ) : $v ) : '[array]';
				}
			}
		}
		return $o;
	};
	$affwp    = get_option( 'affwp_settings', array() );
	$mailster = get_option( 'mailster_options', array() );
	$lp       = get_option( 'listingpro_options', array() );
	global $wpdb;
	$hits = array();
	$rows = $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_value LIKE '%nly-Holistic%' OR option_value LIKE '%onlyholistic%' OR option_value LIKE '%Only Holistic%' LIMIT 50" );
	foreach ( $rows as $r ) {
		$v = maybe_unserialize( $r->option_value );
		$keys = array();
		if ( is_array( $v ) ) {
			array_walk_recursive( $v, function ( $val, $k ) use ( &$keys ) {
				if ( is_string( $val ) && preg_match( '/nly-Holistic|onlyholistic|Only Holistic/i', $val ) ) {
					$keys[] = $k . ' => ' . substr( preg_replace( '/[\w.+-]+@/', '***@', wp_strip_all_tags( $val ) ), 0, 160 );
				}
			} );
		} else {
			$keys[] = substr( preg_replace( '/[\w.+-]+@/', '***@', wp_strip_all_tags( (string) $v ) ), 0, 160 );
		}
		$hits[ $r->option_name ] = $keys;
	}
	$posts = $wpdb->get_results( "SELECT ID, post_type, post_status, post_title FROM {$wpdb->posts} WHERE post_type NOT IN ('revision','attachment','listing','post','page','events','nav_menu_item') AND (post_content LIKE '%nly-Holistic%' OR post_content LIKE '%onlyholistic%' OR post_content LIKE '%Only Holistic%') LIMIT 40" );
	wp_send_json(
		array(
			'hits'           => $hits,
			'posts'          => $posts,
			'wp_mail'        => $cb( 'wp_mail' ),
			'phpmailer_init' => $cb( 'phpmailer_init' ),
			'wp_mail_from'   => $cb( 'wp_mail_from' ),
			'wp_mail_from_name' => $cb( 'wp_mail_from_name' ),
			'affwp'          => $pick( $affwp, array( 'email', 'logo', 'template', 'from' ) ),
			'mailster'       => $pick( $mailster, array( 'system', 'wp_mail', 'from', 'template', 'logo' ) ),
			'lp'             => $pick( $lp, array( 'email', 'logo', 'mail' ) ),
			'blogname'       => get_option( 'blogname' ),
			'admin_email_domain' => substr( strrchr( (string) get_option( 'admin_email' ), '@' ), 1 ),
		)
	);
}, 0 );
