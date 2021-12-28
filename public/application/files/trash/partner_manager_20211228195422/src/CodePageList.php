<?php      
namespace Concrete\Package\PartnerManager\Src;

use Database;
use Concrete\Core\Search\Pagination\Pagination;
use Concrete\Core\Search\ItemList\Database\ItemList as DatabaseItemList;
use Pagerfanta\Adapter\DoctrineDbalAdapter;

use Concrete\Package\PartnerManager\Src;
use Concrete\Package\PartnerManager\Src\Code;

class CodePageList extends DatabaseItemList {
	
	
	public function createQuery()
    {

        //$ord = $_SESSION['KARFU_user']['sortDirection'];

        $ord = $_REQUEST['order'];
        $order = (!empty($ord)) ? $ord : 'partner';
        $direction = $_REQUEST['setDir'];

        $this->query
        ->select('t.sID')
        ->from('btPartnerManager','t')
        ->orderby($order, $direction);

    }
	
	public function getResult($queryRow)
    {
        return Code::getByID($queryRow['sID']);
    }
	
	protected function createPaginationObject()
    {
        $adapter = new DoctrineDbalAdapter($this->deliverQueryObject(), function ($query) {
            $query->select('count(distinct t.sID)')->setMaxResults(1);
        });
        $pagination = new Pagination($this, $adapter);
        return $pagination;
    }
	
	public function getTotalResults()
    {
        $query = $this->deliverQueryObject();
        return $query->select('count(distinct t.sID)')->setMaxResults(1)->execute()->fetchColumn();
    }
	
}
?>