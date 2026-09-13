<?php

class Auth
{
    public static function usuario() { return Session::get('usuario'); }
    public static function estaAutenticado() { return Session::has('usuario'); }
    public static function login($usuario) { Session::set('usuario', $usuario); }
    public static function logout() { Session::delete('usuario'); Session::delete('arbol_id'); }
    public static function esAdministrador() { $u=self::usuario(); return $u && $u->rol_id==1; }
    public static function esVisualizador() { $u=self::usuario(); return $u && $u->rol_id==2; }
    public static function esEditor() { $u=self::usuario(); return $u && $u->rol_id==3; }
    public static function esSupervisor() { $u=self::usuario(); return $u && $u->rol_id==4; }
    public static function puedeAdministrarUsuarios() { return self::esSupervisor()||self::esAdministrador()||self::esEditor(); }
    public static function puedeCrearUsuario($rolId) {
        $rolId=intval($rolId);
        if(self::esSupervisor()) return in_array($rolId,array(1,2,3));
        if(self::esAdministrador()) return in_array($rolId,array(2,3));
        if(self::esEditor()) return $rolId==2;
        return false;
    }
    public static function puedeGestionarUsuario($usuarioId) {
        $actual=self::usuario(); $usuarioId=intval($usuarioId);
        if(!$actual || $actual->id==$usuarioId) return false;
        $objetivo=(new Usuarios())->find_first('conditions: id = '.$usuarioId);
        if(!$objetivo) return false;
        if(self::esSupervisor()) return true;
        if(self::esAdministrador()) return in_array($objetivo->rol_id,array(2,3))&&self::usuarioTieneArbolDelActual($usuarioId);
        if(self::esEditor()) return $objetivo->rol_id==2&&self::usuarioTieneArbolDelActual($usuarioId);
        return false;
    }
    private static function usuarioTieneArbolDelActual($usuarioId) {
        $arbol=self::arbolActual(); if(!$arbol) return false;
        return (bool)(new UsuariosArboles())->find_first('usuario_id = '.intval($usuarioId).' AND arbol_id = '.intval($arbol->id));
    }
    public static function arboles() {
        $u=self::usuario(); if(!$u) return array();
        if(self::esSupervisor()) return (new Arboles())->find('order: nombre');
        return (new UsuariosArboles())->find('usuario_id = '.intval($u->id));
    }
    public static function arbolActual() {
        $arboles=self::arboles(); if(!$arboles) return null;
        if(self::esSupervisor()) {
            $id=Session::get('arbol_id');
            if($id) {
                $arbol=(new Arboles())->find_first('conditions: id = '.intval($id));
                if($arbol) return $arbol;
            }
            $arbol=reset($arboles);
            if($arbol) { Session::set('arbol_id',$arbol->id); return $arbol; }
            return null;
        }
        foreach($arboles as $registro) if($registro->activo==1) return $registro->arboles;
        return null;
    }
    public static function tieneAccesoArbol($arbolId) {
        $u=self::usuario(); if(!$u) return false;
        if(self::esSupervisor()) return (bool)(new Arboles())->find_first('conditions: id = '.intval($arbolId));
        return (bool)(new UsuariosArboles())->find_first('usuario_id = '.intval($u->id).' AND arbol_id = '.intval($arbolId));
    }
    public static function cambiarArbol($arbolId) {
        $u=self::usuario(); if(!$u||!self::tieneAccesoArbol($arbolId)) return false;
        if(self::esSupervisor()) { Session::set('arbol_id',intval($arbolId)); return true; }
        $ua=new UsuariosArboles();
        foreach($ua->find('usuario_id = '.intval($u->id)) as $r) { $r->activo=0; $r->save(); }
        $r=$ua->find_first('usuario_id = '.intval($u->id).' AND arbol_id = '.intval($arbolId));
        if(!$r) return false; $r->activo=1; return $r->save();
    }
    public static function puedeEditar($personaId) {
        $u=self::usuario(); if(!$u) return false;
        if(self::esSupervisor()||self::esAdministrador()) return self::personaEnAmbito($personaId);
        if(self::esVisualizador()) return false;
        if(self::esEditor()) {
            $p=self::buscarPersona($personaId); if(!$p||!self::personaEnArbolActual($p)) return false;
            return (new UsuariosPersonas())->puedeEditar($u->id,$personaId)||self::personaEnRamaDesdeRaiz($personaId);
        }
        return false;
    }
    private static function buscarPersona($id) { return (new Personas())->find_first('conditions: id = '.intval($id)); }
    private static function personaEnArbolActual($p) { $a=self::arbolActual(); return $a&&$p->arbol_id==$a->id; }
    private static function personaEnAmbito($id) { $p=self::buscarPersona($id); if(!$p)return false; return self::esSupervisor()||self::personaEnArbolActual($p); }
    private static function personaEnRamaDesdeRaiz($id) {
        $u=self::usuario(); if(!$u||!$u->persona_referencia_id)return false;
        if(intval($id)==intval($u->persona_referencia_id))return true;
        $raiz=self::buscarPersona($u->persona_referencia_id); $persona=self::buscarPersona($id);
        if(!$raiz||!$persona||$raiz->arbol_id!=$persona->arbol_id)return false;
        $visitados=array(); $pendientes=array($raiz->id); $f=new Filiaciones();
        while($pendientes){ $actual=array_shift($pendientes); if(isset($visitados[$actual]))continue; $visitados[$actual]=true;
            foreach($f->find('progenitor_id = '.intval($actual)) as $fil){ if(intval($fil->hijo_id)==intval($id))return true; if(!isset($visitados[$fil->hijo_id]))$pendientes[]=$fil->hijo_id; }
        }
        return false;
    }
    public static function puedeEditarPersonas($a,$b) { return self::puedeEditar($a)&&self::puedeEditar($b); }
    public static function puedeEditarUnion($id) {
        $u=self::usuario(); if(!$u)return false; $union=(new Uniones())->find_first('conditions: id = '.intval($id)); if(!$union)return false;
        return (self::esSupervisor()||self::esAdministrador()||self::esEditor())&&self::puedeEditarPersonas($union->persona1_id,$union->persona2_id);
    }
    public static function puedeEditarFiliacion($id) {
        $u=self::usuario(); if(!$u)return false; $f=(new Filiaciones())->find_first('conditions: id = '.intval($id)); if(!$f)return false;
        return self::puedeEditar($f->hijo_id)&&self::puedeEditar($f->progenitor_id);
    }
}
