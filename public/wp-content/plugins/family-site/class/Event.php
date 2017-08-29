<?php
namespace FamilySite;

// represents an instance of the Person CPT
class Event extends FSPost {

  protected $taxes = [];

  public function actualDate(){
    $actdate = $this->get("actual_date");
    if ($actdate){
      return $actdate;
    }
    return 'Event undated';
  }


}
