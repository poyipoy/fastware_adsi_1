<?php

namespace App\Services\HR\Reconciliation;

use RuntimeException;

/** Parses literals only; never passes SQL to a database driver. */
class SqlDumpSnapshot
{
    public const TABLES = ['users', 'tc_job_positions', 'mst_job_positions', 'tc_poin_kategoris', 'mst_tcs', 'mst_soft_skills', 'mst_additionals', 'trs_penilaian_tcs', 'mst_pd_pengajuans', 'mst_pd_pengajuan_participants', 'detail_penilaian_tcs'];

    public function load(string $path, string $hash): array
    {
        if (! is_file($path) || ! is_readable($path) || filesize($path) > 33554432) {
            throw new RuntimeException('SNAPSHOT_UNREADABLE_OR_TOO_LARGE');
        }
        $sql = file_get_contents($path);
        if ($sql === false || ! hash_equals($hash, hash('sha256', $sql))) {
            throw new RuntimeException('SNAPSHOT_HASH_MISMATCH');
        }

        return $this->parse($sql) + ['sha256' => hash('sha256', $sql), 'bytes' => strlen($sql)];
    }

    public function parse(string $sql): array
    {
        $tables = array_fill_keys(self::TABLES, []);
        $schema = $foreignKeys = [];
        foreach ($this->statements($sql) as $statement) {
            if (preg_match('/\ACREATE TABLE `([\w]+)`\s*\((.*)\)\s*ENGINE=/s', $statement, $create) && in_array($create[1], self::TABLES, true)) {
                preg_match_all('/^\s*`(\w+)`\s+([^\r\n]+)/m', $create[2], $columns);
                $schema[$create[1]] = ['columns' => array_combine($columns[1], $columns[2]), 'unique_indexes' => []];
            }
            if (preg_match('/\A(?:ALTER TABLE|CREATE TABLE) `(\w+)`/', $statement, $ddl)) {
                preg_match_all('/UNIQUE KEY `(\w+)`\s*\(([^)]+)\)/', $statement, $keys, PREG_SET_ORDER);
                foreach ($keys as $key) {
                    preg_match_all('/`(\w+)`/', $key[2], $names);
                    $schema[$ddl[1]]['unique_indexes'][$key[1]] = $names[1];
                }
                preg_match_all('/FOREIGN KEY\s*\(`([^`]+)`\) REFERENCES `([^`]+)`\s*\(`([^`]+)`\)([^,;]*)/', $statement, $fks, PREG_SET_ORDER);
                foreach ($fks as $fk) {
                    $foreignKeys[] = ['table' => $ddl[1], 'column' => $fk[1], 'parent_table' => $fk[2], 'parent_column' => $fk[3], 'action' => trim($fk[4])];
                }
            }
            if (! preg_match('/\AINSERT\s+(?:IGNORE\s+)?INTO\s+`?(\w+)`?/i', $statement, $insert) || ! array_key_exists($insert[1], $tables)) {
                continue;
            }
            if (! preg_match('/\AINSERT\s+INTO\s+`?(\w+)`?\s*\(([^)]+)\)\s*VALUES\s*/i', $statement, $header)) {
                throw new RuntimeException('UNSUPPORTED_SNAPSHOT_INSERT');
            }
            $columns = array_map(fn ($s) => trim($s, " \t\n\r`"), explode(',', $header[2]));
            if (count(array_unique($columns)) !== count($columns)) {
                throw new RuntimeException('DUPLICATE_INSERT_COLUMN');
            }
            foreach ($this->tuples(substr($statement, strlen($header[0]))) as $tuple) {
                if (count($columns) !== count($tuple)) {
                    throw new RuntimeException('SNAPSHOT_COLUMN_COUNT_MISMATCH');
                }
                $row = [];
                foreach ($columns as $i => $column) {
                    $value = $this->literal($tuple[$i]);
                    if ($header[1] !== 'users' || in_array($column, ['id', 'npk'], true)) {
                        $row[$column] = $value;
                    }
                }
                $tables[$header[1]][] = $row;
            }
        }
        foreach ($schema as &$definition) {
            ksort($definition['unique_indexes']);
        }

        return ['tables' => $tables, 'schema' => $schema, 'foreign_keys' => $foreignKeys];
    }

    private function statements(string $sql): \Generator
    {
        $buffer = '';
        $quote = null;
        for ($i = 0, $length = strlen($sql); $i < $length; $i++) {
            $ch = $sql[$i];
            if ($quote !== null) {
                $buffer .= $ch;
                if ($ch === '\\' && $quote !== '`' && $i + 1 < $length) {
                    $buffer .= $sql[++$i];
                } elseif ($ch === $quote) {
                    if (($sql[$i + 1] ?? null) === $quote) {
                        $buffer .= $sql[++$i];
                    } else {
                        $quote = null;
                    }
                }

                continue;
            }
            if ($ch === '#' || (substr($sql, $i, 2) === '--' && ctype_space($sql[$i + 2] ?? ' '))) {
                while ($i < $length && $sql[$i] !== "\n") {
                    $i++;
                }
                $buffer .= "\n";
            } elseif (substr($sql, $i, 2) === '/*') {
                $end = strpos($sql, '*/', $i + 2);
                if ($end === false) {
                    throw new RuntimeException('UNTERMINATED_SQL_COMMENT');
                }
                if (str_contains(substr($sql, $i, $end - $i), 'NO_BACKSLASH_ESCAPES')) {
                    throw new RuntimeException('UNSUPPORTED_SQL_ESCAPE_MODE');
                }
                $i = $end + 1;
                $buffer .= ' ';
            } elseif (in_array($ch, ["'", '"', '`'], true)) {
                $quote = $ch;
                $buffer .= $ch;
            } elseif ($ch === ';') {
                if (trim($buffer) !== '') {
                    if (str_contains($buffer, 'NO_BACKSLASH_ESCAPES')) {
                        throw new RuntimeException('UNSUPPORTED_SQL_ESCAPE_MODE');
                    }
                    yield trim($buffer);
                }
                $buffer = '';
            } else {
                $buffer .= $ch;
            }
        }
        if ($quote !== null || trim($buffer) !== '') {
            throw new RuntimeException('UNTERMINATED_SQL_STATEMENT');
        }
    }

    private function tuples(string $values): \Generator
    {
        $i = 0;
        $length = strlen($values);
        while ($i < $length) {
            while ($i < $length && ctype_space($values[$i])) {
                $i++;
            }
            if ($i === $length) {
                return;
            }
            if ($values[$i++] !== '(') {
                throw new RuntimeException('EXPECTED_LITERAL_TUPLE');
            }
            $tuple = [];
            $token = '';
            $quote = $closed = false;
            for (; $i < $length; $i++) {
                $ch = $values[$i];
                if ($quote) {
                    $token .= $ch;
                    if ($ch === '\\' && $i + 1 < $length) {
                        $token .= $values[++$i];
                    } elseif ($ch === "'") {
                        if (($values[$i + 1] ?? null) === "'") {
                            $token .= $values[++$i];
                        } else {
                            $quote = false;
                        }
                    }
                } elseif ($ch === "'") {
                    $quote = true;
                    $token .= $ch;
                } elseif ($ch === ',' || $ch === ')') {
                    $tuple[] = trim($token);
                    $token = '';
                    if ($ch === ')') {
                        $i++;
                        $closed = true;
                        break;
                    }
                } else {
                    $token .= $ch;
                }
            }
            if (! $closed) {
                throw new RuntimeException('UNTERMINATED_LITERAL_TUPLE');
            }
            yield $tuple;
            while ($i < $length && ctype_space($values[$i])) {
                $i++;
            }
            if ($i < $length) {
                if ($values[$i++] !== ',') {
                    throw new RuntimeException('NON_LITERAL_INSERT_SUFFIX');
                }
                if (trim(substr($values, $i)) === '') {
                    throw new RuntimeException('TRAILING_TUPLE_SEPARATOR');
                }
            }
        }
    }

    private function literal(string $token): ?string
    {
        if (strcasecmp($token, 'NULL') === 0) {
            return null;
        }
        if (preg_match('/\A[-+]?\d+(?:\.\d+)?(?:[eE][-+]?\d+)?\z/', $token)) {
            return $token; // IDs never lose precision; NULL and zero remain distinct.
        }
        if (strlen($token) < 2 || $token[0] !== "'" || substr($token, -1) !== "'") {
            throw new RuntimeException('NON_LITERAL_SQL_VALUE');
        }
        $out = '';
        for ($i = 1, $end = strlen($token) - 1; $i < $end; $i++) {
            if ($token[$i] === '\\') {
                $next = $token[++$i] ?? '';
                $out .= ['0' => "\0", 'b' => "\x08", 'n' => "\n", 'r' => "\r", 't' => "\t", 'Z' => "\x1a", '%' => '\\%', '_' => '\\_'][$next] ?? $next;
            } elseif ($token[$i] === "'") {
                if (($token[$i + 1] ?? '') !== "'") {
                    throw new RuntimeException('MALFORMED_QUOTED_LITERAL');
                }
                $out .= "'";
                $i++;
            } else {
                $out .= $token[$i];
            }
        }

        return $out;
    }
}
