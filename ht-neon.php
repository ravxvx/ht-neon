<?php
/**
 * Plugin Name: HT Neon
 * Description: Ciemny, fioletowo-różowy wygląd holistictherapists.ie nakładany na motyw ListingPro: własny nagłówek z logo, nowa strona główna (baner z wyszukiwarką, kategorie, zachęta do rejestracji, liczniki) i odświeżone podstrony. Tryb podglądu pokazuje zmiany tylko administratorom. Wyłączenie wtyczki przywraca poprzedni wygląd.
 * Version: 2.1.2
 * Author: Holistic Therapists Ireland
 * License: GPL-2.0-or-later
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: ht-neon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HTN_VERSION', '2.1.2' );
define( 'HTN_OPTION', 'htn_settings' );
define( 'HTN_URL', plugin_dir_url( __FILE__ ) );
define( 'HTN_PATH', plugin_dir_path( __FILE__ ) );

/* ------------------------------------------------------------------ *
 * Settings
 * ------------------------------------------------------------------ */

function htn_defaults() {
	return array(
		'mode'       => 'preview', // off | preview | live
		'home'       => 1,
		'header'     => 1,
		'footer'     => 1,
		'dark'       => 1,
		'events'     => 1,
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
	return htn_is_active() && ! empty( $s['dark'] ) && ! htn_is_dashboard();
}

function htn_url( $path ) {
	$path = (string) $path;
	return ( 0 === strpos( $path, '/' ) ) ? home_url( $path ) : $path;
}

function htn_join_url() {
	$s = htn_settings();
	return htn_url( $s['join_url'] );
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
	}
	wp_enqueue_style( 'htn-neon', HTN_URL . 'assets/css/neon.css', array( 'htn-skin' ), HTN_VERSION );
	if ( htn_is_dashboard() ) {
		wp_enqueue_style( 'htn-dashboard', HTN_URL . 'assets/css/dashboard.css', array( 'htn-neon' ), HTN_VERSION );
	}
	wp_enqueue_script( 'htn-neon', HTN_URL . 'assets/js/neon.js', array(), HTN_VERSION, true );
}

add_filter( 'body_class', 'htn_body_class' );
function htn_body_class( $classes ) {
	if ( htn_is_active() ) {
		$classes[] = 'htn';
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
	foreach ( array( 32, 180, 192, 270, 512 ) as $available ) {
		if ( $size <= $available ) {
			return HTN_URL . 'assets/img/icon-' . $available . '.png?v=' . HTN_VERSION;
		}
	}
	return HTN_URL . 'assets/img/icon-512.png?v=' . HTN_VERSION;
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
add_action( 'template_redirect', 'htn_start_buffer', 0 );
function htn_start_buffer() {
	if ( htn_use_header() && ! is_feed() && ! is_robots() ) {
		ob_start( 'htn_inject_header' );
	}
}

add_action( 'wp_head', 'htn_prepare_header', 1 );
function htn_prepare_header() {
	if ( ! htn_use_header() ) {
		return;
	}
	ob_start();
	htn_render_header();
	$GLOBALS['htn_header_html'] = ob_get_clean();
}

function htn_inject_header( $html ) {
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
	<div class="htn-header" id="htn-header" role="banner">
		<div class="htn-header__inner">
			<?php echo htn_logo(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
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
					echo htn_default_nav(); // phpcs:ignore WordPress.Security.EscapeOutput
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
						<a class="htn-btn htn-btn--ghost htn-login md-trigger" data-modal="modal-3" href="<?php echo esc_url( wp_login_url( home_url( '/' ) ) ); ?>"><?php echo htn_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Log In</a>
						<a class="htn-btn htn-btn--pink" href="<?php echo esc_url( htn_join_url() ); ?>"><?php echo esc_html( $s['join_label'] ); ?></a>
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
		$file = HTN_PATH . 'templates/home.php';
		if ( file_exists( $file ) ) {
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
				<?php echo htn_logo(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
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
					<li><a href="<?php echo esc_url( htn_join_url() ); ?>"><?php echo esc_html( $s['join_label'] ); ?></a></li>
					<?php echo htn_page_link( 'submit-listing', 'Add a Listing' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
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
		<a class="htn-btn htn-btn--pink" href="<?php echo esc_url( htn_join_url() ); ?>"><?php echo esc_html( $s['join_label'] ); ?></a>
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
