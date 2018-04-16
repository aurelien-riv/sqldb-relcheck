<?php

namespace DBChecker\modules\SchemaIntegrityCheck;

use DBChecker\DBAL\AbstractDBAL;
use DBChecker\ModuleInterface;
use DBChecker\ModuleWorkerInterface;

class SchemaIntegrityCheck implements ModuleWorkerInterface
{
    private $config;

    public function __construct(ModuleInterface $module)
    {
        $this->config = $module->getConfig();
    }

    public function run(AbstractDBAL $dbal)
    {
        foreach ($this->config['mapping'] as $mapping)
        {
            $table = key($mapping);
            $expectedChecksum = $mapping[key($mapping)];
            $checksum = $dbal->getTableSchemaSha1sum($table);
            if ($checksum !== $expectedChecksum)
            {
                yield new SchemaIntegrityCheckMatch($dbal->getName(), $table, $checksum);
            }
        }

        if (! $this->config['allow_extras'])
        {
            yield from $this->checkForExtraTables($dbal);
        }
    }

    public function checkForExtraTables(AbstractDBAL $dbal)
    {
        foreach ($dbal->getTableNames() as $table)
        {
            foreach ($this->config['mapping'] as $mapping)
            {
                if ($table === key($mapping))
                {
                    continue 2;
                }
            }
            foreach ($this->config['ignore'] as $ignore)
            {
                if (preg_match('/' . $ignore . '/', $table))
                {
                    continue 2;
                }
            }
            yield new SchemaIntegrityCheckMatch($dbal->getName(), $table, 'unexpected table');
        }
    }

    public function generateConfig(DBQueriesInterface $dbQueries) : string
    {
        $ret = "schemaintegritycheck:\n";
        $ret .= "  mapping:\n";
        foreach ($dbQueries->getTableNames() as $table)
        {
            foreach ($this->config['ignore'] ?? [] as $ignore)
            {
                if (preg_match('/'.$ignore.'/', $table))
                {
                    continue 2;
                }
            }
            $checksum = $dbQueries->getTableSchemaSha1sum($table);
            if ($checksum)
            {
                $ret .= "    - $table: $checksum\n";
            }
        }
        return $ret;
    }
}
