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
		$q = "SELECT sID, partner, partner_type, partner_offerings, link, locations, active, partner_logo, body_styles, hp_percent FROM btPartnerManager WHERE sID = '{$sID}'";
		$r = $db->query($q);
		if ($r) {
			$row = $r->fetchRow();
			$this->set('partner', $row['partner']);
			$this->set('partner_type', $row['partner_type']);
			$this->set('partner_offerings', $row['partner_offerings']);
			$this->set('link', $row['link']);
			$this->set('locations', $row['locations']);
			$this->set('active', $row['active']);
			$this->set('partner_logo', $row['partner_logo']);
			$this->set('body_styles', $row['body_styles']);
			$this->set('hp_percent', $row['hp_percent']);
			$this->set('sID', $row['sID']);
		}
	}
	

	public function edit_partner($sID)
	{
				
		if (!$this->error->has()) {
			$offerings = null;
			$body_styles = null;
			$hpPercent = null;

			if (!empty($_POST['partner_offerings'])) {
				$offerings = implode(',', $_POST['partner_offerings']);
			}

			if (!empty($_POST['body_styles'])) {
				$body_styles = implode(',', $_POST['body_styles']);
			}

			if (isset($_POST['hp_percent']) && is_numeric($_POST['hp_percent'])) {
				$hpPercent = (float) $_POST['hp_percent'];
			}
			
			$db = Loader::db();
			$data = array(
				'partner' => $_POST['partner'],
				'partner_type' => $_POST['partner_type'],
				'partner_offerings' => $offerings,
				'link' => $_POST['link'],
				'locations' => $_POST['locations'],
				'active' => $_POST['active'],
				'partner_logo' => $_POST['partner_logo'],
				'body_styles' => $body_styles,
				'hp_percent' => $hpPercent
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

		$hpPercent = 0;

		if (!$this->error->has()) {

			if(!empty($_POST['partner_offerings'])) {
				$offerings = implode(',', $_POST['partner_offerings']);
			}

			if(!empty($_POST['body_styles'])) {
				$body_styles = implode(',', $_POST['body_styles']);
			}

			if (isset($_POST['hp_percent']) && is_numeric($_POST['hp_percent'])) {
				$hpPercent = (float) $_POST['hp_percent'];
			}

			$db = Loader::db();
			$so = $db->GetOne('select max(sortOrder) from btPartnerManager');
			$so++;
			$db->execute('INSERT INTO btPartnerManager (partner, partner_type, partner_offerings, link, locations, active, partner_logo, body_styles, hp_percent) values(?, ?, ?, ?, ?, ?, ?, ?, ?)',
                array(
                    $_POST['partner'],
                    $_POST['partner_type'],
                    $offerings,
                    $_POST['link'],
                    $_POST['locations'],
                    $_POST['active'],
                    $_POST['partner_logo'],
                    $body_styles,
                    $hpPercent
                )
            );
	
	
			$this->redirect('/dashboard/partner_manager', 'partner_added');
	
		} else {
				$sr = new UserEditResponse();
                $sr->setError($this->error);
        }
    }

	public function getSelectValues($handle) 
	{
		$db = Loader::db();
		$id = $db->GetAll("SELECT akID FROM AttributeKeys WHERE akHandle = ?", array($handle));
		$values = $db->GetAll("SELECT d.value FROM atSelectOptions d JOIN atSelectSettings v ON d.avSelectOptionListID = v.avSelectOptionListID WHERE akID = ?", array($id[0]['akID']));

		return $values;

	}

	public function getBodyStyleOpts()
	{
		$db = Loader::db();
		$opts = $db->GetAll("SELECT attribute_name FROM karfu_attribute_map WHERE mapping_type = 'karfuBodyStyle'");
		
		return $opts;

	}
}
?>