<?php

/*
 * This file is part of informame
 * Copyright (C) 2015-2016  Carlos Garcia Gomez  neorazorx@gmail.com
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

require_model('inme_fuente.php');
require_model('inme_noticia_fuente.php');

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
         /// crear/editar fuente
         $fuente2 = $this->fuente->get($_POST['codfuente']);
         if(!$fuente2)
         {
            $fuente2 = new inme_fuente();
            $fuente2->codfuente = $_POST['codfuente'];
         }
         
         $fuente2->url = $_POST['url'];
         $fuente2->nativa = isset($_POST['nativa']);
         $fuente2->parodia = isset($_POST['parodia']);
         
         if( $fuente2->save() )
         {
            $this->new_message('Fuente '.$fuente2->codfuente.' guardada correctamente.');
         }
         else
            $this->new_error_msg('Error al guardar la fuente '.$fuente2->codfuente);
      }
      else if( isset($_GET['delete']) )
      {
         /// eliminar fuente
         $fuente2 = $this->fuente->get($_GET['delete']);
         if($fuente2)
         {
            if( $fuente2->delete() )
            {
               $this->new_message('Fuente '.$fuente2->codfuente.' eliminada correctamente.');
            }
            else
            {
               $this->new_error_msg('Error al eliminar la fuente '.$fuente2->codfuente);
            }
         }
         else
         {
            $this->new_error_msg('Fuente '.$_GET['delete'].' No encontrada');
         }
      }
      else
      {
         $this->fuente->cron_job();
      }
      
      $this->resultados = $this->fuente->all();
   }
}
