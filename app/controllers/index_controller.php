<?php

/**
 * Controller por defecto si no se usa el routes
 *
 */
class IndexController extends AppController
{
    public function index()
    {
        $this->personas = Personas::all();
    }

    public function ver($id)
    {
        $this->persona = Personas::first($id);

        if (!$this->persona) {
            Flash::error('La persona no existe');
            return Redirect::to('personas/');
        }
    }

    public function nuevo()
    {
        if (Input::hasPost('persona')) {

            $persona = new Personas(Input::post('persona'));

            if ($persona->save()) {
                Flash::valid('Persona creada correctamente');
                return Redirect::to('personas/');
            }

            Flash::error('No se pudo guardar la persona');
            $this->persona = $persona;

        } else {
            $this->persona = new Personas();
        }
    }

    public function editar($id)
    {
        $this->persona = Personas::first($id);

        if (!$this->persona) {
            Flash::error('La persona no existe');
            return Redirect::to('personas/');
        }

        if (Input::hasPost('persona')) {

            $this->persona->nombre = Input::post('persona.nombre');
            $this->persona->apellidos = Input::post('persona.apellidos');
            $this->persona->sexo = Input::post('persona.sexo');
            $this->persona->fecha_nacimiento = Input::post('persona.fecha_nacimiento');
            $this->persona->lugar_nacimiento = Input::post('persona.lugar_nacimiento');
            $this->persona->fecha_defuncion = Input::post('persona.fecha_defuncion');
            $this->persona->lugar_defuncion = Input::post('persona.lugar_defuncion');
            $this->persona->notas = Input::post('persona.notas');

            if ($this->persona->save()) {
                Flash::valid('Persona modificada correctamente');
                return Redirect::to('personas/ver/' . $id);
            }

            Flash::error('No se pudo modificar la persona');
        }
    }
}
