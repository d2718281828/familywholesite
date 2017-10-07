<?php
namespace EntLoader;
use AdminPage\BaseAdmin;


class EntLoadHelp extends BaseAdmin {

  protected function setOpts(){
    $this->parms = ["Ent Load", "Ent Load", "activate_plugins", "ent_loader"]; // admin only
    $this->options = [
      ["Load", "thisloader"],
      ["Delete", "thiskiller"],
    ];
  }

  public function page_content() {
	  echo "<h2>Ent Load information</h2>";
	  echo "<p><input type='submit' name='action' value='Load'>Load up <strong>people</strong> - requires nodes folder in uploads.</p>";
	  echo "<p><input type='submit' name='action' value='Delete'>Delete all ent-created posts - <strong>there is no warning!</strong></p>";
  }
  
  public function thisloader(){
	  return $this->parent->load();
  }
  
  public function thiskiller(){
	  return $this->parent->deleteAll();
  }

}
