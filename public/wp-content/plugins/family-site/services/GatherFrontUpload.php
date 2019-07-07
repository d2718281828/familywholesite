<?php
namespace FamilySite;
require_once(dirname(__FILE__)."/../class/Interest.php");
require_once(dirname(__FILE__)."/DateRange.php");
//use CPTHelper\CptHelper;

// If the Front End Uploader is in use, then this module will attach the uploaded media file to the post
/* Expect a form definition something like this
[fu-upload-form class="html-wrapper-class"
form_layout="post_media" title="Upload your media"]
[input type="text" name="post_title" id="title"
class="required" description="Title"]
[textarea name="post_content" class="textarea"
id="my-textarea" description="Description (optional)"]
[input type="file" name="photo" id="my-photo-submission"
class="required" description="Your Photo" multiple="multiple"]
[input type="submit" class="btn" value="Submit"]
[/fu-upload-form]

_wp_attachment_metadata examples - not reliable
[image_meta][created_timestamp] => 	920185762; 2002/10/15 = 1034683200
Maybe the time on the camera was wrong
[camera] => KODAK DC240 ZOOM DIGITAL CAMERA

Use exif:
[DateTime] => 2005:10:07 21:44:32
[DateTimeOriginal] => 2005:08:24 19:36:22



*/

class GatherFrontUpload {

  /**
  * Constructor - assume this is instantiated in wp_init
  */
  public function __construct(){
	  $this->pictypes = ["jpg","jpeg","png","gif","svg","bmp"];

	  error_log("adding the fu action");
	  add_action("fu_after_upload", [$this, "gather"],10,3); // signal 3 arguments
  }

  /**
  * Find all interest items with this date and add them. The argument must be an event
  * For now we just handle a single attachment
  */
  public function gather($attachment_ids, $success, $post_id){
	  error_log("Front End after upload ".$post_id." ".print_r($attachment_ids,true));
	  if (count($attachment_ids)==0) return;
	  $this->attach($attachment_ids[0],$post_id);
	  if (count($attachment_ids)>1) {
		  error_log("WARNING, some media items not attached - ".implode(",",$attachment_ids));
	  }
  }
  protected function attach($media, $post){
	  $newItem = new Interest($post);
	  
	  // did the  user specify a date
	  $udate = $newItem->get("user_date");
	  $haveuserdate = false;
	  if ($udate){
		  error_log("user date specified ".$udate);
		  $dr = new DateRange($udate);
		  $newItem->set("actual_date",$dr->mid);
		  $newItem->set("date_within",$dr->within);
		  error_log("--- which is  ".$dr->show());
		  $haveuserdate = true;
	  }
	  //$ureq = $_REQUEST["user_date"];
	  //error_log("user date with post meta ",$udate.", with request ".$ureq);
	  
	  // is it a picture?
	  $url = wp_get_attachment_url($media);
	  if (!$url) return;
	  $lastdot = strrpos($url,".");
	  if ($lastdot===false) return;
	  $type = strtolower(substr($url,$lastdot+1));
	  
	  error_log("Attaching ".$media." to ".$post);
	  
	  if (array_search($type,$this->pictypes)===false) {
		  error_log("setting featured media");
		  $newpost->set("featured_media", $media);
	  } else {
		  error_log("setting post thumbnail");
		  set_post_thumbnail($post, $media);
		  if (!$haveuserdate){
			  // does the picture have a date?
			  //tried _wp_attachment_metadata but it didnt have dates for images
			  $exif = exif_read_data(get_attached_file($media)); 
			  if (!$exif) return;
			  //error_log("exif for ".$media." = ".print_r($exif,true));
			  $exifDT = array_key_exists("DateTime", $exif) ? $exif["DateTime"] : null;
			  if (!$exifDT) $exifDT = array_key_exists("DateTimeOriginal", $exif) ? $exif["DateTimeOriginal"] : null;
			  if ($exifDT){
				  error_log("Setting actual date to be ".$this->formatExifDate($exifDT));
				  $newItem->set("actual_date", $this->formatExifDate($exifDT));
			  }
		  }
	  }
	  
  }
  /**
  * convert from exif format '2005:08:24 19:36:22'
  */
  protected function formatExifDate($exifdt){
	  return str_replace(":","-",substr($exifdt,0,10));
  }

}
