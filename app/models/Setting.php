<?php
/**
 * Modelo de Configuración
 */

class Setting extends Model
{
    protected $table = 'settings';
    protected $primaryKey = 'setting_key';

    /**
     * Obtener valor de configuración
     */
    public function get($key, $default = null)
    {
        $result = $this->findBy('setting_key', $key);
        return $result ? $result['setting_value'] : $default;
    }

    /**
     * Establecer valor de configuración
     */
    public function set($key, $value)
    {
        $existing = $this->findBy('setting_key', $key);
        
        if ($existing) {
            $sql = "UPDATE {$this->table} SET setting_value = ? WHERE setting_key = ?";
            return $this->execute($sql, [$value, $key]);
        } else {
            return $this->create([
                'setting_key' => $key,
                'setting_value' => $value
            ]);
        }
    }

    /**
     * Obtener todas las configuraciones como array asociativo
     */
    public function getAll()
    {
        $settings = [];
        $results = $this->findAll();
        
        foreach ($results as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
    }

    /**
     * Obtener costo por resultado
     */
    public function getCostPerResult()
    {
        return floatval($this->get('cost_per_result', 1));
    }

    /**
     * Obtener máximo de resultados por búsqueda
     */
    public function getMaxResults()
    {
        return intval($this->get('max_results_per_search', 1000));
    }

    /**
     * Obtener umbral de alerta de saldo bajo
     */
    public function getMinBalanceAlert()
    {
        return floatval($this->get('min_balance_alert', 10));
    }
}
