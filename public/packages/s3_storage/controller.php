<?php
namespace Concrete\Package\S3Storage;

defined('C5_EXECUTE') or die("Access Denied.");

use \Concrete\Core\File\StorageLocation\Type\Type;
use Illuminate\Filesystem\Filesystem;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use Symfony\Component\ClassLoader\Psr4ClassLoader;

/**
 *
 * @author Michael Krasnow <mnkras@gmail.com>
 *
 */

class Controller extends \Package
{

    protected $pkgHandle = 's3_storage';
    protected $appVersionRequired = '8.3.0';
    protected $pkgVersion = '3.0.3';

    public function getPackageDescription()
    {
        return t("File storage using Amazon S3.");
    }

    public function getPackageName()
    {
        return t("S3 Storage");
    }

    public function install()
    {
        $pkg = parent::install();
        Type::add('s3', 'Amazon S3', $pkg);

    }

    /**
     * @throws \Illuminate\Filesystem\FileNotFoundException
     */
    public function on_start()
    {
        $fs = new Filesystem;

        if (!class_exists(AwsS3Adapter::class)) {
            try {
                $fs->getRequire(__DIR__ . '/vendor/autoload.php');
            } catch (\Illuminate\Filesystem\FileNotFoundException $e) {
                throw new \Exception(t('You forgot to run composer :/'));
            }
        }

        $loader = new Psr4ClassLoader();
        $loader->addPrefix('\\S3Storage', __DIR__ . '/src/S3Storage/');
        //This is to account for c5 changing autoloading in 5.7.4 >.< (Korvin's fault)
        $loader->addPrefix(
            '\\Concrete\\Package\\S3Storage\\Core\\File\\StorageLocation\\Configuration',
            __DIR__ . '/src/S3Storage/'
        );
        $loader->addPrefix(
            '\\Concrete\\Package\\S3Storage\\Src\\File\\StorageLocation\\Configuration',
            __DIR__ . '/src/S3Storage/'
        );
	    $loader->addPrefix(
		    '\\Concrete\\Package\\S3Storage\\File\\StorageLocation\\Configuration',
		    __DIR__ . '/src/S3Storage/'
	    );
        $loader->register();

        //This is also needed because c5 won't autoload the class anymore
        \Core::bind(
            '\Concrete\Package\S3Storage\Src\File\StorageLocation\Configuration\S3Configuration',
            'S3Storage\S3Configuration'
        );
        \Core::bind(
            '\Concrete\Package\S3Storage\Core\File\StorageLocation\Configuration\S3Configuration',
            'S3Storage\S3Configuration'
        );
	    \Core::bind(
		    '\Concrete\Package\S3Storage\File\StorageLocation\Configuration\S3Configuration',
		    'S3Storage\S3Configuration'
	    );
    }
}
