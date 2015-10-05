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

require_model('inme_noticia_fuente.php');
require_model('inme_tema.php');

/**
 * Description of inme_editar_tema
 *
 * @author carlos
 */
class inme_editar_tema extends fs_controller
{
   public $noticias;
   public $tema;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Editar tema', 'informame');
   }
   
   protected function private_core()
   {
      $tema0 = new inme_tema();
      
      $this->tema = FALSE;
      if( isset($_REQUEST['cod']) )
      {
         $this->tema = $tema0->get($_REQUEST['cod']);
      }
      
      if($this->tema)
      {
         if( isset($_POST['titulo']) )
         {
            $this->tema->titulo = $_POST['titulo'];
            $this->tema->texto = $_POST['texto'];
            $this->tema->activo = isset($_POST['activo']);
            
            $this->tema->imagen = NULL;
            if($_POST['imagen'] != '')
            {
               $this->tema->imagen = $_POST['imagen'];
               $this->aplicar_cambios();
            }
            
            if( $this->tema->save() )
            {
               $this->new_message('Datos guardadados correctamente.');
            }
            else
            {
               $this->new_error_msg('Error al guardar los datos.');
            }
         }
         
         $noti = new inme_noticia_fuente();
         $this->noticias = $noti->all_from_keyword($this->tema->codtema);
      }
      else
      {
         $this->new_error_msg('Tema no encontrado.');
      }
   }
   
   private function aplicar_cambios()
   {
      $sql = "UPDATE inme_noticias_fuente SET preview = ".$this->tema->var2str($this->tema->imagen)
              ." WHERE (preview IS NULL OR preview = '') AND keywords LIKE '%[".$this->tema->codtema."]%'";
      $this->db->exec($sql);
   }
}
