<?php
namespace FamilySite;
//use CPTHelper\CptHelper;

// If the Front End Uploader is in use, then this will attach the uploaded media file to the post
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

  public function __construct(){
	  add_action("wp_init", [$this, "init"]);
  }
  public function init(){
	  add_action("fu_after_upload", [$this, "gather"]);
  }

  /**
  * Find all interest items with this date and add them. The argument must be an event
  */
  public function gather($attachment_ids, $success, $post_id){
	  error_log("Front End after upload ".$post_id." ".print_r($attachment_ids,true));
  }

}
