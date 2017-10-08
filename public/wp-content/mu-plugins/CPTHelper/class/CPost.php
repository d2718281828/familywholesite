<?php
namespace CPTHelper;

/**
 * Class CPost is for a single instance of a custom post.
 * It stores not just the post instance but also the CptHelper object which contains the extended field descriptions
 * It is a good place (to extend) to put logic relating to a particular CPT.
 * @package CPTHelper
 */
// try to make this as lazy as possible. we can get custom fields without ever loading the post object, for example.
class CPost {

	static public $post_properties = ["post_title","post_content","post_author","post_name","post_type","post_date","post_date_gmt","post_excerpt","post_status",
            "comment_status","post_parent","post_modified","post_modified_gmt","comment_count","menu_order",];

    public $postid = -1;		// -1 means it is a CPost that has not been instantiated
    protected $type = null;
    public $post = null;
    protected $cpthelper = null;
    protected $is_error = false;        // true if this object is in error and cannot be used
    public $error_message = null;      // message to explain the problem.
    protected $props = [];              // cache requested properties
    protected $pends = [];              // Properties of a post which hasnt been created yet. Post fields and custom fields together

    /**
     * CPost constructor.
     * @param $p int/WP_Post/numeric string Either a post object, or a post id.
     */
    public function __construct($p){
        if (is_object($p)) {
            $this->post = $p;
            $this->postid = $p->ID;
            $this->setType($p->post_type);
        } elseif (is_numeric($p)){
            $this->postid = (int)$p;
        } elseif (is_array($p)){
            $this->pends = $p;
            $this->setType($p["post_type"]);
        } else {
            $this->is_error = true;
            $this->error_message = "Object constructed with invalid argument";
        }
    }

    /**
     * set the type and look up the helper for that type
     * @param $type
     */
    public function setType($type){
        $this->type = $type;
        $this->cpthelper = CptHelper::get($type);
    }
    public function getType(){
        return $this->type;
    }

    /**
     * Client should test with this before use
     * @return bool
     */
    public function is_bad(){
        return $this->is_error;
    }

    /**
     * Driven whenever the underlying post is created or changed - to maintain the consistency of any links
	 * Postid will be set when driven
	 * @param $req int If 1, custom field data is in $_REQUEST. If 0,it is in $props. If 2, you need to go back to postmeta. 
	 * Children should use getcf($req) for each data value they need
     * @return 
     */
    public function on_update($req = 0){
		if (WP_DEBUG) error_log("CPOST::on_update for ".$this->postid);
			
    }

    /**
     * Driven whenever the underlying post is destroyed - to tidy up
	 * Postid will be set when driven
	 * @param $data array should be an array of any custom field values. not sure if I need this.
     * @return 
     */
    public function on_destroy(){
		if (WP_DEBUG) error_log("CPOST::on_delete for ".$this->postid);
    }

    /**
     * Get post property.
     * Dont use it for ID, type
     * In keeping with the lazy loading, if this isnt a post property we will just use get_postmeta
     * @param $property
     */
    public function get($property){
        if ($this->is_error) return null;

        if (isset($this->props[$property])) return $this->props[$property];

        if (in_array($property,self::$post_properties)){
            if ($this->post===null) {
                $this->post = get_post($this->postid);
                if ($this->post===null){
                    $this->is_error = true;
                    $this->error_message = "There is no post number ".$this->postid;
                    return null;
                }
                $this->setType($this->post->post_type);
            }
            return $this->post->$property;
        }
        $cph = $this->getCPH();

        $val = get_post_meta($this->postid, $property, true);
        // todo transform it
        $this->props[$property] = $val;
        return $val;
    }
	public function set($property,$value){
        if ($this->is_error) return;
        if (in_array($property,self::$post_properties)){
			$rc = wp_update_post(["ID"=>$this->postid, $property=>$value], true);
		} else {
			$this->props[$property] = $value;	// save it
			update_post_meta($this->postid, $property, $value);
		}
		
	}

    public function getCPH(){
        if ($this->is_error) return null;
        if ($this->cpthelper) return $this->cpthelper;

        if ($this->type===null) $this->type = get_post_type($this->postid);

        if ($this->type===null) {
            $this->is_error = true;
            $this->error_message = "Post ".$this->postid." has no type";
            return null;
        }
        $this->setType($this->type);
        return $this->cpthelper;
    }
    public function permalink(){
      return get_permalink($this->postid);
    }
    /**
    * call get_template_part with the appropriate type. Also set a context of cpost
    * The output is echoed, of course
    */
    public function get_template_part($name = null){
      global $cpost;
      $savecpost = $cpost;
      $cpost = $this;
      get_template_part( 'template-parts/'.$this->type.'/content', $name );
      $cpost = $savecpost;
    }
    /**
    * Do we have an infobox?
    */
    public function hasInfoBox(){
      return true;
    }
    /**
    * A display box of the extra custom fieelds for this type.
    */
    public function infoBox(){
      return "Info";
    }
    /**
    * Create a new post based on the info which was previously supplied. If postid already set then update.
	* In either case $pends is the data to change.
	* @return the return code from wp_insert_post
    */
    public function create(){
	  if (!$this->pends) return;	// no info so cant.

	  $postnew = [];
	  $meta = [];
	  foreach ($this->pends as $prop=>$val){
		  if (in_array($prop, self::$post_properties)) $postnew[$prop] = $val;
		  else $meta[$prop] = $val;
	  }
	  if ($meta) $postnew["meta_input"] = $meta;
	  $postnew["post_status"] = "publish";

	  // validate the postnew???
	  $rc = wp_insert_post($postnew, true);
	  if (is_wp_error($rc)){
		  $this->is_error = true;
		  $this->error_message = "CREATE ERROR: ".$rc->get_error_message();
		  return false;
	  }
	  $this->postid = $rc;
	  $this->props = $meta;
	  $this->pends = [];
	  $this->on_update(0);
	  return $rc;
    }
	/**
	* Destroy the post and any related bits
	*/
	public function destroy(){
		$this->on_destroy();		// tidy up
		wp_delete_post($this->postid, true);
		$this->is_error = true;		//signal that it is no longer usable
		$this->postid = -1;
		$this->error_message = "Deleted";
	}
    /**
    * Component of the info box.
    */
    protected function infoBit($head,$text){
      return '<div class="info-bit"><div class="info-head">'.$head.'</div><div class="info-body">'.$text.'</div></div>';
    }
    /**
    * Return a simple a tag linked to the permalink, with text which is the post title.
    */
    public function simpleLink($text = null){
      $url = get_permalink($this->postid);
      return '<a href="'.$url.'">'.($text ?: $this->get("post_title")).'</a>';
    }
	/**
	* Return title plus image, linked.
	*/
    public function link(){
      $url = get_permalink($this->postid);
      $m = '<div class="title">'.$this->get("post_title").'</div>';
      $img = get_the_post_thumbnail($this->postid);
      $m.= '<div class="thumb">'.$img.'</div>';
      $m = '<a href="'.$url.'">'.$m.'</div>';
      return $m;
    }
    /**
    * Just give a quick summary of the Cpost, mainly for debugging
    */
    public function show(){
		$cl = get_class($this);
		if ($this->is_error) return $cl.":BAD ".$this->error_message;
		if ($this->postid<0) return $cl.":NEW:".$this->pends["post_name"]."(".$this->type.")";
		$m = $cl.($this->post ? ":".$this->post->post_title : "").":[".$this->postid."](".$this->type.")";
        return $m;
    }
	public function showAllPend(){
		$m = "<h3>".$this->pends["post_title"]."</h3>";
		foreach($this->pends as $prop=>$pendval){
			$m.= "<p><strong>".$prop."</strong> ".htmlentities($pendval)."</p>";
		}
		return $m;
	}
	/**
	* A fuller description of the Cpost.  however it doesnt get postmeta, just values in the structure
	*/
	public function showAll(){
		$m = "<h4>".($this->post ? $this->post->post_title : $this->type."-".$this->postid) . "</h4>";
		$m.= "<p><strong>ID=".$this->postid."</strong>, type".$this->type."</p>";
		foreach($this->props as $prop=>$pendval){
			$m.= "<p><strong>".$prop."</strong> ".(is_string($pendval) ? htmlentities($pendval) : print_r($pendval,true))."</p>";
		}
		return $m;
	}
	/**
	* Internal function to get a custom field value from props or from REQUEST or from postmeta
	* @param $req int If 1, custom field data is in $_REQUEST. If 0,it is in $props. If 2, you need to go back to postmeta.
	*/
	protected function getcf($req,$prop,$default = null){
		switch($req){
			case 0:
			return (isset($this->props[$prop])) ? $this->props[$prop] : $default;
			case 1:
			return (isset($_REQUEST[$prop])) ? $_REQUEST[$prop] : $default;
			case 2:
			default:
			return $this->get($prop);
		}
	}
}




?>
