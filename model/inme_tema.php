<?php

/*
 * This file is part of informame
 * Copyright (C) 2015  Carlos Garcia Gomez  neorazorx@gmail.com
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
 * Description of inme_tema
 *
 * @author carlos
 */
class inme_tema extends fs_model
{
   /**
    * Clave primaria. Varchar (50)
    * @var type 
    */
   public $codtema;
   public $titulo;
   public $texto;
   private $keywords;
   public $imagen;
   public $articulos;
   public $popularidad;
   public $activo;
   
   public function __construct($t = FALSE)
   {
      parent::__construct('inme_temas');
      if($t)
      {
         $this->codtema = $t['codtema'];
         $this->titulo = $t['titulo'];
         $this->texto = $t['texto'];
         $this->keywords = $t['keywords'];
         $this->imagen = $t['imagen'];
         $this->articulos = intval($t['articulos']);
         $this->popularidad = intval($t['popularidad']);
         $this->activo = $this->str2bool($t['activo']);
      }
      else
      {
         $this->codtema = NULL;
         $this->titulo = '';
         $this->texto = '';
         $this->keywords = '';
         $this->imagen = NULL;
         $this->articulos = 0;
         $this->popularidad = 0;
         $this->activo = TRUE;
      }
   }
   
   protected function install()
   {
      return "INSERT INTO inme_temas (codtema,titulo,texto,keywords,imagen,activo) VALUES"
              . " ('espanya','España','España (Reino de España)','[espanya],[spain]','http://i.imgur.com/nDoxKF3.jpg',true)"
              . ",('corrupcion','Corrupción','Corrupción','[corrupcion]','http://i.imgur.com/wYe54PC.jpg',true)"
              . ",('ee-uu','EE.UU','Estados Unidos de América','[estados-unidos],[usa],[ee-uu],[eeuu]','http://i.imgur.com/MsZyxdq.jpg',true)"
              . ",('alemania','Alemania','Alemania','[alemania]','http://i.imgur.com/I8f9WXM.jpg',true)"
              . ",('china','China','China','[china]','http://i.imgur.com/T5KsW3L.jpg',true)"
              . ",('grafeno','Grafeno','Grafeno','[grafeno]','http://i.imgur.com/jjlcWYu.jpg',true)"
              . ",('grecia','Grecia','Grecia','[grecia]','http://i.imgur.com/FyyQJho.jpg',true)"
              . ",('isis','ISIS','ISIS','[isis]','http://i.imgur.com/qXgdYox.jpg',true)"
              . ",('israel','Israel','Israel','[israel]','http://i.imgur.com/2uRAhdA.png',true)"
              . ",('linux','Linux','Linux','[linux]','http://i.imgur.com/zF5yVoQ.png',true)"
              . ",('rusia','Rusia','Rusia','[rusia]','http://i.imgur.com/7WZu7fl.jpg',true)"
              . ",('venezuela','Venezuela','Venezuela','[venezuela]','http://i.imgur.com/jAB2UDd.jpg',true)"
              . ",('microsoft','Microsoft','Microsoft','[microsoft]','http://i.imgur.com/LLX8ddu.jpg',true)"
              . ",('google','Google','Google','[google]','http://i.imgur.com/Gh7Ib2o.png',true)"
              . ",('apple','Apple','Apple','[apple]','http://i.imgur.com/Qttksz6.jpg',true)"
              . ",('nazis','Nazismo','Nazismo','[nazis],[nazismo],[hitler]','http://i.imgur.com/WYdIkd8.png',true)"
              . ",('pp','Partido Popular','Partido Popular','[pp]','http://i.imgur.com/IjmbCcA.jpg',true)"
              . ",('psoe','PSOE','Partido Socialista Obrero Español','[psoe]','https://epolitic.s3.amazonaws.com/uploads/group/avatar/5/logo-psoe.jpg',true)"
              . ",('podemos','Podemos','Podemos','[podemos]','https://pbs.twimg.com/profile_images/478483096598097920/4lnBU17e_bigger.jpeg',true)"
              . ",('raspberry-pi','raspberry-pi','raspberry-pi','[raspberry]','http://i.imgur.com/RZ7iexA.jpg',true)"
              . ",('ubuntu','Ubuntu','Ubuntu','[ubuntu]','http://i.imgur.com/3Sz1WVo.png',true)"
              . ";";
   }
   
   public function url()
   {
      return 'index.php?page=inme_editar_tema&cod='.$this->codtema;
   }
   
   public function texto()
   {
      return nl2br($this->texto);
   }
   
   public function keywords($plain = FALSE)
   {
      $keys = array();
      
      $aux = explode(',', $this->keywords);
      if($aux)
      {
         foreach($aux as $i => $value)
         {
            $key = str_replace( array('[',']') , array('',''), $value);
            if($key)
            {
               $keys[] = $key;
            }
         }
      }
      
      if($plain)
      {
         return join(', ', $keys);
      }
      else
      {
         return $keys;
      }
   }
   
   public function set_keyword($k)
   {
      if($this->keywords == '')
      {
         $this->keywords = '['.strtolower($k).']';
      }
      else if( !in_array( $k, $this->keywords() ) )
      {
         $this->keywords .= ',['.strtolower($k).']';
      }
   }
   
   public function clean_keywords()
   {
      $this->keywords = NULL;
   }
   
   public function get($cod)
   {
      $data = $this->db->select("SELECT * FROM inme_temas WHERE codtema = ".$this->var2str($cod).";");
      if($data)
      {
         return new inme_tema($data[0]);
      }
      else
      {
         $cod = $this->db->escape_string($cod);
         $data = $this->db->select("SELECT * FROM inme_temas WHERE keywords LIKE '%[".$cod."]%';");
         if($data)
         {
            return new inme_tema($data[0]);
         }
         else
         {
            return FALSE;
         }
      }
   }
   
   public function exists()
   {
      if( is_null($this->codtema) )
      {
         return FALSE;
      }
      else
      {
         return $this->db->select("SELECT * FROM inme_temas WHERE codtema = ".$this->var2str($this->codtema).";");
      }
   }
   
   public function save()
   {
      if( strlen($this->codtema) > 1 AND strlen($this->codtema) <= 50 )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE inme_temas SET titulo = ".$this->var2str($this->titulo)
                    .", texto = ".$this->var2str($this->texto)
                    .", keywords = ".$this->var2str($this->keywords)
                    .", imagen = ".$this->var2str($this->imagen)
                    .", articulos = ".$this->var2str($this->articulos)
                    .", popularidad = ".$this->var2str($this->popularidad)
                    .", activo = ".$this->var2str($this->activo)
                    ."  WHERE codtema = ".$this->var2str($this->codtema).";";
         }
         else
         {
            $sql = "INSERT INTO inme_temas (codtema,titulo,texto,keywords,imagen,articulos,activo,popularidad) VALUES "
                    . "(".$this->var2str($this->codtema)
                    . ",".$this->var2str($this->titulo)
                    . ",".$this->var2str($this->texto)
                    . ",".$this->var2str($this->keywords)
                    . ",".$this->var2str($this->imagen)
                    . ",".$this->var2str($this->articulos)
                    . ",".$this->var2str($this->activo)
                    . ",".$this->var2str($this->popularidad).");";
         }
         
         return $this->db->exec($sql);
      }
      else
      {
         $this->new_error_msg('Código del tema no válido: '.$this->codtema
                 .'. Debe tener entre 1 y 50 caracteres.');
         
         return FALSE;
      }
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM inme_temas WHERE codtema = ".$this->var2str($this->codtema).";");
   }
   
   public function all($offset = 0, $order = 'lower(titulo) ASC')
   {
      $tlist = array();
      
      $data = $this->db->select_limit("SELECT * FROM inme_temas ORDER BY ".$order, FS_ITEM_LIMIT, $offset);
      if($data)
      {
         foreach($data as $d)
         {
            $tlist[] = new inme_tema($d);
         }
      }
      
      return $tlist;
   }
   
   public function populares($offset = 0)
   {
      $tlist = array();
      
      $data = $this->db->select_limit("SELECT * FROM inme_temas WHERE activo ORDER BY popularidad DESC, titulo DESC", FS_ITEM_LIMIT, $offset);
      if($data)
      {
         foreach($data as $d)
         {
            $tlist[] = new inme_tema($d);
         }
      }
      
      return $tlist;
   }
   
   public function count()
   {
      $data = $this->db->select("SELECT COUNT(codtema) as num FROM inme_temas;");
      if($data)
      {
         return intval($data[0]['num']);
      }
      else
      {
         return 0;
      }
   }
   
   public function cron_job()
   {
      if( $this->db->table_exists('inme_noticias_fuente') )
      {
         $total = $this->count();
         foreach($this->all( mt_rand(0, $total) ) as $tema)
         {
            $tema->articulos = 0;
            if($tema->activo)
            {
               $sql = "SELECT COUNT(*) as num FROM inme_noticias_fuente WHERE keywords LIKE '%[".$tema->codtema."]%';";
               $data = $this->db->select($sql);
               if($data)
               {
                  $tema->articulos = intval($data[0]['num']);
               }
            }
            
            $tema->popularidad = 0;
            if($tema->activo AND $tema->articulos > 1)
            {
               $sql = "SELECT SUM(popularidad) as total FROM inme_noticias_fuente WHERE keywords LIKE '%[".$tema->codtema."]%';";
               $data = $this->db->select($sql);
               if($data)
               {
                  $tema->popularidad = intval($data[0]['total']);
               }
            }
            
            $tema->save();
         }
      }
   }
}
