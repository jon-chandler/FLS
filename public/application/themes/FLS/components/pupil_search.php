<?php 
	defined('C5_EXECUTE') or die('Access Denied.'); 
?>
<div class="pupil-search <?php echo $class; ?>">
	<form method="get" action="<?php echo View::url('/pupil_list'); ?>" role="search">
		<input name="query" class="search-field" type="text" value="<?php echo htmlentities($query, ENT_COMPAT, APP_CHARSET)?>" maxlength="150" placeholder="<?php echo t('pupil name'); ?>" />
		<button class="data-call-btn" title="<?php echo t('Search'); ?>"></button>
	</form>
</div>