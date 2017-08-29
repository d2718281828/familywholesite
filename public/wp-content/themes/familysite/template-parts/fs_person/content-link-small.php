<?php
global $cpost;
?>
<a href='<?php echo $cpost->permalink(); ?>'>
<div class='link-small fs_person'>
<?php echo $cpost->get("post_title"); ?>
</div>
</a>
