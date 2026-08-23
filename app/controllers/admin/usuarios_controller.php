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

        // Creación de usuario con rol_id = 1
    }
}
