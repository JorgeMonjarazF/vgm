<?php
 /*
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Library General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 *  Author: Nestor Perez Navarro.
 *  E-mail: nesmaster@yahoo.com
 */

	class Acceso {
		function havePerm($keys,$arrPerms){
			/*
			echo "Permisos: ";
			foreach($arrPerms as $v){
				echo $v .",";		
			}
			echo "<br>";
			echo "Llaves:" . $keys ."<br>"  ;
			echo "---------------------------<br>";
			*/
			// convertir a matriz las llaves
			$arrKeys=explode(",",$keys);
			
			foreach($arrKeys as $key){
				// buscar el valor $varLlave en todo el array y de ser true, entonces regresa 1.				
				// mod 20030515 
				if(isset($key) && isset($arrPerms)){
					if( in_array($key,$arrPerms) ){
						return 1;
						exit;
					}		
				}				
			}
						
				
						
		}
	}	
?>