<?php
namespace FamilySite;
use CPTHelper\MediaSelector2;

/**
* Extend the media selector to save the media type as well
* Option:
*	typefield is the name of the custom field which holds the type
*/
class mediaType extends MediaSelector2 {
	/**
	* Filter out any options which are not wanted. 
	* We dont want images
	*/
	protected function filterOptions($list){
		$res = [];
		for ($k=0; $k<count($list); $k++){
			if ($this->typeOfFile($list[$k][1])!=="img") $res[] =  $list[$k];
		}
		return $res;
	}
	/**
	* additional actions for when the value is set - add the type variable
	*/
	protected function updateAction($post_id,$v){
		if (!isset($this->options["typefield"])) return;
		update_post_meta($post_id, $this->options["typefield"], $this->typeOfFile($v));
	}
	/**
	* Return a 3 character file type: img for image, pdf, vid, aud, doc, htm, txt etc
	* It should relate to how the featured media can be shown. 
	*/
	protected function typeOfFile($fname){
		$p = strrpos($fname,".");
		if ($p===false) return "unk";
		$t = strtolower(substr($fname,$p+1));
		switch($t){
			case 'jpg':
			case 'jpeg':
			case 'png':
			case 'gif':
			case 'bmp':
			return 'img';
			
			case 'pdf':
			return 'pdf';
			
			case 'htm':
			case 'html':
			return 'htm';
			
			case 'mp3':
			case 'ogg':
			case 'wav':
			return 'aud';
			
			case 'txt':
			return 'txt';
			
			case 'doc':
			case 'ppt':
			case 'docx':
			case 'pptx':
			return 'doc';
			
			default:
			return 'unk';
		}
	}
}
