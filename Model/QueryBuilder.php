<?php
class QueryBuilder {
    private $pdo;
    private $table;
    private $query = '';
    private $params = [];
    private $whereConditions = [];
    private $joins = [];
    private $orderBy = [];
    private $groupBy = [];
    private $having = [];

    public function __construct($pdo, $table) {
        $this->pdo = $pdo;
        $this->table = $table;
    }

    public function select($columns = '*') {
        $this->query = "SELECT $columns FROM $this->table";
        return $this;
    }

    // Convenience alias used in many models; keeps existing code working
    public function get($columns = '*') {
        return $this->select($columns);
    }

    public function join($table, $condition, $type = 'INNER') {
        $this->joins[] = "$type JOIN $table ON $condition";
        return $this;
    }

    public function where($column, $value, $operator = '=') {
        $this->whereConditions[] = "$column $operator ?";
        $this->params[] = $value;
        return $this;
    }

    public function orWhere($column, $value, $operator = '=') {
        if (empty($this->whereConditions)) {
            return $this->where($column, $value, $operator);
        }
        $this->whereConditions[] = "OR $column $operator ?";
        $this->params[] = $value;
        return $this;
    }

    public function groupBy($column) {
        $this->groupBy[] = $column;
        return $this;
    }

    public function having($condition) {
        $this->having[] = $condition;
        return $this;
    }

    public function orderBy($column, $direction = 'ASC') {
        $this->orderBy[] = "$column $direction";
        return $this;
    }

    public function insert($data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $this->query = "INSERT INTO $this->table ($columns) VALUES ($placeholders)";
        $this->params = array_values($data);
        
        return $this->execute();
    }

    public function update($data) {
        $setClause = implode(', ', array_map(function($col) {
            return "$col = ?";
        }, array_keys($data)));
        
        $this->query = "UPDATE $this->table SET $setClause";
        $this->params = array_values($data);
        
        if (!empty($this->whereConditions)) {
            $this->query .= " WHERE " . implode(' AND ', $this->whereConditions);
        }
        
        return $this->execute();
    }

    public function getResultArray() {
        if (empty(trim($this->query))) {
            // default to selecting all columns when query string wasn't set explicitly
            $this->query = "SELECT * FROM $this->table";
        }
        $this->buildQuery();
        
        try {
            if (empty(trim($this->query))) {
                error_log("QueryBuilder Error: Empty query provided to PDO::prepare");
                return [];
            }
            $stmt = $this->pdo->prepare($this->query);
            $stmt->execute($this->params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("QueryBuilder Error: " . $e->getMessage());
            return [];
        }
    }

    public function getRowArray() {
        if (empty(trim($this->query))) {
            // default to selecting all columns when query string wasn't set explicitly
            $this->query = "SELECT * FROM $this->table";
        }
        $this->buildQuery();
        
        try {
            if (empty(trim($this->query))) {
                error_log("QueryBuilder Error: Empty query provided to PDO::prepare");
                return null;
            }
            $stmt = $this->pdo->prepare($this->query);
            $stmt->execute($this->params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("QueryBuilder Error: " . $e->getMessage());
            return null;
        }
    }

    public function execute() {
        try {
            if (empty(trim($this->query))) {
                error_log("QueryBuilder Execute Error: Empty query provided to PDO::prepare");
                return false;
            }
            $stmt = $this->pdo->prepare($this->query);
            return $stmt->execute($this->params);
        } catch (PDOException $e) {
            error_log("QueryBuilder Execute Error: " . $e->getMessage());
            return false;
        }
    }

    private function buildQuery() {
        if (strpos($this->query, 'SELECT') === 0) {
            // Add joins
            if (!empty($this->joins)) {
                $this->query .= ' ' . implode(' ', $this->joins);
            }
            
            // Add WHERE conditions
            if (!empty($this->whereConditions)) {
                $this->query .= ' WHERE ' . implode(' AND ', $this->whereConditions);
            }
            
            // Add GROUP BY
            if (!empty($this->groupBy)) {
                $this->query .= ' GROUP BY ' . implode(', ', $this->groupBy);
            }
            
            // Add HAVING
            if (!empty($this->having)) {
                $this->query .= ' HAVING ' . implode(' AND ', $this->having);
            }
            
            // Add ORDER BY
            if (!empty($this->orderBy)) {
                $this->query .= ' ORDER BY ' . implode(', ', $this->orderBy);
            }
        }
    }
}
?>