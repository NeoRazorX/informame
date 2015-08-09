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

/**
 * Description of informame_home
 *
 * @author carlos
 */
class inme_home extends fs_controller
{
   public $noticias;
   public $mostrar;
   public $offset;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Portada', 'informame');
   }
   
   protected function private_core()
   {
      if(FS_HOMEPAGE != __CLASS__)
      {
         $this->new_advice('Debes poner <b>'.__CLASS__.'</b> como portada en la'
                 . ' pestaña avanzado del <a href="index.php?page=admin_home">panel de control</a>.');
      }
      
      $this->mostrar = 'portada';
      if( isset($_GET['mostrar']) )
      {
         $this->mostrar = $_GET['mostrar'];
      }
      
      $this->offset = 0;
      if( isset($_GET['offset']) )
      {
         $this->offset = intval($_GET['offset']);
      }
      
      $noti = new inme_noticia_fuente();
      
      if($this->mostrar == 'portada')
      {
         $this->noticias = $noti->all($this->offset, 'publicada DESC');
      }
      else
      {
         $this->noticias = $noti->all($this->offset);
      }
   }
   
   protected function public_core()
   {
      $this->template = 'inme_public/portada';
      
      $this->mostrar = 'portada';
      if( isset($_GET['mostrar']) )
      {
         $this->mostrar = $_GET['mostrar'];
      }
      
      $this->offset = 0;
      if( isset($_GET['offset']) )
      {
         $this->offset = intval($_GET['offset']);
      }
      
      $noti = new inme_noticia_fuente();
      
      if($this->mostrar == 'portada')
      {
         $this->noticias = $noti->all($this->offset, 'publicada DESC');
      }
      else
      {
         $this->noticias = $noti->all($this->offset);
      }
   }
}
