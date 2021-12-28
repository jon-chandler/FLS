<?php         
namespace Concrete\Package\PartnerManager\Controller\SinglePage\Dashboard;

use Controller;
use Loader;
use Database;

defined('C5_EXECUTE') or die(_("Access Denied."));
class SortCodeOrder extends Controller {
	   
	public function SortOrder()
   {
	   
	$uats = $_REQUEST['akID'];
	   
	if (is_array($uats)) {
		$uats = array_filter($uats, 'is_numeric');
	}
	
	if (count($uats)) {
		$db = Loader::db();
		for ($i = 0; $i < count($uats); $i++) {
			$v = array($uats[$i]);
			$db->query("update btPartnerManager set sortOrder = {$i} where sID = ?", $v);
        }
	}
	}
}