<?php
namespace CPTHelper;
use CPTHelper\SelectHelper;

//require_once("SelectHelper.php");

class CPTSelectHelper extends SelectHelper {

    /**
    * Set the $options values as all the embers of a particular custom post type
    */
    public function setupOptions(){
        global $wpdb;
        if (!isset($this->options["posttype"])) return;

        $s = "select ID, post_title from $wpdb->posts where post_type=%s";
        $res = $wpdb->get_results($ps=$wpdb->prepare($s,$this->options["posttype"]),ARRAY_A);

        $this->selOptions[] = [0,"None"];
        foreach ($res as $row){
            $this->selOptions[] = [$row["ID"],$row["post_title"]];
        }
    }




}




?>
