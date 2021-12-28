<?php 
	defined('C5_EXECUTE') or die("Access Denied.");

	$user = new User();
	$userIsLoggedIn = $user->isLoggedIn();
?>
		<footer>
			FOOTER
		</footer>

		<?php View::element('footer_required'); ?>
		</div><?php // END OF PAGE WRAPPER. DO NOT REMOVE. JON ?>
		</div><?php // END OF SITE CONTAINER (the demo mobile container). DO NOT REMOVE. JON ?>
	</body>
</html>
