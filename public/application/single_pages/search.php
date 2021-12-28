<?php
	defined('C5_EXECUTE') or die("Access Denied.");
?>
<div class="full-width">
	<section class="search-results">
		<?php
			// REMOVED WHILST A NEW DESIGN IS CONSIDERED
			//$search = BlockType::getByHandle('search'); 
			//$search->render('templates/karfu_search');

			$a = new Area('Full width content');
			$a->display($c);
		?>
	</section>
</div>