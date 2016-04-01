<?php

/*
 * This file is part of informame
 * Copyright (C) 2016  Carlos Garcia Gomez  neorazorx@gmail.com
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
 * Description of inme_admin
 *
 * @author carlos
 */
class inme_admin extends fs_controller
{
   public $analytics;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Informame', 'admin');
   }
   
   protected function private_core()
   {
      $fsvar = new fs_var();
      $this->analytics = $fsvar->simple_get('inme_analytics');
      
      if( isset($_POST['analytics']) )
      {
         $this->analytics = $_POST['analytics'];
         
         if( $fsvar->simple_save('inme_analytics', $this->analytics) )
         {
            $this->new_message('Datos guardados correctamente.');
         }
         else
         {
            $this->new_error_msg('Error al guardar los datos.');
         }
      }
   }
}
