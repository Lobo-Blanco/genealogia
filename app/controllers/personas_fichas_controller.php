<?php

class PersonasFichasController extends AppController
{
    public function guardar_nuevo()
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

        $persona = new Personas();
        $persona->nombre = Input::post('nombre');
        $persona->apellidos = Input::post('apellidos');
        $persona->sexo = Input::post('sexo');
        $persona->fecha_nacimiento = Input::post('fecha_nacimiento');
        $persona->lugar_nacimiento = Input::post('lugar_nacimiento');
        $persona->notas = Input::post('notas');
        $persona->created_at = date('Y-m-d H:i:s');
        $persona->updated_at = date('Y-m-d H:i:s');
        $persona->arbol_id = $arbol->id;

        if (empty($persona->nombre) || empty($persona->apellidos)) {
            Flash::error('Nombre y apellidos son obligatorios.');
            return Redirect::to('personas/nuevo');
        }

        $persona->begin();

        try {
            if (!$persona->save()) {
                throw new Exception('No se ha podido crear la persona.');
            }

            $this->guardarProgenitorDesdeFormulario($persona, 'padre', 'H');
            $this->guardarProgenitorDesdeFormulario($persona, 'madre', 'M');

            $permisos = new UsuariosPersonas();
            if (!$permisos->conceder(Auth::usuario()->id, $persona->id)) {
                throw new Exception('No se ha podido asignar la persona al usuario.');
            }

            $persona->commit();

            Flash::valid('Persona creada correctamente.');
            return Redirect::to('personas/ver/' . $persona->id);
        } catch (Exception $e) {
            $persona->rollback();
            Flash::error($e->getMessage());
            return Redirect::to('personas/nuevo');
        }
    }

    public function guardar_editar($id)
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
            'conditions: arbol_id = ' . intval($arbol->id) .
            ' AND id = ' . intval($id)
        );

        if (!$persona) {
            Flash::error('La persona no existe.');
            return Redirect::to('personas');
        }

        if (!Auth::puedeEditar($persona->id)) {
            Flash::error('No tiene permiso para modificar esta persona.');
            return Redirect::to('personas/ver/' . $persona->id);
        }

        $persona->nombre = Input::post('nombre');
        $persona->apellidos = Input::post('apellidos');
        $persona->sexo = Input::post('sexo');
        $persona->fecha_nacimiento = Input::post('fecha_nacimiento');
        $persona->lugar_nacimiento = Input::post('lugar_nacimiento');
        $persona->notas = Input::post('notas');
        $persona->updated_at = date('Y-m-d H:i:s');

        if (empty($persona->nombre) || empty($persona->apellidos)) {
            Flash::error('Nombre y apellidos son obligatorios.');
            return Redirect::to('personas/editar/' . $persona->id);
        }

        /* La defunción queda fuera de esta operación y se conserva intacta. */
        $persona->begin();

        try {
            if (!$persona->save()) {
                throw new Exception('No se ha podido guardar la persona.');
            }

            $this->guardarProgenitorDesdeFormulario($persona, 'padre', 'H');
            $this->guardarProgenitorDesdeFormulario($persona, 'madre', 'M');

            $persona->commit();

            Flash::valid('La persona se ha actualizado correctamente.');
            return Redirect::to('personas/ver/' . $persona->id);
        } catch (Exception $e) {
            $persona->rollback();
            Flash::error($e->getMessage());
            return Redirect::to('personas/editar/' . $persona->id);
        }
    }

    private function guardarProgenitorDesdeFormulario($hijo, $rol, $sexoEsperado)
    {
        $filiaciones = new Filiaciones();
        $existente = null;

        $relaciones = $filiaciones->find(
            'conditions: hijo_id = ' . intval($hijo->id)
        );

        foreach ($relaciones as $relacion) {
            $progenitor = (new Personas())->find_first(
                'conditions: id = ' . intval($relacion->progenitor_id)
            );

            if ($progenitor && $progenitor->sexo == $sexoEsperado) {
                $existente = $progenitor;
                break;
            }
        }

        /* Si ya existe ese progenitor, no lo sustituimos ni duplicamos. */
        if ($existente) {
            return;
        }

        $campoId = $rol . '_existente';
        $modo = Input::post($rol . '_modo');

        if (!$modo || $modo == 'ninguno') {
            return;
        }

        $progenitorId = intval(Input::post($campoId));
        $progenitor = null;

        if ($modo == 'existente') {
            if ($progenitorId <= 0) {
                throw new Exception('Debe seleccionar el progenitor existente.');
            }

            $progenitor = (new Personas())->find_first(
                'conditions: arbol_id = ' . intval($hijo->arbol_id) .
                ' AND id = ' . $progenitorId
            );

            if (!$progenitor) {
                throw new Exception('El progenitor seleccionado no existe en el árbol actual.');
            }

            if ($progenitor->sexo != $sexoEsperado) {
                throw new Exception('El progenitor seleccionado no corresponde al sexo indicado.');
            }
        } elseif ($modo == 'nuevo') {
            $progenitor = new Personas();
            $progenitor->nombre = Input::post($rol . '_nombre');
            $progenitor->apellidos = Input::post($rol . '_apellidos');
            $progenitor->sexo = $sexoEsperado;
            $progenitor->fecha_nacimiento = Input::post($rol . '_fecha_nacimiento');
            $progenitor->lugar_nacimiento = Input::post($rol . '_lugar_nacimiento');
            $progenitor->notas = Input::post($rol . '_notas');
            $progenitor->created_at = date('Y-m-d H:i:s');
            $progenitor->updated_at = date('Y-m-d H:i:s');
            $progenitor->arbol_id = $hijo->arbol_id;

            if (empty($progenitor->nombre) || empty($progenitor->apellidos)) {
                throw new Exception(
                    'Nombre y apellidos son obligatorios para el nuevo ' .
                    ($rol == 'padre' ? 'padre.' : 'madre.')
                );
            }

            if (!$progenitor->save()) {
                throw new Exception('No se ha podido crear el nuevo progenitor.');
            }

            $permisos = new UsuariosPersonas();
            if (!$permisos->conceder(Auth::usuario()->id, $progenitor->id)) {
                throw new Exception('No se ha podido asignar el permiso sobre el nuevo progenitor.');
            }
        } else {
            throw new Exception('Modo de progenitor no válido.');
        }

        if ($progenitor->id == $hijo->id) {
            throw new Exception('Una persona no puede ser progenitor de sí misma.');
        }

        $otroRol = ($rol == 'padre') ? 'madre' : 'padre';
        $otroExistente = intval(Input::post($otroRol . '_existente'));
        if ($otroExistente > 0 && $otroExistente == $progenitor->id) {
            throw new Exception('La misma persona no puede ocupar los dos progenitores.');
        }

        $resultado = Genealogia::crearFiliacion(
            $hijo->id,
            $progenitor->id,
            'biologica',
            $hijo->fecha_nacimiento
        );

        if (!$resultado['ok']) {
            throw new Exception($resultado['mensaje']);
        }
    }
}
