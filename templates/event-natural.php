<?php
/**
 * HT Neon – "Natural" single event page (ListingPro "events" post type, also those added by the Google Events Importer).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

the_post();
$htn_id   = get_the_ID();
$htn_ev   = htn_event_data( $htn_id );
$htn_img  = HTN_URL . 'assets/img/natural/';
$htn_list = get_page_by_path( 'events' );
$htn_back = $htn_list ? get_permalink( $htn_list ) : home_url( '/' );
$htn_src  = $htn_ev['img'] ? $htn_ev['img'] : $htn_img . 'ev-1.jpg';
$htn_more = array_values(
	array_filter(
		htn_upcoming_events( 4 ),
		function ( $e ) use ( $htn_ev ) {
			return $e['link'] !== $htn_ev['url'];
		}
	)
);
$htn_more = array_slice( $htn_more, 0, 3 );
$htn_fb   = array( $htn_img . 'ev-1.jpg', $htn_img . 'ev-2.jpg', $htn_img . 'ev-3.jpg' );

get_header();
?>
<main class="htn-main htn-main--natural htn-evsingle" id="htn-main">

	<header class="htn-evs-head">
		<div class="htn-wrap">
			<a class="htn-evs-back" href="<?php echo esc_url( $htn_back ); ?>"><?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> All events</a>
			<?php if ( $htn_ev['start'] ) : ?>
				<p class="htn-nkicker"><?php echo esc_html( $htn_ev['start']->format( 'l · j F Y' ) ); ?></p>
			<?php endif; ?>
			<h1 class="htn-evs-title htn-h1-keep"><?php echo esc_html( $htn_ev['title'] ); ?></h1>
			<ul class="htn-evs-chips">
				<?php if ( $htn_ev['time_label'] ) : ?><li><?php echo htn_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( $htn_ev['time_label'] ); ?></li><?php endif; ?>
				<?php if ( $htn_ev['place'] ) : ?><li><?php echo htn_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( $htn_ev['place'] ); ?></li><?php endif; ?>
				<?php if ( $htn_ev['past'] ) : ?><li class="htn-evs-chips__past">This event has ended</li><?php endif; ?>
			</ul>
		</div>
	</header>

	<div class="htn-wrap htn-evs-grid">
		<article class="htn-evs-main">
			<figure class="htn-evs-photo"><img src="<?php echo esc_url( $htn_src ); ?>" alt="<?php echo esc_attr( $htn_ev['title'] ); ?>"></figure>
			<h2 class="htn-evs-h2">About this event</h2>
			<div class="htn-evs-content">
				<?php the_content(); ?>
			</div>
			<?php echo htn_share_bar( $htn_ev['url'], $htn_ev['title'], 'Share this event' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</article>

		<aside class="htn-evs-side">
			<div class="htn-evs-card">
				<?php if ( $htn_ev['start'] ) : ?>
					<div class="htn-evs-date">
						<span class="htn-evs-date__day"><b><?php echo esc_html( $htn_ev['start']->format( 'j' ) ); ?></b><i><?php echo esc_html( $htn_ev['start']->format( 'M' ) ); ?></i></span>
						<span class="htn-evs-date__txt"><strong><?php echo esc_html( $htn_ev['start']->format( 'l' ) ); ?></strong><?php echo esc_html( $htn_ev['time_label'] ? $htn_ev['time_label'] : $htn_ev['start']->format( 'F Y' ) ); ?></span>
					</div>
				<?php endif; ?>
				<?php if ( $htn_ev['place'] ) : ?>
					<div class="htn-evs-row">
						<?php echo htn_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<div><strong><?php echo esc_html( $htn_ev['place'] ); ?></strong><a href="<?php echo esc_url( $htn_ev['directions'] ); ?>" target="_blank" rel="noopener">Get directions</a></div>
					</div>
				<?php endif; ?>
				<div class="htn-evs-actions">
					<?php if ( $htn_ev['ticket'] ) : ?>
						<a class="htn-nbtn" href="<?php echo esc_url( $htn_ev['ticket'] ); ?>" target="_blank" rel="noopener nofollow">Tickets &amp; details <?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
					<?php endif; ?>
					<?php if ( $htn_ev['gcal'] && ! $htn_ev['past'] ) : ?>
						<a class="htn-nbtn <?php echo $htn_ev['ticket'] ? 'htn-nbtn--ghost' : ''; ?>" href="<?php echo esc_url( $htn_ev['gcal'] ); ?>" target="_blank" rel="noopener nofollow"><?php echo htn_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Add to calendar</a>
					<?php endif; ?>
				</div>
				<?php echo htn_share_bar( $htn_ev['url'], $htn_ev['title'], 'Share', 'htn-share--compact' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<p class="htn-evs-note">Run by an independent organiser. Please check the details with them before you go.</p>
			</div>
			<?php if ( $htn_ev['map'] ) : ?>
				<div class="htn-evs-map">
					<iframe src="<?php echo esc_url( $htn_ev['map'] ); ?>" title="<?php echo esc_attr( 'Map: ' . $htn_ev['place'] ); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
				</div>
			<?php endif; ?>
		</aside>
	</div>

	<?php if ( $htn_more ) : ?>
		<section class="htn-evs-more">
			<div class="htn-wrap">
				<h2 class="htn-evmonth__title"><span>More upcoming events</span></h2>
				<ul class="htn-evgrid">
					<?php foreach ( $htn_more as $htn_k => $htn_e ) : ?>
						<?php $htn_loc = ( $htn_e['loc'] && ! is_numeric( $htn_e['loc'] ) ) ? $htn_e['loc'] : ''; ?>
						<li>
							<a class="htn-evcard" href="<?php echo esc_url( $htn_e['link'] ); ?>">
								<span class="htn-evcard__img"><img src="<?php echo esc_url( $htn_e['img'] ? $htn_e['img'] : $htn_fb[ $htn_k % 3 ] ); ?>" alt="" loading="lazy">
									<?php if ( $htn_e['date'] ) : ?>
										<span class="htn-ndate"><b><?php echo esc_html( date_i18n( 'j', $htn_e['date'] ) ); ?></b><i><?php echo esc_html( date_i18n( 'M', $htn_e['date'] ) ); ?></i></span>
									<?php endif; ?>
								</span>
								<span class="htn-evcard__body">
									<span class="htn-evcard__title"><?php echo esc_html( html_entity_decode( $htn_e['title'], ENT_QUOTES, 'UTF-8' ) ); ?></span>
									<?php if ( $htn_loc ) : ?><span class="htn-evcard__meta"><?php echo htn_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( $htn_loc ); ?></span><?php endif; ?>
									<span class="htn-ncard__more">Details <?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

</main>
<?php
get_footer();
