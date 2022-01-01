<?php
	defined('C5_EXECUTE') or die("Access Denied.");
	$this->inc('elements/header.php');
	$p = Page::getCurrentPage();
?>

<main role="main">
	<h1 class="section-header"><?php echo $p->getCollectionName(); ?></h1>
	<div class="full-width">
		<?php 
			$a = new Area('Full width content');
			$a->display($c);
		?>
	</div>
</main>

<?php $this->inc('elements/footer.php');
