<?php        
namespace Concrete\Package\PartnerManager\Controller\SinglePage\Dashboard\PartnerManager;
use \Concrete\Core\Page\Controller\DashboardPageController;
use Loader;
use \Concrete\Core\User\EditResponse as UserEditResponse;

defined('C5_EXECUTE') or die(_("Access Denied."));
class AddPartner extends DashboardPageController {
	
	public function view() 
	{
		$this->requireAsset('core/file-manager');
        $this->requireAsset('core/sitemap');
        $this->requireAsset('redactor');
		$html = Loader::helper('html');
		$this->set('form', Loader::helper('form'));
	}
	
	public function edit($sID)
	{
		$html = Loader::helper('html');
		$this->set('form',Loader::helper('form'));
		
		$db = Loader::db();
		$q = "SELECT sID, partner, link, active, partner_logo FROM btPartnerManager WHERE sID = '{$sID}'";
		$r = $db->query($q);
		if ($r) {
			$row = $r->fetchRow();
			$this->set('partner', $row['partner']);
			$this->set('link', $row['link']);
			$this->set('active', $row['active']);
			$this->set('partner_logo', $row['partner_logo']);
			$this->set('sID', $row['sID']);
		}
	}
	

	public function edit_partner($sID)
	{
				
		if (!$this->error->has()) {
			$db = Loader::db();
			$data = array(
				'partner' => $_POST['partner'],
				'link' => $_POST['link'],
				'country' => $_POST['country'],
				'active' => $_POST['active'],
				'partner_logo' => $_POST['partner_logo'],
			);
			$db->update('btPartnerManager', $data, array('sID' => $sID));
			
			$this->redirect('/dashboard/partner_manager', 'partner_updated');
			
			} else {
				$sr = new UserEditResponse();
                $sr->setError($this->error);
        }
		
	}
	
	public function add_partner()
	{

		if (!$this->error->has()) {
			$db = Loader::db();
			$so = $db->GetOne('select max(sortOrder) from btPartnerManager');
			$so++;
			$db->execute('INSERT INTO btPartnerManager (partner, link, active, partner_logo) values(?, ?, ?, ?)',
                array(
                    $_POST['partner'],
                    $_POST['link'],
                    $_POST['active'],
                    $_POST['partner_logo']
                )
            );
	
	
			$this->redirect('/dashboard/partner_manager', 'partner_added');
	
		} else {
				$sr = new UserEditResponse();
                $sr->setError($this->error);
        }
    }



}
?>