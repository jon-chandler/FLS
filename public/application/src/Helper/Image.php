<?php

namespace Application\Helper;

use Core;
use Database;
use Aws\S3\S3Client;

class Image {

	public function getPlaceholder($path, $width, $height) {

		$url = $path . '/images/missing_images/' . $width . 'x' . $height . '.png';
		$filename = '.' . $url;
		$font =  __DIR__ . '/ArcherPro.ttf';

		$fontSize = ceil(($width/$height)*80);
		$scale = 2;
		$doubleWidth = $width*$scale;
		$doubleHeight = $height*$scale;
		$green = [43, 217, 92];

		if (!file_exists($filename)) {
			$bigImage = imagecreate($doubleWidth, $doubleHeight);
			imagesetthickness($bigImage, 20);

			$circleColour = imagecolorallocate($bigImage, $green[0], $green[1], $green[2]);
			$transBackground = imagecolorallocate($bigImage, 0, 0, 0);
			imagefilledrectangle ($bigImage, 0,0, $doubleWidth, $doubleHeight, $transBackground);
			imagecolortransparent($bigImage, $transBackground);
			imagearc($bigImage, $width, $height, $height, $height, 0, 360, $circleColour);


			$image = imagecreate($width, $height);
			$backgroundColour = imagecolorallocate($image, 149, 236, 174);
			$textColour = imagecolorallocate($image, $green[0], $green[1], $green[2]);

			imagecopyresampled($image, $bigImage, 0, 0, 0, 0, $width, $height, $doubleWidth, $doubleHeight);
			imagedestroy($bigImage);

			imagefill($image, 0, 0, $backgroundColour);

			$bounds = imagettfbbox($fontSize, 0, $font, '?');
			$textX = $width/2 - (($bounds[4] - $bounds[0])/2);
			$textY = $height/2 - (($bounds[5] - $bounds[1])/2);

			imagettftext($image, $fontSize, 0, $textX, $textY, $textColour, $font, '?');

			imagepng($image, $filename);
			imagedestroy($image);
		}

		return $url;

	}

	public function getManufacturerLogo($manufacturer) {

		$this->con = Database::connection();
		$r = $this->con->executeQuery("SELECT partner_logo FROM btPartnerManager WHERE partner LIKE '%{$manufacturer}%'");
		$res = $r->fetchColumn();
		
		$im = \Loader::helper('image');

		if ($res) {
			$file = \File::getByID($res);
			$thumb = $im->getThumbnail($file, 100, 100);
			$thumb->src;

			return $thumb->src;
		}
	}

	public function getMissingImage($vehicle) {

		$s3Path = 'https://karfu-files.s3.eu-west-2.amazonaws.com/vehicles';

		switch ($vehicle) {
			case 'C':
				$imagePath = "{$s3Path}/missing-image.svg";
			break;
			case 'L':
				$imagePath = "{$s3Path}/missing-image-van.svg";
			break;
			case 'K-ES':
				$imagePath = "{$s3Path}/missing-image-scooter.svg";
			break;
			case 'B':
				$imagePath = "{$s3Path}/missing-image-bike.svg";
			break;
			case 'A':
				$imagePath = "{$s3Path}/missing-image-alternative.svg";
			break;
			default:
				$imagePath = "{$s3Path}/missing-image.svg";
			break;
		}

		return $imagePath;
	}

	public function vehicleImageFromS3($path, $licencePlate) {

		$aws = new S3Client([
            'version' => 'latest',
            'region'  => 'eu-west-2',
            'credentials' => array(
                'key' => 'AKIA3P5CR7LHCQUA4NRQ',
                'secret' => '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'
            )
        ]);

		$img = "{$path}/{$licencePlate}.jpg";

        if($aws->doesObjectExist("karfu-files", $img)) {
            return $aws->getObjectUrl("karfu-files", $img);
        }

	}

}