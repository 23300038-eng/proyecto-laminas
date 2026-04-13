<?php

declare(strict_types=1);

namespace Security\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\Adapter\AdapterInterface;
use Security\Model\ModuloModel;
use Security\Model\PerfilModel;
use Security\Model\PermisoPerfilModel;
use Security\Model\UsuarioModel;

class SecurityController extends AbstractActionController
{
    private AdapterInterface $db;
    private PerfilModel $perfilModel;
    private ModuloModel $moduloModel;
    private UsuarioModel $usuarioModel;
    private PermisoPerfilModel $permisoPerfilModel;

    public function __construct(
        AdapterInterface $db,
        PerfilModel $perfilModel,
        ModuloModel $moduloModel,
        UsuarioModel $usuarioModel,
        PermisoPerfilModel $permisoPerfilModel
    ) {
        $this->db                = $db;
        $this->perfilModel       = $perfilModel;
        $this->moduloModel       = $moduloModel;
        $this->usuarioModel      = $usuarioModel;
        $this->permisoPerfilModel = $permisoPerfilModel;
    }

    /**
     * Menú dinámico filtrado por permisos del usuario en sesión.
     */
/**
     * Menú dinámico usando tabla modulo + submodulo, filtrado por permisos.
     */
    private function getModulosParaMenu(): array
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $esAdmin   = !empty($_SESSION['bit_administrador']);
        $permisos  = $_SESSION['permisos'] ?? [];

        // IDs de módulos permitidos
        $permitidos = null;
        if (!$esAdmin) {
            $permitidos = [];
            foreach ($permisos as $idM => $bits) {
                if ($bits['agregar'] || $bits['editar'] || $bits['consulta'] || $bits['eliminar'] || $bits['detalle']) {
                    $permitidos[] = (int)$idM;
                }
            }
        }

        // Rutas estáticas para submodulos de Seguridad
        $rutasSeguridad = [
            'perfil'          => '/security/perfil',
            'módulo'          => '/security/modulo',
            'modulo'          => '/security/modulo',
            'permisos-perfil' => '/security/permiso-perfil',
            'permisos'        => '/security/permiso-perfil',
            'permiso'         => '/security/permiso-perfil',
            'usuario'         => '/security/usuario',
        ];

        try {
            $sqlMod = "SELECT id, str_nombre_modulo, str_icono FROM modulo WHERE bit_activo = true ORDER BY int_orden ASC";
            $modulos = iterator_to_array($this->db->query($sqlMod, []));

            $menu = [];
            foreach ($modulos as $m) {
                $idMod = (int)$m['id'];
                // Filtrar si no tiene permiso
                if ($permitidos !== null && !in_array($idMod, $permitidos, true)) {
                    continue;
                }

                // Obtener submodulos
                $sqlSub = "SELECT id, str_nombre_submodulo, str_ruta FROM submodulo WHERE id_modulo = ? AND bit_activo = true ORDER BY int_orden ASC";
                $subs   = iterator_to_array($this->db->query($sqlSub, [$idMod]));

                $items = [];
                foreach ($subs as $sub) {
                    $ruta  = $sub['str_ruta'] ?? '#';
                    $nombre = $sub['str_nombre_submodulo'];
                    // Intentar ruta estática para seguridad
                    $key = strtolower($nombre);
                    foreach ($rutasSeguridad as $k => $r) {
                        if (strpos($key, $k) !== false) {
                            $ruta = $r;
                            break;
                        }
                    }
                    $items[] = ['nombre' => $nombre, 'url' => $ruta, 'icono' => ''];
                }

                if (!empty($items)) {
                    $menu[$m['str_nombre_modulo']] = $items;
                }
            }
            return $menu;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Devuelve los permisos del usuario actual para un módulo por nombre (búsqueda flexible).
     * Útil para ocultar/mostrar botones CRUD en las vistas.
     */
    private function getPermisosModulo(string $nombreModulo): array
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!empty($_SESSION['bit_administrador'])) {
            return ['agregar'=>true,'editar'=>true,'consulta'=>true,'eliminar'=>true,'detalle'=>true];
        }
        $permisos = $_SESSION['permisos'] ?? [];
        // Buscar por nombre de módulo en DB
        try {
            $sql    = "SELECT id FROM modulo WHERE LOWER(str_nombre_modulo) LIKE LOWER(?) LIMIT 1";
            $result = $this->db->query($sql, ['%' . $nombreModulo . '%'])->current();
            if ($result && isset($permisos[(int)$result['id']])) {
                return $permisos[(int)$result['id']];
            }
        } catch (\Exception $e) {}
        return ['agregar'=>false,'editar'=>false,'consulta'=>false,'eliminar'=>false,'detalle'=>false];
    }

// ==================== PERFIL ====================

    public function perfilAction()
    {
        $page = (int)$this->params()->fromQuery('page', 1);
        $limit = 5;
        $offset = ($page - 1) * $limit;

        $perfiles = $this->perfilModel->getPerfiles($limit, $offset);
        $total = $this->perfilModel->getPerfilesTotal();
        $pages = ceil($total / $limit);

        return new ViewModel([
            'perfiles' => $perfiles,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'permisos_modulo' => $this->getPermisosModulo('perfil'),
            'modulos' => $this->getModulosParaMenu(),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Perfil', 'url' => null],
            ],
        ]);
    }

    public function perfilAddAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $data = $request->getPost()->toArray();

            // Validaciones básicas
            if (empty($data['str_nombre_perfil'])) {
                return new ViewModel([
                    'error' => 'El nombre del perfil es requerido',
                    'modulos' => $this->getModulosParaMenu(),
                    'breadcrumbs' => [
                        ['nombre' => 'Inicio', 'url' => '/'],
                        ['nombre' => 'Seguridad', 'url' => null],
                        ['nombre' => 'Perfil', 'url' => '/security/perfil'],
                        ['nombre' => 'Crear', 'url' => null],
                    ],
                ]);
            }

            $this->perfilModel->createPerfil($data);
            return $this->redirect()->toRoute('security', ['action' => 'perfil']);
        }

        return new ViewModel([
            'modulos' => $this->getModulosParaMenu(),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Perfil', 'url' => '/security/perfil'],
                ['nombre' => 'Crear', 'url' => null],
            ],
        ]);
    }

    public function perfilEditAction()
    {
        $id = (int)$this->params()->fromRoute('id');
        $perfil = $this->perfilModel->getPerfil($id);

        if (!$perfil) {
            return $this->redirect()->toRoute('security', ['action' => 'perfil']);
        }

        $request = $this->getRequest();

        if ($request->isPost()) {
            $data = $request->getPost()->toArray();

            if (empty($data['str_nombre_perfil'])) {
                return new ViewModel([
                    'perfil' => $perfil,
                    'error' => 'El nombre del perfil es requerido',
                    'modulos' => $this->getModulosParaMenu(),
                    'breadcrumbs' => [
                        ['nombre' => 'Inicio', 'url' => '/'],
                        ['nombre' => 'Seguridad', 'url' => null],
                        ['nombre' => 'Perfil', 'url' => '/security/perfil'],
                        ['nombre' => 'Editar', 'url' => null],
                    ],
                ]);
            }

            $this->perfilModel->updatePerfil($id, $data);
            return $this->redirect()->toRoute('security', ['action' => 'perfil']);
        }

        return new ViewModel([
            'perfil' => $perfil,
            'modulos' => $this->getModulosParaMenu(),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Perfil', 'url' => '/security/perfil'],
                ['nombre' => 'Editar', 'url' => null],
            ],
        ]);
    }

    public function perfilDeleteAction()
    {
        $id = (int)$this->params()->fromRoute('id');

        if ($this->perfilModel->deletePerfil($id)) {
            return $this->redirect()->toRoute('security', ['action' => 'perfil']);
        }

        return new ViewModel([
            'error' => 'No se puede eliminar este perfil. Verifique que no haya usuarios asignados.',
            'modulos' => $this->getModulosParaMenu(),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Perfil', 'url' => '/security/perfil'],
                ['nombre' => 'Eliminar', 'url' => null],
            ],
        ]);
    }

    public function perfilDetalleAction()
    {
        $id = (int)$this->params()->fromRoute('id');
        $perfil = $this->perfilModel->getPerfil($id);

        if (!$perfil) {
            return $this->redirect()->toRoute('security', ['action' => 'perfil']);
        }

        return new ViewModel([
            'perfil' => $perfil,
            'modulos' => $this->getModulosParaMenu(),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Perfil', 'url' => '/security/perfil'],
                ['nombre' => 'Detalle', 'url' => null],
            ],
        ]);
    }

    // ==================== MÓDULO ====================

    public function moduloAction()
    {
        $page = (int)$this->params()->fromQuery('page', 1);
        $limit = 5;
        $offset = ($page - 1) * $limit;

        $modulos = $this->moduloModel->getModulos($limit, $offset);
        $total = $this->moduloModel->getModulosTotal();
        $pages = ceil($total / $limit);

        return new ViewModel([
            'modulos' => $this->getModulosParaMenu(),
            'modulos' => $modulos,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'permisos_modulo' => $this->getPermisosModulo('modulo'),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Módulo', 'url' => null],
            ],
        ]);
    }

    public function moduloAddAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $data = $request->getPost()->toArray();

            if (empty($data['str_nombre_modulo'])) {
                return new ViewModel([
                    'error' => 'El nombre del módulo es requerido',
                    'modulos' => $this->getModulosParaMenu(),
                    'breadcrumbs' => [
                        ['nombre' => 'Inicio', 'url' => '/'],
                        ['nombre' => 'Seguridad', 'url' => null],
                        ['nombre' => 'Módulo', 'url' => '/security/modulo'],
                        ['nombre' => 'Crear', 'url' => null],
                    ],
                ]);
            }

            $this->moduloModel->createModulo($data);
            return $this->redirect()->toRoute('security', ['action' => 'modulo']);
        }

        return new ViewModel([
            'modulos' => $this->getModulosParaMenu(),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Módulo', 'url' => '/security/modulo'],
                ['nombre' => 'Crear', 'url' => null],
            ],
        ]);
    }

    public function moduloEditAction()
    {
        $id = (int)$this->params()->fromRoute('id');
        $modulo = $this->moduloModel->getModulo($id);

        if (!$modulo) {
            return $this->redirect()->toRoute('security', ['action' => 'modulo']);
        }

        $request = $this->getRequest();

        if ($request->isPost()) {
            $data = $request->getPost()->toArray();

            if (empty($data['str_nombre_modulo'])) {
                return new ViewModel([
                    'modulo' => $modulo,
                    'error' => 'El nombre del módulo es requerido',
                    'modulos' => $this->getModulosParaMenu(),
                    'breadcrumbs' => [
                        ['nombre' => 'Inicio', 'url' => '/'],
                        ['nombre' => 'Seguridad', 'url' => null],
                        ['nombre' => 'Módulo', 'url' => '/security/modulo'],
                        ['nombre' => 'Editar', 'url' => null],
                    ],
                ]);
            }

            $this->moduloModel->updateModulo($id, $data);
            return $this->redirect()->toRoute('security', ['action' => 'modulo']);
        }

        return new ViewModel([
            'modulo' => $modulo,
            'modulos' => $this->getModulosParaMenu(),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Módulo', 'url' => '/security/modulo'],
                ['nombre' => 'Editar', 'url' => null],
            ],
        ]);
    }

    public function moduloDeleteAction()
    {
        $id = (int)$this->params()->fromRoute('id');

        if ($this->moduloModel->deleteModulo($id)) {
            return $this->redirect()->toRoute('security', ['action' => 'modulo']);
        }

        return new ViewModel([
            'error' => 'No se puede eliminar este módulo. Verifique que no tenga permisos o submódulos asociados.',
            'modulos' => $this->getModulosParaMenu(),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Módulo', 'url' => '/security/modulo'],
                ['nombre' => 'Eliminar', 'url' => null],
            ],
        ]);
    }

    public function moduloDetalleAction()
    {
        $id = (int)$this->params()->fromRoute('id');
        $modulo = $this->moduloModel->getModulo($id);

        if (!$modulo) {
            return $this->redirect()->toRoute('security', ['action' => 'modulo']);
        }

        return new ViewModel([
            'modulo' => $modulo,
            'modulos' => $this->getModulosParaMenu(),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Módulo', 'url' => '/security/modulo'],
                ['nombre' => 'Detalle', 'url' => null],
            ],
        ]);
    }

    // ==================== USUARIO ====================

    public function usuarioAction()
    {
        $page = (int)$this->params()->fromQuery('page', 1);
        $limit = 5;
        $offset = ($page - 1) * $limit;

        $usuarios = $this->usuarioModel->getUsuarios($limit, $offset);
        $total = $this->usuarioModel->getUsuariosTotal();
        $pages = ceil($total / $limit);

        return new ViewModel([
            'usuarios' => $usuarios,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'permisos_modulo' => $this->getPermisosModulo('usuario'),
            'modulos' => $this->getModulosParaMenu(),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Usuario', 'url' => null],
            ],
        ]);
    }

    public function usuarioAddAction()
    {
        $request = $this->getRequest();
        $perfiles = $this->usuarioModel->getPerfilesForSelect();
        $estados = $this->usuarioModel->getEstadosForSelect();

        if ($request->isPost()) {
            $data = $request->getPost()->toArray();

            // Validaciones básicas
            if (empty($data['str_nombre_usuario']) || empty($data['str_pwd']) || empty($data['str_correo'])) {
                return new ViewModel([
                    'error' => 'Los campos requeridos no pueden estar vacíos',
                    'perfiles' => $perfiles,
                    'estados' => $estados,
                    'modulos' => $this->getModulosParaMenu(),
                    'breadcrumbs' => [
                        ['nombre' => 'Inicio', 'url' => '/'],
                        ['nombre' => 'Seguridad', 'url' => null],
                        ['nombre' => 'Usuario', 'url' => '/security/usuario'],
                        ['nombre' => 'Crear', 'url' => null],
                    ],
                ]);
            }

            // Manejar upload de imagen
            $files = $request->getFiles();
            if ($files->get('imagen')) {
                $file = $files->get('imagen');
                
                // Verificar que sea un objeto válido de UploadedFile
                if (is_object($file) && method_exists($file, 'getClientFilename')) {
                    $uploadDir = 'public/uploads/usuarios/';
                    
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $filename = $file->getClientFilename();
                    if (!empty($filename)) {
                        $ext = pathinfo($filename, PATHINFO_EXTENSION);
                        $nombre = uniqid('user_') . '.' . $ext;
                        $file->moveTo($uploadDir . $nombre);
                        $data['imagen'] = 'uploads/usuarios/' . $nombre;
                    }
                }
            }

            $this->usuarioModel->createUsuario($data);
            return $this->redirect()->toRoute('security', ['action' => 'usuario']);
        }

        return new ViewModel([
            'perfiles' => $perfiles,
            'estados' => $estados,
            'modulos' => $this->getModulosParaMenu(),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Usuario', 'url' => '/security/usuario'],
                ['nombre' => 'Crear', 'url' => null],
            ],
        ]);
    }

    public function usuarioEditAction()
    {
        $id = (int)$this->params()->fromRoute('id');
        $usuario = $this->usuarioModel->getUsuario($id);

        if (!$usuario) {
            return $this->redirect()->toRoute('security', ['action' => 'usuario']);
        }

        $request = $this->getRequest();
        $perfiles = $this->usuarioModel->getPerfilesForSelect();
        $estados = $this->usuarioModel->getEstadosForSelect();

        if ($request->isPost()) {
            $data = $request->getPost()->toArray();

            if (empty($data['str_nombre_usuario']) || empty($data['str_correo'])) {
                return new ViewModel([
                    'usuario' => $usuario,
                    'error' => 'Los campos requeridos no pueden estar vacíos',
                    'perfiles' => $perfiles,
                    'estados' => $estados,
                    'modulos' => $this->getModulosParaMenu(),
                    'breadcrumbs' => [
                        ['nombre' => 'Inicio', 'url' => '/'],
                        ['nombre' => 'Seguridad', 'url' => null],
                        ['nombre' => 'Usuario', 'url' => '/security/usuario'],
                        ['nombre' => 'Editar', 'url' => null],
                    ],
                ]);
            }

            // Manejar upload de imagen
            $files = $request->getFiles();
            if ($files->get('imagen')) {
                $file = $files->get('imagen');
                
                // Verificar que sea un objeto válido de UploadedFile
                if (is_object($file) && method_exists($file, 'getClientFilename')) {
                    $uploadDir = 'public/uploads/usuarios/';
                    
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $filename = $file->getClientFilename();
                    if (!empty($filename)) {
                        $ext = pathinfo($filename, PATHINFO_EXTENSION);
                        $nombre = uniqid('user_') . '.' . $ext;
                        $file->moveTo($uploadDir . $nombre);
                        $data['imagen'] = 'uploads/usuarios/' . $nombre;
                    }
                }
            }

            $this->usuarioModel->updateUsuario($id, $data);
            return $this->redirect()->toRoute('security', ['action' => 'usuario']);
        }

        return new ViewModel([
            'usuario' => $usuario,
            'perfiles' => $perfiles,
            'estados' => $estados,
            'modulos' => $this->getModulosParaMenu(),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Usuario', 'url' => '/security/usuario'],
                ['nombre' => 'Editar', 'url' => null],
            ],
        ]);
    }

    public function usuarioDeleteAction()
    {
        $id = (int)$this->params()->fromRoute('id');

        if ($this->usuarioModel->deleteUsuario($id)) {
            return $this->redirect()->toRoute('security', ['action' => 'usuario']);
        }

        return new ViewModel([
            'error' => 'No se puede eliminar este usuario.',
            'modulos' => $this->getModulosParaMenu(),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Usuario', 'url' => '/security/usuario'],
                ['nombre' => 'Eliminar', 'url' => null],
            ],
        ]);
    }

    public function usuarioDetalleAction()
    {
        $id = (int)$this->params()->fromRoute('id');
        $usuario = $this->usuarioModel->getUsuario($id);

        if (!$usuario) {
            return $this->redirect()->toRoute('security', ['action' => 'usuario']);
        }

        return new ViewModel([
            'usuario' => $usuario,
            'modulos' => $this->getModulosParaMenu(),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Usuario', 'url' => '/security/usuario'],
                ['nombre' => 'Detalle', 'url' => null],
            ],
        ]);
    }

    // ==================== PERMISOS-PERFIL ====================

    public function permisoPerfilAction()
    {
        $page = (int)$this->params()->fromQuery('page', 1);
        $limit = 5;
        $offset = ($page - 1) * $limit;

        $permisos = $this->permisoPerfilModel->getPermisos($limit, $offset);
        $total = $this->permisoPerfilModel->getPermisosTotal();
        $pages = ceil($total / $limit);

        return new ViewModel([
            'permisos' => $permisos,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'permisos_modulo' => $this->getPermisosModulo('permiso'),
            'modulos' => $this->getModulosParaMenu(),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Permisos-Perfil', 'url' => null],
            ],
        ]);
    }

    public function permisoPerfilAddAction()
    {
        $request = $this->getRequest();
        $modulos = $this->permisoPerfilModel->getModulosForSelect();
        $perfiles = $this->permisoPerfilModel->getPerfilesForSelect();

        if ($request->isPost()) {
            $data = $request->getPost()->toArray();

            if (empty($data['id_modulo']) || empty($data['id_perfil'])) {
                return new ViewModel([
                    'error' => 'El módulo y perfil son requeridos',
                    'modulos_list' => $modulos,
                    'perfiles' => $perfiles,
                    'modulos' => $this->getModulosParaMenu(),
                    'breadcrumbs' => [
                        ['nombre' => 'Inicio', 'url' => '/'],
                        ['nombre' => 'Seguridad', 'url' => null],
                        ['nombre' => 'Permisos-Perfil', 'url' => '/security/permiso-perfil'],
                        ['nombre' => 'Crear', 'url' => null],
                    ],
                ]);
            }

            $this->permisoPerfilModel->createPermiso($data);
            return $this->redirect()->toRoute('security', ['action' => 'permiso-perfil']);
        }

        return new ViewModel([
            'modulos_list' => $modulos,
            'perfiles' => $perfiles,
            'modulos' => $this->getModulosParaMenu(),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Permisos-Perfil', 'url' => '/security/permiso-perfil'],
                ['nombre' => 'Crear', 'url' => null],
            ],
        ]);
    }

    public function permisoPerfilEditAction()
    {
        $id = (int)$this->params()->fromRoute('id');
        $permiso = $this->permisoPerfilModel->getPermiso($id);

        if (!$permiso) {
            return $this->redirect()->toRoute('security', ['action' => 'permiso-perfil']);
        }

        $request = $this->getRequest();
        $modulos = $this->permisoPerfilModel->getModulosForSelect();
        $perfiles = $this->permisoPerfilModel->getPerfilesForSelect();

        if ($request->isPost()) {
            $data = $request->getPost()->toArray();

            $this->permisoPerfilModel->updatePermiso($id, $data);
            return $this->redirect()->toRoute('security', ['action' => 'permiso-perfil']);
        }

        return new ViewModel([
            'permiso' => $permiso,
            'modulos_list' => $modulos,
            'perfiles' => $perfiles,
            'modulos' => $this->getModulosParaMenu(),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Permisos-Perfil', 'url' => '/security/permiso-perfil'],
                ['nombre' => 'Editar', 'url' => null],
            ],
        ]);
    }

    public function permisoPerfilDeleteAction()
    {
        $id = (int)$this->params()->fromRoute('id');

        if ($this->permisoPerfilModel->deletePermiso($id)) {
            return $this->redirect()->toRoute('security', ['action' => 'permiso-perfil']);
        }

        return new ViewModel([
            'error' => 'No se puede eliminar este permiso.',
            'modulos' => $this->getModulosParaMenu(),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Permisos-Perfil', 'url' => '/security/permiso-perfil'],
                ['nombre' => 'Eliminar', 'url' => null],
            ],
        ]);
    }

    public function permisoPerfilDetalleAction()
    {
        $id = (int)$this->params()->fromRoute('id');
        $permiso = $this->permisoPerfilModel->getPermiso($id);

        if (!$permiso) {
            return $this->redirect()->toRoute('security', ['action' => 'permiso-perfil']);
        }

        return new ViewModel([
            'permiso' => $permiso,
            'modulos' => $this->getModulosParaMenu(),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Seguridad', 'url' => null],
                ['nombre' => 'Permisos-Perfil', 'url' => '/security/permiso-perfil'],
                ['nombre' => 'Detalle', 'url' => null],
            ],
        ]);
    }}
