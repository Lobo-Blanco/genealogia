<?php

Load::Lib("auth");

class LoginController extends AppController
{
    public function index()
    {
        if (Auth::estaAutenticado()) {
            return Redirect::to('personas');
        }

        $usuarios = new Usuarios();

        if ($usuarios->count() == 0) {
            Redirect::to("usuarios_crear_admin");
        }

        if (Input::hasPost('username')) {
            $username = Input::post('username');
            $password = Input::post('password');

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