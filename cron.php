<?php

/*
 * This file is part of informame
 * Copyright (C) 2015-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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
require_model('inme_noticia_preview.php');
require_model('inme_tema.php');

class inme_cron
{
   private $db;
   
   public function __construct(&$db)
   {
      $this->db = $db;
      
      /// Forzamos una llamada web para picar
      $empresa = new empresa();
      $this->curl_download($empresa->web.'/index.php?page=inme_picar&hidden=TRUE');
      
      /// comprobamos los temas
      $this->comprobar_temas();
      
      /// procesamos noticias aleatorias
      $order = 'popularidad DESC';
      $opcion = mt_rand(0, 2);
      if($opcion == 0)
      {
         $order = 'fecha DESC';
      }
      else if($opcion == 1)
      {
         $order = 'publicada DESC';
      }
      
      echo "\nExaminamos noticias: ".$order."...\n";
      
      $noti0 = new inme_noticia_fuente();
      foreach($noti0->all(0, $order) as $noti)
      {
         $popularidad = $noti->popularidad();
         switch( mt_rand(0, 2) )
         {
            default:
               $noti->tweets = max( array($noti->tweets, $this->tweet_count($noti->url)) );
               break;
            
            case 1:
               $noti->likes = max( array($noti->likes, $this->facebook_count($noti->url)) );
               break;
            
            case 2:
               $noti->meneos = max( array($noti->meneos, $this->meneame_count($noti->url)) );
               break;
         }
         
         $this->preview_noticia($noti);
         
         if( $noti->popularidad() == $popularidad )
         {
            echo '=';
         }
         else
         {
            echo '.';
            $noti->save();
         }
      }
      
      /// comprobamos las fuentes
      $fuente0 = new inme_fuente();
      $fuente0->cron_job();
      
      /// Por último forzamos una llamada web para picar
      $this->curl_download($empresa->web.'/index.php?page=inme_picar&hidden=TRUE');
   }
   
   private function tweet_count($link)
   {
      $json_string = $this->curl_download('https://count.donreach.com/?url='.rawurlencode($link), FALSE);
      $json = json_decode($json_string, TRUE);
      
      if( isset($json['shares']['twitter']) )
      {
         return intval($json['shares']['twitter']);
      }
      else if( isset($json['total']) )
      {
         $total = -1;
         if( isset($json['shares']['google']) )
         {
            $total += intval($json['shares']['google']);
         }
         
         if( isset($json['shares']['linkedin']) )
         {
            $total += intval($json['shares']['linkedin']);
         }
         
         if( isset($json['shares']['reddit']) )
         {
            $total += intval($json['shares']['reddit']);
         }
         
         if($total >= 0)
         {
            return $total;
         }
         else
         {
            return intval($json['total']);
         }
      }
      else
      {
         return 0;
      }
   }
   
   private function facebook_count($link)
   {
      $json_string = $this->curl_download('https://count.donreach.com/?url='.rawurlencode($link), FALSE);
      $json = json_decode($json_string, TRUE);
      
      if( isset($json['shares']['facebook']) )
      {
         return intval($json['shares']['facebook']);
      }
      else if( isset($json['total']) )
      {
         $total = -1;
         if( isset($json['shares']['pinterest']) )
         {
            $total += intval($json['shares']['pinterest']);
         }
         
         if( isset($json['shares']['stumbleupon']) )
         {
            $total += intval($json['shares']['stumbleupon']);
         }
         
         if( isset($json['shares']['delicious']) )
         {
            $total += intval($json['shares']['delicious']);
         }
         
         if($total >= 0)
         {
            return $total;
         }
         else
         {
            return intval($json['total']);
         }
      }
      else
      {
         return 0;
      }
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
   
   public function curl_download($url, $googlebot=TRUE, $timeout=10)
   {
      $ch0 = curl_init($url);
      curl_setopt($ch0, CURLOPT_TIMEOUT, $timeout);
      curl_setopt($ch0, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch0, CURLOPT_FOLLOWLOCATION, true);
      
      if($googlebot)
      {
         curl_setopt($ch0, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
      }
      
      $html = curl_exec($ch0);
      curl_close($ch0);
      
      return $html;
   }
   
   /**
    * Busca imágentes/vídeos en la noticia.
    * @param inme_noticia_fuente $noti
    */
   private function preview_noticia(&$noti)
   {
      if($noti->editada)
      {
         /// si está editada, no hacemos nada
         echo 'E';
      }
      else if( is_null($noti->preview) )
      {
         /// primero intentamos asignar la imagen de un tema
         $tema0 = new inme_tema();
         foreach($noti->keywords() as $key)
         {
            $tema = $tema0->get($key);
            if($tema)
            {
               if($tema->imagen AND $tema->activo)
               {
                  $noti->preview = $tema->imagen;
                  $noti->save();
                  echo 'T';
                  break;
               }
            }
         }
         
         /// ahora buscamos una previsualización
         $preview = new inme_noticia_preview();
         $preview->load($noti->url, $noti->texto);
         if($preview->type)
         {
            /**
             * nos interesan previews de youtube y vimeo, así como imágenes de imgur,
             * PERO si es una imagen normal, solamente la queremos si no tenemos nada.
             */
            if( is_null($noti->preview) AND ($preview->type == 'imgur' OR $preview->type == 'image') )
            {
               $noti->preview = $preview->preview();
               $noti->texto .= "\n<div class='thumbnail'>\n<img src='".$preview->link."' alt='".$noti->titulo."'/>\n</div>";
               $noti->editada = TRUE;
               $noti->save();
            }
            else if($preview->type == 'youtube')
            {
               $imagen = $preview->preview();
               if($imagen)
               {
                  $noti->preview = $imagen;
                  $noti->texto = '<div class="embed-responsive embed-responsive-16by9">'
                          .'<iframe class="embed-responsive-item" src="//www.youtube-nocookie.com/embed/'.$preview->filename.'"></iframe>'
                          .'</div><br/>'.$noti->texto;
                  $noti->editada = TRUE;
                  $noti->save();
               }
            }
            else if($preview->type == 'vimeo')
            {
               $imagen = $preview->preview();
               if($imagen)
               {
                  $noti->preview = $imagen;
                  $noti->texto = '<div class="embed-responsive embed-responsive-16by9">'
                          .'<iframe class="embed-responsive-item" src="//player.vimeo.com/video/'.$preview->filename.'"></iframe>'
                          .'</div><br/>'.$noti->texto;
                  $noti->editada = TRUE;
                  $noti->save();
               }
            }
         }
         else if( is_null($noti->preview) )
         {
            /// exploramos la página para buscar imágenes
            $html = $preview->curl_download($noti->url);
            
            $txt_adicional = FALSE;
            
            $urls = array();
            if( preg_match_all('@<meta property="og:image" content="([^"]+)@', $html, $urls) )
            {
               foreach($urls[1] as $url)
               {
                  $preview->load($url);
                  if($preview->type AND stripos($url, 'logo') === FALSE AND $noti->preview != $preview->link)
                  {
                     $noti->preview = $preview->preview();
                     $noti->save();
                     
                     $txt_adicional = "\n<div class='thumbnail'>\n<img src='".$preview->link."' alt='".$noti->titulo."'/>\n</div>";
                     break;
                  }
               }
            }
            
            if(!$preview->type)
            {
               /// buscamos vídeos de youtube o vimeo
               $urls = array();
               if( preg_match_all('@((https?://)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)*)@', $html, $urls) )
               {
                  foreach($urls[0] as $url)
                  {
                     foreach( array('youtube', 'youtu.be', 'vimeo') as $domain )
                     {
                        if( strpos($url, $domain) !== FALSE )
                        {
                           $preview->load($url);
                           if( in_array($preview->type, array('youtube', 'vimeo')) )
                           {
                              $noti->preview = $preview->preview();
                              $noti->save();
                              
                              if($preview->type == 'youtube')
                              {
                                 $txt_adicional = '<div class="embed-responsive embed-responsive-16by9">'
                                            .'<iframe class="embed-responsive-item" src="//www.youtube-nocookie.com/embed/'.$preview->filename.'"></iframe>'
                                            .'</div>';
                              }
                              else if($preview->type == 'vimeo')
                              {
                                 $txt_adicional = '<div class="embed-responsive embed-responsive-16by9">'
                                            .'<iframe class="embed-responsive-item" src="//player.vimeo.com/video/'.$preview->filename.'"></iframe>'
                                            .'</div>';
                              }
                              break;
                           }
                        }
                     }
                     
                     if($preview->type)
                     {
                        break;
                     }
                  }
               }
            }
            
            if($txt_adicional)
            {
               $noti->texto .= $txt_adicional;
               $noti->save();
            }
         }
         
         if( !is_null($noti->preview) )
         {
            echo 'I';
         }
      }
   }
   
   private function comprobar_temas()
   {
      $noti0 = new inme_noticia_fuente();
      $tema0 = new inme_tema();
      
      /**
       * Leemos noticias y sacamos las keywords.
       */
      $keys = array();
      /// noticias de portadas
      foreach($noti0->all(0, 'publicada DESC') as $n)
      {
         foreach($n->keywords() as $key)
         {
            if( !in_array($key, $keys) )
            {
               $keys[] = $key;
            }
         }
      }
      /// últimas noticias
      foreach($noti0->all() as $n)
      {
         foreach($n->keywords() as $key)
         {
            if( !in_array($key, $keys) )
            {
               $keys[] = $key;
            }
         }
      }
      shuffle($keys);
      
      /**
       * Ahora buscamos los temas de esas keywords.
       */
      $temas = array();
      foreach($keys as $k)
      {
         $tema = $tema0->get($k);
         if($tema)
         {
            $temas[] = $tema;
         }
      }
      
      /**
       * Completamos descripciones de los temas con ayuda de la wikipedia.
       */
      $max = 10;
      foreach($temas as $tema)
      {
         if($max <= 0)
         {
            break;
         }
         else if( $tema->activo AND mb_strtolower($tema->texto, 'UTF8') == mb_strtolower($tema->titulo, 'UTF8') )
         {
            /// buscamos en la wikipedia
            $url = 'https://es.wikipedia.org/w/api.php?format=json&action=query&prop=extracts'
                    . '&exintro=&explaintext=&redirects=1&titles='.urlencode($tema->titulo);
            $html = file_get_contents($url);
            if($html)
            {
               $json = json_decode($html);
               if( isset($json->query) )
               {
                  if( isset($json->query->pages) )
                  {
                     foreach($json->query->pages as $page)
                     {
                        if( !isset($page->extract) )
                        {
                           /// caca
                        }
                        else if( mb_strlen($page->extract) > 100)
                        {
                           $tema->titulo = $page->title;
                           $tema->texto = $page->extract;
                           if( $tema->save() )
                           {
                              echo '- Wikipedia: '.$tema->codtema.' -';
                           }
                        }
                     }
                  }
               }
            }
            
            $max--;
         }
      }
      
      /**
       * Agregamos imágenes a los temas con ayuda de bing.
       */
      $max = 10;
      foreach($temas as $tema)
      {
         if($max <= 0)
         {
            break;
         }
         else if( is_null($tema->imagen) AND $tema->activo AND $tema->articulos > 10 )
         {
            $tema->imagen = $this->get_image_from_bing($tema->titulo);
            if($tema->imagen)
            {
               if( $tema->save() )
               {
                  echo '- Bing: '.$tema->codtema.' ';
                  
                  /// buscamos noticias relacionadas
                  foreach($noti0->all_from_keyword($tema->codtema) as $n)
                  {
                     if( is_null($n->preview) )
                     {
                        $n->preview = $tema->imagen;
                        $n->save();
                        echo '*';
                     }
                  }
                  
                  echo ' -';
               }
            }
            
            $max--;
         }
         else if( substr($tema->imagen, 0, 18) == 'http://i.imgur.com' )
         {
            /// cambiamos http por https para las imágenes de imgur
            $tema->imagen = str_replace('http://i.imgur.com', 'https://i.imgur.com', $tema->imagen);
            $tema->save();
         }
      }
      
      /**
       * Realizamos una busqueda en las noticias para asignarle tema
       */
      $sql = "SELECT * FROM inme_temas WHERE busqueda != '' ORDER BY popularidad DESC;";
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            $tema = new inme_tema($d);
            
            foreach($tema->busquedas() as $buscar)
            {
               echo '- buscar: '.$buscar.' ';
               foreach($noti0->search($buscar, 0, 'fecha DESC') as $no)
               {
                  $no->set_keyword($tema->codtema);
                  
                  if( is_null($no->preview) AND $tema->imagen )
                  {
                     $no->preview = $tema->imagen;
                     echo '*';
                  }
                  
                  $no->save();
               }
               echo ' -';
            }
         }
      }
      
      $max = 10;
      $total = $tema0->count();
      while($total > 0 AND $max > 0)
      {
         $tema0->cron_job();
         $total -= FS_ITEM_LIMIT;
         $max--;
         echo 'T';
      }
   }
   
   function get_image_from_bing($search)
   {
      $imagen = NULL;
      
      $url = "http://www.bing.com/images/search?pq=".urlencode( mb_strtolower($search) )."&count=50&q=".urlencode($search);
      $data = file_get_contents($url);
      if($data)
      {
         preg_match_all('@<img.+src="(.*)".*>@Uims', $data, $matches);
         foreach($matches[1] as $m)
         {
            if( substr($m, 0, 4) == 'http' )
            {
               $imagen = $m;
               break;
            }
         }
      }
      
      return $imagen;
   }
}

new inme_cron($db);