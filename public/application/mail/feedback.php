<?php

	defined('C5_EXECUTE') or die("Access Denied.");

	$answerString = implode($answers);

	$subject = t("Karfu feedback");
	$bodyHTML = t("
		<html>
		<head>
			<title>KARFU</title>
			<link href='https://fonts.googleapis.com/css?family=Montserrat:400,500,700&amp;display=swap' rel='stylesheet'>
			<style>
				* {
					text-align: left:
				}
				ul {
					list-style-type: none;
				}
				a, .no-link {
					pointer-events: none;
					text-decoration: none;
				}
			</style>
		</head>
		<body style='text-align: center; font-family: Arial, sans-serif; color: #004313; font-size: 14px'>
			<table width='800px' style='padding: 30px'>
				<tr>
					<td style='background-color: #2BD95C; padding: 30px'>
						<img src='https://karfu-public-assets.s3.eu-west-2.amazonaws.com/email_assets/logo.png' width='120px' alt='KARFU' />
					</td>
				</tr>
				<tr>
					<td style='padding: 30px'>
						<p class='no-link'><strong>Page:</strong> %s</p>
						<p class='no-link'><strong>Previous page:</strong> %s</p>
						<hr />
						<p><strong>Comments:</strong> %s</p>
						<hr /><br /><br />
						<p><strong>User journey status:</strong></p>
						<ul>%s</ul>
						<hr />
					</td>
				</tr>
			</table>
		</body>
		</html>
	", $currPage, $prevPage, $comment, $answerString);
