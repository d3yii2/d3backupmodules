<?php

namespace d3yii2\d3backupmodules\components;

use RuntimeException;
use Throwable;
use Yii;
use yii\db\ColumnSchema;
use yii\db\Connection;
use yii\db\Query;
use yii\db\Schema;

/**
 * Writes table rows to an SQL file as INSERT statements.
 *
 * Used for the data backup (see BackupBase::createSqlDump()).
 * Works through the Yii DB connection - mysqldump is not required.
 *
 * Errors of one table (missing table, wrong condition) do not stop the
 * dump - the error is written to the SQL file as a comment, logged and
 * saved in $errors.
 *
 * Example:
 *  $dump = new SqlDump($filePath);
 *  $dump->table('inv_invoice', ['id' => $ids]);
 *  $dump->table('user', ['id' => $userIds], [], ['password_hash' => '', 'auth_key' => '']);
 *  $dump->close();
 */
class SqlDump
{
    /** @var int rows in one INSERT statement */
    public int $batchSize = 100;

    /** @var array rows count by table [table => count] */
    public array $stats = [];

    /** @var array errors by table [table => message] */
    public array $errors = [];

    private Connection $db;

    /** @var resource|null */
    private $handle;

    public string $filePath;

    public function __construct(string $filePath, ?Connection $db = null)
    {
        $this->filePath = $filePath;
        $this->db = $db ?? Yii::$app->db;
        if (!$this->handle = fopen($filePath, 'wb')) {
            throw new RuntimeException('Cannot create SQL file: ' . $filePath);
        }
        $this->write(
            '-- Data backup' . PHP_EOL
            . '-- Created: ' . date('Y-m-d H:i:s') . PHP_EOL
            . 'SET NAMES utf8mb4;' . PHP_EOL
            . 'SET FOREIGN_KEY_CHECKS = 0;' . PHP_EOL . PHP_EOL
        );
    }

    /**
     * Writes rows of the table matching the condition
     *
     * @param string $table table name
     * @param array|string|null $condition Yii where condition (may contain sub queries); null - all rows
     * @param string[] $excludeColumns columns not to export (column must be nullable or have a default value)
     * @param array $maskColumns columns exported with a fixed value instead of the real one
     *        [column => value], e.g. ['password_hash' => ''] for NOT NULL secret columns
     * @return int exported rows count
     */
    public function table(string $table, $condition = null, array $excludeColumns = [], array $maskColumns = []): int
    {
        $count = 0;
        try {
            if (!$schema = $this->db->getTableSchema($table, true)) {
                throw new RuntimeException('Table not found');
            }
            $columns = array_values(array_diff($schema->getColumnNames(), $excludeColumns));
            $query = (new Query())
                ->select($columns)
                ->from($table);
            if ($condition !== null) {
                $query->where($condition);
            }
            if ($schema->primaryKey) {
                $query->orderBy(array_fill_keys($schema->primaryKey, SORT_ASC));
            }

            $this->write('-- Table ' . $table . PHP_EOL);
            $insert = 'INSERT INTO ' . $this->db->quoteTableName($table)
                . ' (' . implode(', ', array_map([$this->db, 'quoteColumnName'], $columns)) . ') VALUES' . PHP_EOL;

            foreach ($query->batch($this->batchSize, $this->db) as $rows) {
                $values = [];
                foreach ($rows as $row) {
                    $rowValues = [];
                    foreach ($columns as $column) {
                        $value = array_key_exists($column, $maskColumns) ? $maskColumns[$column] : $row[$column];
                        $rowValues[] = $this->value($value, $schema->getColumn($column));
                    }
                    $values[] = '(' . implode(', ', $rowValues) . ')';
                }
                /** full statement is built before writing - the file never has a half statement */
                $this->write($insert . implode(',' . PHP_EOL, $values) . ';' . PHP_EOL);
                $count += count($rows);
            }
            $this->write('-- ' . $table . ': ' . $count . ' rows' . PHP_EOL . PHP_EOL);
        } catch (Throwable $e) {
            $this->errors[$table] = $e->getMessage();
            $this->write('-- ERROR ' . $table . ': ' . str_replace(["\r", "\n"], ' ', $e->getMessage()) . PHP_EOL . PHP_EOL);
            Yii::error('SqlDump table ' . $table . ': ' . $e->getMessage(), 'd3backupmodules');
        }
        $this->stats[$table] = ($this->stats[$table] ?? 0) + $count;
        return $count;
    }

    /**
     * Total exported rows
     */
    public function getTotalRows(): int
    {
        return array_sum($this->stats);
    }

    /**
     * Writes the footer and closes the file
     */
    public function close(): void
    {
        if ($this->handle) {
            $this->write('SET FOREIGN_KEY_CHECKS = 1;' . PHP_EOL);
            fclose($this->handle);
            $this->handle = null;
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * Value as SQL literal
     *
     * @param mixed $value
     */
    private function value($value, ?ColumnSchema $column): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }
        if ($column && $column->type === Schema::TYPE_BINARY) {
            return $value === '' ? "''" : '0x' . bin2hex($value);
        }
        return $this->db->quoteValue((string)$value);
    }

    private function write(string $text): void
    {
        if (fwrite($this->handle, $text) === false) {
            throw new RuntimeException('Cannot write SQL file: ' . $this->filePath);
        }
    }
}
