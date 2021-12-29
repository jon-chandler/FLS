<?php

	defined('C5_EXECUTE') or die("Access Denied.");

	$subject = t("FLS password reset");
	$bodyHTML = t("
		<html>
		<head>
			<title>KARFU</title>
			<link href='https://fonts.googleapis.com/css?family=Montserrat:400,500,700&amp;display=swap' rel='stylesheet'>
		</head>
		<body style='text-align: center; font-family: Arial, sans-serif; color: #004313; font-size: 14px'>
			<table width='800px' style='padding: 30px'>
				<tr>
					<td style='background-color: #2BD95C; padding: 30px'>
						<img src='https://familylearningschool.s3.eu-west-2.amazonaws.com/email_assets/logo.png' width='120px' alt='KARFU' />
					</td>
				</tr>
				<tr>
					<td style='padding: 30px'>
						<h3>Dear %s</h3>
						<p>You have requested a new password for <strong>%s</strong></p>
						<p>Your username is: <strong>%s</strong><br />
						You can change your password <a href='%s'>here</a></p>
						<h4>Thank you</h4>
					</td>
				</tr>
			</table>
		</body>
		</html>
	", $uName, $siteName, $uName, $changePassURL);
