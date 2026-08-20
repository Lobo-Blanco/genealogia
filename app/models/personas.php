<?php

class Personas extends ActiveRecord
{
    /**
     * Obtiene las filiaciones en las que la persona
     * aparece como hijo.
     */
    public function progenitores()
    {
        return Load::model('filiaciones')->find(
            "conditions: hijo_id = {$this->id}"
        );
    }

    /**
     * Obtiene las filiaciones en las que la persona
     * aparece como progenitor.
     */
    public function hijos()
    {
        return Load::model('filiaciones')->find(
            "conditions: progenitor_id = {$this->id}"
        );
    }

    /**
     * Devuelve las personas que son progenitores.
     */
    public function personasProgenitoras()
    {
        $filiaciones = $this->progenitores();

        $personas = [];

        foreach ($filiaciones as $filiacion) {
            $persona = Load::model('personas')->find(
                $filiacion->progenitor_id
            );

            if ($persona) {
                $personas[] = $persona;
            }
        }

        return $personas;
    }

    /**
     * Devuelve las personas que son hijos.
     */
    public function personasHijas()
    {
        $filiaciones = $this->hijos();

        $personas = [];

        foreach ($filiaciones as $filiacion) {
            $persona = Load::model('personas')->find(
                $filiacion->hijo_id
            );

            if ($persona) {
                $personas[] = $persona;
            }
        }

        return $personas;
    }

    /**
     * Obtiene las uniones en las que la persona
     * es persona1.
     */
    public function unionesComoPersona1()
    {
        return Load::model('uniones')->find(
            "conditions: persona1_id = {$this->id}"
        );
    }

    /**
     * Obtiene las uniones en las que la persona
     * es persona2.
     */
    public function unionesComoPersona2()
    {
        return Load::model('uniones')->find(
            "conditions: persona2_id = {$this->id}"
        );
    }

    public function buscarPorNombre($texto, $excluirId = null)
    {
        $texto = addslashes(trim($texto));

        if ($texto == '') {
            return array();
        }

        $condiciones =
            "(nombre LIKE '%{$texto}%' " .
            "OR apellidos LIKE '%{$texto}%')";

        if ($excluirId !== null) {
            $condiciones .=
                " AND id <> " . intval($excluirId);
        }

        return $this->find(
            "conditions: {$condiciones}",
            "order: apellidos, nombre"
        );
    }
}