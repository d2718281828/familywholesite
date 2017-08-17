<?php
namespace CPTHelper;
use CPTHelper\FieldHelper;

// require_once("FieldHelper.php");

class TextAreaHelper extends FieldHelper {

    public function fieldDiv()
    {
        $id = $this->id;
        return '<textarea id="'.$id.'" name="'.$id.'" class="metafield">'.htmlspecialchars($this->get()).'</textarea>'.$this->fieldExtra();
    }

}




?>
