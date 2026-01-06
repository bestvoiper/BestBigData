<?php
/**
 * Controlador de Cliente
 * Maneja todas las funciones del panel de cliente
 */

class ClientController extends Controller
{
    private $userModel;
    private $searchModel;
    private $transactionModel;
    private $settingModel;
    private $user;
    private $settings;

    public function __construct()
    {
        Session::start();
        
        // Verificar que sea cliente
        if (!Session::isClient()) {
            $this->redirect('auth/login');
            exit;
        }

        $this->userModel = $this->model('User');
        $this->searchModel = $this->model('Search');
        $this->transactionModel = $this->model('Transaction');
        $this->settingModel = $this->model('Setting');
        
        $this->user = $this->userModel->findById(Session::getUserId());
        $this->settings = $this->settingModel->getAll();
    }

    /**
     * Dashboard del cliente
     */
    public function index()
    {
        $stats = $this->searchModel->getUserStats($this->user['id']);
        
        $data = [
            'pageTitle' => 'Dashboard - Cliente',
            'user' => $this->user,
            'settings' => $this->settings,
            'totalSearches' => $stats['total_searches'],
            'totalResults' => $stats['total_results'],
            'totalSpent' => $stats['total_spent'],
            'recentSearches' => $this->searchModel->getUserHistory($this->user['id'], 10),
            'flash' => $this->getFlash()
        ];

        $this->view('client/dashboard', $data);
    }

    /**
     * Búsqueda de números
     */
    public function search()
    {
        $costPerResult = $this->settingModel->getCostPerResult();
        $maxResults = $this->settingModel->getMaxResults();
        
        $data = [
            'pageTitle' => 'Buscar Número - Cliente',
            'user' => $this->user,
            'settings' => $this->settings,
            'costPerResult' => $costPerResult,
            'results' => [],
            'searchPhone' => '',
            'error' => '',
            'searchPerformed' => false,
            'totalCost' => 0,
            'flash' => $this->getFlash()
        ];

        // Procesar búsqueda
        if (isset($_GET['phone']) && !empty($_GET['phone'])) {
            $data['searchPhone'] = $this->sanitize($_GET['phone']);
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            $data['searchPerformed'] = true;

            // Refrescar datos del usuario
            $this->user = $this->userModel->findById($this->user['id']);
            $data['user'] = $this->user;

            // Verificar saldo mínimo
            if ($this->user['balance'] < $costPerResult) {
                $data['error'] = 'Saldo insuficiente para realizar la búsqueda. Tu saldo actual es $' . number_format($this->user['balance'], 2);
            } else {
                // Realizar búsqueda
                $results = $this->searchModel->searchPhoneNumber($data['searchPhone'], $startDate, $endDate);
                
                // Limitar resultados
                $results = array_slice($results, 0, $maxResults);
                
                $resultsCount = count($results);
                $totalCost = $resultsCount * $costPerResult;

                // Verificar si hay suficiente saldo
                if ($totalCost > $this->user['balance']) {
                    $affordableResults = floor($this->user['balance'] / $costPerResult);
                    $results = array_slice($results, 0, $affordableResults);
                    $resultsCount = count($results);
                    $totalCost = $resultsCount * $costPerResult;
                    $data['error'] = "Solo se muestran {$resultsCount} resultados debido a saldo insuficiente.";
                }

                if ($resultsCount > 0) {
                    $balanceBefore = $this->user['balance'];
                    
                    if ($this->userModel->updateBalance($this->user['id'], $totalCost, 'subtract')) {
                        $balanceAfter = $balanceBefore - $totalCost;
                        
                        // Registrar transacción
                        $this->transactionModel->logSearchTransaction(
                            $this->user['id'],
                            $totalCost,
                            $data['searchPhone'],
                            $resultsCount,
                            $balanceBefore,
                            $balanceAfter
                        );
                        
                        // Registrar búsqueda
                        $this->searchModel->logSearch($this->user['id'], $data['searchPhone'], $resultsCount, $totalCost);
                        
                        // Actualizar usuario en memoria
                        $this->user['balance'] = $balanceAfter;
                        $data['user'] = $this->user;
                        $data['results'] = $results;
                        $data['totalCost'] = $totalCost;
                    } else {
                        $data['error'] = 'Error al procesar el cobro. Intente nuevamente.';
                    }
                }
            }
        }

        $this->view('client/search', $data);
    }

    /**
     * Historial de búsquedas
     */
    public function history()
    {
        $data = [
            'pageTitle' => 'Historial - Cliente',
            'user' => $this->user,
            'settings' => $this->settings,
            'searches' => $this->searchModel->getUserHistory($this->user['id']),
            'flash' => $this->getFlash()
        ];

        $this->view('client/history', $data);
    }

    /**
     * Búsqueda masiva por archivo CSV/TXT
     */
    public function bulkSearch()
    {
        $costPerResult = $this->settingModel->getCostPerResult();
        $maxResults = $this->settingModel->getMaxResults();
        
        $data = [
            'pageTitle' => 'Búsqueda Masiva - Cliente',
            'user' => $this->user,
            'settings' => $this->settings,
            'costPerResult' => $costPerResult,
            'results' => [],
            'summary' => [],
            'error' => '',
            'success' => '',
            'searchPerformed' => false,
            'totalCost' => 0,
            'numbersSearched' => 0,
            'flash' => $this->getFlash()
        ];

        // Procesar archivo subido
        if ($this->isPost() && isset($_FILES['phone_file'])) {
            $file = $_FILES['phone_file'];
            $startDate = $_POST['start_date'] ?? null;
            $endDate = $_POST['end_date'] ?? null;
            
            // Validar archivo
            $allowedTypes = ['text/csv', 'text/plain', 'application/vnd.ms-excel'];
            $allowedExtensions = ['csv', 'txt'];
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $data['error'] = 'Error al subir el archivo. Código: ' . $file['error'];
            } elseif (!in_array($extension, $allowedExtensions)) {
                $data['error'] = 'Tipo de archivo no permitido. Solo se aceptan archivos CSV y TXT.';
            } elseif ($file['size'] > 100 * 1024 * 1024) {
                $data['error'] = 'El archivo es demasiado grande. Máximo 100MB.';
            } else {
                // Procesar archivo
                $fileType = $extension;
                $phoneNumbers = $this->searchModel->parsePhoneFile($file['tmp_name'], $fileType);
                
                if (empty($phoneNumbers)) {
                    $data['error'] = 'No se encontraron números de teléfono válidos en el archivo.';
                } else {
                    // Aumentar tiempo de ejecución para archivos grandes
                    set_time_limit(0);
                    ini_set('memory_limit', '512M');
                    
                    $data['searchPerformed'] = true;
                    $data['numbersSearched'] = count($phoneNumbers);
                    
                    // Refrescar datos del usuario
                    $this->user = $this->userModel->findById($this->user['id']);
                    $data['user'] = $this->user;
                    
                    // Estimar costo mínimo
                    $minCost = $costPerResult;
                    
                    if ($this->user['balance'] < $minCost) {
                        $data['error'] = 'Saldo insuficiente para realizar la búsqueda. Tu saldo actual es $' . number_format($this->user['balance'], 2);
                    } else {
                        // Realizar búsqueda masiva
                        $searchResults = $this->searchModel->searchMultiplePhoneNumbers($phoneNumbers, $startDate, $endDate);
                        
                        $allResults = $searchResults['all_results'];
                        $summary = $searchResults['summary'];
                        
                        // Limitar resultados totales
                        $allResults = array_slice($allResults, 0, $maxResults);
                        
                        $resultsCount = count($allResults);
                        $totalCost = $resultsCount * $costPerResult;
                        
                        // Verificar si hay suficiente saldo
                        if ($totalCost > $this->user['balance']) {
                            $affordableResults = floor($this->user['balance'] / $costPerResult);
                            $allResults = array_slice($allResults, 0, $affordableResults);
                            $resultsCount = count($allResults);
                            $totalCost = $resultsCount * $costPerResult;
                            $data['error'] = "Solo se muestran {$resultsCount} resultados debido a saldo insuficiente.";
                        }
                        
                        if ($resultsCount > 0) {
                            $balanceBefore = $this->user['balance'];
                            
                            if ($this->userModel->updateBalance($this->user['id'], $totalCost, 'subtract')) {
                                $balanceAfter = $balanceBefore - $totalCost;
                                
                                // Registrar transacción
                                $this->transactionModel->logSearchTransaction(
                                    $this->user['id'],
                                    $totalCost,
                                    "BULK ({$data['numbersSearched']} números)",
                                    $resultsCount,
                                    $balanceBefore,
                                    $balanceAfter
                                );
                                
                                // Registrar búsqueda
                                $this->searchModel->logBulkSearch(
                                    $this->user['id'],
                                    $data['numbersSearched'],
                                    $resultsCount,
                                    $totalCost
                                );
                                
                                // Actualizar usuario en memoria
                                $this->user['balance'] = $balanceAfter;
                                $data['user'] = $this->user;
                                $data['results'] = $allResults;
                                $data['summary'] = $summary;
                                $data['totalCost'] = $totalCost;
                            } else {
                                $data['error'] = 'Error al procesar el cobro. Intente nuevamente.';
                            }
                        } else {
                            $data['summary'] = $summary;
                        }
                    }
                }
            }
        }

        $this->view('client/bulk-search', $data);
    }

    /**
     * Perfil del usuario
     */
    public function profile()
    {
        if ($this->isPost()) {
            $name = $this->getPost('name');
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';

            $updateData = ['name' => $name];
            $error = '';

            // Si quiere cambiar contraseña
            if (!empty($newPassword)) {
                if (empty($currentPassword)) {
                    $error = 'Debe ingresar la contraseña actual para cambiarla.';
                } elseif (!password_verify($currentPassword, $this->user['password'])) {
                    $error = 'La contraseña actual es incorrecta.';
                } elseif (strlen($newPassword) < 6) {
                    $error = 'La nueva contraseña debe tener al menos 6 caracteres.';
                } else {
                    $updateData['password'] = password_hash($newPassword, PASSWORD_BCRYPT);
                }
            }

            if (empty($error)) {
                if ($this->userModel->update($this->user['id'], $updateData)) {
                    $this->setFlash('success', 'Perfil actualizado exitosamente.');
                    $this->user = $this->userModel->findById($this->user['id']);
                } else {
                    $this->setFlash('danger', 'Error al actualizar el perfil.');
                }
            } else {
                $this->setFlash('danger', $error);
            }
        }

        $transactions = $this->transactionModel->getUserTransactions($this->user['id'], 20);
        
        $data = [
            'pageTitle' => 'Mi Perfil - Cliente',
            'user' => $this->user,
            'settings' => $this->settings,
            'transactions' => $transactions,
            'flash' => $this->getFlash()
        ];

        $this->view('client/profile', $data);
    }
}
