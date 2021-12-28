<?php
	defined('C5_EXECUTE') or die("Access Denied.");
	$this->inc('elements/header.php');

	$sessionOpts = $_SESSION['KARFU_user'];
	$journeyType = $sessionOpts['currentJourneyGroup'];
	$journeyProgress = $sessionOpts['progress'];
	$prevPageName = $sessionOpts['previousPageName'];
	$prevLink = $sessionOpts['previousPage'];

	$journeyLink = '/compare';
	$linkTitle = 'Search';

	switch($journeyType) {
		case 'ev':
			$journeyLink = '/compage/ev';
			$linkTitle = 'Is an electric car right for me';
		break;
		case 'quick':
			$journeyLink = '/compare/quick-search';
			$linkTitle = 'Quick search';
		break;
		case 'full':
	}

	if($journeyType && $journeyProgress) {
		$link = $journeyLink;
	} else {
		$link = $prevLink;
		$linkTitle = $prevPageName;
	}

	if($prevPageName === 'Page Not Found' || empty($prevPageName)) {
		$link = '/';
		$linkTitle = 'Karfu home';
	}

?>

<main role="main">
	<div class="full-width message">
		<h1>404</h1>
		<h3>Page not found</h3>
		<br />
		<p>Perhaps try here</p>
		<a href="<?php echo $link; ?>" title="<?php echo $linkTitle; ?>"><button class="button-dark-green data-call-btn"><span><?php echo $linkTitle; ?></span><div class="button-loader"></div></button></a>
	</div>
</main>

<?php $this->inc('elements/footer.php'); ?>
