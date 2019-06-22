<?php
/*
Plugin Name: Slick Slider implementation
Plugin URI: http://www.flipsidegroup.com
Description: Provides slickslider shortcode, using the popular jQuery Slick Slider, also a function call for templates
Author: Derek Storkey
Author URI:
Version: 2.2
Text Domain: slickslider
License:
*/

// this is designed to be a framework to change - it isnt a bells and whistles customisable thing.
// It will certainly be necessary to adjust the responsive breaks and parameters.
// settings described here: http://kenwheeler.github.io/slick/
function slikslid_make_inline_scroller($id){
    $script = '(function($){

    $(document).ready(function(){
        $(\'.horiz-scroller-'.$id.'\').slick({
            prevArrow: ".arrow.prev.arrow-number-'.$id.'",
            nextArrow: ".arrow.next.arrow-number-'.$id.'",
            infinite: true,
            slidesToShow: 1,
            slidesToScroll: 1,
            centerMode: false,
			autoplay: true,
			autoplaySpeed: 3000,
            adaptiveHeight: true,
             responsive: [
                {
                  breakpoint: 768,
                  settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                  }
                }
              ],
        });
    });

})(jQuery);';

    return "<script>\n".$script."</script>\n";
}

function slick_slider_enqueue_function(){
    $url = plugin_dir_url( __FILE__ );
    wp_enqueue_style( 'slick_slider_css',  $url.'slick-1.8.0/slick/slick.css' , [], '1.0' );
    wp_enqueue_script( 'slick_slider',  $url.'slick-1.8.0/slick/slick.min.js' , array( 'jquery' ), '2.1.2', true );
}
add_action("wp_enqueue_scripts", "slick_slider_enqueue_function");

function slick_slider_shortcode($atts, $content, $tag){
    $a = shortcode_atts([
        "id"=>"1",
    ], $atts);
    $id = $a["id"];
    $m = "<div class='slider-slide horiz-scroller-$id'>".do_shortcode($content)."</div>";
    $m = "<div class='arrow prev arrow-number-$id'>&lt;</div>".$m."<div class='arrow next arrow-number-$id'>&gt;</div>";
    $m = "<div class='slider-container'>".$m."</div>";
    $m = slikslid_make_inline_scroller($id).$m;
    return $m;
}
add_shortcode('slickslider', 'slick_slider_shortcode');

/**
* create a slider displaying posts from wp_query using the given template. This is a function for putting in a template.
* @params $id integer just an id for distinguishing multiple sliders
*/
function slick_slider_query($id, $queryargs, $slug, $name){
	echo slikslid_make_inline_scroller($id);
    echo "<div class='slider-container'>";
    echo "<div class='arrow prev arrow-number-$id'>&lt;</div>";
	echo "<div class='arrow next arrow-number-$id'>&gt;</div>";
    echo "<div class='slider-slide horiz-scroller-$id'>";
	
	$wpq = new WP_Query($queryargs);
	if ($wpq->have_posts()){
		while ($wpq->have_posts()) {
			$wpq->the_post();
			get_template_part( $slug, $name );
		}
	}
	
	echo "</div>";
	echo "</div>";
	wp_reset_query();
}
