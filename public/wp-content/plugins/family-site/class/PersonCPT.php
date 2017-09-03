<?php
namespace FamilySite;
use CPTHelper\CptHelper;
use CPTHelper\FieldHelper;
use CPTHelper\DateHelper;
use CPTHelper\CPTSelectHelper;
use CPTHelper\SelectHelper;
use CPTHelper\UseridSelector;

class PersonCPT extends FSCpt {

  public function setup(){
    parent::setup();
	$this
        ->set_taxonomy("person_tax")
        ->setClass("FamilySite\Person")
        ->addField(new DateHelper("date_birth", "Date of Birth", "Date the person was born"))
        ->addField(new CPTSelectHelper("place_birth", "Place of Birth", "Place the person was living in immediately after birth", ["posttype"=>"fs_place"]))
        ->addField(new DateHelper("date_death", "Date of Death", "Date the person was born"))
        ->addField(new CPTSelectHelper("place_death", "Place of Death", "Place the person was living when they died", ["posttype"=>"fs_place"]))
        ->addField(new CPTSelectHelper("father", "Father", "", ["posttype"=>"fs_person"]))
        ->addField(new CPTSelectHelper("mother", "Mother", "", ["posttype"=>"fs_person"]))
        ->addField(new CPTSelectHelper("spouse", "Spouse", "Spouse, of current marriage", ["posttype"=>"fs_person"]))
        ->addField(new CPTSelectHelper("place_marriage", "Place of Marriage", "Place of the wedding to current spouse", ["posttype"=>"fs_place"]))
        ->addField(new DateHelper("date_marriage", "Date of marriage", "Date of current or only marriage"))
        ->addField((new SelectHelper("gender", "Gender", "" ))
            ->addOption("M","Male")
            ->addOption("F","Female")
        )
        ->addField(new FieldHelper("birthname", "Birth Name", "Full name at birth (maiden name for ladies)"))
        ->addField(new UseridSelector("userid", "Login id", "Link to the person's login id, if they have one"))
    ;
  }
}
 ?>
