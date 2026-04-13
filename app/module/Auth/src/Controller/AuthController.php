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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Si ya está logueado, redirigir al dashboard
        if (!empty($_SESSION['usuario_id'])) {
            return $this->redirect()->toRoute('dashboard', ['action' => 'index']);
        }

        $request = $this->getRequest();
        $error   = null;

        // Generar CAPTCHA si no existe
        if (empty($_SESSION['captcha_num1']) || empty($_SESSION['captcha_num2'])) {
            $_SESSION['captcha_num1'] = rand(1, 9);
            $_SESSION['captcha_num2'] = rand(1, 9);
        }

        if ($request->isPost()) {
            $data         = $request->getPost()->toArray();
            $username     = trim($data['username'] ?? '');
            $password     = $data['password'] ?? '';
            $captchaInput = (int)($data['captcha'] ?? -1);
            $captchaAnswer = (int)$_SESSION['captcha_num1'] + (int)$_SESSION['captcha_num2'];

            if (empty($username) || empty($password)) {
                $error = 'Usuario y contraseña son requeridos.';
            } elseif ($captchaInput !== $captchaAnswer) {
                $error = 'La respuesta del CAPTCHA es incorrecta.';
                $_SESSION['captcha_num1'] = rand(1, 9);
                $_SESSION['captcha_num2'] = rand(1, 9);
            } else {
                $usuario = $this->getUsuarioByUsername($username);

                if (!$usuario) {
                    $error = 'El usuario no existe o la contraseña es incorrecta.';
                } elseif ((int)$usuario['id_estado_usuario'] !== 1) {
                    $error = 'El usuario está inactivo. Contacte al administrador.';
                } elseif (!password_verify($password, $usuario['str_pwd'])) {
                    $error = 'El usuario no existe o la contraseña es incorrecta.';
                } else {
                    // Login exitoso
                    $perfil          = $this->getPerfilById((int)$usuario['id_perfil']);
                    $bitAdministrador = (bool)($perfil['bit_administrador'] ?? false);
                    $permisos        = $this->getPermisosByPerfil((int)$usuario['id_perfil']);

                    $_SESSION['usuario_id']        = $usuario['id'];
                    $_SESSION['usuario_nombre']    = $usuario['str_nombre_usuario'];
                    $_SESSION['usuario_perfil_id'] = $usuario['id_perfil'];
                    $_SESSION['usuario_imagen']    = $usuario['imagen'] ?? null;
                    $_SESSION['bit_administrador'] = $bitAdministrador;
                    $_SESSION['permisos']          = $permisos;
                    $_SESSION['perfil_nombre']     = $perfil['str_nombre_perfil'] ?? 'Usuario';

                    unset($_SESSION['captcha_num1'], $_SESSION['captcha_num2']);
                    $this->updateLastLogin((int)$usuario['id']);

                    return $this->redirect()->toRoute('dashboard', ['action' => 'index']);
                }
            }
        }

        return new ViewModel([
            'error'      => $error,
            'captcha_n1' => $_SESSION['captcha_num1'] ?? 1,
            'captcha_n2' => $_SESSION['captcha_num2'] ?? 1,
        ]);
    }

    public function logoutAction()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_destroy();
        return $this->redirect()->toRoute('auth', ['action' => 'login']);
    }

    public function indexAction()
    {
        return $this->redirect()->toRoute('auth', ['action' => 'login']);
    }

    private function getUsuarioByUsername(string $username): ?array
    {
        $sql    = new Sql($this->db);
        $select = $sql->select('usuario')->where(['str_nombre_usuario' => $username]);
        $row    = $sql->prepareStatementForSqlObject($select)->execute()->current();
        return $row ? (array)$row : null;
    }

    private function getPerfilById(int $id): ?array
    {
        $sql    = new Sql($this->db);
        $select = $sql->select('perfil')->where(['id' => $id]);
        $row    = $sql->prepareStatementForSqlObject($select)->execute()->current();
        return $row ? (array)$row : null;
    }

    private function getPermisosByPerfil(int $idPerfil): array
    {
        $sql    = new Sql($this->db);
        $select = $sql->select('permisos_perfil')->where(['id_perfil' => $idPerfil]);
        $result = $sql->prepareStatementForSqlObject($select)->execute();

        $permisos = [];
        foreach ($result as $row) {
            $permisos[(int)$row['id_modulo']] = [
                'agregar'  => (bool)$row['bit_agregar'],
                'editar'   => (bool)$row['bit_editar'],
                'consulta' => (bool)$row['bit_consulta'],
                'eliminar' => (bool)$row['bit_eliminar'],
                'detalle'  => (bool)$row['bit_detalle'],
            ];
        }
        return $permisos;
    }

    private function updateLastLogin(int $usuarioId): void
    {
        $sql    = new Sql($this->db);
        $update = $sql->update('usuario')
            ->set(['ultimo_login' => new \Laminas\Db\Sql\Expression('CURRENT_TIMESTAMP')])
            ->where(['id' => $usuarioId]);
        $sql->prepareStatementForSqlObject($update)->execute();
    }
}
