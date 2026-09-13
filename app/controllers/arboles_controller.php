<?php

class ArbolesController extends AppController
{
    /**
     * Comprueba que el usuario puede administrar árboles.
     */
    private function puedeAdministrar()
    {
        return Auth::estaAutenticado() &&
            (Auth::esSupervisor() || Auth::esAdministrador());
    }

    /**
     * Lista los árboles a los que tiene acceso el usuario actual.
     */
    public function index()
    {
        if (!Auth::estaAutenticado()) {
            Flash::error('Debe iniciar sesión.');
            return Redirect::to('login');
        }

        $this->arboles = Auth::arboles();
    }

    /**
     * Formulario para crear un árbol.
     *
     * El supervisor puede crear tantos árboles como necesite.
     * El administrador solamente puede crear su único árbol.
     */
    public function nuevo()
    {
        if (!Auth::estaAutenticado()) {
            Flash::error('Debe iniciar sesión.');
            return Redirect::to('login');
        }

        if (!$this->puedeAdministrar()) {
            Flash::error('No tiene permiso para crear árboles.');
            return Redirect::to('arboles');
        }

        if (Auth::esAdministrador() && count(Auth::arboles()) > 0) {
            Flash::error('Un administrador solamente puede tener un árbol.');
            return Redirect::to('arboles');
        }

        $this->arbol = new Arboles();
    }

    /**
     * Guarda un nuevo árbol.
     *
     * Un administrador queda asociado al árbol que crea.
     * Un supervisor no necesita una relación en usuarios_arboles,
     * porque tiene acceso global y selecciona el árbol mediante sesión.
     */
    public function guardar()
    {
        if (!Auth::estaAutenticado()) {
            Flash::error('Debe iniciar sesión.');
            return Redirect::to('login');
        }

        if (!$this->puedeAdministrar()) {
            Flash::error('No tiene permiso para crear árboles.');
            return Redirect::to('arboles');
        }

        $usuario = Auth::usuario();

        if (Auth::esAdministrador() && count(Auth::arboles()) > 0) {
            Flash::error('Un administrador solamente puede tener un árbol.');
            return Redirect::to('arboles');
        }

        $nombre = trim(Input::post('nombre'));
        $descripcion = Input::post('descripcion');

        if ($nombre == '') {
            Flash::error('El nombre del árbol es obligatorio.');
            return Redirect::to('arboles/nuevo');
        }

        $arbol = new Arboles();
        $arbol->nombre = $nombre;
        $arbol->descripcion = $descripcion;
        $arbol->created_at = date('Y-m-d H:i:s');
        $arbol->updated_at = date('Y-m-d H:i:s');

        $arbol->begin();

        try {
            if (!$arbol->save()) {
                throw new Exception('No se ha podido crear el árbol.');
            }

            if (Auth::esAdministrador()) {
                $usuarioArbol = new UsuariosArboles();
                $usuarioArbol->usuario_id = $usuario->id;
                $usuarioArbol->arbol_id = $arbol->id;
                $usuarioArbol->rol = 'administrador';
                $usuarioArbol->activo = 1;
                $usuarioArbol->created_at = date('Y-m-d H:i:s');
                $usuarioArbol->updated_at = date('Y-m-d H:i:s');

                if (!$usuarioArbol->save()) {
                    throw new Exception('No se ha podido asignar el árbol al administrador.');
                }
            }

            $arbol->commit();

            if (Auth::esSupervisor()) {
                Auth::cambiarArbol($arbol->id);
            }

            Flash::valid('Árbol creado correctamente.');
            return Redirect::to('arboles');
        } catch (Exception $e) {
            $arbol->rollback();
            Flash::error($e->getMessage());
            $this->arbol = $arbol;
        }
    }

    /**
     * Formulario para editar un árbol.
     */
    public function editar($id)
    {
        if (!Auth::estaAutenticado()) {
            Flash::error('Debe iniciar sesión.');
            return Redirect::to('login');
        }

        if (!$this->puedeAdministrar()) {
            Flash::error('No tiene permiso para modificar árboles.');
            return Redirect::to('arboles');
        }

        $arbol = (new Arboles())->find_first(
            'conditions: id = ' . intval($id)
        );

        if (!$arbol) {
            Flash::error('El árbol no existe.');
            return Redirect::to('arboles');
        }

        if (!Auth::tieneAccesoArbol($arbol->id)) {
            Flash::error('No tiene acceso a ese árbol.');
            return Redirect::to('arboles');
        }

        $this->arbol = $arbol;
    }

    /**
     * Actualiza los datos de un árbol.
     */
    public function actualizar($id)
    {
        if (!Auth::estaAutenticado()) {
            Flash::error('Debe iniciar sesión.');
            return Redirect::to('login');
        }

        if (!$this->puedeAdministrar()) {
            Flash::error('No tiene permiso para modificar árboles.');
            return Redirect::to('arboles');
        }

        $arbol = (new Arboles())->find_first(
            'conditions: id = ' . intval($id)
        );

        if (!$arbol) {
            Flash::error('El árbol no existe.');
            return Redirect::to('arboles');
        }

        if (!Auth::tieneAccesoArbol($arbol->id)) {
            Flash::error('No tiene acceso a ese árbol.');
            return Redirect::to('arboles');
        }

        $nombre = trim(Input::post('nombre'));

        if ($nombre == '') {
            Flash::error('El nombre del árbol es obligatorio.');
            return Redirect::to('arboles/editar/' . $arbol->id);
        }

        $arbol->nombre = $nombre;
        $arbol->descripcion = Input::post('descripcion');
        $arbol->updated_at = date('Y-m-d H:i:s');

        if (!$arbol->save()) {
            Flash::error('No se ha podido actualizar el árbol.');
            return Redirect::to('arboles/editar/' . $arbol->id);
        }

        Flash::valid('Árbol actualizado correctamente.');
        return Redirect::to('arboles');
    }

    /**
     * Elimina un árbol y sus datos genealógicos.
     */
    public function borrar($id)
    {
        if (!Auth::estaAutenticado()) {
            Flash::error('Debe iniciar sesión.');
            return Redirect::to('login');
        }

        if (!$this->puedeAdministrar()) {
            Flash::error('No tiene permiso para eliminar árboles.');
            return Redirect::to('arboles');
        }

        $arbol = (new Arboles())->find_first(
            'conditions: id = ' . intval($id)
        );

        if (!$arbol) {
            Flash::error('El árbol no existe.');
            return Redirect::to('arboles');
        }

        if (!Auth::tieneAccesoArbol($arbol->id)) {
            Flash::error('No tiene acceso a ese árbol.');
            return Redirect::to('arboles');
        }

        $arbol->begin();

        try {
            $personas = new Personas();
            $listaPersonas = $personas->find(
                'arbol_id = ' . intval($arbol->id)
            );

            foreach ($listaPersonas as $persona) {
                $filiaciones = new Filiaciones();
                $filacionesPersona = $filiaciones->find(
                    'hijo_id = ' . intval($persona->id) .
                    ' OR progenitor_id = ' . intval($persona->id)
                );

                foreach ($filacionesPersona as $filiacion) {
                    $filiacion->delete();
                }

                $uniones = new Uniones();
                $unionesPersona = $uniones->find(
                    'persona1_id = ' . intval($persona->id) .
                    ' OR persona2_id = ' . intval($persona->id)
                );

                foreach ($unionesPersona as $union) {
                    $divorcios = new Divorcios();
                    $divorciosUnion = $divorcios->find(
                        'union_id = ' . intval($union->id)
                    );

                    foreach ($divorciosUnion as $divorcio) {
                        $divorcio->delete();
                    }

                    $union->delete();
                }

                $usuariosPersonas = new UsuariosPersonas();
                $permisosPersona = $usuariosPersonas->find(
                    'persona_id = ' . intval($persona->id)
                );

                foreach ($permisosPersona as $permiso) {
                    $permiso->delete();
                }

                $persona->delete();
            }

            $usuariosArboles = new UsuariosArboles();
            $relacionesArbol = $usuariosArboles->find(
                'arbol_id = ' . intval($arbol->id)
            );

            foreach ($relacionesArbol as $relacion) {
                $relacion->delete();
            }

            if (!$arbol->delete()) {
                throw new Exception('No se ha podido eliminar el árbol.');
            }

            $arbol->commit();
            Flash::valid('Árbol eliminado correctamente.');
        } catch (Exception $e) {
            $arbol->rollback();
            Flash::error($e->getMessage());
        }

        return Redirect::to('arboles');
    }

    /**
     * Selecciona un árbol como activo.
     */
    public function seleccionar($id)
    {
        if (!Auth::estaAutenticado()) {
            Flash::error('Debe iniciar sesión.');
            return Redirect::to('login');
        }

        if (!Auth::tieneAccesoArbol(intval($id))) {
            Flash::error('No tiene acceso a ese árbol.');
            return Redirect::to('arboles');
        }

        if (Auth::cambiarArbol(intval($id))) {
            Flash::valid('Árbol seleccionado correctamente.');
        } else {
            Flash::error('No se ha podido seleccionar el árbol.');
        }

        return Redirect::to('arboles');
    }
}
