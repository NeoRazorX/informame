<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_model('inme_noticia_fuente.php');

/**
 * Description of inme_editar_noticia
 *
 * @author carlos
 */
class inme_editar_noticia extends fs_controller
{
   public $noticia;
   public $relacionada;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Editar noticia', 'informame', FALSE, FALSE);
   }
   
   protected function private_core()
   {
      $this->noticia = FALSE;
      $this->relacionada = FALSE;
      
      if( isset($_REQUEST['id']) )
      {
         $noti0 = new inme_noticia_fuente();
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
               foreach($keys as $k)
                  $this->noticia->set_keyword($k);
            }
            
            if( $this->noticia->save() )
            {
               $this->new_message('Datos modificados correctamente.');
            }
            else
               $this->new_error_msg('Error al guardar los datos.');
         }
         
         if( !is_null($this->noticia->id_relacionada) )
         {
            $this->relacionada = $noti0->get($this->noticia->id_relacionada);
         }
      }
      else
         $this->new_error_msg('Noticia no encontrada.');
   }
}
