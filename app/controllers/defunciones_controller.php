<?php

class DefuncionesController extends AppController
{
    /**
     * Registra o modifica la defunción de una persona.
     *
     * La defunción se trata como una operación independiente
     * de la ficha general de la persona.
     */
    public function registrar($id)
    {
        if (!Auth::estaAutenticado()) {
            Flash::error('Debe iniciar sesión.');
            return Redirect::to('login');
        }

        $arbol = Auth::arbolActual();

        if (!$arbol) {
            Flash::error('No hay un árbol activo.');
            return Redirect::to('personas');
        }

        $persona = (new Personas())->find_first(
            "conditions: arbol_id = " . intval($arbol->id) .
            " AND id = " . intval($id)
        );

        if (!$persona) {
            Flash::error('La persona no existe en el árbol actual.');
            return Redirect::to('personas');
        }

        if (!Auth::puedeEditar($persona->id)) {
            Flash::error(
                'No tiene permiso para modificar esta persona.'
            );

            return Redirect::to(
                'personas/ver/' . $persona->id
            );
        }

        if (Input::Post()) {
            $fechaDefuncion = trim(Input::post('fecha_defuncion'));
            $lugarDefuncion = trim(Input::post('lugar_defuncion'));

            if ($fechaDefuncion == '') {
                Flash::error(
                    'Debe indicar la fecha de defunción.'
                );
            } elseif (
                !empty($persona->fecha_nacimiento) &&
                $fechaDefuncion < $persona->fecha_nacimiento
            ) {
                Flash::error(
                    'La fecha de defunción no puede ser anterior ' .
                    'a la fecha de nacimiento.'
                );
            } else {
                $persona->begin();

                try {
                    $persona->fecha_defuncion = $fechaDefuncion;
                    $persona->lugar_defuncion = $lugarDefuncion;
                    $persona->updated_at = date('Y-m-d H:i:s');

                    if (!$persona->save()) {
                        throw new Exception(
                            'No se ha podido guardar la defunción.'
                        );
                    }

                    /*
                     * Una defunción finaliza todas las uniones que
                     * todavía estén abiertas para esta persona.
                     * Las uniones históricas se conservan.
                     */
                    $uniones = new Uniones();

                    $unionesActivas = $uniones->find(
                        "conditions: " .
                        "(persona1_id = " . intval($persona->id) .
                        " OR persona2_id = " . intval($persona->id) . ")" .
                        " AND fecha_fin IS NULL"
                    );

                    foreach ($unionesActivas as $union) {
                        $parejaId =
                            ($union->persona1_id == $persona->id)
                                ? $union->persona2_id
                                : $union->persona1_id;

                        $pareja = (new Personas())->find_first(
                            "conditions: arbol_id = " .
                            intval($arbol->id) .
                            " AND id = " . intval($parejaId)
                        );

                        if (!$pareja) {
                            throw new Exception(
                                'La persona vinculada a una unión no pertenece al árbol actual.'
                            );
                        }

                        $union->fecha_fin = $fechaDefuncion;
                        $union->fin_tipo = 'fallecimiento';

                        if (!$union->save()) {
                            throw new Exception(
                                'No se ha podido finalizar una de las uniones.'
                            );
                        }
                    }

                    $persona->commit();

                    Flash::valid(
                        'La defunción se ha registrado correctamente.'
                    );

                    return Redirect::to(
                        'personas/ver/' . $persona->id
                    );
                } catch (Exception $e) {
                    $persona->rollback();
                    Flash::error($e->getMessage());
                }
            }
        }

        $this->persona = $persona;
    }
}
