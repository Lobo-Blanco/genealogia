<?php

class Auth
{
    public static function usuario()
    {
        return Session::get('usuario');
    }

    public static function estaAutenticado()
    {
        return Session::has('usuario');
    }

    public static function login($usuario)
    {
        Session::set('usuario', $usuario);
    }

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

    public static function esSupervisor()
    {
        $usuario = self::usuario();
        return $usuario && $usuario->rol_id == 4;
    }

    public static function puedeAdministrarUsuarios()
    {
        return self::esSupervisor() || self::esAdministrador() || self::esEditor();
    }

    public static function puedeCrearUsuario($rolId)
    {
        $rolId = intval($rolId);

        if (self::esSupervisor()) {
            return in_array($rolId, array(1, 2, 3));
        }

        if (self::esAdministrador()) {
            return in_array($rolId, array(2, 3));
        }

        if (self::esEditor()) {
            return $rolId == 2;
        }

        return false;
    }

    public static function puedeGestionarUsuario($usuarioId)
    {
        $actual = self::usuario();
        $usuarioId = intval($usuarioId);

        if (!$actual || $actual->id == $usuarioId) {
            return false;
        }

        $usuarios = new Usuarios();
        $objetivo = $usuarios->find_first(
            'conditions: id = ' . $usuarioId
        );

        if (!$objetivo) {
            return false;
        }

        if (self::esSupervisor()) {
            return true;
        }

        if (self::esAdministrador()) {
            return in_array($objetivo->rol_id, array(2, 3))
                && self::usuarioTieneArbolDelActual($usuarioId);
        }

        if (self::esEditor()) {
            return $objetivo->rol_id == 2
                && self::usuarioTieneArbolDelActual($usuarioId);
        }

        return false;
    }

    private static function usuarioTieneArbolDelActual($usuarioId)
    {
        $arbol = self::arbolActual();
        if (!$arbol) {
            return false;
        }

        $ua = new UsuariosArboles();
        return (bool) $ua->find_first(
            'usuario_id = ' . intval($usuarioId) .
            ' AND arbol_id = ' . intval($arbol->id)
        );
    }

    public static function arboles()
    {
        $usuario = self::usuario();
        if (!$usuario) {
            return array();
        }

        if (self::esSupervisor()) {
            return (new Arboles())->find();
        }

        $ua = new UsuariosArboles();
        return $ua->find(
            'usuario_id = ' . intval($usuario->id)
        );
    }

    public static function arbolActual()
    {
        $arboles = self::arboles();
        if (!$arboles) {
            return null;
        }

        if (self::esSupervisor()) {
            $sesion = Session::get('arbol_id');
            if ($sesion) {
                $arbol = (new Arboles())->find_first(
                    'conditions: id = ' . intval($sesion)
                );
                if ($arbol) {
                    return $arbol;
                }
            }
        }

        $activos = array_filter($arboles, function($u) {
            return $u->activo == 1;
        });

        if (!$activos) {
            return null;
        }

        $activo = reset($activos);
        return self::esSupervisor() ? $activo : $activo->arboles;
    }

    public static function tieneAccesoArbol($arbolId)
    {
        $usuario = self::usuario();
        if (!$usuario) {
            return false;
        }

        if (self::esSupervisor()) {
            return (bool) (new Arboles())->find_first(
                'conditions: id = ' . intval($arbolId)
            );
        }

        $ua = new UsuariosArboles();
        return (bool) $ua->find_first(
            'usuario_id = ' . intval($usuario->id) .
            ' AND arbol_id = ' . intval($arbolId)
        );
    }

    public static function cambiarArbol($arbolId)
    {
        $usuario = self::usuario();
        if (!$usuario || !self::tieneAccesoArbol($arbolId)) {
            return false;
        }

        if (self::esSupervisor()) {
            Session::set('arbol_id', intval($arbolId));
            return true;
        }

        $ua = new UsuariosArboles();
        $registros = $ua->find(
            'usuario_id = ' . intval($usuario->id)
        );

        foreach ($registros as $registro) {
            $registro->activo = 0;
            $registro->save();
        }

        $registro = $ua->find_first(
            'usuario_id = ' . intval($usuario->id) .
            ' AND arbol_id = ' . intval($arbolId)
        );

        if (!$registro) {
            return false;
        }

        $registro->activo = 1;
        return $registro->save();
    }

    public static function puedeEditar($personaId)
    {
        $usuario = self::usuario();
        if (!$usuario) {
            return false;
        }

        if (self::esSupervisor() || self::esAdministrador()) {
            return self::personaEnAmbito($personaId);
        }

        if (self::esVisualizador()) {
            return false;
        }

        if (self::esEditor()) {
            $persona = self::buscarPersona($personaId);
            if (!$persona || !self::personaEnArbolActual($persona)) {
                return false;
            }
            $permisos = new UsuariosPersonas();
            return $permisos->puedeEditar($usuario->id, $personaId)
                || self::personaEnRamaDesdeRaiz($personaId);
        }

        return false;
    }

    private static function buscarPersona($personaId)
    {
        return (new Personas())->find_first(
            'conditions: id = ' . intval($personaId)
        );
    }

    private static function personaEnArbolActual($persona)
    {
        $arbol = self::arbolActual();
        return $arbol && $persona->arbol_id == $arbol->id;
    }

    private static function personaEnAmbito($personaId)
    {
        $persona = self::buscarPersona($personaId);
        if (!$persona) {
            return false;
        }

        if (self::esSupervisor()) {
            return true;
        }

        return self::personaEnArbolActual($persona);
    }

    private static function personaEnRamaDesdeRaiz($personaId)
    {
        $usuario = self::usuario();
        if (!$usuario || !$usuario->persona_referencia_id) {
            return false;
        }

        if (intval($personaId) == intval($usuario->persona_referencia_id)) {
            return true;
        }

        $raiz = self::buscarPersona($usuario->persona_referencia_id);
        $persona = self::buscarPersona($personaId);
        if (!$raiz || !$persona || $raiz->arbol_id != $persona->arbol_id) {
            return false;
        }

        $visitados = array();
        $pendientes = array($raiz->id);
        $filiaciones = new Filiaciones();

        while ($pendientes) {
            $actual = array_shift($pendientes);
            if (isset($visitados[$actual])) {
                continue;
            }
            $visitados[$actual] = true;

            $hijos = $filiaciones->find(
                'progenitor_id = ' . intval($actual)
            );

            foreach ($hijos as $filiacion) {
                if (intval($filiacion->hijo_id) == intval($personaId)) {
                    return true;
                }
                if (!isset($visitados[$filiacion->hijo_id])) {
                    $pendientes[] = $filiacion->hijo_id;
                }
            }
        }

        return false;
    }

    public static function puedeEditarPersonas($persona1Id, $persona2Id)
    {
        return self::puedeEditar($persona1Id)
            && self::puedeEditar($persona2Id);
    }

    public static function puedeEditarUnion($unionId)
    {
        $usuario = self::usuario();
        if (!$usuario) {
            return false;
        }

        $union = (new Uniones())->find_first(
            'conditions: id = ' . intval($unionId)
        );
        if (!$union) {
            return false;
        }

        if (self::esSupervisor() || self::esAdministrador()) {
            return self::puedeEditarPersonas(
                $union->persona1_id,
                $union->persona2_id
            );
        }

        if (self::esEditor()) {
            return self::puedeEditarPersonas(
                $union->persona1_id,
                $union->persona2_id
            );
        }

        return false;
    }

    public static function puedeEditarFiliacion($filiacionId)
    {
        $usuario = self::usuario();
        if (!$usuario) {
            return false;
        }

        $filiacion = (new Filiaciones())->find_first(
            'conditions: id = ' . intval($filiacionId)
        );
        if (!$filiacion) {
            return false;
        }

        return self::puedeEditar($filiacion->hijo_id)
            && self::puedeEditar($filiacion->progenitor_id);
    }
}
