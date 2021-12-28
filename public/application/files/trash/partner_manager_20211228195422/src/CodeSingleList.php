<?php      
namespace Concrete\Package\PartnerManager\Src;

use Database;
use Concrete\Core\Search\Pagination\Pagination;
use Concrete\Core\Search\ItemList\Database\ItemList as DatabaseItemList;
use Pagerfanta\Adapter\DoctrineDbalAdapter;

use Concrete\Package\PartnerManager\Src;
use Concrete\Package\PartnerManager\Src\Code;

class CodeSingleList extends DatabaseItemList {
	
	
	 public function createQuery()
    {
        $this->query
        ->select('t.sID')
        ->from('btPartnerManager','t')
        ->orderBy('RAND()', 'DESC')
		->setMaxResults(1);
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