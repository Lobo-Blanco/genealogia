<?php

class Arboles extends ActiveRecord
{
    public function initialize()
    {
        $this->has_many('personas', "fk: arbol_id");
        $this->has_many('usuarios_arboles', "fk: arbol_id");
    }
}