<?php
/**
 * HT Neon – "Natural" home page template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$htn_s      = htn_settings();
$htn_img    = HTN_URL . 'assets/img/natural/';
$htn_hero   = ! empty( $htn_s['hero_image'] ) ? $htn_s['hero_image'] : $htn_img . 'hero.jpg';
$htn_join   = htn_join_url();
$htn_find   = htn_search_url();
$htn_events = get_page_by_path( 'events' );
$htn_ev_url = $htn_events ? get_permalink( $htn_events ) : $htn_find;
$htn_locs   = get_terms(
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

/* Resolve a label to a listing category (by slug, then by name), otherwise a search / events link. */
$htn_cat_link = function ( $slugs, $fallback ) use ( $htn_find ) {
	foreach ( (array) $slugs as $htn_slug ) {
		$htn_term = get_term_by( 'slug', $htn_slug, 'listing-category' );
		if ( ! $htn_term ) {
			$htn_term = get_term_by( 'name', $htn_slug, 'listing-category' );
		}
		if ( $htn_term && ! is_wp_error( $htn_term ) ) {
			$htn_l = get_term_link( $htn_term );
			if ( ! is_wp_error( $htn_l ) ) {
				return $htn_l;
			}
		}
	}
	return $fallback ? $fallback : add_query_arg( 'select', rawurlencode( is_array( $slugs ) ? $slugs[0] : $slugs ), $htn_find );
};

/* Quick chips under the search bar – as in the design. */
$htn_chips = array(
	array( 'Massage', $htn_cat_link( array( 'massage' ), '' ) ),
	array( 'Reiki', $htn_cat_link( array( 'reiki' ), '' ) ),
	array( 'Access Bars&reg;', $htn_cat_link( array( 'access-bars', 'Access Bars' ), '' ) ),
	array( 'Coaching', $htn_cat_link( array( 'coaching' ), '' ) ),
	array( 'Energy Healing', $htn_cat_link( array( 'bio-energy', 'energy-healing', 'Bio-Energy' ), '' ) ),
	array( 'Workshops', $htn_ev_url ),
	array( 'Retreats', $htn_ev_url ),
);

/* Browse by therapy: label, icon, link – as in the design. */
$htn_browse = array(
	array( 'Massage', 'lotus', $htn_cat_link( array( 'massage' ), '' ) ),
	array( 'Reiki', 'hands', $htn_cat_link( array( 'reiki' ), '' ) ),
	array( 'Access Bars', 'stones', $htn_cat_link( array( 'access-bars', 'Access Bars' ), '' ) ),
	array( 'Coaching', 'compass', $htn_cat_link( array( 'coaching' ), '' ) ),
	array( 'Energy Work', 'infinity', $htn_cat_link( array( 'bio-energy', 'Bio-Energy' ), '' ) ),
	array( 'Holistic Therapies', 'leaf2', $htn_cat_link( array( 'therapist', 'Therapist' ), $htn_find ) ),
	array( 'Workshops', 'calendar', $htn_ev_url ),
	array( 'Retreats', 'mountain', $htn_ev_url ),
);

get_header();
?>
<main class="htn-main htn-main--natural" id="htn-main">

	<section class="htn-nhero" style="--htn-hero-img:url('<?php echo esc_url( $htn_hero ); ?>')">
		<div class="htn-nhero__bg" aria-hidden="true"></div>
		<div class="htn-wrap htn-nhero__inner">
			<p class="htn-eyebrow">Ireland's Holistic Wellbeing Directory</p>
			<h1 class="htn-nhero__title">Find Therapists, Therapies <br>&amp; Wellbeing Events</h1>
			<p class="htn-nhero__lead">A trusted space to connect with qualified holistic therapists <br>and inspiring events across Ireland.</p>

			<form class="htn-nsearch" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" role="search" autocomplete="off">
				<label class="htn-nsearch__field htn-nsearch__field--where">
					<?php echo htn_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span class="screen-reader-text">Location</span>
					<select name="lp_s_loc">
						<option value="">All Ireland</option>
						<?php foreach ( $htn_locs as $htn_loc ) : ?>
							<option value="<?php echo esc_attr( $htn_loc->term_id ); ?>"><?php echo esc_html( $htn_loc->name ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php echo htn_icon( 'chev' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</label>
				<label class="htn-nsearch__field htn-nsearch__field--what">
					<?php echo htn_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span class="screen-reader-text">Therapy</span>
					<input type="text" name="select" placeholder="Therapy, practitioner or keyword...">
				</label>
				<input type="hidden" name="lp_s_cat" value="">
				<input type="hidden" name="s" value="home">
				<input type="hidden" name="post_type" value="listing">
				<button class="htn-nbtn htn-nsearch__btn" type="submit">Search</button>
			</form>

			<?php if ( $htn_chips ) : ?>
				<ul class="htn-chips">
					<?php foreach ( $htn_chips as $htn_c ) : ?>
						<li><a href="<?php echo esc_url( $htn_c[1] ); ?>"><?php echo wp_kses( $htn_c[0], array() ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</section>

	<section class="htn-nsection htn-nfeatures" aria-label="What you can do here">
		<div class="htn-wrap">
			<ul class="htn-nfeatures__list">
				<li><a href="<?php echo esc_url( $htn_find ); ?>"><?php echo htn_icon( 'lotus' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><strong>Find a Therapist</strong><span>Browse trusted practitioners</span></a></li>
				<li><a href="<?php echo esc_url( $htn_ev_url ); ?>"><?php echo htn_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><strong>Discover Events</strong><span>Workshops, circles &amp; retreats</span></a></li>
				<li><a href="<?php echo esc_url( $htn_find ); ?>"><?php echo htn_icon( 'leaf2' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><strong>Explore Therapies</strong><span>From massage to energy work</span></a></li>
				<li><a href="<?php echo esc_url( $htn_join ); ?>"><?php echo htn_icon( 'users' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><strong>Join the Community</strong><span>A space for wellbeing and growth</span></a></li>
			</ul>
		</div>
	</section>

	<?php
	$htn_fallback_feat = array( $htn_img . 'feat-1.jpg', $htn_img . 'feat-2.jpg', $htn_img . 'feat-3.jpg', $htn_img . 'cta-stones.jpg' );
	$htn_fallback_ev   = array( $htn_img . 'ev-1.jpg', $htn_img . 'ev-2.jpg', $htn_img . 'ev-3.jpg', $htn_img . 'cta-tent.jpg' );
	$htn_listings      = new WP_Query(
		array(
			'post_type'           => 'listing',
			'post_status'         => 'publish',
			'posts_per_page'      => 8,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
	$htn_stats = htn_stats();
	?>
	<section class="htn-nsection htn-ntherapists" aria-labelledby="htn-therapists-title">
		<div class="htn-wrap">
			<div class="htn-nhead htn-nhead--big">
				<div>
					<p class="htn-nkicker">Trusted practitioners</p>
					<h2 id="htn-therapists-title"><?php echo htn_icon( 'leaf2' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Featured Therapists</h2>
				</div>
				<a href="<?php echo esc_url( $htn_find ); ?>">View all therapists <?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			</div>
			<?php if ( $htn_listings->have_posts() ) : ?>
				<ul class="htn-ngrid htn-ngrid--4">
					<?php
					$htn_i = 0;
					while ( $htn_listings->have_posts() ) :
						$htn_listings->the_post();
						$htn_cat = get_the_terms( get_the_ID(), 'listing-category' );
						$htn_loc = get_the_terms( get_the_ID(), 'location' );
						$htn_src = get_the_post_thumbnail_url( get_the_ID(), 'medium_large' );
						if ( ! $htn_src ) {
							$htn_src = $htn_fallback_feat[ $htn_i % 4 ];
						}
						$htn_i++;
						?>
						<li>
							<a class="htn-ncard" href="<?php the_permalink(); ?>">
								<span class="htn-ncard__img"><img src="<?php echo esc_url( $htn_src ); ?>" alt="" loading="lazy"></span>
								<span class="htn-ncard__body">
									<span class="htn-ncard__title"><?php the_title(); ?></span>
									<span class="htn-ncard__meta"><?php echo ( $htn_cat && ! is_wp_error( $htn_cat ) ) ? esc_html( end( $htn_cat )->name ) : 'Therapist'; ?><?php echo ( $htn_loc && ! is_wp_error( $htn_loc ) ) ? ' &bull; ' . esc_html( $htn_loc[0]->name ) : ''; ?></span>
									<span class="htn-ncard__more">View profile <?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
								</span>
							</a>
						</li>
					<?php endwhile; ?>
				</ul>
			<?php endif; ?>
			<?php wp_reset_postdata(); ?>
		</div>
	</section>

	<?php $htn_upcoming = ! empty( $htn_s['events'] ) ? htn_upcoming_events( 6 ) : array(); ?>
	<section class="htn-nevents" aria-labelledby="htn-events-title">
		<div class="htn-wrap">
			<div class="htn-nhead htn-nhead--big">
				<div>
					<p class="htn-nkicker">Workshops, circles &amp; retreats</p>
					<h2 id="htn-events-title"><?php echo htn_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Upcoming Events</h2>
				</div>
				<a href="<?php echo esc_url( $htn_ev_url ); ?>">All events <?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			</div>
			<?php if ( $htn_upcoming ) : ?>
				<ul class="htn-ngrid htn-ngrid--3 htn-nevents__list">
					<?php foreach ( $htn_upcoming as $htn_k => $htn_e ) : ?>
						<?php
						$htn_src = $htn_e['img'] ? $htn_e['img'] : $htn_fallback_ev[ $htn_k % 4 ];
						$htn_loc = ( $htn_e['loc'] && ! is_numeric( $htn_e['loc'] ) ) ? $htn_e['loc'] : '';
						?>
						<li>
							<a class="htn-ncard htn-ncard--event" href="<?php echo esc_url( $htn_e['link'] ); ?>">
								<span class="htn-ncard__img"><img src="<?php echo esc_url( $htn_src ); ?>" alt="" loading="lazy">
									<?php if ( $htn_e['date'] ) : ?>
										<span class="htn-ndate"><b><?php echo esc_html( date_i18n( 'j', $htn_e['date'] ) ); ?></b><i><?php echo esc_html( date_i18n( 'M', $htn_e['date'] ) ); ?></i></span>
									<?php endif; ?>
								</span>
								<span class="htn-ncard__body">
									<span class="htn-ncard__title"><?php echo esc_html( $htn_e['title'] ); ?></span>
									<span class="htn-ncard__meta">
										<?php echo $htn_e['date'] ? esc_html( date_i18n( 'l, j F', $htn_e['date'] ) ) : ''; ?><?php echo $htn_e['time'] ? ' &bull; ' . esc_html( $htn_e['time'] ) : ''; ?>
										<?php if ( $htn_loc ) : ?><br><?php echo htn_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( $htn_loc ); ?><?php endif; ?>
									</span>
									<span class="htn-ncard__more">Event details <?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<div class="htn-nempty">
					<p>No upcoming events yet — be the first to list yours.</p>
				</div>
			<?php endif; ?>
			<div class="htn-nevents__cta">
				<p>Running a workshop, retreat or wellbeing circle? Reach people across Ireland who are looking for exactly that.</p>
				<a class="htn-nbtn" href="<?php echo esc_url( $htn_join ); ?>">List Your Event <?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			</div>
		</div>
	</section>

	<section class="htn-nsection htn-nduo" aria-label="For practitioners and organisers">
		<div class="htn-wrap htn-nduo__grid">
			<div class="htn-nbox" style="--htn-box-img:url('<?php echo esc_url( $htn_img . 'cta-stones.jpg' ); ?>')">
				<div class="htn-nbox__img" aria-hidden="true"></div>
				<div class="htn-nbox__body">
					<h2>Are you a Therapist?</h2>
					<p>Increase your visibility, reach new clients and be part of a trusted holistic community in Ireland.</p>
					<a class="htn-nbtn" href="<?php echo esc_url( $htn_join ); ?>">List Your Service <?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				</div>
			</div>
			<div class="htn-nbox" style="--htn-box-img:url('<?php echo esc_url( $htn_img . 'cta-tent.jpg' ); ?>')">
				<div class="htn-nbox__img" aria-hidden="true"></div>
				<div class="htn-nbox__body">
					<h2>Running an Event?</h2>
					<p>Promote your workshop, retreat or wellbeing event to a like-minded audience across Ireland.</p>
					<a class="htn-nbtn" href="<?php echo esc_url( $htn_join ); ?>">List Your Event <?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				</div>
			</div>
		</div>
	</section>

	<section class="htn-nsection htn-nbrowse" aria-labelledby="htn-browse-title">
		<div class="htn-wrap">
			<h2 class="htn-ntitle" id="htn-browse-title"><span>Browse by Therapy</span></h2>
			<ul class="htn-nbrowse__list">
				<?php foreach ( $htn_browse as $htn_b ) : ?>
					<li><a href="<?php echo esc_url( $htn_b[2] ); ?>"><span class="htn-nbrowse__icon"><?php echo htn_icon( $htn_b[1] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span><?php echo esc_html( $htn_b[0] ); ?></span></a></li>
				<?php endforeach; ?>
				<li><a href="<?php echo esc_url( $htn_find ); ?>"><span class="htn-nbrowse__icon"><?php echo htn_icon( 'grid' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span>More</span></a></li>
			</ul>
		</div>
	</section>

	<section class="htn-nwhy" aria-labelledby="htn-why-title">
		<div class="htn-wrap htn-nwhy__grid">
			<div class="htn-nwhy__copy">
				<p class="htn-nkicker">For therapists &amp; practitioners</p>
				<h2 id="htn-why-title">Grow your practice with a community that shares your values</h2>
				<p class="htn-nwhy__lead">Holistic Therapists Ireland is where people go when they are ready to look after themselves. Your profile puts you in front of them.</p>
				<ul class="htn-nwhy__list">
					<li><?php echo htn_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><div><strong>Be found by the right clients</strong><span>Searchable by therapy, location and keyword — on Google too.</span></div></li>
					<li><?php echo htn_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><div><strong>Promote your events</strong><span>Workshops, circles and retreats shown to the whole community.</span></div></li>
					<li><?php echo htn_icon( 'heart2' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><div><strong>A profile that feels like you</strong><span>Photos, your story, reviews, booking and contact details in one place.</span></div></li>
				</ul>
				<div class="htn-nwhy__actions">
					<a class="htn-nbtn htn-nbtn--lg" href="<?php echo esc_url( $htn_join ); ?>">Create your profile <?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
					<a class="htn-nlink" href="<?php echo esc_url( home_url( '/pricing-plan/' ) ); ?>">See plans</a>
				</div>
			</div>
			<div class="htn-nwhy__visual">
				<div class="htn-ncollage">
					<img src="<?php echo esc_url( $htn_img . 'feat-3.jpg' ); ?>" alt="" loading="lazy">
					<img src="<?php echo esc_url( $htn_img . 'ev-2.jpg' ); ?>" alt="" loading="lazy">
					<img src="<?php echo esc_url( $htn_img . 'feat-2.jpg' ); ?>" alt="" loading="lazy">
					<img src="<?php echo esc_url( $htn_img . 'ev-3.jpg' ); ?>" alt="" loading="lazy">
				</div>
				<ul class="htn-nstats">
					<li><b><?php echo esc_html( number_format_i18n( $htn_stats['listings'] ) ); ?>+</b><span>therapists &amp; practitioners</span></li>
					<li><b><?php echo esc_html( number_format_i18n( $htn_stats['categories'] ) ); ?></b><span>therapies to explore</span></li>
					<li><b>32</b><span>counties covered</span></li>
				</ul>
			</div>
		</div>
	</section>

	<section class="htn-nsection htn-nhow" aria-labelledby="htn-how-title">
		<div class="htn-wrap">
			<h2 class="htn-ntitle" id="htn-how-title"><span>How it works</span></h2>
			<ol class="htn-nhow__list">
				<li><span class="htn-nhow__num">1</span><strong>Create your free account</strong><span>Sign up in a minute and choose the plan that suits you.</span></li>
				<li><span class="htn-nhow__num">2</span><strong>Build your profile</strong><span>Add your therapies, photos, location, prices and booking details.</span></li>
				<li><span class="htn-nhow__num">3</span><strong>Get found &amp; grow</strong><span>Clients find you by therapy and county; list events to reach even more people.</span></li>
			</ol>
			<p class="htn-nhow__cta"><a class="htn-nbtn" href="<?php echo esc_url( $htn_join ); ?>">Get started <?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></p>
		</div>
	</section>

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
	if ( $htn_posts->have_posts() ) :
		$htn_blog = (int) get_option( 'page_for_posts' );
		?>
		<section class="htn-nsection htn-nblog" aria-labelledby="htn-blog-title">
			<div class="htn-wrap">
				<div class="htn-nhead htn-nhead--big">
					<div>
						<p class="htn-nkicker">Wellbeing reads</p>
						<h2 id="htn-blog-title"><?php echo htn_icon( 'leaf2' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> From the Blog</h2>
					</div>
					<?php if ( $htn_blog ) : ?><a href="<?php echo esc_url( get_permalink( $htn_blog ) ); ?>">All articles <?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a><?php endif; ?>
				</div>
				<ul class="htn-ngrid htn-ngrid--3">
					<?php
					$htn_i = 0;
					while ( $htn_posts->have_posts() ) :
						$htn_posts->the_post();
						$htn_src = get_the_post_thumbnail_url( get_the_ID(), 'medium_large' );
						if ( ! $htn_src ) {
							$htn_src = $htn_fallback_feat[ $htn_i % 4 ];
						}
						$htn_i++;
						?>
						<li>
							<a class="htn-ncard" href="<?php the_permalink(); ?>">
								<span class="htn-ncard__img"><img src="<?php echo esc_url( $htn_src ); ?>" alt="" loading="lazy"></span>
								<span class="htn-ncard__body">
									<span class="htn-ncard__meta"><?php echo esc_html( get_the_date() ); ?></span>
									<span class="htn-ncard__title"><?php the_title(); ?></span>
									<span class="htn-ncard__text"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></span>
									<span class="htn-ncard__more">Read more <?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
								</span>
							</a>
						</li>
					<?php endwhile; ?>
				</ul>
			</div>
		</section>
		<?php
	endif;
	wp_reset_postdata();
	?>

	<section class="htn-nband" aria-label="Why Holistic Therapists Ireland">
		<div class="htn-wrap">
			<ul class="htn-nband__list">
				<li><?php echo htn_icon( 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>Trusted Community</span></li>
				<li><?php echo htn_icon( 'users' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>For Qualified Practitioners</span></li>
				<li><?php echo htn_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>Events Across Ireland</span></li>
				<li><?php echo htn_icon( 'heart2' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>Wellbeing &amp; Personal Growth</span></li>
			</ul>
		</div>
	</section>

</main>
<?php
get_footer();
