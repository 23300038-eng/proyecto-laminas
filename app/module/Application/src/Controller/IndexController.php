<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Sql;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Security\Model\ModuloModel;
use Security\Model\SubmoduloModel;
use Security\Support\AccessHelper;

class IndexController extends AbstractActionController
{
    private AdapterInterface $db;
    private ModuloModel $moduloModel;
    private SubmoduloModel $submoduloModel;

    public function __construct(AdapterInterface $db)
    {
        $this->db = $db;
        $this->moduloModel = new ModuloModel($db);
        $this->submoduloModel = new SubmoduloModel($db);
    }

    public function indexAction()
    {
        $menu = AccessHelper::buildMenu($this->db);

        return new ViewModel([
            'menu_modulos' => $menu,
            'accesos_directos' => $menu,
            'es_admin' => AccessHelper::isAdmin(),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => null],
            ],
        ]);
    }

    public function miPerfilAction()
    {
        AccessHelper::startSession();

        $usuarioId = (int) ($_SESSION['usuario_id'] ?? 0);
        $usuario = $this->getUsuarioActual($usuarioId);

        if (!$usuario) {
            return $this->redirect()->toRoute('auth', ['action' => 'logout']);
        }

        $error = null;
        $success = null;
        $request = $this->getRequest();

        if ($request->isPost()) {
            $data = $request->getPost()->toArray();

            $payload = [
                'str_nombre_usuario' => trim((string) ($data['str_nombre_usuario'] ?? '')),
                'str_numero_celular' => preg_replace('/\D+/', '', (string) ($data['str_numero_celular'] ?? '')) ?? '',
                'str_pwd' => trim((string) ($data['str_pwd'] ?? '')),
            ];

            $error = $this->validateUserData($payload, false);

            if ($error === null) {
                try {
                    $this->actualizarUsuarioActual($usuarioId, $payload);
                    $_SESSION['usuario_nombre'] = $payload['str_nombre_usuario'];
                    $usuario = $this->getUsuarioActual($usuarioId);
                    $success = 'Tus datos se actualizaron correctamente.';
                } catch (\Throwable $exception) {
                    $error = 'No fue posible actualizar tu perfil. Verifica que el nombre del empleado no esté repetido.';
                }
            }
        }

        return new ViewModel([
            'usuario' => $usuario,
            'error' => $error,
            'success' => $success,
            'menu_modulos' => AccessHelper::buildMenu($this->db),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Mi Perfil', 'url' => null],
            ],
        ]);
    }

    public function modularAction()
    {
        $moduleSlug = trim((string) $this->params()->fromRoute('modulo', ''));
        $submoduleSlug = trim((string) $this->params()->fromRoute('submodulo', ''));
        $requestPath = '/' . ltrim((string) $this->getRequest()->getUri()->getPath(), '/');
        $requestPath = rtrim($requestPath, '/');
        if ($requestPath === '') {
            $requestPath = '/';
        }

        if ($moduleSlug === '') {
            return $this->redirect()->toRoute('home');
        }

        $submodulo = null;
        $modulo = null;

        if ($submoduleSlug !== '') {
            $submodulo = $this->submoduloModel->getSubmoduloByRoute($requestPath);
            if ($submodulo) {
                $modulo = $this->moduloModel->getModulo((int) ($submodulo['id_modulo'] ?? 0));
            }
        }

        if (!$modulo) {
            $modulo = $this->moduloModel->getModuloBySlug($moduleSlug);
        }

        if (!$modulo) {
            return $this->notFoundAction();
        }

        $allSubmodules = array_map(
            static fn($row): array => (array) $row,
            $this->submoduloModel->getSubmodulosActivosByModulo((int) $modulo['id'])
        );
        $accessibleSubmodules = array_values(array_filter($allSubmodules, static function ($submodulo): bool {
            $submodulo = (array) $submodulo;
            return AccessHelper::hasSubmodulePermissionById((int) ($submodulo['id'] ?? 0), 'consulta');
        }));

        if ($submoduleSlug !== '') {
            if (!$submodulo) {
                $submodulo = $this->submoduloModel->getSubmoduloBySlug((int) $modulo['id'], $submoduleSlug);
            }

            if (!$submodulo) {
                return $this->notFoundAction();
            }

            if (!AccessHelper::hasSubmodulePermissionById((int) $submodulo['id'], 'consulta')) {
                return $this->redirect()->toRoute('home');
            }

            return $this->renderModular('application/index/modular-submodule', [
                'modulo' => $modulo,
                'submodulo' => $submodulo,
                'submodulos_accesibles' => $accessibleSubmodules,
                'permisos_actuales' => AccessHelper::getSubmodulePermissionsById((int) $submodulo['id']),
                'breadcrumbs' => [
                    ['nombre' => 'Inicio', 'url' => '/'],
                    ['nombre' => $modulo['str_nombre_modulo'], 'url' => '/modulos/' . AccessHelper::slugify((string) $modulo['str_nombre_modulo'])],
                    ['nombre' => $submodulo['str_nombre_submodulo'], 'url' => null],
                ],
            ]);
        }

        if (!AccessHelper::hasModulePermissionById((int) $modulo['id'], 'consulta') && empty($accessibleSubmodules)) {
            return $this->redirect()->toRoute('home');
        }

        return $this->renderModular('application/index/modular-module', [
            'modulo' => $modulo,
            'submodulos_accesibles' => $accessibleSubmodules,
            'permisos_actuales' => AccessHelper::getModulePermissionsById((int) $modulo['id']),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => $modulo['str_nombre_modulo'], 'url' => null],
            ],
        ]);
    }

    private function renderModular(string $template, array $data): ViewModel
    {
        $view = new ViewModel(array_merge($data, [
            'menu_modulos' => AccessHelper::buildMenu($this->db),
        ]));
        $view->setTemplate($template);
        return $view;
    }

    private function getUsuarioActual(int $usuarioId): ?array
    {
        if ($usuarioId <= 0) {
            return null;
        }

        try {
            $row = $this->db->query(
                'SELECT 
                    u.*,
                    p.str_nombre_perfil,
                    e.str_nombre AS str_nombre_estado
                 FROM usuario u
                 INNER JOIN perfil p ON p.id = u.id_perfil
                 LEFT JOIN estado_usuario e ON e.id = u.id_estado_usuario
                 WHERE u.id = ?',
                [$usuarioId]
            )->current();

            return $row ? (array) $row : null;
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function actualizarUsuarioActual(int $usuarioId, array $data): void
    {
        $sql = new Sql($this->db);

        $updateData = [
            'str_nombre_usuario' => $data['str_nombre_usuario'],
            'str_numero_celular' => $data['str_numero_celular'] !== '' ? $data['str_numero_celular'] : null,
            'actualizado_en' => new Expression('CURRENT_TIMESTAMP'),
        ];

        if (!empty($data['str_pwd'])) {
            $updateData['str_pwd'] = password_hash((string) $data['str_pwd'], PASSWORD_BCRYPT);
        }


        $update = $sql->update('usuario')
            ->set($updateData)
            ->where(['id' => $usuarioId]);

        $sql->prepareStatementForSqlObject($update)->execute();
    }
    private function validateUserData(array $data, bool $requirePassword = false): ?string
    {
        $nombre = trim((string) ($data['str_nombre_usuario'] ?? ''));
        $telefonoCrudo = trim((string) ($data['str_numero_celular'] ?? ''));
        $telefono = preg_replace('/\D+/', '', $telefonoCrudo) ?? '';
        $password = (string) ($data['str_pwd'] ?? '');

        if ($nombre === '') {
            return 'El nombre de empleado es obligatorio.';
        }

        if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 120) {
            return 'El nombre de empleado debe tener entre 3 y 120 caracteres.';
        }

        if (!preg_match('/^[\p{L}\s\.\-]+$/u', $nombre)) {
            return 'El nombre de empleado solo puede contener letras, espacios, puntos y guiones.';
        }

        if ($telefono !== '' && !preg_match('/^\d{10}$/', $telefono)) {
            return 'El teléfono debe contener exactamente 10 dígitos.';
        }

        if ($requirePassword && $password === '') {
            return 'La contraseña es obligatoria.';
        }

        if ($password !== '' && mb_strlen($password) < 8) {
            return 'La contraseña debe tener al menos 8 caracteres.';
        }

        return null;
    }
}

