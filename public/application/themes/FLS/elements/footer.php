<?php 
	defined('C5_EXECUTE') or die("Access Denied.");

	$user = new User();
	$userIsLoggedIn = $user->isLoggedIn();
?>
		<footer>
			<address>
				<h4>The Family Learning School</h4>
				<span>
					Capital House<br />
					47 Rushey Green<br />
					Lewisham<br />
					London<br />
					SE6 4AS<br />
				</span>
				<a href="mailto:office@familylearningschool.com" title="contact">office@familylearningschool.com</a>
			</address>
		</footer>

		<?php View::element('footer_required'); ?>
		</div><?php // END OF PAGE WRAPPER. DO NOT REMOVE. JON ?>
		</div><?php // END OF SITE CONTAINER (the demo mobile container). DO NOT REMOVE. JON ?>
	</body>
</html>
