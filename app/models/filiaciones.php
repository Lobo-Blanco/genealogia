<?php

class Filiaciones extends ActiveRecord
{
    public function initialize()
    {
        $this->belongs_to(
            'hijo',
            'model: Personas',
            'fk: hijo_id'
        );

        $this->belongs_to(
            'progenitor',
            'model: Personas',
            'fk: progenitor_id'
        );
    }
}