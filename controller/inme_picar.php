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

require_model('inme_fuente.php');
require_model('inme_noticia_fuente.php');

/**
 * Description of inme_picar
 *
 * @author carlos
 */
class inme_picar extends fs_controller
{
   public $log;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Minar', 'informame', FALSE, FALSE);
   }
   
   protected function private_core()
   {
      $this->log = array();
      
      if( isset($_GET['picar']) )
      {
         $this->picar( mt_rand(0,3) );
      }
      else
      {
         $this->log[] = "Para estar <b>bien informados</b> primero debemos estar informados.";
         $this->log[] = "Pica un poco para obtener noticias.";
         $this->log[] = "Después las agrupamos, calculamos su popularidad y podemos pasar al siguiente paso..";
      }
   }
   
   protected function public_core()
   {
      $this->template = 'inme_public/picar';
      
      $this->log = array();
      
      if( isset($_GET['picar']) )
      {
         /// no nos interesa que los anónimos estén todo el día picando
         $opcion = mt_rand(0, 20);
         if($opcion < 10)
         {
            $this->picar($opcion);
         }
      }
      else
      {
         $this->log[] = "Para estar <b>bien informados</b> primero debemos estar informados.";
         $this->log[] = "Pica un poco para obtener noticias.";
         $this->log[] = "Después las agrupamos, calculamos su popularidad y podemos pasar al siguiente paso..";
      }
   }
   
   private function picar($opcion)
   {
      switch($opcion)
      {
         case 0:
            /// buscamos noticias en las fuente
            $fuente0 = new inme_fuente();
            $fuentes  = $fuente0->all();
            
            if($fuentes)
            {
               /// leemos de una fuente aleatoria
               $this->leer_fuente( $fuentes[ mt_rand(0, count($fuentes)-1) ] );
            }
            else
            {
               $this->new_error_msg('No hay ninguna fuente que picar. <a href="index.php?page=inme_fuentes">Añade alguna</a>.');
            }
            
            break;
         
         case 1:
            /// marcamos noticias como publicadas
            $this->log[] = 'Seleccionando noticias para portada...';
            
            $noti0 = new inme_noticia_fuente();
            foreach($noti0->all(0, 'popularidad DESC') as $noti)
            {
               if( is_null($noti->publicada) )
               {
                  $noti->publicada = date('d-m-Y H:i:s');
                  if( $noti->save() )
                  {
                     $this->log[] = 'Se ha publicado la noticia: <a href="'.$noti->url
                             .'" target="_blank">'.$noti->titulo.'</a>';
                  }
                  else
                  {
                     $this->log[] = 'Error al publicar la noticia: '.$noti->titulo;
                  }
               }
            }
            break;
         
         default:
            /// actualizamos popularidad de noticias
            $this->log[] = 'Recalculando popularidad de noticias...';
            
            $noti0 = new inme_noticia_fuente();
            
            /// escogemos un punto aleatorio en la lista de noticias
            $offset = mt_rand( 0, $this->total_noticias() );
            
            foreach($noti0->all($offset) as $noti)
            {
               if( is_null($noti->publicada) )
               {
                  $popularidad = $noti->popularidad();
                  
                  switch( mt_rand(0,9) )
                  {
                     case 0:
                        $noti->tweets = $this->tweet_count($noti->url);
                        break;
                     
                     case 1:
                        $noti->likes = $this->facebook_count($noti->url);
                        break;
                     
                     case 2:
                        $noti->meneos = $this->meneame_count($noti->url);
                        break;
                     
                     default:
                        break;
                  }
                  
                  if( $noti->save() )
                  {
                     if( $noti->popularidad() == $popularidad )
                     {
                        
                     }
                     else if( $noti->popularidad() >= $popularidad )
                     {
                        $this->log[] = '<a href="'.$noti->url.'" target="_blank">'.$noti->titulo
                                .'</a> <b>+'.abs($noti->popularidad() - $popularidad).'</b> popularidad.';
                     }
                     else
                     {
                        $this->log[] = '<a href="'.$noti->url.'" target="_blank">'.$noti->titulo
                                .'</a> <mark>-'.abs($noti->popularidad() - $popularidad).'</mark> popularidad.';
                     }
                  }
                  else
                  {
                     $this->log[] = 'Error al actualizada la popularidad de la noticia: '.$noti->titulo;
                  }
               }
            }
            break;
      }
   }
   
   /**
    * 
    * @param inme_fuente $fuente
    */
   private function leer_fuente(&$fuente)
   {
      $this->log[] = 'Examinando fuente <mark>'.$fuente->codfuente.'</mark>';
      
      try
      {
         $this->curl_save($fuente->url, 'tmp/'.$fuente->codfuente.'.xml', TRUE, TRUE);
         if( file_exists('tmp/'.$fuente->codfuente.'.xml') )
         {
            libxml_use_internal_errors(TRUE);
            $xml = simplexml_load_file('tmp/'.$fuente->codfuente.'.xml');
            if($xml)
            {
               /// intentamos leer las noticias
               if( $xml->channel->item )
               {
                  foreach($xml->channel->item as $item)
                  {
                     $this->nueva_noticia($item, $fuente);
                  }
               }
               else if( $xml->item )
               {
                  foreach($xml->item as $item)
                  {
                     $this->nueva_noticia($item, $fuente);
                  }
               }
               else if( $xml->feed->entry )
               {
                  foreach($xml->feed->entry as $item)
                  {
                     $this->nueva_noticia($item, $fuente);
                  }
               }
               else if( $xml->entry )
               {
                  foreach($xml->entry as $item)
                  {
                     $this->nueva_noticia($item, $fuente);
                  }
               }
               else
               {
                  $this->log[] = "Estructura irreconocible en la fuente: ".$fuente->codfuente;
               }
            }
            else
            {
               $this->log[] = "Imposible leer el xml.";
            }
         }
         else
         {
            $this->log[] = "Imposible leer el archivo: tmp/".$fuente->codfuente.'.xml';
         }
      }
      catch (Exception $ex)
      {
         $this->log[] = $ex->getMessage();
      }
   }
   
   /**
    * 
    * @param type $item
    * @param inme_fuente $fuente
    */
   private function nueva_noticia(&$item, &$fuente)
   {
      $url = NULL;
      
      /// intentamos obtener el enlace original de meneame
      $meneos = 0;
      foreach($item->children('meneame', TRUE) as $element)
      {
         if($element->getName() == 'url')
         {
            $url = (string)$element;
         }
         else if($element->getName() == 'votes')
         {
            $meneos = intval( (string)$element );
         }
      }
      
      if( is_null($url) )
      {
         /// intentamos obtener el enlace original de feedburner
         foreach($item->children('feedburner', TRUE) as $element)
         {
            if($element->getName() == 'origLink')
            {
               $url = (string)$element;
               break;
            }
         }
         
         /// intentamos leer el/los links
         if( is_null($url) AND $item->link)
         {
            foreach($item->link as $l)
            {
               if( mb_substr((string)$l, 0, 4) == 'http' )
               {
                  $url = (string)$l;
               }
               else
               {
                  if( $l->attributes()->rel == 'alternate' AND $l->attributes()->type == 'text/html' )
                  {
                     $url = (string)$l->attributes()->href;
                  }
                  else if( $l->attributes()->type == 'text/html' )
                  {
                     $url = (string)$l->attributes()->href;
                  }
               }
            }
         }
      }
      
      /// reemplazamos los &amp;
      $url = str_replace('&amp;', '&', $url);
      
      if( is_null($url) )
      {
         $this->log[] = 'No se ha podido encontrar la url en '.$item->asXML();
         return 0;
      }
      
      /// ¿Ya existe la noticia en la bd?
      $nueva = FALSE;
      $noti0 = new inme_noticia_fuente();
      $noticia = $noti0->get_by_url($url);
      if(!$noticia)
      {
         $nueva = TRUE;
         
         /// si no existe la creamos
         $noticia = new inme_noticia_fuente();
         $noticia->url = $url;
         $noticia->codfuente = $fuente->codfuente;
         
         if( $item->pubDate )
         {
            $noticia->fecha = date('d-m-Y H:i:s', min( array( strtotime( (string)$item->pubDate ), time() ) ) );
         }
         else if( $item->published )
         {
            $noticia->fecha = date('d-m-Y H:i:s', min( array( strtotime( (string)$item->published ), time() ) ) );
         }
         
         $noticia->titulo = (string)$item->title;
         
         if( $item->description )
         {
            $description = (string)$item->description;
         }
         else if( $item->content )
         {
            $description = (string)$item->content;
         }
         else if( $item->summary )
         {
            $description = (string)$item->summary;
         }
         else
         {
            $description = '';
            /// intentamos leer el espacio de nombres atom
            foreach($item->children('atom', TRUE) as $element)
            {
               if($element->getName() == 'summary')
               {
                  $description = (string)$element;
                  break;
               }
            }
            foreach($item->children('content', TRUE) as $element)
            {
               if($element->getName() == 'encoded')
               {
                  $description = (string)$element;
                  break;
               }
            }
         }
         
         if( $fuente->meneame() )
         {
            /// quitamos el latiguillo de las noticias de menéame
            $aux = '';
            for($i = 0; $i < mb_strlen($description); $i++)
            {
               if( mb_substr($description, $i, 4) == '</p>' )
               {
                  break;
               }
               else
                  $aux .= mb_substr($description, $i, 1);
            }
            $description = $aux;
         }
         
         /// eliminamos el html
         $description = strip_tags( html_entity_decode($description, ENT_QUOTES, 'UTF-8') );
         $noticia->texto = $description;
      }
      
      if($meneos > 0)
      {
         $noticia->meneos = $meneos;
      }
      
      if( $noticia->save() )
      {
         if($nueva)
         {
            $this->log[] = 'Encontrada noticia: <a href="'.$noticia->url
                    .'" target="_blank">'.$noticia->titulo.'</a>';
         }
      }
      else
      {
         $this->log[] = 'Error al procesar la noticia: '.$noticia->url;
      }
   }
   
   public function curl_download($url, $googlebot=TRUE, $timeout=5)
   {
      $ch0 = curl_init($url);
      curl_setopt($ch0, CURLOPT_TIMEOUT, $timeout);
      curl_setopt($ch0, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch0, CURLOPT_FOLLOWLOCATION, true);
      
      if($googlebot)
         curl_setopt($ch0, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
      
      $html = curl_exec($ch0);
      curl_close($ch0);
      
      return $html;
   }
   
   private function curl_save($url, $filename, $googlebot=FALSE, $followlocation=FALSE)
   {
      $ch = curl_init($url);
      $fp = fopen($filename, 'wb');
      curl_setopt($ch, CURLOPT_FILE, $fp);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_TIMEOUT, 5);
      
      if($followlocation)
      {
         curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
      }
      
      if($googlebot)
      {
         curl_setopt($ch, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
      }
      
      curl_exec($ch);
      curl_close($ch);
      fclose($fp);
   }
   
   private function tweet_count($link)
   {
      $json_string = $this->curl_download('http://urls.api.twitter.com/1/urls/count.json?url='.rawurlencode($link), FALSE);
      $json = json_decode($json_string, TRUE);
      
      return isset($json['count']) ? intval($json['count']) : 0;
   }
   
   private function facebook_count($link)
   {
      $json_string = $this->curl_download('http://api.facebook.com/restserver.php?method=links.getStats&format=json&urls='.
              rawurlencode($link), FALSE);
      $json = json_decode($json_string, TRUE);
      
      return isset($json[0]['total_count']) ? intval($json[0]['total_count']) : 0;
   }
   
   private function meneame_count($link)
   {
      $string = $this->curl_download('http://www.meneame.net/api/url.php?url='.rawurlencode($link), FALSE);
      $vars = explode(' ', $string);
      
      $meneos = 0;
      if( count($vars) == 4 )
      {
         $meneos = intval($vars[2]);
      }
      
      return $meneos;
   }
   
   private function total_noticias()
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
