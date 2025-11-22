<?php

// Config/Database.php

class Database {
    private $host = "localhost";
    private $db   = "pmi_darah";
    private $user = "root";
    private $pass = "";

    public $pdo;

    public function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->db};charset=utf8",
                $this->user,
                $this->pass
            );

            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } 
        catch (PDOException $e) {
            die("Koneksi gagal: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->pdo;
    }

    // Simple Query Builder Methods
    public function table($table) {
        return new QueryBuilder($this->pdo, $table);
    }
}

// Simple Query Builder Class
class QueryBuilder {
    private $pdo;
    private $table;
    private $where = [];
    private $params = [];
    private $select = '*';
    private $join = '';
    private $groupBy = '';
    private $orderBy = '';
    private $limit = '';

    public function __construct($pdo, $table) {
        $this->pdo = $pdo;
        $this->table = $table;
    }

    public function select($columns) {
        $this->select = $columns;
        return $this;
    }

    public function where($column, $operator, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->where[] = "$column $operator ?";
        $this->params[] = $value;
        return $this;
    }

    public function join($table, $condition, $type = 'INNER') {
        $this->join .= " $type JOIN $table ON $condition";
        return $this;
    }

    public function groupBy($columns) {
        $this->groupBy = "GROUP BY $columns";
        return $this;
    }

    public function orderBy($column, $direction = 'ASC') {
        if ($this->orderBy === '') {
            $this->orderBy = "ORDER BY $column $direction";
        } else {
            $this->orderBy .= ", $column $direction";
        }
        return $this;
    }

    public function limit($limit, $offset = 0) {
        $this->limit = "LIMIT $offset, $limit";
        return $this;
    }

    public function get() {
        $sql = "SELECT {$this->select} FROM {$this->table}";
        
        if ($this->join !== '') {
            $sql .= $this->join;
        }
        
        if (!empty($this->where)) {
            $sql .= " WHERE " . implode(' AND ', $this->where);
        }
        
        if ($this->groupBy !== '') {
            $sql .= " " . $this->groupBy;
        }
        
        if ($this->orderBy !== '') {
            $sql .= " " . $this->orderBy;
        }
        
        if ($this->limit !== '') {
            $sql .= " " . $this->limit;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->params);
        return $stmt;
    }

    public function orWhere($column, $operator, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
    
        $this->where[] = "OR $column $operator ?";
        $this->params[] = $value;
        return $this;
    }

    public function having($condition) {
        if ($this->groupBy === '') {
            $this->groupBy = "GROUP BY 1"; // Default group by
        }
        $this->groupBy .= " HAVING $condition";
        return $this;
    }

    public function getResultArray() {
        $stmt = $this->get();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRowArray() {
        $stmt = $this->get();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }   

    public function insert($data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute(array_values($data));
    }

    public function update($data) {
        $set = [];
        $params = [];
        
        foreach ($data as $column => $value) {
            $set[] = "$column = ?";
            $params[] = $value;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $set);
        
        if (!empty($this->where)) {
            $sql .= " WHERE " . implode(' AND ', $this->where);
            $params = array_merge($params, $this->params);
        }
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete() {
        $sql = "DELETE FROM {$this->table}";
        
        if (!empty($this->where)) {
            $sql .= " WHERE " . implode(' AND ', $this->where);
        }
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($this->params);
    }
}