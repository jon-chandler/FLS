<?php

require __DIR__ . '/../../../config.php';


return array(
	'marketplace' => array(
		'enabled' => false
	),
	'session' => array(
		'handler' => 'database',
		'max_lifetime' => 86400
	),
	'external' => array(
		'news_overlay' => false,
		'news' => false,
	),
	'urls' => array(
		'background_url' => 'none',
	),
	'white_label' => array(
		'background_url' => 'none',
	),
	'external' => array(
		'news_overlay' => false,
		'news' => false,
	),
	'seo' => array(
		'redirect_to_canonical_url' => 1,
		'url_rewriting' => true,
		'url_rewriting_all' => false,
		'title_format' => '%1$s - %2$s',
    	'title_segment_separator' => ' - '
	),
	'icons' => append_config(array(
        'user_avatar'          => array(
            'width'   => 200,
            'height'  => 200
        )
    ))
);