<?php

class Usuarios extends ActiveRecord
{
    /**
     * Busca un usuario por su nombre de usuario.
     */
    public function buscarPorUsername($username)
    {
        return $this->find_first(
            "conditions: username = '" . addslashes($username) . "'"
        );
    }

    /**
     * Comprueba si el usuario tiene un determinado rol.
     */
    public function esAdministrador()
    {
        return $this->rol_id == 1;
    }

    public function esVisualizador()
    {
        return $this->rol_id == 2;
    }

    public function esEditor()
    {
        return $this->rol_id == 3;
    }
}