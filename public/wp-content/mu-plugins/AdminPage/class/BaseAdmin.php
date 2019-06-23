<?php
namespace AdminPage;
/*
Basic admin page or subpage
*/
abstract class BaseAdmin {

  protected $parent;    // PARENT plugin class object
  protected $upMenu;    // BaseAdminX object of parent
  protected $options;   // Action buttons
  protected $settings;  // a settings group
  protected $parms;
  protected $menulist = null;    // list of menus
  protected $css;                 // list of css files to enqueue
  protected $subPages;

  public function __construct($plugin,$dad=null) {
    $this->options = [];
    $this->css = [];
    $this->parent = $plugin;
    $this->upMenu = $dad;
    $this->settings = null;
    $this->subPages = [];

    //error_log(">>>>>>>>>>>>>>>>new base admin class>>>>>adding actions>>>>>>>>>>>>>>>>>>> ".get_class($this));
    $this->setOpts();

    // Notify the higher level menu of our details
    if ($this->upMenu) $this->upMenu->setSubPage($this->parms);

    add_action( "admin_menu", array($this, "plugin_menu") );
    add_action( 'admin_head', [$this,'queueCSS' ]);
    add_action( 'admin_init', [$this,'admin_init' ]);
  }
  /**
  * Parms: page title, menu title, capability, slug (arguments to add_menu_page
  */
  protected function setOpts(){
    $this->parms = ["Editec MC", "Editec MC", "edit_posts", "editec_multichannel"];
    $this->options = [
      ["Create", "createtab"],
    ];
  }
  public function setSubPage($parms){
    $this->subPages[] = [$parms[0],$parms[3]];
  }
  public function admin_init(){
    //error_log(">>>> admin init for page ".get_class($this));
  }
  public function set_data(){
    if (!$this->settings) return "";
    return $this->settings->set();
  }

  public function queueCSS(){
    foreach($this->css as $cssfile){
      $url = plugin_dir_url(__FILE__).'/css/'.$cssfile[1];
      $url = str_replace("admin/","",$url);
      wp_register_style( $cssfile[0], $url, false, '1.0.0' );
      //error_log("ENqueueing the CSS ".$cssfile[0]."=".$url);
      wp_enqueue_style( $cssfile[0] );
    }
  }
  public function plugin_menu() {
    // get_currentuserinfo(); // doesn't work at this hook
    //error_log(">>>> adding menu/submenu page ".get_class($this)."=".$this->parms[0]);
    if ($this->upMenu){
      //error_log("ADDING SUBMENU PAGE ".json_encode($this->parms));
      $parent = $this->upMenu->getID();
      add_submenu_page($parent,$this->parms[0],$this->parms[1],$this->parms[2],$this->parms[3], array($this, "show_menu_page"));
    } else {
      add_menu_page($this->parms[0],$this->parms[1],$this->parms[2],$this->parms[3], array($this, "show_menu_page"));
    }
  }
  public function getID(){
    return $this->parms[3];
  }
  public function show_menu_page() {
    $resp = "";
    // TODO this could be simpler now we have settings groups
    if (array_key_exists("action",$_REQUEST) ) {
      check_admin_referer( 'update_page_'.$this->parms[3] );
      foreach ($this->options as $opt) {
        error_log(">>> option ".$opt[0].",".$opt[1].(count($opt)>2?" MORE":""));
        if ($_REQUEST["action"] == $opt[0]) {
          $act = $opt[1];
          if (count($opt)>2 && method_exists($opt[2],$act)){
            $obj = $opt[2];
            $resp.= $obj->$act();
          } else {
            error_log(">>> option, doing ".$act);
            $resp.= $this->$act();
          }
        }
      }
    }
    if ($this->upMenu) {  // this runs on the child page
      echo '<div class="admin-page-content">';
      $this->upMenu->common_section();
      $this->upMenu->doTabs($this->parms[3]);
      echo '</div>';
    }
    if (count($this->subPages)>0){  // if it runs on a parent page
      $this->common_section();
      $this->doTabs("");
    }
    echo '<div class="admin-page-content">';
    echo '<p>'.$resp.'</p>';
    echo '<form method="post">';
    wp_nonce_field( 'update_page_'.$this->parms[3] );
    $this->page_content();
    echo '</form>';
    echo '</div>';
  }
  /**
  * This runs on the higher level page
  */
  public function doTabs($thispageid){
    $m = '<div class="tab-section"><ul>';
    foreach ($this->subPages as $sub){
      if ($sub[1]==$thispageid){
        $m.='<li class="tab-item current">'.$sub[0].'</li>';
      } else {
        $l = '<a href="/wp-admin/admin.php?page='.$sub[1].'">'.$sub[0].'</a>';
        $m.='<li class="tab-item alternate">'.$l.'</li>';
      }
    }
    $m.='</ul></div>';
    echo $m;
  }
  /**
  * Sub-levels of tabbing for child pages
  */
  public function doSubTabs($list,$current,$link){
    $m = '<div class="tab-section"><ul>';
    foreach ($list as $tab){
      if ($tab==$current){
        $m.='<li class="tab-item current">'.$tab.'</li>';
      } else {
        $l = '<a href="'.$link.$tab.'">'.$tab.'</a>';
        $m.='<li class="tab-item alternate">'.$l.'</li>';
      }
    }
    $m.='</ul></div>';
    echo $m;
  }
  /**
  * This is the main content of the form page, including settings. It should not show the
  * actual form tags.
  * It should echo, not return the text.
  */
  public function page_content() {
  }

  /**
  * This is the content of the common part which is shown on all pages. It should be defined
  * only on the top level, parent page.
  * It should echo, not return the text.
  */
  public function common_section() {
  }
  protected function yesno($val){
    if ($val) return "YES";
    return "no";
  }
  /**
  * General purpose function to print a table
  * @param $arr array list of rows, each of which is an associative array
  * @param $heads array - list of heading names - these correspond to the keys of the individual rows
  */
  protected function tabulate($arr,$heads){
    $m = '<table class="widefat">';
    $alt = true;
    $m.= '<thead>';
    $m.= '<tr>';
    foreach ($heads as $head){
      $m.= '<th>'.$head.'</th>';
    }
    $m.= '</tr>';
    $m.= '</thead>';
    $m.= '<tbody>';
    foreach ($arr as $row){
      $m.= '<tr>';
      foreach ($heads as $head){
        $m.= '<td>'.(isset($row[$head]) ? $row[$head] : "").'</td>';
      }
      $m.= '</tr>';
      $alt = !$alt;
    }
    $m.= '</tbody>';
    $m.= '</table>';
    return $m;
  }
  protected function getnonce(){

  }
  protected function fieldId($id){
    return $id;
  }
    /**
     * Format shortcode examples
     * @param $m
     * @return string
     */
    protected function xmp($m){
        return '<div class="xmp">'.wpautop($m).'</div>';
    }
    protected function editlink($postid){
      return "<a href='/wp-admin/post.php?post=$postid&action=edit' target='_blank'>$postid</a>";
    }
    protected function postTypeSelector($optid, $post_type, $noneoption = false){
        global $wpdb;
        $s = "select ID, post_title from ".$wpdb->posts." where post_type='$post_type' and post_status='publish' order by post_title";

        $res = $wpdb->get_results($s, ARRAY_A);

        $m = "<select name='$optid' id='$optid'>\n";

        $current = get_site_option($optid);

        if ($noneoption){
            $checked = (-1 == $current) ? " selected" : "";
            $m.= "<option value='-1'$checked>$noneoption</option>";
        }

        foreach($res as $item){
            $checked = ($item["ID"] == $current) ? " selected" : "";
            $m.= "<option value='".$item["ID"]."'$checked>".$item["post_title"]."</option>";
        }
        $m.= "</select>\n";
        return $m;
    }
}
?>
