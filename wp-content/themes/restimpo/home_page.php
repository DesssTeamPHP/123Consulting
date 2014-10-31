<?php
/**
 * Template Name: Home Page 
 * @package RestImpo
 * @since RestImpo 1.0.0
*/
get_header(); ?>

<div id="wrapper-content">
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
  
  <div class="container">
  <div id="main-content">
    <article id="content">
      <div class="post-thumbnail"><?php restimpo_get_display_image_page(); ?></div> 

    <div class="content-headline">
      <h1><?php the_title(); ?></h1>
    </div>     
      <div class="entry-content">
<?php the_content(); ?>
<?php edit_post_link( __( '(Edit)', 'restimpo' ), '<p>', '</p>' ); ?>
      </div>
<?php endwhile; endif; ?>
<?php comments_template( '', true ); ?>
    </article> <!-- end of content -->
  </div>
<?php get_sidebar(); ?>
</div>
</div> 
     
<div class="spacer"></div>
	<div class="wrapper-content homepage-heading">
    	<div class="container">
			<h3 class="">Flexible solutions fit your needs</h3>
    	</div>   
        <div class="spacer"></div>   
	</div>
    <div class="spacer"></div>  
    <div class="wrapper-content bg-color">
    	<div class="container">
        	<div class="home-content-left">
            	<img src="http://desss-portfolio.com/123consulting/wp-content/uploads/2014/10/cloud2.png" alt="" title="" />
				<h3>Flexible solutions fit your needs</h3>
                <p>Take your business anywhere. Our affordable EZClaim cloud-based medical billing software solution allows you to perform all of your tasks from any internet connected computer.</p>
                <h4>PLANS START AT $49/month</h4>
                <p><a href="#">Learn More</a></p>
                <br />
            </div>
            <div class="or">
            	<p> <img src="http://desss-portfolio.com/123consulting/wp-content/uploads/2014/10/OR.png" alt="" title="" /></p>
            </div>
            <div class="home-content-right">
            <img src="http://desss-portfolio.com/123consulting/wp-content/uploads/2014/10/Computer-icon.png" alt="" title="" />
				<h3 class="">Flexible solutions fit your needs</h3>
                <p>Prefer to have EZClaim medical billing software on your desktop computer? EZClaim is simple to download and easy to install – you can begin processing your first claim within 30 minutes.</p>
                <h4>PLANS START AT $395</h4>
                <p><a href="#">Learn More</a></p>
                <br />
            </div>
            <br class="spacer" />   
    	</div>
        <br class="spacer"> 
	</div>

<!-- end of wrapper-content -->
<?php get_footer(); ?>