<?php
/**
 * This file is part of informame
 * Copyright (C) 2016-2018 Carlos Garcia Gomez <neorazorx@gmail.com>
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
 * Description of inme_ediciones
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class inme_ediciones extends fs_controller
{

    public $preview;
    public $resultados;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Ediciones', 'informame');
    }

    protected function private_core()
    {
        $this->preview = new inme_noticia_preview();

        $this->resultados = array();
        $noti = new inme_noticia_fuente();
        foreach ($noti->all(0, 'editada DESC, publicada DESC') as $no) {
            if ($no->editada) {
                $this->resultados[] = $no;
            }
        }
    }
}
