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
   public $inme_config;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Informame', 'admin');
   }
   
   protected function private_core()
   {
      $this->check_menu();
      
      $fsvar = new fs_var();
      $this->inme_config = array(
          'inme_analytics' => '',
          'inme_modrewrite' => '0',
      );
      $this->inme_config = $fsvar->array_get($this->inme_config, FALSE);
      
      if( isset($_POST['analytics']) )
      {
         $this->inme_config['inme_analytics'] = $_POST['analytics'];
         $this->inme_config['inme_modrewrite'] = $_POST['modrewrite'];
         
         $this->empresa->web = $_POST['web'];
         $this->empresa->save();
         
         if( $fsvar->array_save($this->inme_config) )
         {
            $this->new_message('Datos guardados correctamente.');
         }
         else
         {
            $this->new_error_msg('Error al guardar los datos.');
         }
      }
      else if( isset($_GET['htaccess']) )
      {
         $this->save_htaccess();
      }
   }
   
   private function save_htaccess()
   {
      $txt = file_get_contents('htaccess-sample');
      $txt .= file_get_contents('plugins/informame/htaccess-sample');
      
      if( file_put_contents('.htaccess', $txt) )
      {
         $this->new_message('Archivo .htaccess modificado correctamente.', TRUE);
      }
      else
      {
         $this->new_error_msg('Error al modificar el archivo .htaccess');
      }
   }
   
   private function check_menu()
   {
      if( !$this->page->get('inme_home') )
      {
         if( file_exists(__DIR__) )
         {
            /// activamos las páginas del plugin
            foreach( scandir(__DIR__) as $f)
            {
               if( is_string($f) AND strlen($f) > 0 AND !is_dir($f) AND $f != __CLASS__.'.php' )
               {
                  $page_name = substr($f, 0, -4);
                  
                  if($page_name != 'inme_sitemap')
                  {
                     require_once __DIR__.'/'.$f;
                     $new_fsc = new $page_name();
                     
                     if( !$new_fsc->page->save() )
                     {
                        $this->new_error_msg("Imposible guardar la página ".$page_name);
                     }
                     
                     unset($new_fsc);
                  }
               }
            }
         }
         else
         {
            $this->new_error_msg('No se encuentra el directorio '.__DIR__);
         }
         
         $this->load_menu(TRUE);
      }
   }
   
   public function stats_cookies()
   {
      $stats = array();
      $sql = "select DATE_FORMAT(fecha, '%Y-%m') as fecha2,count(id) as total from fs_logs"
              . " where tipo = 'cookies' group by fecha2 order by fecha2 asc;";
      
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            $stats[$d['fecha2']] = intval($d['total']);
         }
      }
      
      return $stats;
   }
   
   public function stats_picar()
   {
      $stats = array();
      $sql = "select DATE_FORMAT(fecha, '%Y-%m') as fecha2,count(id) as total from fs_logs"
              . " where tipo = 'picar' group by fecha2 order by fecha2 asc;";
      
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            $stats[$d['fecha2']] = intval($d['total']);
         }
      }
      
      return $stats;
   }
}
