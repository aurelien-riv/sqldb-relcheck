<?php

namespace DBChecker\modules\SchemaIntegrityCheck;

use DBChecker\DBQueries\AbstractDbQueries;
use DBChecker\ModuleInterface;
use DBChecker\ModuleWorkerInterface;

class SchemaIntegrityCheck implements ModuleWorkerInterface
{
    private $config;

    public function __construct(ModuleInterface $module)
    {
        $this->config = $module->getConfig();
    }

    public function run(AbstractDbQueries $dbQueries)
    {
        foreach ($this->config['mapping'] as $table => $expectedChecksum)
        {
            $checksum = $dbQueries->getTableSchemaSha1sum($table);
            if ($checksum !== $expectedChecksum)
            {
                yield new SchemaIntegrityCheckMatch($dbQueries->getName(), $table, $checksum);
            }
        }

        if (! $this->config['allow_extras'])
        {
            yield from $this->checkForExtraTables($dbQueries);
        }
    }

    public function checkForExtraTables(DBQueriesInterface $dbQueries)
    {
        foreach ($dbQueries->getTableNames()->fetchAll(\PDO::FETCH_COLUMN) as $table)
        {
            if (!isset($this->config['mapping'][$table]))
            {
                foreach ($this->config['ignore'] as $ignore)
                {
                    if (preg_match('/' . $ignore . '/', $table))
                    {
                        continue 2;
                    }
                }
                yield new SchemaIntegrityCheckMatch($dbQueries->getName(), $table, 'unexpected table');
            }
        }
    }

    public function generateConfig(DBQueriesInterface $dbQueries)
    {
        echo "schemaintegrity:";
        echo "  mapping:";
        foreach ($dbQueries->getTableNames()->fetchAll(\PDO::FETCH_COLUMN) as $table)
        {
            if (isset($this->config['ignore']))
            {
                foreach ($this->config['ignore'] as $ignore)
                {
                    if (preg_match('/'.$ignore.'/', $table))
                    {
                        continue 2;
                    }
                }
            }
            $checksum = $dbQueries->getTableSchemaSha1sum($table);
            if ($checksum)
            {
                echo "    - $table: $checksum\n";
            }
        }
    }
}