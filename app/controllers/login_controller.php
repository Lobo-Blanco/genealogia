<?php

Load::Lib("auth");

class LoginController extends AppController
{
    public function index()
    {
        $crear_admin = false;

        if (!Auth::estaAutenticado()) {
            if ((new Usuarios)->count() == 0) {
                $crear_admin = true;
            } else {{
                return Redirect::to('login');
            }
        }

        if (Input::hasPost('username')) {

            $username = Input::post('username');
            $password = Input::post('password');

            if ($cread_admin) {
                // ir a crear administrador
                return Redirect::lo("login");
            }

            $usuarios = new Usuarios();

            $usuario = $usuarios->buscarPorUsername($username);

            if (
                $usuario &&
                $usuario->activo &&
                password_verify(
                    $password,
                    $usuario->password
                )
            ) {

                Auth::login($usuario);

                Flash::valid(
                    'Bienvenido, ' .
                    $usuario->nombre
                );

                return Redirect::to('personas');
            }

            Flash::error(
                'Usuario o contraseña incorrectos.'
            );
        }
    }

    public function logout()
    {
        Auth::logout();

        Flash::valid(
            'Sesión cerrada correctamente.'
        );

        return Redirect::to('login');
    }
}