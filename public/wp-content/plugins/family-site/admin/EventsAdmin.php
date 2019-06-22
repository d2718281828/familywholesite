<?php 
namespace FamilySite;
use \AdminPage\BaseAdmin;

class EventsAdmin extends BaseAdmin {

  protected function setOpts(){
    $this->parms = ["FamilySite", "FamilySite", "activate_plugins", "family_site"]; // admin only
    $this->options = [
      ["Create", "createtab"],
    ];
  }
  /**
  * This is the main content of the form page, including settings. It should not show the
  * actual form tags.
  * It should echo, not return the text.
  */
  public function page_content() {
	echo "<h3>Event Tools</h3>";
  }

}
