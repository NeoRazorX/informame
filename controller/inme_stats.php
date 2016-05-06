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
 * Description of inme_stats
 *
 * @author carlos
 */
class inme_stats extends fs_controller
{
   public $analytics;
   public $buscar;
   public $modrewrite;
   public $page_description;
   public $page_title;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Informame', 'informes');
   }
   
   protected function private_core()
   {
      
   }
   
   protected function public_core()
   {
      $this->template = 'inme_public/stats';
      $this->page_title = $this->empresa->nombrecorto;
      $this->page_description = 'Portal de noticias colaborativo, para los que huyen de la mafia de menéame.'
              . ' Exploramos la web para mostrarte los temas de actualidad.';
      
      $this->buscar = '';
      
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
   }
   
   public function stats_noticias()
   {
      $stats = array();
      
      $sql = "select DATE_FORMAT(fecha, '%d-%m-%Y') as fecha2,count(*) as total"
              . " from inme_noticias_fuente group by fecha2;";
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            $stats[$d['fecha2']]['num'] = intval($d['total']);
         }
      }
      
      $sql = "select DATE_FORMAT(fecha, '%d-%m-%Y') as fecha2,sum(likes+tweets+meneos) as total"
              . " from inme_noticias_fuente group by fecha2;";
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            $stats[$d['fecha2']]['popularidad'] = intval($d['total']);
         }
      }
      
      return $stats;
   }
   
   public function full_url()
   {
      return $this->empresa->web;
   }
   
   public function get_keywords()
   {
      $txt = '';
      
      return $txt;
   }
}
