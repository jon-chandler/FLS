<?php
	defined('C5_EXECUTE') or die("Access Denied.");

	if(!$_REQUEST['comment']) {
    echo <<<MAIN
    <div class="full-width">
        <section class="feedback">
        <div class="err">MISSING DETAILS</div>
        </section>
    </div>
MAIN;
	}


