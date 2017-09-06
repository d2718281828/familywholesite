<?php
namespace FamilySite;

class TimeLine {

  protected $focus = null;

  public function __construct($focus = null){
    $this->focus = $focus;
  }
  public function html(){
    global $wpdb;

    $sql = $this->makeSQL();
    $res = $wpdb->get_results($sql, ARRAY_A);

    $m = "";
    foreach($res as $post) {
      $cp = \CPTHelper\CPTHelper::make($post["ID"],$post["post_type"]);
      $m.='<div class="timeline-link"><div class="timeline-date">'.$post["actual_date"].'</div>';
      $m.='<div class="timeline-pic">'.$cp->link().'</div></div>';
    }
    return $m;
  }
  protected function makeSQL(){
    global $wpdb;

    $select = ["P.ID","P.post_type"];
    $from = [$wpdb->posts." P "];
    $where = ["P.post_status = 'publish'"];
    $order = [];

    if ($this->focus){
      $tag = $this->focus->get("fs_matching_tag_id"); // this is the term_taxonomy_id
      $from[] = $wpdb->term_relationships." TR ";
      $where[] = "P.ID = TR.object_id";
      $where[] = "TR.term_taxonomy_id = ".((int)$tag);
    }
    // get the date
    $from[] = $wpdb->postmeta." D ";
    $select[] = "D.meta_value as actual_date";
    $where[] = "P.ID = D.post_id";
    $where[] = "D.meta_key = 'actual_date'";
    $order[] = "actual_date DESC";

    $s = "SELECT ".implode(",",$select)." FROM ".implode(",",$from);
    $s.= " WHERE (".implode(") and (", $where). ") ORDER BY ".implode(",",$order);
    return $s;
  }
  static function activate(){
	  error_log("Timeline::activate called");
	  global $wpdb;
	  $tname = $wpdb->prefix."timeline";
	  $create = "CREATE TABLE IF NOT EXISTS $tname (
		ID  bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		event_date DATE NOT NULL,
		source bigint(20)  NOT NULL,
		source_type varchar(30) NOT NULL,
		event char(10) NOT NULL,
		object2 bigint(20) ,
		object2_type VARCHAR(30),
		PRIMARY KEY (ID),
		KEY sourceindex(source),
		KEY dateindex (event_date),
		KEY object2index (object2)
		
	  )  ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
  }
}
 ?>
