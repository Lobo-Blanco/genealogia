<?php

class UsuariosController extends AdminController
{
    private function comprobarAcceso()
    {
        if (!Auth::estaAutenticado()) {
            Flash::error('Debe iniciar sesión.');
            Redirect::to('login');
            return false;
        }

        if (!Auth::puedeAdministrarUsuarios()) {
            Flash::error('No tiene permiso para administrar usuarios.');
            Redirect::to('personas');
            return false;
        }

        return true;
    }

    private function rolesPermitidos()
    {
        $roles = array();
        foreach (array(1, 2, 3, 4) as $rolId) {
            if (Auth::puedeCrearUsuario($rolId)) {
                $roles[$rolId] = $this->nombreRol($rolId);
            }
        }
        return $roles;
    }

    private function nombreRol($rolId)
    {
        $nombres = array(
            1 => 'Administrador',
            2 => 'Visualizador',
            3 => 'Editor',
            4 => 'Supervisor'
        );
        return isset($nombres[$rolId]) ? $nombres[$rolId] : 'Desconocido';
    }

    private function arbolesDisponibles()
    {
        if (Auth::esSupervisor()) {
            return (new Arboles())->find('order: nombre');
        }
        return Auth::arboles();
    }

    private function personasDelArbol($arbolId)
    {
        return (new Personas())->find(
            'conditions: arbol_id = ' . intval($arbolId),
            'order: apellidos, nombre'
        );
    }

    public function index()
    {
        if (!$this->comprobarAcceso()) {
            return;
        }

        $this->usuarios = (new Usuarios())->find('order: apellidos, nombre');
    }

    public function nuevo()
    {
        if (!$this->comprobarAcceso()) {
            return;
        }

        $this->roles = $this->rolesPermitidos();
        $this->arboles = $this->arbolesDisponibles();
        $this->personas = array();
    }

    public function personas_arbol($arbolId)
    {
        if (!$this->comprobarAcceso() || !Auth::tieneAccesoArbol($arbolId)) {
            return;
        }

        $personas = $this->personasDelArbol($arbolId);
        $resultado = array();
        foreach ($personas as $persona) {
            $resultado[] = array(
                'id' => $persona->id,
                'texto' => trim($persona->apellidos . ', ' . $persona->nombre)
            );
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($resultado);
        View::select(null);
    }

    public function guardar()
    {
        if (!$this->comprobarAcceso()) {
            return;
        }

        $rolId = intval(Input::post('rol_id'));
        $arbolId = intval(Input::post('arbol_id'));
        $personaId = intval(Input::post('persona_referencia_id'));

        if (!Auth::puedeCrearUsuario($rolId)) {
            Flash::error('No tiene permiso para crear ese tipo de usuario.');
            return Redirect::to('admin/usuarios/nuevo');
        }

        if (!Auth::tieneAccesoArbol($arbolId)) {
            Flash::error('No tiene acceso al árbol seleccionado.');
            return Redirect::to('admin/usuarios/nuevo');
        }

        $persona = (new Personas())->find_first(
            'conditions: id = ' . $personaId
        );

        if (!$persona || intval($persona->arbol_id) != $arbolId) {
            Flash::error('La persona raíz no pertenece al árbol seleccionado.');
            return Redirect::to('admin/usuarios/nuevo');
        }

        $username = trim(Input::post('username'));
        $password = Input::post('password');

        if ($username == '' || $password == '') {
            Flash::error('El nombre de usuario y la contraseña son obligatorios.');
            return Redirect::to('admin/usuarios/nuevo');
        }

        $usuarios = new Usuarios();
        if ($usuarios->buscarPorUsername($username)) {
            Flash::error('Ese nombre de usuario ya existe.');
            return Redirect::to('admin/usuarios/nuevo');
        }

        $usuario = new Usuarios();
        $usuario->nombre = trim(Input::post('nombre'));
        $usuario->apellidos = trim(Input::post('apellidos'));
        $usuario->email = trim(Input::post('email'));
        $usuario->username = $username;
        $usuario->password = password_hash($password, PASSWORD_DEFAULT);
        $usuario->rol_id = $rolId;
        $usuario->persona_referencia_id = $personaId;
        $usuario->activo = 1;
        $usuario->created_at = date('Y-m-d H:i:s');
        $usuario->updated_at = date('Y-m-d H:i:s');

        if (!$usuario->save()) {
            Flash::error('No se ha podido crear el usuario.');
            return Redirect::to('admin/usuarios/nuevo');
        }

        $ua = new UsuariosArboles();
        $ua->usuario_id = $usuario->id;
        $ua->arbol_id = $arbolId;
        $ua->rol = $this->nombreRolArbol($rolId);
        $ua->activo = 1;
        $ua->created_at = date('Y-m-d H:i:s');
        $ua->updated_at = date('Y-m-d H:i:s');

        if (!$ua->save()) {
            $usuario->delete();
            Flash::error('No se ha podido asignar el árbol al usuario.');
            return Redirect::to('admin/usuarios/nuevo');
        }

        if ($rolId == 3 || $rolId == 2) {
            (new UsuariosPersonas())->conceder($usuario->id, $personaId);
        }

        Flash::valid('Usuario creado correctamente.');
        return Redirect::to('admin/usuarios');
    }

    private function nombreRolArbol($rolId)
    {
        $nombres = array(
            1 => 'administrador',
            2 => 'visualizador',
            3 => 'editor',
            4 => 'administrador'
        );
        return $nombres[$rolId];
    }

    public function editar($id)
    {
        if (!$this->comprobarAcceso() || !Auth::puedeGestionarUsuario($id)) {
            if (Auth::estaAutenticado()) {
                Flash::error('No tiene permiso para modificar ese usuario.');
                Redirect::to('admin/usuarios');
            }
            return;
        }

        $usuario = (new Usuarios())->find_first(
            'conditions: id = ' . intval($id)
        );
        if (!$usuario) {
            Flash::error('El usuario no existe.');
            return Redirect::to('admin/usuarios');
        }

        $this->usuario = $usuario;
        $this->roles = $this->rolesPermitidos();
        $this->arboles = $this->arbolesDisponibles();
        $this->personas = array();

        $ua = (new UsuariosArboles())->find_first(
            'conditions: usuario_id = ' . intval($id)
        );
        $this->arbolUsuario = $ua;

        if ($ua) {
            $this->personas = $this->personasDelArbol($ua->arbol_id);
        }
    }

    public function actualizar($id)
    {
        if (!$this->comprobarAcceso() || !Auth::puedeGestionarUsuario($id)) {
            Flash::error('No tiene permiso para modificar ese usuario.');
            return Redirect::to('admin/usuarios');
        }

        $usuario = (new Usuarios())->find_first(
            'conditions: id = ' . intval($id)
        );
        if (!$usuario) {
            Flash::error('El usuario no existe.');
            return Redirect::to('admin/usuarios');
        }

        $rolId = intval(Input::post('rol_id'));
        $arbolId = intval(Input::post('arbol_id'));
        $personaId = intval(Input::post('persona_referencia_id'));

        if (!Auth::puedeCrearUsuario($rolId)) {
            Flash::error('No tiene permiso para asignar ese rol.');
            return Redirect::to('admin/usuarios/editar/' . $id);
        }
        if (!Auth::tieneAccesoArbol($arbolId)) {
            Flash::error('No tiene acceso al árbol seleccionado.');
            return Redirect::to('admin/usuarios/editar/' . $id);
        }

        $persona = (new Personas())->find_first(
            'conditions: id = ' . $personaId
        );
        if (!$persona || intval($persona->arbol_id) != $arbolId) {
            Flash::error('La persona raíz no pertenece al árbol seleccionado.');
            return Redirect::to('admin/usuarios/editar/' . $id);
        }

        $usuario->nombre = trim(Input::post('nombre'));
        $usuario->apellidos = trim(Input::post('apellidos'));
        $usuario->email = trim(Input::post('email'));
        $usuario->rol_id = $rolId;
        $usuario->persona_referencia_id = $personaId;
        $usuario->activo = Input::post('activo') ? 1 : 0;

        $password = Input::post('password');
        if ($password != '') {
            $usuario->password = password_hash($password, PASSWORD_DEFAULT);
        }
        $usuario->updated_at = date('Y-m-d H:i:s');

        if (!$usuario->save()) {
            Flash::error('No se ha podido actualizar el usuario.');
            return Redirect::to('admin/usuarios/editar/' . $id);
        }

        $ua = (new UsuariosArboles())->find_first(
            'conditions: usuario_id = ' . intval($id)
        );
        if (!$ua) {
            $ua = new UsuariosArboles();
            $ua->usuario_id = $id;
            $ua->created_at = date('Y-m-d H:i:s');
        }
        $ua->arbol_id = $arbolId;
        $ua->rol = $this->nombreRolArbol($rolId);
        $ua->activo = 1;
        $ua->updated_at = date('Y-m-d H:i:s');
        $ua->save();

        if ($rolId == 2 || $rolId == 3) {
            (new UsuariosPersonas())->conceder($id, $personaId);
        }

        Flash::valid('Usuario actualizado correctamente.');
        return Redirect::to('admin/usuarios');
    }

    public function desactivar($id)
    {
        if (!$this->comprobarAcceso() || !Auth::puedeGestionarUsuario($id)) {
            Flash::error('No tiene permiso para desactivar ese usuario.');
            return Redirect::to('admin/usuarios');
        }

        $usuario = (new Usuarios())->find_first(
            'conditions: id = ' . intval($id)
        );
        if (!$usuario) {
            Flash::error('El usuario no existe.');
            return Redirect::to('admin/usuarios');
        }

        $usuario->activo = 0;
        $usuario->updated_at = date('Y-m-d H:i:s');
        $usuario->save();

        $registros = (new UsuariosArboles())->find(
            'usuario_id = ' . intval($id)
        );
        foreach ($registros as $registro) {
            $registro->activo = 0;
            $registro->updated_at = date('Y-m-d H:i:s');
            $registro->save();
        }

        Flash::valid('Usuario desactivado.');
        return Redirect::to('admin/usuarios');
    }

    public function borrar($id)
    {
        if (!$this->comprobarAcceso() || !Auth::puedeGestionarUsuario($id)) {
            Flash::error('No tiene permiso para eliminar ese usuario.');
            return Redirect::to('admin/usuarios');
        }

        $usuario = (new Usuarios())->find_first(
            'conditions: id = ' . intval($id)
        );
        if (!$usuario) {
            Flash::error('El usuario no existe.');
            return Redirect::to('admin/usuarios');
        }

        $usuario->delete();
        Flash::valid('Usuario eliminado.');
        return Redirect::to('admin/usuarios');
    }
}
