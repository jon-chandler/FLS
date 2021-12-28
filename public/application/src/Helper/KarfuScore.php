<?php

namespace Application\Helper;

use Core;
use Database;

class KarfuScore {

	public function getKarfuScore($capID) : array
	{
		$db = \Database::connection();
        $r = $db->executeQuery("SELECT * FROM karfu_score WHERE CapID= ?", [$capID]);        
        $res = $r->fetch();

        if($res) {
        	$res['KarfuScore'] = $this->sanitiseScore($res['KarfuScore'], 10);
        } else {
        	$res['error'] = 'Failed to gather the score';
        }
        
        return $res;
	}

	private function sanitiseScore(int $score, int $range) : string
	{
		return (($score / $range) * 100) . '%';
	}

}