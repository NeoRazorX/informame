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
require_model('inme_tema.php');

class inme_cron
{
   public function __construct()
   {
      $order = 'popularidad DESC';
      if( mt_rand(0, 1) == 0 )
      {
         $order = 'fecha DESC';
      }
      
      $noti0 = new inme_noticia_fuente();
      foreach($noti0->all( mt_rand(0, 100), $order ) as $noti)
      {
         $popularidad = $noti->popularidad();
         switch( mt_rand(0,2) )
         {
            default:
            case 0:
               $noti->tweets = $this->tweet_count($noti->url);
               break;
            
            case 1:
               $noti->likes = $this->facebook_count($noti->url);
               break;
            
            case 2:
               $noti->meneos = $this->meneame_count($noti->url);
               break;
         }
         
         if( $noti->popularidad() == $popularidad )
         {
            echo '=';
         }
         else
         {
            $noti->save();
            echo '.';
         }
      }
      
      $fuente0 = new inme_fuente();
      $fuente0->cron_job();
      
      $tema0 = new inme_tema();
      $total = $tema0->count();
      while($total > 0)
      {
         $tema0->cron_job();
         $total -= FS_ITEM_LIMIT;
         echo 'T';
      }
      
      /// Por último forzamos una llamada web para picar
      $empresa = new empresa();
      $this->curl_download($empresa->web.'/index.php?page=inme_picar&picar=TRUE');
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
}

new inme_cron();