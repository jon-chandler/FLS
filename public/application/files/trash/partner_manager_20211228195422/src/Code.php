<?php      
namespace Concrete\Package\PartnerManager\Src;

use Concrete\Core\Foundation\ConcreteObject as ConcreteObject;
use Database;

use Concrete\Package\PartnerManager\Src\CodePageList;

defined('C5_EXECUTE') or die(_("Access Denied."));
class Code extends ConcreteObject
{
	 public static function getByID($sID) {
        $db = Database::get();
        $data = $db->GetRow("SELECT * FROM btPartnerManager WHERE sID=?", $sID);
        if(!empty($data)){
            $code = new Code();
            $code->setPropertiesFromArray($data);
        }
        return($code instanceof Code) ? $code : false;
    }  
	
	
}