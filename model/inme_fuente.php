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
 * Description of inme_fuente
 *
 * @author carlos
 */
class inme_fuente extends fs_model
{
   /**
    * Clave primaria. Varchar (50)
    * @var type 
    */
   public $codfuente;
   public $url;
   
   public function __construct($t = FALSE)
   {
      parent::__construct('inme_fuentes', 'plugins/informame/');
      if($t)
      {
         $this->codfuente = $t['codfuente'];
         $this->url = $t['url'];
      }
      else
      {
         $this->codfuente = NULL;
         $this->url = NULL;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function meneame()
   {
      return ( mb_substr($this->url, 0, 23) == 'http://www.meneame.net/' OR mb_substr($this->url, 0, 24) == 'https://www.meneame.net/' );
   }
   
   public function get($cod)
   {
      $data = $this->db->select("SELECT * FROM inme_fuentes WHERE codfuente = ".$this->var2str($cod).";");
      if($data)
      {
         return new inme_fuente($data[0]);
      }
      else
         return FALSE;
   }
   
   public function get_by_url($url)
   {
      $data = $this->db->select("SELECT * FROM inme_fuentes WHERE url = ".$this->var2str($url).";");
      if($data)
      {
         return new inme_fuente($data[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->codfuente) )
      {
         return FALSE;
      }
      else
      {
         return $this->db->select("SELECT * FROM inme_fuentes WHERE codfuente = ".$this->var2str($this->codfuente).";");
      }
   }
   
   public function save()
   {
      if( strlen($this->codfuente) > 1 AND strlen($this->codfuente) <= 50 )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE inme_fuentes SET url = ".$this->var2str($this->url)
                    ."  WHERE codfuente = ".$this->var2str($this->codfuente).";";
         }
         else
         {
            $sql = "INSERT INTO inme_fuentes (codfuente,url) VALUES (".$this->var2str($this->codfuente).
                    ",".$this->var2str($this->url).");";
         }
         
         return $this->db->exec($sql);
      }
      else
      {
         $this->new_error_msg('Código de la fuente no válido. Debe tener entre 1 y 50 caracteres.');
         
         return FALSE;
      }
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM inme_fuentes WHERE codfuente = ".$this->var2str($this->codfuente).";");
   }
   
   public function all()
   {
      $tlist = array();
      
      $data = $this->db->select("SELECT * FROM inme_fuentes ORDER BY lower(codfuente) ASC;");
      if($data)
      {
         foreach($data as $d)
            $tlist[] = new inme_fuente($d);
      }
      
      return $tlist;
   }
}
