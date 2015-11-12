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
 * Description of informame_fuentes
 *
 * @author carlos
 */
class inme_fuentes extends fs_controller
{
   public $resultados;
   public $fuente;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Fuentes', 'informame');
   }
   
   protected function private_core()
   {
      $this->fuente = new inme_fuente();
      
      if( isset($_POST['codfuente']) )
      {
         /// crear/editar fuente
         $fuente2 = $this->fuente->get($_POST['codfuente']);
         if(!$fuente2)
         {
            $fuente2 = new inme_fuente();
            $fuente2->codfuente = $_POST['codfuente'];
         }
         
         $fuente2->url = $_POST['url'];
         $fuente2->nativa = isset($_POST['nativa']);
         $fuente2->parodia = isset($_POST['parodia']);
         
         if( $fuente2->save() )
         {
            $this->new_message('Fuente '.$fuente2->codfuente.' guardada correctamente.');
         }
         else
            $this->new_error_msg('Error al guardar la fuente '.$fuente2->codfuente);
      }
      else if( isset($_GET['delete']) )
      {
         /// eliminar fuente
         $fuente2 = $this->fuente->get($_GET['delete']);
         if($fuente2)
         {
            if( $fuente2->delete() )
            {
               $this->new_message('Fuente '.$fuente2->codfuente.' eliminada correctamente.');
            }
            else
            {
               $this->new_error_msg('Error al eliminar la fuente '.$fuente2->codfuente);
            }
         }
         else
         {
            $this->new_error_msg('Fuente '.$_GET['delete'].' No encontrada');
         }
      }
      else if( isset($_GET['import']) )
      {
         $this->importar_de_feedstorm($_GET['import']);
      }
      else
      {
         $this->fuente->cron_job();
      }
      
      $this->resultados = $this->fuente->all();
   }
   
   private function importar_de_feedstorm($web = 'locierto.es')
   {
      switch($web)
      {
         case 'kelinux.net':
            $html = $this->curl_download('http://www.kelinux.net/export.php');
            break;
         
         default:
            $html = $this->curl_download('http://www.locierto.es/export.php');
            break;
      }
      
      if($html)
      {
         $xml = simplexml_load_string($html);
         if($xml)
         {
            if( $xml->item )
            {
               $fuentes = 0;
               $noticias = 0;
               $urls = array();
               
               /// importamos fuentes
               foreach($xml->item as $item)
               {
                  $url = base64_decode( (string)$item->feed );
                  
                  if( !in_array($url, $urls) )
                  {
                     if( $this->fuente->get_by_url($url) )
                     {
                        /// ya existe la fuente
                     }
                     else
                     {
                        $aux = explode('/', substr( str_replace('www.', '', $url), 7));
                        if($aux)
                        {
                           $fuente = new inme_fuente();
                           
                           if( $this->fuente->get($aux[0]) )
                           {
                              $fuente->codfuente = $this->random_string(10);
                           }
                           else
                           {
                              $fuente->codfuente = $aux[0];
                           }
                           
                           $fuente->url = $url;
                           
                           if( $fuente->save() )
                           {
                              $urls[] = $url;
                              $fuentes++;
                           }
                           else
                           {
                              $this->new_error_msg('Error al añadir la fuente '.$url);
                           }
                        }
                     }
                  }
               }
               
               /// importamos las noticias más populares
               /*
               $noti0 = new inme_noticia_fuente();
               foreach($xml->story as $item)
               {
                  $noti2 = $noti0->get_by_url( base64_decode( (string)$item->link ) );
                  if(!$noti2)
                  {
                     $noti2 = new inme_noticia_fuente();
                     $noti2->titulo = base64_decode( (string)$item->title );
                     $noti2->texto = $noti2->resumen = base64_decode( (string)$item->description );
                     $noti2->url = base64_decode( (string)$item->link );
                     $noti2->publicada = $noti2->fecha = date('d-m-Y H:i:s', intval( (string)$item->date ));
                     $noti2->save();
                     $noticias++;
                  }
               }
                * 
                */
               
               $this->new_message($fuentes.' fuentes y '.$noticias.' noticias importadas.');
            }
            else
               $this->new_error_msg("Estructura irreconocible.");
         }
         else
            $this->new_error_msg("Error al leer el archivo.");
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
}
