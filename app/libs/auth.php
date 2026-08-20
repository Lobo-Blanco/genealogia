<?php

class Auth
{
    /**
     * Devuelve el usuario actualmente autenticado.
     */
    public static function usuario()
    {
        return Session::get('usuario');
    }

    /**
     * Indica si existe una sesión autenticada.
     */
    public static function estaAutenticado()
    {
        return Session::has('usuario');
    }

    /**
     * Inicia sesión con un usuario.
     */
    public static function login($usuario)
    {
        Session::set('usuario', $usuario);
    }

    /**
     * Cierra la sesión.
     */
    public static function logout()
    {
        Session::delete('usuario');
    }

    public static function esAdministrador()
    {
        $usuario = self::usuario();

        return $usuario && $usuario->rol_id == 1;
    }

    public static function esVisualizador()
    {
        $usuario = self::usuario();

        return $usuario && $usuario->rol_id == 2;
    }

    public static function esEditor()
    {
        $usuario = self::usuario();

        return $usuario && $usuario->rol_id == 3;
    }

    /**
     * Comprueba si puede modificar una persona.
     */
    public static function puedeEditar($personaId)
    {
        $usuario = self::usuario();

        if (!$usuario) {
            return false;
        }

        // Administrador: puede modificar cualquier persona.
        if ($usuario->rol_id == 1) {
            return true;
        }

        // Visualizador: nunca puede modificar.
        if ($usuario->rol_id == 2) {
            return false;
        }

        // Editor: solamente su ámbito.
        if ($usuario->rol_id == 3) {
            $arbol = self::arbolActual();

            if (!$arbol) {
                return false;
            }

            $personas = new Personas();

            $persona = $personas->find_first(
                "conditions: id = " . intval($personaId)
            );

            if (!$persona) {
                return false;
            }

            if ($persona->arbol_id != $arbol->id) {
                return false;
            }

            $permisos = new UsuariosPersonas();

            return $permisos->puedeEditar(
                $usuario->id,
                $personaId
            );
        }

        return false;
    }

public static function arboles()
{
    $usuario = Auth::usuario();

    if (!$usuario) {
        return array();
    }

    $ua = new UsuariosArboles();

    return $ua->find(
        "usuario_id = {$usuario->id}"
    );
}

public static function arbolActual()
{
    $arboles = Auth::arboles();

    if (!$arboles) {
        return null;
    }

    $activos = array_filter($arboles, function($u) {
        return $u->activo == 1;
    });

    if (!$activos) {
        return null;
    }

    $activo = reset($activos);

    return $activo->arboles;
}

public static function tieneAccesoArbol($arbolId)
{
    $usuario = Auth::usuario();

    if (!$usuario) {
        return false;
    }

    $ua = new UsuariosArboles();

    return (bool) $ua->find_first(
        "usuario_id = {$usuario->id}",
        "arbol_id = {$arbolId}"
    );
}

public static function cambiarArbol($arbolId)
{
    $usuario = Auth::usuario();

    if (!$usuario) {
        return false;
    }

    $ua = new UsuariosArboles();

    // Primero desactivamos los árboles del usuario.
    $registros = $ua->find(
        "usuario_id = {$usuario->id}"
    );

    foreach ($registros as $registro) {
        $registro->activo = 0;
        $registro->save();
    }

    // Activamos el seleccionado.
    $registro = $ua->find_first(
        "usuario_id = {$usuario->id}",
        "arbol_id = {$arbolId}"
    );

    if (!$registro) {
        return false;
    }

    $registro->activo = 1;

    return $registro->save();
}

/**
 * Comprueba si puede modificar dos personas.
 */
public static function puedeEditarPersonas(
    $persona1Id,
    $persona2Id
) {
    $usuario = self::usuario();

    if (!$usuario) {
        return false;
    }

    // Administrador: puede modificar cualquier persona.
    if ($usuario->rol_id == 1) {
        return true;
    }

    // Visualizador: nunca puede modificar.
    if ($usuario->rol_id == 2) {
        return false;
    }

    // Editor: debe tener permiso sobre las dos personas.
    if ($usuario->rol_id == 3) {

        return self::puedeEditar($persona1Id)
            && self::puedeEditar($persona2Id);
    }

    return false;
}

/**
 * Comprueba si puede modificar una unión.
 */
public static function puedeEditarUnion($unionId)
{
    $usuario = self::usuario();

    if (!$usuario) {
        return false;
    }

    // Administrador: puede modificar cualquier unión.
    if ($usuario->rol_id == 1) {
        return true;
    }

    // Visualizador: nunca puede modificar.
    if ($usuario->rol_id == 2) {
        return false;
    }

    // Editor.
    if ($usuario->rol_id == 3) {

        $arbol = self::arbolActual();

        if (!$arbol) {
            return false;
        }

        $uniones = new Uniones();

        $union = $uniones->find_first(
            "conditions: id = " . intval($unionId)
        );

        if (!$union) {
            return false;
        }

        // La unión debe haber sido creada por este usuario.
        if ($union->usuario_id != $usuario->id) {
            return false;
        }

        // Comprobamos que las dos personas existen.
        $personas = new Personas();

        $persona1 = $personas->find_first(
            "conditions: id = " . intval($union->persona1_id)
        );

        $persona2 = $personas->find_first(
            "conditions: id = " . intval($union->persona2_id)
        );

        if (!$persona1 || !$persona2) {
            return false;
        }

        // Las dos personas deben pertenecer al árbol actual.
        if (
            $persona1->arbol_id != $arbol->id ||
            $persona2->arbol_id != $arbol->id
        ) {
            return false;
        }

        return true;
    }

    return false;
}

/**
 * Comprueba si puede modificar una filiación.
 */
public static function puedeEditarFiliacion($filiacionId)
{
    $usuario = self::usuario();

    if (!$usuario) {
        return false;
    }

    // Administrador.
    if ($usuario->rol_id == 1) {
        return true;
    }

    // Visualizador.
    if ($usuario->rol_id == 2) {
        return false;
    }

    // Editor: solamente las filiaciones que ha creado.
    if ($usuario->rol_id == 3) {

        $filiaciones = new Filiaciones();

        $filiacion = $filiaciones->find_first(
            "conditions: id = " .
            intval($filiacionId)
        );

        if (!$filiacion) {
            return false;
        }

        return $filiacion->usuario_id ==
            $usuario->id;
    }

    return false;
}
}