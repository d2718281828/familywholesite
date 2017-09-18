<?php
/*
Plugin Name: Ent Loader
Plugin URI:
Description: Loading stuff exported from the ent library
Author: Derek Storkey
Version: 0.1
Author URI:
*/

namespace EntLoader;
use CPTHelper\CptHelper;
use CPTHelper\FieldHelper;
use CPTHelper\DateHelper;
use CPTHelper\CPTSelectHelper;
use CPTHelper\SelectHelper;
require_once("class/Ent.php");
require_once("class/EntCPost.php");

class EntLoader {
	
	protected $input;
	protected $numfiles = 0;
	protected $set = [];		// Ent objects read from the node files
	protected $cposts = [];		// CPost objects, before creation
	protected $report = "";
	protected $newplaces = [];

  public function __construct(){
	  add_action("init", [$this,"init"]);
	  $up = wp_upload_dir();
	  $this->input = $up["basedir"]."/nodes";
  }
  public function init(){
	  if (is_admin()) $this->wp_init();
  }
  protected function wp_init(){
	  include("class/EntLoadHelp.php");
	  $admin = new EntLoadHelp($this);
  }
  protected function loadStart(){
	  $this->numfiles = 0;	 
	  $this->set = [];
  }
  //phase 1 - from nodes files to CPost pre-entries
  public function load($dir = null){
	  if ($dir){
		  $ddir = $dir;
	  } else {
		  $ddir = "";
		  $this->loadStart();
	  }
	  $m = "<ul>";
	  
	  $adir = $this->input.'/'.$ddir;
	  
	  if (WP_DEBUG) error_log("Loading Ents from ".$adir);
	  $list = scandir($adir);
	  foreach ($list as $fil){
		  if ($fil=='.' || $fil=='..') continue;
		  $full = $adir.'/'.$fil;
		  if (is_dir($full)){
			  $m.=$this->load($full);
		  } else {
			  $z = new Ent($fil, $ddir, $adir);
			  $this->set[$z->key()] = $z;
			  $m.= "<li>".$z->show()."</li>";
		  }
	  }
	  $m.="</ul>";
	  $this->report["load"] = $m;
	  
	  // pre-filtering
	  $this->get("violet")->setMale(false);
	  $this->setAncs("paulinst");
	  //$this->setDescs("violet",5);
	  $this->setDescs("anc5",5);
	  $this->setDescs("ans1",5);
	  $this->setGenders();
	  $this->wantedEvents();
	  
	  foreach($this->set as $id=>$obj) $obj->reorg();
	  
	  $this->build();
	  $this->phase1();		// initial WP create of everything.

	  $this->phase2();		// resolve references.
	  
	  $m = $this->reports("makeplaces","phase2","placecode");
	  return $m;
  }
  protected function report3(){
	  $m = $this->cposts["neils"]->showAllPend();
	  $m.= $this->cposts["derek"]->showAllPend();
	  $m.= $this->cposts["marians"]->showAllPend();
	  return $m;
  }
  protected function report2(){
  
	  $m = "";
	  foreach($this->cposts as $id=>$cpost) $m.="<br />".$cpost->show()." - ".$this->end2($this->set[$id]->get("type"));
	  return $m;
  }
  protected function end2($str){
	$m = ord(substr($str,-2,1));
	$m.= ".".ord(substr($str,-1,1));
	return $m;
  }
  protected function report(){
  
	  $m = $this->listWanted();
	  //$m.= $this->listTypes("event");
	  //$m.= $this->listTypes("place");
	  
	  $m.=$this->set["violet"]->showAll();
	  $m.=$this->set["yvonne"]->showAll();
	  
	  
	  return $m;
  }
  protected function build(){
	  
	  $convert = new EntCPost($this);

	  foreach($this->set as $id=>$obj) {
		  if (!$obj->isWanted()) continue;
		  $this->cposts[$id] = $convert->make($obj);
	  }

	  $this->makePlaces();
  
  }
  protected function phase1(){
	  $m = "<h2>Phase 1</h2>";
	  $cp = $this->cposts["neils"];
	  //$rc = $cp->create();
	  $m.= "<br/>neils ".( $rc===false ? $cp->error_message : $rc); 
	  $this->report["phase1"] = $m;
	  return $m;
  }
  protected function makePlaces(){
	  $m = "<h2>Making places</h2>";
	  foreach($this->set as $id=>$ent) {
		  if (!$ent->isWanted()) continue;
		  $this->translatePlace($ent, "place_birth");
		  $this->translatePlace($ent, "place_death");
		  $this->translatePlace($ent, "place_wedding");
	  }
	  $this->report["makeplaces"] =  $this->listPlaces();
	  return;
  }
  protected function translatePlace($ent, $property){
	  $pr=$ent->get($property);
	  if (!$pr) return;
	  $place = $this->placeNorm($pr);
	  $token = EntCPost::makeName($place);
	  
	  if (!isset($this->newplaces[$token])){
		  $z = new Ent($token);	// a virtual ent
		  $z->props = [
			"title"=>$place,
			"type"=>"place",
			"ent_ref"=>$token,
		  ];
		  $z-reorg();
		  
		  $this->newplaces[$token] = $z;
	  } else $z = $this->newplaces[$token];
	  
	  $ent->set("ent_link_".$property, $token);
  }
  protected function listPlaces(){
	  $m = "<h3>New Places</h3><ul>";
	  foreach ($this->newplaces as $tok->$ent) $m.="<li>".$tok."</li>";
	  return $m."</ul>";
  }
  protected function phase2(){
	  $m = "<h2>Phase 2</h2>";
	  
	  $cp = $this->cposts["neils"];
	  
	  $m.= "<br/>neils ".( $rc===false ? $cp->error_message : $rc); 
	  $this->report["phase2"] = $m;
	  return $m;
  }
  public function get($who){
      return isset($this->set[$who]) ? $this->set[$who] : null;
  }
  public function reports($list){
      $m = "";
	  $z = func_get_args();
	  foreach($z as $rep) $m.=$this->report[$rep];
	  return $m;
  }
  // clean up input data
  protected function setGenders(){
	  $males=["brianhe","alanmit","alex","benben","calebs","chrismit","danst","davben","edwardt","elijah","ericm","jackn","jakell",
	  "jamess","jimnay","joelst","johnbus","johnll","johns","johnst","jonathoh","kieran","laurben","markmac","maxn",
	  "natll","nobu","philtur","tobben","torin","zadok"];
	  
	  
	  $females=["akina","haruna","helens","anna","annies","bethany","charlst","chizuko","chloest","daphneg","doreens","doriss",
	  "elaines","emma","flis","hazelst","heathst","hollyll","ionamit","joans","karas","karenst","katiet","kerryl","kimst",
	  "laurapk","maja","marina","mollyn","rhians","yvonne",];
	  
	  
	  foreach ($males as $m) $this->get($m)->setMale(true);
	  foreach ($females as $f) $this->get($f)->setMale(false);
  }
  public function wantedEvents(){
	  $events = ["slub","vvcaleb","wedpsan","vvwpedor","vvwmarhe","vvwjonpa","vvwaldor",];
	  foreach ($events as $m) $this->get($m)->setWanted();
  }
  protected function listWanted(){
	  $num=0;
	  $m="<ul>";
	  foreach($this->set as $id=>$obj) {
		if ($obj->isWanted()) {
			$m.='<li>'.$obj->show().'</li>';
			$num++;
		}
	  }
	  return $m.'</ul>('.$num.' people)';
  }
  protected function listTypes($type){
	  $m="<ul>";
	  foreach($this->set as $id=>$obj) {
		  if ($obj->getType()==$type) $m.='<li>'.$type.": ".$obj->show().'</li>';
	  }
	  return $m."</ul>";
  }
  protected function setDescs($who,$depth){
	  $anc = $this->get($who);
	  $spo=$this->spouseOf($anc);
	  error_log("Spouse of $who is $spo");
	  if ($spo){
		  $spob = $this->get($spo);
		  $spob->setWanted();
		  $g = $anc->getGender();
		  if ($g=="M") $spob->setMale(false);
		  if ($g=="F") $spob->setMale(true);
	  }
	  foreach($this->set as $id=>$obj) {
		  $mum = $obj->get("mother");
		  $dad = $obj->get("father");
		  if ($who==$mum) {
			  $obj->setWanted();
			  $anc->setMale(false);
			  if ($depth>0) $this->setDescs($id,$depth-1);
		  }
		  if ($who==$dad) {
			  $obj->setWanted();
			  $anc->setMale(true);
			  if ($depth>0) $this->setDescs($id,$depth-1);
		  }
	  }
  }
  public function spouseOf($pers){
	  if ($spo=$pers->get("married_to")) return $spo;
	  $persid = $pers->key();
	  foreach($this->set as $id=>$obj) if ($obj->get("married_to")==$persid) return $id;
	  return null;
  }
  protected function setAncs($who){
	  $person = $this->get($who);
	  $person->setWanted();
	  if ($mum=$person->get("mother")) {
		  error_log("mother of ".$who." is ".$mum);
		  $this->get($mum)->setMale(false); 
		  $this->setAncs($mum);
	  }
	  if ($dad=$person->get("father")) {
		  error_log("father of ".$who." is ".$dad);
		  $this->get($dad)->setMale(true); 
		  $this->setAncs($dad);
	  }
  }
  protected function placeNorm($place){
	  switch($place){
case "18 Normansmead, Willesden (?)": return "18 Normansmead, Willesden";
case "Bardwell": return "Bardwell, Suffolk";
case "Bardwell, Suffolk": return "Bardwell, Suffolk";
case "Bardwell, Thingoe, Suffolk": return "Bardwell, Thingoe, Suffolk";
case "Barningham Wesleyan": return "Barningham Wesleyan";
case "Barningham": return "Barningham";
case "Blything Union Workhouse Bulcamp": return "Blything Union Workhouse Bulcamp";
case "Chediston Suffolk": return "Chediston, Suffolk";
case "Chediston": return "Chediston, Suffolk";
case "Chediston, Suffolk": return "Chediston, Suffolk";
case "Chediston?": return "Chediston, Suffolk";
case "Euston": return "Euston, Thetford, Norfolk";
case "Euston, Thetford, Norfolk": return "Euston, Thetford, Norfolk";
case "Felmingham, Norfolk": return "Felmingham, Norfolk";
case "Filby Norfolk": return "Filby, Norfolk";
case "Filby": return "Filby, Norfolk";
case "Filby, Norfolk": return "Filby, Norfolk";
case "Hopton, Norfolk": return "Hopton, Norfolk";
case "Hopton, Suffolk": return "Hopton, Suffolk";
case "Hopton, W Suffolk": return "Hopton, Suffolk";
case "Huntingfield Suffolk": return "Huntingfield, Suffolk";
case "Huntingfield, Suffolk": return "Huntingfield, Suffolk";
case "North Walsham": return "North Walsham, Norfolk";
case "North Walsham, Norfolk": return "North Walsham, Norfolk";
case "Prim. Meth. Kilburn, London": return "Prim. Meth. Kilburn, London";
case "Registry Office, Mutford and Lothingland": return "Registry Office, Mutford and Lothingland";
case "Somerton Norfolk": return "Somerton Norfolk";
case "Somerton": return "Somerton Norfolk";
case "South Walsham": return "South Walsham";
case "Spa Common, N Walsham": return "Spa Common, North Walsham, Norfolk";
case "Spa Common, North Walsham, Norfolk": return "Spa Common, North Walsham, Norfolk";
case "St Marys Parish Church": return "St Marys Parish Church";
case "Tunstead": return "Tunstead, Norfolk";
case "Tunstead, Norfolk": return "Tunstead, Norfolk";
case "Witton, near North Walsham": return "Witton, near North Walsham";
default: return $place;
	  }
  }

}

$ent_loader = new EntLoader();

 ?>
