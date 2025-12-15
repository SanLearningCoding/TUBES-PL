<?php

/**
 * Model/QueryBuilder.php
 * 
 * Query Builder untuk memudahkan pembuatan query SQL dengan cara yang aman
 * Menggunakan Prepared Statements untuk mencegah SQL injection
 * 
 * CATATAN PENTING:
 * - Semua values akan di-escape otomatis melalui prepared statements
 * - Method bersifat fluent (chainable), bisa di-chain berulang kali
 * - Untuk query yang kompleks, bisa gunakan whereRaw() dengan hati-hati
 * 
 * CONTOH PENGGUNAAN:
 * $builder = new QueryBuilder($pdo, 'stok_darah');
 * $result = $builder->select('*')
 *                   ->where('status', 'tersedia')
 *                   ->where('is_deleted', 0)
 *                   ->orderBy('tanggal_kadaluarsa', 'ASC')
 *                   ->getResultArray();
 */
class QueryBuilder {
    private $pdo;           // PDO connection object
    private $table;         // Table name (bisa include alias: 'stok_darah sd')
    private $query = '';    // Constructed query string
    private $params = [];   // Parameters untuk prepared statement
    private $whereConditions = [];  // Collected WHERE conditions
    private $joins = [];            // Collected JOIN clauses
    private $orderBy = [];          // Collected ORDER BY clauses
    private $groupBy = [];          // Collected GROUP BY clauses
    private $having = [];           // Collected HAVING conditions

    /**
     * __construct($pdo, $table)
     * Inisialisasi QueryBuilder dengan PDO connection dan nama table
     * 
     * @param PDO $pdo Database connection
     * @param string $table Nama table atau "table_name alias" (e.g., 'petugas p')
     */
    public function __construct($pdo, $table) {
        $this->pdo = $pdo;
        $this->table = $table;
    }

    /**
     * select($columns)
     * Memulai SELECT query dengan kolom yang ditentukan
     * 
     * @param string $columns Kolom yang diselect (e.g., 'id, nama' atau '*')
     * @return $this Chainable
     */
    public function select($columns = '*') {
        $this->query = "SELECT $columns FROM $this->table";
        return $this;
    }

    /**
     * get($columns)
     * Alias untuk select() - untuk compatibility dengan kode lama
     * 
     * @param string $columns Kolom yang diselect
     * @return $this Chainable
     */
    public function get($columns = '*') {
        return $this->select($columns);
    }

    /**
     * join($table, $condition, $type)
     * Menambahkan JOIN clause ke query
     * 
     * @param string $table Table yang akan di-join (bisa include alias)
     * @param string $condition Kondisi join (e.g., 'gd.id = sd.id_gol_darah')
     * @param string $type Tipe join: 'INNER', 'LEFT', 'RIGHT', 'FULL' (default: 'INNER')
     * @return $this Chainable
     */
    public function join($table, $condition, $type = 'INNER') {
        $this->joins[] = "$type JOIN $table ON $condition";
        return $this;
    }

    /**
     * where($column, $value, $operator)
     * Menambahkan WHERE condition dengan automatic parameter binding
     * Kondisi multiple diperlakukan dengan AND
     * 
     * @param string $column Nama kolom
     * @param mixed $value Nilai yang dicari
     * @param string $operator Operator: '=', '!=', '<', '>', '<=', '>=', 'LIKE', 'IN', dll (default: '=')
     * @return $this Chainable
     */
    public function where($column, $value, $operator = '=') {
        $this->whereConditions[] = "$column $operator ?";
        $this->params[] = $value;
        return $this;
    }

    /**
     * orWhere($column, $value, $operator)
     * Menambahkan WHERE condition dengan OR logic
     * 
     * @param string $column Nama kolom
     * @param mixed $value Nilai yang dicari
     * @param string $operator Operator (default: '=')
     * @return $this Chainable
     */
    public function orWhere($column, $value, $operator = '=') {
        if (empty($this->whereConditions)) {
            return $this->where($column, $value, $operator);
        }
        $this->whereConditions[] = "OR $column $operator ?";
        $this->params[] = $value;
        return $this;
    }

    /**
     * whereRaw($condition)
     * Menambahkan raw WHERE condition tanpa parameter binding
     * HATI-HATI: Gunakan hanya untuk kondisi yang sudah divalidasi
     * 
     * @param string $condition Raw SQL condition
     * @return $this Chainable
     */
    public function whereRaw($condition) {
        $this->whereConditions[] = $condition;
        return $this;
    }

    /**
     * orWhereRaw($condition)
     * Menambahkan raw WHERE condition dengan OR logic
     * 
     * @param string $condition Raw SQL condition
     * @return $this Chainable
     */
    public function orWhereRaw($condition) {
        if (empty($this->whereConditions)) {
            return $this->whereRaw($condition);
        }
        $this->whereConditions[] = "OR $condition";
        return $this;
    }

    /**
     * groupBy($column)
     * Menambahkan GROUP BY clause
     * 
     * @param string $column Kolom untuk group
     * @return $this Chainable
     */
    public function groupBy($column) {
        $this->groupBy[] = $column;
        return $this;
    }

    /**
     * having($condition)
     * Menambahkan HAVING condition (filter hasil GROUP BY)
     * 
     * @param string $condition HAVING condition
     * @return $this Chainable
     */
    public function having($condition) {
        $this->having[] = $condition;
        return $this;
    }

    /**
     * orderBy($column, $direction)
     * Menambahkan ORDER BY clause
     * 
     * @param string $column Kolom untuk sort
     * @param string $direction 'ASC' atau 'DESC' (default: 'ASC')
     * @return $this Chainable
     */
    public function orderBy($column, $direction = 'ASC') {
        $this->orderBy[] = "$column $direction";
        return $this;
    }

    /**
     * insert($data)
     * Membuat dan execute INSERT query
     * 
     * @param array $data Associative array: ['kolom' => 'value', ...]
     * @return bool true jika berhasil insert, false jika gagal
     */
    public function insert($data) {
        // Bangun kolom dan placeholder dari keys $data
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        // Buat query INSERT
        $this->query = "INSERT INTO $this->table ($columns) VALUES ($placeholders)";
        $this->params = array_values($data);
        
        // Execute immediately
        return $this->execute();
    }

    /**
     * update($data)
     * Membuat dan execute UPDATE query
     * Gunakan where() sebelumnya untuk menentukan record mana yang diupdate
     * 
     * @param array $data Associative array field yang akan diupdate
     * @return bool true jika berhasil update, false jika gagal
     */
    public function update($data) {
        // Bangun SET clause dari keys $data
        $setClause = implode(', ', array_map(function($col) {
            return "$col = ?";
        }, array_keys($data)));

        // Simpan params WHERE condition yang sudah dikumpulkan
        $whereParams = $this->params;

        // Reset params dengan params dari data yang diupdate
        $this->query = "UPDATE $this->table SET $setClause";
        $this->params = array_values($data);

        // Tambahkan WHERE conditions jika ada
        if (!empty($this->whereConditions)) {
            $this->query .= " WHERE " . implode(' AND ', $this->whereConditions);
            // Gabung params: update params + where params
            $this->params = array_merge($this->params, $whereParams);
        }

        // Execute immediately
        return $this->execute();
    }

    /**
     * getResultArray()
     * Execute SELECT query dan return array of rows
     * Gunakan ini jika expect multiple results
     * 
     * @return array Array berisi row-row hasil query, atau array kosong jika error
     */
    public function getResultArray() {
        // Default ke SELECT * jika query belum diset
        if (empty(trim($this->query))) {
            $this->query = "SELECT * FROM $this->table";
        }
        
        // Build lengkap query (tambah joins, where, groupby, having, orderby)
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

    /**
     * getRowArray()
     * Execute SELECT query dan return single row (atau null jika tidak ada)
     * Gunakan ini jika expect single result
     * 
     * @return array|null Satu row hasil query atau null
     */
    public function getRowArray() {
        // Default ke SELECT * jika query belum diset
        if (empty(trim($this->query))) {
            $this->query = "SELECT * FROM $this->table";
        }
        
        // Build lengkap query
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

    /**
     * execute()
     * Execute query (INSERT/UPDATE/DELETE) dan return bool
     * 
     * @return bool true jika berhasil, false jika gagal
     */
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

    /**
     * buildQuery()
     * Private helper: melengkapi query dengan joins, where, groupby, having, orderby
     * Dipanggil otomatis sebelum execute SELECT
     */
    private function buildQuery() {
        // Hanya untuk SELECT query
        if (strpos($this->query, 'SELECT') === 0) {
            // Tambahkan JOINs jika ada
            if (!empty($this->joins)) {
                $this->query .= ' ' . implode(' ', $this->joins);
            }
            
            // Tambahkan WHERE conditions jika ada
            if (!empty($this->whereConditions)) {
                $this->query .= ' WHERE ' . implode(' AND ', $this->whereConditions);
            }
            
            // Tambahkan GROUP BY jika ada
            if (!empty($this->groupBy)) {
                $this->query .= ' GROUP BY ' . implode(', ', $this->groupBy);
            }
            
            // Tambahkan HAVING jika ada
            if (!empty($this->having)) {
                $this->query .= ' HAVING ' . implode(' AND ', $this->having);
            }
            
            // Tambahkan ORDER BY jika ada
            if (!empty($this->orderBy)) {
                $this->query .= ' ORDER BY ' . implode(', ', $this->orderBy);
            }
        }
    }
}
