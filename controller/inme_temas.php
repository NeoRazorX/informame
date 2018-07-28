<?php
/**
 * This file is part of informame
 * Copyright (C) 2015-2018 Carlos Garcia Gomez <neorazorx@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Description of informame_temas
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class inme_temas extends fs_controller
{

    public $offset;
    public $orden;
    public $num_resultados;
    public $resultados;
    public $tema;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Temas', 'informame');
    }

    protected function private_core()
    {
        $this->tema = new inme_tema();
        if (isset($_POST['codtema'])) {
            $this->tema->codtema = $_POST['codtema'];
            $this->tema->titulo = $_POST['titulo'];
            $this->tema->texto = $_POST['texto'];
            if ($this->tema->save()) {
                $this->new_message('Tema guardado correctamente.');
                header('Location: ' . $this->tema->url());
            } else
                $this->new_error_msg('Error al guardar el tema.');
        }
        else if (isset($_GET['delete'])) {
            $tema2 = $this->tema->get($_GET['delete']);
            if ($tema2) {
                if ($tema2->codtema == $_GET['delete']) {
                    if ($tema2->delete()) {
                        $this->new_message('Tema eliminado correctamente.');
                    }
                }
            }
        }

        $this->offset = 0;
        if (isset($_GET['offset'])) {
            $this->offset = intval($_GET['offset']);
        }

        $this->orden = 'popularidad desc';
        if (isset($_REQUEST['orden'])) {
            $this->orden = $_REQUEST['orden'];
        }

        $this->resultados = $this->buscar();
    }

    private function buscar()
    {
        $tlist = array();
        $this->num_resultados = 0;

        $query = $this->tema->no_html(mb_strtolower($this->query, 'UTF8'));

        $sql = "";
        if ($query != '') {
            $sql .= " WHERE lower(titulo) LIKE '%" . $query . "%' OR lower(texto) LIKE '%" . $query . "%'";
        }

        $data = $this->db->select("SELECT COUNT(*) as total from inme_temas" . $sql);
        if ($data) {
            $this->num_resultados = intval($data[0]['total']);

            $sql = "SELECT * FROM inme_temas" . $sql . " ORDER BY " . $this->orden;
            $data2 = $this->db->select_limit($sql, FS_ITEM_LIMIT, $this->offset);
            if ($data2) {
                foreach ($data2 as $d) {
                    $tlist[] = new inme_tema($d);
                }
            }
        }

        return $tlist;
    }
}
