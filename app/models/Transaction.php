<?php
/**
 * Modelo de Transacción
 */

class Transaction extends Model
{
    protected $table = 'transactions';

    /**
     * Registrar transacción de búsqueda
     */
    public function logSearchTransaction($userId, $amount, $searchQuery, $resultsCount, $balanceBefore, $balanceAfter)
    {
        return $this->create([
            'user_id' => $userId,
            'type' => 'search',
            'amount' => $amount,
            'description' => "Búsqueda de número: {$searchQuery}",
            'search_query' => $searchQuery,
            'results_count' => $resultsCount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Registrar recarga de saldo
     */
    public function logRecharge($userId, $amount, $description, $balanceBefore, $balanceAfter)
    {
        return $this->create([
            'user_id' => $userId,
            'type' => 'recharge',
            'amount' => $amount,
            'description' => $description,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Obtener transacciones del usuario
     */
    public function getUserTransactions($userId, $limit = null)
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC";
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        return $this->query($sql, [$userId]);
    }

    /**
     * Obtener todas las transacciones con información del usuario
     */
    public function getAllWithUser($limit = null)
    {
        $sql = "SELECT t.*, u.name as user_name, u.email as user_email 
                FROM {$this->table} t 
                JOIN users u ON t.user_id = u.id 
                ORDER BY t.created_at DESC";
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        return $this->query($sql);
    }

    /**
     * Obtener resumen de transacciones
     */
    public function getSummary($userId = null)
    {
        $sql = "SELECT 
                    type,
                    COUNT(*) as count,
                    COALESCE(SUM(amount), 0) as total
                FROM {$this->table}";
        $params = [];
        
        if ($userId) {
            $sql .= " WHERE user_id = ?";
            $params[] = $userId;
        }
        
        $sql .= " GROUP BY type";
        return $this->query($sql, $params);
    }
}
