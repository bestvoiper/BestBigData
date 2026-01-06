<?php
/**
 * Controlador de Administración
 * Maneja todas las funciones del panel de administración
 */

class AdminController extends Controller
{
    private $userModel;
    private $searchModel;
    private $transactionModel;
    private $settingModel;
    private $user;

    public function __construct()
    {
        Session::start();
        
        // Verificar que sea administrador
        if (!Session::isAdmin()) {
            $this->redirect('auth/login');
            exit;
        }

        $this->userModel = $this->model('User');
        $this->searchModel = $this->model('Search');
        $this->transactionModel = $this->model('Transaction');
        $this->settingModel = $this->model('Setting');
        
        $this->user = $this->userModel->findById(Session::getUserId());
    }

    /**
     * Dashboard de administración
     */
    public function index()
    {
        $data = [
            'pageTitle' => 'Dashboard - Admin',
            'user' => $this->user,
            'totalClients' => $this->userModel->countClients(),
            'activeClients' => $this->userModel->countClients('active'),
            'searchesToday' => $this->searchModel->countToday(),
            'revenueToday' => $this->searchModel->getRevenueToday(),
            'recentSearches' => $this->searchModel->getRecentSearches(10),
            'recentUsers' => $this->userModel->getRecentUsers(5),
            'flash' => $this->getFlash()
        ];

        $this->view('admin/dashboard', $data);
    }

    /**
     * Gestión de usuarios
     */
    public function users()
    {
        $data = [
            'pageTitle' => 'Usuarios - Admin',
            'user' => $this->user,
            'clients' => $this->userModel->getClients(),
            'flash' => $this->getFlash()
        ];

        $this->view('admin/users', $data);
    }

    /**
     * Crear usuario
     */
    public function createUser()
    {
        if (!$this->isPost()) {
            $this->redirect('admin/users');
            return;
        }

        $name = $this->getPost('name');
        $email = $this->getPost('email');
        $password = $_POST['password'] ?? '';
        $balance = floatval($_POST['balance'] ?? 0);

        // Validar
        if (empty($name) || empty($email) || empty($password)) {
            $this->setFlash('danger', 'Todos los campos son requeridos.');
            $this->redirect('admin/users');
            return;
        }

        // Verificar que el email no exista
        if ($this->userModel->findByEmail($email)) {
            $this->setFlash('danger', 'El email ya está registrado.');
            $this->redirect('admin/users');
            return;
        }

        $userId = $this->userModel->createUser([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => 'cliente',
            'balance' => $balance,
            'status' => 'active'
        ]);

        if ($userId) {
            $this->setFlash('success', 'Usuario creado exitosamente.');
        } else {
            $this->setFlash('danger', 'Error al crear el usuario.');
        }

        $this->redirect('admin/users');
    }

    /**
     * Actualizar usuario
     */
    public function updateUser($id)
    {
        if (!$this->isPost()) {
            $this->redirect('admin/users');
            return;
        }

        $name = $this->getPost('name');
        $email = $this->getPost('email');
        $status = $this->getPost('status');

        $data = [
            'name' => $name,
            'email' => $email,
            'status' => $status
        ];

        // Si se proporciona nueva contraseña
        if (!empty($_POST['password'])) {
            $data['password'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
        }

        if ($this->userModel->update($id, $data)) {
            $this->setFlash('success', 'Usuario actualizado exitosamente.');
        } else {
            $this->setFlash('danger', 'Error al actualizar el usuario.');
        }

        $this->redirect('admin/users');
    }

    /**
     * Recargar saldo
     */
    public function recharge($id)
    {
        if (!$this->isPost()) {
            $this->redirect('admin/users');
            return;
        }

        $amount = floatval($_POST['amount'] ?? 0);
        $description = $this->getPost('description', 'Recarga de saldo');

        if ($amount <= 0) {
            $this->setFlash('danger', 'El monto debe ser mayor a 0.');
            $this->redirect('admin/users');
            return;
        }

        $targetUser = $this->userModel->findById($id);
        if (!$targetUser) {
            $this->setFlash('danger', 'Usuario no encontrado.');
            $this->redirect('admin/users');
            return;
        }

        $balanceBefore = $targetUser['balance'];
        
        if ($this->userModel->updateBalance($id, $amount, 'add')) {
            $balanceAfter = $balanceBefore + $amount;
            
            // Registrar transacción
            $this->transactionModel->logRecharge(
                $id, 
                $amount, 
                $description, 
                $balanceBefore, 
                $balanceAfter
            );
            
            $this->setFlash('success', 'Saldo recargado exitosamente.');
        } else {
            $this->setFlash('danger', 'Error al recargar el saldo.');
        }

        $this->redirect('admin/users');
    }

    /**
     * Historial de transacciones
     */
    public function transactions()
    {
        $data = [
            'pageTitle' => 'Transacciones - Admin',
            'user' => $this->user,
            'transactions' => $this->transactionModel->getAllWithUser(),
            'flash' => $this->getFlash()
        ];

        $this->view('admin/transactions', $data);
    }

    /**
     * Historial de búsquedas
     */
    public function searches()
    {
        $data = [
            'pageTitle' => 'Búsquedas - Admin',
            'user' => $this->user,
            'searches' => $this->searchModel->getRecentSearches(100),
            'flash' => $this->getFlash()
        ];

        $this->view('admin/searches', $data);
    }

    /**
     * Configuración
     */
    public function settings()
    {
        if ($this->isPost()) {
            $this->settingModel->set('cost_per_result', $_POST['cost_per_result'] ?? 1);
            $this->settingModel->set('max_results_per_search', $_POST['max_results_per_search'] ?? 1000);
            $this->settingModel->set('min_balance_alert', $_POST['min_balance_alert'] ?? 10);
            
            $this->setFlash('success', 'Configuración guardada exitosamente.');
            $this->redirect('admin/settings');
            return;
        }

        $data = [
            'pageTitle' => 'Configuración - Admin',
            'user' => $this->user,
            'settings' => $this->settingModel->getAll(),
            'flash' => $this->getFlash()
        ];

        $this->view('admin/settings', $data);
    }
}
