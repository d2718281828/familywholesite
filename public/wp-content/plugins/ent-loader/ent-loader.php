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
	protected $knownEnts = [];
	protected $thisSite = "familysite";
	protected $creators = [];		// list of stored creators

  public function __construct(){
	  add_action("init", [$this,"init"]);
	  $up = wp_upload_dir();
	  $this->input = $up["basedir"]."/nodes";
	  $this->testset = ["neils","marians","joans","rhians","20-raglan-st-lowestoft-suffolk",
	  "euston-thetford-norfolk","58a-robson-avenue-willesden-london-nw10","bens","violet","markmac"];
	  $this->wantedPeople = [["chrisx","M"],["vivian","F"]];		// anyone not pulled in by the setAncs and setDescs

	  // pictures we definitely dont want
	  $this->blackPix = ["problems","dscn7147","dscn7159","dscn7161","dscn7198","dscn7199","mdeufbd",
	  "ewcndw4","ewcndw5","ewcndw6","ewcndw7","gmmhkoxd","gmmhkoxc","eopasmk2","abz4le06","abz4le07","abz4le08",
	  "abz4le09","abz4le11","abz4le12","abz4le13","abz4le14","abz4le15","abz4le17","abz4le21","abz4le22",
	  "abz4le23","sjnkht00","mkkfeppl","jstzz022","jstzz025","jstzz024","jstzz023",
	  "jstzz030", "jstzz031", "jstzz032", "jstzz033","jstzz034","dscn7107", "dscn7108", "dscn7109",
	  "foskxnv1", "foskxnv2", "foskxnv3", "foskxnv4", "foskxnv5", "foskxnv6", "foskxnv7","sjnkht00",
	  "ioddtey","iodds3d","iodds3c","iodds3b","iodds3a",
	  "dagvcj4","dagvcj3","dagvcj2","dagvcj1","avfsdsvg","avfsdsvh",
	  "jstnu003","jstnu004",
	  "jstpinv1","jstpinv2","jstpinv3","jstpinv4","jstpinv5","imga4569",
	  "jstpinv6","jstpinv7","jstpinv8","jstpinv9","jstpinva","kjnsdkcn","onwjcnf",
	  "abz4le00","abz4le04","shsjhyx9","yxw7o091","yxw7o092","jst4924a","jst4924b","jst4925a","jst4925b",
	  "kdfmdyur","mcdlvs0","mcdlvs1","mcdlvs2","mcdlvs3"];
	  
	  $this->picBatchSize = 10;
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
  /**
  * COMMAND read in from nodes files to CPost pre-entries
  */
  public function loadPeople(){
	  $up = wp_upload_dir();
	  $this->input = $up["basedir"]."/nodes";
	  
	  $this->load();
	  $this->reportLoad();
	  
	  // pre-filtering - fixing problems with the album nodes
	  if ($vio=$this->get("violet")) $vio->setMale(false);
	  $this->setAncs("rowanst");
	  $this->setAncs("brianhe");
	  //$this->setDescs("violet",5);
	  $this->setDescs("anc5",8);
	  $this->setAncs("elaines");
	  $this->setAncs("paddyg");
	  $this->setAncs("annettes");
	  $this->setDescs("elaines",8);
	  $this->setDescs("daphneg",8);
	  $this->setDescs("ans1",8);
	  $this->setGenders();
	  $this->setWantedPeople();
	  $this->wantedEvents();
	  
	  foreach($this->set as $id=>$obj) $obj->reorg();
	  $this->reportLoad();
	  
	  $this->build(true);		// create cposts out of ents, and get the places too
	  
	  $this->phase1();		// initial WP create of everything.

	  $this->phase2();		// resolve references in parameters, like mother, father
	  
	  $this->phase3();		// re-save and convert text in the descriptions
	  
	  $m = "<p>Available reports: ".implode(",",array_keys($this->report));
	  $m.= $this->reports("builtsample","phase1","phase2","phase3","placecode");
	  return $m;
	  
  }
  public function load($dir = null){
	  if ($dir){
		  $ddir = $dir;
	  } else {
		  $ddir = "";
		  $this->loadStart();
	  }
	  $adir = $this->input.'/'.$ddir;
	  
	  if (WP_DEBUG) error_log("Loading Ents from ".$adir);
	  $list = scandir($adir);
	  foreach ($list as $fil){
		  if ($fil=='.' || $fil=='..') continue;
		  $full = $adir.'/'.$fil;
		  if (is_dir($full)){
			  $this->load($ddir.'/'.$fil);
		  } else {
			  $key = Ent::makeKey($fil);
			  if (isset($this->set[$key])){
				$z = $this->set[$key];
				$z->addFile($fil, $ddir, $adir);
			  } else {
				$z = new Ent($fil, $ddir, $adir);
				$this->set[$key] = $z;
			  }
		  }
	  }
	  
  }
  /**
  * COMMAND delete everything which was loaded from ent
  * @param $picsonly boolean if true then only delete the pictures
  */
  public function deleteAll($picsonly = false){
	  global $wpdb;
	  $s_all = 'select post_id from '.$wpdb->postmeta.' where meta_key="ent_ref";';
	  $s_pics = 'select P.ID from '.$wpdb->postmeta.' PM, '.$wpdb->posts.' P  
	  where P.ID = PM.post_id and meta_key="ent_ref"
	  and P.post_type = "post";';
	  $posts = $wpdb->get_col($picsonly ? $s_pics : $s_all);
	  $m = "<h2>Deleting</h2>";
	  foreach ($posts as $post){
		  $cp = CptHelper::make($post);
		  $cp->destroy();
		  $m.= ", ".$post;
	  }
	  \FamilySite\TimeLine::clearAll();
	  return $m;
  }
  /**
  * COMMAND read in pics from album
  */
  public function loadPics(){
	  $starttime = microtime();
	  $up = wp_upload_dir();
	  $this->input = $up["basedir"]."/album";
	  
	  $justThese = null;
	  //$justThese = ["dscn7229"];

	  $this->load();
	  set_time_limit(60); 
	  
	  $this->wantedPics();
	  $this->reportLoad(false);
	  set_time_limit(60); 
	  
	  foreach($this->set as $id=>$obj) $obj->reorg();
	  
	  $this->build();		// create cposts out of ents
	  set_time_limit(60); 
	  
	  $testset = $justThese ?: $this->nextBatch($this->picBatchSize);
	  if (!$testset) return "<p>No further pictures to load.".$this->reports("stats");
	  echo "<p>Test set ".implode(", ",$testset);
	  
	  $this->phase1($testset);		// initial WP create of everything.
	  set_time_limit(60); 

	  $this->phase2($testset);		// resolve references in parameters, like mother, father
	  set_time_limit(60); 
	  
	  $this->phase3($testset);		// re-save and convert text in the descriptions
	  
	  $m = "<p>Available reports: ".implode(",",array_keys($this->report));
	  $m.= $this->reports("loaded","builtsample","stats",/* "phase1","phase2",*/ "phase2a","phase3");
	  $m.= "<p>Elapsed time : ".$this->timeDiff(microtime(),$starttime);
	  return $m;
	  
  }
  /**
  * Things to be done after all pics loaded
  */
  public function complete(){
	  $m = $this->resaveAll();
	  return $m.$this->addNodePics();
  }
  /**
  * Things to be done after all pics loaded
  */
  public function addNodePics(){
	  global $wpdb;
	  //  find all posts with a node pic for which a post exists
	  $nodepics = 'select P.ID, P.post_title , PIC.post_id as media
	  from '.$wpdb->posts.' P, '.$wpdb->postmeta.' PN, '.$wpdb->postmeta.' PIC
	  where PN.post_id = P.ID and PN.meta_value = PIC.meta_value
	  and P.post_status="publish"
	  and PN.meta_key = "ent_link_featured" and PIC.meta_key = "ent_ref"
	  ;';
	  $res = $wpdb->get_results($nodepics,ARRAY_A);
	  
	  $m = "<p>NOTE: this hasnt been finished yet, waiting for something to show up in the list below";
	  $m.= "<p>SQL=".$nodepics;
	  $m.= "<table>";
	  $m.= "<tr><td>ID with nodepic</td><td>post title</td><td>the pic node</td></tr>";
	  for ($k=0; $k<count($res);  $k++){
		  $id = $res[$k]["ID"];
		  $pp = "<a href='".get_permalink($id)."' target='_blank'>".$res[$k]["post_title"]."</a>";
		  
		  $picurl = wp_get_attachment_url($res[$k]["media"]);
		  $pic = "<a href='$picurl'>".$res[$k]["media"]."</a>";
		  $m.= "<tr><td>".$id."</td><td>".$pp."</td><td>".$pic."</td></tr>";		  
	  }
	  $m.= "</table>";
	  $m.= "<p>Found ".count($res);
	  return $m;
	  
  }
  /**
  *
  */
  public function resaveAll(){
	  global $wpdb;
	  //  find all ent loaded posts
	  $allents = 'select P.*
	  from '.$wpdb->posts.' P, '.$wpdb->postmeta.' PM 
	  where PM.post_id = P.ID and PM.meta_key = "ent_ref"
	  ;';
	  $res = $wpdb->get_results($allents);
	  $m = "<p>Found ".count($res)." ent-created items";
	  
	  for ($k=0; $k<count($res); $k++){
		  $cp = CptHelper::make($res[$k]);
		  $cp->on_update(2);
	  }
	  
	  return $m;
  }
  /**
  * COMMAND look for and fix any pictures which didnt load properly - most probably due to a timeout.  Fix or delete?
  * I am a bit confused by this one, there's an edit link put out after the post is deleted.
  * It seems to be picking out all posts with ent_curly_desc...
  */
  public function fixPics(){
	  global $wpdb;
	  //  just list them to start
	  $aborted = 'select P.ID, P.post_title 
	  from '.$wpdb->posts.' P, '.$wpdb->postmeta.' PM
	  where PM.post_id = P.ID 
	  and P.post_type="post" and P.post_status="publish" and P.post_content="pending"
	  and PM.meta_key = "ent_curly_desc"
	  ;';
	  $res = $wpdb->get_results($aborted,ARRAY_A);
	  
	  $m = "<table>";
	  $m.= "<tr><td>ID</td><td>post</td><td>edit</td></tr>";
	  for ($k=0; $k<count($res);  $k++){
		  $id = $res[$k]["ID"];
		  $pp = "<a href='".get_permalink($id)."' target='_blank'>".$res[$k]["post_title"]."</a>";
		  $ed = "<a href='".get_site_url()."/wp-admin/post.php?post=".$id."&action=edit' target='_blank'>Edit</a>";
 		  wp_delete_post( $id, true );
		  $m.= "<tr><td>".$id."</td><td>".$pp."</td><td>".$ed."</td></tr>";		  
	  }
	  $m.= "</table>";
	  $m.= "<p>Found ".count($res);
	  return $m;
  }
  /**
  * Subtract microtimes. microtime format is two numbers separated by a space, both are in seconds.
  */
  public function timeDiff($a,$b){
	  $av = explode(" ",$a);
	  $bv = explode(" ",$b);
	  return $av[0]-$bv[0] + ($av[1]-$bv[1]);
  }
  protected function nextBatch($batchsize = 5){
	  $res = [];
	  $num = 0;
	  $created = 0;
	  $m = "<h2>Stats</h2>";
	  foreach($this->set as $entid=>$ent){
		  if ($ent->exists()) $created++;
		  else {
			if ($ent->isWanted()) {
			  $batchsize--;
			  if ($batchsize>=0) $res[] = $entid;
			}
		  }
	  }
	  $m.="<p>Total images to load ".$num;
	  $m.="<p>Already loaded ".$created;
	  $this->report["stats"] = $m;
	  return $res;
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
  protected function reportLoad($wantall=true){
  
	  $m = "<h2>Loaded files</h2><ul>";
	  $numwanted = 0;
	  foreach ($this->set as $ent) {
		  if ($wantall || $ent->isWanted()) $m.="<li>".$ent->show().$ent->thumb()."</li>";
		  if ($ent->isWanted()) $numwanted++;
	  }
	  $m.= "</ul>";
	  $m.= "<p>Total ".count($this->set)." items, ".$numwanted." wanted.</p>";
	  	  
	  $this->report["loaded"] = $m;
  }
  /**
  * create cposts out of ents, for people and the newly made places
  * The cposts are all virtual at this point, they havent been created.
  */
  protected function build($withplaces = false){
	  
	  $convert = new EntCPost($this);

	  if ($withplaces) $this->makePlaces();

	  foreach($this->set as $id=>$obj) {
		  if (!$obj->isWanted()) continue;
		  $this->cposts[$id] = $convert->make($obj);
	  }

	  if ($withplaces){
		  $m = "<h2>Build of places</h2>";
		  foreach($this->newplaces as $id=>$obj) {
			  $this->cposts[$id] = $convert->make($obj);
			  $m.=$this->cposts[$id]->showAllPend();
		  }
		  $this->report["buildplaces"] = $m;
	  }
	  $m = "<h2>Sample of Built items</h2>";
	  $sample = ["vvbjwav","dscn7229","vvbjwav4","herinl16"];
	  foreach($sample as $item){
		  if (!isset($this->cposts[$item])) continue;
		  $m.="<h3>".$item."</h3>";
		  $m.= $this->cposts[$item]->showAllPend();
		  $m.= "<p>-----";
		  $m.= $this->set[$item]->showAll();
	  }
	  $this->report["builtsample"] = $m;
  
  }
  /**
  * First pass creating actual WP posts
  */
  protected function phase1($testkeys = null){
	  $m = "<h2>Phase 1</h2>";
	  if ($testkeys) $m.="<p>Test set only</p>";
	  $keyset = $testkeys?: array_keys($this->cposts);
	  
	  
	  foreach ($keyset as $id){
		$cp = $this->cposts[$id];
		if (!$cp) echo "<p>*** NO CP for ".$id;
		$rc = $cp->create();
		$m.= "<br/>Created ".$cp->get("post_title")." ".( $rc===false ? $cp->error_message : $rc);
		
		if ((isset($this->set[$id])) && $pic = $this->set[$id]->getImageFile()){
			$pic = str_replace("//","/",$pic);
			$caption = ["caption"=> $cp->get("post_title") ];
			$id = $this->sideload($pic, 0, $caption);
			if ( is_wp_error($id)) $m.="<br />Error loading image ".implode("<br/>",$id->get_error_messages());
			else {
				$mtype = $this->typeOfFile($pic);
				if ($mtype == "img"){
					$m.=" Loaded image ".$id;
					set_post_thumbnail($cp->postid, $id);					
				} else {
					update_post_meta($cp->postid, "featured_media", $id);					
					update_post_meta($cp->postid, "featured_media_type", $mtype);					
				}
			}
		}
	  }
	  $this->report["phase1"] = $m;
	  return $m;
  }
  // this is copied from mediaType
	protected function typeOfFile($fname){
		$p = strrpos($fname,".");
		if ($p===false) return "unk";
		$t = strtolower(substr($fname,$p+1));
		switch($t){
			case 'jpg':
			case 'jpeg':
			case 'png':
			case 'gif':
			case 'bmp':
			return 'img';
			
			case 'pdf':
			return 'pdf';
			
			case 'htm':
			case 'html':
			return 'htm';
			
			case 'mp3':
			case 'ogg':
			case 'wav':
			return 'aud';
			
			case 'txt':
			return 'txt';
			
			case 'doc':
			case 'ppt':
			case 'docx':
			case 'pptx':
			return 'doc';
			
			default:
			return 'unk';
		}
	}
  /**
  * is it an image based on the filetype?
  */
  protected function is_image_Obs($fname){
	  $dot = strrpos($fname,".");
	  if ($dot===false) return false;
	  $ext = strtolower(substr($fname,$dot+1));
	  switch($ext){
		  case "jpg":
		  case "jpeg":
		  case "png":
		  case "gif":
		  return true;
		  
		  // this stuff is just to write out a warning if we dont recognise the type at all
		  case "pdf":
		  
		  case "ppt":
		  case "doc":
		  case "docx":
		  
		  case "mov":
		  case "mp4":
		  
		  case "mp3":
		  case "ogg":
		  case "wav":
		  return false;
		  
	  }
	  echo "<p>WARNING unknown file extension $ext on file $fname";
	  return false;
	  
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
  /**
  * get the place string from a person, make it into an ent-like token, create a place with that token as ent_ref,
  * and add it as an ent_link_ to that person
  */
  protected function translatePlace($ent, $property){
	  $pr=$ent->get($property);
	  if (!$pr) return;
	  $place = $this->placeNorm($pr);
	  $token = EntCPost::makeName($place);
	  
	  if (!isset($this->newplaces[$token])){
		  $z = new Ent($token);	// a virtual ent
		  $z->props = [
			"title"=>$place,
			"description" => $place,
			"type"=>"place",
			"ent_ref"=>$token,
		  ];
		  $z->reorg();
		  $z->digestAtts();
		  
		  $this->newplaces[$token] = $z;
	  } else $z = $this->newplaces[$token];
	  
	  $ent->set("ent_link_".$property, $token);
  }
  protected function listPlaces(){
	  $m = "<h3>New Places</h3><ul>";
	  foreach ($this->newplaces as $tok=>$ent) $m.="<li>".$tok."</li>";
	  return $m."</ul>";
  }
  protected function phase2($testkeys = null){
	  global $wpdb;
	  $m = "<h2>Phase 2</h2>";
	  if ($testkeys) $m.="<p>Test set only</p>";
	  
	  // pick up the hanging refs
	  $s = "select * from ".$wpdb->postmeta." where meta_key like 'ent#_link#_%' ESCAPE '#';";
	  $refs = $wpdb->get_results($s,ARRAY_A);
	  foreach ($refs as $ref){
		  $entref = $ref["meta_value"];
		  $prop = substr($ref["meta_key"],9); // everything after the ent_link_ is the actual prooperty name
		  $actual_id = EntCPost::get_postid_by_entref($entref);
		  $m.="<br/>Resolved ".$prop." for ".$ref["post_id"].", ".$entref;
		  if ($actual_id){
			  $m.=" as ".$actual_id;
			  update_post_meta($ref["post_id"], $prop, $actual_id);	// update the new property
			  delete_post_meta($ref["post_id"],$ref["meta_key"]); 
		  }
	  }
	  	  
	  $this->report["phase2"] = $m; 
	  
	  $m = "<h2>Phase 2a</h2><p>Index tagging</p>";
	  $s = "select PM.post_id as pid, P.post_type as ptype, PM.meta_value as ix 
	  from ".$wpdb->postmeta." PM,".$wpdb->posts." P 
	  where P.ID = PM.post_id and P.post_status = 'publish' and meta_key = 'ent_links' ;";
	  $m.="<p>SQL: ".$s;
	  $refs = $wpdb->get_results($s,ARRAY_A);
	  foreach ($refs as $ref){
		  $theItem = CptHelper::make($ref["pid"], $ref["ptype"]);
		  $index = $ref["ix"];
		  if ($index){
		    $index = get_post_meta($theItem->postid,"ent_links",true);	// get WP to decode it the way it wants to
		    $co = $this->tagPostByIndex($theItem,$index);
		    $m.="<p>Indexed ".$ref["pid"]." with $co new tags";
		  } else $m.= "<p>No actual index for ".$ref["pid"];
	  }
	  $this->report["phase2a"] = $m; 
	  return "";
  }
  protected function tagPostByIndex($theItem,$index){
	  $set=[];
	  foreach($index as $entry){
		  // many index entries will be nothing to do with ents
		  //echo "<p>Tagging ".$theItem->show()." with ".print_r($entry[0],true);
		  $cp = $this->get_cpost_by_entref($entry[0]);
		  if ($cp) $set[] = $cp;
	  }
	  if ($set) $rc = $theItem->tagWith($set);
	  else $rc="";
	  return $rc;
  }
  protected function phase3($testkeys = null){
	  global $wpdb;
	  $m = "<h2>Phase 3</h2>";
	  if ($testkeys) $m.="<p>Test set only</p>";
	  $keyset = $testkeys?: array_keys($this->cposts);

	  $convert = new EntCPost($this);
	  
	  foreach ($keyset as $id){
		$cp = $this->cposts[$id];
		$m.=$convert->phase3($cp);
		$m.=$convert->addEvent($cp);
	  }
	  
	  $this->report["phase3"] = $m; 
	  return $m;
  }
  public function get($who){
      return isset($this->set[$who]) ? $this->set[$who] : null;
  }
  public function reports($list){
      $m = "";
	  $z = func_get_args();
	  foreach($z as $rep) if (isset($this->report[$rep])) $m.=$this->report[$rep];
	  return $m;
  }
  // clean up input data
  protected function setGenders(){
	  $males=["brianhe","alanmit","alex","benben","calebs","neils","chrismit","danst","davben","edwardt","elijah","ericm","jackn","jakell",
	  "jamess","jimnay","joelst","johnbus","johnll","johns","johnst","jonathoh","kieran","laurben","markmac","maxn",
	  "natll","nobu","philtur","tobben","torin","zadok"];
	  
	  
	  $females=["akina","haruna","helens","anna","annies","bethany","charlst","chizuko","chloest","daphneg","doreens","doriss",
	  "elaines","emma","flis","hazelst","heathst","hollyll","ionamit","joans","karas","karenst","katiet","kerryl","kimst",
	  "laurapk","maja","marina","mollyn","rhians","yvonne",];
	  
	  
	  foreach ($males as $m) {
		  if ($x=$this->get($m)) $x->setMale(true);
	  }
	  foreach ($females as $f) {
		if ($x=$this->get($f)) $x->setMale(false);  
	  }
  }
  /**
  * Determine whether a picture is wanted.
  */
  public function wantedPics(){
	  foreach($this->set as $id=>$ent){
		  $entid = $ent->key();
		  
		  // check the public attribute - this overrides anyother rules. 
		  // <public>familysite<x>y
		  $public = $ent->get("public");
		  if ($public){
			  if (is_array($public) && is_array($public[0]) && $public[0][0]=="familysite"){
				if ($public[0][1]=="y" || $public[0][1]=="yes") {
					$ent->setWanted(true);
					continue;
				}
				if ($public[0][1]=="n" || $public[0][1]=="no") {
				  $ent->setWanted(false);
				  continue;	
				}	
			  }
				print_r($public);
			  echo "<p>Public attribute not set correctly in ".$id.", ignored: ".($public[0])."-".($public[1]);
		  }
		  $ix = $ent->get("index");
		  $picdate = $ent->get("date_created");
		  // if any current nodes have this as a featured image
		  if ($node=$this->isImageFor($entid)){
			  $ent->setWanted();
			  $ent->set("ent_is_image_for",$node);
		  }
		  // if the index is a tag for any known node
		  // except for the immediate family.
		  if ($ix){
		  foreach($ix as $ixentry){
			  if ($cpost=$this->get_cpost_by_entref($ixentry[0])){
				$eid = strtolower($ixentry[0]);
				if ($eid!="derek" && $eid!="anna" && $eid!="maja" && $eid!="alex" && $eid!="yvonne") $ent->setWanted();
				$ent->tagWith($cpost);
			  }
		  }
		  }
		  // if the picture is on the same date as an existing event (only if exactly one event)
		  // I have decided to take this off, i think it is pulling in a lot of un-necessary stuff
		  if (false && $theevent=$this->getEventWithDate($picdate)){
			  $ent->setWanted();
			  $ent->set("event",$theevent);			  
		  }
	  }
	  // go through again, checking against the blacklist
	  // and also check the public attribute 
	  foreach($this->set as $id=>$ent){
		  if (in_array($id,$this->blackPix)) $ent->setWanted(false);
		  $pub = $ent->getPublic($this->thisSite);
		  // override all of the above
		  if ($pub=="n") $ent->setWanted(false);
		  if ($pub=="y") $ent->setWanted(true);
		  if ($pub) echo "<br />NOTE new public attribute set for ".$id." to ".$pub;
	  }
  }
  protected function isImageFor($entid){
	  global $wpdb;
	  $s = "select PM.post_id from ".$wpdb->postmeta." PM,".$wpdb->posts." P 
	  where PM.post_id = P.ID
	  and P.post_status='publish'
	  and PM.meta_key='ent_link_featured'
	  and PM.meta_value = %s";
	  $res = $wpdb->get_results($wpdb->prepare($s,$entid),ARRAY_A);
	  if (count($res)>0) return $res[0]["post_id"];
	  return null;
  }
  protected function getEventWithDate($date){
	  global $wpdb;
	  $s = "select PM.post_id from ".$wpdb->postmeta." PM,".$wpdb->posts." P 
	  where PM.post_id = P.ID
	  and P.post_status='publish'
	  and P.post_type='fs_event'
	  and PM.meta_key='actual_date'
	  and PM.meta_value = %s";
	  $res = $wpdb->get_results($wpdb->prepare($s,$date),ARRAY_A);
	  if (count($res)==1) return $res[0]["post_id"];
	  return null;
	  
  }
  protected function get_cpost_by_entref($entref){
	  //if ($entref=="derek" || $entref=="anna" || $entref=="maja" || $entref=="alex" || $entref=="yvonne") return null;
	  //echo "<p>--";print_r($entref); 
	  if (is_array($entref)) $entref = $entref[0];
	  if (isset($this->knownEnts[$entref])) return $this->knownEnts[$entref];
	  
	  $pid = EntCPost::get_postid_by_entref($entref);
	  if ($pid) $cp = CptHelper::make($pid);
	  else $cp = null;
	  
	  $this->knownEnts[$entref] = $cp;
	  return $cp;
  }
  public function wantedEvents(){
	  $events = ["slub","vvcaleb","wedpsan","vvwpedor","vvwmarhe","vvwjonpa","vvwaldor",
	  "w0020713","vvcoln02","vvcot05","w0070707","vvbjwav4","vvbikl13","vvbikl13","vvbjwav4","vvbjwav",
	  "vvcoln00","vvcoln02","vvcoln04","vvcot05","vvcot06","vvhook02","vvhook03","vvhook05","vvware99",
	  ];
	  foreach ($events as $m) {
		  if ($v=$this->get($m)) $v->setWanted();
	  }
  }
  public function setWantedPeople(){
	  foreach ($this->wantedPeople as $pp) {
		  if ($v=$this->get($pp[0])) {
			  $v->setWanted();
			  $v->setMale($pp[1]=="M");
		  }
	  }
  }
  public function sideload($fullfile, $post_id, $description=null){
	// Need to require these files
	if ( !function_exists('media_handle_upload') ) {
		require_once(ABSPATH . "wp-admin" . '/includes/image.php');
		require_once(ABSPATH . "wp-admin" . '/includes/file.php');
		require_once(ABSPATH . "wp-admin" . '/includes/media.php');
	}
	$file_array = array();

	$file_array['name'] = basename($fullfile);
	$file_array['tmp_name'] = $fullfile;
	if (WP_DEBUG) error_log("media handle sideload with ".$file_array['name'].", ".$file_array['tmp_name']);

	// do the validation and storage stuff
	$filetitle = $description && array_key_exists("title",$description) ? $description["title"] : $file_array['name'] ;
	$id = media_handle_sideload( $file_array, $post_id ?: 0, $filetitle);

	// If error storing permanently, unlink
	if ( is_wp_error($id) ) {
		// this doesnt work, file permissionss
		@unlink($file_array['tmp_name']);
		error_log("Error sideloading ".$fullfile." ".$id->get_error_message());
		return $id;
	}
	// set caption for searchability
	$extras = ["ID"=>$id]; $num=0;
	foreach ($description as $prop=>$val){
		if ($prop == "caption") {
			$extras["post_excerpt"] = $val;
			$num++;
		}
	}
	if ($num > 0)  wp_update_post($extras);
	
	return $id;
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
	  if (!$anc) return;
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
	  if (!$person) return;
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
  /**
  * Lookup and cache creator names
  * @return two emelements, the maker, or null, and the maker_text, or null.
  */
  public function getCreator($creatorName){
	  // is it cached?
	  if (array_key_exists($creatorName, $this->creators)) return $this->creators[$creatorName];
	  
	  $wpname = $this->convertCreatorName($creatorName);
	  if (!$wpname) {
		  $this->creators[$creatorName] = [null, $creatorName];
		  return [null, $creatorName];
	  }
	  $wpid = $this->getIdBySlug($wpname, "fs_person");
	  if ($wpid) $res = [ $wpid, null];
	  else $res = [null, $creatorName];
	  $this->creators[$creatorName] = $res;
	  return $res;
  }
  public function getIdBySlug($slug, $type){
	global $wpdb;
	$s = "select ID from ".$wpdb->posts." where post_type=%s and post_status='publish' and post_name=%s;";
	$res = $wpdb->get_results($wpdb->prepare($s, $type, $slug),ARRAY_A);
	if (count($res)==0) return null;
	return $res[0]["ID"];
  }
  /**
  * cross reference from names which have been used in the album to WP names
  */
  protected function convertCreatorName($creatorName){
	  switch($creatorName){
		  case "Derek":
		  case "derek":
		  return "derek-storkey";
		  case "Peter":
		  case "peter":
		  return "peter-storkey";
		  case "{ a johns}":
		  case "John Storkey-Taylor":
		  case "John S-T":
		  case "{a johns:JST}":
		  return "john-storkeytaylor";
		  case "Matthew":
		  return "matthew-storkey";
		  case "Anna":
		  return "anna-storkey";
		  case "Alex":
		  return "alex-storkey";
		  case "Verity":
		  return "verity-mitchell";
		  case "Neil":
		  return "neil-storkey";
		  //case "Jonathan":		// since i dont know, leave it out, it could be done manually one day
		  //return "";
		  case "Hazel":
		  return "hazel-bustin";
		  case "Rowan":
		  return "rowan-lloyd";
		  case "John S":
		  case "John Stephens":
		  return "john-stephens";
		  case "Joan Storkey":
		  return "joan-storkey";
		  case "Jenny":
		  return "jenny-heritage";
		  case "Judith":
		  return "judith-turner";
		  case "Phil":
		  return "phil-turner";
		  case "Maja":
		  return "maja-storkey";
		  case "Brian":
		  return "brian-heritage";
		  case "Pauline":
		  return "pauline-stephens";
		  case "Marian":
		  return "marian-mackintosh";
		  case "Yvonne":
		  return "yvonne-storkey";
		  case "Mark Stephens":
		  return "mark-stephens";
		  case "Alan Storkey":
		  return "alan-storkey";
		  case "Chris Patrick":
		  return "chris-patrick";
	  }
	  return null;
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
