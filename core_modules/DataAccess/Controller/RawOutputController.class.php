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

namespace Cx\Core_Modules\DataAccess\Controller;

/**
 * Raw Output Controller
 * @copyright   Cloudrexx AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_modules_dataaccess
 */
class RawOutputController extends OutputController
{
    /**
     * Return the raw data
     *
     * Implemented for use with MediaSource only; expects the 'data'
     * element to contain a structure as returned by MediaSource::get().
     * Sets the content type header to application/octet-stream.
     * Returns the contents of the first file found in the 'data' element.
     * @param   array   $data
     * @return  string                      Raw, stream-like data
     * @throws  MediaSourceException        unless exactly one file is present
     */
    public function parse($data)
    {
        if ($data['status'] === 'error') {
            return 'Error: ' . current($data['messages']['error']) . PHP_EOL;
        }
        // Catch invalid arguments, and skip to end of method
        if (!is_array($data['data'] ?? '')) {
            $data['data'] = [];
        }
        if (count($data['data']) > 1) {
            $data['data'] = [];
        }
        // Loops at most once.  Mind that each() is deprecated.
        foreach ($data['data'] as $path => $info) {
            if (($info['type'] ?? '') !== 'file') {
                continue;
            }
            $mediaSourceName = $info['source'] ?? '';
            $mediaSource = $this->cx->getMediaSourceManager()
                ->getMediaType($mediaSourceName);
            $filesystem = $mediaSource->getFileSystem();
            // Uses getFileList() to "verify" the file, which is not efficient
            $file = $filesystem->getFileFromPath($path);
            header('Content-Type: application/octet-stream');
            header(
                'Content-Disposition: attachment; filename="'
                . basename($path) . '"'
            );
            header('Last-Modified: ' . $filesystem->getModificationTime($file));
            // TODO: Support for ranges; add parameters to API first
            header('Content-Length: ' . $filesystem->getSize($file));
            return $filesystem->passthru($file);
        }
        // For consistency with the API
        throw new \Cx\Core\MediaSource\Model\Entity\MediaSourceException(
            '',
            \Cx\Core\MediaSource\Model\Entity\MediaSourceException::STATUS_400
        );
    }

}
