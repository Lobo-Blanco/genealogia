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

        if ($arbol !== null ) {
            $this->totalPersonas = (new personas)->count("conditions: arbol_id = {$arbol->id}");
            $this->totalFamilias = (new filiaciones)->count();
        }
    }
}
