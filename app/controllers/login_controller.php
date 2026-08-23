<?php

Load::Lib("auth");

class LoginController extends AppController
{
    public function index()
    {
        $usuarios = new Usuarios();

        if (!$usuarios->find_first()) {
            return Redirect::to('login/crear_administrador');
        }

        if (Auth::estaAutenticado()) {
            return Redirect::to('personas');
        }

        if (Input::hasPost('username')) {
            $username = Input::post('username');
            $password = Input::post('password');
            $usuario = $usuarios->buscarPorUsername($username);

            if (
                $usuario &&
                $usuario->activo &&
                password_verify($password, $usuario->password)
            ) {
                Auth::login($usuario);

                Flash::valid(
                    'Bienvenido, ' . $usuario->nombre
                );

                return Redirect::to('personas');
            }

            Flash::error(
                'Usuario o contraseña incorrectos.'
            );
        }
    }

    /**
     * Crea el primer usuario de la aplicación.
     * Este usuario adquiere automáticamente el rol de administrador.
     */
    public function crear_administrador()
    {
        if (Auth::estaAutenticado()) {
            return Redirect::to('personas');
        }

        $usuarios = new Usuarios();

        if ($usuarios->find_first()) {
            Flash::error(
                'La aplicación ya tiene usuarios creados.'
            );

            return Redirect::to('login');
        }

        if (Input::hasPost('username')) {
            $nombre = trim(Input::post('nombre'));
            $username = trim(Input::post('username'));
            $password = Input::post('password');
            $passwordConfirmacion =
                Input::post('password_confirmacion');

            if ($nombre === '') {
                Flash::error('Debe indicar el nombre.');
                return;
            }

            if ($username === '') {
                Flash::error('Debe indicar el nombre de usuario.');
                return;
            }

            if ($password === '') {
                Flash::error('Debe indicar una contraseña.');
                return;
            }

            if ($password !== $passwordConfirmacion) {
                Flash::error('Las contraseñas no coinciden.');
                return;
            }

            $usuario = new Usuarios();
            $usuario->nombre = $nombre;
            $usuario->username = $username;
            $usuario->password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );
            $usuario->rol_id = 1;
            $usuario->activo = 1;

            if (!$usuario->save()) {
                Flash::error(
                    'No se ha podido crear el administrador.'
                );

                return;
            }

            Auth::login($usuario);

            Flash::valid(
                'Administrador creado correctamente. Bienvenido.'
            );

            return Redirect::to('personas');
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
