<?php

namespace GuidoFaecke\DB2\Database;

use GuidoFaecke\DB2\Database\Query\Grammars\DB2Grammar as QueryGrammar;
use GuidoFaecke\DB2\Database\Query\Processors\DB2Processor;
use GuidoFaecke\DB2\Database\Query\Processors\DB2ZOSProcessor;
use GuidoFaecke\DB2\Database\Schema\Builder;
use GuidoFaecke\DB2\Database\Schema\Grammars\DB2ExpressCGrammar;
use GuidoFaecke\DB2\Database\Schema\Grammars\DB2Grammar as SchemaGrammar;
use Illuminate\Database\Connection;
use Illuminate\Database\Grammar;
use PDO;

/**
 * Class DB2Connection
 *
 * @package GuidoFaecke\DB2\Database
 */
class DB2Connection extends Connection
{
    /**
     * The name of the default schema.
     *
     * @var string
     */
    protected $defaultSchema;
    /**
     * The name of the current schema in use.
     *
     * @var string
     */
    protected $currentSchema;

    public function __construct(PDO $pdo, $database = '', $tablePrefix = '', array $config = [])
    {
        parent::__construct($pdo, $database, $tablePrefix, $config);
        $this->currentSchema = $this->defaultSchema = strtoupper($config['schema'] ?? null);
    }

    /**
     * Get the name of the default schema.
     *
     * @return string
     */
    public function getDefaultSchema(): string
    {
        return $this->defaultSchema;
    }

    /**
     * Reset to default the current schema.
     *
     * @return bool
     */
    public function resetCurrentSchema(): bool
    {
        return $this->setCurrentSchema($this->getDefaultSchema());
    }

    /**
     * Set the name of the current schema.
     *
     * @param $schema
     *
     * @return bool
     */
    public function setCurrentSchema($schema): bool
    {
        return $this->statement('SET SCHEMA ?', [strtoupper($schema)]);
    }

    /**
     * Execute a system command on IBMi.
     *
     * @param $command
     *
     * @return bool
     */
    public function executeCommand($command): bool
    {
        return $this->statement('CALL QSYS2.QCMDEXC(?)', [$command]);
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return Builder
     */
    public function getSchemaBuilder(): Builder
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new Builder($this);
    }

    /**
     * @return Grammar
     */
    protected function getDefaultQueryGrammar(): Grammar
    {
        $defaultGrammar = new QueryGrammar();

        if (array_key_exists('date_format', $this->config)) {
            $defaultGrammar->setDateFormat($this->config['date_format']);
        }

        if (array_key_exists('offset_compatibility_mode', $this->config)) {
            $defaultGrammar->setOffsetCompatibilityMode($this->config['offset_compatibility_mode']);
        }

        return $this->withTablePrefix($defaultGrammar);
    }

    /**
     * Default grammar for specified Schema
     *
     * @return Grammar
     */
    protected function getDefaultSchemaGrammar(): Grammar
    {
        switch ($this->config['driver']) {
            case 'db2_expressc_odbc':
                $defaultGrammar = $this->withTablePrefix(new DB2ExpressCGrammar());
                break;
            default:
                $defaultGrammar = $this->withTablePrefix(new SchemaGrammar());
                break;
        }

        return $defaultGrammar;
    }

    /**
     * Get the default post processor instance.
     *
     * @return DB2Processor|DB2ZOSProcessor
     */
    protected function getDefaultPostProcessor()
    {
        switch ($this->config['driver']) {
            case 'db2_zos_odbc':
                $defaultProcessor = new DB2ZOSProcessor();
                break;
            default:
                $defaultProcessor = new DB2Processor();
                break;
        }

        return $defaultProcessor;
    }
}
