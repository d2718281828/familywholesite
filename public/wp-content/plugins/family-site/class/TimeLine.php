<?php
namespace FamilySite;
require_once("ApproxDate.php");
require_once("timeline_aux/Aggregator.php");
require_once("timeline_aux/Unique.php");
require_once("timeline_aux/TLCounter.php");

// todo use photo crops
/* notes
Working on the grands-
	Ancestors working OK.
		I am not doing children's death
	Might need a dead_by which means we dont know when the death was but it was definitely before dead_by
	need the descendents version, like ancestors

Events are now adde to the timeline but only if they are tagged with a person.
They dont get tagged with everyone who is at the event though, so they still wont show up in focused TLs

shortcode needs a focus parameter.
Could experiment with timelines for places...

Bugs
		/timeline/?summary=40 doesnt work

*/
class TimeLine {

  protected $focus = null;
  protected $timerange = null;	// two element array date from, to, or null. from/to can also be null
  protected $summary = null;		// summary level. 0 = not summarised. 100 is the most possible.

  public function __construct($focus = null){
    $this->focus = $focus;
	$this->ad = new ApproxDate();
	$this->timefrom = array_key_exists("from", $_REQUEST) ? $_REQUEST["from"] : null ;
	$this->timeto = array_key_exists("to", $_REQUEST) ? $_REQUEST["to"] : null ;
	$this->summary = array_key_exists("summary", $_REQUEST) ? $_REQUEST["summary"] : null ;
  }
  /**
  * set from and to date strings or null
  */
  public function setRange($from,$to){
	if (!$this->timefrom) $this->timefrom = $from;
	if (!$this->timeto) $this->timefrom = $to;
  }
  public function setSummary($level){
	if (!is_numeric($this->summary)) $this->summary = $level;
  }
  /**
  * Output the html for the timeline.
  * Most of the work is devolved to the aggregator.
  */
  public function html(){
    global $wpdb;
	
	$predicates = [];
	if ($this->focus) $predicates[] = "object=".$this->focus->postid;
	
	if ($this->timefrom && $this->timeto){
		$predicates[] = "event_date between '".$this->timefrom."' and '".$this->timeto."'";
	} else {
		if ($this->timefrom) $predicates[] = "event_date >= '".$this->timefrom."'";
		if ($this->timeto) $predicates[] = "event_date <= '".$this->timeto."'";
	}

    $sql = "select * from ".$wpdb->prefix."timeline";
	$sql.= count($predicates)>0 ? " where ".join($predicates," and ") : "";
	$sql.= " order by event_date desc, source asc, event_type;";
    $res = $wpdb->get_results($sql, ARRAY_A);
	
	/* Choose the aggregator
	* summary 0 Aggregator or Unique
	* 		10 daily - Unique - needs to cope with focus. One picture per day
	* 		20 - monthly	Counter
	*		30	yearly
	*		40  decade
	* Also aggregator needs a page link maybe?
	*/
	if ($this->summary < 10) {
		if ($this->focus) $current = new Aggregator($this->summary, $this->focus);
		else $current = new Unique($this->summary, null);
	} elseif($this->summary < 20) {
		$current = new Unique($this->summary, $this->focus);
	} else {
		$current = new TLCounter($this->summary, $this->focus);
	}

    $m = "<div class='timeline-wrap'>";
    foreach($res as $event) {
	  $next = $current->nextOne($event);
	  if ($next){
		  $m.= $current->html();
		  $current = $next;
	  }
    }
	$m.=$current->html()."</div><!-- end timeline-wrap --->";
    return $m;
  }
  /* timeline types
  source is the post that writes these when being saved
  object is the filter for a particular timeline
  
  source		object	object2
  person  BORN  samepers place
  person  SON   parent
  person  DIED	samepers place
  person  MARRIAGE samepers spouse place
  picture PIC   tagged-pers
  picture PIC   tagged-event
  picture PIC   tagged-place
  event   EVENT  tagged-place
  event   EVENT  tagged-person (wedding)
  */
  static function activate(){
	  error_log("Timeline::activate called");
	  global $wpdb;
	  $tname = $wpdb->prefix."timeline";
	  $create = "CREATE TABLE IF NOT EXISTS $tname (
		ID  bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		event_date DATE NOT NULL,
		date_within INTEGER default 0,
		source bigint(20)  NOT NULL,
		source_type  varchar(20) COLLATE utf8mb4_unicode_ci  NOT NULL,
		event_type char(10) NOT NULL,
		object bigint(20) not null ,
		object_type  varchar(20) COLLATE utf8mb4_unicode_ci not null,
		object2 bigint(20) not null default 0 ,
		object2_type  varchar(20) COLLATE utf8mb4_unicode_ci not null default '' ,
		place bigint(20)  not null default 0,
		event bigint(20)  not null default 0,
		PRIMARY KEY (ID),
		KEY sourceindex(source),
		KEY dateindex (event_date),
		KEY object2index (object2)
		
	  )  ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
	  $wpdb->query($create);
  }
  static function clearSource($id){
	global $wpdb;
	$timeline = $wpdb->prefix . "timeline";
	$del = "delete from $timeline where source=%d";
	$rc = $wpdb->query($wpdb->prepare($del,$id));
  }
  static function clearAll(){
	global $wpdb;
	$timeline = $wpdb->prefix . "timeline";
	$del = "delete from $timeline";
	$rc = $wpdb->query($del);
  }
  static function addEntry($event_date, $sid, $stype, $ev, $oid, $otype, $place, $event, $o2=null, $o2type=null, $within=0 ){
	global $wpdb;
	$timeline = $wpdb->prefix . "timeline";
	$ins = "insert into  $timeline(event_date, date_within, source, source_type,event_type, object, object_type, event, place, object2, object2_type) values(%s,%d, %d,%s,%s,%d,%s,%d,%d,%d,%s);";
	$ins2 = "insert into  $timeline(event_date, date_within, source, source_type,event_type, object, object_type,event, place) values(%s, %d, %d,%s,%s,%d,%s,%d,%d);";
	
	list($cd,$dwithin) = self::correctDate($event_date,$within);
	$dwithin = $dwithin>0 ? $dwithin : $within;
	if ($o2===null) $sql = $wpdb->prepare($ins2,$cd,$dwithin, $sid, $stype,$ev,$oid, $otype, $place, $event);
	else $sql = $wpdb->prepare($ins,$cd,$dwithin,$sid, $stype,$ev, $oid, $otype, $place, $event, $o2, $o2type);
	
	$rc = $wpdb->query($sql);
  }
  /**
  * This is to cope with dates which are not days. we will accept yyyy and yyyy/mm.
  * if we are given yyyy then we return yyyy/06/30 (half way through the year) and a date within not less than 182 (half a year). etc for months.
  * @return two element list, the date, and the date within day count.
  */
  static function correctDate($dt,$within){
	  if (strlen($dt)==4) {
		  if ($within<182) $within=182;
		  return [$dt."/06/30", $within];
	  }
	  if (strlen($dt)==7) {
		  if ($within<15) $within=15;
		  return [$dt."/15", $within];
	  }
	  return [$dt, $within];
  }
  /** Birth or death of a single person, evtype is BORN or DIED
  */
  static function add1($event_date, $sid, $evtype, $place, $event){
	  self::addEntry($event_date, $sid, "fs_person", $evtype, $sid, "fs_person", $place, $event);
  }
  /** person sid is child of parent, evtype is SON or DAUGHTER
  */
  static function addChild($event_date, $sid, $evtype, $parent, $place=0 , $event=0){
	  self::addEntry($event_date, $sid, "fs_person", $evtype, $parent, "fs_person", $place, $event);
  }
  /** Marriage of a to b
  */
  static function addMarriage($event_date, $sid, $a, $b, $place, $event){
	  self::addEntry($event_date, $sid, "fs_person", "MARRIAGE", $a, "fs_person", $place, $event, $b, "fs_person");
  }
  /** Interest item, containing $x
  */
  static function addInterest($event_date, $sid, $stype, $x, $xtype, $date_within=0){
	  self::addEntry($event_date, $sid, $stype, "INTEREST", $x, $xtype, 0, 0, null, null, $date_within);
  }
  /** event
  */
  static function addEvent($event_date, $sid, $stype, $x, $xtype, $date_within=0){
	  self::addEntry($event_date, $sid, $stype, "EVENT", $x, $xtype, 0, 0, null, null, $date_within);
  }
  /** Call this in init to define the timeline shortcode
  */
  static function init(){
  	add_shortcode("timeline",[__CLASS__,"do_shortcode"]);
  }
  /** Call this in init to define the timeline shortcode
  */
  static function do_shortcode($atts, $content, $tag){
	  $a = shortcode_atts( array(
		'summary' =>  100,
		'from' => null,
		'to' => null,
		// todo focus?? yes, but how? just postid?
	  ), $atts );
	  
	  $tl = new TimeLine();
	  $tl->setSummary($a["summary"]);
	  $tl->setRange($a["from"], $a["to"]);
	  
	  return $tl->html();
  }
}
 ?>
