<?php

namespace DBChecker\modules\DataIntegrityCheck;

use DBChecker\DBAL\AbstractDBAL;
use DBChecker\ModuleInterface;
use DBChecker\ModuleWorkerInterface;

/**
 * Compare the checksum of all the data in a table and the value stored in the config file
 */
class DataIntegrityCheck implements ModuleWorkerInterface
{
    private $config;

    public function __construct(ModuleInterface $module)
    {
        $this->config = $module->getConfig();
    }

    public function run(AbstractDBAL $dbal)
    {
        foreach ($this->config['mapping'] as $table => $expectedChecksum)
        {
            $checksum = $dbal->getTableDataSha1sum($table);
            if ($checksum !== $expectedChecksum)
            {
                yield new DataIntegrityCheckMatch($dbal->getName(), $table, $checksum);
            }
        }
    }

    /**
     * @param DBQueriesInterface $dbQueries
     * Proposes a new set of checksums for the configuration file
     */
    public function updateConfig(DBQueriesInterface $dbQueries)
    {
        echo "datainregritycheck";
        echo "  mapping:";
        foreach (array_keys($this->config['mapping']) as $table)
        {
            $checksum = $dbQueries->getTableDataSha1sum($table);
            if ($checksum)
            {
                echo "    - $table: $checksum\n";
            }
        }
    }

    /**
     * @param DBQueriesInterface $dbQueries
     * Generate a set of checksums for the configuration file
     */
    public function generateConfig(DBQueriesInterface $dbQueries)
    {
        echo "datainregritycheck";
        echo "  mapping:";
        foreach ($dbQueries->getTableNames()->fetchAll(\PDO::FETCH_COLUMN) as $table)
        {
            $checksum = $dbQueries->getTableDataSha1sum($table);
            if ($checksum)
            {
                echo "    - $table: $checksum\n";
            }
        }
    }
}