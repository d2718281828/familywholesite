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
    protected $cpthelper;

    /**
     * CPost constructor.
     * @param $p int/WP_Post/numeric string Either a post object, or a post id.
     */
    public function __construct($p){

    }

    public function get($property){

    }
}




?>
