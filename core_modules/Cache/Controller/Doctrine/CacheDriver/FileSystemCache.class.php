<?php

/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2015
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Cloudrexx" is a registered trademark of Cloudrexx AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

/**
 * Filesystem Driver Adapter for doctrine
 *
 * @copyright   Cloudrexx AG
 * @author      Ueli Kramer <ueli.kramer@comvation.com>
 * @package     cloudrexx
 * @subpackage  coremodule_cache
 */

namespace Cx\Core_Modules\Cache\Controller\Doctrine\CacheDriver;

/**
 * Filesystem Driver Adapter for doctrine
 *
 * @copyright   Cloudrexx AG
 * @author      Ueli Kramer <ueli.kramer@comvation.com>
 * @package     cloudrexx
 * @subpackage  coremodule_cache
 */
class FilesystemCache extends \Cx\Core_Modules\Cache\Controller\Doctrine\CacheDriver\FileCache
{
    const EXTENSION = '.tmp';

    /**
     * {@inheritdoc}
     */
    protected $extension = self::EXTENSION;

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {

        $data     = '';
        $lifetime = -1;
        $filename = $this->getFilename($id);

        if ( ! is_file($filename)) {
            return false;
        }

//        $resource = fopen($filename, "r");
//
//        if (false !== ($line = fgets($resource))) {
//            $lifetime = (integer) $line;
//        }
//
//        if ($lifetime != 0 && $lifetime < time()) {
//            fclose($resource);
//
//            return false;
//        }
//
//        while (false !== ($line = fgets($resource))) {
//            $data .= $line;
//        }
//
//        fclose($resource);

        list( $lifetime, $data ) = preg_split("/\r?\n/", file_get_contents( $filename ), 2);

        $ret = unserialize($data);

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        $lifetime = -1;
        $filename = $this->getFilename($id);

        if ( ! is_file($filename)) {
            return false;
        }

        $resource = fopen($filename, "r");

        if (false !== ($line = fgets($resource))) {
            $lifetime = (integer) $line;
        }

        fclose($resource);

        return $lifetime === 0 || $lifetime > time();
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        if ($lifeTime > 0) {
            $lifeTime = time() + $lifeTime;
        }
        $data       = serialize($data);
        $filename   = $this->getFilename($id);
        $filepath   = pathinfo($filename, PATHINFO_DIRNAME);

        if ( ! is_dir($filepath)) {
            mkdir($filepath, 0777, true);
        }

        return file_put_contents($filename, intval($lifeTime) . PHP_EOL . $data) !== false;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        foreach (new \DirectoryIterator($this->directory) as $file) {
            if ($file->isDir() && !$file->isDot()) {
                \Cx\Lib\FileSystem\FileSystem::delete_folder(str_replace('\\', '/', $file->getPath() .'/'. $file->getFilename()), true);
}
        }

        return true;
    }

    /**
     * Deletes all cache entries.
     *
     * @return boolean TRUE if the cache entries were successfully deleted, FALSE otherwise.
     */
    public function deleteAll()
    {
        return $this->doFlush();
    }
}
