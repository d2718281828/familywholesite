<?php
namespace EntLoader;
use AdminPage\BaseAdmin;


class EntLoadHelp extends BaseAdmin {

  protected function setOpts(){
    $this->parms = ["Ent Load", "Ent Load", "activate_plugins", "ent_loader"]; // admin only
    $this->options = [
      ["Load", "thisloader"],
    ];
  }

  public function page_content() {
	  echo "<h2>Ent Load information</h2>";
	  echo "<p><input type='submit' name='action' value='Load'>Load up  people - requires nodes flder in uploads.</p>";
  }
  
  public function thisloader(){
	  return $this->parent->load();
  }

}
