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

require_model('inme_tema.php');

/**
 * Description of informame_temas
 *
 * @author carlos
 */
class inme_temas extends fs_controller
{
   public $offset;
   public $resultados;
   public $tema;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Temas', 'informame');
   }
   
   protected function private_core()
   {
      $this->offset = 0;
      if( isset($_GET['offset']) )
      {
         $this->offset = intval($_GET['offset']);
      }
      
      $this->tema = new inme_tema();
      $this->tema->cron_job();
      
      if( isset($_POST['codtema']) )
      {
         $this->tema->codtema = $_POST['codtema'];
         $this->tema->titulo = $_POST['titulo'];
         $this->tema->texto = $_POST['texto'];
         if( $this->tema->save() )
         {
            $this->new_message('Tema guardado correctamente.');
            header('Location: '.$this->tema->url());
         }
         else
            $this->new_error_msg('Error al guardar el tema.');
      }
      
      if( isset($_GET['order']) )
      {
         switch($_GET['order'])
         {
            case 'articulos':
               $this->resultados = $this->tema->all($this->offset, 'articulos DESC');
               break;
            
            case 'popularidad':
               $this->resultados = $this->tema->all($this->offset, 'popularidad DESC');
               break;
            
            default:
               $this->resultados = $this->tema->all($this->offset);
               break;
         }
      }
      else
      {
         $this->resultados = $this->tema->all($this->offset);
      }
   }
}
