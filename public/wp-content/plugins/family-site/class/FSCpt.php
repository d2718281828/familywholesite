<?php
namespace FamilySite;
use CPTHelper\CptHelper;

class FSCpt extends CptHelper {

  protected function setup() {
    $this->prefix = "fs_";
  }
  protected function on_save(){
    
  }

}


 ?>
