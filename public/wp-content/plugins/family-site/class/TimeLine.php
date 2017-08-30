<?php

class TimeLine {

  protected $focus = null;

  public function __construct($focus = null){
    $this->focus = $focus;
  }
  public function html(){
    return "timeline here";
  }
}
 ?>
