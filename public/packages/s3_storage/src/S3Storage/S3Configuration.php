<?php
namespace S3Storage;

use Aws\S3\S3Client;
use Concrete\Core\File\StorageLocation\Configuration\DeferredConfigurationInterface;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use \Concrete\Core\File\StorageLocation\Configuration\ConfigurationInterface;
use \Concrete\Core\File\StorageLocation\Configuration\Configuration;
use \Concrete\Core\Error\Error;
use League\Url\Url;

class S3Configuration extends Configuration implements ConfigurationInterface, DeferredConfigurationInterface
{

	public $bucket;

	public $key;

	public $secret;

	public $expire;

	public $expire_enabled;

	public $region;

	public $base_url;

	public $useIAM;

	public function hasPublicURL()
	{
		return true;
	}

	public function hasRelativePath()
	{
		return false;
	}

	public function loadFromRequest(\Concrete\Core\Http\Request $req)
	{
		$data = $req->get('fslType');
		$this->useIAM = $data['useIAM'];
		$this->bucket = $data['bucket'];
		$this->key = $data['key'];
		$this->secret = $data['secret'];
		$this->expire = $data['expire'];
		$this->expire_enabled = $data['expire_enabled'];
		$this->region = $data['region'];
		$this->base_url = $data['base_url'];

	}

	public function validateRequest(\Concrete\Core\Http\Request $req)
	{
		$e = new Error();
		$data = $req->get('fslType');
		$this->useIAM = $data['useIAM'];
		$this->bucket = $data['bucket'];
		$this->key = $data['key'];
		$this->secret = $data['secret'];
		$this->expire = $data['expire'];
		$this->expire_enabled = $data['expire_enabled'];
		$this->region = $data['region'];
		$this->base_url = $data['base_url'];

		if (!$this->bucket) {
			$e->add(t("You must set a S3 Bucket."));
		} elseif (!S3Client::isBucketDnsCompatible($this->bucket)) {
			$e->add(t('Invalid S3 Bucket Name'));
		}

		if (!$this->useIAM) {
			if (!$this->key) {
				$e->add(t("You must set a S3 Key."));
			}
			if (!$this->secret) {
				$e->add(t("You must set a S3 Secret."));
			}
			if ($this->expire_enabled && (intval($this->expire) !== 0 && strtotime($this->expire) === false)) {
				$e->add(t('Invalid Expire Time'));
			}
			if (!$this->region) {
				$e->add(t('You must supply a region, eg: us-east-1, us-west-1, eu-west-1'));
			}
		}
		return $e;
	}

	public function getAdapter()
	{
		return new AwsS3Adapter($this->getClient(), $this->bucket);
	}

	protected function getClient()
	{
		if ($this->useIAM) {
			return new S3Client(array());
		}

		if (!$this->region) {
			$this->region = 'us-east-1';
		}
		$conf = array(
			'credentials' => array(
				'key' => $this->key,
				'secret' => $this->secret
			),
			'region' => $this->region,
			'version' => 'latest'
		);

		$client = new S3Client($conf);
		return $client;
	}

	public function getPublicURLToFile($file)
	{
		$file = trim($file, '/');
		if ($this->expire_enabled) {
			$cmd = $this->getClient()->getCommand('GetObject', array(
				'Bucket' => $this->bucket,
				'Key' => $file
			));
			$expire = strtotime($this->expire);
			return $this->getClient()->createPresignedRequest($cmd, $expire)->getUri();
		}

		$url = $this->getClient()->getObjectUrl($this->bucket, $file);
		if (strlen($this->base_url)) {
			$url = Url::createFromUrl($this->base_url);
			$url->setPath($file);
		}
		return (string) $url;
	}

	public function getRelativePathToFile($file)
	{
		return $file;
	}
}

/**
 * This code is a hack because c5 broke autoloading and uses a serialized object so the class has to match >.<
 * Remember to blame Korvin for this...
 */
namespace Concrete\Package\S3Storage\Core\File\StorageLocation\Configuration;

class S3Configuration extends \S3Storage\S3Configuration
{

}
