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
 * Db Class
 *
 * Database connection handler
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_db
 * @todo        make class a pure library
 */

namespace {
    /**
     * Factory callback for AdoDB NewConnection
     *
     * This is in global namespace for backwards compatibility to PHP 5.3
     * $ADODB_NEWCONNECTION = array($this, 'adodbPdoConnectionFactory');
     * leads to a "function name must be a string"
     * @deprecated Use Doctrine!
     * @return \Cx\Core\Model\CustomAdodbPdo
     */
    function cxAdodbPdoConnectionFactory() {
        $obj = new \Cx\Core\Model\CustomAdodbPdo(\Env::get('pdo'));
        return $obj;
    }
}

namespace Cx\Core\Model {

    /**
     * DB Exception
     *
     * @copyright   Cloudrexx AG
     * @author      Michael Ritter <michael.ritter@comvation.com>
     * @package     cloudrexx
     * @subpackage  core_db
     */
    class DbException extends \Exception {}

    /**
     * Db Class
     *
     * Database connection handler
     * @copyright   Cloudrexx AG
     * @author      Michael Ritter <michael.ritter@comvation.com>
     * @package     cloudrexx
     * @subpackage  core_db
     */
    class Db {

        /**
         * PDO instance
         * @var \PDO
         */
        protected $pdo = null;

        /**
         * AdoDB instance
         * @var \ADONewConnection
         */
        protected $adodb = null;

        /**
         * Doctrine entity manager instance
         * @var \Doctrine\ORM\EntityManager
         */
        protected $em = null;

        /**
         * Doctrine LoggableListener instance
         * @var \Gedmo\Loggable\LoggableListener
         */
        protected $loggableListener = null;

        /**
         * @var \Gedmo\Translatable\TranslationListener
         */
        protected $translationListener = null;

        /*
         * db instance
         * @var \Cx\Core\Model\Model\Entity/Db
         * */
        protected $db;

         /*
         * db user instance
         * @var \Cx\Core\Model\Model\Entity/DbUser
         * */
        protected $dbUser;

        /**
         * doctrine cache driver instance
         * @var mixed
         */
        protected $cacheDriver;

        /**
         * Creates a new instance of the database connection handler
         * @param \Cx\Core\Model\Model\Entity\Db $db Database connection details
         * @param \Cx\Core\Model\Model\Entity\DbUser $dbUser Database user details
         */
        public function __construct(\Cx\Core\Model\Model\Entity\Db $db, \Cx\Core\Model\Model\Entity\DbUser $dbUser, $cacheDriver) {
            $this->db = $db;
            $this->dbUser = $dbUser;
            $this->cacheDriver = $cacheDriver;
        }

        /**
         * Creates a new instance by using an existing database connection
         * @return  \Cx\Core\Model\Model\Entity\Db  $dbInfo Database connection infos
         * @return  \Cx\Core\Model\Model\Entity\DbUser  $dbUser Database user connection infos
         * @param   \PDO    $pdo    Existing PDO connection
         * @param   \ADONewConnection   $adoDb  Existing AdoDb connection based on $pdo
         * @param   \Cx\Core\Model\Controller\EntityManager $em Existing Entity Manager object based on $pdo
         * @return  \Cx\Core\Model\Db   Instance based on existing database connection
         */
        public static function fromExistingConnection(\Cx\Core\Model\Model\Entity\Db $dbInfo, \Cx\Core\Model\Model\Entity\DbUser $dbUser,
                                                      \PDO $pdo, \ADODB_pdo $adoDb, \Cx\Core\Model\Controller\EntityManager $em
        ) {
            // Bind database connection

            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $cacheDriver = $cx->getComponent('Cache')->getCacheDriver();
            $db = new static($dbInfo, $dbUser, $cacheDriver);
            $db->setPdoConnection($pdo);
            $db->setAdoDb($adoDb);
            $db->setEntityManager($em);
            return $db;
        }

        /**
         * Sets the username for loggable listener
         * @param string $username Username data as string
         */
        public function setUsername($username) {
            $this->loggableListener->setUsername($username);
        }

        /**
         * Initializes the PDO connection
         * @return \PDO PDO connection
         */
        public function getPdoConnection() {
            if ($this->pdo) {
                return $this->pdo;
            }
            $objDateTimeZone = new \DateTimeZone($this->db->getTimezone());
            $objDateTime = new \DateTime('now', $objDateTimeZone);
            $offset = $objDateTimeZone->getOffset($objDateTime);
            $offsetHours = floor(abs($offset)/3600);
            $offsetMinutes = round((abs($offset)-$offsetHours*3600) / 60);
            $offsetString = ($offset > 0 ? '+' : '-').($offsetHours < 10 ? '0' : '').$offsetHours.':'.($offsetMinutes < 10 ? '0' : '').$offsetMinutes;

            $dbCharSet = $this->db->getCharset();
            $this->pdo = new \PDO(
                'mysql:dbname=' . $this->db->getName() . ';charset=' . $dbCharSet . ';host=' . preg_replace('/:/', ';port=', $this->db->getHost()),
                $this->dbUser->getName(),
                $this->dbUser->getPassword(),
                array(
                    // Setting the connection character set in the DSN (see below new \PDO()) prior to PHP 5.3.6 did not work.
                    // We will have to manually do it by executing the SET NAMES query when connection to the database.
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '.$dbCharSet,
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET time_zone = \'' . $offsetString . '\'',
                    \PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
                )
            );
            $this->pdo->setAttribute(\PDO::ATTR_STATEMENT_CLASS, array('Doctrine\DBAL\Driver\PDOStatement', array()));

            // disable ONLY_FULL_GROUP_BY, STRICT_TRANS_TABLES mode
            // this is a temporary fix to ensure MySQL 5.7 compatability
            $statement = $this->pdo->query('SELECT @@SESSION.sql_mode');
            $modes = $statement->fetch(\PDO::FETCH_NUM);
            $sqlModes = explode(',', $modes[0]);
            $sqlModes = array_filter(
                $sqlModes,
                function($e) {
                    if (
                        in_array(
                            trim($e),
                            array(
                                'ONLY_FULL_GROUP_BY',
                                'STRICT_TRANS_TABLES',
                                'STRICT_ALL_TABLES',
                                'TRADITIONAL',
                                'NO_ZERO_DATE',
                                'NO_ZERO_IN_DATE',
                            )
                        )
                    ) {
                        return false;
                    }
                    return true;
                }
            );
            $this->pdo->exec('SET SESSION sql_mode = \'' . implode(',', $sqlModes) . '\'');

            \Env::set('pdo', $this->pdo);
            return $this->pdo;
        }

        /**
         * Bind initialized PDO connection
         * @param   \PDO    $pdo    Initialized PDO connection to be used as
         *                          database connection.
         */
        public function setPdoConnection($pdo) {
            $this->pdo = $pdo;
            \Env::set('pdo', $this->pdo);
        }

        /**
         * Returns the AdoDB connection
         * @deprecated Use Doctrine (getEntityManager()) instead
         * @global string $ADODB_FETCH_MODE
         * @return \ADONewConnection
         */
        public function getAdoDb() {
            if ($this->adodb) {
                return $this->adodb;
            }
            // Make sure, \Env::get('pdo') is set
            $this->getPdoConnection();

            global $ADODB_FETCH_MODE, $ADODB_NEWCONNECTION;

            // open db connection
            \Env::get('ClassLoader')->loadFile(ASCMS_LIBRARY_PATH.'/adodb/adodb.inc.php');
            $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
            $ADODB_NEWCONNECTION = 'cxAdodbPdoConnectionFactory';
            $this->adodb = \ADONewConnection('pdo');

            $errorNo = $this->adodb->ErrorNo();
            if ($errorNo != 0) {
                if ($errorNo == 1049) {
                    throw new DbException('The database is unavailable');
                } else {
                    throw new DbException($this->adodb->ErrorMsg() . '<br />');
                }
                unset($this->adodb);
                return false;
            }
            return $this->adodb;
        }

        /**
         * Sets the AdoDB connection
         * @param \ADONewConnection $adoDb Initialized AdoDB connection to be
         *                                 used for legacy database queries.
         */
        public function setAdoDb($adoDb) {
            $this->adodb = $adoDb;
        }

        /**
         * Returns the database info object
         * @return \Cx\Core\Model\Model\Entity\Db Database info object
         */
        public function getDb() {
            return $this->db;
        }

        /**
         * Adds YAML directories to entity manager
         * @param array $paths List of paths
         */
        public function addSchemaFileDirectories(array $paths) {
            if (!$this->em) {
                $this->getEntityManager();
            }

            $drivers = $this->em->getConfiguration()->getMetadataDriverImpl()->getDrivers();
            $drivers['Cx']->getLocator()->addPaths($paths);
        }

        /**
         * Returns the doctrine entity manager
         * @return \Doctrine\ORM\EntityManager
         */
        public function getEntityManager() {
            if ($this->em) {
                return $this->em;
            }

            $config = new \Doctrine\ORM\Configuration();

            $config->setResultCacheImpl($this->cacheDriver);
            $config->setMetadataCacheImpl($this->cacheDriver);
            $config->setQueryCacheImpl($this->cacheDriver);

            $config->setProxyDir(ASCMS_MODEL_PROXIES_PATH);
            $config->setProxyNamespace('Cx\Model\Proxies');

            /**
             * We set this to false as we only generate proxies by hand.
             * Use one of the following commands to do so:
             * ./cx workbench database update
             * ./cx workbench doctrine orm:generate-proxies
             */
            $config->setAutoGenerateProxyClasses(false);

            $connectionOptions = array(
                'pdo'       => $this->getPdoConnection(),
                'dbname'    => $this->db->getName(),
            );

            $evm = new \Doctrine\Common\EventManager();

            $chainDriverImpl = new \Doctrine\ORM\Mapping\Driver\DriverChain();
            $driverImpl = new \Cx\Core\Model\Controller\YamlDriver(array(
                ASCMS_CORE_PATH.'/Core'.'/Model/Yaml',             // Component YAML files
            ));
            $chainDriverImpl->addDriver($driverImpl, 'Cx');

            //loggable stuff
            $loggableDriverImpl = $config->newDefaultAnnotationDriver(
                ASCMS_LIBRARY_PATH.'/doctrine/Gedmo/Loggable/Entity' // Document for ODM
            );
            $chainDriverImpl->addDriver($loggableDriverImpl, 'Gedmo\Loggable');

            $this->loggableListener = new \Cx\Core\Model\Model\Event\LoggableListener();
            $this->loggableListener->setUsername('currently_loggedin_user');
            // in real world app the username should be loaded from session, example:
            // Session::getInstance()->read('user')->getUsername();
            $evm->addEventSubscriber($this->loggableListener);

            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $sluggableDriverImpl = $config->newDefaultAnnotationDriver(
                $cx->getCodeBaseLibraryPath() . '/doctrine/Gedmo/Sluggable'
            );
            $sluggableListener = new \Gedmo\Sluggable\SluggableListener();
            $sluggableListener->setAnnotationReader($sluggableDriverImpl);
            $evm->addEventSubscriber($sluggableListener);

            $timestampableDriverImpl = $config->newDefaultAnnotationDriver(
                $cx->getCodeBaseLibraryPath() . '/doctrine/Gedmo/Timestampable'
            );
            $chainDriverImpl->addDriver($timestampableDriverImpl,
                'Gedmo\Timestampable');
            $timestampableListener = new \Gedmo\Timestampable\TimestampableListener();
            //$timestampableListener->setAnnotationReader($cachedAnnotationReader);
            $evm->addEventSubscriber($timestampableListener);

            // Note that LANG_ID and other language constants/variables
            // have not been set yet!
            $translatableDriverImpl = $config->newDefaultAnnotationDriver(
                $cx->getCodeBaseLibraryPath() . '/doctrine/Gedmo/Translatable/Entity'
            );
            $this->translationListener = new \Gedmo\Translatable\TranslatableListener();
            $this->translationListener->setAnnotationReader($translatableDriverImpl);

            // Current language for backend mode is set in
            // \Cx\Core\LanguageManager\Controller\ComponentController::postResolve()
            // Current language for frontend mode is set in
            // \Cx\Core\Routing\Resolver::resolve()
            // Current language for command mode is set in
            // \Cx\Core\Core\Controller\Cx::loadContrexx()

            // We don't want automatic fallbacks as we want to control them.
            $this->translationListener->setTranslationFallback(false);
            $evm->addEventSubscriber($this->translationListener);

            // RK: Note:
            // This is apparently not yet present in this Doctrine version:
            //$sortableListener = new \Gedmo\Sortable\SortableListener();
            //$sortableListener->setAnnotationReader($cachedAnnotationReader);
            //$evm->addEventSubscriber($sortableListener);

            //tree stuff
            $treeListener = new \Gedmo\Tree\TreeListener();
            $evm->addEventSubscriber($treeListener);
            $config->setMetadataDriverImpl($chainDriverImpl);

            //table prefix
            $prefixListener = new \DoctrineExtension\TablePrefixListener($this->db->getTablePrefix());
            $evm->addEventListener(\Doctrine\ORM\Events::loadClassMetadata, $prefixListener);

            $config->setSqlLogger(new \Cx\Lib\DBG\DoctrineSQLLogger());

            $em = \Cx\Core\Model\Controller\EntityManager::create($connectionOptions, $config, $evm);

            //resolve enum, set errors
            $conn = $em->getConnection();
            foreach (array('enum', 'timestamp') as $type) {
                \Doctrine\DBAL\Types\Type::addType(
                    $type,
                    'Cx\Core\Model\Model\Entity\\' . ucfirst($type) . 'Type'
                );
                $conn->getDatabasePlatform()->registerDoctrineTypeMapping(
                    $type,
                    $type
                );
            }
            $conn->getDatabasePlatform()->registerDoctrineTypeMapping(
                'set',
                'string'
            );
            \Cx\Core\Model\Controller\YamlDriver::registerKnownEnumTypes($conn);

            $this->em = $em;
            return $this->em;
        }

        /**
         * Return the TranslationListener
         * @return \Gedmo\Translatable\TranslationListener
         * @author  Reto Kohli <reto.kohli@comvation.com>
         */
        public function getTranslationListener()
        {
            return $this->translationListener;
        }

        /**
         * Bind initialized Entity Manager
         * @param \Doctrine\ORM\EntityManager   $em Initialized Entity Manager
         *                                          to be used by doctrine.
         */
        public function setEntityManager($em) {
            $this->em = $em;
        }
    }
}
