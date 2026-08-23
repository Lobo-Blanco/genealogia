<?php

class ArbolesController extends AppController
{
    /**
     * Lista los árboles a los que tiene acceso
     * el usuario actual.
     */
    public function index()
    {
        if (!Auth::estaAutenticado()) {
            Flash::error(
                'Debe iniciar sesión.'
            );

            return Redirect::to('login');
        }

        $this->arboles =
            Auth::arboles();
    }


    /**
     * Formulario para crear un árbol.
     */
    public function nuevo()
    {
        if (!Auth::estaAutenticado()) {
            Flash::error(
                'Debe iniciar sesión.'
            );

            return Redirect::to('login');
        }

        /*
         * De momento solamente el administrador
         * puede crear árboles.
         */
        if (!Auth::esAdministrador()) {
            Flash::error(
                'No tiene permiso para crear árboles.'
            );

            return Redirect::to('arboles');
        }

        $this->arbol = new Arboles();
    }


    /**
     * Guarda un nuevo árbol y asigna al usuario
     * actual como administrador del mismo.
     */
    public function guardar()
    {
        if (!Auth::estaAutenticado()) {
            Flash::error(
                'Debe iniciar sesión.'
            );

            return Redirect::to('login');
        }

        if (!Auth::esAdministrador()) {
            Flash::error(
                'No tiene permiso para crear árboles.'
            );

            return Redirect::to('arboles');
        }

        $nombre =
            trim(Input::post('nombre'));

        $descripcion =
            Input::post('descripcion');

        if ($nombre == '') {
            Flash::error(
                'El nombre del árbol es obligatorio.'
            );

            return Redirect::to(
                'arboles/nuevo'
            );
        }

        $usuario =
            Auth::usuario();

        $arbol =
            new Arboles();

        $arbol->nombre =
            $nombre;

        $arbol->descripcion =
            $descripcion;

        $arbol->created_at =
            date('Y-m-d H:i:s');

        $arbol->updated_at =
            date('Y-m-d H:i:s');


        /*
         * Árbol y relación con el usuario forman
         * una única operación.
         */
        $arbol->begin();

        try {
            if (!$arbol->save()) {
                throw new Exception(
                    'No se ha podido crear el árbol.'
                );
            }

            $usuarioArbol =
                new UsuariosArboles();

            $usuarioArbol->usuario_id =
                $usuario->id;

            $usuarioArbol->arbol_id =
                $arbol->id;

            $usuarioArbol->rol =
                'administrador';

            $usuarioArbol->activo =
                1;

            $usuarioArbol->created_at =
                date('Y-m-d H:i:s');

            $usuarioArbol->updated_at =
                date('Y-m-d H:i:s');

            /*
             * Desactivamos los demás árboles
             * antes de activar el nuevo.
             */
            $registros =
                (new UsuariosArboles())->find(
                    "usuario_id = " .
                    intval($usuario->id)
                );

            foreach ($registros as $registro) {
                $registro->activo = 0;

                if (!$registro->save()) {
                    throw new Exception(
                        'No se ha podido actualizar el árbol activo.'
                    );
                }
            }


            if (!$usuarioArbol->save()) {
                throw new Exception(
                    'No se ha podido asignar el árbol al usuario.'
                );
            }


            $arbol->commit();

            Flash::valid(
                'Árbol creado correctamente.'
            );

            return Redirect::to(
                'arboles'
            );

        } catch (Exception $e) {
            $arbol->rollback();

            Flash::error(
                $e->getMessage()
            );

            $this->arbol = $arbol;

            return;
        }
    }


    /**
     * Selecciona un árbol como activo.
     */
    public function seleccionar($id)
    {
        if (!Auth::estaAutenticado()) {
            Flash::error(
                'Debe iniciar sesión.'
            );

            return Redirect::to('login');
        }

        if (!Auth::tieneAccesoArbol(
            intval($id)
        )) {
            Flash::error(
                'No tiene acceso a ese árbol.'
            );

            return Redirect::to(
                'arboles'
            );
        }

        if (Auth::cambiarArbol(
            intval($id)
        )) {
            Flash::valid(
                'Árbol seleccionado correctamente.'
            );
        } else {
            Flash::error(
                'No se ha podido seleccionar el árbol.'
            );
        }

        return Redirect::to(
            'arboles'
        );
    }
}