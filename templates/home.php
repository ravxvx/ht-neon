<?php
/**
 * HT Neon – home page template. Used instead of the front page template while the plugin is active.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$htn_s     = htn_settings();
$htn_hero  = ! empty( $htn_s['hero_image'] ) ? $htn_s['hero_image'] : HTN_URL . 'assets/img/hero.jpg';
$htn_join  = htn_join_url();
$htn_find  = htn_search_url();
$htn_tiles = htn_home_tiles();
$htn_locs  = get_terms(
	array(
		'taxonomy'   => 'location',
		'hide_empty' => true,
		'orderby'    => 'name',
		'order'      => 'ASC',
	)
);
if ( is_wp_error( $htn_locs ) ) {
	$htn_locs = array();
}

get_header();
?>
<main class="htn-main" id="htn-main">

	<section class="htn-hero" style="--htn-hero-img:url('<?php echo esc_url( $htn_hero ); ?>')">
		<div class="htn-hero__bg" aria-hidden="true"></div>
		<div class="htn-wrap htn-hero__inner">
			<p class="htn-eyebrow">Ireland's Holistic Wellness Directory</p>
			<h1 class="htn-hero__title">Find Your Balance.<br>Discover <span>Holistic Therapies.</span></h1>
			<p class="htn-hero__lead">Connect with trusted, qualified holistic therapists across Ireland.<br><span class="htn-hero__tags">Wellbeing &bull; Balance &bull; Healing &bull; Mind &bull; Body &bull; Spirit</span></p>

			<form class="htn-search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" role="search" autocomplete="off">
				<label class="htn-search__field htn-search__field--what">
					<?php echo htn_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span class="screen-reader-text">Therapy</span>
					<input type="text" name="select" placeholder="Search for a therapy, e.g. Reiki, Massage, Yoga...">
				</label>
				<label class="htn-search__field htn-search__field--where">
					<?php echo htn_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span class="screen-reader-text">Location</span>
					<select name="lp_s_loc">
						<option value="">Location (e.g. Cork, Galway)</option>
						<?php foreach ( $htn_locs as $htn_loc ) : ?>
							<option value="<?php echo esc_attr( $htn_loc->term_id ); ?>"><?php echo esc_html( $htn_loc->name ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php echo htn_icon( 'chev' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</label>
				<input type="hidden" name="lp_s_cat" value="">
				<input type="hidden" name="s" value="home">
				<input type="hidden" name="post_type" value="listing">
				<button class="htn-btn htn-btn--pink htn-search__btn" type="submit">Search Therapists <?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			</form>

			<ul class="htn-features">
				<li><?php echo htn_icon( 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><div><strong>Verified Therapists</strong><span>Trusted &amp; qualified professionals</span></div></li>
				<li><?php echo htn_icon( 'leaf' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><div><strong>Wide Range of Therapies</strong><span>Massage, Reiki, Counselling &amp; more</span></div></li>
				<li><?php echo htn_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><div><strong>Across Ireland</strong><span>Find local therapists near you</span></div></li>
				<li><?php echo htn_icon( 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><div><strong>Support Your Wellbeing</strong><span>Mind, body and spirit in balance</span></div></li>
			</ul>
		</div>
	</section>

	<section class="htn-section htn-cats" aria-label="Therapy categories">
		<div class="htn-wrap">
			<ul class="htn-tiles">
				<?php foreach ( $htn_tiles as $htn_tile ) : ?>
					<li>
						<a class="htn-tile" href="<?php echo esc_url( $htn_tile['link'] ); ?>" style="--htn-tile-img:url('<?php echo esc_url( $htn_tile['image'] ); ?>')">
							<span class="htn-tile__label"><?php echo esc_html( $htn_tile['label'][0] ); ?><br><?php echo esc_html( $htn_tile['label'][1] ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
				<li>
					<a class="htn-tile htn-tile--more" href="<?php echo esc_url( $htn_find ); ?>">
						<?php echo htn_lotus_svg( 50 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span class="htn-tile__label">More<br>Therapies <?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					</a>
				</li>
			</ul>
		</div>
	</section>

	<section class="htn-section htn-duo" aria-label="Get started">
		<div class="htn-wrap htn-duo__grid">
			<div class="htn-box htn-box--seeker" style="--htn-box-img:url('<?php echo esc_url( HTN_URL . 'assets/img/cta-seeker.jpg' ); ?>')">
				<div class="htn-box__icon"><?php echo htn_lotus_svg( 58 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<div class="htn-box__body">
					<h2 class="htn-box__title">Looking for a Therapist?</h2>
					<p class="htn-box__text">Browse trusted holistic professionals in your area and take the next step towards better wellbeing.</p>
					<a class="htn-btn htn-btn--ghost" href="<?php echo esc_url( $htn_find ); ?>">Find a Therapist <?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				</div>
			</div>
			<div class="htn-box htn-box--therapist" style="--htn-box-img:url('<?php echo esc_url( HTN_URL . 'assets/img/cta-therapist.jpg' ); ?>')">
				<div class="htn-box__icon"><?php echo htn_icon( 'users' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<div class="htn-box__body">
					<h2 class="htn-box__title">Are You a Holistic Therapist?</h2>
					<p class="htn-box__text">Join Ireland's growing wellness community, showcase your services and reach new clients.</p>
					<a class="htn-btn htn-btn--pink" href="<?php echo esc_url( $htn_join ); ?>">Register Your Practice Today <?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				</div>
			</div>
		</div>
	</section>

	<?php if ( ! empty( $htn_s['stats'] ) ) : ?>
		<?php $htn_stats = htn_stats(); ?>
		<section class="htn-section htn-stats" aria-label="Directory in numbers">
			<div class="htn-wrap">
				<ul class="htn-stats__list">
					<li><?php echo htn_icon( 'users' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><div><strong><?php echo esc_html( number_format_i18n( $htn_stats['listings'] ) ); ?></strong><span>Listed Therapists &amp; Practices</span></div></li>
					<li><?php echo htn_icon( 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><div><strong><?php echo esc_html( number_format_i18n( $htn_stats['categories'] ) ); ?></strong><span>Therapy Categories</span></div></li>
					<li><?php echo htn_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><div><strong>All Across Ireland</strong><span>Local &amp; National Listings</span></div></li>
					<li><?php echo htn_icon( 'spark' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><div><strong>Growing Community</strong><span>Supporting Holistic Wellbeing</span></div></li>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<?php $htn_events = ! empty( $htn_s['events'] ) ? htn_upcoming_events( 4 ) : array(); ?>
	<?php if ( $htn_events ) : ?>
		<?php $htn_events_page = get_page_by_path( 'events' ); ?>
		<section class="htn-section htn-events" aria-labelledby="htn-events-title">
			<div class="htn-wrap">
				<div class="htn-head">
					<h2 class="htn-head__title" id="htn-events-title">Upcoming Events</h2>
					<?php if ( $htn_events_page ) : ?>
						<a class="htn-head__link" href="<?php echo esc_url( get_permalink( $htn_events_page ) ); ?>">All events <?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
					<?php endif; ?>
				</div>
				<ul class="htn-cards htn-cards--4">
					<?php foreach ( $htn_events as $htn_event ) : ?>
						<li>
							<a class="htn-card htn-card--event" href="<?php echo esc_url( $htn_event['link'] ); ?>">
								<span class="htn-card__img">
									<?php if ( $htn_event['img'] ) : ?>
										<img src="<?php echo esc_url( $htn_event['img'] ); ?>" alt="" loading="lazy">
									<?php endif; ?>
									<?php if ( $htn_event['date'] ) : ?>
										<span class="htn-card__date"><b><?php echo esc_html( date_i18n( 'j', $htn_event['date'] ) ); ?></b><?php echo esc_html( date_i18n( 'M', $htn_event['date'] ) ); ?></span>
									<?php endif; ?>
								</span>
								<span class="htn-card__body">
									<?php if ( $htn_event['date'] ) : ?>
										<span class="htn-card__meta"><?php echo esc_html( date_i18n( 'l, j F Y', $htn_event['date'] ) ); ?><?php echo $htn_event['time'] ? ' &middot; ' . esc_html( $htn_event['time'] ) : ''; ?></span>
									<?php endif; ?>
									<span class="htn-card__title"><?php echo esc_html( $htn_event['title'] ); ?></span>
									<?php if ( $htn_event['loc'] ) : ?>
										<span class="htn-card__loc"><?php echo htn_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( $htn_event['loc'] ); ?></span>
									<?php endif; ?>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<?php
	$htn_listings = new WP_Query(
		array(
			'post_type'           => 'listing',
			'post_status'         => 'publish',
			'posts_per_page'      => 4,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'meta_query'          => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'EXISTS',
				),
			),
		)
	);
	?>
	<?php if ( $htn_listings->have_posts() ) : ?>
		<section class="htn-section htn-latest" aria-labelledby="htn-latest-title">
			<div class="htn-wrap">
				<div class="htn-head">
					<h2 class="htn-head__title" id="htn-latest-title">Recently Added Therapists</h2>
					<a class="htn-head__link" href="<?php echo esc_url( $htn_find ); ?>">View all <?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				</div>
				<ul class="htn-cards htn-cards--4">
					<?php
					while ( $htn_listings->have_posts() ) :
						$htn_listings->the_post();
						$htn_cat = get_the_terms( get_the_ID(), 'listing-category' );
						$htn_loc = get_the_terms( get_the_ID(), 'location' );
						?>
						<li>
							<a class="htn-card" href="<?php the_permalink(); ?>">
								<span class="htn-card__img"><?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy', 'alt' => '' ) ); ?></span>
								<span class="htn-card__body">
									<?php if ( $htn_cat && ! is_wp_error( $htn_cat ) ) : ?>
										<span class="htn-card__meta"><?php echo esc_html( end( $htn_cat )->name ); ?></span>
									<?php endif; ?>
									<span class="htn-card__title"><?php the_title(); ?></span>
									<?php if ( $htn_loc && ! is_wp_error( $htn_loc ) ) : ?>
										<span class="htn-card__loc"><?php echo htn_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( $htn_loc[0]->name ); ?></span>
									<?php endif; ?>
								</span>
							</a>
						</li>
					<?php endwhile; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>
	<?php wp_reset_postdata(); ?>

	<?php
	$htn_posts = new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 3,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
	$htn_blog  = (int) get_option( 'page_for_posts' );
	?>
	<?php if ( $htn_posts->have_posts() ) : ?>
		<section class="htn-section htn-blog" aria-labelledby="htn-blog-title">
			<div class="htn-wrap">
				<div class="htn-head">
					<h2 class="htn-head__title" id="htn-blog-title">News &amp; Tips</h2>
					<?php if ( $htn_blog ) : ?>
						<a class="htn-head__link" href="<?php echo esc_url( get_permalink( $htn_blog ) ); ?>">Read the blog <?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
					<?php endif; ?>
				</div>
				<ul class="htn-cards htn-cards--3">
					<?php
					while ( $htn_posts->have_posts() ) :
						$htn_posts->the_post();
						?>
						<li>
							<a class="htn-card" href="<?php the_permalink(); ?>">
								<?php if ( has_post_thumbnail() ) : ?>
									<span class="htn-card__img"><?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy', 'alt' => '' ) ); ?></span>
								<?php endif; ?>
								<span class="htn-card__body">
									<span class="htn-card__meta"><?php echo esc_html( get_the_date() ); ?></span>
									<span class="htn-card__title"><?php the_title(); ?></span>
								</span>
							</a>
						</li>
					<?php endwhile; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>
	<?php wp_reset_postdata(); ?>

</main>
<?php
get_footer();
