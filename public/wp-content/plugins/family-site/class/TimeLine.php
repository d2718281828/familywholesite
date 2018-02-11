<?php
namespace FamilySite;

class TimeLine {

  protected $focus = null;

  public function __construct($focus = null){
    $this->focus = $focus;
  }
  // next job - rework this with different timeline event types
  public function html(){
    global $wpdb;

    $sql = "select * from ".$wpdb->prefix."timeline";
	if ($this->focus) $sql.=" where object=".$this->focus->postid;
	$sql.= " order by event_date desc;";
    $res = $wpdb->get_results($sql, ARRAY_A);

    $m = "<div class='timeline-wrap'>";
    foreach($res as $event) {
      $source = \CPTHelper\CPTHelper::make($event["source"],$event["source_type"]);
      $evdate = new \DateTime($event["event_date"]);
      $m.= '<div class="timeline-link"><div class="timeline-date">'.$evdate->format("Y, jS F").'</div>';
	  switch($event["event_type"]){
		case "BORN":
		$m.= '<div class="timeline-body">Born</div>';
		break;
		case "DIED":
		$m.= '<div class="timeline-body">Died</div>';
		break;
		case "SON":
		case "DAUGHTER":
		$m.= '<div class="timeline-body">'.$event["event_type"].' '.$source->simpleBirthLink().'</div>';
		break;
		case "MARRIAGE":
		if ($event["object2"]){
			$spouse = \CPTHelper\CPTHelper::make($event["object2"],$event["object2_type"]);
			$m.= '<div class="timeline-body">Marriage to '.$spouse->simpleLink().'</div>';
		}
		break;
		default:
		$m.= '<div class="timeline-pic">'.$source->link().'</div><!-- end timeline-pic --->';
	  }
	  $m.= '</div><!-- end timeline-link --->';
    }
	$m.="</div><!-- end timeline-wrap --->";
    return $m;
  }
  /* timeline types
  source is the post that writes these when being saved
  object is the filter for a particular timeline
  
  source		object	object2
  person  BORN  samepers place
  person  SON   parent
  person  DIED	samepers place
  person  MARRIED samepers spouse place
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
	$rc = $wpdb->query($del));
  }
  static function addEntry($event_date, $sid, $stype, $ev, $oid, $otype, $place, $event, $o2=null, $o2type=null ){
	global $wpdb;
	$timeline = $wpdb->prefix . "timeline";
	$ins = "insert into  $timeline(event_date, source, source_type,event_type, object, object_type, event, place, object2, object2_type) values(%s,%d,%s,%s,%d,%s,%d,%d,%d,%s);";
	$ins2 = "insert into  $timeline(event_date, source, source_type,event_type, object, object_type,event, place) values(%s, %d,%s,%s,%d,%s,%d,%d);";
	
	if ($o2===null) $sql = $wpdb->prepare($ins2,$event_date,$sid, $stype,$ev,$oid, $otype, $place, $event);
	else $sql = $wpdb->prepare($ins,$event_date,$sid, $stype,$ev, $oid, $otype, $place, $event, $o2, $o2type);
	
	$rc = $wpdb->query($sql);
	  
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
  static function addInterest($event_date, $sid, $stype, $x, $xtype){
	  self::addEntry($event_date, $sid, $stype, "INTEREST", $x, $xtype, 0, 0);
  }
}
 ?>
