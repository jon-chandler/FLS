<?php

require __DIR__ . '/../../../config.php';

return array(
	'default-connection' => 'concrete',
	'connections' => array(
		'concrete' => array(
			'driver' => 'c5_pdo_mysql',
			'server' => $CONFIG_DB_HOST . ':' . $CONFIG_DB_PORT,
			'database' => $CONFIG_DB_NAME,
			'username' => $CONFIG_DB_USER,
			'password' => $CONFIG_DB_PASSWORD,
			'charset' => 'utf8'
		)
	)
);
