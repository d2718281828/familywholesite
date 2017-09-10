<?php
namespace EntLoader;
use AdminPage\BaseAdmin;


class EntLoadHelp extends BaseAdmin {

  protected function setOpts(){
    $this->parms = ["Ent Load", "Ent Load", "edit_posts", "ent_loader"];
    $this->options = [
      ["Load", "thisloader"],
    ];
  }

  public function page_content() {
	  echo "<h2>I am here</h2>";
	  echo "<p><input type='submit' name='action' value='Load'></p>";
  }
  
  public function thisloader(){
	  return "loaded";
  }

}
