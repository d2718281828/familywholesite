<?php
namespace FamilySite;
use CPTHelper\CptHelper;
use CPTHelper\FieldHelper;
use CPTHelper\DateHelper;
use CPTHelper\CPTSelectHelper;
use CPTHelper\SelectHelper;
use CPTHelper\UseridSelector;
use CPTHelper\MediaSelector2;

class InterestCPT extends FSCpt {

  public function setup(){
    parent::setup();
	$this
	      ->setClass("FamilySite\Interest")
        ->addField(new DateHelper("actual_date", "Actual date", "Date that the picture was actually taken"))
        ->addField(new FieldHelper("uploader_ref", "Uploader's reference", "The file name or folder and filename that the uploader can use to cross reference the picture"))
        ->addField(new MediaSelector2("featured_pdf", "Featured PDF Document", "This document will be added to the post with a PDF viewer if active"))
        ->addField(new CPTSelectHelper("event", "Event", "", ["posttype"=>"fs_event"]))
    ;
	
	add_filter( 'the_content', [$this, 'add_pdf_viewer'] );
  }
  public function add_pdf_viewer($content){
	  global $post;
	  if ($post && is_single()){
		$postid = $post->ID;
		$mldoc = get_post_meta($postid,"featured_pdf",true);
		if ($mldoc){
			$pdf = wp_get_attachment_url($mldoc);
			// Could support other pdf plugins potentially
			if (class_exists("core_pdf_embedder")){
				$section = '<div class="pdf-wrapper">[pdf-embedder url='.$pdf.']</div>';
			} else {
				$section = "<p><a href='$pdf'>PDF</a></p>";
			}
			$content.=$section;
		}
	  }
	  return $content;
  }
  public function on_save_obs($post_id, $post){
    if (WP_DEBUG) error_log("in FamilySite::InterestCPT::on_save method");
	parent::on_save($post_id, $post);
	
	// TODO check it isnt editorial or help (although absence of actual date will also do the same)

	// refresh timeline info
	TimeLine::clearSource($post_id);
	
	$actual_date = "";
	if (isset($_REQUEST["actual_date"]) && $_REQUEST["actual_date"]) $actual_date = $_REQUEST["actual_date"];
	else {
		if (isset($_REQUEST["event"]) && $_REQUEST["event"]) {
			$event = (int)$_REQUEST["event"];
			$actual_date = get_post_meta($event, "actual_date", true);
		}
	}
    if (WP_DEBUG) error_log("Interest $post_id has date $actual_date");
	
	if ($actual_date){
		$interest = new Interest($post);
		$links = $interest->getLinks();
		foreach($links as $link){
			TimeLine::addInterest($actual_date, $post_id,  $post->post_type, $link->postid, $link->getType());
		}
	}
	
  }
}
 ?>
