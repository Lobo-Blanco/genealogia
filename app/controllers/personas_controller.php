<?php

class PersonasController extends AppController
{
    /**
     * Lista todas las personas.
     */
    public function index()
    {
        $arbol = Auth::arbolActual();

        if (!$arbol) {
            Flash::error(
                'No hay un árbol activo.'
            );

            return Redirect::to('personas');
        }

        $this->personas = (new Personas)->find(
            "conditions: arbol_id = " . $arbol->id); //, "order: apellidos, nombre" );
    }

    /**
     * Muestra una persona.
     *
     * URL:
     * /personas/ver/8
     */
    public function ver($id)
    {
        $arbol = Auth::arbolActual();

        if (!$arbol) {
            Flash::error(
                'No hay un árbol activo.'
            );

            return Redirect::to('personas');
        }

        $personas = new Personas();

        $persona = $personas->find_first(
            "conditions: arbol_id = " .
            $arbol->id .
            " AND id = " .
            intval($id)
        );

        if (!$persona) {
            Flash::error(
                'La persona no existe en el árbol actual.'
            );

            return Redirect::to('personas');
        }

        $this->persona = $persona;

        $this->progenitores =
            $persona->progenitores();

        $this->hijos =
            $persona->hijos();

        $this->uniones1 =
            $persona->unionesComoPersona1();

        $this->uniones2 =
            $persona->unionesComoPersona2();
    }

    public function editar($id)
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
            Flash::error('La persona no existe.');
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

            /*
            * Conservamos la fecha anterior para saber si
            * realmente se está registrando ahora la defunción.
            */
            $fechaDefuncionAnterior =
                $persona->fecha_defuncion;

            $persona->nombre =
                Input::post('nombre');

            $persona->apellidos =
                Input::post('apellidos');

            $persona->sexo =
                Input::post('sexo');

            $persona->fecha_nacimiento =
                Input::post('fecha_nacimiento');

            $persona->lugar_nacimiento =
                Input::post('lugar_nacimiento');

            $persona->fecha_defuncion =
                Input::post('fecha_defuncion');

            $persona->lugar_defuncion =
                Input::post('lugar_defuncion');

            $persona->notas =
                Input::post('notas');

            $persona->updated_at =
                date('Y-m-d H:i:s');

            /*
            * Sólo consideramos nueva defunción cuando antes
            * no había fecha y ahora sí la hay.
            */
            $nuevaDefuncion =
                empty($fechaDefuncionAnterior) &&
                !empty($persona->fecha_defuncion);

            $persona->begin();

            try {

                /*
                * Guardamos primero la persona.
                */
                if (!$persona->save()) {
                    throw new Exception(
                        'No se ha podido guardar la persona.'
                    );
                }

                /*
                * Si acaba de registrarse la defunción,
                * buscamos un matrimonio activo de esta persona.
                *
                * Las parejas que no sean matrimonio no se
                * modifican por fallecimiento.
                */
                if ($nuevaDefuncion) {

                    $uniones = new Uniones();

                    $union = $uniones->find_first(
                        "conditions: " .
                        "(persona1_id = " . intval($persona->id) .
                        " OR persona2_id = " . intval($persona->id) . ")" .
                        " AND fecha_fin IS NULL"
                    );

                    if ($union) {
                        $parejaId =
                            ($union->persona1_id == $persona->id)
                                ? $union->persona2_id
                                : $union->persona1_id;

                        $pareja = (new Personas())->find_first(
                            "conditions: arbol_id = " .
                            intval($arbol->id) .
                            " AND id = " .
                            intval($parejaId)
                        );

                        if (!$pareja) {
                            throw new Exception(
                                'La persona vinculada a la unmión no pertenece al árbol actual.'
                            );
                        }

                        $union->fecha_fin =
                            $persona->fecha_defuncion;

                        $union->fin_tipo =
                            'fallecimiento';

                        if (!$union->save()) {
                            throw new Exception(
                                'No se ha podido finalizar la unión.'
                            );
                        }
                    }
                }

                /*
                * Todo ha ido correctamente.
                */
                $persona->commit();

                Flash::valid(
                    'La persona se ha actualizado correctamente.'
                );

                return Redirect::to(
                    'personas/ver/' . $persona->id
                );

            } catch (Exception $e) {

                $persona->rollback();

                Flash::error(
                    $e->getMessage()
                );
            }
        }

        $this->persona = $persona;
    }

    public function nuevo()
    {
        // Solamente el administrador puede utilizar
        // de momento el alta general.
        if (!Auth::estaAutenticado()) {

            Flash::error('Debe iniciar sesión.');

            return Redirect::to('login');
        }

        $arbol = Auth::arbolActual();

        if (!$arbol) {
            Flash::error(
                'No hay un árbol activo.'
            );

            return Redirect::to('personas');
        }

        $persona = new Personas();

        if (Input::hasPost('nombre')) {

            $persona->nombre =
                Input::post('nombre');

            $persona->apellidos =
                Input::post('apellidos');

            $persona->sexo =
                Input::post('sexo');

            $persona->fecha_nacimiento =
                Input::post('fecha_nacimiento');

            $persona->lugar_nacimiento =
                Input::post('lugar_nacimiento');

            $persona->fecha_defuncion =
                Input::post('fecha_defuncion');

            $persona->lugar_defuncion =
                Input::post('lugar_defuncion');

            $persona->notas =
                Input::post('notas');

            $persona->created_at =
                date('Y-m-d H:i:s');

            $persona->updated_at =
                date('Y-m-d H:i:s');
            
            $persona->arbol_id = 
                $arbol->id;

            /*
            * Persona y propietario deben crearse
            * como una única operación.
            */
            $persona->begin();

            try {

                if (!$persona->save()) {
                    throw new Exception(
                        'No se ha podido crear la persona.'
                    );
                }

                $usuariosPersonas = new UsuariosPersonas();

                $usuariosPersonas->usuario_id =
                    Auth::usuario()->id;

                $usuariosPersonas->persona_id =
                    $persona->id;

                if (!$usuariosPersonas->save()) {
                    throw new Exception(
                        'No se ha podido asignar la persona al usuario.'
                    );
                }

                $persona->commit();

                Flash::valid(
                    'Persona creada correctamente.'
                );

                return Redirect::to(
                    'personas/ver/' . $persona->id
                );

            } catch (Exception $e) {

                $persona->rollback();

                Flash::error(
                    'No se ha podido crear la persona.'
                );
            }
        }

        $this->persona = $persona;
    }

public function nuevo_progenitor($personaId)
{
    $arbol = Auth::arbolActual();

    if (!$arbol) {
        Flash::error(
            'No hay un árbol activo.'
        );

        return Redirect::to('personas');
    }

    $persona = (new Personas())->find_first(
        "conditions: arbol_id = " .
        intval($arbol->id) .
        " AND id = " .
        intval($personaId)
    );

    if (!$persona) {

        Flash::error(
            'La persona no existe en el árbol actual.'
        );

        return Redirect::to('personas');
    }

    if (!Auth::estaAutenticado()) {

        Flash::error(
            'Debe iniciar sesión.'
        );

        return Redirect::to('login');
    }

    if (!Auth::puedeEditar($persona->id)) {

        Flash::error(
            'No tiene permiso para modificar esta persona.'
        );

        return Redirect::to(
            'personas/ver/' . $persona->id
        );
    }

    $this->persona = $persona;

    $progenitor = new Personas();
    $filiacion = new Filiaciones();

    /*
     * Persona existente.
     */
    if (Input::hasPost('persona_existente')) {
        $progenitorId =
            intval(Input::post('persona_existente'));

        $progenitor = (new Personas())->find_first(
            "conditions: arbol_id = " .
            intval($arbol->id) .
            " AND id = " .
            $progenitorId
        );

        if (!$progenitor) {
            Flash::error(
                'La persona seleccionada no existe en el árbol actual.'
            );
        } else {
            $filiacion->hijo_id =
                $persona->id;

            $filiacion->progenitor_id =
                $progenitor->id;

            $filiacion->tipo =
                Input::post('tipo_filiacion');

            if ($filiacion->tipo == 'biologica') {
                $filiacion->fecha_inicio = $persona->fecha_nacimiento;
            } elseif (
                $filiacion->tipo == 'pre-adoptiva' ||
                $filiacion->tipo == 'adoptiva'
            ) {
                $filiacion->fecha_inicio = Input::post('fecha_inicio');
            } else {
                Flash::error(
                    'Tipo de filiación no válido.'
                );

                $this->filiacion = $filiacion;
                return;
            }

            $filiacion->fecha_fin = null;

            $filiacion->notas =
                Input::post('notas_filiacion');

            if ($filiacion->save()) {

                Flash::valid(
                    'Progenitor añadido correctamente.'
                );

                return Redirect::to(
                    'personas/ver/' .
                    $persona->id
                );
            }

            Flash::error(
                'No se ha podido añadir la filiación.'
            );
        }
    }

    /*
     * Crear una persona nueva.
     */
    elseif (Input::hasPost('nombre')) {
        $progenitor->nombre =
            Input::post('nombre');

        $progenitor->apellidos =
            Input::post('apellidos');

        $progenitor->sexo =
            Input::post('sexo');

        $progenitor->fecha_nacimiento =
            Input::post('fecha_nacimiento');

        $progenitor->lugar_nacimiento =
            Input::post('lugar_nacimiento');

        $progenitor->fecha_defuncion =
            Input::post('fecha_defuncion');

        $progenitor->lugar_defuncion =
            Input::post('lugar_defuncion');

        $progenitor->notas =
            Input::post('notas');

        $progenitor->created_at =
            date('Y-m-d H:i:s');

        $progenitor->updated_at =
            date('Y-m-d H:i:s');

        $progenitor->arbol_id =
            $arbol->id;

        if (
            empty($progenitor->nombre) ||
            empty($progenitor->apellidos)
        ) {
            Flash::error(
                'Nombre y apellidos son obligatorios.'
            );

        } else {
            $filiacion->hijo_id =
                $persona->id;
            $filiacion->tipo =
                Input::post('tipo_filiacion');
            if ($filiacion->tipo == 'biologica') {
                $filiacion->fecha_inicio =
                    $persona->fecha_nacimiento;
            } elseif (
                $filiacion->tipo == 'pre-adoptiva' ||
                $filiacion->tipo == 'adoptiva'
            ) {
                $filiacion->fecha_inicio =
                    Input::post('fecha_inicio');
            } else {
                Flash::error(
                    'Tipo de filiación no válido.'
                );

                $this->progenitor = $progenitor;
                $this->filiacion = $filiacion;
                return;
            }

            $filiacion->fecha_fin = null;

            $filiacion->notas =
                Input::post('notas_filiacion');

            $progenitor->begin();

            try {
                if (!$progenitor->save()) {
                    throw new Exception(
                        'No se ha podido crear el progenitor.'
                    );
                }

                $filiacion->progenitor_id =
                    $progenitor->id;

                if (!$filiacion->save()) {
                    throw new Exception(
                        'No se ha podido crear la filiación.'
                    );
                }

                $usuario = Auth::usuario();

                $permisos =
                    new UsuariosPersonas();

                if (!$permisos->conceder(
                    $usuario->id,
                    $progenitor->id
                )) {
                    throw new Exception(
                        'No se han podido asignar los permisos.'
                    );
                }                    

                $progenitor->commit();

                Flash::valid(
                    'Progenitor añadido correctamente.'
                );

                return Redirect::to(
                    'personas/ver/' .
                    $persona->id
                );
            } catch (Exception $e) {
                $progenitor->rollback();

                Flash::error(
                    $e->getMessage()
                );
            }
        }
    }

    $this->progenitor = $progenitor;
    $this->filiacion = $filiacion;
}

    public function nuevo_hijo($personaId)
    {
        $arbol = Auth::arbolActual();

        if (!$arbol) {
            Flash::error(
                'No hay un árbol activo.'
            );

            return Redirect::to('personas');
        }

        $persona = (new Personas())->find_first(
            "conditions: arbol_id = " .
            intval($arbol->id) .
            " AND id = " .
            intval($personaId)
        );

        if (!$persona) {
            Flash::error('La persona no existe.');
            return Redirect::to('personas');
        }

        if (!Auth::estaAutenticado()) {
            Flash::error('Debe iniciar sesión.');
            return Redirect::to('login');
        }

        if (!Auth::puedeEditar($persona->id)) {
            Flash::error(
                'No tiene permiso para modificar esta persona.'
            );

            return Redirect::to(
                'personas/ver/' . $persona->id
            );
        }

        $hijo = new Personas();
        $filiacion = new Filiaciones();

        if (Input::hasPost('nombre')) {
            $hijo->nombre =
                Input::post('nombre');

            $hijo->apellidos =
                Input::post('apellidos');

            $hijo->sexo =
                Input::post('sexo');

            $hijo->fecha_nacimiento =
                Input::post('fecha_nacimiento');

            $hijo->lugar_nacimiento =
                Input::post('lugar_nacimiento');

            $hijo->fecha_defuncion =
                Input::post('fecha_defuncion');

            $hijo->lugar_defuncion =
                Input::post('lugar_defuncion');

            $hijo->notas =
                Input::post('notas');

            $hijo->created_at =
                date('Y-m-d H:i:s');

            $hijo->updated_at =
                date('Y-m-d H:i:s');
            
            $hijo->arbol_id =
                $arbol->id;
            
            $filiacion->progenitor_id =
                $persona->id;

            $filiacion->tipo =
                Input::post('tipo_filiacion');

            if ($filiacion->tipo == 'biologica') {
                $filiacion->fecha_inicio =
                    $hijo->fecha_nacimiento;
            } elseif (
                $filiacion->tipo == 'pre-adoptiva' ||
                $filiacion->tipo == 'adoptiva'
            ) {
                $filiacion->fecha_inicio =
                    Input::post('fecha_inicio');

            } else {
                Flash::valid(
                    'Tipo de filiación no válido.'
                );

                $this->hijo = $hijo;
                $this->filiacion = $filiacion;
                return;
            }

            $filiacion->fecha_fin = null;

            $filiacion->notas =
                Input::post('notas_filiacion');

            if (
                empty($hijo->nombre) ||
                empty($hijo->apellidos)
            ) {
                Flash::error(
                    'Nombre y apellidos son obligatorios.'
                );
            } else {
                $hijo->begin();

                try {
                    if (!$hijo->save()) {
                        throw new Exception(
                            'No se pudo guardar el hijo.'
                        );
                    }

                    $filiacion->hijo_id = $hijo->id;

                    if (!$filiacion->save()) {
                        throw new Exception(
                            'No se ha podido crear la filiación.'
                        );
                    }

                    $usuario = Auth::usuario();

                    $permisos = new UsuariosPersonas();

                    if (!$permisos->conceder(
                            $usuario->id,
                            $hijo->id
                        )
                    ) {
                        throw new Exception(
                            'No se pudieron asignar los permisos.'
                        );
                    }

                    $hijo->commit();

                    Flash::valid(
                        'Hijo añadido correctamente.'
                    );

                    return Redirect::to(
                        'personas/ver/' .
                        $persona->id
                    );
                } catch (Exception $e) {
                    $hijo->rollback();

                    Flash::error(
                        $e->getMessage()
                    );
                }
            }
        }

        $this->persona = $persona;
        $this->hijo = $hijo;
        $this->filiacion = $filiacion;
    }

    public function buscar()
    {
        $this->personas = array();

        if (!Input::hasPost('texto')) {
            return;
        }

        $texto = trim(Input::post('texto'));

        if ($texto == '') {
            return;
        }

        $texto = addslashes($texto);

        $arbol = Auth::arbolActual();

        if (!$arbol) {
            Flash::error(
                'No hay un árbol activo.'
            );

            return;
        }

        $personas = new Personas();

        $this->personas = $personas->find(
            "conditions: arbol_id = " . $arbol->id .
            " AND (nombre LIKE '%{$texto}%' " .
            "OR apellidos LIKE '%{$texto}%')",
            "order: apellidos, nombre"
        );
    }

    public function nueva_pareja($personaId)
    {
        $arbol = Auth::arbolActual();

        if (!$arbol) {
            Flash::error(
                'No hay un árbol activo.'
            );

            return Redirect::to('personas');
        }

        $persona = (new Personas())->find_first("conditions: id = $personaId and arbol_id = {$arbol->id}");

        if (!$persona) {
            Flash::error('La persona no existe.');
            return Redirect::to('personas');
        }

        if (!Auth::estaAutenticado()) {
            Flash::error('Debe iniciar sesión.');
            return Redirect::to('login');
        }

        if (!Auth::puedeEditar($persona->id)) {
            Flash::error(
                'No tiene permiso para modificar esta persona.'
            );

            return Redirect::to(
                'personas/ver/' . $persona->id
            );
        }

        $personas = new Personas();

        $this->persona = $persona;
        $this->resultados = array();

        /*
        * Persona existente seleccionada.
        */
        $personaSeleccionada = null;

        if (Input::hasPost('crear_persona')) {

            $nueva = new Personas();

            $nueva->nombre =
                Input::post('nombre');

            $nueva->apellidos =
                Input::post('apellidos');

            $nueva->sexo =
                Input::post('sexo');

            $nueva->fecha_nacimiento =
                Input::post('fecha_nacimiento');

            $nueva->lugar_nacimiento =
                Input::post('lugar_nacimiento');

            $nueva->notas =
                Input::post('notas');

            $nueva->created_at =
                date('Y-m-d H:i:s');

            $nueva->updated_at =
                date('Y-m-d H:i:s');

            $nueva->arbol_id =
                $arbol->id;

            if (
                empty($nueva->nombre) ||
                empty($nueva->apellidos)
            ) {

                Flash::error(
                    'Nombre y apellidos son obligatorios.'
                );

            } else {

                /*
                * Persona y unión forman una única operación.
                */
                $nueva->begin();

                try {

                    if (!$nueva->save()) {
                        throw new Exception(
                            'No se ha podido crear la persona.'
                        );
                    }

                    /*
                    * La persona nueva pertenece al usuario
                    * que la ha creado.
                    */
                    $permisos =
                        new UsuariosPersonas();

                    if (!$permisos->conceder(
                        Auth::usuario()->id,
                        $nueva->id
                    )) {
                        throw new Exception(
                            'No se ha podido asignar la persona al usuario.'
                        );
                    }

                    /*
                    * Crear la unión.
                    */
                    $resultado =
                        Genealogia::crearUnion(
                            $persona->id,
                            $nueva->id,
                            Input::post('tipo'),
                            Input::post('fecha_inicio'),
                            Input::post('lugar'),
                            Input::post('notas')
                        );

                    if (!$resultado['ok']) {
                        throw new Exception(
                            $resultado['mensaje']
                        );
                    }

                    $nueva->commit();

                    Flash::valid(
                        'Persona y pareja creadas correctamente.'
                    );

                    return Redirect::to(
                        'personas/ver/' . $persona->id
                    );

                } catch (Exception $e) {

                    $nueva->rollback();

                    Flash::error(
                        $e->getMessage()
                    );
                }
            }
        }

        if (Input::hasPost('buscar')) {

            $texto = trim(Input::post('buscar'));

            $this->resultados =
                $personas->buscarPorNombre(
                    $texto,
                    $persona->id
                );
        }

        if (Input::hasPost('persona_id')) {

            $personaSeleccionada =
                $personas->find_first("conditions: arbol_id = {$arbol->id} AND id = " .
                    Input::post('persona_id')
                );

            if (!$personaSeleccionada) {

                Flash::error(
                    'La persona seleccionada no existe.'
                );

            } else {
                $resultado = Genealogia::crearUnion(
                    $persona->id,
                    Input::post('persona_id'),
                    Input::post('tipo'),
                    Input::post('fecha_inicio'),
                    Input::post('lugar'),
                    Input::post('notas')
                );

                if ($resultado['ok']) {
                    Flash::valid(
                        $resultado['mensaje']
                    );

                    return Redirect::to(
                        'personas/ver/' . $persona->id
                    );
                }

                Flash::error(
                    $resultado['mensaje']
                );
            }

            $this->personaSeleccionada =
                $personaSeleccionada;
        }
    }

public function uniones($id)
{
    $arbol = Auth::arbolActual();

    if (!$arbol) {
        Flash::error(
            'No hay un árbol activo.'
        );

        return Redirect::to('personas');
    }

    $personas = new Personas();
    
    $persona = $personas->find_first("conditions: arbol_id = {$arbol->id} AND id = " . $id);

    if (!$persona) {
        Flash::error('La persona no existe.');
        return Redirect::to('personas/');
    }

    $uniones = new Uniones();

    $lista = $uniones->find(
        "conditions: " .
        "(persona1_id = " . intval($id) .
        " OR persona2_id = " . intval($id) . ")",
        "order: fecha_inicio DESC"
    );

    $this->persona = $persona;
    $this->uniones = $lista;
}    

public function familia($id)
{
    $arbol = Auth::arbolActual();

    if (!$arbol) {
        Flash::error(
            'No hay un árbol activo.'
        );

        return Redirect::to('personas');
    }

    $personas = new Personas();
    
    $persona = $personas->find_first(
        "conditions: arbol_id = {$arbol->id} AND id = " . 
        intval($id)
    );

    if (!$persona) {
        Flash::error(
            'La persona no existe.'
        );

        return Redirect::to(
            'personas/'
        );
    }

    $filiaciones = new Filiaciones();

    /*
     * Progenitores de la persona.
     */
    $progenitores = $filiaciones->find(
        "conditions: hijo_id = " .
        intval($id),
        "order: tipo"
    );

    /*
     * Hijos de la persona.
     */
    $hijos = $filiaciones->find(
        "conditions: progenitor_id = " .
        intval($id),
        "order: tipo"
    );

    $this->persona = $persona;
    $this->progenitores = $progenitores;
    $this->hijos = $hijos;
}

public function buscar_familiar($id, $tipo = 'progenitor')
{
    $arbol = Auth::arbolActual();

    if (!$arbol) {
        Flash::error(
            'No hay un árbol activo.'
        );

        return Redirect::to('personas');
    }

    $personas = new Personas();

    $persona = $personas->find_first("conditions: arbol_id = {$arbol->id} AND id = " . intval($id));

    if (!$persona) {
        Flash::error('La persona no existe.');

        return Redirect::to('personas/');
    }

    if (!in_array($tipo, array('progenitor', 'hijo'))) {
        Flash::error('Tipo de familiar no válido.');

        return Redirect::to(
            'personas/familia/' . $persona->id
        );
    }

    $this->persona = $persona;
    $this->tipo = $tipo;
    $this->resultados = array();

    /*
     * Buscar personas existentes.
     */
    if (Input::hasPost('buscar')) {

        $texto = trim(
            Input::post('buscar')
        );

        if ($texto != '') {

            $this->resultados =
                $personas->buscarPorNombre(
                    $texto,
                    $persona->id
                );
        }
    }
}

public function agregar_progenitor(
    $id,
    $progenitorId,
    $tipo = 'biologica'
) {

    $arbol = Auth::arbolActual();

    if (!$arbol) {
        Flash::error(
            'No hay un árbol activo.'
        );

        return Redirect::to('personas');
    }

    $persona = (new Personas())->find_first("conditions: arbol_id = {$arbol->id} AND id = " .
        intval($id)
    );

    if (!$persona) {
        Flash::error('La persona no existe.');

        return Redirect::to('personas');
    }

    if (!Auth::estaAutenticado()) {
        Flash::error('Debe iniciar sesión.');

        return Redirect::to('login');
    }

    if (!Auth::puedeEditar($persona->id)) {
        Flash::error(
            'No tiene permiso para modificar esta persona.'
        );

        return Redirect::to(
            'personas/ver/' . $persona->id
        );
    }

    $resultado =
        Genealogia::crearFiliacion(
            $persona->id,
            intval($progenitorId),
            $tipo
        );

    if ($resultado['ok']) {
        Flash::valid($resultado['mensaje']);
    } else {
        Flash::error($resultado['mensaje']);
    }

    return Redirect::to(
        'personas/familia/' . $persona->id
    );
}

public function agregar_hijo(
    $id,
    $hijoId,
    $tipo = 'biologica'
) {
    $arbol = Auth::arbolActual();

    if (!$arbol) {
        Flash::error(
            'No hay un árbol activo.'
        );

        return Redirect::to('personas');
    }

    $persona = (new Personas())->find_first("conditions: arbol_id = {$arbol->id} AND id = " .
        intval($id)
    );

    if (!$persona) {
        Flash::error('La persona no existe.');

        return Redirect::to('personas');
    }

    if (!Auth::estaAutenticado()) {
        Flash::error('Debe iniciar sesión.');

        return Redirect::to('login');
    }

    if (!Auth::puedeEditar($persona->id)) {
        Flash::error(
            'No tiene permiso para modificar esta persona.'
        );

        return Redirect::to(
            'personas/ver/' . $persona->id
        );
    }

    $resultado =
        Genealogia::crearFiliacion(
            intval($hijoId),
            $persona->id,
            $tipo
        );

    if ($resultado['ok']) {
        Flash::valid($resultado['mensaje']);
    } else {
        Flash::error($resultado['mensaje']);
    }

    return Redirect::to(
        'personas/familia/' . $persona->id
    );
}

public function nuevo_familiar($id, $tipo)
{
    $arbol = Auth::arbolActual();

    if (!$arbol) {
        Flash::error(
            'No hay un árbol activo.'
        );

        return Redirect::to('personas');
    }

    $personas = new Personas();

    $persona = $personas->find_first("conditions: arbol_id = {$arbol->id} AND id = " . 
        intval($id)
    );

    if (!$persona) {
        Flash::error('La persona no existe.');

        return Redirect::to('personas/');
    }

    if (!in_array(
        $tipo,
        array('progenitor', 'hijo')
    )) {
        Flash::error(
            'Tipo de familiar no válido.'
        );

        return Redirect::to(
            'personas/familia/' . $persona->id
        );
    }

    $this->persona = $persona;
    $this->tipo = $tipo;
}

public function guardar_familiar($id, $tipo)
{
    $arbol = Auth::arbolActual();

    if (!$arbol) {
        Flash::error(
            'No hay un árbol activo.'
        );

        return Redirect::to('personas');
    }

    $personas = new Personas();

    $persona = $personas->find_first(
        "conditions: arbol_id = " .
        intval($arbol->id) .
        " AND id = " .
        intval($id)
    );

    if (!$persona) {
        Flash::error(
            'La persona no existe.'
        );

        return Redirect::to('personas/');
    }

    if (!in_array(
        $tipo,
        array('progenitor', 'hijo')
    )) {
        Flash::error(
            'Tipo de familiar no válido.'
        );

        return Redirect::to(
            'personas/familia/' . $persona->id
        );
    }

    /*
     * Si se ha seleccionado una persona existente,
     * utilizamos esa persona.
     */
    $familiarId =
        intval(Input::post('persona_existente'));

    if ($familiarId > 0) {
        $familiar = $personas->find_first(
            "conditions: arbol_id = " .
            intval($arbol->id) .
            " AND id = " .
            $familiarId
        );

        if (!$familiar) {
            Flash::error(
                'La persona seleccionada no existe ' .
                'en el árbol actual.'
            );

            return Redirect::to(
                'personas/nuevo_familiar/' .
                $persona->id . '/' . $tipo
            );
        }

        /*
         * No concedemos UsuariosPersonas aquí.
         *
         * La persona ya existe y pertenece a otro usuario
         * o puede ser una persona que ya tenga propietario.
         *
         * Lo que estamos creando es una nueva filiación,
         * cuyo propietario será el usuario actual.
         */
        if ($tipo == 'progenitor') {
            $tipoFiliacion = 'biologica';

            $fecha_inicio =
                $persona->fecha_nacimiento;
        } else {
            $tipoFiliacion =
                Input::post('tipo_filiacion');

            if ($tipoFiliacion == 'biologica') {
                $fecha_inicio =
                    $familiar->fecha_nacimiento;
            } elseif (
                $tipoFiliacion == 'adoptiva' ||
                $tipoFiliacion == 'pre-adoptiva'
            ) {
                $fecha_inicio =
                    Input::post('fecha_inicio');

            } else {
                Flash::valid(
                    'Tipo de filiación no válido.'
                );
            }
        }

        $personas->begin();

        try {
            if ($tipo == 'progenitor') {
                $resultado =
                    Genealogia::crearFiliacion(
                        $persona->id,
                        $familiar->id,
                        $tipoFiliacion,
                        $fecha_inicio
                    );
            } else {
                $resultado =
                    Genealogia::crearFiliacion(
                        $familiar->id,
                        $persona->id,
                        $tipoFiliacion,
                        $fecha_inicio
                    );
            }

            if (!$resultado['ok']) {
                throw new Exception(
                    $resultado['mensaje']
                );
            }

            $personas->commit();

            Flash::valid(
                'Filiación registrada correctamente.'
            );

            return Redirect::to(
                'personas/familia/' .
                $persona->id
            );

        } catch (Exception $e) {
            $personas->rollback();

            Flash::error(
                $e->getMessage()
            );

            return Redirect::to(
                'personas/nuevo_familiar/' .
                $persona->id . '/' . $tipo
            );
        }
    }

    /*
     * No se ha seleccionado una persona existente.
     * Creamos una persona nueva.
     */
    $nueva = new Personas();

    $nueva->nombre =
        Input::post('nombre');

    $nueva->apellidos =
        Input::post('apellidos');

    $nueva->sexo =
        Input::post('sexo');

    $nueva->fecha_nacimiento =
        Input::post('fecha_nacimiento');

    $nueva->lugar_nacimiento =
        Input::post('lugar_nacimiento');

    $nueva->notas =
        Input::post('notas');

    $nueva->arbol_id =
        $arbol->id;

    /*
     * Transacción
     */
    $personas->begin();

    try {
        if (!$nueva->save()) {
            throw new Exception(
                'No se ha podido crear la persona.'
            );
        }

        if ($tipo == 'progenitor') {
            $tipoFiliacion = 'biologica';

            $fecha_inicio =
                $persona->fecha_nacimiento;
        } else {
            $tipoFiliacion =
                Input::post('tipo_filiacion');

            if ($tipoFiliacion == 'biologica') {
                $fecha_inicio =
                    $nueva->fecha_nacimiento;
            } elseif (
                $tipoFiliacion == 'adoptiva' ||
                $tipoFiliacion == 'pre-adoptiva'
            ) {
                $fecha_inicio =
                    Input::post('fecha_inicio');

            } else {
                Flash::valid(
                    'Tipo de filiación no válido.'
                );

                return Redirect::to(
                    'personas/familia/' .
                    $persona->id
                );
            }
        }

        if ($tipo == 'progenitor') {
            $resultado =
                Genealogia::crearFiliacion(
                    $persona->id,
                    $nueva->id,
                    $tipoFiliacion,
                    $fecha_inicio
                );
        } else {
            $resultado =
                Genealogia::crearFiliacion(
                    $nueva->id,
                    $persona->id,
                    $tipoFiliacion,
                    $fecha_inicio
                );
        }

        if (!$resultado['ok']) {
            throw new Exception(
                $resultado['mensaje']
            );
        }

        /*
         * La persona nueva pertenece al usuario actual.
         */
        $usuario = Auth::usuario();

        $permisos =
            new UsuariosPersonas();

        if (!$permisos->conceder(
            $usuario->id,
            $nueva->id
        )) {
            throw new Exception(
                'No se ha podido asignar el permiso ' .
                'sobre la nueva persona.'
            );
        }

        $personas->commit();

        Flash::valid(
            'Persona creada y filiación registrada correctamente.'
        );

        return Redirect::to(
            'personas/familia/' .
            $persona->id
        );

    } catch (Exception $e) {

        $personas->rollback();

        Flash::error(
            $e->getMessage()
        );

        return Redirect::to(
            'personas/nuevo_familiar/' .
            $persona->id . '/' . $tipo
        );
    }
}

public function disolver_pareja($unionId, $origenId)
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

    $uniones = new Uniones();

    $union = $uniones->find_first(
        "conditions: id = " . intval($unionId)
    );

    if (!$union) {
        Flash::error(
            'La unión no existe.'
        );

        return Redirect::to('personas');
    }

    $persona1 = (new Personas())->find_first(
        "conditions: arbol_id = " .
        intval($arbol->id) .
        " AND id = " .
        intval($union->persona1_id)
    );

    $persona2 = (new Personas())->find_first(
        "conditions: arbol_id = " .
        intval($arbol->id) .
        " AND id = " .
        intval($union->persona2_id)
    );

    if (!$persona1 || !$persona2) {
        Flash::error(
            'La unión no pertenece al árbol actual.'
        );

        return Redirect::to(
            'personas/ver/' . $origenId
        );
    }

    if (!Auth::puedeEditarUnion($union->id)) {
        Flash::error(
            'No tiene permiso para modificar esta unión.'
        );

        return Redirect::to(
            'personas/ver/' . $origenId
        );
    }

    if (Input::hasPost('fecha')) {

        $resultado =
            Genealogia::disolverPareja(
                $union->id,
                Input::post('fecha'),
                Input::post('lugar'),
                Input::post('notas')
            );

        if ($resultado['ok']) {

            Flash::valid(
                $resultado['mensaje']
            );

            return Redirect::to(
                'personas/uniones/' .
                $origenId
            );
        }

        Flash::error(
            $resultado['mensaje']
        );
    }

    $this->union = $union;
}

public function divorciar($unionId, $origenId)
{
    $arbol = Auth::arbolActual();

    if (!$arbol) {
        Flash::error(
            'No hay un árbol activo.'
        );

        return Redirect::to('personas');
    }

    if (!Auth::estaAutenticado()) {
        Flash::error(
            'Debe iniciar sesión.'
        );

        return Redirect::to('login');
    }

    $uniones = new Uniones();

    $union = $uniones->find_first(
        "conditions: id = " . intval($unionId)
    );

    if (!$union) {
        Flash::error(
            'La unión no existe.'
        );

        return Redirect::to('personas');
    }

    $persona1 = (new Personas())->find_first(
        "conditions: arbol_id = " .
        intval($arbol->id) .
        " AND id = " .
        intval($union->persona1_id)
    );

    $persona2 = (new Personas())->find_first(
        "conditions: arbol_id = " .
        intval($arbol->id) .
        " AND id = " .
        intval($union->persona2_id)
    );

    if (!$persona1 || !$persona2) {
        Flash::error(
            'La unión no pertenece al árbol actual.'
        );

        return Redirect::to(
            'personas/ver/' . $origenId
        );
    }

    if (!Auth::puedeEditarUnion($union->id)) {
        Flash::error(
            'No tiene permiso para modificar esta unión.'
        );

        return Redirect::to(
            'personas/ver/' . $origenId
        );
    }

    if (Input::hasPost('fecha')) {
        $resultado =
            Genealogia::registrarDivorcio(
                $union->id,
                Input::post('fecha'),
                Input::post('lugar'),
                Input::post('notas')
            );

        if ($resultado['ok']) {

            Flash::valid(
                $resultado['mensaje']
            );

            return Redirect::to(
                'personas/uniones/' .
                $origenId
            );
        }

        Flash::error(
            $resultado['mensaje']
        );
    }

    $this->union = $union;
}

public function editar_union($id, $origenId)
{
    if (!Auth::estaAutenticado()) {
        Flash::error(
            'Debe iniciar sesión.'
        );

        return Redirect::to('login');
    }

    $arbol = Auth::arbolActual();

    if (!$arbol) {
        Flash::error(
            'No hay un árbol activo.'
        );

        return Redirect::to('personas');
    }

    $uniones = new Uniones();

    $union = $uniones->find_first(
        "conditions: id = " . intval($id)
    );

    if (!$union) {
        Flash::error(
            'La unión no existe.'
        );

        return Redirect::to('personas');
    }

    if (!Auth::puedeEditarUnion($union->id)) {
        Flash::error(
            'No tiene permiso para modificar esta unión.'
        );

        return Redirect::to(
            'personas/uniones/' . $origenId
        );
    }

    /*
     * Comprobamos que las dos personas de la unión
     * pertenecen al árbol actual.
     */
    $personas = new Personas();

    $persona1 = $personas->find_first(
        "conditions: arbol_id = " .
        $arbol->id .
        " AND id = " .
        intval($union->persona1_id)
    );

    $persona2 = $personas->find_first(
        "conditions: arbol_id = " .
        $arbol->id .
        " AND id = " .
        intval($union->persona2_id)
    );

    if (!$persona1 || !$persona2) {
        Flash::error(
            'La unión no pertenece al árbol actual.'
        );

        return Redirect::to('personas');
    }

    if (Input::hasPost('fecha_inicio')) {
        $union->fecha_inicio =
            Input::post('fecha_inicio');

        $union->lugar =
            Input::post('lugar');

        $union->notas =
            Input::post('notas');

        if ($union->save()) {

            Flash::valid(
                'La unión se ha actualizado correctamente.'
            );

            return Redirect::to(
                'personas/uniones/' .
                $origenId
            );
        }

        Flash::error(
            'No se ha podido actualizar la unión.'
        );
    }

    $this->union = $union;
    $this->persona1 = $persona1;
    $this->persona2 = $persona2;
}

public function eliminar_filiacion($id, $origenId)
{
    if (!Auth::estaAutenticado()) {
        Flash::error(
            'Debe iniciar sesión.'
        );

        return Redirect::to('login');
    }

    $arbol = Auth::arbolActual();

    if (!$arbol) {
        Flash::error(
            'No hay un árbol activo.'
        );

        return Redirect::to('personas/familia/' . $origenId);
    }

    $filiaciones = new Filiaciones();

    $filiacion = $filiaciones->find_first(
        "conditions: id = " .
        intval($id)
    );

    if (!$filiacion) {
        Flash::error(
            'La filiación no existe.'
        );

        return Redirect::to('personas/familia/' . $origenId);
    }

    if (!Auth::puedeEditarFiliacion(
        $filiacion->id
    )) {

        Flash::error(
            'No tiene permiso para eliminar esta filiación.'
        );

        return Redirect::to(
            'personas/ver/' . $origenId
        );
    }

    /*
     * Comprobamos que el hijo pertenece
     * al árbol actual.
     */
    $personas = new Personas();

    $hijo = $personas->find_first(
        "conditions: arbol_id = " .
        $arbol->id .
        " AND id = " .
        intval($filiacion->hijo_id)
    );

    if (!$hijo) {
        Flash::error(
            'La filiación no pertenece al árbol actual.'
        );

        return Redirect::to('personas/familia/' . $origenId);
    }

    /*
     * También comprobamos el progenitor.
     */
    $progenitor = $personas->find_first(
        "conditions: arbol_id = " .
        $arbol->id .
        " AND id = " .
        intval($filiacion->progenitor_id)
    );

    if (!$progenitor) {
        Flash::error(
            'El progenitor no pertenece al árbol actual.'
        );

        return Redirect::to('personas/familia/' . $origenId);
    }

    if ($filiacion->delete()) {
        Flash::valid(
            'La filiación se ha eliminado correctamente.'
        );

        return Redirect::to(
            'personas/familia/' . $origenId
        );
    }

    Flash::error(
        'No se ha podido eliminar la filiación.'
    );

    return Redirect::to(
        'personas/familia/' . $origenId
    );
}

public function editar_filiacion($id, $origenId)
{
    if (!Auth::estaAutenticado()) {
        Flash::error(
            'Debe iniciar sesión.'
        );

        return Redirect::to('login');
    }

    $arbol = Auth::arbolActual();

    if (!$arbol) {
        Flash::error(
            'No hay un árbol activo.'
        );

        return Redirect::to('personas');
    }

    $filiaciones = new Filiaciones();

    $filiacion = $filiaciones->find_first(
        "conditions: id = " . intval($id)
    );

    if (!$filiacion) {
        Flash::error(
            'La filiación no existe.'
        );

        return Redirect::to("personas/familia/$origenId");
    }

    if (!Auth::puedeEditarFiliacion(
        $filiacion->id
    )) {
        Flash::error(
            'No tiene permiso para modificar esta filiación.'
        );

        return Redirect::to(
            "personas/familia/$origenId"
        );
    }

    $personas = new Personas();

    /*
     * Comprobamos que el hijo pertenece
     * al árbol actual.
     */
    $hijo = $personas->find_first(
        "conditions: arbol_id = " .
        intval($arbol->id) .
        " AND id = " .
        intval($filiacion->hijo_id)
    );

    /*
     * Comprobamos que el progenitor pertenece
     * al árbol actual.
     */
    $progenitor = $personas->find_first(
        "conditions: arbol_id = " .
        intval($arbol->id) .
        " AND id = " .
        intval($filiacion->progenitor_id)
    );

    if (!$hijo || !$progenitor) {
        Flash::error(
            'La filiación no pertenece al árbol actual.'
        );

        return Redirect::to("personas/familia/$origenId");
    }

    if (Input::hasPost('tipo_filiacion')) {
        $tipo = Input::post('tipo_filiacion');

        if (
            $tipo != 'biologica' &&
            $tipo != 'pre-adoptiva' &&
            $tipo != 'adoptiva'
        ) {
            Flash::error(
                'El tipo de filiación no es válido.'
            );

        } else {
            $filiacion->tipo = $tipo;

            if ($filiacion->save()) {

                Flash::valid(
                    'La filiación se ha actualizado correctamente.'
                );

                return Redirect::to(
                    "personas/familia/$origenId"
                );
            }

            Flash::error(
                'No se ha podido actualizar la filiación.'
            );
        }
    }

    $this->filiacion = $filiacion;
    $this->hijo = $hijo;
    $this->progenitor = $progenitor;
    $this->origenId = $origenId;
}

public function arbol($id)
{
    $arbol = Auth::arbolActual();

    if (!$arbol) {
        Flash::error(
            'No hay un árbol activo.'
        );

        return Redirect::to('personas');
    }

    $personas = new Personas();

    $persona = $personas->find_first(
        "conditions: arbol_id = " .
        intval($arbol->id) .
        " AND id = " .
        intval($id)
    );

    if (!$persona) {
        Flash::error(
            'La persona no existe.'
        );

        return Redirect::to('personas/');
    }

    /*
     * Obtenemos todas las personas del árbol actual. Las tablas
     * de filiaciones y uniones relacionan personas, por lo que
     * no necesitan tener arbol_id para poder limitar los datos.
     */
    $personasArbol = $personas->find(
        "conditions: arbol_id = " . intval($arbol->id)
    );

    $idsPersonas = array();

    foreach ($personasArbol as $personaArbol) {
        $idsPersonas[] = intval($personaArbol->id);
    }

    $ids = implode(',', $idsPersonas);

    $filiaciones = new Filiaciones();
    $todasFiliaciones = array();

    if ($ids !== '') {
        $todasFiliaciones = $filiaciones->find(
            "conditions: hijo_id IN ({$ids}) " .
            "AND progenitor_id IN ({$ids})"
        );
    }

    /*
     * Todas las uniones que pertenecen al árbol actual.
     */
    $unionesModelo = new Uniones();
    $uniones = array();

    if ($ids !== '') {
        $uniones = $unionesModelo->find(
            "conditions: persona1_id IN ({$ids}) " .
            "AND persona2_id IN ({$ids})",
            "order: fecha_inicio"
        );
    }

    /*
     * Índice rápido de personas.
     */
    $personasPorId = array();

    foreach ($personasArbol as $personaArbol) {
        $personasPorId[(string) $personaArbol->id] = $personaArbol;
    }

    /*
     * Índice de progenitores por hijo.
     */
    $progenitoresPorHijo = array();

    foreach ($todasFiliaciones as $filiacion) {
        $hijoId = (string) $filiacion->hijo_id;
        $progenitoresPorHijo[$hijoId][] = $filiacion;
    }

    /*
     * Construimos el formato que espera js_family_tree:
     *
     * persons -> personas
     * unions  -> parejas/uniones
     * links   -> conexiones persona/unión
     *
     * La biblioteca está diseñada expresamente para este modelo
     * de persona + unión + enlaces.
     */
    $datosPersonas = array();
    $datosUniones = array();
    $links = array();

    foreach ($personasArbol as $personaArbol) {
        $personaId = "id{$personaArbol->id}";
        $datosPersonas[$personaId] = array(
            'id' => $personaId,
            'name' => trim(
                $personaArbol->nombre . ' ' .
                $personaArbol->apellidos
            ),
            'birthyear' => !empty($personaArbol->fecha_nacimiento)
                ? intval(substr($personaArbol->fecha_nacimiento, 0, 4))
                : null,
            'deathyear' => !empty($personaArbol->fecha_defuncion)
                ? intval(substr($personaArbol->fecha_defuncion, 0, 4))
                : null,
            'birthplace' => $personaArbol->lugar_nacimiento,
            'deathplace' => $personaArbol->lugar_defuncion,
            //'own_unions' => array()
        );
    }

    foreach ($uniones as $union) {
        $unionId = 'u' . intval($union->id);
        $persona1Id = (string) $union->persona1_id;
        $persona2Id = (string) $union->persona2_id;

        $datosUniones[$unionId] = array(
            'id' => $unionId,
            'partner' => array("id{$persona1Id}", "id{$persona2Id}"),
            'children' => array(),
            //'tipo' => $union->tipo,
            //'fecha_inicio' => $union->fecha_inicio,
            //'fecha_fin' => $union->fecha_fin,
            //'lugar' => $union->lugar
        );
        //if (isset($datosPersonas["id{$persona1Id}"])) {
        //    $datosPersonas["id{$persona1Id}"]['own_unions'][] = $unionId;
        //}

        //if (isset($datosPersonas["id{$persona2Id}"])) {
        //    $datosPersonas["id{$persona2Id}"]['own_unions'][] = $unionId;
        //}

        $links[] = array("id{$persona1Id}", $unionId);
        $links[] = array("id{$persona2Id}", $unionId);
    }

    /*
     * Intentamos asociar cada hijo a la unión correspondiente.
     * Si los dos progenitores registrados son los miembros de una
     * unión, esa unión pasa a ser su parent_union.
     */
    foreach ($progenitoresPorHijo as $hijoId => $filiacionesHijo) {
        $progenitorIds = array();

        foreach ($filiacionesHijo as $filiacion) {
            $progenitorIds[] = (string) "id{$filiacion->progenitor_id}";
        }

        $found = false;
        foreach ($datosUniones as $unionId => &$datosUnion) {
            $partners = $datosUnion['partner'];
            $coinciden = 0;

            foreach ($partners as $partnerId) {
                if (in_array($partnerId, $progenitorIds, true)) {
                   $coinciden++;
                }
            }

            if ($coinciden === count($partners)) {
                $datosUnion['children'][] = "id{$hijoId}";

                switch ($union->tipo) {
                    case 'pareja':
                        if ($union->fin_tipo === "disolucion") {
                            $datosUnion['status'] = "broken_up";
                            break;
                        }
                        $datosUnion['status'] = "couple";
                        break;
                    
                    case 'matrimonio':
                        if ($union->fin_tipo === "divorcio") {
                            $datosUnion['status'] = "divorced";
                            break;
                        }
                        $datosUnion['status'] = "married";
                        break;
                }

                //if (isset($datosPersonas["id{$hijoId}"])) {
                //    $datosPersonas["id{$hijoId}"]['parent_union'] = $unionId;
                //}

                $links[] = array($unionId, "id{$hijoId}");
                $found = true;
                break;
            } elseif ($coinciden === 1) {
                $this->anadirUnion($progenitorIds, $datosUniones, $links, $hijoId);
                $datosUnion['status'] = "single";
                $found = true;
                break;
           }
        }

        if (!$found) {
            $this->anadirUnion($progenitorIds, $datosUniones, $links, $hijoId);
        }

        unset($datosUnion);
    }

    $datos = array(
        'start' => "id" . (string) $persona->id,
        'persons' => $datosPersonas,
        'unions' => $datosUniones,
        'links' => $links
    );

    $this->persona = $persona;
    $this->datosArbol = $datos;
}

private function anadirUnion($progenitorIds, &$datosUniones, &$links, $hijoId) {
    $unionId = null;
    $progenitorId1 = $progenitorIds[0];
    if (isset($progenitorIds[1])) {
        $progenitorId2 = $progenitorIds[1];
    }

    foreach ($datosUniones as $key => $union) {
        // Acceder a name[0] porque es un array
        if (in_array($progenitorId1, $union['partner'])) {
            if (isset($progenitorId2) && in_array($progenitorId2, $union['partner'])) {
                $unionId = $key;
                break;
            }
        }
    }

    if ($unionId === null) {
        $unionId = array_key_last($datosUniones);

        if ($unionId === null) {
            $unionId = "u1";
        } else {
            $numero = (int) str_replace('u', '', $unionId);
            $unionId = 'u' . ($numero + 1);
        }

        $datosUniones[$unionId] = [
            "id" => $unionId,
            "partner" => [],
            'children'=> []
        ];
        //$datosPersonas[$progenitorId]['own_unions'] = [$unionId];
        $links[] = array($progenitorId1, $unionId);
        if (isset($progenitorId2)) {
            $links[] = array($progenitorId2, $unionId);
        }
    }

    $partner[] = $progenitorId1;
    if (isset($progenitorId2)) {
        $partner[] = $progenitorId2;
    }

    $datosUniones[$unionId]['children'][] = "id{$hijoId}";
    $datosUniones[$unionId]['partner'] = $partner;
    //if (isset($datosPersonas["id{$hijoId}"])) {
    //    $datosPersonas["id{$hijoId}"]['parent_union'] = $unionId;
    //}
    $links[] = array($unionId, "id{$hijoId}");
}

public function finalizar_pre_adopcion($filiacionId)
{
    if (!Auth::estaAutenticado()) {
        Flash::error(
            'Debe iniciar sesión.'
        );

        return Redirect::to('login');
    }

    $arbol = Auth::arbolActual();

    if (!$arbol) {
        Flash::error(
            'No hay un árbol activo.'
        );

        return Redirect::to('personas');
    }

    $filiaciones = new Filiaciones();

    $filiacion = $filiaciones->find_first(
        "conditions: id = " . intval($filiacionId)
    );

    if (!$filiacion) {
        Flash::error(
            'La filiación no existe.'
        );

        return Redirect::to('personas');
    }

    /*
     * Sólo se puede finalizar una pre-adopción
     * que todavía esté abierta.
     */
    if ($filiacion->tipo != 'pre-adoptiva') {
        Flash::error(
            'La filiación no es pre-adoptiva.'
        );

        return Redirect::to(
            'personas/familia/' .
            intval($filiacion->hijo_id)
        );
    }

    if (!empty($filiacion->fecha_fin)) {
        Flash::error(
            'La pre-adopción ya está finalizada.'
        );

        return Redirect::to(
            'personas/familia/' .
            intval($filiacion->hijo_id)
        );
    }

    $personas = new Personas();

    /*
     * Comprobamos ambos extremos dentro del árbol actual.
     */
    $hijo = $personas->find_first(
        "conditions: arbol_id = " .
        intval($arbol->id) .
        " AND id = " .
        intval($filiacion->hijo_id)
    );

    $progenitor = $personas->find_first(
        "conditions: arbol_id = " .
        intval($arbol->id) .
        " AND id = " .
        intval($filiacion->progenitor_id)
    );

    if (!$hijo || !$progenitor) {
        Flash::error(
            'La filiación no pertenece al árbol activo.'
        );

        return Redirect::to('personas');
    }

    /*
     * La persona que inicia la operación debe poder editar
     * al hijo, igual que en el resto de operaciones sobre
     * su familia.
     */
    if (!Auth::puedeEditar($hijo->id)) {
        Flash::error(
            'No tiene permiso para modificar esta filiación.'
        );

        return Redirect::to(
            'personas/ver/' . $hijo->id
        );
    }

    /*
     * GET: mostramos el formulario.
     */
    if (!Input::Post()) {

        $this->filiacion = $filiacion;
        $this->hijo = $hijo;
        $this->progenitor = $progenitor;
        $this->origenId = $hijo->id;

        return;
    }

    /*
     * POST: recibimos la fecha de finalización.
     */
    $fechaFin = Input::post('fecha_fin');

    if (empty($fechaFin)) {
        Flash::error(
            'Debe indicar la fecha de finalización.'
        );

        return Redirect::to(
            'personas/finalizar_pre_adopcion/' .
            intval($filiacionId)
        );
    }

    /*
     * La finalización nunca puede ser anterior
     * al comienzo de la pre-adopción.
     */
    if (
        !empty($filiacion->fecha_inicio) &&
        $fechaFin < $filiacion->fecha_inicio
    ) {
        Flash::error(
            'La fecha de finalización no puede ser anterior ' .
            'a la fecha de inicio.'
        );

        return Redirect::to(
            'personas/finalizar_pre_adopcion/' .
            intval($filiacionId)
        );
    }

    /*
     * Guardamos únicamente la fecha de finalización.
     * El registro histórico se conserva.
     */
    $filiacion->fecha_fin = $fechaFin;

    if (!$filiacion->save()) {
        Flash::error(
            'No se ha podido finalizar la pre-adopción.'
        );

        return Redirect::to(
            'personas/familia/' . $hijo->id
        );
    }

    Flash::valid(
        'Pre-adopción finalizada correctamente.'
    );

    return Redirect::to(
        'personas/familia/' . $hijo->id
    );
}

public function formalizar_adopcion($filiacionId)
{
    if (!Auth::estaAutenticado()) {
        Flash::error(
            'Debe iniciar sesión.'
        );

        return Redirect::to('login');
    }

    $arbol = Auth::arbolActual();

    if (!$arbol) {
        Flash::error(
            'No hay un árbol activo.'
        );

        return Redirect::to('personas');
    }

    $filiaciones = new Filiaciones();

    $filiacion = $filiaciones->find_first(
        "conditions: id = " .
        intval($filiacionId)
    );

    if (!$filiacion) {
        Flash::error(
            'La filiación no existe.'
        );

        return Redirect::to('personas');
    }

    if ($filiacion->tipo != 'pre-adoptiva') {
        Flash::error(
            'La filiación no es pre-adoptiva.'
        );

        return Redirect::to(
            'personas/familia/' .
            intval($filiacion->hijo_id)
        );
    }

    if (!empty($filiacion->fecha_fin)) {
        Flash::error(
            'La pre-adopción ya ha finalizado.'
        );

        return Redirect::to(
            'personas/familia/' .
            intval($filiacion->hijo_id)
        );
    }

    $personas = new Personas();

    $hijo = $personas->find_first(
        "conditions: arbol_id = " .
        intval($arbol->id) .
        " AND id = " .
        intval($filiacion->hijo_id)
    );

    $progenitor = $personas->find_first(
        "conditions: arbol_id = " .
        intval($arbol->id) .
        " AND id = " .
        intval($filiacion->progenitor_id)
    );

    if (!$hijo || !$progenitor) {
        Flash::error(
            'La filiación no pertenece al árbol activo.'
        );

        return Redirect::to('personas');
    }

    if (!Auth::puedeEditar($hijo->id)) {
        Flash::error(
            'No tiene permiso para modificar esta filiación.'
        );

        return Redirect::to(
            'personas/ver/' . $hijo->id
        );
    }

    /*
     * GET: mostrar formulario.
     */
    if (!Input::Post()) {

        $this->filiacion = $filiacion;
        $this->hijo = $hijo;
        $this->progenitor = $progenitor;

        return;
    }

    $fechaAdopcion =
        Input::post('fecha_adopcion');

    if (empty($fechaAdopcion)) {
        Flash::error(
            'Debe indicar la fecha de adopción.'
        );

        return Redirect::to(
            'personas/formalizar_adopcion/' .
            intval($filiacionId)
        );
    }

    if (
        !empty($filiacion->fecha_inicio) &&
        $fechaAdopcion < $filiacion->fecha_inicio
    ) {
        Flash::error(
            'La fecha de adopción no puede ser anterior ' .
            'a la fecha de inicio de la pre-adopción.'
        );

        return Redirect::to(
            'personas/formalizar_adopcion/' .
            intval($filiacionId)
        );
    }

    /*
     * Una sola transacción para cerrar la pre-adopción
     * y crear la adopción.
     */
    $filiaciones->begin();

    try {

        /*
         * Cerramos la pre-adopción.
         */
        $filiacion->fecha_fin =
            $fechaAdopcion;

        if (!$filiacion->save()) {
            throw new Exception(
                'No se ha podido finalizar la pre-adopción.'
            );
        }

        /*
         * Creamos la nueva filiación adoptiva.
         */
        $adopcion = new Filiaciones();

        $adopcion->hijo_id =
            $filiacion->hijo_id;

        $adopcion->progenitor_id =
            $filiacion->progenitor_id;

        $adopcion->tipo =
            'adoptiva';

        $adopcion->fecha_inicio =
            $fechaAdopcion;

        $adopcion->fecha_fin =
            null;

        $adopcion->notas =
            $filiacion->notas;

        if (!$adopcion->save()) {
            throw new Exception(
                'No se ha podido crear la filiación adoptiva.'
            );
        }

        $filiaciones->commit();

    } catch (Exception $e) {

        $filiaciones->rollback();

        Flash::error(
            $e->getMessage()
        );

        return Redirect::to(
            'personas/formalizar_adopcion/' .
            intval($filiacionId)
        );
    }

    Flash::valid(
        'Adopción formalizada correctamente.'
    );

    return Redirect::to(
        'personas/familia/' . $hijo->id
    );
}
}
