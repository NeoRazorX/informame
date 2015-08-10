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

/**
 * Description of inme_noticia_fuente
 *
 * @author carlos
 */
class inme_noticia_fuente extends fs_model
{
   public $id;
   public $url;
   public $titulo;
   public $texto;
   public $fecha;
   public $publicada;
   public $codfuente;
   public $likes;
   public $tweets;
   public $meneos;
   private $popularidad;
   private $keywords;
   public $preview;
   
   public function __construct($n = FALSE)
   {
      parent::__construct('inme_noticias_fuente', 'plugins/informame/');
      if($n)
      {
         $this->id = $this->intval($n['id']);
         $this->url = $n['url'];
         $this->titulo = $n['titulo'];
         $this->texto = $n['texto'];
         $this->fecha = date('d-m-Y H:i:s', strtotime($n['fecha']));
         
         $this->publicada = NULL;
         if($n['publicada'])
         {
            $this->publicada = date('d-m-Y H:i:s', strtotime($n['publicada']));
         }
         
         $this->codfuente = $n['codfuente'];
         $this->likes = intval($n['likes']);
         $this->tweets = intval($n['tweets']);
         $this->meneos = intval($n['meneos']);
         $this->popularidad = intval($n['popularidad']);
         $this->keywords = $n['keywords'];
         $this->preview = $n['preview'];
      }
      else
      {
         $this->id = NULL;
         $this->url = NULL;
         $this->titulo = NULL;
         $this->texto = NULL;
         $this->fecha = date('d-m-Y H:i:s');
         $this->publicada = NULL;
         $this->codfuente = NULL;
         $this->likes = 0;
         $this->tweets = 0;
         $this->meneos = 0;
         $this->popularidad = 0;
         $this->keywords = '';
         $this->preview = '';
      }
   }
   
   protected function install()
   {
      /// forzamos la comprobaciones de la tabla de fuentes
      new inme_fuente();
      
      return '';
   }
   
   public function popularidad()
   {
      $tclics = $this->tweets + $this->likes + $this->meneos;
      $dias = 1 + intval( (time() - strtotime($this->fecha)) / 86400 );
      $semanas = pow(2, intval($dias/7));
      
      if( strlen($this->titulo) < 2 OR strlen($this->texto) < 10 )
      {
         /// si el título o el texto es muy corto, no nos interesa valorarlo.
         $this->popularidad = 0;
      }
      else if($tclics > 0)
      {
         /// la popularidad debe bajar con el paso del tiempo
         $this->popularidad = intval( $tclics / $dias * $semanas );
      }
      else
         $this->popularidad = 0;
      
      return $this->popularidad;
   }
   
   public function keywords()
   {
      $keys = array();
      
      $aux = explode(',', $this->keywords);
      if($aux)
      {
         foreach($aux as $i => $value)
         {
            $keys[] = str_replace( array('[',']') , array('',''), $value);
         }
      }
      
      return $keys;
   }
   
   public function set_keyword($k)
   {
      if($this->keywords == '')
      {
         $this->keywords = '['.$k.']';
      }
      else
      {
         $this->keywords .= ',['.$k.']';
      }
   }
   
   public function get($id)
   {
      $data = $this->db->select("SELECT * FROM inme_noticias_fuente WHERE id = ".$this->var2str($id).";");
      if($data)
      {
         return new inme_noticia_fuente($data[0]);
      }
      else
      {
         return FALSE;
      }
   }
   
   public function get_by_url($url)
   {
      $data = $this->db->select("SELECT * FROM inme_noticias_fuente WHERE url = ".$this->var2str($url).";");
      if($data)
      {
         return new inme_noticia_fuente($data[0]);
      }
      else
      {
         return FALSE;
      }
   }
   
   public function exists()
   {
      if( is_null($this->id) )
      {
         return FALSE;
      }
      else
      {
         return $this->db->select("SELECT * FROM inme_noticias_fuente WHERE id = ".$this->var2str($this->id).";");
      }
   }
   
   public function save()
   {
      /// calculamos la popularidad
      $this->popularidad();
      
      if( $this->exists() )
      {
         $sql = "UPDATE inme_noticias_fuente SET url = ".$this->var2str($this->url)
                 .", titulo = ".$this->var2str($this->titulo)
                 .", texto = ".$this->var2str($this->texto)
                 .", fecha = ".$this->var2str($this->fecha)
                 .", publicada = ".$this->var2str($this->publicada)
                 .", codfuente = ".$this->var2str($this->codfuente)
                 .", likes = ".$this->var2str($this->likes)
                 .", tweets = ".$this->var2str($this->tweets)
                 .", meneos = ".$this->var2str($this->meneos)
                 .", popularidad = ".$this->var2str($this->popularidad)
                 .", keywords = ".$this->var2str($this->keywords)
                 .", preview = ".$this->var2str($this->preview)
                 ."  WHERE id = ".$this->var2str($this->id).";";
         
         return $this->db->exec($sql);
      }
      else
      {
         $sql = "INSERT INTO inme_noticias_fuente (url,titulo,texto,fecha,publicada"
                 . ",codfuente,likes,tweets,meneos,popularidad,keywords,preview) VALUES ("
                 .$this->var2str($this->url).","
                 .$this->var2str($this->titulo).","
                 .$this->var2str($this->texto).","
                 .$this->var2str($this->fecha).","
                 .$this->var2str($this->publicada).","
                 .$this->var2str($this->codfuente).","
                 .$this->var2str($this->likes).","
                 .$this->var2str($this->texto).","
                 .$this->var2str($this->meneos).","
                 .$this->var2str($this->popularidad).","
                 .$this->var2str($this->keywords).","
                 .$this->var2str($this->preview).");";
         
         if( $this->db->exec($sql) )
         {
            $this->id = $this->db->lastval();
            return TRUE;
         }
         else
         {
            return FALSE;
         }
      }
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM inme_noticias_fuente WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function all($offset = 0, $order = 'fecha DESC')
   {
      $nlist = array();
      
      $data = $this->db->select_limit("SELECT * FROM inme_noticias_fuente ORDER BY ".$order, FS_ITEM_LIMIT, $offset);
      if($data)
      {
         foreach($data as $d)
            $nlist[] = new inme_noticia_fuente($d);
      }
      
      return $nlist;
   }
   
   public function all_from_fuente($codfuente, $offset = 0)
   {
      $nlist = array();
      $sql = "SELECT * FROM inme_noticias_fuente WHERE codfuente = ".$this->var2str($codfuente)." ORDER BY fecha DESC";
      
      $data = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
      if($data)
      {
         foreach($data as $d)
            $nlist[] = new inme_noticia_fuente($d);
      }
      
      return $nlist;
   }
   
   public function search($query, $offset = 0)
   {
      $nlist = array();
      $query = $this->no_html( strtolower($query) );
      $sql = "SELECT * FROM inme_noticias_fuente WHERE lower(titulo) LIKE '%".$query."%'"
              . " OR lower(texto) LIKE '%".$query."%'  ORDER BY popularidad DESC";
      
      $data = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
      if($data)
      {
         foreach($data as $d)
            $nlist[] = new inme_noticia_fuente($d);
      }
      
      return $nlist;
   }
}
