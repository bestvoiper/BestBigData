<?php
/**
 * Controlador de Autenticación
 * Maneja login, logout y registro
 */

class AuthController extends Controller
{
    private $userModel;

    public function __construct()
    {
        Session::start();
        $this->userModel = $this->model('User');
    }

    /**
     * Mostrar página de login
     */
    public function login()
    {
        // Si ya está logueado, redirigir
        if (Session::isLoggedIn()) {
            $this->redirectByRole();
            return;
        }

        $data = [
            'pageTitle' => 'Iniciar Sesión',
            'error' => '',
            'email' => ''
        ];

        if ($this->isPost()) {
            $email = $this->getPost('email');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $data['error'] = 'Por favor complete todos los campos.';
                $data['email'] = $email;
            } else {
                $user = $this->userModel->authenticate($email, $password);

                if ($user) {
                    if ($user['status'] !== 'active') {
                        $data['error'] = 'Su cuenta está inactiva. Contacte al administrador.';
                        $data['email'] = $email;
                    } else {
                        // Login exitoso
                        Session::login($user['id'], $user['role'], $user['name']);
                        
                        // Actualizar último login
                        $this->userModel->update($user['id'], [
                            'last_login' => date('Y-m-d H:i:s')
                        ]);

                        $this->redirectByRole();
                        return;
                    }
                } else {
                    $data['error'] = 'Credenciales incorrectas.';
                    $data['email'] = $email;
                }
            }
        }

        $this->view('auth/login', $data);
    }

    /**
     * Cerrar sesión
     */
    public function logout()
    {
        Session::logout();
        $this->redirect('auth/login');
    }

    /**
     * Redirigir según el rol
     */
    private function redirectByRole()
    {
        if (Session::isAdmin()) {
            $this->redirect('admin');
        } else {
            $this->redirect('client');
        }
    }
}
