<?php

namespace CPTHelper;
use CPTHelper\SelectHelper;

/**
* Temporary Media selector based on a post selector - media selector interferes with featured image
* It uses 2 options:
*   posttypes - a list of custom post types to search for
*   filetypes - a list of filetypes - only valid if the posttypes is ["attachment"].
*
*/
class MediaSelector2 extends SelectHelper {

    public function setupOptions(){
        global $wpdb;

        $posttypes = "post_type = 'attachment' and ";

        $ftype = "";

        $s = "select ID, post_title from ".$wpdb->posts." where $posttypes
              post_status in ('publish','inherit') $ftype order by post_date desc;";

        $this->selOptions = $wpdb->get_results($s, ARRAY_N);

        array_splice($this->selOptions,0,0,[[0, "None of these"]]);
    }
    public function fieldExtra(){
        $val = $this->get();
        if ($val) return '<img style="width: 80px;" src="'.wp_get_attachment_url($val).'">';
        return "";
    }
}




?>
