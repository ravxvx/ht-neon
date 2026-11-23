<?php
/**
 * HT Neon – "Natural" single blog post: readable article layout, featured image, therapist links, related posts.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

the_post();
$htn_id    = get_the_ID();
$htn_title = html_entity_decode( get_the_title(), ENT_QUOTES, 'UTF-8' );
$htn_cats  = get_the_category( $htn_id );
$htn_cat   = $htn_cats ? $htn_cats[0] : null;
$htn_words = str_word_count( wp_strip_all_tags( get_post_field( 'post_content', $htn_id ) ) );
$htn_mins  = max( 1, (int) round( $htn_words / 200 ) );
$htn_thumb = get_post_thumbnail_id( $htn_id );
$htn_blog  = get_option( 'page_for_posts' ) ? get_permalink( (int) get_option( 'page_for_posts' ) ) : home_url( '/' );
$htn_url   = get_permalink( $htn_id );
$htn_links = htn_post_therapy_links( $htn_id, 4 );
$htn_rel   = htn_related_posts( $htn_id, 3 );
$htn_find  = htn_search_url();

get_header();
?>
<main class="htn-main htn-main--natural htn-post" id="htn-main">

	<header class="htn-post-head">
		<div class="htn-wrap">
			<a class="htn-evs-back" href="<?php echo esc_url( $htn_blog ); ?>"><?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Blog</a>
			<?php if ( $htn_cat && 'uncategorized' !== $htn_cat->slug ) : ?>
				<p class="htn-nkicker"><a href="<?php echo esc_url( get_category_link( $htn_cat ) ); ?>"><?php echo esc_html( $htn_cat->name ); ?></a></p>
			<?php endif; ?>
			<h1 class="htn-post-title htn-h1-keep"><?php echo esc_html( $htn_title ); ?></h1>
			<p class="htn-post-meta">
				<span><?php echo htn_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( get_the_date( 'j F Y' ) ); ?></span>
				<span><?php echo htn_icon( 'leaf' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( $htn_mins . ' min read' ); ?></span>
			</p>
		</div>
	</header>

	<?php if ( $htn_thumb ) : ?>
		<figure class="htn-post-hero">
			<?php echo wp_get_attachment_image( $htn_thumb, 'full', false, array( 'class' => 'htn-post-hero__img', 'loading' => 'eager', 'alt' => get_post_meta( $htn_thumb, '_wp_attachment_image_alt', true ) ? get_post_meta( $htn_thumb, '_wp_attachment_image_alt', true ) : $htn_title, 'sizes' => '(max-width: 1100px) 100vw, 1100px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</figure>
	<?php endif; ?>

	<div class="htn-post-body">
		<article class="htn-post-content">
			<?php the_content(); ?>
		</article>

		<?php if ( $htn_links ) : ?>
			<aside class="htn-post-find">
				<p class="htn-nkicker">Find a practitioner</p>
				<h2>Looking for a holistic therapist in Ireland?</h2>
				<div class="htn-post-find__links">
					<?php foreach ( $htn_links as $htn_l ) : ?>
						<a href="<?php echo esc_url( $htn_l['url'] ); ?>"><?php echo esc_html( $htn_l['name'] ); ?> <?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
					<?php endforeach; ?>
				</div>
			</aside>
		<?php else : ?>
			<aside class="htn-post-find">
				<p class="htn-nkicker">Find a practitioner</p>
				<h2>Looking for a holistic therapist in Ireland?</h2>
				<div class="htn-post-find__links"><a href="<?php echo esc_url( $htn_find ); ?>">Browse the directory <?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></div>
			</aside>
		<?php endif; ?>

		<div class="htn-evs-share">
			<span>Share</span>
			<a href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $htn_url ) ); ?>" target="_blank" rel="noopener">Facebook</a>
			<a href="<?php echo esc_url( 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( $htn_url ) ); ?>" target="_blank" rel="noopener">LinkedIn</a>
			<a href="<?php echo esc_url( 'https://wa.me/?text=' . rawurlencode( $htn_title . ' ' . $htn_url ) ); ?>" target="_blank" rel="noopener">WhatsApp</a>
			<a href="<?php echo esc_url( 'mailto:?subject=' . rawurlencode( $htn_title ) . '&body=' . rawurlencode( $htn_url ) ); ?>">Email</a>
		</div>

		<?php if ( comments_open() || get_comments_number() ) : ?>
			<div class="htn-post-comments">
				<?php comments_template(); ?>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $htn_rel ) : ?>
		<section class="htn-evs-more htn-post-more">
			<div class="htn-wrap">
				<h2 class="htn-evmonth__title"><span>Keep reading</span></h2>
				<ul class="htn-evgrid">
					<?php foreach ( $htn_rel as $htn_p ) : ?>
						<li>
							<a class="htn-evcard" href="<?php echo esc_url( get_permalink( $htn_p ) ); ?>">
								<span class="htn-evcard__img">
									<?php if ( has_post_thumbnail( $htn_p ) ) : ?>
										<?php echo get_the_post_thumbnail( $htn_p, 'medium_large', array( 'loading' => 'lazy', 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
									<?php else : ?>
										<img src="<?php echo esc_url( HTN_URL . 'assets/img/natural/feat-1.jpg' ); ?>" alt="" loading="lazy">
									<?php endif; ?>
								</span>
								<span class="htn-evcard__body">
									<span class="htn-evcard__title"><?php echo esc_html( html_entity_decode( get_the_title( $htn_p ), ENT_QUOTES, 'UTF-8' ) ); ?></span>
									<span class="htn-evcard__meta"><?php echo htn_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( get_the_date( 'j M Y', $htn_p ) ); ?></span>
									<span class="htn-ncard__more">Read <?php echo htn_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
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
