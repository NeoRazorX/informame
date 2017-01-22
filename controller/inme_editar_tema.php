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
require_model('inme_tema.php');

/**
 * Description of inme_editar_tema
 *
 * @author carlos
 */
class inme_editar_tema extends fs_controller
{
   public $allow_delete;
   public $noticias;
   public $tema;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Editar tema', 'informame', FALSE, FALSE);
   }
   
   protected function private_core()
   {
      /// ¿El usuario tiene permiso para eliminar en esta página?
      $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
      
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
            $this->tema->busqueda = $_POST['busqueda'];
            $this->tema->activo = isset($_POST['activo']);
            
            $this->tema->clean_keywords();
            $keys = explode(',', $_POST['keywords']);
            if($keys)
            {
               foreach($keys as $k)
               {
                  if($k != '')
                  {
                     $this->tema->set_keyword( $this->sanitize_url($k, 50) );
                  }
               }
            }
            
            $this->tema->imagen = NULL;
            if($_POST['imagen'] != '')
            {
               $this->tema->imagen = $_POST['imagen'];
               $this->aplicar_cambios();
            }
            
            if( $this->tema->save() )
            {
               $this->new_message('Datos guardadados correctamente.');
               $this->cache->delete('inme_temas_populares');
            }
            else
            {
               $this->new_error_msg('Error al guardar los datos.');
            }
         }
         else if( isset($_GET['bad_image']) )
         {
            $this->cambiar_imagen();
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
   
   private function sanitize_url($text, $len = 85)
   {
      $text = strtolower($text);
      $changes = array('/à/' => 'a', '/á/' => 'a', '/â/' => 'a', '/ã/' => 'a', '/ä/' => 'a',
          '/å/' => 'a', '/æ/' => 'ae', '/ç/' => 'c', '/è/' => 'e', '/é/' => 'e', '/ê/' => 'e',
          '/ë/' => 'e', '/ì/' => 'i', '/í/' => 'i', '/î/' => 'i', '/ï/' => 'i', '/ð/' => 'd',
          '/ñ/' => 'n', '/ò/' => 'o', '/ó/' => 'o', '/ô/' => 'o', '/õ/' => 'o', '/ö/' => 'o',
          '/ő/' => 'o', '/ø/' => 'o', '/ù/' => 'u', '/ú/' => 'u', '/û/' => 'u', '/ü/' => 'u',
          '/ű/' => 'u', '/ý/' => 'y', '/þ/' => 'th', '/ÿ/' => 'y', '/ñ/' => 'ny',
          '/&quot;/' => '-', '/&#39;/' => ''
      );
      $text = preg_replace(array_keys($changes), $changes, $text);
      $text = preg_replace('/[^a-z0-9]/i', '-', $text);
      $text = preg_replace('/-+/', '-', $text);
      
      if( substr($text, 0, 1) == '-' )
      {
         $text = substr($text, 1);
      }
      
      if( substr($text, -1) == '-' )
      {
         $text = substr($text, 0, -1);
      }
      
      return $text;
   }
   
   private function cambiar_imagen()
   {
      $this->tema->imagen = NULL;
      $txt = $this->tema->titulo;
      $num = mt_rand(0, 3);
      
      $url = "https://www.bing.com/images/search?pq=".urlencode( mb_strtolower($txt) )."&count=50&q=".urlencode($txt);
      $data = file_get_contents($url);
      if($data)
      {
         preg_match_all('@<img.+src="(.*)".*>@Uims', $data, $matches);
         foreach($matches[1] as $m)
         {
            if( substr($m, 0, 6) == 'https:' )
            {
               $this->tema->imagen = $m;
               
               if($num == 0)
               {
                  break;
               }
               else
               {
                  $num--;
               }
            }
         }
      }
      
      if( $this->tema->save() )
      {
         $this->new_message('Imagen cambiada correctamente.');
         $this->cache->delete('inme_temas_populares');
      }
      else
      {
         $this->new_error_msg('Error al cambiar la imagen.');
      }
   }
}
