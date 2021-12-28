<?php         
namespace Concrete\Package\PartnerManager\Controller\SinglePage\Dashboard;
use \Concrete\Core\Page\Controller\DashboardPageController;
use Loader;
use \Concrete\Package\PartnerManager\Src\CodePageList;

defined('C5_EXECUTE') or die(_("Access Denied."));
class PartnerManager extends DashboardPageController {
	public $helpers = array('form');

	public function view()
    {
        $codeList = new CodePageList();       
        $codeList->setItemsPerPage(25);
        $paginator = $codeList->getPagination();
        $pagination = $paginator->renderDefaultView();
        $this->set('codesList',$paginator->getCurrentPageResults());  
        $this->set('pagination',$pagination);
        $this->set('paginator', $paginator);
    }

	public function delete_check($sID) {
		$db = Loader::db();
		$db->Execute(
			'DELETE FROM btPartnerManager WHERE sID = ' . $sID
		);
		$this->set('success', t("Partner Deleted."));
		$this->view();
	}
	
	public function partner_added()
    {
        $this->set('success', t("Partner Added."));
        $this->view();
    }
	
	public function partner_updated()
    {
        $this->set('success', t("Partner Updated."));
        $this->view();
    }
	public function sortorder() {
		
	}


}

?>