<?php

class UsuariosPersonas extends ActiveRecord
{
    public function puedeEditar(
        $usuarioId,
        $personaId
    ) {
        return $this->count(
            "conditions: usuario_id = {$usuarioId}
                AND persona_id = {$personaId}"
        ) > 0;
    }

    public function conceder(
        $usuarioId,
        $personaId
    ) {
        if (
            $this->puedeEditar(
                $usuarioId,
                $personaId
            )
        ) {
            return true;
        }

        $permiso = new UsuariosPersonas();

        $permiso->usuario_id = $usuarioId;
        $permiso->persona_id = $personaId;

        return $permiso->save();
    }
}