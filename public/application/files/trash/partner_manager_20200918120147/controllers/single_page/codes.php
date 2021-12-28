<?php         
namespace Concrete\Package\Partnermanager\Controller\SinglePage;
use Loader;
use \Concrete\Core\Page\Controller\PageController;
use \Concrete\Package\PartnerManager\Src\CodePageList;
use \Concrete\Core\Attribute\Key\CollectionKey as CollectionAttributeKey;
use Page;
use Core;
use Package;
use View;

defined('C5_EXECUTE') or die(_("Access Denied."));
class Codes extends PageController
{
	public $helpers = array('form');
	    public function view()
    {
		
		$c = Page::getCurrentPage();
        $codeList = new CodePageList();       
        $codeList->setItemsPerPage(20);
        $paginator = $codesList->getPagination();
        $pagination = $paginator->renderDefaultView();
        $this->set('codeslist',$paginator->getCurrentPageResults());  
        $this->set('pagination',$pagination);
        $this->set('paginator', $paginator);  
		
		$pkg = Package::getByHandle('partner_manager');
		$packagePath = $pkg->getRelativePath(); 
    }

}
