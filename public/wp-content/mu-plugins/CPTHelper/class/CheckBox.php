<?php
namespace CPTHelper;
use CPTHelper\FieldHelper;

//require_once("FieldHelper.php");

class CheckBox extends FieldHelper {

  public function rqValue(){
      // Booleans will be represented in the options db by 1 and 0
      return isset($_REQUEST[$this->id]) ? 1 : 0;
  }
  public function fieldDiv()
  {
      $id = $this->id;
      $checked = $this->get() ? " checked" : "";
      return '<input type="checkbox" id="'.$id.'" name="'.$id.'" class="metafield" value="1" '.$checked.'>'.$this->fieldExtra();
  }

}




?>
