<?php
	defined('C5_EXECUTE') or die("Access Denied.");
	$this->inc('elements/header.php');
	$user = new User();
	$userIsLoggedIn = $user->isLoggedIn();
	$superUser = $user->isSuperUser();
?>

<main role="main">
	<h1>PAGE NAME</h1>
	<div class="full-width">
		<?php 
			$a = new Area('Full width content');
			$a->display($c);
		?>
	</div>
</main>

<?php $this->inc('elements/footer.php');
