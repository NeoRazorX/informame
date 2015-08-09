<?php

/*
 * This file is part of informame
 * Copyright (C) 2015  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_model('inme_fuente.php');

/**
 * Description of informame_fuentes
 *
 * @author carlos
 */
class inme_fuentes extends fs_controller
{
   public $resultados;
   public $fuente;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Fuentes', 'informame');
   }
   
   protected function private_core()
   {
      $this->fuente = new inme_fuente();
      
      if( isset($_POST['codfuente']) )
      {
         $this->fuente->codfuente = $_POST['codfuente'];
         $this->fuente->url = $_POST['url'];
         if( $this->fuente->save() )
         {
            $this->new_message('Fuente guardada correctamente.');
         }
         else
            $this->new_error_msg('Error al guardar la fuente.');
      }
      
      $this->resultados = $this->fuente->all();
   }
}
