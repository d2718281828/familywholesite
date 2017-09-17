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
        ->addField(new FieldHelper("email_address", "Email Address", "How to contact this person"))
        ->addField(new DateHelper("date_birth", "Date of Birth", "Date the person was born"))
        ->addField(new CPTSelectHelper("place_birth", "Place of Birth", "Place the person was living in immediately after birth", ["posttype"=>"fs_place"]))
        ->addField(new DateHelper("date_death", "Date of Death", "Date the person was born"))
        ->addField(new CPTSelectHelper("place_death", "Place of Death", "Place the person was living when they died", ["posttype"=>"fs_place"]))
        ->addField(new CPTSelectHelper("father", "Father", "", ["posttype"=>"fs_person"]))
        ->addField(new CPTSelectHelper("mother", "Mother", "", ["posttype"=>"fs_person"]))
        ->addField(new CPTSelectHelper("spouse", "Spouse", "Spouse, of current marriage. This only has to be specified for one spouse.", ["posttype"=>"fs_person"]))
        ->addField(new CPTSelectHelper("place_marriage", "Place of Marriage", "Place of the wedding to current spouse", ["posttype"=>"fs_place"]))
        ->addField(new DateHelper("date_marriage", "Date of marriage", "Date of current or only marriage"))
        ->addField((new SelectHelper("gender", "Gender", "" ))
            ->addOption("M","Male")
            ->addOption("F","Female")
        )
        ->addField(new FieldHelper("birthname", "Birth Name", "Full name at birth (maiden name for ladies)"))
        ->addField(new FieldHelper("occupation", "Occupation", "Main occupation"))
        ->addField(new UseridSelector("userid", "Login id", "Link to the person's login id, if they have one"))
        ->addField(new DateHelper("date_baptism", "Date of Baptism", "This features in a few geneological records"))
    ;
  }
  protected function on_save($post_id, $post){
    if (WP_DEBUG) error_log("in FamilySite::PersonCPT::on_save method");
	parent::on_save($post_id, $post);

	// refresh timeline info
	TimeLine::clearSource($post_id);
	
	if (isset($_REQUEST["date_birth"]) && $_REQUEST["date_birth"]){
		$place = $_REQUEST["place_birth"] ?: 0;
		TimeLine::add1($_REQUEST["date_birth"], $post_id, "BORN", $place, 0);
	}
	if (isset($_REQUEST["date_death"]) && $_REQUEST["date_death"]){
		$place = $_REQUEST["place_death"] ?: 0;
		TimeLine::add1($_REQUEST["date_death"], $post_id, "DIED", $place,0 );
	}
	if (isset($_REQUEST["date_marriage"]) && $_REQUEST["date_marriage"]){
		$place = $_REQUEST["place_marriage"] ?: 0;
		$spouse = $_REQUEST["spouse"] ?: 0;		// so you can record that someone married without saying who to!
		TimeLine::addMarriage($_REQUEST["date_marriage"], $post_id, $post_id, $spouse,$place,0);
		if ($spouse){
			TimeLine::addMarriage($_REQUEST["date_marriage"], $post_id, $spouse, $post_id,$place,0);
		}
	}
  }
}
 ?>
