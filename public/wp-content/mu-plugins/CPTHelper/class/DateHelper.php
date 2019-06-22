<?php
namespace CPTHelper;
use CPTHelper\FieldHelper;

// todo required and validation and CSS. is it even possible in WP admin?

class DateHelper extends FieldHelper {
    public function fieldDiv()
    {
        $id = $this->id;
        return '<input type="date" id="'.$id.'" name="'.$id.'" class="metafield" value="'.esc_attr($this->get()).'">'.$this->fieldExtra();
    }
}



?>
