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
 * Description of inme_editar_noticia
 *
 * @author carlos
 */
class inme_editar_noticia extends fs_controller
{
   public $allow_delete;
   public $analytics;
   public $buscar;
   public $modrewrite;
   public $noticia;
   public $page_description;
   public $page_title;
   public $relacionada;
   public $temas;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Editar noticia', 'informame', FALSE, FALSE);
   }
   
   protected function private_core()
   {
      /// ¿El usuario tiene permiso para eliminar en esta página?
      $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
      
      $fsvar = new fs_var();
      $this->analytics = $fsvar->simple_get('inme_analytics');
      $this->modrewrite = $fsvar->simple_get('inme_modrewrite');
      
      $this->buscar = '';
      $this->noticia = FALSE;
      $this->relacionada = FALSE;
      $this->temas = array();
      
      $noti0 = new inme_noticia_fuente();
      if( isset($_GET['delete']) )
      {
         $delete = $noti0->get($_GET['delete']);
         if($delete)
         {
            if( $delete->delete() )
            {
               $this->new_error_msg('Noticia eliminada correctamente.');
            }
         }
      }
      
      if( isset($_REQUEST['id']) )
      {
         $this->noticia = $noti0->get($_REQUEST['id']);
      }
      
      if($this->noticia)
      {
         if( isset($_POST['url']) )
         {
            $this->noticia->editada = TRUE;
            $this->noticia->url = $_POST['url'];
            $this->noticia->titulo = $_POST['titulo'];
            $this->noticia->resumen = substr($_POST['resumen'], 0, 300);
            $this->noticia->texto = $_POST['texto'];
            
            $this->noticia->id_relacionada = null;
            if($_POST['id_relacionada'] != '')
            {
               $this->noticia->id_relacionada = intval($_POST['id_relacionada']);
            }
            
            $this->noticia->preview = $_POST['preview'];
            
            $this->noticia->clean_keywords();
            $keys = explode(',', $_POST['keywords']);
            if($keys)
            {
               $tema0 = new inme_tema();
               
               foreach($keys as $k)
               {
                  if($k != '')
                  {
                     $codtema = $this->sanitize_url($k, 50);
                     
                     $tema = $tema0->get($codtema);
                     if(!$tema)
                     {
                        $tema = new inme_tema();
                        $tema->codtema = $codtema;
                        $tema->titulo = $tema->texto = $k;
                     }
                     
                     if( $tema->save() )
                     {
                        $this->noticia->set_keyword($codtema);
                     }
                  }
               }
            }
            
            $this->noticia->destacada = isset($_POST['destacada']);
            $this->noticia->nativa = isset($_POST['nativa']);
            $this->noticia->parodia = isset($_POST['parodia']);
            
            if( $this->noticia->save() )
            {
               $this->new_message('Datos modificados correctamente.');
            }
            else
               $this->new_error_msg('Error al guardar los datos.');
         }
         
         $this->page->title = $this->noticia->titulo;
         
         if( !is_null($this->noticia->id_relacionada) )
         {
            $this->relacionada = $noti0->get($this->noticia->id_relacionada);
         }
         
         $tema0 = new inme_tema();
         foreach($this->noticia->keywords() as $key)
         {
            $tema = $tema0->get($key);
            if($tema)
            {
               if($tema->activo)
               {
                  $this->temas[] = $tema;
                  
                  /// si no hay una preview, usamos la de un tema
                  if($tema->imagen AND !$this->noticia->preview)
                  {
                     $this->noticia->preview = $tema->imagen;
                     $this->noticia->save();
                  }
               }
            }
            else
            {
               $this->new_error_msg('Tema '.$key.' no encontrado.');
            }
         }
      }
      else
         $this->new_error_msg('Noticia no encontrada.');
   }
   
   protected function public_core()
   {
      $this->template = 'inme_public/editar_noticia';
      $this->page_description = 'Detalle de la noticia.';
      
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
      
      $this->noticia = FALSE;
      $this->relacionada = FALSE;
      $this->temas = array();
      
      $noti0 = new inme_noticia_fuente();
      if( isset($_REQUEST['id']) )
      {
         $this->noticia = $noti0->get($_REQUEST['id']);
      }
      else if( isset($_REQUEST['permalink']) )
      {
         $this->noticia = $noti0->get_by_permalink($_REQUEST['permalink']);
      }
      
      if($this->noticia)
      {
         $this->page_title = $this->noticia->titulo;
         $this->page_description = $this->true_text_break($this->noticia->resumen, 140);
         
         if( !is_null($this->noticia->id_relacionada) )
         {
            $this->relacionada = $noti0->get($this->noticia->id_relacionada);
         }
         
         $tema0 = new inme_tema();
         foreach($this->noticia->keywords() as $key)
         {
            $tema = $tema0->get($key);
            if($tema)
            {
               if($tema->activo)
               {
                  $this->temas[] = $tema;
                  
                  /// si no hay una preview, usamos la de un tema
                  if($tema->imagen AND !$this->noticia->preview)
                  {
                     $this->noticia->preview = $tema->imagen;
                     $this->noticia->save();
                  }
               }
            }
            else
            {
               $this->new_error_msg('Tema '.$key.' no encontrado.');
            }
         }
      }
      else
         $this->new_error_msg('Noticia no encontrada.');
   }
   
   private function sanitize_url($text, $len = 85)
   {
      $text = strtolower( $this->true_text_break($text, $len) );
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
   
   private function true_text_break($str, $max_t_width=500)
   {
      $desc = $this->noticia->no_html($str);
      
      if( mb_strlen($desc) <= $max_t_width )
      {
         return trim($desc);
      }
      else
      {
         $description = '';
         
         foreach(explode(' ', $desc) as $aux)
         {
            if( mb_strlen($description.' '.$aux) < $max_t_width-3 )
            {
               if($description == '')
               {
                  $description = $aux;
               }
               else
                  $description .= ' ' . $aux;
            }
            else
               break;
         }
         
         return trim($description).'...';
      }
   }
   
   public function populares()
   {
      $lista = array();
      foreach($this->noticia->all(0, 'popularidad DESC') as $i => $noti)
      {
         if($i < 10)
         {
            $lista[] = $noti;
         }
      }
      
      return $lista;
   }
   
   public function full_url()
   {
      return $this->empresa->web;
   }
   
   public function get_keywords()
   {
      $txt = '';
      
      if($this->noticia)
      {
         $txt = $this->noticia->keywords(TRUE);
      }
      
      return $txt;
   }
   
   public function twitter_url()
   {
      if($this->noticia)
      {
         return 'https://twitter.com/share?url='.urlencode( $this->empresa->web.'/'.$this->noticia->url($this->modrewrite) ).
            '&amp;text='.urlencode( html_entity_decode($this->noticia->titulo) );
      }
      else
         return 'https://twitter.com/share';
   }
   
   public function facebook_url()
   {
      if($this->noticia)
      {
         return 'http://www.facebook.com/sharer.php?s=100&amp;p[title]='.urlencode( html_entity_decode($this->noticia->titulo) ).
            '&amp;p[url]='.urlencode( $this->empresa->web.'/'.$this->noticia->url($this->modrewrite) );
      }
      else
         return 'http://www.facebook.com/sharer.php';
   }
}
