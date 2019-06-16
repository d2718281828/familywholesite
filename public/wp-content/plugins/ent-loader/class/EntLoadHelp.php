<?php
namespace EntLoader;
use AdminPage\BaseAdmin;


class EntLoadHelp extends BaseAdmin {

  protected function setOpts(){
    $this->parms = ["Ent Load", "Ent Load", "activate_plugins", "ent_loader"]; // admin only
    $this->options = [
      ["Load People", "thisloader"],
      ["Load Pics", "picsloader"],
      ["Fix Load Problems", "picsfixer"],
      ["Delete", "thiskiller"],
      ["Delete Pics", "killpics"],
      ["Complete Pics", "complete"],
    ];
  }

  public function page_content() {
	  echo "<h2>Ent Load information</h2>";
	  echo "<p><input type='submit' name='action' value='Load People'>Load up <strong>people</strong> - requires nodes folder in uploads.</p>";
	  echo "<p><input type='submit' name='action' value='Load Pics'>Load up <strong>pictures</strong> - whatever is in the album folder in uploads.</p>";
	  echo "<p><input type='submit' name='action' value='Fix Load Problems'> pictures which did not complete loading - those with ent_curly_desc. Dont Use</p>";
	  echo "<p><input type='submit' name='action' value='Delete'>Delete all ent-created posts - <strong>there is no warning!</strong></p>";
	  echo "<p><input type='submit' name='action' value='Delete Pics'>Delete just the pictures - <strong>there is no warning!</strong></p>";
	  echo "<p><input type='submit' name='action' value='Complete Pics'>Complete - e.g. things that need to wait until all pics have been loaded</p>";
  }
  
  public function thisloader(){
	  return $this->parent->loadPeople();
  }
  
  public function picsloader(){
	  return $this->parent->loadPics();
  }
  public function picsfixer(){
	  return $this->parent->fixPics();
  }
  
  public function thiskiller(){
	  return $this->parent->deleteAll();
  }
  public function killpics(){
	  return $this->parent->deleteAll(true);
  }
  public function complete(){
	  return $this->parent->complete();
  }

}
