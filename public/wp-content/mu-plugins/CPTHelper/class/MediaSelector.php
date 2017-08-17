<?php

namespace CPTHelper;
use CPTHelper\FieldHelper;

/**
* A selection field for something in the media library - it will bring up a modal which WP also uses within the media library,
* (allowing you to choose an image or upload a new one.)
* The value stored is just the post id for the media library item.
*/
class MediaSelector extends FieldHelper {

    public function admin_init(){
        //wp_enqueue_media();
        //wp_enqueue_script( 'WPMediaSetting', $this->plugin_url(__FILE__).'js/WPMediaSetting.js', [] );
    }
    protected function plugin_url($file){
        $f = plugin_dir_url($file);
        $z = strrpos($f,"/",-2);    // ignore the last char which is also a /
        if ($z===false) return $f;
        return substr($f,0,$z+1);   // +1 to include trailing slash
    }
    public function fieldDiv()
    {
        $id = $this->id;
        $val = $this->get();   // current media id

        // Get WordPress' media upload URL
        $upload_link = esc_url( get_upload_iframe_src( 'image') );

        // Get the image src
        $your_img_src = ($val)? wp_get_attachment_image_src( $val, 'full' ) : "";

        // For convenience, see if the array is valid
        $you_have_img = is_array( $your_img_src );

        // Your image container, which can be manipulated with js
        $m = '<div class="wpms-img-container">';
        if ( $you_have_img ) $m.= '<img src="'.$your_img_src[0].'" alt="" style="max-width:100%;" />';
        $m.= '</div>';

        // Your add & remove image links
        $m.= '<p class="hide-if-no-js">
            <a class="wpms-upload-custom-img '.($you_have_img ? 'hidden' : '').'"
              href="'.$upload_link.'">'. __('Set custom image'). '</a>';
        $m.= '<a class="wpms-delete-custom-img '.( ! $you_have_img  ?'hidden' : '').'"
              href="#">'.__('Remove this image') .'</a>
                </p>';
        // A hidden input to set and post the chosen image id
        $m.= '<input class="wpms-img-id '.$id.'" name="'.$id.'" type="hidden" value="'.esc_attr( $val ).'">';

        $q = '<div class="wrap-input">'.$m.'</div>';  // the javascript uses this wrapper

        return $q.$this->fieldExtra();
    }

}




?>
