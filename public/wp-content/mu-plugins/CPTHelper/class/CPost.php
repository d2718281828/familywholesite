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

    public $postid;
    protected $type = null;
    public $post = null;
    protected $cpthelper = null;
    protected $is_error = false;        // true if this object is in error and cannot be used
    protected $error_message = null;      // message to explain the problem.
    protected $post_properties = null;
    protected $props = [];              // cache requested properties

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
        } else {
            $this->is_error = true;
            $this->error_message = "Object constructed with invalid argument";
        }
        $this->post_properties = ["poat_title","post_content","post_author","post_name","post_title","post_date","post_date_gmt","post_excerpt","post_status",
            "comment_status","post_parent","post_modified","post_modified_gmt","comment_count","menu_order",];
    }

    /**
     * set the type and look up the helper for that type
     * @param $type
     */
    public function setType($type){
        $this->type = $type;
        $this->cpthelper = CptHelper::get($type);
    }

    /**
     * Client should test with this before use
     * @return bool
     */
    public function is_bad(){
        return $this->is_error;
    }

    /**
     * Get post property.
     * Dont use it for ID, type etc.
     * In keeping with the lazy loading, if this isnt a post property we will just use get_postmeta
     * @param $property
     */
    public function get($property){
        if ($this->is_error) return null;

        if (isset($this->props[$property])) return $this->props[$property];

        if (in_array($property,$this->post_properties)){
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
    */
    public function get_template_part($name = null){
      global $cpost;
      $savecpost = $cpost;
      $cpost = $this;
      get_template_part( 'template-parts/'.$this->type.'/content', $name );
      $cpost = $savecpost;
    }
    /**
    * A display box of the extra custom fieelds for this type.
    */
    public function infoBox(){
      return "Info";
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
    public function simpleLink(){
      $url = get_permmalink($this->postid);
      return '<a href="'.$url.'">'.$this->get("post_title").'</a>';
    }
    /**
    * Just give a quick summary of the Cpost, mainly for debugging
    */
    public function show(){
      return "CPost:".$this->postid."(".$this->type.")";
    }


}




?>
