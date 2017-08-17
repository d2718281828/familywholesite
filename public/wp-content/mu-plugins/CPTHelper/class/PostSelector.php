<?php

namespace CPTHelper;
use CPTHelper\SelectHelper;

/**
* Extend a Select Helper to provide a drop-down list of post titles.
* It uses 2 options:
*   posttypes - a list of custom post types to search for
*   filetypes - a list of filetypes - only valid if the posttypes is ["attachment"].
*
*/
class PostSelector extends SelectHelper {

    public function setupOptions(){
        global $wpdb;

        if (isset($this->options["posttypes"])){
            $posttypes = $this->options["posttypes"];
            $posttypes = "post_type in ('".implode("','",$posttypes)."') and ";
        } else $posttypes = "";

        if (isset($this->options["filetypes"])){
            $ftype = "";
            $or = "";
            foreach($this->options["filetypes"] as $type){
                $ftype = $or." guid like '%.$type'";
                $or = " or ";
            }
            $ftype = " and ($ftype) ";
        } else $ftype = "";

        $s = "select ID, post_title from ".$wpdb->posts." where $posttypes
              post_status in ('publish','inherit') $ftype order by post_title;";

        $this->selOptions = $wpdb->get_results($s, ARRAY_N);

        array_splice($this->selOptions,0,0,[[0, "None of these"]]);
    }

}




?>
