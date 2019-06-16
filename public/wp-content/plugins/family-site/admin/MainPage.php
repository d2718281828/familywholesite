<?php 
namespace FamilySite;
use \AdminPage\BaseAdmin;

class MainPage extends BaseAdmin {

  protected function setOpts(){
    $this->parms = ["Editec MC", "Editec MC", "edit_posts", "editec_multichannel"];
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
	echo "Event Tools";
  }

}
