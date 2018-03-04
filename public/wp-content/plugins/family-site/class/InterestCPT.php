<?php
namespace FamilySite;
use CPTHelper\CptHelper;
use CPTHelper\FieldHelper;
use CPTHelper\DateHelper;
use CPTHelper\CPTSelectHelper;
use CPTHelper\SelectHelper;
use CPTHelper\UseridSelector;
//use CPTHelper\MediaSelector2;
require_once("types/mediaType.php");

class InterestCPT extends FSCpt {

  public function setup(){
    parent::setup();
	$this
	      ->setClass("FamilySite\Interest")
        ->addField(new DateHelper("actual_date", "Actual date", "Date that the picture was actually taken"))
        ->addField(new FieldHelper("date_within", "Date within (days)", "The true date should be within this number of days of the date given"))
        ->addField(new FieldHelper("uploader_ref", "Uploader's reference", "The file name or folder and filename that the uploader can use to cross reference the picture"))
        ->addField(new mediaType("featured_media", "Featured Media file, doc, audio, video", "Associated media file if not image"))
		// featured_media_type	field set autmattically, doesnt appear in edit
        ->addField(new CPTSelectHelper("event", "Event", "", ["posttype"=>"fs_event"]))
    ;
	
	add_filter( 'the_content', [$this, 'add_final_content'] );
  }
  /**
  * Add the featured media link, or whole viewer if a PDF embedder is active, to the end of the post, if one is present.
  * We can assume that the file is not an image.
  * @return {string} Post content
  */
  public function add_final_content($content){
	  return $content.$this->exif().$this->loaderRef().$this->featured_media();
  }
  public function loaderRef(){
	  global $post;
	  if (!$post || !is_single()) return '';
	  if ($x = get_post_meta($post->ID,"uploader_ref",true)){
		return '<div class="loader-reference">Poster\'s reference: '.$x.'</div>';
	  }
	  return '';
  }
  /**
  * Add the featured media link, or whole viewer if a PDF embedder is active, to the end of the post, if one is present.
  * We can assume that the file is not an image.
  * @return {string} html
  */
  public function featured_media(){
	  global $post;
	  if ($post && is_single()){
		$postid = $post->ID;
		
		$mldoc = get_post_meta($postid,"featured_media",true);
		if (!$mldoc) return '';
		
		$url = wp_get_attachment_url($mldoc);
		
		// I thought about using mimetype functions but the mime types are just as complicated as file extensions.
		// here we're just picking out the ones we have special formatting for.
	    switch($this->filetype($url)){
		  case "jpg":
		  case "jpeg":
		  case "png":
		  case "gif":
		  case "jpeg":
		  return $this->imageBlock($url);
		  
		  case "pdf":
		  return $this->pdfBlock($url);
		  
		  case "ppt":
		  case "doc":
		  case "docx":
		  return $this->docBlock($url);
		  
		  case "mov":
		  case "mp4":
		  case "mpg":
		  
		  case "mp3":
		  case "ogg":
		  case "wav":
		  
		  default:
		  return $this->linkblock($url);
	    }
	  }
	  return '';
  }
  /**
  * PDF files viewer
  * @return {string} html
  */
  protected function pdfBlock($url){
	// Could support other pdf plugins potentially
	if (class_exists("core_pdf_embedder")){
		return '<div class="pdf-wrapper">[pdf-embedder url='.$url.']</div>';
	}
	return $this->linkblock($url);
  }
  /**
  * Microsoft viewer
  * @return {string} html
  */
  protected function docBlock($url){
	// uses embed any document plugin if it is there
	if (class_exists("Awsm_embed")){
		return '<div class="doc-wrapper">[embeddoc url="'.$url.'" download="all" viewer="microsoft"]</div>';
	}
	return $this->linkblock($url);
  }
  /**
  * Just a link to the file
  * @return {string} html
  */
  protected function linkBlock($url){
	return "<p><a href='$url'>Media File</a></p>";
  }
  /**
  * An image tag
  * @return {string} html
  */
  protected function imageBlock($url){
		return '<div class="image-wrapper"><img src="'.$url.'"></div>';
  }
  /**
  * Return the image EXIF data, if any has been stored
  * @return {string} html
  */
  public function exif(){
	  global $post;
	  if (!$post || !is_single()) return '';
	  $postid = $post->ID;
	  
	  $exif = get_post_meta($postid,"exif",true);
	  if (!$exif) return '';
	  
	  $m = "";
	  foreach ($exif as $prop=>$val){
		  $m.= $prop."=".$val." ";
	  }
	  return '<div class="exif-data">'.$m.'</div>';
  }
  protected function filetype($fname){
	  $dot = strrpos($fname,".");
	  if ($dot===false) return "";
	  return strtolower(substr($fname,$dot+1));
  }
}
 ?>
