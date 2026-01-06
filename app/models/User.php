<?php
/**
 * Modelo de Usuario
 */

class User extends Model
{
    protected $table = 'users';

    /**
     * Buscar por email
     */
    public function findByEmail($email)
    {
        return $this->findBy('email', $email);
    }

    /**
     * Verificar credenciales
     */
    public function authenticate($email, $password)
    {
        $user = $this->findByEmail($email);
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    /**
     * Crear usuario con contraseña hasheada
     */
    public function createUser($data)
    {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->create($data);
    }

    /**
     * Actualizar contraseña
     */
    public function updatePassword($userId, $newPassword)
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        return $this->update($userId, ['password' => $hashedPassword]);
    }

    /**
     * Obtener clientes
     */
    public function getClients($status = null)
    {
        $sql = "SELECT * FROM {$this->table} WHERE role = 'cliente'";
        $params = [];
        
        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY created_at DESC";
        return $this->query($sql, $params);
    }

    /**
     * Actualizar saldo
     */
    public function updateBalance($userId, $amount, $operation = 'subtract')
    {
        if ($operation === 'add') {
            $sql = "UPDATE {$this->table} SET balance = balance + ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$amount, $userId]);
        } else {
            $sql = "UPDATE {$this->table} SET balance = balance - ? WHERE id = ? AND balance >= ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$amount, $userId, $amount]);
            return $stmt->rowCount() > 0;
        }
    }

    /**
     * Obtener saldo
     */
    public function getBalance($userId)
    {
        $user = $this->findById($userId);
        return $user ? floatval($user['balance']) : 0;
    }

    /**
     * Contar clientes
     */
    public function countClients($status = null)
    {
        $where = "role = 'cliente'";
        $params = [];
        
        if ($status) {
            $where .= " AND status = ?";
            $params[] = $status;
        }
        
        return $this->count($where, $params);
    }

    /**
     * Obtener últimos usuarios registrados
     */
    public function getRecentUsers($limit = 5)
    {
        $sql = "SELECT * FROM {$this->table} WHERE role = 'cliente' ORDER BY created_at DESC LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
