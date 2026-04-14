<?php

declare(strict_types=1);

namespace Security\Controller;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Security\Model\ModuloModel;
use Security\Model\PerfilModel;
use Security\Model\PermisoPerfilModel;
use Security\Model\SubmoduloModel;
use Security\Model\UsuarioModel;
use Security\Support\AccessHelper;

class SecurityController extends AbstractActionController
{
    private AdapterInterface $db;
    private PerfilModel $perfilModel;
    private ModuloModel $moduloModel;
    private SubmoduloModel $submoduloModel;
    private UsuarioModel $usuarioModel;
    private PermisoPerfilModel $permisoPerfilModel;

    public function __construct(
        AdapterInterface $db,
        PerfilModel $perfilModel,
        ModuloModel $moduloModel,
        SubmoduloModel $submoduloModel,
        UsuarioModel $usuarioModel,
        PermisoPerfilModel $permisoPerfilModel
    ) {
        $this->db = $db;
        $this->perfilModel = $perfilModel;
        $this->moduloModel = $moduloModel;
        $this->submoduloModel = $submoduloModel;
        $this->usuarioModel = $usuarioModel;
        $this->permisoPerfilModel = $permisoPerfilModel;
    }

    public function indexAction()
    {
        return $this->redirect()->toRoute('security', ['action' => 'perfil']);
    }

    public function perfilAction()
    {
        $page = max(1, (int) $this->params()->fromQuery('page', 1));
        $limit = 5;
        $offset = ($page - 1) * $limit;
        $total = $this->perfilModel->getPerfilesTotal();

        return $this->render('security/perfil', [
            'perfiles' => $this->perfilModel->getPerfiles($limit, $offset),
            'page' => $page,
            'pages' => (int) ceil($total / max(1, $limit)),
            'permisos_modulo' => $this->getPermisosRuta('/security/perfil'),
            'breadcrumbs' => $this->breadcrumbs([
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Perfil', 'url' => null],
            ]),
        ]);
    }

    public function perfilAddAction()
    {
        $request = $this->getRequest();
        $error = null;
        $matrix = $this->permisoPerfilModel->getModulosActivosConSubmodulos();
        $permisosIndexados = ['modulos' => [], 'submodulos' => []];

        if ($request->isPost()) {
            $data = $request->getPost()->toArray();
            $nombre = trim((string) ($data['str_nombre_perfil'] ?? ''));

            if ($nombre === '') {
                $error = 'El nombre del perfil es obligatorio.';
            } else {
                try {
                    $connection = $this->db->getDriver()->getConnection();
                    $connection->beginTransaction();

                    $perfilId = $this->perfilModel->createPerfil([
                        'str_nombre_perfil' => $nombre,
                        'descripcion' => trim((string) ($data['descripcion'] ?? '')),
                    ]);

                    if ($perfilId <= 0) {
                        throw new \RuntimeException('No se pudo obtener el ID del perfil recién creado.');
                    }

                    $this->permisoPerfilModel->savePermisosByPerfil($perfilId, $data['permisos'] ?? []);
                    $connection->commit();

                    return $this->redirect()->toRoute('security', ['action' => 'perfil']);
                } catch (\Throwable $exception) {
                    if (isset($connection)) {
                        try {
                            $connection->rollback();
                        } catch (\Throwable $rollbackException) {
                        }
                    }
                    $error = 'No fue posible crear el perfil con sus permisos. Verifica la información e inténtalo de nuevo.';
                }
            }

            $permisosIndexados = $this->normalizePostedPermissions($data['permisos'] ?? []);
        }

        return $this->render('security/perfil-add', [
            'error' => $error,
            'matrix' => $matrix,
            'permisos_indexados' => $permisosIndexados,
            'breadcrumbs' => $this->breadcrumbs([
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Perfil', 'url' => '/security/perfil'],
                ['nombre' => 'Crear', 'url' => null],
            ]),
        ]);
    }

    public function perfilEditAction()
    {
        AccessHelper::startSession();

        $id = (int) $this->params()->fromRoute('id');
        $perfil = $this->perfilModel->getPerfil($id);
        if (!$perfil) {
            return $this->redirect()->toRoute('security', ['action' => 'perfil']);
        }

        $request = $this->getRequest();
        $error = null;
        $matrix = $this->permisoPerfilModel->getModulosActivosConSubmodulos();
        $permisosIndexados = $this->permisoPerfilModel->getPermisosIndexadosByPerfil($id);

        if ($request->isPost()) {
            $data = $request->getPost()->toArray();
            $nombre = trim((string) ($data['str_nombre_perfil'] ?? ''));

            if ($nombre === '') {
                $error = 'El nombre del perfil es obligatorio.';
                $permisosIndexados = $this->normalizePostedPermissions($data['permisos'] ?? []);
            } else {
                $this->perfilModel->updatePerfil($id, [
                    'str_nombre_perfil' => $nombre,
                    'descripcion' => trim((string) ($data['descripcion'] ?? '')),
                ]);
                $this->permisoPerfilModel->savePermisosByPerfil($id, $data['permisos'] ?? []);

                if ((int) ($_SESSION['usuario_perfil_id'] ?? 0) === $id) {
                    $this->refreshCurrentSession();
                }

                return $this->redirect()->toRoute('security', ['action' => 'perfil']);
            }

            $perfil['str_nombre_perfil'] = $nombre;
            $perfil['descripcion'] = trim((string) ($data['descripcion'] ?? $perfil['descripcion'] ?? ''));
        }

        return $this->render('security/perfil-edit', [
            'perfil' => $perfil,
            'error' => $error,
            'matrix' => $matrix,
            'permisos_indexados' => $permisosIndexados,
            'breadcrumbs' => $this->breadcrumbs([
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Perfil', 'url' => '/security/perfil'],
                ['nombre' => 'Editar', 'url' => null],
            ]),
        ]);
    }

    public function perfilDeleteAction()
    {
        $ok = $this->perfilModel->deletePerfil((int) $this->params()->fromRoute('id'));
        if ($ok) {
            return $this->redirect()->toRoute('security', ['action' => 'perfil']);
        }

        return $this->render('security/perfil-detalle', [
            'error' => 'No se puede eliminar este perfil porque tiene usuarios asignados o dependencias activas.',
            'perfil' => null,
            'matrix' => [],
            'permisos_indexados' => ['modulos' => [], 'submodulos' => []],
            'breadcrumbs' => $this->breadcrumbs([
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Perfil', 'url' => '/security/perfil'],
                ['nombre' => 'Detalle', 'url' => null],
            ]),
        ]);
    }

    public function perfilDetalleAction()
    {
        $id = (int) $this->params()->fromRoute('id');
        $perfil = $this->perfilModel->getPerfil($id);
        if (!$perfil) {
            return $this->redirect()->toRoute('security', ['action' => 'perfil']);
        }

        return $this->render('security/perfil-detalle', [
            'perfil' => $perfil,
            'matrix' => $this->permisoPerfilModel->getModulosActivosConSubmodulos(),
            'permisos_indexados' => $this->permisoPerfilModel->getPermisosIndexadosByPerfil($id),
            'breadcrumbs' => $this->breadcrumbs([
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Perfil', 'url' => '/security/perfil'],
                ['nombre' => 'Detalle', 'url' => null],
            ]),
        ]);
    }

    public function moduloAction()
    {
        $page = max(1, (int) $this->params()->fromQuery('page', 1));
        $limit = 5;
        $offset = ($page - 1) * $limit;
        $total = $this->moduloModel->getModulosTotal();

        return $this->render('security/modulo', [
            'modulos' => $this->moduloModel->getModulos($limit, $offset),
            'page' => $page,
            'pages' => (int) ceil($total / max(1, $limit)),
            'permisos_modulo' => $this->getPermisosRuta('/security/modulo'),
            'breadcrumbs' => $this->breadcrumbs([
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Módulo', 'url' => null],
            ]),
        ]);
    }

    public function moduloAddAction()
    {
        $request = $this->getRequest();
        $error = null;

        if ($request->isPost()) {
            $data = $request->getPost()->toArray();
            if (trim((string) ($data['str_nombre_modulo'] ?? '')) === '') {
                $error = 'El nombre del módulo es obligatorio.';
            } else {
                $this->moduloModel->createModulo($data);
                return $this->redirect()->toRoute('security', ['action' => 'modulo']);
            }
        }

        return $this->render('security/modulo-add', [
            'error' => $error,
            'breadcrumbs' => $this->breadcrumbs([
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Módulo', 'url' => '/security/modulo'],
                ['nombre' => 'Crear', 'url' => null],
            ]),
        ]);
    }

    public function moduloEditAction()
    {
        $id = (int) $this->params()->fromRoute('id');
        $modulo = $this->moduloModel->getModulo($id);
        if (!$modulo) {
            return $this->redirect()->toRoute('security', ['action' => 'modulo']);
        }

        $request = $this->getRequest();
        $error = null;

        if ($request->isPost()) {
            $data = $request->getPost()->toArray();
            if (trim((string) ($data['str_nombre_modulo'] ?? '')) === '') {
                $error = 'El nombre del módulo es obligatorio.';
                $modulo = array_merge($modulo, $data);
            } else {
                $this->moduloModel->updateModulo($id, $data);
                return $this->redirect()->toRoute('security', ['action' => 'modulo']);
            }
        }

        return $this->render('security/modulo-edit', [
            'modulo' => $modulo,
            'error' => $error,
            'breadcrumbs' => $this->breadcrumbs([
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Módulo', 'url' => '/security/modulo'],
                ['nombre' => 'Editar', 'url' => null],
            ]),
        ]);
    }

    public function moduloDeleteAction()
    {
        $ok = $this->moduloModel->deleteModulo((int) $this->params()->fromRoute('id'));
        if ($ok) {
            return $this->redirect()->toRoute('security', ['action' => 'modulo']);
        }

        return $this->render('security/modulo-detalle', [
            'error' => 'No se puede eliminar este módulo porque tiene submódulos o permisos activos.',
            'modulo' => null,
            'breadcrumbs' => $this->breadcrumbs([
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Módulo', 'url' => '/security/modulo'],
                ['nombre' => 'Detalle', 'url' => null],
            ]),
        ]);
    }

    public function moduloDetalleAction()
    {
        $id = (int) $this->params()->fromRoute('id');
        $modulo = $this->moduloModel->getModulo($id);
        if (!$modulo) {
            return $this->redirect()->toRoute('security', ['action' => 'modulo']);
        }

        return $this->render('security/modulo-detalle', [
            'modulo' => $modulo,
            'submodulos' => $this->submoduloModel->getSubmodulosActivosByModulo($id),
            'breadcrumbs' => $this->breadcrumbs([
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Módulo', 'url' => '/security/modulo'],
                ['nombre' => 'Detalle', 'url' => null],
            ]),
        ]);
    }

    public function submoduloAction()
    {
        $page = max(1, (int) $this->params()->fromQuery('page', 1));
        $moduleId = (int) $this->params()->fromQuery('modulo', 0);
        $limit = 8;
        $offset = ($page - 1) * $limit;
        $total = $this->submoduloModel->getSubmodulosTotal($moduleId > 0 ? $moduleId : null);

        return $this->render('security/submodulo', [
            'submodulos' => $this->submoduloModel->getSubmodulos($limit, $offset, $moduleId > 0 ? $moduleId : null),
            'modulos_select' => $this->submoduloModel->getModulosForSelect(),
            'modulo_filtro' => $moduleId,
            'page' => $page,
            'pages' => (int) ceil($total / max(1, $limit)),
            'permisos_modulo' => $this->getPermisosRuta('/security/submodulo'),
            'breadcrumbs' => $this->breadcrumbs([
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Submódulo', 'url' => null],
            ]),
        ]);
    }

    public function submoduloAddAction()
    {
        $request = $this->getRequest();
        $error = null;

        if ($request->isPost()) {
            $data = $request->getPost()->toArray();
            if (empty($data['id_modulo']) || trim((string) ($data['str_nombre_submodulo'] ?? '')) === '') {
                $error = 'Debes seleccionar un módulo y escribir el nombre del submódulo.';
            } else {
                $this->submoduloModel->createSubmodulo($data);
                return $this->redirect()->toRoute('security', ['action' => 'submodulo']);
            }
        }

        return $this->render('security/submodulo-add', [
            'error' => $error,
            'modulos_select' => $this->submoduloModel->getModulosForSelect(),
            'breadcrumbs' => $this->breadcrumbs([
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Submódulo', 'url' => '/security/submodulo'],
                ['nombre' => 'Crear', 'url' => null],
            ]),
        ]);
    }

    public function submoduloEditAction()
    {
        $id = (int) $this->params()->fromRoute('id');
        $submodulo = $this->submoduloModel->getSubmodulo($id);
        if (!$submodulo) {
            return $this->redirect()->toRoute('security', ['action' => 'submodulo']);
        }

        $request = $this->getRequest();
        $error = null;

        if ($request->isPost()) {
            $data = $request->getPost()->toArray();
            if (empty($data['id_modulo']) || trim((string) ($data['str_nombre_submodulo'] ?? '')) === '') {
                $error = 'Debes seleccionar un módulo y escribir el nombre del submódulo.';
                $submodulo = array_merge($submodulo, $data);
            } else {
                $this->submoduloModel->updateSubmodulo($id, $data);
                return $this->redirect()->toRoute('security', ['action' => 'submodulo']);
            }
        }

        return $this->render('security/submodulo-edit', [
            'submodulo' => $submodulo,
            'modulos_select' => $this->submoduloModel->getModulosForSelect(),
            'error' => $error,
            'breadcrumbs' => $this->breadcrumbs([
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Submódulo', 'url' => '/security/submodulo'],
                ['nombre' => 'Editar', 'url' => null],
            ]),
        ]);
    }

    public function submoduloDeleteAction()
    {
        $ok = $this->submoduloModel->deleteSubmodulo((int) $this->params()->fromRoute('id'));
        if ($ok) {
            return $this->redirect()->toRoute('security', ['action' => 'submodulo']);
        }

        return $this->render('security/submodulo-detalle', [
            'error' => 'No se puede eliminar este submódulo porque tiene permisos activos.',
            'submodulo' => null,
            'breadcrumbs' => $this->breadcrumbs([
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Submódulo', 'url' => '/security/submodulo'],
                ['nombre' => 'Detalle', 'url' => null],
            ]),
        ]);
    }

    public function submoduloDetalleAction()
    {
        $id = (int) $this->params()->fromRoute('id');
        $submodulo = $this->submoduloModel->getSubmodulo($id);
        if (!$submodulo) {
            return $this->redirect()->toRoute('security', ['action' => 'submodulo']);
        }

        return $this->render('security/submodulo-detalle', [
            'submodulo' => $submodulo,
            'breadcrumbs' => $this->breadcrumbs([
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Submódulo', 'url' => '/security/submodulo'],
                ['nombre' => 'Detalle', 'url' => null],
            ]),
        ]);
    }

    public function usuarioAction()
    {
        $page = max(1, (int) $this->params()->fromQuery('page', 1));
        $limit = 5;
        $offset = ($page - 1) * $limit;
        $total = $this->usuarioModel->getUsuariosTotal();

        return $this->render('security/usuario', [
            'usuarios' => $this->usuarioModel->getUsuarios($limit, $offset),
            'page' => $page,
            'pages' => (int) ceil($total / max(1, $limit)),
            'permisos_modulo' => $this->getPermisosRuta('/security/usuario'),
            'breadcrumbs' => $this->breadcrumbs([
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Usuario', 'url' => null],
            ]),
        ]);
    }

    public function usuarioAddAction()
    {
        $request = $this->getRequest();
        $error = null;

        if ($request->isPost()) {
            $data = $request->getPost()->toArray();

            $data['str_numero_celular'] = preg_replace('/\D+/', '', (string) ($data['str_numero_celular'] ?? '')) ?? '';
            $error = $this->validateUsuarioData($data, true);

            if ($error === null) {
                $this->usuarioModel->createUsuario($data);
                return $this->redirect()->toRoute('security', ['action' => 'usuario']);
            }
        }

        return $this->render('security/usuario-add', [
            'error' => $error,
            'perfiles' => $this->usuarioModel->getPerfilesForSelect(),
            'estados' => $this->usuarioModel->getEstadosForSelect(),
            'breadcrumbs' => $this->breadcrumbs([
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Usuario', 'url' => '/security/usuario'],
                ['nombre' => 'Crear', 'url' => null],
            ]),
        ]);
    }

    public function usuarioEditAction()
    {
        AccessHelper::startSession();

        $id = (int) $this->params()->fromRoute('id');
        $usuario = $this->usuarioModel->getUsuario($id);
        if (!$usuario) {
            return $this->redirect()->toRoute('security', ['action' => 'usuario']);
        }

        $request = $this->getRequest();
        $error = null;

        if ($request->isPost()) {
            $data = $request->getPost()->toArray();

            $data['str_numero_celular'] = preg_replace('/\D+/', '', (string) ($data['str_numero_celular'] ?? '')) ?? '';
            $error = $this->validateUsuarioData($data, false);

            if ($error !== null) {
                $usuario = array_merge($usuario, $data);
            } else {
                $this->usuarioModel->updateUsuario($id, $data);
                if ((int) ($_SESSION['usuario_id'] ?? 0) === $id) {
                    $this->refreshCurrentSession();
                }
                return $this->redirect()->toRoute('security', ['action' => 'usuario']);
            }
        }

        return $this->render('security/usuario-edit', [
            'usuario' => $usuario,
            'error' => $error,
            'perfiles' => $this->usuarioModel->getPerfilesForSelect(),
            'estados' => $this->usuarioModel->getEstadosForSelect(),
            'breadcrumbs' => $this->breadcrumbs([
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Usuario', 'url' => '/security/usuario'],
                ['nombre' => 'Editar', 'url' => null],
            ]),
        ]);
    }

    public function usuarioDeleteAction()
    {
        $ok = $this->usuarioModel->deleteUsuario((int) $this->params()->fromRoute('id'));
        if ($ok) {
            return $this->redirect()->toRoute('security', ['action' => 'usuario']);
        }

        return $this->render('security/usuario-detalle', [
            'error' => 'No se puede eliminar este usuario.',
            'usuario' => null,
            'breadcrumbs' => $this->breadcrumbs([
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Usuario', 'url' => '/security/usuario'],
                ['nombre' => 'Detalle', 'url' => null],
            ]),
        ]);
    }

    public function usuarioDetalleAction()
    {
        $id = (int) $this->params()->fromRoute('id');
        $usuario = $this->usuarioModel->getUsuario($id);
        if (!$usuario) {
            return $this->redirect()->toRoute('security', ['action' => 'usuario']);
        }

        return $this->render('security/usuario-detalle', [
            'usuario' => $usuario,
            'breadcrumbs' => $this->breadcrumbs([
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Usuario', 'url' => '/security/usuario'],
                ['nombre' => 'Detalle', 'url' => null],
            ]),
        ]);
    }

    public function permisoPerfilAction()
    {
        $page = max(1, (int) $this->params()->fromQuery('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $total = $this->permisoPerfilModel->getPermisosTotal();

        return $this->render('security/permiso-perfil', [
            'permisos' => $this->permisoPerfilModel->getPermisos($limit, $offset),
            'page' => $page,
            'pages' => (int) ceil($total / max(1, $limit)),
            'permisos_modulo' => $this->getPermisosRuta('/security/permiso-perfil'),
            'breadcrumbs' => $this->breadcrumbs([
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Permisos-Perfil', 'url' => null],
            ]),
        ]);
    }

    public function permisoPerfilAddAction()
    {
        $request = $this->getRequest();
        $error = null;
        $modulos = $this->permisoPerfilModel->getModulosActivosConSubmodulos();

        if ($request->isPost()) {
            $data = $request->getPost()->toArray();
            if (empty($data['id_perfil']) || (empty($data['id_modulo']) && empty($data['id_submodulo']))) {
                $error = 'Debes seleccionar un perfil y un recurso.';
            } else {
                $this->permisoPerfilModel->createPermiso($data);
                if ((int) ($_SESSION['usuario_perfil_id'] ?? 0) === (int) $data['id_perfil']) {
                    $this->refreshCurrentSession();
                }
                return $this->redirect()->toRoute('security', ['action' => 'permiso-perfil']);
            }
        }

        return $this->render('security/permiso-perfil-add', [
            'error' => $error,
            'perfiles' => $this->permisoPerfilModel->getPerfilesForSelect(),
            'modulos_tree' => $modulos,
            'breadcrumbs' => $this->breadcrumbs([
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Permisos-Perfil', 'url' => '/security/permiso-perfil'],
                ['nombre' => 'Crear', 'url' => null],
            ]),
        ]);
    }

    public function permisoPerfilEditAction()
    {
        AccessHelper::startSession();

        $id = (int) $this->params()->fromRoute('id');
        $permiso = $this->permisoPerfilModel->getPermiso($id);
        if (!$permiso) {
            return $this->redirect()->toRoute('security', ['action' => 'permiso-perfil']);
        }

        $request = $this->getRequest();
        if ($request->isPost()) {
            $this->permisoPerfilModel->updatePermiso($id, $request->getPost()->toArray());
            if ((int) ($_SESSION['usuario_perfil_id'] ?? 0) === (int) ($permiso['id_perfil'] ?? 0)) {
                $this->refreshCurrentSession();
            }
            return $this->redirect()->toRoute('security', ['action' => 'permiso-perfil']);
        }

        return $this->render('security/permiso-perfil-edit', [
            'permiso' => $permiso,
            'breadcrumbs' => $this->breadcrumbs([
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Permisos-Perfil', 'url' => '/security/permiso-perfil'],
                ['nombre' => 'Editar', 'url' => null],
            ]),
        ]);
    }

    public function permisoPerfilDeleteAction()
    {
        AccessHelper::startSession();
        $id = (int) $this->params()->fromRoute('id');
        $permiso = $this->permisoPerfilModel->getPermiso($id);
        $ok = $this->permisoPerfilModel->deletePermiso($id);

        if ($ok) {
            if ($permiso && (int) ($_SESSION['usuario_perfil_id'] ?? 0) === (int) ($permiso['id_perfil'] ?? 0)) {
                $this->refreshCurrentSession();
            }
            return $this->redirect()->toRoute('security', ['action' => 'permiso-perfil']);
        }

        return $this->render('security/permiso-perfil-detalle', [
            'error' => 'No se pudo eliminar el permiso.',
            'permiso' => $permiso,
            'breadcrumbs' => $this->breadcrumbs([
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Permisos-Perfil', 'url' => '/security/permiso-perfil'],
                ['nombre' => 'Detalle', 'url' => null],
            ]),
        ]);
    }

    public function permisoPerfilDetalleAction()
    {
        $permiso = $this->permisoPerfilModel->getPermiso((int) $this->params()->fromRoute('id'));
        if (!$permiso) {
            return $this->redirect()->toRoute('security', ['action' => 'permiso-perfil']);
        }

        return $this->render('security/permiso-perfil-detalle', [
            'permiso' => $permiso,
            'breadcrumbs' => $this->breadcrumbs([
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Permisos-Perfil', 'url' => '/security/permiso-perfil'],
                ['nombre' => 'Detalle', 'url' => null],
            ]),
        ]);
    }

    private function render(string $template, array $data = []): ViewModel
    {
        $view = new ViewModel(array_merge($data, [
            'menu_modulos' => AccessHelper::buildMenu($this->db),
        ]));
        $view->setTemplate($template);
        return $view;
    }

    private function breadcrumbs(array $breadcrumbs): array
    {
        return $breadcrumbs;
    }

    private function getPermisosRuta(string $path): array
    {
        return AccessHelper::getPathPermissions($this->db, $path);
    }

    private function normalizePostedPermissions(array $permisos): array
    {
        return [
            'modulos' => is_array($permisos['modulos'] ?? null) ? $permisos['modulos'] : [],
            'submodulos' => is_array($permisos['submodulos'] ?? null) ? $permisos['submodulos'] : [],
        ];
    }
    private function validateUsuarioData(array $data, bool $requirePassword = true): ?string
    {
        $nombre = trim((string) ($data['str_nombre_usuario'] ?? ''));
        $correo = strtolower(trim((string) ($data['str_correo'] ?? '')));
        $telefono = preg_replace('/\D+/', '', (string) ($data['str_numero_celular'] ?? '')) ?? '';
        $perfil = (int) ($data['id_perfil'] ?? 0);
        $password = (string) ($data['str_pwd'] ?? '');

        if ($nombre === '' || $correo === '' || $perfil <= 0) {
            return 'Nombre de empleado, correo y perfil son obligatorios.';
        }

        if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 120) {
            return 'El nombre de empleado debe tener entre 3 y 120 caracteres.';
        }

        if (!preg_match('/^[\p{L}\s\.\-]+$/u', $nombre)) {
            return 'El nombre de empleado solo puede contener letras, espacios, puntos y guiones.';
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return 'El correo electrónico no tiene un formato válido.';
        }

        if ($telefono !== '' && !preg_match('/^\d{10}$/', $telefono)) {
            return 'El teléfono debe contener exactamente 10 dígitos numéricos.';
        }

        if ($requirePassword && $password === '') {
            return 'La contraseña es obligatoria.';
        }

        if ($password !== '' && mb_strlen($password) < 8) {
            return 'La contraseña debe tener al menos 8 caracteres.';
        }

        return null;
    }

    private function refreshCurrentSession(): void
    {
        AccessHelper::startSession();
        $userId = (int) ($_SESSION['usuario_id'] ?? 0);
        if ($userId <= 0) {
            return;
        }

        $row = $this->db->query(
            'SELECT 
                u.id,
                u.str_nombre_usuario,
                u.str_correo,
                u.id_perfil,
                p.str_nombre_perfil,
                p.bit_administrador
             FROM usuario u
             INNER JOIN perfil p ON p.id = u.id_perfil
             WHERE u.id = ?',
            [$userId]
        )->current();

        if (!$row) {
            return;
        }

        $sessionPermissions = $this->permisoPerfilModel->getSessionPermissionsForPerfil((int) $row['id_perfil']);
        $esAdmin = $this->permisoPerfilModel->profileIsAdmin((int) $row['id_perfil']);

        $_SESSION['usuario_nombre'] = $row['str_nombre_usuario'];
        $_SESSION['usuario_correo'] = $row['str_correo'];
        $_SESSION['usuario_perfil_id'] = (int) $row['id_perfil'];
        $_SESSION['perfil_nombre'] = $row['str_nombre_perfil'];
        $_SESSION['usuario_rol'] = $row['str_nombre_perfil'];
        $_SESSION['permisos_modulos'] = $sessionPermissions['modulos'];
        $_SESSION['permisos_submodulos'] = $sessionPermissions['submodulos'];
        $_SESSION['permisos'] = $sessionPermissions['modulos'];
        $_SESSION['es_admin'] = $esAdmin;
        $_SESSION['bit_administrador'] = $esAdmin;
    }
}
