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
   public $imagen;
   public $articulos;
   public $activo;
   
   public function __construct($t = FALSE)
   {
      parent::__construct('inme_temas', 'plugins/informame/');
      if($t)
      {
         $this->codtema = $t['codtema'];
         $this->titulo = $t['titulo'];
         $this->texto = $t['texto'];
         $this->imagen = $t['imagen'];
         $this->articulos = intval($t['articulos']);
         $this->activo = $this->str2bool($t['activo']);
      }
      else
      {
         $this->codtema = NULL;
         $this->titulo = '';
         $this->texto = '';
         $this->imagen = NULL;
         $this->articulos = 0;
         $this->activo = TRUE;
      }
   }
   
   protected function install()
   {
      return "INSERT INTO inme_temas (codtema,titulo,texto,imagen,activo) VALUES"
              . " ('espanya','España','España (Reino de España)','http://i.imgur.com/nDoxKF3.jpg',true)"
              . ",('corrupcion','Corrupción','Corrupción','http://i.imgur.com/wYe54PC.jpg',true)"
              . ",('ee-uu','EE.UU','Estados Unidos de América','http://i.imgur.com/MsZyxdq.jpg',true)"
              . ",('eeuu','EE.UU','Estados Unidos de América','http://i.imgur.com/MsZyxdq.jpg',true)"
              . ",('usa','EE.UU','Estados Unidos de América','http://i.imgur.com/MsZyxdq.jpg',true)"
              . ",('estados-unidos','EE.UU','Estados Unidos de América','http://i.imgur.com/MsZyxdq.jpg',true)"
              . ",('alemania','Alemania','Alemania','http://i.imgur.com/I8f9WXM.jpg',true)"
              . ",('china','China','China','http://i.imgur.com/T5KsW3L.jpg',true)"
              . ",('grafeno','Grafeno','Grafeno','http://i.imgur.com/jjlcWYu.jpg',true)"
              . ",('grecia','Grecia','Grecia','http://i.imgur.com/FyyQJho.jpg',true)"
              . ",('isis','ISIS','ISIS','http://i.imgur.com/qXgdYox.jpg',true)"
              . ",('israel','Israel','Israel','http://i.imgur.com/2uRAhdA.png',true)"
              . ",('linux','Linux','Linux','http://i.imgur.com/zF5yVoQ.png',true)"
              . ",('rusia','Rusia','Rusia','http://i.imgur.com/7WZu7fl.jpg',true)"
              . ",('venezuela','Venezuela','Venezuela','http://i.imgur.com/jAB2UDd.jpg',true)"
              . ",('microsoft','Microsoft','Microsoft','http://i.imgur.com/LLX8ddu.jpg',true)"
              . ",('google','Google','Google','http://i.imgur.com/Gh7Ib2o.png',true)"
              . ",('apple','Apple','Apple','http://i.imgur.com/Qttksz6.jpg',true)"
              . ",('nazis','Nazismo','Nazismo','http://i.imgur.com/WYdIkd8.png',true)"
              . ";";
   }
   
   public function url()
   {
      return 'index.php?page=inme_editar_tema&cod='.$this->codtema;
   }
   
   public function get($cod)
   {
      $data = $this->db->select("SELECT * FROM inme_temas WHERE codtema = ".$this->var2str($cod).";");
      if($data)
      {
         return new inme_tema($data[0]);
      }
      else
         return FALSE;
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
                    .", imagen = ".$this->var2str($this->imagen)
                    .", articulos = ".$this->var2str($this->articulos)
                    .", activo = ".$this->var2str($this->activo)
                    ."  WHERE codtema = ".$this->var2str($this->codtema).";";
         }
         else
         {
            $sql = "INSERT INTO inme_temas (codtema,titulo,texto,imagen,articulos,activo) VALUES "
                    . "(".$this->var2str($this->codtema)
                    . ",".$this->var2str($this->titulo)
                    . ",".$this->var2str($this->texto)
                    . ",".$this->var2str($this->imagen)
                    . ",".$this->var2str($this->articulos)
                    . ",".$this->var2str($this->activo).");";
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
   
   public function all($offset = 0)
   {
      $tlist = array();
      
      $data = $this->db->select_limit("SELECT * FROM inme_temas ORDER BY lower(titulo) ASC", FS_ITEM_LIMIT, $offset);
      if($data)
      {
         foreach($data as $d)
            $tlist[] = new inme_tema($d);
      }
      
      return $tlist;
   }
   
   public function populares($offset = 0)
   {
      $tlist = array();
      
      $data = $this->db->select_limit("SELECT * FROM inme_temas WHERE activo ORDER BY articulos DESC, titulo DESC", FS_ITEM_LIMIT, $offset);
      if($data)
      {
         foreach($data as $d)
            $tlist[] = new inme_tema($d);
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
      $total = $this->count();
      
      foreach($this->all( mt_rand(0, $total) ) as $tema)
      {
         $tema->articulos = 0;
         $sql = "SELECT COUNT(*) as num FROM inme_noticias_fuente WHERE keywords LIKE '%[".$tema->codtema."]%';";
         $data = $this->db->select($sql);
         if($data)
         {
            $tema->articulos = intval($data[0]['num']);
         }
         
         $tema->save();
      }
   }
}
