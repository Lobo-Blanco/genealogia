<?php

class UsuariosArboles extends ActiveRecord
{
    public function initialize()
    {
        $this->belongs_to('usuarios', "fk: usuario_id");
        $this->belongs_to('arboles', "fk: arbol_id");
    }
}