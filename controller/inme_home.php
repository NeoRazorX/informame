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
require_model('inme_noticia_preview.php');

/**
 * Description of informame_home
 *
 * @author carlos
 */
class inme_home extends fs_controller
{
   public $codfuente;
   public $buscar;
   public $noticias;
   public $mostrar;
   public $preview;
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
                 . ' pestaña avanzado del <a href="index.php?page=admin_home#avanzado">panel de control</a>.');
      }
      
      $this->mostrar = 'portada';
      if( isset($_GET['mostrar']) )
      {
         $this->mostrar = $_GET['mostrar'];
      }
      
      $this->buscar = '';
      if( isset($_REQUEST['buscar']) )
      {
         $this->buscar = $_REQUEST['buscar'];
      }
      
      $this->codfuente = '';
      if( isset($_GET['codfuente']) )
      {
         $this->codfuente = $_GET['codfuente'];
      }
      
      $this->offset = 0;
      if( isset($_GET['offset']) )
      {
         $this->offset = intval($_GET['offset']);
      }
      
      $this->preview = new inme_noticia_preview();
      
      $noti = new inme_noticia_fuente();
      if($this->buscar != '')
      {
         $this->noticias = $noti->search($this->buscar, $this->offset);
      }
      else if($this->codfuente != '')
      {
         $this->noticias = $noti->all_from_fuente($this->codfuente, $this->offset);
      }
      else if($this->mostrar == 'portada')
      {
         $this->noticias = $noti->all($this->offset, 'publicada DESC');
      }
      else if($this->mostrar == 'populares')
      {
         $this->noticias = $noti->all($this->offset, 'popularidad DESC');
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
      
      $this->buscar = '';
      if( isset($_REQUEST['buscar']) )
      {
         $this->buscar = $_REQUEST['buscar'];
      }
      
      $this->codfuente = '';
      if( isset($_GET['codfuente']) )
      {
         $this->codfuente = $_GET['codfuente'];
      }
      
      $this->offset = 0;
      if( isset($_GET['offset']) )
      {
         $this->offset = intval($_GET['offset']);
      }
      
      $this->preview = new inme_noticia_preview();
      
      $noti = new inme_noticia_fuente();
      if($this->buscar != '')
      {
         $this->noticias = $noti->search($this->buscar, $this->offset);
      }
      else if($this->codfuente != '')
      {
         $this->noticias = $noti->all_from_fuente($this->codfuente, $this->offset);
      }
      else if($this->mostrar == 'portada')
      {
         $this->noticias = $noti->all($this->offset, 'publicada DESC');
      }
      else if($this->mostrar == 'populares')
      {
         $this->noticias = $noti->all($this->offset, 'popularidad DESC');
      }
      else
      {
         $this->noticias = $noti->all($this->offset);
      }
   }
   
   public function total_noticias()
   {
      $total = 0;
      
      $data = $this->db->select("SELECT COUNT(id) as total FROM inme_noticias_fuente;");
      if($data)
      {
         $total = intval($data[0]['total']);
      }
      
      return $total;
   }
}
