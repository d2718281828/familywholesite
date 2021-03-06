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
class UseridSelector extends SelectHelper {

    public function user_can_update(){
      return current_user_can("promote_users"); // sample admin capability
    }
    public function setupOptions(){
        global $wpdb;

        $s = "select ID, user_nicename from ".$wpdb->users." order by user_nicename;";

        $this->selOptions = $wpdb->get_results($s, ARRAY_N);

        array_splice($this->selOptions,0,0,[[0, "No Login"]]);
    }

}




?>
