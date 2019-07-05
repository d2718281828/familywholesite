<?php
namespace FamilySite;
require_once("../class/Interest.php");
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
  }
  protected attach($media, $post){
	  // we have to find out what it is
	  $url = wp_get_attachment_url($media);
	  if (!$url) return;
	  $lastdot = strrpos($url,".");
	  if ($lastdot===false) return;
	  $type = substr($url,$lastdot+1);
	  
	  $newItem = new Interest($post);
	  
	  if (array_search($type,$this->pictypes)===false) {
		  error_log("setting featured media");
		  $newpost->set("featured_media", $media);
	  } else {
		  error_log("setting post thumbnail");
		  set_post_thumbnail($post, $pic);
		  // does the picture have a date?
		  $meta = get_post_meta($pic,"_wp_attachment_metadata");
		  error_log("attachment meta for ".$pic." = ".print_r($meta,true));
		  
	  }
	  
  }

}
