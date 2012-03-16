<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2012  Carlos Garcia Gomez  neorazorx@gmail.com
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

require_once 'base/fs_model.php';
require_once 'model/articulo.php';

class familia extends fs_model
{
   public $codfamilia;
   public $descripcion;
   
   public function __construct($f=FALSE)
   {
      parent::__construct('familias');
      if($f)
      {
         $this->codfamilia = $f['codfamilia'];
         $this->descripcion = $f['descripcion'];
      }
      else
      {
         $this->codfamilia = NULL;
         $this->descripcion = '';
      }
   }
   
   public function url()
   {
      return "index.php?page=general_familia&cod=".$this->codfamilia;
   }
   
   public function get_articulos($offset=0)
   {
      $articulo = new articulo();
      return $articulo->all_from_familia($this->codfamilia, $offset);
   }

   protected function install()
   {
      return '';
   }
   
   public function exists()
   {
      return $this->db->select("SELECT * FROM ".$this->table_name." WHERE codfamilia = '".$this->codfamilia."';");
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET descripcion = '".$this->descripcion."' WHERE codfamilia = '".$this->codfamilia."';";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (codfamilia,descripcion) VALUES ('".$this->codfamilia."','".$this->descripcion."');";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codfamilia = '".$this->codfamilia."';");
   }
   
   public function get($cod)
   {
      $f = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codfamilia = '".$cod."';");
      if($f)
         return new familia($f[0]);
      else
         return FALSE;
   }

   public function all()
   {
      $famlist = array();
      $familias = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY descripcion ASC;");
      if($familias)
      {
         foreach($familias as $f)
         {
            $famlist[] = new familia($f);
         }
      }
      return $famlist;
   }
}

?>
