<?php
namespace Application\Tools\PageMaker;
use Database;
use URLify;

class PageMaker { 

	public function makePage($data) {

		// page id needed to call for data on page load
		$id = $data['contentID'];

		// Basic stuff needed to generate the page that should be sent in the GET request 
		$vehicleType = "/vehicles/{$data['vehicleType']}";

		$modelVariant = $data['vehicleName'];
		$modelDescription = $data['modelDescription'];
		$modelImage = $data['modelImage'];

		$section = \Page::getByPath($vehicleType);
		$pageType = \PageType::getByHandle('Vehicle');
		$pageTemplate = \PageTemplate::getByHandle('vehicle');

		$slug = URLify::filter($modelVariant);

		$vehicleDetails = array(
			//'cName' => $slug,
			'cName' => $modelVariant,
			'cDescription' => $modelDescription
		);

		$entry = $section->add($pageType, $vehicleDetails, $pageTemplate);
		$entry->setAttribute('vehicle_id', $id);
		$entry->setAttribute('content_image_src', $modelImage);
		//$entry->setCanonicalPagePath('/' . $slug);
		$entry->addAdditionalPagePath('/' . $slug);
	}

	public function killPage($slug) {
		$pge = \Page::getByPath($slug);
		$pge->delete();
	}

}

$pge = new PageMaker;
$data = $_GET;
$pge->makePage($data);

?>
