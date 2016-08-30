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

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Cx\Core_Modules\Cache\Controller\Doctrine\CacheDriver;

/**
 * Base file cache driver.
 *
 * @since  2.3
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 * @package     cloudrexx
 * @subpackage  coremodule_cache
 */
abstract class FileCache extends \Cx\Core_Modules\Cache\Controller\Doctrine\CacheDriver\CacheProvider
{
    /**
     * The cache directory.
     *
     * @var string
     */
    protected $directory;

    /**
     * The cache file extension.
     *
     * @var string|null
     */
    protected $extension;

    /**
     * Constructor.
     *
     * @param string      $directory The cache directory.
     * @param string|null $extension The cache file extension.
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($directory, $extension = null)
    {
        if ( ! is_dir($directory) && ! @mkdir($directory, 0777, true)) {
            throw new \InvalidArgumentException(sprintf(
                'The directory "%s" does not exist and could not be created.',
                $directory
            ));
        }

        if ( ! is_writable($directory)) {
            throw new \InvalidArgumentException(sprintf(
                'The directory "%s" is not writable.',
                $directory
            ));
        }

        $this->directory = realpath($directory);
        $this->extension = $extension ?: $this->extension;
    }

    /**
     * Gets the cache directory.
     *
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * Gets the cache file extension.
     *
     * @return string|null
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * @param string $id
     *
     * @return string
     */
    protected function getFilename($id)
    {
        $hash = hash('sha256', $id);
        $path = implode(str_split($hash, 16), DIRECTORY_SEPARATOR);
        $path = $this->directory . DIRECTORY_SEPARATOR . $path;
        $id   = preg_replace('@[\\\/:"*?<>|]+@', '', $id);

        return $path . DIRECTORY_SEPARATOR . $id . $this->extension;
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        return @unlink($this->getFilename($id));
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        foreach ($this->getIterator() as $name => $file) {
            @unlink($name);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        $usage = 0;
        foreach ($this->getIterator() as $name => $file) {
            $usage += $file->getSize();
        }

        $free = disk_free_space($this->directory);

        return array(
            Cache::STATS_HITS               => null,
            Cache::STATS_MISSES             => null,
            Cache::STATS_UPTIME             => null,
            Cache::STATS_MEMORY_USAGE       => $usage,
            Cache::STATS_MEMORY_AVAILABLE   => $free,
        );
    }

    /**
     * @return \Iterator
     */
    private function getIterator()
    {
        $pattern = '/^.+\\' . $this->extension . '$/i';
        $iterator = new \RecursiveDirectoryIterator($this->directory);
        $iterator = new \RecursiveIteratorIterator($iterator);
        return new \RegexIterator($iterator, $pattern);
    }
}
