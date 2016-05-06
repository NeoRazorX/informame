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

require_model('inme_noticia_fuente.php');
require_model('inme_noticia_preview.php');
require_model('inme_tema.php');

/**
 * Description of informame_home
 *
 * @author carlos
 */
class inme_home extends fs_controller
{
   public $analytics;
   public $codfuente;
   public $buscar;
   public $keyword;
   public $modrewrite;
   public $mostrar;
   public $mostrar_tema;
   public $noticias;
   public $offset;
   public $page_description;
   public $page_title;
   public $preview;
   public $temas_populares;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Portada', 'informame');
   }
   
   protected function private_core()
   {
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
      
      $this->keyword = '';
      if( isset($_GET['keyword']) )
      {
         $this->keyword = $_GET['keyword'];
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
      else if($this->keyword != '')
      {
         $this->noticias = $noti->all_from_keyword($this->keyword, $this->offset);
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
      
      $tema = new inme_tema();
      $this->mostrar_tema = FALSE;
      $this->temas_populares = $tema->populares();
      if( isset($_GET['keyword']) )
      {
         $this->mostrar_tema = $tema->get($_GET['keyword']);
      }
   }
   
   protected function public_core()
   {
      $this->template = 'inme_public/portada';
      $this->page_title = $this->empresa->nombrecorto;
      $this->page_description = 'Portal de noticias colaborativo, para los que huyen de la mafia de menéame.'
              . ' Exploramos la web para mostrarte los temas de actualidad.';
      
      $fsvar = new fs_var();
      $this->analytics = $fsvar->simple_get('inme_analytics');
      $this->modrewrite = $fsvar->simple_get('inme_modrewrite');
      
      if( isset($_GET['ok_cookies']) )
      {
         setcookie('ok_cookies', 'TRUE', time()+FS_COOKIES_EXPIRE, '/');
         
         $fslog = new fs_log();
         $fslog->tipo = 'cookies';
         $fslog->detalle = 'Se han aceptado las cookies';
         $fslog->ip = $_SERVER['REMOTE_ADDR'];
         $fslog->save();
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
      
      $this->keyword = '';
      if( isset($_GET['keyword']) )
      {
         $this->keyword = $_GET['keyword'];
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
      else if($this->keyword != '')
      {
         $this->noticias = $noti->all_from_keyword($this->keyword, $this->offset);
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
      
      $tema = new inme_tema();
      $this->mostrar_tema = FALSE;
      $this->temas_populares = $tema->populares();
      if( isset($_GET['keyword']) )
      {
         $this->mostrar_tema = $tema->get($_GET['keyword']);
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
   
   public function anterior_url()
   {
      return $this->url().'&mostrar='.$this->mostrar.'&buscar='.$this->buscar.'&codfuente='.
              $this->codfuente.'&keyword='.$this->keyword.'&offset='.($this->offset-FS_ITEM_LIMIT);
   }
   
   public function siguiente_url()
   {
      return $this->url().'&mostrar='.$this->mostrar.'&buscar='.$this->buscar.'&codfuente='.
              $this->codfuente.'&keyword='.$this->keyword.'&offset='.($this->offset+FS_ITEM_LIMIT);
   }
   
   public function full_url()
   {
      $url = $this->empresa->web;
      
      if( isset($_SERVER['SERVER_NAME']) )
      {
         if($_SERVER['SERVER_NAME'] == 'localhost')
         {
            $url = 'http://'.$_SERVER['SERVER_NAME'];
            
            if( isset($_SERVER['REQUEST_URI']) )
            {
               $aux = parse_url( str_replace('/index.php', '', $_SERVER['REQUEST_URI']) );
               $url .= $aux['path'];
            }
         }
      }
      
      return $url;
   }
   
   public function get_keywords()
   {
      $txt = '';
      
      if($this->temas_populares)
      {
         foreach($this->temas_populares as $i => $tema)
         {
            if($i == 0)
            {
               $txt = $tema->codtema;
            }
            else if($i < 9)
            {
               $txt .= ', '.$tema->codtema;
            }
         }
      }
      
      return $txt;
   }
}
