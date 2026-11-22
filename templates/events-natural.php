<?php
/**
 * HT Neon – "Natural" events page: hero, the next event featured, then upcoming events grouped by month.
 * Replaces the ListingPro calendar widget on the /events/ page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$htn_s        = htn_settings();
$htn_img      = HTN_URL . 'assets/img/natural/';
$htn_join     = htn_join_url();
$htn_hero     = ! empty( $htn_s['hero_image'] ) ? $htn_s['hero_image'] : $htn_img . 'cta-tent.jpg';
$htn_fallback = array( $htn_img . 'ev-1.jpg', $htn_img . 'ev-2.jpg', $htn_img . 'ev-3.jpg', $htn_img . 'cta-tent.jpg' );
$htn_events   = htn_upcoming_events( 60 );

foreach ( $htn_events as $htn_k => $htn_e ) {
	$htn_events[ $htn_k ]['src']   = $htn_e['img'] ? $htn_e['img'] : $htn_fallback[ $htn_k % 4 ];
	$htn_events[ $htn_k ]['place'] = ( $htn_e['loc'] && ! is_numeric( $htn_e['loc'] ) ) ? $htn_e['loc'] : '';
	$htn_events[ $htn_k ]['name']  = html_entity_decode( $htn_e['title'], ENT_QUOTES, 'UTF-8' );
}

$htn_next = $htn_events ? array_shift( $htn_events ) : null;

/* Group the rest by month ("December 2026"); events without a date go last. */
$htn_months = array();
foreach ( $htn_events as $htn_e ) {
	$htn_key                  = $htn_e['date'] ? date_i18n( 'F Y', $htn_e['date'] ) : 'Date to be confirmed';
	$htn_months[ $htn_key ][] = $htn_e;
}

$htn_when = function ( $e, $long = true ) {
	$out = $e['date'] ? date_i18n( $long ? 'l, j F Y' : 'D, j M', $e['date'] ) : '';
	if ( $e['time'] ) {
		$out .= ( $out ? ' · ' : '' ) . $e['time'];
	}
	return $out;
};

get_header();
?>
<main class="htn-main htn-main--natural htn-evpage" id="htn-main">

	<section class="htn-evhero" style="--htn-evhero-img:url('<?php echo esc_url( $htn_hero ); ?>')">
		<div class="htn-evhero__bg" aria-hidden="true"></div>
		<div class="htn-wrap htn-evhero__inner">
			<p class="htn-nkicker">Workshops · Circles · Retreats</p>
			<h1 class="htn-evhero__title htn-h1-keep">Holistic Events <span>in Ireland</span></h1>
			<p class="htn-evhero__lead">Meditation evenings, healing workshops, sound baths and retreats, run by holistic practitioners around the country.</p>
			<div class="htn-evhero__actions">
				<a class="htn-nbtn" href="#htn-evlist"><?php echo htn_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> See what's on</a>
				<a class="htn-nbtn htn-nbtn--ghost" href="<?php echo esc_url( $htn_join ); ?>">List your event</a>
			</div>
		</div>
	</section>

	<div class="htn-evbody" id="htn-evlist">
		<div class="htn-wrap">

			<?php if ( $htn_next ) : ?>
				<p class="htn-nkicker htn-evlabel">Next up</p>
				<a class="htn-evnext" href="<?php echo esc_url( $htn_next['link'] ); ?>">
					<span class="htn-evnext__img"><img src="<?php echo esc_url( $htn_next['src'] ); ?>" alt="" loading="eager"></span>
					<span class="htn-evnext__body">
						<?php if ( $htn_next['date'] ) : ?>
							<span class="htn-evnext__date"><b><?php echo esc_html( date_i18n( 'j', $htn_next['date'] ) ); ?></b><i><?php echo esc_html( date_i18n( 'F', $htn_next['date'] ) ); ?></i></span>
						<?php endif; ?>
						<span class="htn-evnext__title"><?php echo esc_html( $htn_next['name'] ); ?></span>
						<span class="htn-evnext__meta">
							<?php if ( $htn_when( $htn_next ) ) : ?><span><?php echo htn_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( $htn_when( $htn_next ) ); ?></span><?php endif; ?>
							<?php if ( $htn_next['place'] ) : ?><span><?php echo htn_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( $htn_next['place'] ); ?></span><?php endif; ?>
						</span>
						<span class="htn-nbtn htn-evnext__btn">View event <?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					</span>
				</a>
			<?php endif; ?>

			<?php if ( count( $htn_months ) > 1 ) : ?>
				<nav class="htn-evmonths" aria-label="Jump to month">
					<?php foreach ( array_keys( $htn_months ) as $htn_m ) : ?>
						<a href="#<?php echo esc_attr( sanitize_title( 'm-' . $htn_m ) ); ?>"><?php echo esc_html( $htn_m ); ?></a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>

			<?php foreach ( $htn_months as $htn_month => $htn_items ) : ?>
				<section class="htn-evmonth" id="<?php echo esc_attr( sanitize_title( 'm-' . $htn_month ) ); ?>">
					<h2 class="htn-evmonth__title"><span><?php echo esc_html( $htn_month ); ?></span></h2>
					<ul class="htn-evgrid">
						<?php foreach ( $htn_items as $htn_e ) : ?>
							<li>
								<a class="htn-evcard" href="<?php echo esc_url( $htn_e['link'] ); ?>">
									<span class="htn-evcard__img"><img src="<?php echo esc_url( $htn_e['src'] ); ?>" alt="" loading="lazy">
										<?php if ( $htn_e['date'] ) : ?>
											<span class="htn-ndate"><b><?php echo esc_html( date_i18n( 'j', $htn_e['date'] ) ); ?></b><i><?php echo esc_html( date_i18n( 'M', $htn_e['date'] ) ); ?></i></span>
										<?php endif; ?>
									</span>
									<span class="htn-evcard__body">
										<span class="htn-evcard__title"><?php echo esc_html( $htn_e['name'] ); ?></span>
										<?php if ( $htn_when( $htn_e, false ) ) : ?><span class="htn-evcard__meta"><?php echo htn_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( $htn_when( $htn_e, false ) ); ?></span><?php endif; ?>
										<?php if ( $htn_e['place'] ) : ?><span class="htn-evcard__meta"><?php echo htn_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( $htn_e['place'] ); ?></span><?php endif; ?>
										<span class="htn-ncard__more">Details <?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
									</span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endforeach; ?>

			<?php if ( ! $htn_next ) : ?>
				<div class="htn-evempty">
					<span class="htn-evempty__icon"><?php echo htn_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<h2>No upcoming events just yet</h2>
					<p>New workshops, circles and retreats are added regularly – check back soon, or be the first to list yours.</p>
				</div>
			<?php endif; ?>

			<div class="htn-evcta" style="--htn-evcta-img:url('<?php echo esc_url( $htn_img . 'cta-stones.jpg' ); ?>')">
				<div class="htn-evcta__text">
					<h2>Running a workshop or retreat?</h2>
					<p>Share it with people across Ireland who are looking for exactly that. It only takes a few minutes.</p>
				</div>
				<a class="htn-nbtn" href="<?php echo esc_url( $htn_join ); ?>">List your event <?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			</div>
		</div>
	</div>

</main>
<?php
get_footer();
