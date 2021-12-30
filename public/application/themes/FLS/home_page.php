<?php
	defined('C5_EXECUTE') or die("Access Denied.");
	$this->inc('elements/header.php');
	$user = new User();
	$userIsLoggedIn = $user->isLoggedIn();
	$adminUsers = Group::getByName("Administrators");
?>

<main role="main">
	<div class="full-width">

		<?php
			if($user->inGroup($adminUsers)) {
				$this->inc('components/pupil_search.php', ['class' => 'homepage']);
			}
		?>

		<?php 
			$a = new Area('Full width content');
			$a->display($c);
		?>
	</div>
</main>

<?php $this->inc('elements/footer.php');
