<?php 

    defined('C5_EXECUTE') or die("Access Denied.");

    $userString = "{$userName}'s";
?>


<div class="student-options">
	<a href="<?php echo $calLink ?>" title="<?php echo "{$userString} calendar"; ?>">
		<button class="cal"></button>
	</a>
	<a href="/account/edit_profile" title="<?php echo "{$userString} profile"; ?>">
		<button class="profile"></button>
	</a>
	<a href="<?php echo View::url('/login','logout'); ?>" title="Log-out">
		<button class="logout"></button>
	</a>
</div>