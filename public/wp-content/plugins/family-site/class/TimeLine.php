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
    foreach($res as $post) $m.= " ".$post["ID"];
    return $m;
  }
  protected function makeSQL(){
    global $wpdb;

    $select = ["P.ID"];
    $from = [$wpdb->posts." P "];
    $where = [];
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
}
 ?>
