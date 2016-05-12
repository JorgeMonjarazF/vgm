<?php

// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Library General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.

// Written by Gabriel López Núñez - galo@liceaga.facmed.unam.mx
// Last modification:  21-01-2002

class Paging {
	// Constructor
	function Paging( $numRows,$pageSize ){
	global $PHP_SELF, $position, $searchUsuario;

	$this->position = (!isset($position)) ? 0 : $position;
	$this->numRows = $this->total = $numRows;	// Total number of items (SQL count from db)
	$this->pageSize = $pageSize;  // Number of tuples to show per page

	$this->lower = ( $this->position + 1 );
	$this->upper = ($this->position + $this->pageSize >= $this->numRows) ? $this->numRows : ($this->position + $this->pageSize);

	$this->totalPages = $this->numRows / $this->pageSize;
	$this->currentPage = number_format( ( $this->position * $this->totalPages ) / $this->numRows, 0);

	if($this->position!=0)
	$this->previousLink = "<a href=\"$PHP_SELF?position=".( $this->position - $this->pageSize )."&searchUsuario=$searchUsuario\">";

    if( ($this->numRows - $this->position) > $this->pageSize ){
      $newPosition = $this->position + $this->pageSize;
      $this->nextLink = "<a href=\"$PHP_SELF?position=$newPosition&searchUsuario=$searchUsuario\".\">";
    }
  }

  function addToURL(){
  }

  // Returns an array of string (href link with the page number)
  function getPagingRowArray(){
    global $PHP_SELF;

    for( $i=0; $i<$this->totalPages; $i++ ){
      if( $i == $this->currentPage ){	// if current page, do not make a link
        $pagesArray[$i] = "<b>". ($i+1) ."</b>";
      }else{
        $newPosition = ( $i * $this->pageSize );
        $pagesArray[$i] = "<a href=\"". $PHP_SELF ."?position=$newPosition\">". ($i+1) ."</a>";
      }
    }
    return $pagesArray;
  }

} // End Paging Class

?>