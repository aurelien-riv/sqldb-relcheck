<?php

namespace DBChecker;

require_once 'DataIntegrityCheckMatch.php';

/**
 * Compare the checksum of all the data in a table and the value stored in the config file
 */
class DataIntegrityCheck
{
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function run()
    {
        $queries = $this->config->getQueries();
        foreach ($this->config->getDataintegrityConfig() as $table => $expectedChecksum)
        {
            $checksum = $queries->getTableSha1sum($table);
            if ($checksum !== $expectedChecksum)
                yield new DataIntegrityCheckMatch($table);
        }
    }
}