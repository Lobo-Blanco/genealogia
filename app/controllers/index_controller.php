<?php

/**
 * Controller por defecto si no se usa el routes
 *
 */
class IndexController extends AppController
{
    public function index()
    {
        $usuarios = new Usuarios();

        /*
         * Primera ejecución de la aplicación:
         * si no existe ningún usuario, debemos crear el
         * administrador inicial.
         */
        if (!$usuarios->find_first()) {
            return Redirect::to('login/crear_administrador');
        }

        /*
         * Si ya existen usuarios, el acceso normal comienza
         * en la pantalla de inicio de sesión.
         */
        if (!Auth::estaAutenticado()) {
            return Redirect::to('login');
        }

        return Redirect::to('personas');
    }
}
