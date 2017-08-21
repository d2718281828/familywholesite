<?php
namespace FamilySite;
use CPTHelper\CPTHelper;
/*
Plugin Name: Family Site
Plugin URI:
Description: Family Site
Author: Derek Storkey
Version: 0.1
Author URI:
*/
class FamilySite {

  public function __construct(){

    $this->setupCPTs();

  }

  protected function setupCPTs(){

    $z = (new CptHelper("person", "Person", "People", []))
        ->addField(new FieldHelper("date_birth", "Date or Birth", "Date the person was born"))
    ;
  }


}

$family_site = new FamilySite();

 ?>
