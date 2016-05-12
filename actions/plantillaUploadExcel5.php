<?php
session_start();
//--------------------------------------------
// Archivo:
// Descripcion:
// Autor: Néstor Pérez Navarro.
// Fecha:
// Modificacion:
//--------------------------------------------
include_once("../include/db_mysqli.inc");
include_once("../include/template.inc");
include_once("../include/confGral.php");
include_once("../include/acceso.class.php");
// Librerias para reconocer archivos de Excel
include_once("../include/PHPExcel/PHPExcel.php");
include_once("../include/PHPExcel/Reader/Excel2007.php");

$usuario=new Acceso;
$t = new Template("../templates", "keep");
// havePerm ('cadena de permisos','arreglo de permisos creado en index.php como var_sesion').
// Esta funcion se encarga de verificar si las llaves de las paginas
// coinciden con el arreglo de permisos que tiene el usuario.
// de ser verdad, permite presentar la pag. correcta de lo contrario mostrara "acceso denegado"
// if( $usuario->havePerm("1",$mscArrPerms) ){
    $db = new DB_Sql;
    $db->connect("MscCobranza", "localhost", "root", "");

    // ffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function showForm(){
        global $db,$t,$PHP_SELF;
        $t->set_file("page", "plantillaUploadExcel5.inc.html");

        // inicializar vars
        $t->set_var("ACTION",$PHP_SELF);
        $t->set_var("MENSAJE","");


        // -------------------------------------------
        //  Control de mensajes
        // -------------------------------------------
        if(!empty($msg)){
            $canMsg=count($msg);
            if($canMsg>0){
                foreach($msg as $val){
                    $cadMsg.=$val ." <br>";
                }
                $t->set_var(array(
                    "MENSAJE"=>$cadMsg,
                ));
            }
        }

        $t->pparse("out","page");
    }
    // ffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function uploadFile(){	    
        global $t,$db,$mscIdUsuario;


        // ---------------------------------------------------
        // PROCESO COPIAR ARCHIVO
        // ---------------------------------------------------    
        if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
            copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/demo.xlsx");
        }
        else {
            echo "Error de archivo!.";
        }


        // ---------------------------------
        // ---- LEER ARCHIVO DE EXCEL ---
        // ---------------------------------        
        $objReader = new PHPExcel_Reader_Excel2007();
        //$objReader = new PHPExcel_Reader_Excel5();
        $objPHPExcel = $objReader->load('../files/demo.xlsx');
        $objPHPExcel->setActiveSheetIndex(0);

        // ---------------------------------
        // Extracción de Celdas Especificas
        // ----------------------------------
        $titulo= $objPHPExcel->getActiveSheet()->getCell("A1")->getValue();

        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------    
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $array_data = array();
        foreach($rowIterator as $row){
            //echo "fila ".$row->getRowIndex ();
            //echo "<br>";
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            //if(1 == $row->getRowIndex ()) continue; //skip first row la del encabezado
            $rowIndex = $row->getRowIndex ();
            //$array_data[$rowIndex] = array('A'=>'', 'B'=>'','C'=>'','D'=>'','E'=>'');            

            foreach ($cellIterator as $cell) {            
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn()){
                    $datos[$rowIndex]['User Name'] = $cell->getValue();
                    if( $datos[$rowIndex]['User Name']=="User Name" ){
                        $rowStar=$rowIndex;
                    }
                }

                if('B' == $cell->getColumn()){$datos[$rowIndex]['Login'] = $cell->getValue();}
                if('C' == $cell->getColumn()){$datos[$rowIndex]['Email'] = $cell->getValue();}

            }
        }


        // -------------------------------------------------
        // NORMA43 : II. REGISTRO PRINCIPAL DE MOVIMIENTOS 
        // -------------------------------------------------
        $i=1;

        echo "
        <table class='formulario'>
        <tr class='row'>
        <th>User Name</th>
        <th>Login</th>
        <th>Email</th>
        </tr>
        ";
        foreach( $datos as $dato=>$x ){

            // Datos
            $nombre= $x['User Name'];
            $login = $x['Login'];
            $email = $x['Email'];

            echo "<tr class='row'>\n";
            // Comenzar en ...
            if( $i > $rowStar && !empty($nombre) ){
                // Insertar a B.D.
                $nombre = trim($nombre);
                echo "
                    <td>$nombre</td>
                    <td>$login</td>
                    <td>$email</td>
                ";

            }
            echo "</tr>";
            $i++;
        }
        echo "</table>";




    }
    // ffffffffffffffffffffffffffffffffffffffffffffffffffffff
    switch($modo){
        case "enviar":
            showForm();
            uploadFile();

            break;
        default:
            showForm();
            break;
    }

/*
 } // fin havePerm()
else{
    $t->set_file("page", "accesoDenegado.inc.html");
    $t->pparse("out","page");
}
*/

?>