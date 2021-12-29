<?php

	use Concrete\Core\View\View;
	
	defined('C5_EXECUTE') or die("Access Denied.");

	$v = View::getInstance();

	$user = new User();
	$userIsLoggedIn = $user->isLoggedIn();
	$isAdmin = Group::getByName("Administrators"); 
	$bodyClass[] = ($userIsLoggedIn) ? 'user-logged-in' : 'user-logged-out';


	if($user->inGroup($isAdmin)) {
		$bodyClass[] = 'admin-logged-in';
		$adminUser = true;
	}

	//$this->addFooterItem($html->javascript($this->getThemePath() . '/js/bundle.js'));

	$siteName = Config::get('concrete.site');
	$p = Page::getCurrentPage();
	$link = $p->getCollectionLink();
	$title = $p->getCollectionName();
	$desc = $p->getCollectionDescription();

	$loaderContent = $p->getAttribute('loader_content');

	
	if(!$p->isError() && $p->getCollectionID() == HOME_CID){
		$bodyClass[] = 'homepage';
	};
	if($c->isEditMode()) {
		$bodyClass[] = 'edit-mode';
	}

	if($c->getCollectionID() == HOME_CID) {
		$isHomePage = true;
	}


	if (!empty($p->getAttribute('content_image')))  {
		$i = File::getByID($p->getAttribute('content_image'));
		$shareImage = $i->getURL();
	} else {
		$shareImage = BASE_URL . $view->getThemePath() . '/images/social.png';
	}

	$classes = implode(' ', $bodyClass);

?>
<!DOCTYPE html>
	<html lang="<?php echo Localization::activeLanguage() ?>" class="<?php echo $classes; ?>">
		<head>
			<meta charset="utf-8">
			<link rel="shortcut icon" href="<?php echo $view->getThemePath()?>/fls.png" />
			<link rel="apple-touch-icon" href="<?php echo $view->getThemePath()?>/fls.png" >
			<link rel="icon" href="<?php echo $view->getThemePath()?>/fls.ico" type="image/x-icon" >
			<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,400;0,500;0,700;1,400">
			<link rel="stylesheet" type="text/css" href="<?php echo $view->getThemePath()?>/css/font-awesome.css">
			<link rel="stylesheet" type="text/css" href="<?php echo $view->getThemePath()?>/css/bundle.css">
			
			<meta name="viewport" content="width=device-width,user-scalable=no">
			<meta name="mobile-web-app-capable" content="yes">
			<meta name="apple-mobile-web-app-capable" content="yes">
			<meta name="apple-mobile-web-app-title" content="FLS">
			
			<meta property="og:site_name" content="<?php echo $siteName; ?>">
			<meta property="og:title" content="<?php echo $title; ?>">
			<meta property="og:type" content="website">
			<meta property="og:url" content="<?php echo $link; ?>">
			<meta property="og:description" content="<?php echo $desc; ?>">
			<meta property="og:image" content="<?php echo $shareImage; ?>">

			<meta name="twitter:card" content="summary_large_image">
			<meta name="twitter:title" content="<?php echo $title; ?>">
			<meta name="twitter:description" content="<?php echo $desc; ?>">
			<meta name="twitter:image" content="<?php echo $shareImage; ?>">



			<script>
				const pageTitle = "<?php echo $title; ?>";
				const pageDescription = "<?php echo $desc; ?>";
				const pageURL = "<?php echo $link; ?>";
			</script>


			<?php
				View::element('header_required', [
				'pageTitle' => isset($title) ? $siteName . ' - ' . $title : '',
				'pageDescription' => isset($desc) ? $desc : '',
				'pageMetaKeywords' => isset($pageMetaKeywords) ? $pageMetaKeywords : ''
				]);
			?>

			<?php if (Page::getCurrentPage()->isEditMode()): ?>
				<script src="https://code.jquery.com/jquery-2.2.4.js"></script>
			<?php endif; ?>

		</head>

	    <body class="<?php echo $classes; ?>">

	    <?php /* DO NOT REMOVE, PAGE WRAPPER ELEMENT IS NEEDED TO ENSURE THE EDITOR MODE FUNCTIONS CORRECTLY */ ?>
	    	<div class="site-container">

	    	<div class="<?php echo $c->getPageWrapperClass()?> content-wrapper">
	    		<noscript><div class="no-script">This site requires JavaScript</div></noscript>

	    		<header>
	    			<img src="<?php echo $view->getThemePath()?>/images/logo.jpg" alt="The Family Learning School" title="The Family Learning School" />
	    		</header>

