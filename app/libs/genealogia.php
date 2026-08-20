<?php

class Genealogia
{
    /**
     * Busca la última unión histórica entre dos personas.
     *
     * El orden de persona1_id y persona2_id es indiferente.
     */
    public static function buscarUnion($persona1Id, $persona2Id)
    {
        $uniones = new Uniones();

        return $uniones->find_first(
            "conditions: " .
            "((persona1_id = " . intval($persona1Id) .
            " AND persona2_id = " . intval($persona2Id) . ") " .
            "OR " .
            "(persona1_id = " . intval($persona2Id) .
            " AND persona2_id = " . intval($persona1Id) . "))",
            "order: fecha_inicio DESC"
        );
    }


    /**
     * Busca la unión activa de una persona.
     *
     * Una unión está activa cuando fecha_fin es NULL.
     */
    public static function unionActiva($personaId)
    {
        $uniones = new Uniones();

        return $uniones->find_first(
            "conditions: " .
            "(persona1_id = " . intval($personaId) .
            " OR persona2_id = " . intval($personaId) . ") " .
            "AND fecha_fin IS NULL",
            "order: fecha_inicio DESC"
        );
    }


    /**
     * Comprueba si dos IDs representan la misma persona.
     */
    public static function sonLaMismaPersona(
        $persona1Id,
        $persona2Id
    ) {
        return intval($persona1Id) === intval($persona2Id);
    }


    /**
     * Comprueba que una persona exista.
     */
    public static function obtenerPersona($personaId)
    {
        $personas = new Personas();

        return $personas->find_first(
            "conditions: id = " . intval($personaId)
        );
    }


    /**
     * Crea una nueva pareja.
     *
     * No permite:
     * - una persona consigo misma;
     * - dos uniones activas para una persona;
     * - una nueva unión si ya existe una unión activa
     *   entre las mismas personas.
     */
public static function crearUnion(
    $persona1Id,
    $persona2Id,
    $tipo,
    $fechaInicio = null,
    $lugar = null,
    $notas = null
) {
    /*
     * ---------------------------------------------------------
     * 1. Validaciones básicas
     * ---------------------------------------------------------
     */

    $persona1Id = intval($persona1Id);
    $persona2Id = intval($persona2Id);

    /*
     * Comprobar que ambas personas existen.
     */
    $personas = new Personas();

    $persona1 = $personas->find_first(
        "conditions: id = " . intval($persona1Id)
    );

    $persona2 = $personas->find_first(
        "conditions: id = " . intval($persona2Id)
    );

    if (!$persona1 || !$persona2) {
        return array(
            'ok' => false,
            'mensaje' =>
                'Una de las personas no existe.'
        );
    }

    // No puede relacionarse una persona consigo misma.
    if ($persona1->id == $persona2->id) {
        return array(
            'ok' => false,
            'mensaje' =>
                'No puede relacionarse consigo mismo.'
        );
    }

    // Ambas personas deben pertenecer al mismo árbol.
    if ($persona1->arbol_id != $persona2->arbol_id) {
        return array(
            'ok' => false,
            'mensaje' =>
                'Una de las personas no pertenece a su árbol.'
        );
    }

    if (!$persona1->fecha_nacimiento > $fechaInicio || !$persona2->fecha_nacimiento > $fechaInicio) {
        return array(
            'ok' => false,
            'mensaje' =>
                'La unión es anterior a la fecha de nacimiento de uno de los contrayentes.'
        );
    }

    // Ese árbol debe ser el árbol activo del usuario.
    $arbol = Auth::arbolActual();

    if (!$arbol || $persona1->arbol_id != $arbol->id) {
        return array(
            'ok' => false,
            'mensaje' =>
                'Una de las personas no pertenece al árbol activo del usuario.'
        );
    }
    
    if (!in_array($tipo, array('pareja', 'matrimonio'))) {
        return array(
            'ok' => false,
            'mensaje' =>
                'El tipo de unión no es válido.'
        );
    }

    /*
     * ---------------------------------------------------------
     * 2. Buscar las uniones activas
     * ---------------------------------------------------------
     */

    $unionActiva1 =
        self::unionActiva($persona1Id);

    $unionActiva2 =
        self::unionActiva($persona2Id);


    /*
     * ---------------------------------------------------------
     * 3. No existe ninguna unión activa
     * ---------------------------------------------------------
     */

    if (!$unionActiva1 && !$unionActiva2) {
        return self::guardarNuevaUnion(
            $persona1Id,
            $persona2Id,
            $tipo,
            $fechaInicio,
            $lugar,
            $notas
        );
    }


    /*
     * ---------------------------------------------------------
     * 4. Comprobar si la unión activa es entre estas
     *    mismas dos personas.
     * ---------------------------------------------------------
     */

    $mismaUnion = false;

    if ($unionActiva1) {
        $mismaUnion =
            (
                (
                    $unionActiva1->persona1_id == $persona1Id &&
                    $unionActiva1->persona2_id == $persona2Id
                )
                ||
                (
                    $unionActiva1->persona1_id == $persona2Id &&
                    $unionActiva1->persona2_id == $persona1Id
                )
            );
    }


    /*
     * ---------------------------------------------------------
     * 5. Ya son pareja/matrimonio entre ellos
     * ---------------------------------------------------------
     */

    if ($mismaUnion) {

        /*
         * No se puede crear otra unión del mismo tipo.
         */
        if ($unionActiva1->tipo == $tipo) {
            return array(
                'ok' => false,
                'mensaje' =>
                    'Ya existe una unión activa de ese tipo ' .
                    'entre estas dos personas.'
            );
        }


        /*
         * Pareja -> matrimonio.
         */
        if (
            $unionActiva1->tipo == 'pareja' &&
            $tipo == 'matrimonio'
        ) {
            if (!Auth::puedeEditarUnion($unionActiva1->id)) {
                return array(
                    'ok' => false,
                    'mensaje' =>
                        'No tiene permiso para modificar esta unión.'
                );
            }

            return self::crearMatrimonio(
                $unionActiva1,
                $fechaInicio,
                $lugar,
                $notas
            );
        }


        /*
         * Matrimonio -> pareja entre las mismas personas
         * no tiene sentido en este modelo.
         */
        return array(
            'ok' => false,
            'mensaje' =>
                'No se puede cambiar directamente un matrimonio ' .
                'a pareja.'
        );
    }


    /*
     * ---------------------------------------------------------
     * 6. La persona destino ya tiene otra unión activa.
     *
     * No podemos crear una nueva unión sin resolver primero
     * la relación de esa persona.
     * ---------------------------------------------------------
     */

    if ($unionActiva2) {
        return array(
            'ok' => false,
            'mensaje' =>
                'La segunda persona ya tiene una unión activa.'
        );
    }


    /*
     * ---------------------------------------------------------
     * 7. La persona 1 tiene una unión con OTRA persona.
     * ---------------------------------------------------------
     */

    $uniones = new Uniones();

    /*
     * Matrimonio anterior -> divorcio.
     */
    if ($unionActiva1->tipo == 'matrimonio') {
        if (empty($fechaInicio)) {
            return array(
                'ok' => false,
                'mensaje' =>
                    'Debe indicar la fecha de inicio de la nueva unión.'
            );
        }

        if (
            $unionActiva1->fecha_inicio &&
            $fechaInicio < $unionActiva1->fecha_inicio
        ) {
            return array(
                'ok' => false,
                'mensaje' =>
                    'La fecha de la nueva unión no puede ser ' .
                    'anterior al inicio del matrimonio anterior.'
            );
        }


        /*
         * La nueva unión no puede comenzar antes de que
         * termine el matrimonio anterior.
         */
        $divorcio = new Divorcios();

        $divorcio->union_id =
            $unionActiva1->id;

        $divorcio->fecha =
            $fechaInicio;

        $divorcio->lugar =
            $lugar;

        $divorcio->notas =
            $notas;


        $unionActiva1->fecha_fin =
            $fechaInicio;

        $unionActiva1->fin_tipo =
            'divorcio';


        /*
         * Crear la nueva unión.
         */
        $nuevaUnion = new Uniones();

        $nuevaUnion->persona1_id =
            $persona1Id;

        $nuevaUnion->persona2_id =
            $persona2Id;

        $nuevaUnion->tipo =
            $tipo;

        $nuevaUnion->fecha_inicio =
            $fechaInicio;

        $nuevaUnion->fecha_fin =
            null;

        $nuevaUnion->fin_tipo =
            'ninguno';

        $nuevaUnion->lugar =
            $lugar;

        $nuevaUnion->notas =
            $notas;

        $nuevaUnion->usuario_id = 
            Auth::usuario()->id;
        
        return self::guardarFinYNuevaUnion(
            $unionActiva1,
            $divorcio,
            $nuevaUnion
        );
    }


    /*
     * ---------------------------------------------------------
     * 8. Pareja anterior -> disolución.
     * ---------------------------------------------------------
     */

    if ($unionActiva1->tipo == 'pareja') {

        if (empty($fechaInicio)) {
            return array(
                'ok' => false,
                'mensaje' =>
                    'Debe indicar la fecha de inicio de la nueva unión.'
            );
        }

        if (
            $unionActiva1->fecha_inicio &&
            $fechaInicio < $unionActiva1->fecha_inicio
        ) {
            return array(
                'ok' => false,
                'mensaje' =>
                    'La fecha de la nueva unión no puede ser ' .
                    'anterior al inicio de la pareja anterior.'
            );
        }


        /*
         * Finalizar pareja.
         */
        $unionActiva1->fecha_fin =
            $fechaInicio;

        $unionActiva1->fin_tipo =
            'disolucion';


        /*
         * Crear nueva unión.
         */
        $nuevaUnion = new Uniones();

        $nuevaUnion->persona1_id =
            $persona1Id;

        $nuevaUnion->persona2_id =
            $persona2Id;

        $nuevaUnion->tipo =
            $tipo;

        $nuevaUnion->fecha_inicio =
            $fechaInicio;

        $nuevaUnion->fecha_fin =
            null;

        $nuevaUnion->fin_tipo =
            'ninguno';

        $nuevaUnion->lugar =
            $lugar;

        $nuevaUnion->notas =
            $notas;

        $nuevaUnion->usuario_id = 
            Auth::usuario()->id;

        if (!Auth::puedeEditarUnion($unionActiva1->id)) {
            return array(
                'ok' => false,
                'mensaje' =>
                    'No tiene permiso para modificar la unión existente.'
            );
        }

        return self::guardarFinYNuevaUnion(
            $unionActiva1,
            null,
            $nuevaUnion
        );
    }


    return array(
        'ok' => false,
        'mensaje' =>
            'No se ha podido determinar el estado de la unión.'
    );
}

    /**
     * Convierte una pareja en matrimonio.
     *
     * La pareja anterior se cierra y se crea una
     * nueva unión de tipo matrimonio.
     */
    private static function crearMatrimonio(
        $pareja,
        $fechaMatrimonio,
        $lugar,
        $notas
    ) {
        if (empty($fechaMatrimonio)) {
            return array(
                'ok' => false,
                'mensaje' =>
                    'Debe indicar la fecha del matrimonio.'
            );
        }

        if (
            $pareja->fecha_inicio &&
            $fechaMatrimonio < $pareja->fecha_inicio
        ) {
            return array(
                'ok' => false,
                'mensaje' =>
                    'La fecha del matrimonio no puede ser ' .
                    'anterior al inicio de la pareja.'
            );
        }

        $fechaFinPareja = date(
            'Y-m-d',
            strtotime($fechaMatrimonio . ' -1 day')
        );

        $matrimonio = new Uniones();

        $matrimonio->usuario_id =
            $pareja->usuario_id;

        $matrimonio->persona1_id =
            $pareja->persona1_id;

        $matrimonio->persona2_id =
            $pareja->persona2_id;

        $matrimonio->tipo =
            'matrimonio';

        $matrimonio->fecha_inicio =
            $fechaMatrimonio;

        $matrimonio->fecha_fin =
            null;

        $matrimonio->fin_tipo =
            'ninguno';

        $matrimonio->lugar =
            $lugar;

        $matrimonio->notas =
            $notas;

        $uniones = new Uniones();

        $uniones->begin();

        try {

            $pareja->fecha_fin =
                $fechaFinPareja;

            $pareja->fin_tipo =
                'matrimonio';

            
            if (!$pareja->save()) {
                throw new Exception(
                    'No se ha podido finalizar la pareja.'
                );
            }

            if (!$matrimonio->save()) {
                throw new Exception(
                    'No se ha podido crear el matrimonio.'
                );
            }

            $uniones->commit();

        } catch (Exception $e) {

            $uniones->rollback();

            return array(
                'ok' => false,
                'mensaje' => $e->getMessage()
            );
        }

        return array(
            'ok' => true,
            'mensaje' =>
                'Matrimonio creado correctamente.',
            'union' => $matrimonio
        );
    }

    /**
     * Disuelve una pareja.
     *
     * No es un divorcio: la relación era de pareja,
     * pero no matrimonio.
     */
    public static function disolverPareja(
        $unionId,
        $fecha,
        $lugar = null,
        $notas = null
    ) {
        if (empty($fecha)) {
            return array(
                'ok' => false,
                'mensaje' =>
                    'Debe indicar la fecha de la disolución.'
            );
        }


        $uniones = new Uniones();

        $union = $uniones->find_first(
            "conditions: id = " .
            intval($unionId)
        );

        if (!$union) {
            return array(
                'ok' => false,
                'mensaje' =>
                    'La unión no existe.'
            );
        }

        if (!Auth::puedeEditarUnion($union->id)) {
            return array(
                'ok' => false,
                'mensaje' =>
                    'No tiene permiso para modificar esta unión.'
            );
        }

        if ($union->tipo != 'pareja') {
            return array(
                'ok' => false,
                'mensaje' =>
                    'La unión seleccionada no es una pareja.'
            );
        }


        if (
            $union->fecha_fin !== null &&
            $union->fecha_fin != ''
        ) {
            return array(
                'ok' => false,
                'mensaje' =>
                    'La pareja ya ha finalizado.'
            );
        }


        if (
            $union->fecha_inicio &&
            $fecha < $union->fecha_inicio
        ) {
            return array(
                'ok' => false,
                'mensaje' =>
                    'La fecha de disolución no puede ser ' .
                    'anterior al inicio de la pareja.'
            );
        }


        $union->fecha_fin =
            $fecha;

        $union->fin_tipo =
            'disolucion';

        /*
         * Guardamos los datos adicionales de la
         * disolución en las propias notas/lugar.
         */
        if ($lugar !== null) {
            $union->lugar = $lugar;
        }

        if ($notas !== null) {
            $union->notas = $notas;
        }


        if (!$union->save()) {
            return array(
                'ok' => false,
                'mensaje' =>
                    'No se ha podido disolver la pareja.'
            );
        }


        return array(
            'ok' => true,
            'mensaje' =>
                'Pareja disuelta correctamente.',
            'union' => $union
        );
    }


    /**
     * Registra un divorcio y finaliza el matrimonio.
     *
     * Las dos operaciones forman una única transacción.
     */
    public static function registrarDivorcio(
        $unionId,
        $fecha,
        $lugar = null,
        $notas = null
    ) {
        if (empty($fecha)) {
            return array(
                'ok' => false,
                'mensaje' =>
                    'Debe indicar la fecha del divorcio.'
            );
        }


        $uniones = new Uniones();
        $divorcios = new Divorcios();


        $union = $uniones->find_first(
            "conditions: id = " .
            intval($unionId)
        );

        if (!$union) {
            return array(
                'ok' => false,
                'mensaje' =>
                    'La unión no existe.'
            );
        }

        if (!Auth::puedeEditarUnion($union->id)) {
            return array(
                'ok' => false,
                'mensaje' =>
                    'No tiene permiso para modificar esta unión.'
            );
        }

        if ($union->tipo != 'matrimonio') {
            return array(
                'ok' => false,
                'mensaje' =>
                    'Solo se puede registrar un divorcio ' .
                    'sobre un matrimonio.'
            );
        }


        if (
            $union->fecha_fin !== null &&
            $union->fecha_fin != ''
        ) {
            return array(
                'ok' => false,
                'mensaje' =>
                    'El matrimonio ya ha finalizado.'
            );
        }

        if (
            $union->fecha_inicio &&
            $fecha < $union->fecha_inicio
        ) {
            return array(
                'ok' => false,
                'mensaje' =>
                    'La fecha del divorcio no puede ser ' .
                    'anterior al inicio del matrimonio.'
            );
        }


        $existente = $divorcios->find_first(
            "conditions: union_id = " .
            intval($unionId)
        );

        if ($existente) {
            return array(
                'ok' => false,
                'mensaje' =>
                    'Este matrimonio ya tiene registrado ' .
                    'un divorcio.'
            );
        }


        $divorcio = new Divorcios();

        $divorcio->union_id =
            $union->id;

        $divorcio->fecha =
            $fecha;

        $divorcio->lugar =
            $lugar;

        $divorcio->notas =
            $notas;


        $union->fecha_fin =
            $fecha;

        $union->fin_tipo =
            'divorcio';


        $uniones->begin();

        try {

            if (!$divorcio->save()) {
                throw new Exception(
                    'No se ha podido registrar el divorcio.'
                );
            }


            if (!$union->save()) {
                throw new Exception(
                    'No se ha podido finalizar el matrimonio.'
                );
            }


            $uniones->commit();

        } catch (Exception $e) {

            $uniones->rollback();

            return array(
                'ok' => false,
                'mensaje' => $e->getMessage()
            );
        }


        return array(
            'ok' => true,
            'mensaje' =>
                'Divorcio registrado correctamente.',
            'divorcio' => $divorcio,
            'union' => $union
        );
    }

    public static function buscarUnionActivaEntre(
        $persona1Id,
        $persona2Id
    ) {
        $uniones = new Uniones();

        return $uniones->find_first(
            "conditions: " .
            "fecha_fin IS NULL AND " .
            "((persona1_id = " . intval($persona1Id) .
            " AND persona2_id = " . intval($persona2Id) . ") " .
            "OR " .
            "(persona1_id = " . intval($persona2Id) .
            " AND persona2_id = " . intval($persona1Id) . "))"
        );
    }

    private static function guardarNuevaUnion(
        $persona1Id,
        $persona2Id,
        $tipo,
        $fechaInicio,
        $lugar,
        $notas
    ) {
        $union = new Uniones();

        $union->persona1_id = $persona1Id;
        $union->persona2_id = $persona2Id;
        $union->tipo = $tipo;
        $union->fecha_inicio = $fechaInicio;
        $union->fecha_fin = null;
        $union->fin_tipo = 'ninguno';
        $union->lugar = $lugar;
        $union->notas = $notas;
        $union->usuario_id = Auth::usuario()->id;

        $uniones = new Uniones();

        $uniones->begin();

        try {

            if (!$union->save()) {
                throw new Exception(
                    'No se ha podido guardar la unión.'
                );
            }

            $uniones->commit();

        } catch (Exception $e) {

            $uniones->rollback();

            return array(
                'ok' => false,
                'mensaje' => $e->getMessage()
            );
        }

        return array(
            'ok' => true,
            'mensaje' =>
                $tipo == 'matrimonio'
                    ? 'Matrimonio creado correctamente.'
                    : 'Pareja creada correctamente.',
            'union' => $union
        );
    }    

private static function guardarFinYNuevaUnion(
    $unionAnterior,
    $divorcio,
    $nuevaUnion
) {
    $uniones = new Uniones();

    $uniones->begin();

    try {

        /*
         * Finalizar la unión anterior.
         */
        if (!$unionAnterior->save()) {
            throw new Exception(
                'No se ha podido finalizar la unión anterior.'
            );
        }


        /*
         * Si existe divorcio, guardarlo.
         */
        if ($divorcio) {
            if (!$divorcio->save()) {
                throw new Exception(
                    'No se ha podido registrar el divorcio.'
                );
            }
        }


        /*
         * Crear la nueva unión.
         */
        if (!$nuevaUnion->save()) {
            throw new Exception(
                'No se ha podido crear la nueva unión.'
            );
        }

        $uniones->commit();

    } catch (Exception $e) {

        $uniones->rollback();

        return array(
            'ok' => false,
            'mensaje' => $e->getMessage()
        );
    }


    if ($divorcio) {
        $mensaje =
            'Matrimonio anterior finalizado por divorcio ' .
            'y nueva unión creada.';
    } else {
        $mensaje =
            'Pareja anterior disuelta y nueva unión creada.';
    }


    return array(
        'ok' => true,
        'mensaje' => $mensaje,
        'union' => $nuevaUnion,
        'union_anterior' => $unionAnterior,
        'divorcio' => $divorcio
    );
}

public static function crearFiliacion(
    $hijoId,
    $progenitorId,
    $tipo = 'biologica',
    $fechaInicio = null,
    $fechaFin = null,
    $notas = null
) {
    $hijoId = intval($hijoId);
    $progenitorId = intval($progenitorId);

    $personas = new Personas();

    $progenitor = $personas->find_first(
        "conditions: id = " . intval($progenitorId)
    );

    $hijo = $personas->find_first(
        "conditions: id = " . intval($hijoId)
    );

    if (!$progenitor || !$hijo) {
        return array(
            'ok' => false,
            'mensaje' =>
                'El hijo o el progenitor no existe.'
        );
    }

    /*
    * Evitar ciclos genealógicos.
    *
    * Si el progenitor ya es descendiente del hijo,
    * crear esta filiación produciría un ciclo.
    */
    if (
        self::esDescendienteDe(
            $progenitorId,
            $hijoId
        )
    ) {
        return array(
            'ok' => false,
            'mensaje' =>
                'La filiación crearía un ciclo genealógico.'
        );
    }

    if ($progenitor->arbol_id != $hijo->arbol_id) {
        return array(
            'ok' => false,
            'mensaje' =>
                'Las dos personas deben pertenecer al mismo árbol.'
        );
    }

    $arbol = Auth::arbolActual();

    if (!$arbol || $progenitor->arbol_id != $arbol->id) {
        return array(
            'ok' => false,
            'mensaje' =>
                'Las personas no pertenecen al árbol actual.'
        );
    }

    /*
     * Una persona no puede ser progenitor
     * de sí misma.
     */
    if ($hijoId === $progenitorId) {
        return array(
            'ok' => false,
            'mensaje' =>
                'Una persona no puede ser progenitor de sí misma.'
        );
    }

    /*
     * Tipo de filiación.
     */
    if (!in_array(
        $tipo,
        array('biologica', 'adoptiva')
    )) {
        return array(
            'ok' => false,
            'mensaje' =>
                'El tipo de filiación no es válido.'
        );
    }

    /*
     * Comprobar que existen ambas personas.
     */
    $personas = new Personas();

    $hijo = $personas->find($hijoId);
    $progenitor = $personas->find($progenitorId);

    if (!$hijo || !$progenitor) {
        return array(
            'ok' => false,
            'mensaje' =>
                'El hijo o el progenitor no existe.'
        );
    }

    /*
     * Comprobar que no exista ya la filiación.
     */
    $filiaciones = new Filiaciones();

    $existe = $filiaciones->find_first(
        "conditions: " .
        "hijo_id = " . $hijoId .
        " AND progenitor_id = " . $progenitorId .
        " AND tipo = '" . $tipo . "'"
    );

    if ($existe) {
        return array(
            'ok' => false,
            'mensaje' =>
                'Esta filiación ya existe.'
        );
    }

    /*
     * Comprobar que las fechas sean coherentes
    */
    if (
        $fechaInicio !== null &&
        $fechaFin !== null &&
        $fechaFin < $fechaInicio
    ) {
        return array(
            'ok' => false,
            'mensaje' =>
                'La fecha de finalización no puede ser ' .
                'anterior a la fecha de inicio.'
        );
    }

    if (
        $hijo->fecha_nacimiento &&
        $fechaInicio &&
        $fechaInicio < $hijo->fecha_nacimiento
    ) {
        return array(
            'ok' => false,
            'mensaje' =>
                'La filiación no puede comenzar antes ' .
                'del nacimiento del hijo.'
        );
    }

    if (
        $hijo->fecha_nacimiento &&
        $fechaFin &&
        $fechaFin < $hijo->fecha_nacimiento
    ) {
        return array(
            'ok' => false,
            'mensaje' =>
                'La filiación no puede finalizar antes ' .
                'del nacimiento del hijo.'
        );
    }

    /*
     * Crear filiación.
     */
    $filiacion = new Filiaciones();

    $filiacion->hijo_id =
        $hijoId;

    $filiacion->progenitor_id =
        $progenitorId;

    $filiacion->tipo =
        $tipo;

    $filiacion->fecha_inicio =
        $fechaInicio;

    $filiacion->fecha_fin =
        $fechaFin;

    $filiacion->notas =
        $notas;

    $filiacion->usuario_id = 
        Auth::usuario()->id;
    
    /*
     * Guardar.
     */
    if (!$filiacion->save()) {
        return array(
            'ok' => false,
            'mensaje' =>
                'No se ha podido guardar la filiación.'
        );
    }

    return array(
        'ok' => true,
        'mensaje' =>
            'Filiación creada correctamente.',
        'filiacion' => $filiacion
    );
}

/**
 * Comprueba si una persona es descendiente de otra.
 *
 * $personaId          Persona de la que partimos.
 * $posibleAncestroId  Persona que queremos comprobar si es ancestro.
 *
 * Devuelve true si $posibleAncestroId aparece entre los
 * progenitores de $personaId, directa o indirectamente.
 */
public static function esDescendienteDe(
    $personaId,
    $posibleAncestroId
) {
    $personaId =
        intval($personaId);

    $posibleAncestroId =
        intval($posibleAncestroId);

    if (
        $personaId <= 0 ||
        $posibleAncestroId <= 0
    ) {
        return false;
    }

    /*
     * Una persona es descendiente de sí misma
     * a efectos de esta comprobación de ciclo.
     */
    if ($personaId === $posibleAncestroId) {
        return true;
    }

    $visitadas = array();

    /*
     * Comprobamos recursivamente los progenitores.
     */
    return self::buscarAncestro(
        $personaId,
        $posibleAncestroId,
        $visitadas
    );
}

/**
 * Busca un ancestro recorriendo las filiaciones
 * hacia los progenitores.
 */
private static function buscarAncestro(
    $personaId,
    $ancestroId,
    &$visitadas
) {
    if (isset($visitadas[$personaId])) {
        return false;
    }

    $visitadas[$personaId] = true;

    $filiaciones =
        new Filiaciones();

    $progenitores =
        $filiaciones->find(
            "conditions: hijo_id = " .
            intval($personaId)
        );

    if (!$progenitores) {
        return false;
    }

    foreach ($progenitores as $filiacion) {

        $progenitorId =
            intval($filiacion->progenitor_id);

        if ($progenitorId === $ancestroId) {
            return true;
        }

        if (
            self::buscarAncestro(
                $progenitorId,
                $ancestroId,
                $visitadas
            )
        ) {
            return true;
        }
    }

    return false;
}
}