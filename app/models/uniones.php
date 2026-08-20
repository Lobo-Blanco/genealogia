<?php

class Uniones extends ActiveRecord
{
    public function buscarEntrePersonas($persona1Id, $persona2Id)
    {
        return $this->find_first(
            "conditions: " .
            "((persona1_id = " . intval($persona1Id) .
            " AND persona2_id = " . intval($persona2Id) . ") " .
            "OR " .
            "(persona1_id = " . intval($persona2Id) .
            " AND persona2_id = " . intval($persona1Id) . ")) " .
            "order: fecha_inicio DESC"
        );
    }

    public function buscarActiva($personaId)
    {
        return $this->find_first(
            "conditions: " .
            "(persona1_id = " . intval($personaId) .
            " OR persona2_id = " . intval($personaId) . ") " .
            "AND fecha_fin IS NULL " .
            "order: fecha_inicio DESC"
        );
    }
}