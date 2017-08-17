<?php
namespace CPTHelper;
use CPTHelper\FieldHelper;

// require_once("FieldHelper.php");

class MultiValued extends FieldHelper {

    protected $options = [];

    public function fieldDiv()
    {
        $id = $this->id;
        $values = $this->get();

        $m = "";
        $numvals = count($values);

        $br = '';
        for($k=0; $k<$numvals; $k++){
          $m.= $br.$this->fieldInput($k,$values[$k]);
          $br = '<br />';
        }
        // Do a few extra ones for further values.
        // probably would be good to have some javascript for this.
        for($k=$numvals; $k<$numvals+3; $k++){
          $m.= $br.$this->fieldInput($k,'');
          //$m.= $br."<input type='text' class='metafield' name='".$this->id."[".$k."]' value=''>";
          //$m.= "<input type='hidden' name='".$this->id."_old[".$k."]' value=''>";
          $br = '<br />';
        }

        return $m.$this->fieldExtra();
    }
    protected function fieldInput($k,$value){
      traceit("fieldinput ******************".print_r($value,true));
      $m.= "<input type='text' class='metafield' name='".$this->id."[".$k."]' value='".esc_attr($value)."'>";
      $m.= "<input type='hidden' name='".$this->id."_old[".$k."]' value='".esc_attr($value)."'>";
      return $m;
    }
    public function rqValue(){
        $res = [];
        for ($k=0; $k<count($_REQUEST[$this->id]); $k++){
          if ($_REQUEST[$this->id][$k]) $res[] = $_REQUEST[$this->id][$k];
        }
        return $res;
    }
    public function get()
    {
        if ($this->valueIsSet) return $this->value;
        global $post;
        return $this->setValue($post ? get_post_meta($post->ID, $this->id, false) : [] );
    }
    // Although it looks crude, deleting them all and re-inserting is the only way to avoid issues with multiple inserts and post preview
    public function update($post_id)
    {
        if ($post_id){
            $values = $this->setValue($this->rqValue());
            if (TRACEIT) traceit("Multi-FIELD UPDATE for post=".$post_id." = ".print_r($values,true));
            delete_post_meta($post_id,$this->id);
            for ($k=0; $k<count($values); $k++){
                if (!array_is_empty($values[$k])) add_post_meta($post_id, $this->id, $values[$k] );
            }
        }
    }
    public function update_undo($post_id)
    {
        if ($post_id){
            $values = $this->setValue($this->rqValue());
            $previous = $_REQUEST[$this->id."_old"];
            if (TRACEIT) traceit("Multi-FIELD UPDATE for post=".$post_id." = ".print_r($values,true)."/nPrevious = ".print_r($previous,true));
            for ($k=0; $k<count($values); $k++){
              if ($values[$k] != $previous[$k]){
                  // check empty/full for new and old values.
                  $changes=(array_is_empty($values[$k]) ? "E":"F").(array_is_empty($previous[$k])?"E":"F");
                  if (TRACEIT) traceit("---- changing ".$changes." new=".$post_id." = ".print_r($values[$k],true)."\nold=".print_r($previous[$k],true));

                  switch($changes){
                    case "FF": update_post_meta($post_id, $this->id, $values[$k], stripslashes($previous[$k]) );
                    break;
                    case "FE": add_post_meta($post_id, $this->id, $values[$k] );
                    break;
                    case "EF": delete_post_meta($post_id, $this->id, stripslashes($previous[$k]) );
                    break;
                  }
              }
            }
        }
    }



}




?>
