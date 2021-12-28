<?php      
namespace Concrete\Package\PartnerManager;
use Package;
use BlockType;
use SinglePage;
use View;
use Loader;
use Route;

defined('C5_EXECUTE') or die(_("Access Denied."));

class Controller extends Package {

	protected $pkgHandle = 'partner_manager';
	protected $appVersionRequired = '5.7.4';
	protected $pkgVersion = '1.0.0';
		
	public function on_start()
	{
		Route::register('/PartnerManager/sortorder', '\Concrete\Package\PartnerManager\Controller\SinglePage\Dashboard\SortCodeOrder::SortOrder');
	}
 	
	public function getPackageName() 
	{
		return t("Partner manager");
	}

	public function getPackageDescription() 
	{
		return t("Manage Partners");
	}

	public function install() 
	{
		$pkg = parent::install();

		$page1 = SinglePage::add('/dashboard/partner_manager', $pkg);
        $page1->updateCollectionName(t('Partner manager'));
		
		$page2 = SinglePage::add('/dashboard/partner_manager/add_partner', $pkg);
		$page2->updateCollectionName(t('Add partner'));
	
		return $pkg;
	}
	public function uninstall() {
		parent::uninstall();
		$db = Loader::db();
		$db->Execute('DROP TABLE btPartnerManager');
	}

	public function upgrade() {
	    parent::upgrade();
	    $pkg = Package::getByHandle('partner_manager');
	}

}