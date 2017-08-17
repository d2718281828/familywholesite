<?php
namespace CPTHelper;
// use CPTHelper\SelectHelper;

// todo required and validation and CSS. is it even possible in WP admin?

class FieldHelper {
    protected $id;
    protected $label;
    protected $description;
    protected $options;
    protected $value;
    protected $valueIsSet = false;

    public function __construct($id,$label,$desc = "",$options = null)
    {
        if (TRACEIT) traceit("New FieldHelper::: ".get_class($this));
        $this->id = $id;
        $this->label = $label;
        $this->description = $desc;
        $this->options = $options ? $options : [];
    }

    /**
     * Hook for admin init, in case any types need this (the media selector might)
     */
    public function admin_init(){
    }
    /**
     * If you want the label after the field, just override this function.
     * @return string
     */
    // parametrise this - make it data???
    public function html(){
        return '<div class="metabox-field">'.
          '<div class="metabox-label">'.$this->labelDiv().'</div>'.$this->descDiv().
          '<div class="metabox-value">'.$this->fieldDiv().'</div>'.
          '</div>';
    }
    public function labelDiv()
    {
        return '<label for="'.$this->id.'">'.$this->label.'</label>';
    }
    public function fieldDiv()
    {
        $id = $this->id;
        return '<input type="text" id="'.$id.'" name="'.$id.'" class="metafield" value="'.esc_attr($this->get()).'">'.$this->fieldExtra();
    }
    public function descDiv()
    {
        return '<div class="metabox-field-desc">'.$this->description.'</div>';
    }
    public function rqValue(){
        return $_REQUEST[$this->id];
    }
    protected function setValue($valu)
    {
        $this->value = $valu;
        $this->valueIsSet = true;
        return $valu;
    }
    public function update($post_id)
    {
        if (TRACEIT) traceit("FIELD UPDATE for post=".$post_id);
        if ($post_id){
            update_post_meta($post_id, $this->id, $this->setValue($this->rqValue()));
        }
    }
    public function get()
    {
        if ($this->valueIsSet) return $this->value;
        global $post;
        return $this->setValue($post ? get_post_meta($post->ID, $this->id, true) : null);
    }

    /**
     * Any extra content to go straight after the field.
     * @return string
     */
    public function fieldExtra(){
        return "";
    }
}



?>
