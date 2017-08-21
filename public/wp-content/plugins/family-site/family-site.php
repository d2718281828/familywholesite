<?php

class FamilySite {

  public function __construct(){

    $this->setupCPTs();

  }

  protected function setupCPTs(){

    $z = new CptHelper("person", "Person", "People", [])
    ->addField(new FieldHelper("date_birth", "Date or Birth", "Date the person was born"))
    ;
  }


}

$family_site = new FamilySite();

 ?>
