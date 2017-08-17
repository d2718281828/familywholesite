<?php

namespace CPTHelper;
use CPTHelper\FieldHelper;

/**
* This setting is shown on a post, but it is actually a sitye-wide setting
*/
class SiteField extends FieldHelper {

    public function update($post_id)
    {
        if (TRACEIT) traceit("SITEWIDE UPDATE for post=".$post_id);
        if ($post_id){
            update_option($this->id, $this->setValue($this->rqValue()));
        }
    }
    public function get()
    {
        if ($this->valueIsSet) return $this->value;
        global $post;
        return $this->setValue(get_option($this->id)) ;
    }


}




?>
