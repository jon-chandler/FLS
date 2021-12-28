<?php 
	defined('C5_EXECUTE') or die("Access Denied.");
	$p = Redirect::to('/your-profile-and-searches');
	$p->send();
	exit;
?>