<?php

declare(strict_types=1);

namespace Auth\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Sql;

class AuthController extends AbstractActionController
{
    private AdapterInterface $db;

    public function __construct(AdapterInterface $db)
    {
        $this->db = $db;
    }

    public function loginAction()
    {
        // Iniciar sesión si no está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $request = $this->getRequest();
        $error = null;

        if ($request->isPost()) {
            $data = $request->getPost()->toArray();
            $username = $data['username'] ?? '';
            $password = $data['password'] ?? '';

            // Validación básica
            if (empty($username) || empty($password)) {
                $error = 'Usuario y contraseña son requeridos';
            } else {
                // Buscar usuario en base de datos
                $usuario = $this->getUsuarioByUsername($username);

                if (!$usuario) {
                    $error = 'Usuario no encontrado';
                } elseif ($usuario['id_estado_usuario'] != 1) {
                    // Estado no es "Activo"
                    $error = 'Usuario inactivo o suspendido';
                } elseif (!password_verify($password, $usuario['str_pwd'])) {
                    $error = 'Contraseña incorrecta';
                } else {
                    // Autenticación exitosa
                    // Aquí se puede implementar JWT o sesiones
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['usuario_nombre'] = $usuario['str_nombre_usuario'];
                    $_SESSION['usuario_perfil'] = $usuario['id_perfil'];

                    // Actualizar último login
                    $this->updateLastLogin($usuario['id']);

                    return $this->redirect()->toRoute('security', ['action' => 'perfil']);
                }
            }
        }

        return new ViewModel([
            'error' => $error,
        ]);
    }

    public function logoutAction()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        return $this->redirect()->toRoute('auth', ['action' => 'login']);
    }

    public function indexAction()
    {
        // Redirigir al login
        return $this->redirect()->toRoute('auth', ['action' => 'login']);
    }

    private function getUsuarioByUsername(string $username): ?array
    {
        $sql = new Sql($this->db);

        $select = $sql->select('usuario')
            ->where(['str_nombre_usuario' => $username]);

        $stmt = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();
        $row = $result->current();

        return $row ? (array)$row : null;
    }

    private function updateLastLogin(int $usuarioId): void
    {
        $sql = new Sql($this->db);

        $update = $sql->update('usuario')
            ->set(['ultimo_login' => new \Laminas\Db\Sql\Expression('CURRENT_TIMESTAMP')])
            ->where(['id' => $usuarioId]);

        $stmt = $sql->prepareStatementForSqlObject($update);
        $stmt->execute();
    }
}
