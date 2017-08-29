<?php
namespace FamilySite;

// represents an item of interest - an ordinary post, most likely a picture
class Interest extends FSPost {

  protected $taxes = [];

  /**
  * Do we have an infobox?
  */
  public function hasInfoBox(){
    return false;
  }
  public function infoBox() {
    return "";
  }

}
