<?php
	defined('C5_EXECUTE') or die("Access Denied.");
	$this->inc('elements/header.php');
	$user = new User();
	$userIsLoggedIn = $user->isLoggedIn();
	$adminUsers = Group::getByName("Administrators");

	$class1 = Group::getByName("Class 1");
	$class2 = Group::getByName("Class 2");
	$class3 = Group::getByName("Class 3");

	$isAdmin = $user->inGroup($adminUsers);

	$header = ($isAdmin) ? 'Search pupils' : '';
?>

<main role="main">
	<div class="full-width">

		<?php 
			if($_REQUEST['update']) {
				echo '<div class="user-msg"><div>Pupil details updated</div></div>';
			}
		?>

		<div class="homepage-options">
			<?php

				if($userIsLoggedIn) {
					$ui = UserInfo::getByID($user->getUserID());
					$userName = $ui->getAttribute('firstName');
				}

				if($isAdmin) {
					$this->inc('components/pupil_search.php', ['class' => 'homepage']);
				}

				if($user->inGroup($class1)) {
					$this->inc('components/student_options.php', ['calLink' => '/class_1', 'userName' => $userName]);
				}

				if($user->inGroup($class2)) {
					$this->inc('components/student_options.php', ['calLink' => '/class_2', 'userName' => $userName]);
				}

				if($user->inGroup($class3)) {
					$this->inc('components/student_options.php', ['calLink' => '/class_3', 'userName' => $userName]);
				}
			?>
		</div>

		<?php 
			$a = new Area('Full width content');
			$a->display($c);
		?>
	</div>
</main>

<?php $this->inc('elements/footer.php');
