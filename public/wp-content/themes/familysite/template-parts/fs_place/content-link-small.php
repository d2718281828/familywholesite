<?php
global $cpost;
?>
<a href='<?php echo $cpost->permalink(); ?>'>
<div class='link-small fs_place'>
<?php echo $cpost->get("post_title"); ?>
</div>
</a>
