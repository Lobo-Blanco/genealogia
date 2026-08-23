<?php

/**
 * Controller por defecto si no se usa el routes
 *
 */
class UsuariosController extends AdminController
{
    public function index()
    {
    }

    public function crear_admin() {
        if ((new Usuarios)->count() > 0) {
            return Redirect::to("login");
        }

        if (Input::hasPost('username')) {
            $usuarios = new Usuarios();

            $usuarios->nombre = Input::post('nombre');
            $usuarios->apellidos = Input::post('apellidos');
            $usuarios->email = Input::post('email');
            $usuarios->username = Input::post('username');
            $usuarios->password = password_hash(
                Input::post('password'),
                PASSWORD_DEFAULT
            );
            $usuarios->rol_id = 1;
            $usuarios->persona_referencia_id = null;
            $usuarios->activo = 1;

            $usuarios->save();

            Auth::login($usuario);

            Flash::valid(
                'Bienvenido, ' .
                $usuario->nombre
            );

            return Redirect::to('personas');
        }
    }
}
