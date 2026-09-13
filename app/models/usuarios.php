<?php

class Usuarios extends ActiveRecord
{
    public function buscarPorUsername($username)
    {
        return $this->find_first(
            "conditions: username = '" . addslashes($username) . "'"
        );
    }

    public function esAdministrador() { return $this->rol_id == 1; }
    public function esVisualizador() { return $this->rol_id == 2; }
    public function esEditor() { return $this->rol_id == 3; }
    public function esSupervisor() { return $this->rol_id == 4; }
}
