<?php
global $cpost;
?>
<a class="link-small-wrap" href='<?php echo $cpost->permalink(); ?>'>
<div class='link-small fs_event'>
<?php echo $cpost->get("post_title"); ?>
</div>
</a>
