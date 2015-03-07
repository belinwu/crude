<?php

/**
 * The Crude class.
 */
class Crude {
    /**
     * The database connection.
     * @var \PDO
     */
    private $connection;

    /**
     * The Data Source Name.
     * @var string
     */
    private $dsn;

    /**
     * The username for the DSN string.
     * @var string
     */
    private $username = null;

    /**
     * The password for the DSN string.
     * @var string
     */
    private $password = null;

    /**
     * A (key => value) array of driver-specific connection options.
     * @var array
     */
    private $options = [];

    public function __construct(array $configs) {
        foreach ($configs as $key => $value) {
            $this->$key = $value;
        }

        try {
            $this->connection = new \PDO($this->dsn, $this->username, $this->password);
            foreach ($this->options as $attr => $value) {
                $this->connection->setAttribute($attr, $value);
            }
        } catch (\PDOException $e) {
            echo 'Can not create the connection! ' . $e->getMessage();
        }
    }

    public function query($query, callable $handler, array $params = []) {
        if (!is_null($handler) && ($result_set = $this->execute($query, $params))) {
            return call_user_func_array($handler, [$result_set]);
        }
    }

    public function columns($query, array $params = []) {
        return $this->query($query, function ($result_set) {
            $columns = [];
            while ($row = $result_set->fetchColumn()) {
                $columns[] = $row;
            }
            return $columns;
        }, $params);
    }

    public function column($query, array $params = []) {
        $columns = $this->columns($query, $params);
        return array_shift($columns);
    }

    public function named_columns($query, array $params = []) {
        return $this->query($query, function ($result_set) {
            $count = $result_set->columnCount();
            $named_columns = [];
            for ($i = 0; $i < $count; $i++) {
                $column = $result_set->getColumnMeta($i)['name'];
                $named_columns[$column] = [];
            }
            while ($row = $result_set->fetch(\PDO::FETCH_NUM)) {
                for ($i = 0; $i < $count; $i++) {
                    $named_columns[$column][] = $row[$i];
                }
            }
            return $named_columns;
        }, $params);
    }

    public function rows($query, array $params = []) {
        return $this->query($query, function ($result_set) {
            $rows = [];
            while ($row = $result_set->fetch(\PDO::FETCH_NUM)) {
                $rows[] = $row;
            }
            return $rows;
        }, $params);
    }

    public function maps($query, array $params = []) {
        return $this->query($query, function ($result_set) {
            $maps = [];
            while ($row = $result_set->fetch(\PDO::FETCH_ASSOC)) {
                $maps[] = $row;
            }
            return $maps;
        }, $params);
    }

    public function pairs($query, array $params = []) {
        return $this->query($query, function ($result_set) {
            $pairs = [];
            while ($row = $result_set->fetch(\PDO::FETCH_NUM)) {
                $pairs[$row[0]] = $row[1];
            }
            return $pairs;
        }, $params);
    }

    private function keyeds($query, callable $create_value, array $params) {
        return $this->query($query, function ($result_set) use ($column, $create_value) {
            $keyeds = [];
            if ($result_set->columnCount() < 0) {
                return $keyeds;
            }
            $column = $result_set->getColumnMeta(0)['name'];
            while ($row = $result_set->fetch(\PDO::FETCH_ASSOC)) {
                $keyeds[$row[$column]] = call_user_func_array($create_value, [$row]);
            }
            return $keyeds;
        }, $params);
    }

    private function keys_multis($query, callable $create_value, array $params) {
        return $this->query($query, function ($result_set) use ($create_value) {
            $column = $result_set->getColumnMeta(0)['name'];
            $keyeds = [];
            while ($row = $result_set->fetch(\PDO::FETCH_ASSOC)) {
                if (!isset($row[$column])) { 
                    return null; 
                }
                if (!isset($keyeds[$row[$column]])) {
                    $keyeds[$row[$column]] = [];
                }
                $keyeds[$row[$column]][] = call_user_func_array($create_value, [$row]);
            }
            return $keyeds;
        }, $params);
    }

    public function keys_maps($query, array $params = []) {
        return $this->keys_multis($query, function ($row) {
            return $row;
        }, $params);            
    }

    public function keys_rows($query, array $params = []) {
        return $this->key_multis($query, function ($row) {
            return array_values($row);
        }, $params);            
    }        

    public function key_maps($query, array $params = []) {
        return $this->keyeds($query, function ($row) {
            return $row;
        }, $params);
    }

    public function key_rows($query, array $params = []) {
        return $this->keyeds($query, function ($row) {
            return array_values($row);
        }, $params);
    }

    private function keyed_multis($query, callable $do_multi, array $params) {
        return $this->keyeds($query, function ($row) use ($do_multi) {
            $multi = [];
            foreach ($row as $key => $value) {
                list($table, $key) = explode('_', $key, 2);
                if (!isset($multi[$table])) {
                    $multi[$table] = [];
                }
                call_user_func_array($do_multi, [$table, $key, $value, &$multi]);
            }
            return $multi;
        }, $params);
    }

    public function keyed_multimaps($query, array $params = []) {
        return $this->keyed_multis($query, function ($table, $key, $value, &$multi) {
            $multi[$table][$key] = $value;
        }, $params);
    }

    public function keyed_multirows($query, array $params = []) {
        return $this->keyed_multis($query, function ($table, $key, $value, &$multi) {
            $multi[$table][] = $value;
        }, $params);
    }

    private function listed_multis($query, callable $do_multi, array $params) {
        return $this->query($query, function ($result_set) use ($do_multi) {
            $listeds = [];
            while ($row = $result_set->fetch(\PDO::FETCH_ASSOC)) {
                $multi = [];
                foreach ($row as $key => $value) {
                    list($table, $key) = explode('_', $key, 2);
                    if (!isset($multi[$table])) {
                        $multi[$table] = [];
                    }
                    call_user_func_array($do_multi, [$table, $key, $value, &$multi]);
                }
                $listeds[] = $multi;
            }
            return $listeds;
        }, $params);
    }

    public function multimaps($query, array $params = []) {
        return $this->listed_multis($query, function ($table, $key, $value, &$multi) {
            $multi[$table][$key] = $value;
        }, $params);
    }

    public function multirows($query, array $params = []) {
        return $this->listed_multis($query, function ($table, $key, $value, &$multi) {
            $multi[$table][] = $value;
        }, $params);
    }

    public function multirow($query, array $params = []) {
        $multirows = $this->multirows($query, $params);
        return array_shift($multirows);
    }

    public function row($query, array $params = []) {
        $rows = $this->rows($query, $params);
        return array_shift($rows);
    }

    public function map($query, array $params = []) {
        $maps = $this->maps($query, $params);
        return array_shift($maps);
    }

    public function multimap($query, array $params = []) {
        $multimaps = $this->multimaps($query, $params);
        return array_shift($multimaps);
    }

    public function update($query, array $params = []) {
        if ($statement = $this->execute($query, $params)) {
            return $statement->rowCount();
        }
    }

    public function batch($query, array $params = []) {
        $affected_counts = [];
        try {
            $statement = $this->connection->prepare($query);
            foreach ($params as $param) {
                if (!is_array($param)) { continue; }
                if ($statement->execute($param)) {
                    $affected_counts[] = $statement->rowCount();
                }
            }
        } catch (\PDOException $e) {
            // TODO log
            throw $e;
        }
        return $affected_counts;
    }

    private function execute($query, array $params = []) {
        try {
            $statement = $this->connection->prepare($query);
            $statement->execute($params);
        } catch (\PDOException $e) {
            // TODO log
            throw $e;
        }
        return $statement;
    }

    public function create($table, array $datas) {
        $ids = [];
        if (!isset($datas[0])) {
            $datas = [$datas];
        }
        foreach ($datas as $data) {
            $fields = implode(',', array_keys($data));
            $values = rtrim(str_repeat('?,', count($data)), ',');
            $query = 'INSERT INTO ' . $table . '(' . $fields . ') ' . 'VALUES(' . $values . ')';
            if ($this->execute($query, array_values($data))) {
                $ids[] = $this->connection->lastInsertId();
            }
        }
        return count($ids) > 1 ? $ids : $ids[0];
    }

    public function remove($table, $id, $column = 'id') {
        $query = 'DELETE FROM ' . $table . ' WHERE ' . $column . '=?';
        if ($statement = $this->execute($query, [$id])) {
            return $statement->rowCount();
        }
    }

    public function modify($table, array $columns, $id, $column = 'id') {
        $updates = implode('=?,', array_keys($columns)) . '=?';
        $query = 'UPDATE '. $table . ' SET ' . $updates .  ' WHERE ' . $column . '=?';
        if ($statement = $this->execute($query, array_values($columns + [$id]))) {
            return $statement->rowCount();
        }
    }

    public function select($table, $id, $columns = []) {
        $selections = !empty($columns) ? $columns : '*';
        $query = 'SELECT ' . $selections . ' FROM ' . $table . ' WHERE id=?';
        if ($result_set = $this->execute($query, [$id])) {
            return $result_set->fetch(\PDO::FETCH_ASSOC);
        }
    }

    public function transaction(callable $transactional) {
        if ($this->connection->inTransaction() || !is_callable($transactional)) {
            return; // report a exception or error. do not support recursive transactions.
        }
        $this->connection->beginTransaction();
        try {
            call_user_func_array($transactional, [$this]);
            return $this->connection->commit();
        } catch (\PDOException $e) {
            $this->connection->rollBack();
            return false;
        }
    }
}