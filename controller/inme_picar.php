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
require_model('inme_noticia_preview.php');
require_model('inme_tema.php');

/**
 * Description of inme_picar
 *
 * @author carlos
 */
class inme_picar extends fs_controller
{
   public $log;
   private $noticia;
   public $page_description;
   public $recargar;
   private $tema;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Picar...', 'informame', FALSE, FALSE, TRUE);
   }
   
   protected function private_core()
   {
      $this->share_extensions();
      
      $this->log = array();
      $this->noticia = new inme_noticia_fuente();
      $this->recargar = 0;
      $this->tema = new inme_tema();
      
      if( !function_exists('curl_init') )
      {
         $this->new_error_msg('No se encuentra la extensión php-curl, tienes que instalarla.');
      }
      else if( isset($_GET['hidden']) )
      {
         $this->template = FALSE;
         $this->picar();
      }
      else if( isset($_GET['picar']) )
      {
         $this->picar();
         
         if( intval($_GET['picar']) >= 15 )
         {
            $this->recargar = intval($_GET['picar']);
         }
      }
      else
      {
         $this->log[] = "Para estar <b>bien informados</b> primero debemos estar informados.";
         $this->log[] = "Pica un poco para obtener noticias.";
         $this->log[] = "Después las agrupamos, calculamos su popularidad y podemos pasar al siguiente paso...";
      }
   }
   
   protected function public_core()
   {
      $this->template = 'inme_public/picar';
      $this->page_description = 'Picar noticias.';
      
      $this->log = array();
      $this->noticia = new inme_noticia_fuente();
      $this->tema = new inme_tema();
      
      if( !function_exists('curl_init') )
      {
         $this->new_error_msg('No se encuentra la extensión php-curl, tienes que instalarla.');
      }
      else if( isset($_GET['picar']) )
      {
         $this->picar();
      }
      else
      {
         $this->log[] = "Para estar <b>bien informados</b> primero debemos estar informados.";
         $this->log[] = "Pica un poco para obtener noticias.";
         $this->log[] = "Después las agrupamos, calculamos su popularidad y podemos pasar al siguiente paso...";
      }
   }
   
   private function share_extensions()
   {
      $fsext = new fs_extension();
      $fsext->name = 'iframe_home';
      $fsext->from = __CLASS__;
      $fsext->to = 'inme_home';
      $fsext->type = 'hidden_iframe';
      $fsext->params = '&hidden=TRUE';
      $fsext->save();
   }
   
   private function picar()
   {
      /// buscamos noticias en las fuentes
      $fuente0 = new inme_fuente();
      $fuentes = $fuente0->all('fcomprobada ASC');
      if($fuentes)
      {
         /// no leeremos fuentes que ya hayamos leido hace menos de 1 hora
         if( strtotime($fuentes[0]->fcomprobada) < time() - 3600 )
         {
            $this->leer_fuente($fuentes[0]);
         }
         else
         {
            /// si no hay fuentes que leer, hacemos otras cosas
            $opcion = mt_rand(0, 6);
            switch($opcion)
            {
               case 0:
                  /// marcamos noticias como publicadas
                  $this->log[] = 'Seleccionando noticias para portada...';
                  $seleccionadas = FALSE;
                  
                  foreach($this->noticia->all(0, 'popularidad DESC') as $noti)
                  {
                     if( is_null($noti->publicada) AND $noti->popularidad() > 1 )
                     {
                        $noti->publicada = date('d-m-Y H:i:s');
                        if( $noti->save() )
                        {
                           $seleccionadas = TRUE;
                           $this->log[] = 'Se ha publicado la noticia: <a href="'.$noti->edit_url()
                                   .'" target="_blank">'.$noti->titulo.'</a> <span class="badge">'
                                   .$noti->popularidad().'</span>';
                        }
                        else
                        {
                           $this->log[] = 'Error al publicar la noticia: '.$noti->titulo;
                        }
                     }
                     else
                     {
                        $noti->save();
                     }
                  }
                  
                  if(!$seleccionadas)
                  {
                     $this->log[] = 'Ninguna noticia seleccionada.';
                  }
                  
                  /// también podemos aprovechar para procesar temas y fuentes
                  $this->tema->cron_job();
                  $fuente0 = new inme_fuente();
                  $fuente0->cron_job();
                  break;
                  
               case 1:
               case 2:
               case 3:
                  $this->log[] = 'Buscamos imágenes en las noticias...';
                  $this->preview_noticias();
                  break;
               
               default:
                  /// actualizamos popularidad de noticias
                  $this->log[] = 'Recalculando popularidad de noticias...';
                  
                  /// escogemos un punto aleatorio en la lista de noticias
                  $offset = mt_rand( 0, max( array( 0 ,$this->total_noticias() - FS_ITEM_LIMIT ) ) );
                  
                  foreach($this->noticia->all($offset) as $noti)
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
                     
                     if( $noti->popularidad() == $popularidad )
                     {
                        
                     }
                     else if( $noti->save() )
                     {
                        if( $noti->popularidad() >= $popularidad )
                        {
                           $this->log[] = '<a href="'.$noti->edit_url().'" target="_blank">'.$noti->titulo
                                   .'</a> <b>+'.abs($noti->popularidad() - $popularidad).'</b> popularidad.';
                        }
                        else
                        {
                           $this->log[] = '<a href="'.$noti->edit_url().'" target="_blank">'.$noti->titulo
                                   .'</a> <mark>-'.abs($noti->popularidad() - $popularidad).'</mark> popularidad.';
                        }
                     }
                     else
                     {
                        $this->log[] = 'Error al actualizada la popularidad de la noticia: '.$noti->titulo;
                     }
                  }
                  break;
            }
         }
      }
      else
      {
         $this->new_error_msg('No hay ninguna fuente que picar. <a href="index.php?page=inme_fuentes">Añade alguna</a>.');
      }
   }
   
   /**
    * Examinamos la fuente y extraemos las noticias.
    * @param inme_fuente $fuente
    */
   private function leer_fuente(&$fuente)
   {
      $this->log[] = 'Examinando fuente <mark>'.$fuente->codfuente.'</mark>';
      
      $fuente->fcomprobada = date('d-m-Y H:i:s');
      $fuente->save();
      
      try
      {
         $filename = 'tmp/'.str_replace('/', '_', $fuente->codfuente).'.xml';
         $this->curl_save($fuente->url, $filename, TRUE, TRUE);
         if( file_exists($filename) )
         {
            libxml_use_internal_errors(TRUE);
            $xml = simplexml_load_file($filename);
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
      $meneame_link = FALSE;
      foreach($item->children('meneame', TRUE) as $element)
      {
         if($element->getName() == 'url')
         {
            $url = (string)$element;
            $meneame_link = (string)$item->link;
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
      $noticia = $this->noticia->get_by_url($url);
      if($noticia)
      {
         /// procesamos las keywords de categorías
         if($item->category)
         {
            foreach($item->category as $cat)
            {
               if( strlen( (string)$cat ) > 1 )
               {
                  $codtema = $this->sanitize_url( (string)$cat, 50 );
                  
                  $tema = $this->tema->get($codtema);
                  if(!$tema)
                  {
                     $tema = new inme_tema();
                     $tema->codtema = $codtema;
                     $tema->titulo = $tema->texto = (string)$cat;
                  }
                  
                  if($tema->activo)
                  {
                     if( !in_array($codtema, $noticia->keywords()) )
                     {
                        $tema->articulos++;
                     }
                     
                     if( $tema->save() )
                     {
                        if( is_null($noticia->preview) OR $noticia->preview == '' )
                        {
                           $noticia->preview = $tema->imagen;
                        }
                        
                        $noticia->set_keyword($codtema);
                     }
                  }
               }
            }
         }
      }
      else
      {
         $nueva = TRUE;
         
         /// si no existe la creamos
         $noticia = new inme_noticia_fuente();
         $noticia->url = $url;
         $noticia->codfuente = $fuente->codfuente;
         $noticia->nativa = $fuente->nativa;
         $noticia->parodia = $fuente->parodia;
         
         if( $item->pubDate )
         {
            $noticia->fecha = date('d-m-Y H:i:s', min( array( strtotime( (string)$item->pubDate ), time() ) ) );
         }
         else if( $item->published )
         {
            $noticia->fecha = date('d-m-Y H:i:s', min( array( strtotime( (string)$item->published ), time() ) ) );
         }
         
         $noticia->titulo = $this->true_text_break( (string)$item->title, 140 );
         
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
         
         /// eliminamos el html de la descripción
         $description = strip_tags( html_entity_decode($description, ENT_QUOTES, 'UTF-8') );
         $noticia->texto = trim($description);
         $noticia->resumen = $this->true_text_break($noticia->texto, 300);
         
         /// procesamos las keywords de categorías
         if($item->category)
         {
            foreach($item->category as $cat)
            {
               if( strlen( (string)$cat ) > 1 )
               {
                  $codtema = $this->sanitize_url( (string)$cat, 50 );
                  
                  $tema = $this->tema->get($codtema);
                  if(!$tema)
                  {
                     $tema = new inme_tema();
                     $tema->codtema = $codtema;
                     $tema->titulo = $tema->texto = (string)$cat;
                  }
                  
                  if($tema->activo)
                  {
                     $tema->articulos++;
                     if( $tema->save() )
                     {
                        if( is_null($noticia->preview) OR $noticia->preview == '' )
                        {
                           $noticia->preview = $tema->imagen;
                        }
                        
                        $noticia->set_keyword($codtema);
                     }
                  }
                  
                  /// ¿Parodia?
                  if( strpos((string)$cat, 'humor') !== FALSE )
                  {
                     $noticia->parodia = TRUE;
                  }
               }
            }
         }
         
         /// procesamos las keywords y las imágenes de media
         foreach($item->children('media', TRUE) as $element)
         {
            if($element->getName() == 'thumbnail')
            {
               $noticia->preview = (string)$element;
            }
            else if($element->getName() == 'keywords')
            {
               $aux = explode(',', (string)$element);
               if($aux)
               {
                  foreach($aux as $a)
                  {
                     if( strlen( (string)$a ) > 1 )
                     {
                        $codtema = $this->sanitize_url( (string)$a, 50 );
                        $tema = $this->tema->get($codtema);
                        if(!$tema)
                        {
                           $tema = new inme_tema();
                           $tema->codtema = $codtema;
                           $tema->titulo = $tema->texto = (string)$a;
                        }
                        
                        if($tema->activo)
                        {
                           $tema->articulos++;
                           if( $tema->save() )
                           {
                              if( is_null($noticia->preview) OR $noticia->preview == '' )
                              {
                                 $noticia->preview = $tema->imagen;
                              }
                              
                              $noticia->set_keyword($codtema);
                           }
                        }
                     }
                  }
               }
            }
         }
      }
      
      if($meneos > 0)
      {
         $noticia->meneos = $meneos;
         $noticia->meneame_link = $meneame_link;
      }
      
      if( $noticia->save() )
      {
         if($nueva)
         {
            $this->log[] = 'Encontrada noticia: <a href="'.$noticia->edit_url()
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
      {
         curl_setopt($ch0, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
      }
      
      $html = curl_exec($ch0);
      curl_close($ch0);
      
      return $html;
   }
   
   private function curl_save($url, $filename, $googlebot=FALSE, $followlocation=FALSE)
   {
      $ch = curl_init($url);
      $fp = fopen($filename, 'wb');
      if($fp)
      {
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
   
   private function preview_noticias()
   {
      $preview = new inme_noticia_preview();
      $offset = mt_rand(0, 100);
      $this->log[] = 'Comprobando noticias populares a partir de la '.$offset;
      
      foreach($this->noticia->all($offset, 'popularidad DESC') as $noti)
      {
         $preview->load($noti->url, $noti->texto.' '.$noti->preview);
         if($noti->editada)
         {
            /// si está editada, no hacemos nada
         }
         else if($preview->type)
         {
            if(!$noti->preview)
            {
               if($preview->type == 'image' OR $preview->type == 'imgur')
               {
                  $noti->preview = $preview->preview();
                  $noti->texto .= "\n<div class='thumbnail'>\n<img src='".$preview->link."' alt='".$noti->titulo."'/>\n</div>";
                  $noti->editada = TRUE;
                  $noti->save();
               }
               else if($preview->type == 'youtube')
               {
                  $noti->preview = $preview->preview();
                  $noti->texto = '<div class="embed-responsive embed-responsive-16by9">'
                          .'<iframe class="embed-responsive-item" src="//www.youtube-nocookie.com/embed/'.$preview->filename.'"></iframe>'
                          .'</div><br/>'.$noti->texto;
                  $noti->editada = TRUE;
                  $noti->save();
               }
               else if($preview->type == 'vimeo')
               {
                  $noti->preview = $preview->preview();
                  $noti->texto = '<div class="embed-responsive embed-responsive-16by9">'
                          .'<iframe class="embed-responsive-item" src="//player.vimeo.com/video/'.$preview->filename.'"></iframe>'
                          .'</div><br/>'.$noti->texto;
                  $noti->editada = TRUE;
                  $noti->save();
               }
            }
         }
         else
         {
            $txt_adicional = FALSE;
            
            $html = $preview->curl_download($noti->url);
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
                     $this->log[] = 'Encontrada imagen: <a href="'.$preview->link.'" target="_blank">'.$preview->link.'</a>';
                     
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
                              $this->log[] = 'Encontrado vídeo: <a href="'.$preview->link.'" target="_blank">'.$preview->link.'</a>';
                              
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
            else
            {
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
                        $this->log[] = 'Asignada imagen del tema '.$tema->titulo
                                .': <a href="'.$noti->edit_url().'" target="_blank">'.$noti->titulo.'</a>';
                        break;
                     }
                  }
               }
            }
         }
      }
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
      $desc = $this->tema->no_html($str);
      
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
}
