<?php
namespace CPTHelper;
use CPTHelper\FieldHelper;

class SelectHelper extends FieldHelper {

    protected $selOptions = [];

    public function fieldDiv()
    {
        $this->setupOptions();
        $id = $this->id;
        $value = $this->get();

        $m = "<select id='$id' name='$id' >";
        foreach($this->selOptions as $option){
            $m.= "<option value='$option[0]'".($value==$option[0] ? " selected" : "").">".$option[1]."</option>";
        }
        $m.= "</select>";

        return $m.$this->fieldExtra();
    }
    /**
    * Set the $selOptions values based on some options - this is for overloaded classes.
    */
    public function setupOptions(){
    }
    public function addOption($value,$description){
        $this->selOptions[] = [$value,$description];
        return $this;
    }




}




?>
