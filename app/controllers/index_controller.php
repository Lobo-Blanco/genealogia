<?php

/**
 * Controller por defecto si no se usa el routes
 *
 */
class IndexController extends AppController
{
    public function index()
    {
        if (!Auth::estaAutenticado()) {
            return Redirect::to('login');
        }

        return Redirect::to('personas');
    }
}
