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
 * Class MigrationsDiffDoctrineCommand
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_update
 */

namespace Cx\Core_Modules\Update\Model\Entity;

use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Doctrine\ORM\Tools\SchemaTool,
    Doctrine\DBAL\Version as DbalVersion,
    Doctrine\DBAL\Migrations\Configuration\Configuration;

/**
 * Command for generate migration classes by comparing your current database schema
 * to your mapping information.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_update
 */
class MigrationsDiffDoctrineCommand extends \Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand {

    /**
     * Configuration
     */
    protected function configure() {
        parent::configure();

        $this
                ->setName('migrations:diff')
                ->setDescription('Generate a migration by comparing your current database to your mapping information.')
                ->setHelp(<<<EOT
The <info>%command.name%</info> command generates a migration by comparing your current database to your mapping information:

    <info>%command.full_name%</info>

You can optionally specify a <comment>--editor-cmd</comment> option to open the generated file in your favorite editor:

    <info>%command.full_name% --editor-cmd=mate</info>
EOT
                )
                ->addOption('filter-expression', null, InputOption::VALUE_OPTIONAL, 'Tables which are filtered by Regular Expression.');
    }

    /**
     * Generate sql queries and create new migration class
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return null
     * @throws \InvalidArgumentException
     */
    public function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
            $isDbalOld = (DbalVersion::compare('2.2.0') > 0);
            $configuration = $this->getMigrationConfiguration($input, $output);

            $em = $this->getHelper('em')->getEntityManager();
            $conn = $em->getConnection();
            $platform = $conn->getDatabasePlatform();
            $metadata = $em->getMetadataFactory()->getAllMetadata();

            if (empty($metadata)) {
                $output->writeln('No mapping information to process.', 'ERROR');
                return;
            }

            if ($filterExpr = $input->getOption('filter-expression')) {
                if ($isDbalOld) {
                    throw new \InvalidArgumentException('The "--filter-expression" option can only be used as of Doctrine DBAL 2.2');
                }

                $conn->getConfiguration()
                        ->setFilterSchemaAssetsExpression($filterExpr);
            }

            $tool = new SchemaTool($em);

            $fromSchema = $conn->getSchemaManager()->createSchema();
            $toSchema = $tool->getSchemaFromMetadata($metadata);

            foreach ($fromSchema->getTables() AS $tableName => $table) {
                if (!$toSchema->hasTable($tableName)) {
                    // if drop the table from the $fromSchema, could not generate the DROP TABLE sql
                    $fromSchema->dropTable($tableName);
                }
            }

            //Not using value from options, because filters can be set from config.yml
            if (!$isDbalOld && $filterExpr = $conn->getConfiguration()->getFilterSchemaAssetsExpression()) {
                $tableNames = $toSchema->getTableNames();
                foreach ($tableNames as $tableName) {
                    $tableName = substr($tableName, strpos($tableName, '.') + 1);
                    if (!preg_match($filterExpr, $tableName)) {
                        $toSchema->dropTable($tableName);
                    }
                }
            }

            $up = $this->buildCodeFromSql($configuration, $fromSchema->getMigrateToSql($toSchema, $platform));
            $down = $this->buildCodeFromSql($configuration, $fromSchema->getMigrateFromSql($toSchema, $platform));

            if (!$up && !$down) {
                $output->writeln('No changes detected in your mapping information.', 'ERROR');
                return;
            }

            $version = date('YmdHis');
            $path = $this->generateMigration($configuration, $input, $version, $up, $down);

            $output->writeln(sprintf('Generated new migration class to "<info>%s</info>" from schema differences.', $path));
    }

    /**
     * create the sql queries
     *
     * @param Configuration $configuration
     * @param array         $sql
     *
     * @return string
     */
    private function buildCodeFromSql(Configuration $configuration, array $sql) {
        $currentPlatform = $configuration->getConnection()->getDatabasePlatform()->getName();
        $code = array(
            "\$this->abortIf(\$this->connection->getDatabasePlatform()->getName() != \"$currentPlatform\");", "",
        );
        foreach ($sql as $query) {
            if (strpos($query, $configuration->getMigrationsTableName()) !== false) {
                continue;
            }
            $code[] = "\$this->addSql(\"$query\");";
        }
        return implode("\n", $code);
    }

}
