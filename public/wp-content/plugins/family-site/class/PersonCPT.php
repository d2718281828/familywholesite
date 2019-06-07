<?php
namespace FamilySite;
use CPTHelper\CptHelper;
use CPTHelper\FieldHelper;
use CPTHelper\DateHelper;
use CPTHelper\CPTSelectHelper;
use CPTHelper\SelectHelper;
use CPTHelper\UseridSelector;

require_once("Marriages.php");

class PersonCPT extends FSCpt {

  public function setup(){
    parent::setup();
	$this
        ->set_taxonomy("person_tax")
		->addToQueries(["category"])
		->allowExcerpt()
        ->setClass("FamilySite\Person")
        ->addField(new DateHelper("date_birth", "Date of Birth", "Date the person was born"))
        ->addField(new CPTSelectHelper("place_birth", "Place of Birth", "Place the person was living in immediately after birth", ["posttype"=>"fs_place"]))
        ->addField(new DateHelper("date_death", "Date of Death", "Date the person died"))
        ->addField(new CPTSelectHelper("place_death", "Place of Death", "Place the person was living when they died", ["posttype"=>"fs_place"]))
        ->addField(new CPTSelectHelper("father", "Father", "", ["posttype"=>"fs_person"]))
        ->addField(new CPTSelectHelper("mother", "Mother", "", ["posttype"=>"fs_person"]))
        ->addField(new CPTSelectHelper("spouse", "Spouse", "Spouse, of current marriage. This only has to be specified for one spouse.", ["posttype"=>"fs_person"]))
        ->addField(new CPTSelectHelper("place_marriage", "Place of Marriage", "Place of the wedding to current spouse", ["posttype"=>"fs_place"]))
        ->addField(new DateHelper("date_marriage", "Date of marriage", "Date of start of current or only marriage"))
        ->addField((new SelectHelper("gender", "Gender", "" ))
            ->addOption("M","Male")
            ->addOption("F","Female")
        )
        ->addField(new FieldHelper("birthname", "Birth Name", "Full name at birth (maiden name for ladies)"))
        ->addField(new FieldHelper("occupation", "Occupation", "Main occupation"))
        ->addField(new UseridSelector("userid", "Login id", "Link to the person's login id, if they have one"))
        ->addField(new FieldHelper("email_address", "Email Address", "How to contact this person - not needed if the login id is set."))
        ->addField(new DateHelper("date_baptism", "Date of Baptism", "This features in a few geneological records"))
        ->addField(new Marriages("prior_marriages", "Previous Marriages", "Previous marriage detais. This has to be added for both spouses."))
    ;
  }
  protected function list_heading(){
	return "<th>Person</th><th>DOB</th><th>reference</th>";
  }
  protected function list_row($cpost){
	$url = $cpost->permalink();
	$m = '<td><a href="'.$url.'">'.$cpost->get("post_title").'</a></td>';
	$m.= '<td>'.$cpost->get("date_birth").'</td>';
	$m.= '<td>[person '.$cpost->get("post_name").']</td>';
	return $m;
  }
}
 ?>
