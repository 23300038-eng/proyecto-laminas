<?php

declare(strict_types=1);

namespace Auth\Controller;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Sql;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Security\Model\PermisoPerfilModel;
use Security\Support\AccessHelper;

class AuthController extends AbstractActionController
{
    private AdapterInterface $db;

    /** @var array<string, string> */
    private array $hcaptchaConfig;

    public function __construct(AdapterInterface $db, array $hcaptchaConfig = [])
    {
        $this->db = $db;
        $this->hcaptchaConfig = $hcaptchaConfig;
    }

    public function loginAction()
    {
        AccessHelper::startSession();

        if (!empty($_SESSION['usuario_id'])) {
            return $this->redirect()->toRoute('home');
        }

        $request = $this->getRequest();
        $error = null;

        if ($request->isPost()) {
            $data = $request->getPost()->toArray();
            $email = strtolower(trim((string) ($data['email'] ?? '')));
            $password = (string) ($data['password'] ?? '');
            $captchaToken = trim((string) ($data['h-captcha-response'] ?? ''));

            if ($email === '' || $password === '') {
                $error = 'Correo y contraseña son requeridos.';
            } elseif (!$this->verifyHCaptcha($captchaToken, (string) $request->getServer('REMOTE_ADDR'))) {
                $error = 'No se pudo validar el hCaptcha. Intenta nuevamente.';
            } else {
                $usuario = $this->getUsuarioByEmail($email);

                if (!$usuario) {
                    $error = 'El usuario no existe o la contraseña es incorrecta.';
                } elseif ((int) ($usuario['id_estado_usuario'] ?? 0) !== 1) {
                    $error = 'El usuario está inactivo. Contacte al administrador.';
                } elseif (!password_verify($password, (string) ($usuario['str_pwd'] ?? ''))) {
                    $error = 'El usuario no existe o la contraseña es incorrecta.';
                } else {
                    $perfil = $this->getPerfilById((int) $usuario['id_perfil']);
                    $sessionPermissions = $this->buildSessionPermissions((int) $usuario['id_perfil']);
                    $esAdmin = $this->isAdminProfile((int) $usuario['id_perfil']);

                    $_SESSION['usuario_id'] = (int) $usuario['id'];
                    $_SESSION['usuario_nombre'] = $usuario['str_nombre_usuario'];
                    $_SESSION['usuario_perfil_id'] = (int) $usuario['id_perfil'];
                                        $_SESSION['usuario_correo'] = $usuario['str_correo'] ?? null;
                    $_SESSION['perfil_nombre'] = $perfil['str_nombre_perfil'] ?? 'Usuario';
                    $_SESSION['usuario_rol'] = $perfil['str_nombre_perfil'] ?? 'Usuario';
                    $_SESSION['permisos_modulos'] = $sessionPermissions['modulos'];
                    $_SESSION['permisos_submodulos'] = $sessionPermissions['submodulos'];
                    $_SESSION['permisos'] = $sessionPermissions['modulos'];
                    $_SESSION['es_admin'] = $esAdmin;
                    $_SESSION['bit_administrador'] = $esAdmin;
                    $_SESSION['menu_modulos_cache'] = AccessHelper::buildMenu($this->db);

                    $this->updateLastLogin((int) $usuario['id']);

                    return $this->redirect()->toRoute('home');
                }
            }
        }

        $viewModel = new ViewModel([
            'error' => $error,
            'siteKey' => $this->hcaptchaConfig['site_key'] ?? '',
        ]);
        $viewModel->setTerminal(true);

        return $viewModel;
    }

    public function logoutAction()
    {
        AccessHelper::startSession();
        session_destroy();

        return $this->redirect()->toRoute('auth', ['action' => 'login']);
    }

    public function indexAction()
    {
        return $this->redirect()->toRoute('auth', ['action' => 'login']);
    }

    private function verifyHCaptcha(string $token, string $remoteIp = ''): bool
    {
        $secret = trim((string) ($this->hcaptchaConfig['secret'] ?? ''));
        $siteKey = trim((string) ($this->hcaptchaConfig['site_key'] ?? ''));

        if ($token === '' || $secret === '') {
            return false;
        }

        $payload = [
            'secret' => $secret,
            'response' => $token,
        ];

        if ($siteKey !== '') {
            $payload['sitekey'] = $siteKey;
        }

        if ($remoteIp !== '') {
            $payload['remoteip'] = $remoteIp;
        }

        $context = stream_context_create([
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($payload),
                'timeout' => 15,
            ],
        ]);

        try {
            $response = @file_get_contents('https://api.hcaptcha.com/siteverify', false, $context);
            if ($response === false) {
                return false;
            }
            $decoded = json_decode($response, true);
            return !empty($decoded['success']);
        } catch (\Throwable $exception) {
            return false;
        }
    }

    private function getUsuarioByEmail(string $email): ?array
    {
        $sql = new Sql($this->db);
        $select = $sql->select('usuario')->where(['str_correo' => $email]);
        $row = $sql->prepareStatementForSqlObject($select)->execute()->current();
        return $row ? (array) $row : null;
    }

    private function getPerfilById(int $id): ?array
    {
        $sql = new Sql($this->db);
        $select = $sql->select('perfil')->where(['id' => $id]);
        $row = $sql->prepareStatementForSqlObject($select)->execute()->current();
        return $row ? (array) $row : null;
    }

    private function buildSessionPermissions(int $idPerfil): array
    {
        $model = new PermisoPerfilModel($this->db);
        return $model->getSessionPermissionsForPerfil($idPerfil);
    }

    private function isAdminProfile(int $idPerfil): bool
    {
        $model = new PermisoPerfilModel($this->db);
        return $model->profileIsAdmin($idPerfil);
    }

    private function updateLastLogin(int $usuarioId): void
    {
        $sql = new Sql($this->db);
        $update = $sql->update('usuario')
            ->set(['ultimo_login' => new Expression('CURRENT_TIMESTAMP')])
            ->where(['id' => $usuarioId]);

        $sql->prepareStatementForSqlObject($update)->execute();
    }
}
