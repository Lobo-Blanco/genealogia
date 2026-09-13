<?php

class PersonasFamiliaController extends AppController
{
    public function guardar_hijo_existente($id)
    {
        $arbol = Auth::arbolActual();

        if (!$arbol) {
            Flash::error('No hay un árbol activo.');
            return Redirect::to('personas');
        }

        if (!Auth::estaAutenticado()) {
            Flash::error('Debe iniciar sesión.');
            return Redirect::to('login');
        }

        $personas = new Personas();
        $persona = $personas->find_first(
            "conditions: arbol_id = " . intval($arbol->id) .
            " AND id = " . intval($id)
        );

        if (!$persona) {
            Flash::error('La persona no existe.');
            return Redirect::to('personas');
        }

        if (!Auth::puedeEditar($persona->id)) {
            Flash::error('No tiene permiso para modificar esta persona.');
            return Redirect::to('personas/ver/' . $persona->id);
        }

        $hijoId = intval(Input::post('persona_existente'));
        $hijo = $personas->find_first(
            "conditions: arbol_id = " . intval($arbol->id) .
            " AND id = " . $hijoId
        );

        if (!$hijo || $hijo->id == $persona->id) {
            Flash::error('El hijo seleccionado no es válido.');
            return Redirect::to('personas/buscar_familiar/' . $persona->id . '/hijo');
        }

        $tipo = Input::post('tipo_filiacion');
        $fechaInicio = $this->obtenerFechaInicio($tipo, $hijo->fecha_nacimiento);

        if ($fechaInicio === false) {
            Flash::error('Tipo de filiación no válido.');
            return Redirect::to('personas/buscar_familiar/' . $persona->id . '/hijo');
        }

        $personas->begin();

        try {
            $resultado = Genealogia::crearFiliacion(
                $hijo->id,
                $persona->id,
                $tipo,
                $fechaInicio
            );

            if (!$resultado['ok']) {
                throw new Exception($resultado['mensaje']);
            }

            $this->agregarParejaComoProgenitor(
                $persona,
                $hijo,
                $tipo,
                $fechaInicio
            );

            $personas->commit();

            Flash::valid('Hijo añadido correctamente.');
            return Redirect::to('personas/ver/' . $persona->id);

        } catch (Exception $e) {
            $personas->rollback();
            Flash::error($e->getMessage());
            return Redirect::to('personas/buscar_familiar/' . $persona->id . '/hijo');
        }
    }

    public function guardar_hijo_nuevo($id)
    {
        $arbol = Auth::arbolActual();

        if (!$arbol) {
            Flash::error('No hay un árbol activo.');
            return Redirect::to('personas');
        }

        if (!Auth::estaAutenticado()) {
            Flash::error('Debe iniciar sesión.');
            return Redirect::to('login');
        }

        $persona = (new Personas())->find_first(
            "conditions: arbol_id = " . intval($arbol->id) .
            " AND id = " . intval($id)
        );

        if (!$persona) {
            Flash::error('La persona no existe.');
            return Redirect::to('personas');
        }

        if (!Auth::puedeEditar($persona->id)) {
            Flash::error('No tiene permiso para modificar esta persona.');
            return Redirect::to('personas/ver/' . $persona->id);
        }

        $hijo = new Personas();
        $hijo->nombre = Input::post('nombre');
        $hijo->apellidos = Input::post('apellidos');
        $hijo->sexo = Input::post('sexo');
        $hijo->fecha_nacimiento = Input::post('fecha_nacimiento');
        $hijo->lugar_nacimiento = Input::post('lugar_nacimiento');
        $hijo->notas = Input::post('notas');
        $hijo->created_at = date('Y-m-d H:i:s');
        $hijo->updated_at = date('Y-m-d H:i:s');
        $hijo->arbol_id = $arbol->id;

        if (empty($hijo->nombre) || empty($hijo->apellidos)) {
            Flash::error('Nombre y apellidos son obligatorios.');
            return Redirect::to('personas/nuevo_familiar/' . $persona->id . '/hijo');
        }

        $tipo = Input::post('tipo_filiacion');
        $fechaInicio = $this->obtenerFechaInicio($tipo, $hijo->fecha_nacimiento);

        if ($fechaInicio === false) {
            Flash::error('Tipo de filiación no válido.');
            return Redirect::to('personas/nuevo_familiar/' . $persona->id . '/hijo');
        }

        $hijo->begin();

        try {
            if (!$hijo->save()) {
                throw new Exception('No se ha podido crear el hijo.');
            }

            $resultado = Genealogia::crearFiliacion(
                $hijo->id,
                $persona->id,
                $tipo,
                $fechaInicio
            );

            if (!$resultado['ok']) {
                throw new Exception($resultado['mensaje']);
            }

            $this->agregarParejaComoProgenitor(
                $persona,
                $hijo,
                $tipo,
                $fechaInicio
            );

            $permisos = new UsuariosPersonas();
            if (!$permisos->conceder(Auth::usuario()->id, $hijo->id)) {
                throw new Exception('No se han podido asignar los permisos.');
            }

            $hijo->commit();

            Flash::valid('Hijo creado correctamente.');
            return Redirect::to('personas/ver/' . $persona->id);

        } catch (Exception $e) {
            $hijo->rollback();
            Flash::error($e->getMessage());
            return Redirect::to('personas/nuevo_familiar/' . $persona->id . '/hijo');
        }
    }

    private function obtenerFechaInicio($tipo, $fechaNacimiento)
    {
        if ($tipo == 'biologica') {
            return $fechaNacimiento;
        }

        if ($tipo == 'pre-adoptiva' || $tipo == 'adoptiva') {
            return Input::post('fecha_inicio');
        }

        return false;
    }

    private function agregarParejaComoProgenitor(
        $persona,
        $hijo,
        $tipo,
        $fechaInicio
    ) {
        $union = Genealogia::unionActiva($persona->id);

        if (!$union) {
            return;
        }

        $parejaId =
            $union->persona1_id == $persona->id
                ? $union->persona2_id
                : $union->persona1_id;

        if ($parejaId == $hijo->id) {
            return;
        }

        $filiaciones = new Filiaciones();
        $existente = $filiaciones->find_first(
            "conditions: hijo_id = " . intval($hijo->id) .
            " AND progenitor_id = " . intval($parejaId)
        );

        if ($existente) {
            return;
        }

        $resultado = Genealogia::crearFiliacion(
            $hijo->id,
            $parejaId,
            $tipo,
            $fechaInicio
        );

        if (!$resultado['ok']) {
            throw new Exception(
                'No se ha podido añadir automáticamente a la pareja como progenitor: ' .
                $resultado['mensaje']
            );
        }
    }
}
