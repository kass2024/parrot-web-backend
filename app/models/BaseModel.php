<?php

require_once __DIR__ . '/../config/database.php';

class BaseModel {
    protected $conn;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getAll($conditions = [], $order = '', $limit = '', $offset = 0) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $where_clauses = [];
            foreach ($conditions as $field => $value) {
                $where_clauses[] = "{$field} = :{$field}";
                $params[":{$field}"] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }

        if (!empty($order)) {
            $sql .= " ORDER BY {$order}";
        }

        if (!empty($limit)) {
            $sql .= " LIMIT {$limit}";
            if ($offset > 0) {
                $sql .= " OFFSET {$offset}";
            }
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function findBy($field, $value) {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = :value";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':value' => $value]);
        return $stmt->fetchAll();
    }

    public function findOneBy($field, $value) {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = :value LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':value' => $value]);
        return $stmt->fetch();
    }

    public function create($data) {
        $filtered_data = $this->filterData($data);
        
        if (empty($filtered_data)) {
            return false;
        }

        $fields = array_keys($filtered_data);
        $placeholders = array_map(function($field) {
            return ":{$field}";
        }, $fields);

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->conn->prepare($sql);
        $params = array_combine($placeholders, array_values($filtered_data));
        
        if ($stmt->execute($params)) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }

    public function update($id, $data) {
        $filtered_data = $this->filterData($data);
        
        if (empty($filtered_data)) {
            return false;
        }

        $set_clauses = [];
        $params = [':id' => $id];

        foreach ($filtered_data as $field => $value) {
            $set_clauses[] = "{$field} = :{$field}";
            $params[":{$field}"] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $set_clauses) . " WHERE {$this->primaryKey} = :id";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $where_clauses = [];
            foreach ($conditions as $field => $value) {
                $where_clauses[] = "{$field} = :{$field}";
                $params[":{$field}"] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return (int) $result['count'];
    }

    public function paginate($page = 1, $limit = ITEMS_PER_PAGE, $conditions = [], $order = '') {
        $offset = ($page - 1) * $limit;
        $total = $this->count($conditions);
        
        $data = $this->getAll($conditions, $order, $limit, $offset);
        
        return [
            'data' => $data,
            'pagination' => paginate($total, $page, $limit)
        ];
    }

    public function query($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function queryOne($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function execute($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    public function commit() {
        return $this->conn->commit();
    }

    public function rollback() {
        return $this->conn->rollback();
    }

    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }

    protected function filterData($data) {
        return array_intersect_key($data, array_flip($this->fillable));
    }

    public function hideFields($data) {
        if (is_array($data)) {
            foreach ($this->hidden as $field) {
                unset($data[$field]);
            }
        }
        return $data;
    }

    public function validate($data, $rules = []) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $rules_array = explode('|', $rule);
            
            foreach ($rules_array as $r) {
                if ($r === 'required' && (!isset($data[$field]) || empty($data[$field]))) {
                    $errors[$field][] = "The {$field} field is required.";
                }
                
                if (isset($data[$field]) && strpos($r, 'min:') === 0) {
                    $min_length = (int) substr($r, 4);
                    if (strlen($data[$field]) < $min_length) {
                        $errors[$field][] = "The {$field} must be at least {$min_length} characters.";
                    }
                }
                
                if (isset($data[$field]) && strpos($r, 'max:') === 0) {
                    $max_length = (int) substr($r, 4);
                    if (strlen($data[$field]) > $max_length) {
                        $errors[$field][] = "The {$field} must not exceed {$max_length} characters.";
                    }
                }
                
                if (isset($data[$field]) && $r === 'email' && !validateEmail($data[$field])) {
                    $errors[$field][] = "The {$field} must be a valid email address.";
                }
            }
        }
        
        return $errors;
    }
}
?>
