<?php
session_start();

//--------------------------------------------
// Fecha: 20051017
//--------------------------------------------
include_once("../include/template.inc");
include_once("../include/confGral.php");
include_once("../include/acceso.class.php");
include_once("../include/class.phpmailer.php");
include_once("../include/PHPExcel/PHPExcel.php");
include_once("../include/PHPExcel/PHPExcel/Reader/Excel2007.php");

$usuario=new Acceso;
$t = new Template("../templates", "keep");
$sesIdUsuario = $_SESSION[sesIdUsuario];
//$sesOficina =  $_SESSION[sesOficina];

// Reservar memoria en servidor PHP
//   Si el archivo final tiene 5Mb, reservar 500Mb
//   Por cada operación, phpExcel mapea en memoria la imagen del archivo y esto satura la mamoria
ini_set("memory_limit","1024M");
//ini_set("memory_limit","12M");

//    if( $usuario->havePerm("1,4",$_SESSION['sesArrPerms'] )){
if( isset($sesIdUsuario) ) {

    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function normaliza ($cadena){

        // METODO 1 
        // NOTA IMPORTANTE : TENER MUCHO CUIDADO YA QUE PUEDE CAMBIAR CARACTERES EN LOS BLS
        // POR EJEMPLO UN RH1234567 LO CAMBIA POR BH1234567. PELIGROSO. ATT. Nestor 20150106
        /*
        $originales = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿ´R´r';
        $modificadas = 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr';
        $cadena = utf8_decode($cadena);
        $cadena = strtr($cadena, utf8_decode($originales), $modificadas);
        $cadena = strtolower($cadena);
        return utf8_encode($cadena);
        */
       
        // METODO 2 
        $charset='UTF-8'; // o 'UTF-8' / ISO-8859-1
        $str = iconv($charset, 'ASCII//TRANSLIT', $cadena);
        return $str;

}
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateInMscExcelAlpasa($stConte,$sesIdUsuario,$idNav,$opcPro){

        // EXCEL
        // ---------------------------------------------------
        // PROCESO COPIAR ARCHIVO
        // ---------------------------------------------------
        if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
            copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/entradasAlpasa.xls");
        }
        else {
            echo "Error de archivo!.";
        }

        // -------------------------------------
        // GET-IN
        // -------------------------------------
        unset($fp);
        unset($data);
        unset($dataX);


        // ---------------------------------
        // ---- LEER ARCHIVO DE EXCEL ---
        // ---------------------------------
        //$objReader = new PHPExcel_Reader_Excel2007();
        $objReader = new PHPExcel_Reader_Excel5();
        $objPHPExcel = $objReader->load('../edi_files/csv/entradasAlpasa.xls');
        $objPHPExcel->setActiveSheetIndex(0);


        $validFile="True";
        $l=1;

        // ------------------
        // Validacion campos
        // ------------------
        // ---------------------------------
        // Validar el encabezado
        // ----------------------------------
        $encC1= $objPHPExcel->getActiveSheet()->getCell("A9")->getValue();
        $encC2= $objPHPExcel->getActiveSheet()->getCell("B9")->getValue();
        $encC3= $objPHPExcel->getActiveSheet()->getCell("C9")->getValue();
        $encC4= $objPHPExcel->getActiveSheet()->getCell("D9")->getValue();
        $encC5= $objPHPExcel->getActiveSheet()->getCell("E9")->getValue();
        $encC6= $objPHPExcel->getActiveSheet()->getCell("F9")->getValue();
        $encC7= $objPHPExcel->getActiveSheet()->getCell("G9")->getValue();
        $encC8= $objPHPExcel->getActiveSheet()->getCell("H9")->getValue();

        if( $encC1!="Fecha Ing."){$validFile=0;echo "[Error Encabezado] Fecha Ing.<br>";}
        if( $encC2!="Num. Contenedor"){$validFile=0;echo "[Error Encabezado] Num. Contenedor<br>";}
        //if( $encC3!="TIPO/TAMAÑO" ){$validFile=0;echo "[Error Encabezado] TIPO/TAMAÑO<br>";}
        if( $encC4!="CALIDAD" ){$validFile=0;echo "[Error Encabezado] CALIDAD <br>";}
        if( $encC5!="Condiciones del Contenedor al ingreso" ){$validFile=0;echo "[Error Encabezado] Condiciones del Contenedor al ingreso <br>";}
        if( $encC6!="Ag. Aduanal / Transportista" ){$validFile=0;echo "[Error Encabezado] Ag. Aduanal / Transportista <br>";}
        if( $encC7!="No.- EIR" ){$validFile=0;echo "[Error Encabezado] No.- EIR <br>";}
        if( $encC8!="Maniobra por cuenta de" ){$validFile=0;echo "[Error Encabezado] Maniobra por cuenta de<br>";}
        // ---- Fin de la validación ----

        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $array_data = array();
        foreach( $rowIterator as $row ){
            $lin = $row->getRowIndex ();
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            $rowIndex = $row->getRowIndex ();

            foreach ($cellIterator as $cell) {
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn() ){
                    $fechaOri = $cell->getFormattedValue();
                    $fecha = $cell->getFormattedValue();
                    $fecha = str_replace("/","-",$fecha);
                    // Validar Fecha : En caso de que los formatos sean 01-mes-15
                    unset($parts);
                    if( preg_match("/(\d{1,2})-(\w{3,3})-(\d{2,2})/i",$fecha,$parts) ){
                        // echo "FECHA:  $fecha OK <br>";
                        $dd = $parts[1];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $mes = $parts[2];
                        $aa = 2000 + $parts[3];
                        $mm = getOkMes($mes);
                        $fecha = $aa."-".$mm."-".$dd;
                    }
                    elseif (preg_match("/(\d{1,2})-(\d{1,2})-(\d{4})/i", $fecha,$parts)) {
                        $dd = $parts[1];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $aa = $parts[3];
                        $fecha = $aa."-".$mm."-".$dd;   
                    }
                    elseif (preg_match("/(\d{1,2})-(\d{1,2})-(\d{2})/i", $fecha,$parts)) {
                        $mm = $parts[1];
                        $mm = str_pad($mm,2,"0", STR_PAD_LEFT);
                        $dd = $parts[2];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $aa = 2000 + $parts[3];
                        $fecha = $aa."-".$mm."-".$dd;   
                    }
                    $datos[$rowIndex]['FECHA'] = $fecha;
                    
                    // HORA
                    // ----------------
                    unset($parts);
                    $fecha = str_replace(".", "", $fechaOri);
                    $fecha = str_replace(" ", "", $fechaOri);
                    //echo "HoraOri: $fecha<br>";

                    if( preg_match("/(\d{1,2}):(\d{2,2})$/",$fechaOri,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) {0,1}[PM|pm]$/",$fechaOri,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        if( $hh<12 ){
                            $hh = $hh + 12;
                        }
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) {0,1}[AM|am]$/",$fechaOri,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    //echo "HoraRes: $hora <br>";
                    $datos[$rowIndex]['HORA'] = $hora;

                }                
                if('B' == $cell->getColumn()){
                    $conte = $cell->getValue();
                    $conte = strtoupper($conte);
                    $conte = str_replace("-", "", $conte);
                    $conte = str_replace("/", "", $conte);
                    $conte = str_replace(" ", "", $conte);
                    $datos[$rowIndex]['CONTENEDOR'] = $conte;
                }
                if('C' == $cell->getColumn()){
                    $equipo= $cell->getValue();
                    $equipo = str_replace("\"", "", $equipo);
                    $equipo = str_replace("DRY WEIGHT", "DC", $equipo);
                    $equipo = str_replace("DRY CARGO", "DC", $equipo);
                    $equipo = str_replace("HIGH CUBE", "HC", $equipo);
                    $equipo = str_replace(" ", "", $equipo);
                    $datos[$rowIndex]['EQUIPO'] = $equipo;
                }
                if('G' == $cell->getColumn()){
                    $datos[$rowIndex]['EIR'] = $cell->getValue();
                }
                if('H' == $cell->getColumn()){
                    $datos[$rowIndex]['MANIOBRA'] = $cell->getValue();
                }
                if('F' == $cell->getColumn()){
                    // Normalizar el nombre del transportista en caso de que contenga caracteres acentuados.
                    $tra = $cell->getValue();
                    $tra = normaliza($tra);
                    $datos[$rowIndex]['TRANSPORTISTA'] = $tra;
                }
                /*
                Pregunta : Es necesario meter Impo/Expo en otra columna?
                Preguntar a Lemuel.
                Att. Nestor
                 */
                /*if('J' == $cell->getColumn()){
                    $datos[$rowIndex]['I_E'] = $cell->getValue();
                }
                */
            }
        }

        if( $validFile=="True" ) {
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $flgRec = 1;

            $l = 1;
            $f = date(Ymd);

            // ------------------------------------------------
            // Generar archivo EDI
            // ------------------------------------------------
            $fileEDI = "../edi_files/edi/ALPASA_MSC_GATE_IN".$fecD1.$fecD2.".edi";
            $fileName = "ALPASA_MSC_GATE_IN".$fecD1.$fecD2.".edi";
            $fp2 = fopen("$fileEDI", "w");
            $sepa = "\r\n";
            $codPatio = "LMR06";
            $codPatioName = "ALPASA";
            $senderID = "MXLMR06";
            $receiverID = "MSC";
            // ENCABEZADO
            $enc = "UNB+UNOA:1+$senderID+MSC+$fecD1:$fecD2+$fecD1$fecD2'" . $sepa;
            $enc .= "UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'" . $sepa;
            $enc .= "BGM+34+$fecD1$fecD2+9'" . $sepa;
            $enc .= "LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'" . $sepa;
            fputs($fp2, $enc);
            $tlConte = 0;
            $tlSegmentos = 4;

            // -------------------------------------------------
            // OBTENIENDO LA INFO
            // -------------------------------------------------
            $l=1;
            foreach ($datos as $dato => $x) {

                if( $l>9 ) {
                    $validFec=0;
                    
                    $fecha = $x['FECHA'];
                    if( preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha,$parts) ){
                        $d= $parts[3];
                        $m= $parts[2];
                        $a= $parts[1];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }

                    $hora = $x['HORA'];
                    $hora = strtolower($hora);
                    $hora = str_replace(" ","",$hora);
                    $hora = str_replace(":","",$hora);
                    $hora = str_replace("-","",$hora);
                    $hora = str_replace(".","",$hora);
                    $hora = str_replace("pm","",$hora);
                    $hora = str_replace("am","",$hora);
                    $hora = str_pad($hora,4,"0", STR_PAD_LEFT);
                    $regHora = $hora;

                    $conte = $x['CONTENEDOR'];
                    $equipo = $x['EQUIPO'];
                    $tipo = getEquipoTipo($equipo);
                    $eir = $x['EIR'];
                    if(!empty($eir)){
                        $eir="EIR: $eir ";
                    }
                    $maniobra = $x['MANIOBRA'];
                    $transportista = $x['TRANSPORTISTA'];
                    $impoExpo = $x[I_E];

                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;

                        // ---------------------------------------------------------
                        // COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                        // ----------------------------------------------------------
                        ($impoExpo=="E")?$stIE=2:$stIE=3;
                        ($stConte=="E")?$stCo=4:$stCo=5;
                        $conte = strtoupper($conte);
                        $enc2= "EQD+CN+$conte+$tipo++$stIE+$stCo'".$sepa;
                        $tlSegmentos++;
                        $enc2.="DTM+7:$regFecha$regHora:203'".$sepa;
                        $tlSegmentos++;
                        $enc2.="FTX+AAI+++$transportista|$eir'".$sepa;
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'".$sepa;
                        $tlSegmentos++;
                        //$enc2.="LOC+99+$codePatio+::ZZZ'".$sepa;   // 99 : Place of empty equipment return
                        //$tlSegmentos++;
                        fputs($fp2,$enc2);
                        $flgOkFile=1;
                    }

                }
                $l++;
            }

            // Pie de pagina
            // Total de contenedores
            $pie="CNT+16:$tlConte'".$sepa;
            $tlSegmentos++;
            $pie.="UNT+$tlSegmentos+$fecD1$fecD2'".$sepa;
            $pie.="UNZ+1+$fecD1$fecD2'";
            //$enc33=utf8_encode($enc3);
            fputs($fp2,$pie);
            fclose($fp2);
            unset($rowIterator);
            unset($datos);

            if($flgOkFile==1) {
                if ($opcPro == "email") {
                    //sendNotifyOneFile($sesIdUsuario, 'MSC', $fileEDI, $fileName);
                    //unlink($fileEDI);
                } 
                elseif ($opcPro == "ftp") {
                    sentToFTP($fileEDI,$idNav,'/MEXICO/MEXICO/');
                }
                return $fileEDI;
            }


        }

    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateOutMscExcelAlpasa($stConte,$sesIdUsuario,$idNav,$opcPro){

        // EXCEL
        // ---------------------------------------------------
        // PROCESO COPIAR ARCHIVO
        // ---------------------------------------------------
        if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
            copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/salidasAlpasa.xls");
        }
        else {
            echo "Error de archivo!.";
        }

        // -------------------------------------
        // GET-IN
        // -------------------------------------
        unset($fp);
        unset($data);
        unset($dataX);


        // ---------------------------------
        // ---- LEER ARCHIVO DE EXCEL ---
        // ---------------------------------
        //$objReader = new PHPExcel_Reader_Excel2007();
        $objReader = new PHPExcel_Reader_Excel5();
        $objPHPExcel = $objReader->load('../edi_files/csv/salidasAlpasa.xls');
        $objPHPExcel->setActiveSheetIndex(1); // Leer Hoja 2


        $validFile="True";
        $l=1;

        // ---------------------------------
        // Validar el encabezado
        // ----------------------------------
        $encC1= $objPHPExcel->getActiveSheet()->getCell("A9")->getValue();
        $encC2= $objPHPExcel->getActiveSheet()->getCell("B9")->getValue();
        $encC3= $objPHPExcel->getActiveSheet()->getCell("C9")->getValue();
        $encC4= $objPHPExcel->getActiveSheet()->getCell("D9")->getValue();
        $encC5= $objPHPExcel->getActiveSheet()->getCell("E9")->getValue();
        $encC6= $objPHPExcel->getActiveSheet()->getCell("F9")->getValue();
        $encC7= $objPHPExcel->getActiveSheet()->getCell("G9")->getValue();
        $encC8= $objPHPExcel->getActiveSheet()->getCell("H9")->getValue();
        $encC9= $objPHPExcel->getActiveSheet()->getCell("I9")->getValue();
        $encC10= $objPHPExcel->getActiveSheet()->getCell("J9")->getValue();

        if( $encC1!="Fecha Salidas"){$validFile=0;echo "[Error Encabezado] Fecha Salidas<br>";}
        if( $encC2!="Num. Contenedor"){$validFile=0;echo "[Error Encabezado] Num. Contenedor<br>";}
        //if( $encC3!="TIPO/TAMAÑO" ){$validFile=0;echo "[Error Encabezado] TIPO/TAMAÑO<br>";}
        if( $encC4!="Booking" ){$validFile=0;echo "[Error Encabezado] Booking <br>";}
        if( $encC5!="Cliente / Agencia" ){$validFile=0;echo "[Error Encabezado] Cliente / Agencia <br>";}
        if( $encC6!="Transportista" ){$validFile=0;echo "[Error Encabezado] Transportista <br>";}
        if( $encC7!="CALIDAD" ){$validFile=0;echo "[Error Encabezado] CALIDAD <br>";}
        if( $encC8!="No.- EIR" ){$validFile=0;echo "[Error Encabezado] No.- EIR <br>";}
        if( $encC9!="Sello Salida" ){$validFile=0;echo "[Error Encabezado] Sello Salida <br>";}
        if( $encC10!="Maniobra por cuenta de" ){$validFile=0;echo "[Error Encabezado] Maniobra por cuenta de<br>";}
        // ---- Fin de la validación ----

        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $array_data = array();
        foreach($rowIterator as $row){
            $lin = $row->getRowIndex ();
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            $rowIndex = $row->getRowIndex ();

            foreach ($cellIterator as $cell) {
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn() ){
                    $fechaOri = $cell->getFormattedValue();
                    $fecha = $cell->getFormattedValue();
                    $fecha = str_replace("/","-",$fecha);
                    // Validar Fecha : En caso de que los formatos sean 01-mes-15
                    unset($parts);
                    if( preg_match("/(\d{1,2})-(\w{3,3})-(\d{2,2})/i",$fecha,$parts) ){
                        // echo "FECHA:  $fecha OK <br>";
                        $dd = $parts[1];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $mes = $parts[2];
                        $aa = 2000 + $parts[3];
                        $mm = getOkMes($mes);
                        $fecha = $aa."-".$mm."-".$dd;
                    }
                    elseif (preg_match("/(\d{1,2})-(\d{1,2})-(\d{4})/i", $fecha,$parts)) {
                        $dd = $parts[1];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $aa = $parts[3];
                        $fecha = $aa."-".$mm."-".$dd;   
                    }
                    elseif (preg_match("/(\d{1,2})-(\d{1,2})-(\d{2})/i", $fecha,$parts)) {
                        $mm = $parts[1];
                        $mm = str_pad($mm,2,"0", STR_PAD_LEFT);
                        $dd = $parts[2];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $aa = 2000 + $parts[3];
                        $fecha = $aa."-".$mm."-".$dd;   
                    }
                    $datos[$rowIndex]['FECHA'] = $fecha;
                    
                    // HORA
                    // ----------------
                    unset($parts);
                    $fecha = str_replace(".", "", $fechaOri);
                    $fecha = str_replace(" ", "", $fechaOri);
                    //echo "HoraOri: $fecha<br>";

                    if( preg_match("/(\d{1,2}):(\d{2,2})$/",$fechaOri,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) {0,1}[PM|pm]$/",$fechaOri,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        if( $hh<12 ){
                            $hh = $hh + 12;
                        }
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) {0,1}[AM|am]$/",$fechaOri,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    //echo "HoraRes: $hora <br>";
                    $datos[$rowIndex]['HORA'] = $hora;   
                }
                if('B' == $cell->getColumn()){
                    $conte = $cell->getValue();
                    $conte = strtoupper($conte);
                    $conte = str_replace("-", "", $conte);
                    $conte = str_replace("/", "", $conte);
                    $conte = str_replace(" ", "", $conte);
                    $datos[$rowIndex]['CONTENEDOR'] = $conte; 
                }
                if('C' == $cell->getColumn()){
                    $equipo= $cell->getValue();
                    $equipo = str_replace("\"", "", $equipo);
                    $equipo = str_replace("DRY WEIGHT", "DC", $equipo);
                    $equipo = str_replace("DRY CARGO", "DC", $equipo);
                    $equipo = str_replace("HIGH CUBE", "HC", $equipo);
                    $equipo = str_replace(" ", "", $equipo);
                    $datos[$rowIndex]['EQUIPO'] = $equipo;
                }
                if('D' == $cell->getColumn()){
                    $datos[$rowIndex]['BOOKING'] = $cell->getValue();
                }
                if('E' == $cell->getColumn()){
                    $datos[$rowIndex]['CLIENTE'] = $cell->getValue();
                }
                if('F' == $cell->getColumn()){
                    $datos[$rowIndex]['TRANSPORTISTA'] = $cell->getValue();
                }
                if('H' == $cell->getColumn()){
                    $datos[$rowIndex]['EIR'] = $cell->getValue();
                }
                if('I' == $cell->getColumn()){
                    $datos[$rowIndex]['SELLO'] = $cell->getValue();
                }
                if('J' == $cell->getColumn()){
                    $datos[$rowIndex]['MANIOBRA'] = $cell->getValue();
                }
                /*
                if('L' == $cell->getColumn()){
                    $datos[$rowIndex]['I_E'] = $cell->getValue();
                }
                 */

            }
        }

        if( $validFile=="True" ) {
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $flgRec = 1;

            $l = 1;
            $f = date(Ymd);

            // ------------------------------------------------
            // Generar archivo EDI
            // ------------------------------------------------
            $fileEDI = "../edi_files/edi/ALPASA_MSC_GATE_OUT".$fecD1.$fecD2.".edi";
            $fileName = "ALPASA_MSC_GATE_OUT".$fecD1.$fecD2.".edi";
            $fp2 = fopen("$fileEDI", "w");
            $sepa = "\r\n";

            $codPatio = "LMR06";
            $codPatioName = "ALPASA";
            $senderID = "MXLMR06";
            $receiverID = "MSC";

            // ENCABEZADO
            $enc = "UNB+UNOA:1+$senderID+MSC+$fecD1:$fecD2+$fecD1$fecD2'" . $sepa;
            $enc .= "UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'" . $sepa;
            $enc .= "BGM+36+$fecD1$fecD2+9'" . $sepa;
            $enc .= "LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'" . $sepa;
            fputs($fp2, $enc);
            $tlConte = 0;
            $tlSegmentos = 4;

            // -------------------------------------------------
            // OBTENIENDO LA INFO
            // -------------------------------------------------
            $l=1;
            foreach ($datos as $dato => $x) {

                if( $l>9 ) {
                    $validFec=0;

                    $fecha = $x['FECHA'];
                    if( preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha,$parts) ){
                        $d= $parts[3];
                        $m= $parts[2];
                        $a= $parts[1];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }

                    $hora = $x['HORA'];
                    $hora = strtolower($hora);
                    $hora = str_replace(" ","",$hora);
                    $hora = str_replace(":","",$hora);
                    $hora = str_replace("-","",$hora);
                    $hora = str_replace(".","",$hora);
                    $hora = str_replace("pm","",$hora);
                    $hora = str_replace("am","",$hora);
                    $hora = str_pad($hora,4,"0", STR_PAD_LEFT);
                    $regHora = $hora;

                    $conte = $x['CONTENEDOR'];
                    $conte = str_replace("-", "", $conte);
                    $conte = str_replace(" ", "", $conte);
                    $equipo = $x['EQUIPO'];
                    $tipo = getEquipoTipo($equipo);
                    $eir = $x['EIR'];
                    $maniobra = $x['MANIOBRA'];
                    $transportista = $x['TRANSPORTISTA'];
                    $transportista = normaliza($transportista);
                    $cliente = $x['CLIENTE'];
                    $cliente = strtoupper($cliente);
                    $cliente = normaliza($cliente);
                    $bkgNumber = $x['BOOKING'];
                    $bkgNumber = strtoupper($bkgNumber);
                    $bl = $x['BL'];
                    $bkgNumber = strtoupper($bkgNumber);
                    $sello = $x[SELLO];
                    $impoExpo = $x[I_E];
                    // Segun Lemuel este no importa, por que no sabemos el destino.
                    $codOD="";


                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;

                        // ---------------------------------------------------------
                        // COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                        // ----------------------------------------------------------
                        ($impoExpo=="E")?$stIE=2:$stIE=3;
                        ($stConte=="E")?$stCo=4:$stCo=5;
                        $conte = strtoupper($conte);
                        $enc2= "EQD+CN+$conte+$tipo++$stIE+$stCo'".$sepa;
                        $tlSegmentos++;
                        if( !empty($bl) ){
                            $enc2.= "RFF+BM:$bl'\r\n";
                            $tlSegmentos++;
                        }
                        if( !empty($bkgNumber) ) {
                            $enc2.= "RFF+BN:$bkgNumber'\r\n";
                            $tlSegmentos++;
                        }
                        $enc2.="DTM+7:$regFecha$regHora:203'".$sepa;
                        $tlSegmentos++;
                        //$enc2.= "LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'\r\n";
                        //$tlSegmentos++;
                        $enc2.="SEL+$sello+CA'\r\n";
                        $tlSegmentos++;
                        $enc2.="FTX+AAI+++$bkgNumber|$cliente|$transportista'".$sepa;
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'".$sepa;
                        $tlSegmentos++;
                        // Cuando sean salidas es necesario agregar esta línea para cada movimiento,
                        // ya que con este se especifica el Destination Depot.
                        //$enc2.="LOC+99+$codOD'".$sepa;   // 99 : Place of empty equipment return
                        //$tlSegmentos++;
                        fputs($fp2,$enc2);
                        // Validando que el archivo por lo menos contiene un registro.
                        $flgOkFile=1;
                    }

                }
                $l++;
            }

            // Pie de pagina
            // Total de contenedores
            $pie="CNT+16:$tlConte'".$sepa;
            $tlSegmentos++;
            $pie.="UNT+$tlSegmentos+$fecD1$fecD2'".$sepa;
            $pie.="UNZ+1+$fecD1$fecD2'";

            fputs($fp2,$pie);
            fclose($fp2);

            unset($rowIterator);
            unset($datos);

            
            if( $flgOkFile==1) {
                if ($opcPro == "email") {
                    // No enviar... esto lo hace desde afuera de la funcion con envio de 2 archivos.
                } elseif ($opcPro == "ftp") {
                    sentToFTP($fileEDI,$idNav,'/MEXICO/MEXICO/');
                }
                return $fileEDI;
            }
        }

    }
    // ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function sendNotify2F($fileIN,$fileOUT,$naviera,$opcPro){
        global $sesIdUsuario;

        // Email del usuario
        $usrEmail = getValueTable("email","USUARIO","id_usuario",$sesIdUsuario);
        $usrEmailCC = getValueTable("email_cc","USUARIO","id_usuario",$sesIdUsuario);
        $razonSocial = getValueTable("razon_social","USUARIO","id_usuario",$sesIdUsuario);

        $fileINName = str_replace("../edi_files/edi/", "", $fileIN);
        $fileOUTName = str_replace("../edi_files/edi/", "", $fileOUT);

        // ------------------------------------------------
        // CONFIGURAR EMAIL.
        // ------------------------------------------------
        //Definimos las propiedades y llamamos a los métodos
        //correspondientes del objeto mail

        //Con PluginDir le indicamos a la clase phpmailer donde se
        //encuentra la clase smtp que como he comentado al principio de
        //este ejemplo va a estar en el subdirectorio includes
        $mail = new phpmailer();
        $mail->Priority=0; // Se declara la prioridad del mensaje.
        $mail->PluginDir = "../include/";
        $mail->Mailer = "smtp";

        // Configurar la cuenta de correo.
        $mail->Host = "vishnu.hosting-mexico.net";
        $mail->SMTPAuth = true;
        $mail->Username = "robot@edifactory.org";
        $mail->Password = "!robotedifactory";
        $mail->From = "robot@edifactory.org";
        $mail->FromName = "Robot EdiFactory";




        //El valor por defecto 10 de Timeout es un poco escaso dado que voy a usar
        //una cuenta gratuita, por tanto lo pongo a 30
        //$mail->Timeout=10;
        $mail->Timeout=10;

        // --------------------
        // FORMATO HTML
        // --------------------
        $opcPro = strtoupper($opcPro);
        $mail->Body = "
        <!DOCTYPE HTML>
        <html>
        <head>
            <meta charset=\"ISO-8859-1\">
        </head>
        
        <body>
        <b>
        EDICODECO-D95<br>
        Notificación de envío exitoso<br>
        </b>
        <p>
        El sistema Web (www.edifactory.org) ha detectado en automático nuevas entradas y salidas mismas que fueron codificadas en formato
        EDI-CODECO para reconocimiento informático de otros sistemas navieros.<br>
        <br>
        <b>De :</b> $razonSocial <br>
        <b>Para :</b> $naviera <br>
        <b>Archivo GateIN :</b> $fileINName <br>
        <b>Archivo GateOUT:</b> $fileOUTName <br>
        <b>Medio :</b> $opcPro <br>
        <br>
        <i>Att. Robot - Edifactory<br></i>
        <p>
        <hr>
        <font color=\"red\" size=\"2\">
        <i>
        Nota : 
        Este es un correo de envío automático generado por el sistema www.edifactory.org, por favor NO responda este email ya que no será contestado.
        </i>
        </font>
        <br>
        <br>
        <br>

        </body>
        </html>

        ";

        // -------------------------------------------------------
        // FORMATO TEXTO
        // Definimos AltBody por si el destinatario del correo
        // no admite email con formato html
        // -------------------------------------------------------
        $mail->AltBody = "
        =====================================================================
        ";

        // Nota :
        // La direccion PARA solo se puede manejar 1.
        // Las direcciones CC puede manejar N correos.

        // -------------
        // Destinatarios
        // -------------
        $mail->ClearAddresses();
        // ------------------------------------------------

        // TO :
        $mail->AddAddress ( $usrEmail );

        // COPIA A:
        //$usrEmailCC="";
        if( !empty($usrEmailCC) ){
            $arrDirDestino = explode("\n",$usrEmailCC);
            foreach ( $arrDirDestino as $emailDestino ) {
                if (! empty ( $emailDestino )) {
                    $mail->AddCC( $emailDestino );
                    $usrEmailCC= $usrEmailCC . $emailDestino."<br>";
                }
            }
        }

        // BCC :
        $mail->AddBCC("nestor@nesoftware.net");
        
        //Incluir Attach.
        $fileINSR = str_replace("../edi_files/edi/","",$fileIN);
        $fileOUTSR = str_replace("../edi_files/edi/","",$fileOUT);
        $mail->AddAttachment($fileIN,$fileINSR);
        $mail->AddAttachment($fileOUT,$fileOUTSR);
        $mail->Subject = "[EDIFACTORY][$naviera] Notificación de envío EDICODECO";

        // Se envia el mensaje, si no ha habido problemas, la variable $exito tendra el valor true
        //if( is_array($arrEdiFile) ){
        $exito = $mail->Send();
        /*
        // PARA INTAR REENVIARLO
        //Si el mensaje no ha podido ser enviado se realizaran 4 intentos mas como mucho
        //para intentar enviar el mensaje, cada intento se hara 5 segundos despues
        //del anterior, para ello se usa la funcion sleep
        $intentos=1;
        while ((!$exito) && ($intentos < 5)) {
        sleep(5);
        $exito = $mail->Send();
        $intentos=$intentos+1;
        }
        */

        if( !$exito ){
            echo "[ <font color=red><b>ERROR</b>] Problema de envío Email : ".$mail->ErrorInfo."<hr>";
        }
        else{
            echo "[<b><font color=green>OK</b></font>]  Email enviado a : $usrEmail , CC: $usrEmailCC <hr>";
        }


    }
    // ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function sendEmailError($naviera,$opcPro){
        global $sesIdUsuario;

        // Email del usuario
        $usrEmail = getValueTable("email","USUARIO","id_usuario",$sesIdUsuario);
        $usrEmailCC = getValueTable("email_cc","USUARIO","id_usuario",$sesIdUsuario);
        $razonSocial = getValueTable("razon_social","USUARIO","id_usuario",$sesIdUsuario);

        // ------------------------------------------------
        // CONFIGURAR EMAIL.
        // ------------------------------------------------
        //Definimos las propiedades y llamamos a los métodos
        //correspondientes del objeto mail

        //Con PluginDir le indicamos a la clase phpmailer donde se
        //encuentra la clase smtp que como he comentado al principio de
        //este ejemplo va a estar en el subdirectorio includes
        $mail = new phpmailer();
        $mail->Priority=0; // Se declara la prioridad del mensaje.
        $mail->PluginDir = "../include/";
        $mail->Mailer = "smtp";

        // Configurar la cuenta de correo.
        $mail->Host = "vishnu.hosting-mexico.net";
        $mail->SMTPAuth = true;
        $mail->Username = "robot@edifactory.org";
        $mail->Password = "!robotedifactory";
        $mail->From = "robot@edifactory.org";
        $mail->FromName = "Robot EdiFactory";




        //El valor por defecto 10 de Timeout es un poco escaso dado que voy a usar
        //una cuenta gratuita, por tanto lo pongo a 30
        //$mail->Timeout=10;
        $mail->Timeout=10;

        // --------------------
        // FORMATO HTML
        // --------------------
        $opcPro = strtoupper($opcPro);
        $mail->Body = "
        <!DOCTYPE HTML>
        <html>
        <head>
            <meta charset=\"ISO-8859-1\">
        </head>
        
        <body>
        <b>
        EDICODECO-D95<br>
        <font color=red>Error en la transmición</font><br>
        </b>
        <p>
        No fueron transmitidos sus archivos!.<br>
        Revisar los siguientes puntos por favor: <br>
        <br>
        1. El formato del archivo debe ser .xlsx<br>
        2. Los encabezados sean los mismos que la plantilla maestra.<br>
        3. Los encabezados deben estar siempre en el mismo renglon.<br>
        4. La fecha debe contener el formato fecha (aaaa-mm-dd)<br>
        <br>
        Revise estos puntos por favor y si el problema persiste favor de re-enviar el correo a : <br>
        nestor@nesoftware.net / lemuel@nesoftware.net para revisar a la brevedad.<br>
        <br>
        <b>Compañia :</b> $razonSocial <br>
        <b>Naviera :</b> $naviera <br>
        <b>Medio :</b> $opcPro <br>
        <br>
        <br>
        Que tenga buen día.<br>
        Att. Robot EdiFactory<br>
        <br>
        <hr>
        <font color=\"red\" size=\"2\">
        <i>
        Nota : 
        Este es un correo de envío automático generado por el sistema www.edifactory.org, por favor NO responda este email ya que no será contestado.
        </i>
        </font>
        <br>
        <br>
        <br>

        </body>
        </html>

        ";

        // -------------------------------------------------------
        // FORMATO TEXTO
        // Definimos AltBody por si el destinatario del correo
        // no admite email con formato html
        // -------------------------------------------------------
        $mail->AltBody = "
        =====================================================================
        ";

        // Nota :
        // La direccion PARA solo se puede manejar 1.
        // Las direcciones CC puede manejar N correos.

        // -------------
        // Destinatarios
        // -------------
        $mail->ClearAddresses();
        // ------------------------------------------------

        // TO :
        $mail->AddAddress ( $usrEmail );

        // COPIA A:
        //$usrEmailCC="";
        if( !empty($usrEmailCC) ){
            $arrDirDestino = explode("\n",$usrEmailCC);
            foreach ( $arrDirDestino as $emailDestino ) {
                if (! empty ( $emailDestino )) {
                    $mail->AddCC( $emailDestino );
                    $usrEmailCC= $usrEmailCC . $emailDestino."<br>";
                }
            }
        }

        // BCC :
        $mail->AddBCC("nestor@nesoftware.net");
        
        //Incluir Attach.
        $fileINSR = str_replace("../edi_files/edi/","",$fileIN);
        $fileOUTSR = str_replace("../edi_files/edi/","",$fileOUT);
        $mail->AddAttachment($fileIN,$fileINSR);
        $mail->AddAttachment($fileOUT,$fileOUTSR);
        $mail->Subject =     "[EDIFACTORY][$naviera] ERROR - EDICODECO";

        // Se envia el mensaje, si no ha habido problemas, la variable $exito tendra el valor true
        //if( is_array($arrEdiFile) ){
        $exito = $mail->Send();
        /*
        // PARA INTAR REENVIARLO
        //Si el mensaje no ha podido ser enviado se realizaran 4 intentos mas como mucho
        //para intentar enviar el mensaje, cada intento se hara 5 segundos despues
        //del anterior, para ello se usa la funcion sleep
        $intentos=1;
        while ((!$exito) && ($intentos < 5)) {
        sleep(5);
        $exito = $mail->Send();
        $intentos=$intentos+1;
        }
        */

        if( !$exito ){
            echo "[ <font color=red><b>ERROR</b>] Problema de envío Email : ".$mail->ErrorInfo."<hr>";
        }
        else{
            echo "[<b><font color=green>OK</b></font>]  Email enviado a : $usrEmail , CC: $usrEmailCC <hr>";
        }


    }
    // ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function getEquipoTipo($tamano){
        $tamano = str_replace(" ", "", $tamano);
        // 20' DRY VAN
        if ($tamano == "22.10" || $tamano == "22.1" || $tamano == "20DC" || $tamano == "20DV") {
            $tipo = "22G0";
        }
        // 20' FLAT COLLAPSIBLE
        if ($tamano == "22.63" || $tamano == "20FL") {
            $tipo = "22P3";
        }
        // 20' REEFER
        if ($tamano == "22.32" || $tamano == "20RF") {
            $tipo = "22R1";
        }
        // 20CT TANK CONTAINER
        if ($tamano == "22.70" || $tamano == "22.7" || $tamano == "20TK") {
            $tipo = "22T3";
        }
        // 20OT OPEN TOP
        if ($tamano == "22.51" || $tamano == "20OT") {
            $tipo = "22U1";
        }
        // 40' DRY VAN
        if ($tamano == "43.10" || $tamano == "43.1" || $tamano == "40DC" || $tamano == "40DV" ) {
            $tipo = "42G0";
        }
        // 40' HIGH CUBE
        if ($tamano == "45.10" || $tamano == "45.1" || $tamano == "40HC") {
            $tipo = "45G0";
        }
        // 40' OPEN TOP
        if ($tamano == "43.51" || $tamano == "40OT") {
            $tipo = "42U1";
        }
        // RF4/40' REEFER
        if ($tamano == "43.32" || $tamano == "40RF") {
            $tipo = "42R1";
        }
        // 40CT TANK CONTAINER
        if ($tamano == "43.70" || $tamano == "40TK") {
            $tipo = "42T0";
        }

        if (empty($tipo)) {
            $tipo = $tamano;
        }

        return $tipo;

    }

    // ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function sentToFTP($fileEDI,$idNav,$dirDes=""){

        if( $idNav==3 ){
            // MOL
            $ftp_server = "ediftp.mol-it.com";
            $ftp_user_name = "MXMTY";
            $ftp_user_pass = "02q24j1N";
            $naviera = "MOL";
        }
        elseif( $idNav==4 ){
            // Hamburg Sud
            $ftp_server = "ftpham04.hamburgsud.com";
            $ftp_user_name = "mym";
            $ftp_user_pass = "mym1030ftp";
            $naviera = "HAMBURG SUD";
        }
        elseif( $idNav==5 ){
            // ZIM
            $ftp_server = "ftp.zimshipping.com";
            $ftp_user_name = "ttm";
            $ftp_user_pass = "graze87ink";   
            $naviera = "ZIM";
        }
        elseif( $idNav==1 ){
            // MSC
            $ftp_server = "187.174.238.23";
            $ftp_user_name = "estefi";
            $ftp_user_pass = "estefi2015";
            $naviera = "MSC";
        }
        elseif( $idNav=='1CA' ){
            // MSC Centro America
            $ftp_server = "187.174.238.23";
            $ftp_user_name = "ctoam";
            $ftp_user_pass = "estefi2015";   
            $naviera = "MSC";
        }
        elseif( $idNav==2 ){
            // HL
            $ftp_server = "ftp.hlcl.com";
            $ftp_user_name = "mxtra02";
            $ftp_user_pass = "ZngU854V"; 
            $naviera = "Hapag-Lloyd";  
        }
        elseif( $idNav=='HSTERR' ){
            // HS TERRAPORTS
            $ftp_server = "ftpham04.hamburgsud.com";
            $ftp_user_name = "mek";
            $ftp_user_pass = "mek0727ftp"; 
            $naviera = "HAMBURG SUD";  
        }
        
        

        $source_file = $fileEDI;
        $fileName = str_replace("../edi_files/edi/", "", $source_file);
        $destination_file = $dirDes.$fileName;

        $conn_id = ftp_connect($ftp_server);
        $login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
        // verificar la conexión
        if ((!$conn_id) || (!$login_result)) {
            echo "<font color=red>
            [ERROR] La conexión al FTP ha fallado!.<br>
            Naviera : $naviera <br>
            Host : $ftp_server <br>
            Contacto : Nestor Pérez Cel.: (55)47-2626-25<br>
            Email : nestor@nesoftware.net / lemuel@nesoftware.net<br>
            </font><hr>";
            exit;
        } else {
            //echo "<font color=blue>Conexión a $ftp_server realizada con éxito.</font><br>";
        }
        // subir un archivo
        $upload = ftp_put($conn_id, $destination_file, $source_file, FTP_BINARY);
        // comprobar el estado de la subida
        if (!$upload) {
            echo "<font color=red>
            [ERROR] La carga del archivo ( $source_file ) ha fallado!.<br>
            Naviera : $naviera <br>
            Host : $ftp_server <br>
            Contacto : Nestor Pérez Cel.: (55)47-2626-25<br>
            Email : nestor@nesoftware.net / lemuel@nesoftware.net<br>
            </font><hr>";
        } else {
            echo "<font color=navy>
            [<font color=green><b>OK</b></font>] La carga del archivo ( $source_file ) ha sido exitoso.<br>
            Naviera : $naviera <br>
            Host : $ftp_server <br>
            </font><hr>";
        }
        ftp_close($conn_id);

    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function sendNotifyOneFile($sesIdUsuario,$naviera,$fileEDI,$fileName){
        global $hoy;

        // Email del usuario
        
        $usrEmail = getValueTable("email","USUARIO","id_usuario",$sesIdUsuario);
        $usrEmailCC = getValueTable("email_cc","USUARIO","id_usuario",$sesIdUsuario);
        $razonSocial = getValueTable("razon_social","USUARIO","id_usuario",$sesIdUsuario);
        
       
        //$usrEmail = "nestor@nesoftware.net";


        // ------------------------------------------------
        // CONFIGURAR EMAIL.
        // ------------------------------------------------
        //Definimos las propiedades y llamamos a los métodos
        //correspondientes del objeto mail

        //Con PluginDir le indicamos a la clase phpmailer donde se
        //encuentra la clase smtp que como he comentado al principio de
        //este ejemplo va a estar en el subdirectorio includes
        $mail = new phpmailer();
        $mail->Priority=0; // Se declara la prioridad del mensaje.
        $mail->PluginDir = "../include/";
        $mail->Mailer = "smtp";

        // Configurar la cuenta de correo.
        $mail->Host = "vishnu.hosting-mexico.net";
        $mail->SMTPAuth = true;
        $mail->Username = "robot@edifactory.org";
        $mail->Password = "!robotedifactory";
        $mail->From = "robot@edifactory.org";
        $mail->FromName = "Robot EdiFactory";




        //El valor por defecto 10 de Timeout es un poco escaso dado que voy a usar
        //una cuenta gratuita, por tanto lo pongo a 30
        //$mail->Timeout=10;
        $mail->Timeout=10;

        // --------------------
        // FORMATO HTML
        // --------------------
        $mail->Body = "
        <html>
        <body>
        <b>
        EDICODECO-D95<br>
        Notificación de envío exitoso<br>
        </b>
        <br>
        Los archivos EDI han sido transmitidos con éxito a la naviera.
        De cualquier forma se adjunta copia de los mismos para los fines que considere necesarios.<br>
        <br>
        De : $razonSocial<br>
        Para : $naviera <br>
        <br>
        <br>
        Att. Robot Edifactory <br>
        <br>
        <br>
        <hr>
        <font color=\"red\" size=\"2\">
        <i>
        Nota : 
        Este es un correo de envío automático generado por el sistema www.edifactory.org, por favor NO responda este email ya que no será contestado.
        </i>
        </font>
        <br>
        <br>
        <br>

        </body>
        </html>

        ";

        // -------------------------------------------------------
        // FORMATO TEXTO
        // Definimos AltBody por si el destinatario del correo
        // no admite email con formato html
        // -------------------------------------------------------
        $mail->AltBody = "
        =====================================================================
        ";

        // Nota :
        // La direccion PARA solo se puede manejar 1.
        // Las direcciones CC puede manejar N correos.

        // -------------
        // Destinatarios
        // -------------
        $mail->ClearAddresses();
        // ------------------------------------------------

        // TO :
        $mail->AddAddress ( $usrEmail );

        // COPIA A:
        //$usrEmailCC="";
        if( !empty($usrEmailCC) ){
            $arrDirDestino = explode("\n",$usrEmailCC);
            foreach ( $arrDirDestino as $emailDestino ) {
                if (! empty ( $emailDestino )) {
                    $mail->AddCC( $emailDestino );
                    $usrEmailCC= $usrEmailCC . $emailDestino."<br>";
                }
            }
        }

        // BCC :
        $mail->AddBCC("nestor@nesoftware.net");
        //$mail->AddBCC("lrodriguez@mscmexico.com");

        // Subject :
        $mail->Subject = "[EDIFACTORY][$naviera] Notificación de envío EDICODECO";

        //Incluir Attach.
        $mail->AddAttachment($fileEDI,$fileName);


        // Se envia el mensaje, si no ha habido problemas, la variable $exito tendra el valor true
        //if( is_array($arrEdiFile) ){
        $exito = $mail->Send();
        /*
        // PARA INTAR REENVIARLO
        //Si el mensaje no ha podido ser enviado se realizaran 4 intentos mas como mucho
        //para intentar enviar el mensaje, cada intento se hara 5 segundos despues
        //del anterior, para ello se usa la funcion sleep
        $intentos=1;
        while ((!$exito) && ($intentos < 5)) {
        sleep(5);
        $exito = $mail->Send();
        $intentos=$intentos+1;
        }
        */

        if( !$exito ){
            echo "[ <font color=red><b>ERROR</b>] Problema de envío Email : ".$mail->ErrorInfo."<hr>";
        }
        else{
            echo "[<b><font color=green>OK</b></font>]  Email enviado a : $usrEmail , CC: $usrEmailCC <hr>";
        }

        // ---------------------------------------------------------
        // ELIMINAR los archivos
        // ---------------------------------------------------------
        unlink($fileEDI);

    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function sendNotifyHL($sesIdUsuario,$idNav,$file1="",$file2="",$opcPro){
        global $hoy;

        // Email del usuario
        $usrEmail = getValueTable("email","USUARIO","id_usuario",$sesIdUsuario);
        $usrEmailCC = getValueTable("email_cc","USUARIO","id_usuario",$sesIdUsuario);
        $razonSocial = getValueTable("razon_social","USUARIO","id_usuario",$sesIdUsuario);
        $navCode="Hapag-Lloyd";
        
        // ------------------------------------------------
        // CONFIGURAR EMAIL.
        // ------------------------------------------------
        //Definimos las propiedades y llamamos a los métodos
        //correspondientes del objeto mail

        //Con PluginDir le indicamos a la clase phpmailer donde se
        //encuentra la clase smtp que como he comentado al principio de
        //este ejemplo va a estar en el subdirectorio includes
        $mail = new phpmailer();
        $mail->Priority=0; // Se declara la prioridad del mensaje.
        $mail->PluginDir = "../include/";
        $mail->Mailer = "smtp";

        // Configurar la cuenta de correo.
        $mail->Host = "vishnu.hosting-mexico.net";
        $mail->SMTPAuth = true;
        $mail->Username = "robot@edifactory.org";
        $mail->Password = "!robotedifactory";
        $mail->From = "robot@edifactory.org";
        $mail->FromName = "Robot EdiFactory";




        //El valor por defecto 10 de Timeout es un poco escaso dado que voy a usar
        //una cuenta gratuita, por tanto lo pongo a 30
        //$mail->Timeout=10;
        $mail->Timeout=10;

        // --------------------
        // FORMATO HTML
        // --------------------
        $opcPro = strtoupper($opcPro);
        $mail->Body = "
        <html>
        <body>
        <b>
        EDICODECO-D95<br>
        Notificación de envío exitoso<br>
        </b>
        <br>
        Los archivos EDI han sido transmitidos con éxito a la naviera.
        De cualquier forma se adjunta copia de los mismos para los fines que considere necesarios.<br>
        <br>
        De : $razonSocial<br>
        Para : $navCode <br>
        Medio : $opcPro <br>
        <br>
        <br>
        Att. Robot Edifactory <br>
        <br>
        <br>
        <hr>
        <font color=\"red\" size=\"2\">
        <i>
        Nota : 
        Este es un correo de envío automático generado por el sistema www.edifactory.org, por favor NO responda este email ya que no será contestado.
        </i>
        </font>
        <br>
        <br>
        <br>

        </body>
        </html>

        ";

        // -------------------------------------------------------
        // FORMATO TEXTO
        // Definimos AltBody por si el destinatario del correo
        // no admite email con formato html
        // -------------------------------------------------------
        $mail->AltBody = "
        =====================================================================
        ";

        // Nota :
        // La direccion PARA solo se puede manejar 1.
        // Las direcciones CC puede manejar N correos.

        // -------------
        // Destinatarios
        // -------------
        $mail->ClearAddresses();
        // ------------------------------------------------

        // TO :
        $mail->AddAddress ( $usrEmail );

        // COPIA A:
        // Correo de Uma para que le llegue por Email.
        $usrEmailCC.="\nediham@edi.hlcl.com";

        if( !empty($usrEmailCC) ){
            $arrDirDestino = explode("\n",$usrEmailCC);
            foreach ( $arrDirDestino as $emailDestino ) {
                if ( !empty ($emailDestino) ) {
                    $emailDestino = trim($emailDestino);
                    //$emailDestino = str_replace(",","",$emailDestino);
                    $mail->AddCC( $emailDestino );
                    $usrEmailCC= $usrEmailCC . $emailDestino."<br>";
                }
            }
        }
        $mail->AddBCC("nestor@nesoftware.net");
        
        // Subject :
        $mail->Subject = "[EDIFACTORY][$navCode] Notificacion de envío EDICODECO ";

        //Incluir Attach.
        $file1N = str_replace("../edi_files/edi/","",$file1);
        $file2N = str_replace("../edi_files/edi/","",$file2);
        if( file_exists($file1)){ $mail->AddAttachment($file1,$file1N);}
        if( file_exists($file2)){ $mail->AddAttachment($file2,$file2N);}
        //if( is_array($arrEdiFile) ){
        $exito = $mail->Send();
        /*
        // PARA INTAR REENVIARLO
        //Si el mensaje no ha podido ser enviado se realizaran 4 intentos mas como mucho
        //para intentar enviar el mensaje, cada intento se hara 5 segundos despues
        //del anterior, para ello se usa la funcion sleep
        $intentos=1;
        while ((!$exito) && ($intentos < 5)) {
        sleep(5);
        $exito = $mail->Send();
        $intentos=$intentos+1;
        }
        */
        if( !$exito ){
            echo "[ <font color=red><b>ERROR</b>] Problema de envío Email : ".$mail->ErrorInfo."<hr>";
        }
        else{
            echo "[<b><font color=green>OK</b></font>]  Email enviado a : $usrEmail , CC: $usrEmailCC <hr>";
        }

    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function sendNotifyMSC($sesIdUsuario,$idNav,$file1="",$file2="",$file3="",$opcPro){
        global $hoy;

        // Email del usuario
        $usrEmail = getValueTable("email","USUARIO","id_usuario",$sesIdUsuario);
        $usrEmailCC = getValueTable("email_cc","USUARIO","id_usuario",$sesIdUsuario);
        $razonSocial = getValueTable("razon_social","USUARIO","id_usuario",$sesIdUsuario);
        $navCode = "MSC";

        // ------------------------------------------------
        // CONFIGURAR EMAIL.
        // ------------------------------------------------
        //Definimos las propiedades y llamamos a los métodos
        //correspondientes del objeto mail

        //Con PluginDir le indicamos a la clase phpmailer donde se
        //encuentra la clase smtp que como he comentado al principio de
        //este ejemplo va a estar en el subdirectorio includes
        $mail = new phpmailer();
        $mail->Priority=0; // Se declara la prioridad del mensaje.
        $mail->PluginDir = "../include/";
        $mail->Mailer = "smtp";

        // Configurar la cuenta de correo.
        $mail->Host = "vishnu.hosting-mexico.net";
        $mail->SMTPAuth = true;
        $mail->Username = "robot@edifactory.org";
        $mail->Password = "!robotedifactory";
        $mail->From = "robot@edifactory.org";
        $mail->FromName = "Robot EdiFactory";




        //El valor por defecto 10 de Timeout es un poco escaso dado que voy a usar
        //una cuenta gratuita, por tanto lo pongo a 30
        //$mail->Timeout=10;
        $mail->Timeout=10;

        // --------------------
        // FORMATO HTML
        // --------------------
        $opcPro = strtoupper($opcPro);
        $mail->Body = "
        <html>
        <body>
        <b>
        EDICODECO-D95<br>
        Notificación de envío exitoso<br>
        </b>
        <br>
        Los archivos EDI han sido transmitidos con éxito a la naviera.
        De cualquier forma se adjunta copia de los mismos para los fines que considere necesarios.<br>
        <br>
        De : $razonSocial<br>
        Para : $navCode <br>
        Medio : $opcPro <br>
        <br>
        <br>
        Att. Robot Edifactory <br>
        <br>
        <br>
        <hr>
        <font color=\"red\" size=\"2\">
        <i>
        Nota : 
        Este es un correo de envío automático generado por el sistema www.edifactory.org, por favor NO responda este email ya que no será contestado.
        </i>
        </font>
        <br>
        <br>
        <br>

        </body>
        </html>

        ";

        // -------------------------------------------------------
        // FORMATO TEXTO
        // Definimos AltBody por si el destinatario del correo
        // no admite email con formato html
        // -------------------------------------------------------
        $mail->AltBody = "
        =====================================================================
        ";

        // Nota :
        // La direccion PARA solo se puede manejar 1.
        // Las direcciones CC puede manejar N correos.

        // -------------
        // Destinatarios
        // -------------
        $mail->ClearAddresses();
        // ------------------------------------------------

        // TO :
        $mail->AddAddress ( $usrEmail );

        // COPIA A:
        //$usrEmailCC="";
        if( !empty($usrEmailCC) ){
            $arrDirDestino = explode("\n",$usrEmailCC);
            foreach ( $arrDirDestino as $emailDestino ) {
                if ( !empty ($emailDestino) ) {
                    $emailDestino = trim($emailDestino);
                    //$emailDestino = str_replace(",","",$emailDestino);
                    $mail->AddCC( $emailDestino );
                    $usrEmailCC= $usrEmailCC . $emailDestino."<br>";
                }
            }
        }

        if( $idNav==1 ){
            // BCC :
            $mail->AddBCC("lrodriguez@mscmexico.com");
            $mail->AddBCC("nestor@nesoftware.net");
        }

        // Subject :
        $mail->Subject = "[EDIFACTORY][MSC] Notificacion de envío EDICODECO";

        //Incluir Attach.
        $file1N = str_replace("../edi_files/edi/","",$file1);
        $file2N = str_replace("../edi_files/edi/","",$file2);
        $file3N = str_replace("../edi_files/edi/","",$file3);
        if( file_exists($file1)){ $mail->AddAttachment($file1,$file1N);}
        if( file_exists($file2)){ $mail->AddAttachment($file2,$file2N);}
        if( file_exists($file3)){ $mail->AddAttachment($file3,$file3N);}

        /*
        // Leer un directorio
        $path ='../edi_files/edi/';
        $directorio = opendir($path); //ruta actual
        while ( $archivo = readdir($directorio)){

            if( $sesIdUsuario==10 ){
                // 
                if( preg_match("/TRANE_$navCode/i",$archivo) ){
                    $tamanio = filesize($path.$archivo);
                    if( $tamanio > 50 ){
                        $mail->AddAttachment($path.$archivo,$archivo);
                    }
                }
            }
            else{
                $fileZ = $sesIdUsuario.$navCode;
                if( preg_match("/^$fileZ.+/i",$archivo) ){
                    $tamanio = filesize($path.$archivo);
                    if( $tamanio > 50 ){
                        $mail->AddAttachment($path.$archivo,$archivo);
                    }
                }
            }
        }
        */

        // Se envia el mensaje, si no ha habido problemas, la variable $exito tendra el valor true
        //if( is_array($arrEdiFile) ){
        $exito = $mail->Send();
        /*
        // PARA INTAR REENVIARLO
        //Si el mensaje no ha podido ser enviado se realizaran 4 intentos mas como mucho
        //para intentar enviar el mensaje, cada intento se hara 5 segundos despues
        //del anterior, para ello se usa la funcion sleep
        $intentos=1;
        while ((!$exito) && ($intentos < 5)) {
        sleep(5);
        $exito = $mail->Send();
        $intentos=$intentos+1;
        }
        */

        if( !$exito ){
            echo "[ <font color=red><b>ERROR</b>] Problema de envío Email : ".$mail->ErrorInfo."<hr>";
        }
        else{
            echo "[<b><font color=green>OK</b></font>]  Email enviado a : $usrEmail , CC: $usrEmailCC <hr>";
        }
    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function showForm($form="",$msg=""){
        global $t,$PHP_SELF,$sesOficina,$sesIdUsuario,$db,$db2;

        $t->set_file("pageH", "header.inc.html");
        $t->set_var("SESUSUARIO",$_SESSION['sesUsuario']);
        $t->pparse("out","pageH");
        $t->set_file("page", "converterVgm.inc.html");

        // inicializar vars
        $t->set_var("ACTION",$PHP_SELF);
        $t->set_var("MENSAJE","");
        $t->set_var("OFICINA",$sesOficina);




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

        $t->set_file("pageF", "footer.inc.html");
        $t->pparse("out","pageF");        
    }
    // ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function getOkMes($mes){
        $mes = strtolower($mes);
        switch($mes){
            case "ene":$mm="01";break;
            case "feb":$mm="02";break;
            case "mar":$mm="03";break;
            case "abr":$mm="04";break;
            case "may":$mm="05";break;
            case "jun":$mm="06";break;
            case "jul":$mm="07";break;
            case "ago":$mm="08";break;
            case "sep":$mm="09";break;
            case "oct":$mm="10";break;
            case "nov":$mm="11";break;
            case "dic":$mm="12";break;

            case "jan":$mm="01";break;
            case "feb":$mm="02";break;
            case "mar":$mm="03";break;
            case "apr":$mm="04";break;
            case "may":$mm="05";break;
            case "jun":$mm="06";break;
            case "jul":$mm="07";break;
            case "ago":$mm="08";break;
            case "sep":$mm="09";break;
            case "oct":$mm="10";break;
            case "nov":$mm="11";break;
            case "dic":$mm="12";break;
        }
        return $mm;

    }
    // ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateInHS($stConte,$sesIdUsuario,$formato,$idNav,$opcPro){

        if( $formato=="excel2007" ) {
            // EXCEL 2007
            // ---------------------------------------------------
            // PROCESO COPIAR ARCHIVO
            // ---------------------------------------------------
            if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/entradas$sesIdUsuario.xlsx");
            } else {
                echo "Error de archivo!.";
            }
            // -------------------------------------
            // GET-IN
            // -------------------------------------
            unset($fp);
            unset($data);
            unset($dataX);
            // ---------------------------------
            // ---- LEER ARCHIVO DE EXCEL ---
            // ---------------------------------
            $objReader = new PHPExcel_Reader_Excel2007();
            //$objReader = new PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader->load("../edi_files/csv/entradas$sesIdUsuario.xlsx");
            $objPHPExcel->setActiveSheetIndex(0);
        }
        elseif( $formato=="excel2005" ) {
            // EXCEL 2007
            // ---------------------------------------------------
            // PROCESO COPIAR ARCHIVO
            // ---------------------------------------------------
            if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/entradas$sesIdUsuario.xls");
            } else {
                echo "Error de archivo!.";
            }
            // -------------------------------------
            // GET-IN
            // -------------------------------------
            unset($fp);
            unset($data);
            unset($dataX);
            // ---------------------------------
            // ---- LEER ARCHIVO DE EXCEL ---
            // ---------------------------------
            $objReader = new PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader->load("../edi_files/csv/entradas$sesIdUsuario.xls");
            $objPHPExcel->setActiveSheetIndex(0);

        }

        $validFile="True";
        $l=1;

        // ------------------
        // Validacion campos
        // ------------------
        // ---------------------------------
        // Validar el encabezado
        // ----------------------------------
        $encC1= $objPHPExcel->getActiveSheet()->getCell("A5")->getValue();
        $encC2= $objPHPExcel->getActiveSheet()->getCell("B5")->getValue();
        $encC3= $objPHPExcel->getActiveSheet()->getCell("C5")->getValue();
        $encC4= $objPHPExcel->getActiveSheet()->getCell("D5")->getValue();
        $encC5= $objPHPExcel->getActiveSheet()->getCell("E5")->getValue();
        $encC6= $objPHPExcel->getActiveSheet()->getCell("F5")->getValue();
        $encC7= $objPHPExcel->getActiveSheet()->getCell("G5")->getValue();
        $encC8= $objPHPExcel->getActiveSheet()->getCell("H5")->getValue();
        $encC9= $objPHPExcel->getActiveSheet()->getCell("I5")->getValue();
        $encC10= $objPHPExcel->getActiveSheet()->getCell("J5")->getValue();
        $encC11= $objPHPExcel->getActiveSheet()->getCell("K5")->getValue();
        $encC12= $objPHPExcel->getActiveSheet()->getCell("L5")->getValue();

        if( $encC1!="FECHA"){$validFile=0;echo "[Error Encabezado] <br>";}
        if( $encC2!="HORA" ){$validFile=0;echo "[Error Encabezado] HORA<br>";}
        if( $encC3!="CONTENEDOR"){$validFile=0;echo "[Error Encabezado] CONTENEDOR<br>";}
        if( $encC4!="TIPO" ){$validFile=0;echo "[Error Encabezado] TIPO<br>";}
        if( $encC5!="EIR" ){$validFile=0;echo "[Error Encabezado] EIR<br>";}
        if( $encC6!="MANIOBRA" ){$validFile=0;echo "[Error Encabezado] MANIOBRA<br>";}
        if( $encC7!="TRANSPORTISTA" ){$validFile=0;echo "[Error Encabezado] TRANSPORTISTA<br>";}
        if( $encC8!="CLIENTE" ){$validFile=0;echo "[Error Encabezado] CLIENTE <br>";}
        if( $encC9!="NOTA" ){$validFile=0;echo "[Error Encabezado] NOTA <br>";}
        if( $encC10!="SELLO" ){$validFile=0;echo "[Error Encabezado] SELLO <br>";}
        if( $encC11!="I_E" ){$validFile=0;echo "[Error Encabezado] I_E <br>";}
        if( $encC12!="TIPO_INGRESO" ){$validFile=0;echo "[Error Encabezado] Falta, \"TIPO_INGRESO\" en columna L<br>";}
        // ---- Fin de la validación ----

        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $array_data = array();

        foreach($rowIterator as $row){
            $lin = $row->getRowIndex ();
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            $rowIndex = $row->getRowIndex ();
            foreach ($cellIterator as $cell) {
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn() ){
                    //$fecha = $cell->getValue();
                    $fecha = $cell->getFormattedValue();
                    // Validar Fecha : En caso de que los formatos sean 01-mes-15
                    unset($parts);
                    if( preg_match("/(\d{1,2})-(\w{3,3})-(\d{2,2})/i",$fecha,$parts) ){
                        // echo "FECHA:  $fecha OK <br>";
                        $dd = $parts[1];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $mes = $parts[2];
                        $aa = 2000 + $parts[3];
                        $mm = getOkMes($mes);
                        $fecha = $aa."-".$mm."-".$dd;
                    }

                    if( $lin>5 && !empty($fecha) && ( !preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha)  ) ){
                        echo "[Error] Lin: $lin | Fecha : $fecha | El formato es incorrecto, cambie el formato como -> aaaa-mm-dd  <br>";
                        $validFile=0;
                    }
                    $datos[$rowIndex]['FECHA'] = $fecha;

                }
                if('B' == $cell->getColumn()){
                    $hora = $cell->getFormattedValue();
                    unset($parts);
                    if( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) PM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        if( $hh<12 ){
                            $hh = $hh + 12;
                        }
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) AM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/^(\d{1,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    $datos[$rowIndex]['HORA'] = $hora;

                }
                if('C' == $cell->getColumn()){
                    $conte = $cell->getValue();
                    $conte = strtoupper($conte);
                    $datos[$rowIndex]['CONTENEDOR'] = $conte;
                }
                if('D' == $cell->getColumn()){
                    $datos[$rowIndex]['EQUIPO'] = $cell->getValue();
                }
                if('E' == $cell->getColumn()){
                    $datos[$rowIndex]['EIR'] = $cell->getValue();
                }
                if('F' == $cell->getColumn()){
                    $datos[$rowIndex]['MANIOBRA'] = $cell->getValue();
                }
                if('G' == $cell->getColumn()){
                    $datos[$rowIndex]['TRANSPORTISTA'] = $cell->getValue();
                }
                if('H' == $cell->getColumn()){
                    $datos[$rowIndex]['CLIENTE'] = $cell->getValue();
                }
                if('I' == $cell->getColumn()){
                    $datos[$rowIndex]['NOTA'] = $cell->getValue();
                }
                if('J' == $cell->getColumn()){
                    $datos[$rowIndex]['SELLO'] = $cell->getValue();
                }
                if('K' == $cell->getColumn()){
                    $datos[$rowIndex]['I_E'] = $cell->getValue();
                }
                if('L' == $cell->getColumn()){
                    $datos[$rowIndex]['TIPO_INGRESO'] = $cell->getValue();
                }

            }
        }


        // ------------------------------------------------
        // Validar datos despues del encabezado.
        // ------------------------------------------------
        $l=1;
        foreach ($datos as $dato => $x) {
            if( $l>5 ) {
                $validFec=0;
                if( !empty($x['CONTENEDOR']) ){
                    $tIngresoT = $x['TIPO_INGRESO'];
                    $tIngresoT = strtoupper($tIngresoT);
                    if( $tIngresoT!='GC' && $tIngresoT!='FG' && $tIngresoT!='DG' ){
                        echo "<b><font color=red>[ERROR]</b> Linea $l : Tipo de Ingreso no especificado (GC/FG/DG)...<br></font>";
                        $validFile="False";
                    }
                }
            }
            $l++;
        }



        if( $validFile=="True" ) {
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $flgRec = 1;

            $l = 1;
            $f = date(Ymd);

            // ------------------------------------------------
            // Generar archivo EDI
            // ------------------------------------------------
            $fileEDI = "../edi_files/edi/".$sesIdUsuario."HSD_GATE_IN_".$fecD1.$fecD2.".edi";
            $fileName = $sesIdUsuario."HSD_GATE_IN".$fecD1.$fecD2.".edi";
            $fp2 = fopen("$fileEDI", "w");
            
            $codPatio = getValueTable("cod_patio", "USUARIO", "id_usuario", $sesIdUsuario);
            $codPatioName = getValueTable("cod_patio_name", "USUARIO", "id_usuario", $sesIdUsuario);
            $senderID = getValueTable("sender_id", "USUARIO", "id_usuario", $sesIdUsuario);
            $receiverID = "HSUD";
            
            // ------------------------
            // SENDER_ID SETTINGS
            // ------------------------
            // Terraports Cd.Mexico; Id_usuario = 12; codigo= MEXK2
            if( $sesIdUsuario==12 ){
                $senderID = "MEXK2";
            }
            
            // Lition
            // SenederID: QROLT
            if( $sesIdUsuario==18 ){
                $senderID = "QROLT";
            } 

            // ENCABEZADO
            // MENSAJES CODIFICACION
            // 872 = Return empty container to facility / Regreso de vacio a la instalacón.
            $unbCodFin = 135;
            $sepa = "\r\n";
            //$lin= "UNB+UNOA:2+$senderID+HSUD+$fecD1:$fecD2+$fecD1$fecD2+$unbCodFin'".$sepa;
            $lin= "UNB+UNOA:2+$senderID+HSUD+$fecD1:$fecD2+$unbCodFin'".$sepa;
            fputs($fp2, $lin);
            $tlSegmentos++;
            $lin= "UNH+1+CODECO:D:95B:UN:ITG14'".$sepa;
            fputs($fp2, $lin);
            $tlSegmentos++;
            $lin= "BGM+34+$unbCodFin+9'".$sepa;
            fputs($fp2, $lin);
            $tlSegmentos++;
            // Ej, NAD+CF+HSD:172'  // Tal vez el 172 cambie
            $lin= "NAD+MS+$senderID:172:20'\r\n";
            fputs($fp2, $lin);
            $tlSegmentos++;
            
            $tlConte = 0;
            // -------------------------------------------------
            // CICLO POR CONTENEDOR. OBTENIENDO LA INFO POR CADA CONTENEDOR
            // -------------------------------------------------
            $l=1;
            foreach ($datos as $dato => $x) {

                if( $l>5 ) {
                    $validFec=0;
                    $fecha = $x['FECHA'];
                    if( preg_match("/(\d{4})-(\d{2})-(\d{2})$/i",$fecha,$parts) ){
                        $d= $parts[3];
                        $m= $parts[2];
                        $a= $parts[1];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }
                    $hora = $x['HORA'];
                    $hora = strtolower($hora);
                    $hora = str_replace(" ","",$hora);
                    $hora = str_replace(":","",$hora);
                    $hora = str_replace("-","",$hora);
                    $hora = str_replace(".","",$hora);
                    $hora = str_replace("pm","",$hora);
                    $hora = str_replace("am","",$hora);
                    $hora = str_pad($hora,4,"0", STR_PAD_LEFT);
                    $regHora = $hora;
                    $conte = $x['CONTENEDOR'];
                    $conte = strtoupper($conte);
                    $conte = str_replace("-","",$conte);
                    $conte = str_replace("/","",$conte);
                    $conte = str_replace(" ","",$conte);
                    $equipo = $x['EQUIPO'];
                    $tipo = getEquipoTipo($equipo);
                    $eir = $x['EIR'];
                    $maniobra = $x['MANIOBRA'];
                    $transportista = $x['TRANSPORTISTA'];
                    $cliente = $x['CLIENTE'];
                    $cliente = strtoupper($cliente);
                    $bl = $x['BL'];
                    $sello = $x['SELLO'];
                    $impoExpo = $x['I_E'];
                    $tipoIngreso = $x['TIPO_INGRESO'];
                    $tipoIngreso = strtoupper($tipoIngreso);

                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;

                        // ---------------------------------------------------------
                        // COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                        // ----------------------------------------------------------
                        ($impoExpo=="E")?$stIE=2:$stIE=3;
                        ($stConte=="E")?$stCo=4:$stCo=5;
                        $conte = strtoupper($conte);
                        
                        $lin= "EQD+CN+$conte+$tipo:102:5++$stIE+$stCo'".$sepa;
                        fputs($fp2,$lin);
                        $tlSegmentos++;
                        $lin="DTM+7:$regFecha$regHora:203'".$sepa;
                        fputs($fp2,$lin);
                        $tlSegmentos++;
                        
                        // Pendiente de agregar en plantilla-> DAMAGE = OK / DM 
                        if($tipoIngreso=="DG"){
                            $damage="DM";
                        }
                        else{
                            $damage="OK";
                        }
                        
                        $lin = "FTX+DAR++$damage::184'".$sepa;
                        fputs($fp2, $lin);
                        $tlSegmentos++;
                        // Pendiente de agregar a la plantilla.
                        // Tipo de ingreso : GC = General Cargo, FG = Food Grade, DG = Dañado.
                        $lin = "FTX+DAR+++$tipoIngreso'".$sepa;
                        fputs($fp2, $lin);
                        $tlSegmentos++;

                        if( $damage=="DM" || $tipoIngreso=="DG" ) {
                            // ----- DAÑADOS Inicia Segmento -----
                            // Ej. 3 lineas en caso de ingresar dañados.
                            // FTX+DAR++OK:130:184'
                            // FTX+DAR+++DG'
                            // DAM+1+98:::Para Ser Limpiados'
                            // --------------------------------
                            // $lin = "FTX+DAR++$damage:ZZZ:184'\r\n";
                            // fputs($fp2, $lin);
                            // $tlSegmentos++;
                            // $lin = "FTX+DAR+++$claseHS'\r\n";
                            // fputs($fp2, $lin);
                            // $tlSegmentos++;
                            // $lin = "DAM+1+98:::$danoNota'\r\n";
                            $lin = "DAM+1'".$sepa;
                            fputs($fp2, $lin);
                            $tlSegmentos++;
                            // --------- DAÑADOS Finaliza Segmento ----------
                        }

                        $placas = str_replace(".", "", $placas);
                        if( empty($placas) ){
                            $placas="";
                        }                    
                        $operador = normaliza($operador);
                        //$lin= "TDT+30++3++:::$transportista-$operador++$placas'\r\n";
                        $operador = substr($operador, 0,35);
                        $placas = str_replace("-", "", $placas);
                        $placas = str_replace(".", "", $placas);
                        $placas = str_replace(" ", "", $placas);                    
                        $lin= "TDT+1++3+++++$placas'".$sepa;
                        fputs($fp2, $lin);
                        $tlSegmentos++;
                        $flgOkFile=1;
                    }

                }
                $l++;
            }
            
            $lin = "CNT+16:1'\r\n";
            fputs($fp2, $lin);
            $tlSegmentos++;
            $lin = "UNT+$tlSegmentos+1'\r\n";
            fputs($fp2, $lin);
            $lin = "UNZ+1+$unbCodFin'";
            fputs($fp2, $lin);
            fclose($fp2);
            
            unset($rowIterator);
            unset($datos);


            if( $flgOkFile==1 ) {
                if ($opcPro == "email") {
                    //sendNotifyOneFile($sesIdUsuario, 'HAMBURG SUD', $fileEDI, $fileName);
                } 
                elseif ($opcPro == "ftp") {
                    // -----------------------------
                    // FTP - GETIN
                    // -----------------------------
                    if ($sesIdUsuario == 12 ) {
                        // Terraporrts
                        sentToFTP($fileEDI,'HSTERR','/from/');
                    }
                }
                return $fileEDI;
            }
        }

    }
    // ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateOutHS($stConte,$sesIdUsuario,$formato,$idNav,$opcPro){


        if( $formato=="excel2007" ) {
            // EXCEL 2007 o mayor
            // ---------------------------------------------------
            // PROCESO COPIAR ARCHIVO
            // ---------------------------------------------------
            if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/salidas$sesIdUsuario.xlsx");
            } else {
                echo "Error de archivo!.";
            }
            // -------------------------------------
            // GET-IN
            // -------------------------------------
            unset($fp);
            unset($data);
            unset($dataX);
            // ---------------------------------
            // ---- LEER ARCHIVO DE EXCEL ---
            // ---------------------------------
            $objReader = new PHPExcel_Reader_Excel2007();
            //$objReader = new PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader->load("../edi_files/csv/salidas$sesIdUsuario.xlsx");
            $objPHPExcel->setActiveSheetIndex(1);
        }
        elseif( $formato=="excel2005" ) {
            // EXCEL 2007 o mayor
            // ---------------------------------------------------
            // PROCESO COPIAR ARCHIVO
            // ---------------------------------------------------
            if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/salidas$sesIdUsuario.xls");
            } else {
                echo "Error de archivo!.";
            }
            // -------------------------------------
            // GET-IN
            // -------------------------------------
            unset($fp);
            unset($data);
            unset($dataX);
            // ---------------------------------
            // ---- LEER ARCHIVO DE EXCEL ---
            // ---------------------------------
            $objReader = new PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader->load("../edi_files/csv/salidas$sesIdUsuario.xls");
            $objPHPExcel->setActiveSheetIndex(1);
        }

        $validFile="True";
        $l=1;

        // ---------------------------------
        // Validar el encabezado
        // ----------------------------------
        $encC1= $objPHPExcel->getActiveSheet()->getCell("A5")->getValue();
        $encC2= $objPHPExcel->getActiveSheet()->getCell("B5")->getValue();
        $encC3= $objPHPExcel->getActiveSheet()->getCell("C5")->getValue();
        $encC4= $objPHPExcel->getActiveSheet()->getCell("D5")->getValue();
        $encC5= $objPHPExcel->getActiveSheet()->getCell("E5")->getValue();
        $encC6= $objPHPExcel->getActiveSheet()->getCell("F5")->getValue();
        $encC7= $objPHPExcel->getActiveSheet()->getCell("G5")->getValue();
        $encC8= $objPHPExcel->getActiveSheet()->getCell("H5")->getValue();
        $encC9= $objPHPExcel->getActiveSheet()->getCell("I5")->getValue();
        $encC10= $objPHPExcel->getActiveSheet()->getCell("J5")->getValue();
        $encC11= $objPHPExcel->getActiveSheet()->getCell("K5")->getValue();
        $encC12= $objPHPExcel->getActiveSheet()->getCell("L5")->getValue();
        $encC13= $objPHPExcel->getActiveSheet()->getCell("M5")->getValue();
        $encC14= $objPHPExcel->getActiveSheet()->getCell("N5")->getValue();
        $encC15= $objPHPExcel->getActiveSheet()->getCell("O5")->getValue();

        if( $encC1!="FECHA"){$validFile=0;echo "[Error Encabezado] FECHA<br>";}
        if( $encC2!="HORA" ){$validFile=0;echo "[Error Encabezado] HORA<br>";}
        if( $encC3!="CONTENEDOR" ){$validFile=0;echo "[Error Encabezado] CONTENEDOR<br>";}
        if( $encC4!="TIPO" ){$validFile=0;echo "[Error Encabezado] TIPO<br>";}
        if( $encC5!="EIR" ){$validFile=0;echo "[Error Encabezado] EIR<br>";}
        if( $encC6!="MANIOBRA" ){$validFile=0;echo "[Error Encabezado] MANIOBRA<br>";}
        if( $encC7!="TRANSPORTISTA" ){$validFile=0;echo "[Error Encabezado] TRANSPORTISTA<br>";}
        if( $encC8!="CLIENTE" ){$validFile=0;echo "[Error Encabezado] CLIENTE<br>";}
        if( $encC9!="AGENTE ADUANAL" ){$validFile=0;echo "[Error Encabezado] AGENTE ADUANAL <br>";}
        if( $encC10!="BL" ){$validFile=0;echo "[Error Encabezado] BL <br>";}
        if( $encC11!="BOOKING" ){$validFile=0;echo "[Error Encabezado] BOOKING <br>";}
        if( $encC12!="BUQUE" ){$validFile=0;echo "[Error Encabezado] BUQUE <br>";}
        if( $encC13!="VIAJE" ){$validFile=0;echo "[Error Encabezado] VIAJE <br>";}
        if( $encC14!="SELLO" ){$validFile=0;echo "[Error Encabezado] SELLO <br>";}
        if( $encC15!="I_E" ){$validFile=0;echo "[Error Encabezado] I_E <br>";}
        // ---- Fin de la validación ----


        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $array_data = array();
        foreach($rowIterator as $row){
            $lin = $row->getRowIndex ();
            //echo "fila ".$row->getRowIndex ();
            //echo "<br>";
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            //if(1 == $row->getRowIndex ()) continue; //skip first row la del encabezado
            $rowIndex = $row->getRowIndex ();
            //$array_data[$rowIndex] = array('A'=>'', 'B'=>'','C'=>'','D'=>'','E'=>'');

            foreach ($cellIterator as $cell) {
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn() ){
                    //$fecha = $cell->getValue();
                    $fecha = $cell->getFormattedValue();
                    // Validar Fecha : En caso de que los formatos sean 01-mes-15
                    unset($parts);
                    if( preg_match("/(\d{1,2})-(\w{3,3})-(\d{2,2})/i",$fecha,$parts) ){
                        // echo "FECHA:  $fecha OK <br>";
                        $dd = $parts[1];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $mes = $parts[2];
                        $aa = 2000 + $parts[3];
                        $mm = getOkMes($mes);
                        $fecha = $aa."-".$mm."-".$dd;
                    }
                    if( $lin>5 && !empty($fecha) && ( !preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha)  ) ){
                        echo "[Error] Lin: $lin | Fecha : $fecha | El formato es incorrecto, cambie el formato como -> aaaa-mm-dd  <br>";
                        $validFile=0;
                    }
                    $datos[$rowIndex]['FECHA'] = $fecha;
                }
                if('B' == $cell->getColumn()){
                    $hora = $cell->getFormattedValue();
                    unset($parts);
                    if( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) PM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        if( $hh<12 ){
                            $hh = $hh + 12;
                        }
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) AM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/^(\d{1,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    $datos[$rowIndex]['HORA'] = $hora;
                }
                if('C' == $cell->getColumn()){
                    $conte = $cell->getValue();
                    $conte = strtoupper($conte);
                    $datos[$rowIndex]['CONTENEDOR'] = $conte;
                }
                if('D' == $cell->getColumn()){
                    $datos[$rowIndex]['EQUIPO'] = $cell->getValue();
                }
                if('E' == $cell->getColumn()){
                    $datos[$rowIndex]['EIR'] = $cell->getValue();
                }
                if('F' == $cell->getColumn()){
                    $datos[$rowIndex]['MANIOBRA'] = $cell->getValue();
                }
                if('G' == $cell->getColumn()){
                    $datos[$rowIndex]['TRANSPORTISTA'] = $cell->getValue();
                }
                if('H' == $cell->getColumn()){
                    $datos[$rowIndex]['CLIENTE'] = $cell->getValue();
                }
                if('I' == $cell->getColumn()){
                    $datos[$rowIndex]['AGENTE ADUANAL'] = $cell->getValue();
                }
                if('J' == $cell->getColumn()){
                    $datos[$rowIndex]['BL'] = $cell->getValue();
                }
                if('K' == $cell->getColumn()){
                    $datos[$rowIndex]['BOOKING'] = $cell->getValue();
                }
                if('L' == $cell->getColumn()){
                    $datos[$rowIndex]['BUQUE'] = $cell->getValue();
                }
                if('M' == $cell->getColumn()){
                    $datos[$rowIndex]['VIAJE'] = $cell->getValue();
                }
                if('N' == $cell->getColumn()){
                    $datos[$rowIndex]['SELLO'] = $cell->getValue();
                }
                if('O' == $cell->getColumn()){
                    $datos[$rowIndex]['I_E'] = $cell->getValue();
                }

            }
        }

        if( $validFile=="True" ) {
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $flgRec = 1;

            $l = 1;
            $f = date(Ymd);

            // ------------------------------------------------
            // Generar archivo EDI
            // ------------------------------------------------
            $fileEDI = "../edi_files/edi/".$sesIdUsuario."HS_GATE_OUT".$fecD1.$fecD2.".edi";
            $fileName = $sesIdUsuario."HS_GATE_OUT".$fecD1.$fecD2.".edi";
            $fp2 = fopen("$fileEDI", "w");
            $sepa = "\r\n";
            $codPatio = getValueTable("cod_patio", "USUARIO", "id_usuario", $sesIdUsuario);
            $codPatioName = getValueTable("cod_patio_name", "USUARIO", "id_usuario", $sesIdUsuario);
            $senderID = getValueTable("sender_id", "USUARIO", "id_usuario", $sesIdUsuario);
            $receiverID = "HSUD";
            
            // ------------------------
            // SENDER_ID SETTINGS
            // ------------------------
            // Terraports Cd.Mexico; Id_usuario = 12; codigo= MEXK2
            if( $sesIdUsuario==12 ){
                $senderID = "MEXK2";
            }
            // Lition
            // SenederID: QROLT
            if( $sesIdUsuario==18 ){
                $senderID = "QROLT";
            } 
            

            // ENCABEZADO
            // MENSAJES CODIFICACION
            // 135 = Empty container from facility to customer
            $unbCodFin = "135";
            //$lin= "UNB+UNOA:2+$senderID+HSUD+$fecD1:$fecD2+$unbCodFin'\r\n";
            $lin= "UNB+UNOA:2+$senderID+HSUD+$fecD1:$fecD2+$unbCodFin'\r\n";
            fputs($fp2, $lin);
            $tlSegmentos++;
            $lin= "UNH+1+CODECO:D:95B:UN:ITG14'\r\n";
            fputs($fp2, $lin);
            $tlSegmentos++;
            $lin= "BGM+36+$unbCodFin+9'\r\n";
            fputs($fp2, $lin);
            $tlSegmentos++;
            $lin= "NAD+MS+$senderID:172:20'\r\n";
            fputs($fp2, $lin);
            $tlSegmentos++;          
            $tlConte = 0;

            // -------------------------------------------------
            // OBTENIENDO LA INFO
            // -------------------------------------------------
            $l=1;
            foreach ($datos as $dato => $x) {

                if( $l>5 ) {
                    $validFec=0;
                    $fecha = $x['FECHA'];
                    if( preg_match("/(\d{4})-(\d{2})-(\d{2})$/i",$fecha,$parts) ){
                        $a= $parts[1];
                        $m= $parts[2];
                        $d= $parts[3];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }

                    $hora = $x['HORA'];
                    $hora = strtolower($hora);
                    $hora = str_replace(" ","",$hora);
                    $hora = str_replace(":","",$hora);
                    $hora = str_replace("-","",$hora);
                    $hora = str_replace(".","",$hora);
                    $hora = str_replace("pm","",$hora);
                    $hora = str_replace("am","",$hora);
                    $regHora = $hora;

                    $conte = $x['CONTENEDOR'];
                    $conte = strtoupper($conte);
                    $conte = str_replace("-","",$conte);
                    $conte = str_replace("/","",$conte);
                    $conte = str_replace(" ","",$conte);

                    $equipo = $x['EQUIPO'];
                    $tipo = getEquipoTipo($equipo);
                    // $eir = $x['EIR'];
                    $maniobra = $x['MANIOBRA'];
                    $transportista = $x['TRANSPORTISTA'];
                    $cliente = $x['CLIENTE'];
                    $cliente = strtoupper($cliente);
                    $bkgNumber = $x['BOOKING'];
                    $bkgNumber = strtoupper($bkgNumber);
                    $bl = $x['BL'];
                    $sello = $x[SELLO];
                    $impoExpo = $x[I_E];


                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;

                        // ---------------------------------------------------------
                        // COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                        // ----------------------------------------------------------
                        ($impoExpo=="E")?$stIE=2:$stIE=3;
                        ($stConte=="E")?$stCo=4:$stCo=5;
                        $conte = strtoupper($conte);
                        
                        $lin= "EQD+CN+$conte+$tipo:102:5++$stIE+$stCo'".$sepa;
                        fputs($fp2, $lin);
                        $tlSegmentos++;
                        
                        if (!empty($bkgNumber)) {
                            $bkgNumber = str_replace(" ", "", $bkgNumber);
                            $bkgNumber = str_replace(".", "", $bkgNumber);
                            $bkgNumber = str_replace("/", "", $bkgNumber);
                            $bkgNumber = str_replace("-", "", $bkgNumber);
                            $lin= "RFF+BN:$bkgNumber'\r\n";
                            fputs($fp2, $lin);
                            $tlSegmentos++;
                        }
                        
                        $lin= "DTM+7:$regFecha$regHora:203'\r\n";
                        fputs($fp2, $lin);
                        $tlSegmentos++;

                        // Pendiente, analizar si tiene lugar para los sellos la plantilla.
                        $lin="SEL+$sello+CA'\r\n";
                        fputs($fp2, $lin);
                        $tlSegmentos++;
                       
                        $placas = str_replace(".", "", $placas);
                        if( empty($placas) ){
                            $placas="";
                        }
                        $operador = normaliza($operador);
                        $operador = substr($operador, 0,35);
                        $placas = str_replace("-", "", $placas);
                        $placas = str_replace(".", "", $placas);
                        $placas = str_replace(" ", "", $placas);                    
                        $lin= "TDT+1++3+++++$placas'\r\n";
                        fputs($fp2, $lin);
                        $tlSegmentos++;

                        $flgOkFile=1;
                    }

                }
                $l++;
            }

            $lin = "CNT+16:1'\r\n";
            fputs($fp2, $lin);
            $tlSegmentos++;
            $lin = "UNT+$tlSegmentos+1'\r\n";
            fputs($fp2, $lin);
            $lin = "UNZ+1+$unbCodFin'";
            fputs($fp2, $lin);
            // Cierre de archivo.
            fclose($fp2);
            unset($rowIterator);
            unset($datos);


            if( $flgOkFile==1 ) {
                if ($opcPro == "email") {
                    //sendNotifyOneFile($sesIdUsuario, 'HAMBURG SUD', $fileEDI, $fileName);
                } 
                elseif ($opcPro == "ftp") {
                    // -----------------------------
                    // FTP - GETIN
                    // -----------------------------
                    if ($sesIdUsuario == 12 ) {
                        // Terraporrts
                        sentToFTP($fileEDI,'HSTERR','/from/');
                    }
                }
                return $fileEDI;
            }
        }
    }
    // ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateInMOL($stConte,$sesIdUsuario,$formato,$idNav,$opcPro){

        if( $formato=="excel2007" ) {
            // EXCEL 2007
            // ---------------------------------------------------
            // PROCESO COPIAR ARCHIVO
            // ---------------------------------------------------
            if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/entradas$sesIdUsuario.xlsx");
            } else {
                echo "Error de archivo!.";
            }
            // -------------------------------------
            // GET-IN
            // -------------------------------------
            unset($fp);
            unset($data);
            unset($dataX);
            // ---------------------------------
            // ---- LEER ARCHIVO DE EXCEL ---
            // ---------------------------------
            $objReader = new PHPExcel_Reader_Excel2007();
            //$objReader = new PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader->load("../edi_files/csv/entradas$sesIdUsuario.xlsx");
            $objPHPExcel->setActiveSheetIndex(0);
        }
        elseif( $formato=="excel2005" ) {
            // EXCEL 2007
            // ---------------------------------------------------
            // PROCESO COPIAR ARCHIVO
            // ---------------------------------------------------
            if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/entradas$sesIdUsuario.xls");
            } else {
                echo "Error de archivo!.";
            }
            // -------------------------------------
            // GET-IN
            // -------------------------------------
            unset($fp);
            unset($data);
            unset($dataX);
            // ---------------------------------
            // ---- LEER ARCHIVO DE EXCEL ---
            // ---------------------------------
            $objReader = new PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader->load("../edi_files/csv/entradas$sesIdUsuario.xls");
            $objPHPExcel->setActiveSheetIndex(0);

        }

        $validFile="True";
        $l=1;

        // ------------------
        // Validacion campos
        // ------------------
        // ---------------------------------
        // Validar el encabezado
        // ----------------------------------
        $encC1= $objPHPExcel->getActiveSheet()->getCell("A5")->getValue();
        $encC2= $objPHPExcel->getActiveSheet()->getCell("B5")->getValue();
        $encC3= $objPHPExcel->getActiveSheet()->getCell("C5")->getValue();
        $encC4= $objPHPExcel->getActiveSheet()->getCell("D5")->getValue();
        $encC5= $objPHPExcel->getActiveSheet()->getCell("E5")->getValue();
        $encC6= $objPHPExcel->getActiveSheet()->getCell("F5")->getValue();
        $encC7= $objPHPExcel->getActiveSheet()->getCell("G5")->getValue();
        $encC8= $objPHPExcel->getActiveSheet()->getCell("H5")->getValue();
        $encC9= $objPHPExcel->getActiveSheet()->getCell("I5")->getValue();
        $encC10= $objPHPExcel->getActiveSheet()->getCell("J5")->getValue();
        $encC11= $objPHPExcel->getActiveSheet()->getCell("K5")->getValue();

        if( $encC1!="FECHA"){$validFile=0;echo "[Error Encabezado] <br>";}
        if( $encC2!="HORA" ){$validFile=0;echo "[Error Encabezado] HORA<br>";}
        if( $encC3!="CONTENEDOR"){$validFile=0;echo "[Error Encabezado] CONTENEDOR<br>";}
        if( $encC4!="TIPO" ){$validFile=0;echo "[Error Encabezado] TIPO<br>";}
        if( $encC5!="EIR" ){$validFile=0;echo "[Error Encabezado] EIR<br>";}
        if( $encC6!="MANIOBRA" ){$validFile=0;echo "[Error Encabezado] MANIOBRA<br>";}
        if( $encC7!="TRANSPORTISTA" ){$validFile=0;echo "[Error Encabezado] TRANSPORTISTA<br>";}
        if( $encC8!="CLIENTE" ){$validFile=0;echo "[Error Encabezado] CLIENTE <br>";}
        if( $encC9!="NOTA" ){$validFile=0;echo "[Error Encabezado] NOTA <br>";}
        if( $encC10!="SELLO" ){$validFile=0;echo "[Error Encabezado] SELLO <br>";}
        if( $encC11!="I_E" ){$validFile=0;echo "[Error Encabezado] I_E <br>";}
        // ---- Fin de la validación ----

        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $array_data = array();

        foreach($rowIterator as $row){
            $lin = $row->getRowIndex ();
            //echo "fila ".$row->getRowIndex ();
            //echo "<br>";
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            //if(1 == $row->getRowIndex ()) continue; //skip first row la del encabezado
            $rowIndex = $row->getRowIndex ();
            //$array_data[$rowIndex] = array('A'=>'', 'B'=>'','C'=>'','D'=>'','E'=>'');


            foreach ($cellIterator as $cell) {
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn() ){
                    //$fecha = $cell->getValue();
                    $fecha = $cell->getFormattedValue();
                    // Validar Fecha : En caso de que los formatos sean 01-mes-15
                    unset($parts);
                    if( preg_match("/(\d{1,2})-(\w{3,3})-(\d{2,2})/i",$fecha,$parts) ){
                        // echo "FECHA:  $fecha OK <br>";
                        $dd = $parts[1];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $mes = $parts[2];
                        $aa = 2000 + $parts[3];
                        $mm = getOkMes($mes);
                        $fecha = $aa."-".$mm."-".$dd;
                    }

                    if( $lin>5 && !empty($fecha) && ( !preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha)  ) ){
                        echo "[Error] Lin: $lin | Fecha : $fecha | El formato es incorrecto, cambie el formato como -> aaaa-mm-dd  <br>";
                        $validFile=0;
                    }
                    $datos[$rowIndex]['FECHA'] = $fecha;

                }
                if('B' == $cell->getColumn()){
                    $hora = $cell->getFormattedValue();
                    unset($parts);
                    if( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) PM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        if( $hh<12 ){
                            $hh = $hh + 12;
                        }
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) AM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/^(\d{1,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    $datos[$rowIndex]['HORA'] = $hora;

                }
                if('C' == $cell->getColumn()){
                    $conte = $cell->getValue();
                    $conte = strtoupper($conte);
                    $datos[$rowIndex]['CONTENEDOR'] = $conte;
                }
                if('D' == $cell->getColumn()){
                    $datos[$rowIndex]['EQUIPO'] = $cell->getValue();
                }
                if('E' == $cell->getColumn()){
                    $datos[$rowIndex]['EIR'] = $cell->getValue();
                }
                if('F' == $cell->getColumn()){
                    $datos[$rowIndex]['MANIOBRA'] = $cell->getValue();
                }
                if('G' == $cell->getColumn()){
                    $datos[$rowIndex]['TRANSPORTISTA'] = $cell->getValue();
                }
                if('H' == $cell->getColumn()){
                    $datos[$rowIndex]['CLIENTE'] = $cell->getValue();
                }
                if('I' == $cell->getColumn()){
                    $datos[$rowIndex]['NOTA'] = $cell->getValue();
                }
                if('J' == $cell->getColumn()){
                    $datos[$rowIndex]['SELLO'] = $cell->getValue();
                }
                if('K' == $cell->getColumn()){
                    $datos[$rowIndex]['I_E'] = $cell->getValue();
                }


            }

        }


        if( $validFile=="True" ) {
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $flgRec = 1;

            $l = 1;
            $f = date(Ymd);

            // ------------------------------------------------
            // Generar archivo EDI
            // ------------------------------------------------
            $fileEDI = "../edi_files/edi/".$sesIdUsuario."MOL_GATE_IN_".$fecD1.$fecD2.".edi";
            $fileName = $sesIdUsuario."MOL_GATE_IN".$fecD1.$fecD2.".edi";
            $fp2 = fopen("$fileEDI", "w");
            $sepa = "\r\n";
            $codPatio = getValueTable("cod_patio", "USUARIO", "id_usuario", $sesIdUsuario);
            $codPatioName = getValueTable("cod_patio_name", "USUARIO", "id_usuario", $sesIdUsuario);
            $receiverID = "MOL";

            // Configuracion de SenderID
            switch( $sesIdUsuario ){
                case "12":
                    // Terraports
                    // Login: tpsmex
                    $senderID = "MXMEXTP";
                    break;
                case "13":
                    // Terraports
                    // Login: tpsver
                    $senderID = "MXMEXTP";
                    break;
                default:
                    $senderID = getValueTable("sender_id", "USUARIO", "id_usuario", $sesIdUsuario);
            }

            // ENCABEZADO
            $enc = "UNB+UNOA:2+$senderID+$receiverID+$fecD1:$fecD2+$fecD1$fecD2'".$sepa;
            $enc .= "UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'".$sepa;
            $enc .= "BGM+34+$fecD1$fecD2+9'".$sepa;
            $enc .= "LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'" . $sepa;
            fputs($fp2, $enc);
            $tlSegmentos = 4;
            $tlConte = 0;

            // -------------------------------------------------
            // OBTENIENDO LA INFO
            // -------------------------------------------------
            $l=1;
            foreach ($datos as $dato => $x) {

                if( $l>5 ) {
                    $validFec=0;
                    $fecha = $x['FECHA'];
                    if( preg_match("/(\d{4})-(\d{2})-(\d{2})$/i",$fecha,$parts) ){
                        $d= $parts[3];
                        $m= $parts[2];
                        $a= $parts[1];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }

                    $hora = $x['HORA'];
                    $hora = strtolower($hora);
                    $hora = str_replace(" ","",$hora);
                    $hora = str_replace(":","",$hora);
                    $hora = str_replace("-","",$hora);
                    $hora = str_replace(".","",$hora);
                    $hora = str_replace("pm","",$hora);
                    $hora = str_replace("am","",$hora);
                    $hora = str_pad($hora,4,"0", STR_PAD_LEFT);
                    $regHora = $hora;
                    // echo "$regHora <br>";

                    $conte = $x['CONTENEDOR'];
                    $equipo = $x['EQUIPO'];
                    $tipo = getEquipoTipo($equipo);
                    $eir = $x['EIR'];
                    if(!empty($eir)){
                        $eir="EIR: $eir ";
                    }
                    $maniobra = $x['MANIOBRA'];
                    $transportista = $x['TRANSPORTISTA'];
                    $cliente = $x['CLIENTE'];
                    $cliente = strtoupper($cliente);
                    //$bkgNumber = $x['BOOKING'];
                    //$bkgNumber = strtoupper($bkgNumber);
                    $bl = $x['BL'];
                    $sello = $x[SELLO];
                    $impoExpo = $x[I_E];

                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;

                        // ---------------------------------------------------------
                        // COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                        // ----------------------------------------------------------
                        ($impoExpo=="E")?$stIE=2:$stIE=3;
                        ($stConte=="E")?$stCo=4:$stCo=5;
                        $conte = strtoupper($conte);
                        $enc2= "EQD+CN+$conte+$tipo++$stIE+$stCo'".$sepa;
                        $tlSegmentos++;
                        $enc2.="DTM+7:$regFecha$regHora:203'".$sepa;
                        $tlSegmentos++;
                        $enc2.="FTX+AAI+++$transportista|$eir'".$sepa;
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'".$sepa;
                        $tlSegmentos++;
                        fputs($fp2,$enc2);
                        $flgOkFile=1;
                    }

                }
                $l++;
            }

            // Pie de pagina
            // Total de contenedores
            $pie="CNT+16:$tlConte'".$sepa;
            $tlSegmentos++;
            $pie.="UNT+$tlSegmentos+$fecD1$fecD2'".$sepa;
            $pie.="UNZ+1+$fecD1$fecD2'";

            //$enc33=utf8_encode($enc3);
            fputs($fp2,$pie);
            fclose($fp2);

            unset($rowIterator);
            unset($datos);


            if( $flgOkFile==1 ) {
                if ($opcPro == "email") {
                    // sendNotifyOneFile($sesIdUsuario, 'MOL', $fileEDI, $fileName);
                }
                elseif ($opcPro == "ftp") {
                    sentToFTP($fileEDI,$idNav);
                }
                return $fileEDI;
            }
        }
    }
    // ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateOutMOL($stConte,$sesIdUsuario,$formato,$idNav,$opcPro){


        if( $formato=="excel2007" ) {
            // EXCEL 2007 o mayor
            // ---------------------------------------------------
            // PROCESO COPIAR ARCHIVO
            // ---------------------------------------------------
            if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/salidas$sesIdUsuario.xlsx");
            } else {
                echo "Error de archivo!.";
            }
            // -------------------------------------
            // GET-IN
            // -------------------------------------
            unset($fp);
            unset($data);
            unset($dataX);
            // ---------------------------------
            // ---- LEER ARCHIVO DE EXCEL ---
            // ---------------------------------
            $objReader = new PHPExcel_Reader_Excel2007();
            //$objReader = new PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader->load("../edi_files/csv/salidas$sesIdUsuario.xlsx");
            $objPHPExcel->setActiveSheetIndex(1);
        }
        elseif( $formato=="excel2005" ) {
            // EXCEL 2007 o mayor
            // ---------------------------------------------------
            // PROCESO COPIAR ARCHIVO
            // ---------------------------------------------------
            if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/salidas$sesIdUsuario.xls");
            } else {
                echo "Error de archivo!.";
            }
            // -------------------------------------
            // GET-IN
            // -------------------------------------
            unset($fp);
            unset($data);
            unset($dataX);
            // ---------------------------------
            // ---- LEER ARCHIVO DE EXCEL ---
            // ---------------------------------
            $objReader = new PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader->load("../edi_files/csv/salidas$sesIdUsuario.xls");
            $objPHPExcel->setActiveSheetIndex(1);
        }

        $validFile="True";
        $l=1;

        // ---------------------------------
        // Validar el encabezado
        // ----------------------------------
        $encC1= $objPHPExcel->getActiveSheet()->getCell("A5")->getValue();
        $encC2= $objPHPExcel->getActiveSheet()->getCell("B5")->getValue();
        $encC3= $objPHPExcel->getActiveSheet()->getCell("C5")->getValue();
        $encC4= $objPHPExcel->getActiveSheet()->getCell("D5")->getValue();
        $encC5= $objPHPExcel->getActiveSheet()->getCell("E5")->getValue();
        $encC6= $objPHPExcel->getActiveSheet()->getCell("F5")->getValue();
        $encC7= $objPHPExcel->getActiveSheet()->getCell("G5")->getValue();
        $encC8= $objPHPExcel->getActiveSheet()->getCell("H5")->getValue();
        $encC9= $objPHPExcel->getActiveSheet()->getCell("I5")->getValue();
        $encC10= $objPHPExcel->getActiveSheet()->getCell("J5")->getValue();
        $encC11= $objPHPExcel->getActiveSheet()->getCell("K5")->getValue();
        $encC12= $objPHPExcel->getActiveSheet()->getCell("L5")->getValue();
        $encC13= $objPHPExcel->getActiveSheet()->getCell("M5")->getValue();
        $encC14= $objPHPExcel->getActiveSheet()->getCell("N5")->getValue();
        $encC15= $objPHPExcel->getActiveSheet()->getCell("O5")->getValue();

        if( $encC1!="FECHA"){$validFile=0;echo "[Error Encabezado] FECHA<br>";}
        if( $encC2!="HORA" ){$validFile=0;echo "[Error Encabezado] HORA<br>";}
        if( $encC3!="CONTENEDOR" ){$validFile=0;echo "[Error Encabezado] CONTENEDOR<br>";}
        if( $encC4!="TIPO" ){$validFile=0;echo "[Error Encabezado] TIPO<br>";}
        if( $encC5!="EIR" ){$validFile=0;echo "[Error Encabezado] EIR<br>";}
        if( $encC6!="MANIOBRA" ){$validFile=0;echo "[Error Encabezado] MANIOBRA<br>";}
        if( $encC7!="TRANSPORTISTA" ){$validFile=0;echo "[Error Encabezado] TRANSPORTISTA<br>";}
        if( $encC8!="CLIENTE" ){$validFile=0;echo "[Error Encabezado] CLIENTE<br>";}
        if( $encC9!="AGENTE ADUANAL" ){$validFile=0;echo "[Error Encabezado] AGENTE ADUANAL <br>";}
        if( $encC10!="BL" ){$validFile=0;echo "[Error Encabezado] BL <br>";}
        if( $encC11!="BOOKING" ){$validFile=0;echo "[Error Encabezado] BOOKING <br>";}
        if( $encC12!="BUQUE" ){$validFile=0;echo "[Error Encabezado] BUQUE <br>";}
        if( $encC13!="VIAJE" ){$validFile=0;echo "[Error Encabezado] VIAJE <br>";}
        if( $encC14!="SELLO" ){$validFile=0;echo "[Error Encabezado] SELLO <br>";}
        if( $encC15!="I_E" ){$validFile=0;echo "[Error Encabezado] I_E <br>";}
        // ---- Fin de la validación ----


        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $array_data = array();
        foreach($rowIterator as $row){
            $lin = $row->getRowIndex ();
            //echo "fila ".$row->getRowIndex ();
            //echo "<br>";
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            //if(1 == $row->getRowIndex ()) continue; //skip first row la del encabezado
            $rowIndex = $row->getRowIndex ();
            //$array_data[$rowIndex] = array('A'=>'', 'B'=>'','C'=>'','D'=>'','E'=>'');

            foreach ($cellIterator as $cell) {
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn() ){
                    //$fecha = $cell->getValue();
                    $fecha = $cell->getFormattedValue();
                    // Validar Fecha : En caso de que los formatos sean 01-mes-15
                    unset($parts);
                    if( preg_match("/(\d{1,2})-(\w{3,3})-(\d{2,2})/i",$fecha,$parts) ){
                        // echo "FECHA:  $fecha OK <br>";
                        $dd = $parts[1];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $mes = $parts[2];
                        $aa = 2000 + $parts[3];
                        $mm = getOkMes($mes);
                        $fecha = $aa."-".$mm."-".$dd;
                    }
                    if( $lin>5 && !empty($fecha) && ( !preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha)  ) ){
                        echo "[Error] Lin: $lin | Fecha : $fecha | El formato es incorrecto, cambie el formato como -> aaaa-mm-dd  <br>";
                        $validFile=0;
                    }
                    $datos[$rowIndex]['FECHA'] = $fecha;
                }
                if('B' == $cell->getColumn()){
                    $hora = $cell->getFormattedValue();
                    unset($parts);
                    if( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) PM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        if( $hh<12 ){
                            $hh = $hh + 12;
                        }
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) AM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/^(\d{1,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    $datos[$rowIndex]['HORA'] = $hora;
                }
                if('C' == $cell->getColumn()){
                    $conte = $cell->getValue();
                    $conte = strtoupper($conte);
                    $datos[$rowIndex]['CONTENEDOR'] = $conte;
                }
                if('D' == $cell->getColumn()){
                    $datos[$rowIndex]['EQUIPO'] = $cell->getValue();
                }
                if('E' == $cell->getColumn()){
                    $datos[$rowIndex]['EIR'] = $cell->getValue();
                }
                if('F' == $cell->getColumn()){
                    $datos[$rowIndex]['MANIOBRA'] = $cell->getValue();
                }
                if('G' == $cell->getColumn()){
                    $datos[$rowIndex]['TRANSPORTISTA'] = $cell->getValue();
                }
                if('H' == $cell->getColumn()){
                    $datos[$rowIndex]['CLIENTE'] = $cell->getValue();
                }
                if('I' == $cell->getColumn()){
                    $datos[$rowIndex]['AGENTE ADUANAL'] = $cell->getValue();
                }
                if('J' == $cell->getColumn()){
                    $datos[$rowIndex]['BL'] = $cell->getValue();
                }
                if('K' == $cell->getColumn()){
                    $datos[$rowIndex]['BOOKING'] = $cell->getValue();
                }
                if('L' == $cell->getColumn()){
                    $datos[$rowIndex]['BUQUE'] = $cell->getValue();
                }
                if('M' == $cell->getColumn()){
                    $datos[$rowIndex]['VIAJE'] = $cell->getValue();
                }
                if('N' == $cell->getColumn()){
                    $datos[$rowIndex]['SELLO'] = $cell->getValue();
                }
                if('O' == $cell->getColumn()){
                    $datos[$rowIndex]['I_E'] = $cell->getValue();
                }

            }
        }

        if( $validFile=="True" ) {
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $flgRec = 1;

            $l = 1;
            $f = date(Ymd);

            // ------------------------------------------------
            // Generar archivo EDI
            // ------------------------------------------------
            $fileEDI = "../edi_files/edi/".$sesIdUsuario."MOL_GATE_OUT".$fecD1.$fecD2.".edi";
            $fileName = $sesIdUsuario."MOL_GATE_OUT".$fecD1.$fecD2.".edi";
            $fp2 = fopen("$fileEDI", "w");
            $sepa = "\r\n";
            $codPatio = getValueTable("cod_patio", "USUARIO", "id_usuario", $sesIdUsuario);
            $codPatioName = getValueTable("cod_patio_name", "USUARIO", "id_usuario", $sesIdUsuario);
            //$senderID = getValueTable("sender_id", "USUARIO", "id_usuario", $sesIdUsuario);
            $receiverID = "MOL";

            // Configuracion de SenderID
            switch( $sesIdUsuario ){
                case "12":
                    // Terraports
                    // Login: tpsmex
                    $senderID = "MXMEXTP";
                    break;
                case "13":
                    // Terraports
                    // Login: tpsver
                    $senderID = "MXMEXTP";
                    break;
                default:
                    $senderID = getValueTable("sender_id", "USUARIO", "id_usuario", $sesIdUsuario);
            }

            // ENCABEZADO
            $enc = "UNB+UNOA:2+$senderID+$receiverID+$fecD1:$fecD2+$fecD1$fecD2'" . $sepa;
            $enc .= "UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'" . $sepa;
            $enc .= "BGM+36+$fecD1$fecD2+9'" . $sepa;
            $enc .= "LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'" . $sepa;
            fputs($fp2, $enc);
            $tlConte = 0;
            $tlSegmentos = 4;

            // -------------------------------------------------
            // OBTENIENDO LA INFO
            // -------------------------------------------------
            $l=1;
            foreach ($datos as $dato => $x) {

                if( $l>5 ) {
                    $validFec=0;
                    $fecha = $x['FECHA'];
                    if( preg_match("/(\d{4})-(\d{2})-(\d{2})$/i",$fecha,$parts) ){
                        $a= $parts[1];
                        $m= $parts[2];
                        $d= $parts[3];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }

                    $hora = $x['HORA'];
                    $hora = strtolower($hora);
                    $hora = str_replace(" ","",$hora);
                    $hora = str_replace(":","",$hora);
                    $hora = str_replace("-","",$hora);
                    $hora = str_replace(".","",$hora);
                    $hora = str_replace("pm","",$hora);
                    $hora = str_replace("am","",$hora);
                    $regHora = $hora;

                    $conte = $x['CONTENEDOR'];
                    $equipo = $x['EQUIPO'];
                    $tipo = getEquipoTipo($equipo);
                    // $eir = $x['EIR'];
                    $maniobra = $x['MANIOBRA'];
                    $transportista = $x['TRANSPORTISTA'];
                    $cliente = $x['CLIENTE'];
                    $cliente = strtoupper($cliente);
                    $bkgNumber = $x['BOOKING'];
                    $bkgNumber = strtoupper($bkgNumber);
                    $bl = $x['BL'];
                    $sello = $x[SELLO];
                    $impoExpo = $x[I_E];


                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;

                        // ---------------------------------------------------------
                        // COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                        // ----------------------------------------------------------
                        ($impoExpo=="E")?$stIE=2:$stIE=3;
                        ($stConte=="E")?$stCo=4:$stCo=5;
                        $conte = strtoupper($conte);
                        $enc2= "EQD+CN+$conte+$tipo++$stIE+$stCo'".$sepa;
                        $tlSegmentos++;
                        if( !empty($bl) ){
                            $enc2.= "RFF+BM:$bl'\r\n";
                            $tlSegmentos++;
                        }
                        if( !empty($bkgNumber) ) {
                            $enc2.= "RFF+BN:$bkgNumber'\r\n";
                            $tlSegmentos++;
                        }
                        $enc2.="DTM+7:$regFecha$regHora:203'".$sepa;
                        $tlSegmentos++;

                        $enc2.="FTX+AAI+++$bkgNumber|$cliente|$transportista'".$sepa;
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'".$sepa;  // 3 : Equivale a Camion y 1. Es Tren.
                        $tlSegmentos++;
                        // Cuando sean salidas es necesario agregar esta línea para cada movimiento,
                        // ya que con este se especifica el Destination Depot.
                        $enc2.="LOC+99+'".$sepa;   // 99 : Place of empty equipment return
                        $tlSegmentos++;
                        fputs($fp2,$enc2);
                        $flgOkFile=1;
                    }

                }
                $l++;
            }

            // Pie de pagina
            // Total de contenedores
            $pie="CNT+16:$tlConte'".$sepa;
            $tlSegmentos++;
            $pie.="UNT+$tlSegmentos+$fecD1$fecD2'".$sepa;
            $pie.="UNZ+1+$fecD1$fecD2'";

            fputs($fp2,$pie);
            fclose($fp2);

            unset($rowIterator);
            unset($datos);


            if( $flgOkFile==1 ) {
                if ($opcPro == "email") {
                    //sendNotifyOneFile($sesIdUsuario, 'MOL', $fileEDI, $fileName);
                }
                elseif ($opcPro == "ftp") {
                    sentToFTP($fileEDI,$idNav);
                }
                return $fileEDI;
            }
        }

    }
    // fffffffffffffffffffffFf ANAKOSTA fffffffffffffffffffffffffffffffffffffffff
    function ediGateInMscExcelAnakosta($stConte,$sesIdUsuario,$idNav,$opcPro){

        // EXCEL 2007
        // ---------------------------------------------------
        // PROCESO COPIAR ARCHIVO
        // ---------------------------------------------------
        if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
            copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/entradas$sesIdUsuario.xlsx");
        } else {
            echo "Error de archivo!.";
        }
        // -------------------------------------
        // GET-IN
        // -------------------------------------
        unset($fp);
        unset($data);
        unset($dataX);
        // ---------------------------------
        // ---- LEER ARCHIVO DE EXCEL ---
        // ---------------------------------
        $objReader = new PHPExcel_Reader_Excel2007();
        //$objReader = new PHPExcel_Reader_Excel5();
        $objPHPExcel = $objReader->load("../edi_files/csv/entradas$sesIdUsuario.xlsx");
        $objPHPExcel->setActiveSheetIndex(0);
    
        

        $validFile="True";
        $l=1;

        // ------------------
        // Validacion campos
        // ------------------
        // ---------------------------------
        // Validar el encabezado
        // ----------------------------------
        $encC1= $objPHPExcel->getActiveSheet()->getCell("A5")->getValue();
        $encC2= $objPHPExcel->getActiveSheet()->getCell("B5")->getValue();
        $encC3= $objPHPExcel->getActiveSheet()->getCell("C5")->getValue();
        $encC4= $objPHPExcel->getActiveSheet()->getCell("D5")->getValue();
        $encC5= $objPHPExcel->getActiveSheet()->getCell("E5")->getValue();
        $encC6= $objPHPExcel->getActiveSheet()->getCell("F5")->getValue();
        $encC7= $objPHPExcel->getActiveSheet()->getCell("G5")->getValue();
        $encC8= $objPHPExcel->getActiveSheet()->getCell("H5")->getValue();
        $encC9= $objPHPExcel->getActiveSheet()->getCell("I5")->getValue();
        $encC10= $objPHPExcel->getActiveSheet()->getCell("J5")->getValue();
        $encC11= $objPHPExcel->getActiveSheet()->getCell("K5")->getValue();
        $encC12= $objPHPExcel->getActiveSheet()->getCell("L5")->getValue();
        $encC13= $objPHPExcel->getActiveSheet()->getCell("M5")->getValue();
        $encC14= $objPHPExcel->getActiveSheet()->getCell("N5")->getValue();

        if( $encC1!="FECHA"){$validFile=0;echo "[Error Encabezado] <br>";}
        if( $encC2!="HORA" ){$validFile=0;echo "[Error Encabezado] HORA<br>";}
        if( $encC3!="CONTENEDOR"){$validFile=0;echo "[Error Encabezado] CONTENEDOR<br>";}
        if( $encC4!="TIPO" ){$validFile=0;echo "[Error Encabezado] TIPO<br>";}
        if( $encC5!="EIR" ){$validFile=0;echo "[Error Encabezado] EIR<br>";}
        if( $encC6!="MANIOBRA" ){$validFile=0;echo "[Error Encabezado] MANIOBRA<br>";}
        if( $encC7!="TRANSPORTISTA" ){$validFile=0;echo "[Error Encabezado] TRANSPORTISTA<br>";}
        if( $encC8!="BOOKING" ){$validFile=0;echo "[Error Encabezado] BOOKING <br>";}
        if( $encC9!="BL" ){$validFile=0;echo "[Error Encabezado] BL <br>";}
        if( $encC10!="CLIENTE" ){$validFile=0;echo "[Error Encabezado] CLIENTE <br>";}
        if( $encC11!="NOTA" ){$validFile=0;echo "[Error Encabezado] NOTA <br>";}
        if( $encC12!="SELLO" ){$validFile=0;echo "[Error Encabezado] SELLO <br>";}
        if( $encC13!="I_E" ){$validFile=0;echo "[Error Encabezado] I_E <br>";}
        // ---- Fin de la validación ----

        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $array_data = array();

        foreach($rowIterator as $row){
            $lin = $row->getRowIndex ();
            //echo "fila ".$row->getRowIndex ();
            //echo "<br>";
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            //if(1 == $row->getRowIndex ()) continue; //skip first row la del encabezado
            $rowIndex = $row->getRowIndex ();
            //$array_data[$rowIndex] = array('A'=>'', 'B'=>'','C'=>'','D'=>'','E'=>'');


            foreach ($cellIterator as $cell) {
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn() ){
                    //$fecha = $cell->getValue();
                    $fecha = $cell->getFormattedValue();
                    // Validar Fecha : En caso de que los formatos sean 01-mes-15
                    unset($parts);
                    if( preg_match("/(\d{1,2})-(\w{3,3})-(\d{2,2})/i",$fecha,$parts) ){
                        // echo "FECHA:  $fecha OK <br>";
                        $dd = $parts[1];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $mes = $parts[2];
                        $aa = 2000 + $parts[3];
                        $mm = getOkMes($mes);
                        $fecha = $aa."-".$mm."-".$dd;
                    }

                    if( $lin>5 && !empty($fecha) && ( !preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha)  ) ){
                        echo "[Error] Lin: $lin | Fecha : $fecha | El formato es incorrecto, cambie el formato como -> aaaa-mm-dd  <br>";
                        $validFile=0;
                    }
                    $datos[$rowIndex]['FECHA'] = $fecha;

                }
                if('B' == $cell->getColumn()){
                    $hora = $cell->getFormattedValue();
                    unset($parts);
                    if( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) PM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        if( $hh<12 ){
                            $hh = $hh + 12;
                        }
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) AM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/^(\d{1,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    $datos[$rowIndex]['HORA'] = $hora;

                }
                if('C' == $cell->getColumn()){
                    $conte = $cell->getValue();
                    $conte = strtoupper($conte);
                    $datos[$rowIndex]['CONTENEDOR'] = $conte;
                }
                if('D' == $cell->getColumn()){
                    $datos[$rowIndex]['EQUIPO'] = $cell->getValue();
                }
                if('E' == $cell->getColumn()){
                    $datos[$rowIndex]['EIR'] = $cell->getValue();
                }
                if('F' == $cell->getColumn()){
                    $datos[$rowIndex]['MANIOBRA'] = $cell->getValue();
                }
                if('G' == $cell->getColumn()){
                    $datos[$rowIndex]['TRANSPORTISTA'] = $cell->getValue();
                }
                if('H' == $cell->getColumn()){
                    $datos[$rowIndex]['BOOKING'] = $cell->getValue();
                }
                if('I' == $cell->getColumn()){
                    $datos[$rowIndex]['BL'] = $cell->getValue();
                }
                if('J' == $cell->getColumn()){
                    $datos[$rowIndex]['CLIENTE'] = $cell->getValue();
                }
                if('K' == $cell->getColumn()){
                    $datos[$rowIndex]['NOTA'] = $cell->getValue();
                }
                if('L' == $cell->getColumn()){
                    $datos[$rowIndex]['SELLO'] = $cell->getValue();
                }
                if('M' == $cell->getColumn()){
                    $datos[$rowIndex]['I_E'] = $cell->getValue();
                }
                if('N' == $cell->getColumn()){
                    $datos[$rowIndex]['TIPO_INGRESO'] = $cell->getValue();
                }

            }

        }


        if( $validFile=="True" ) {
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $flgRec = 1;

            $l = 1;
            $f = date(Ymd);

            // ------------------------------------------------
            // Generar archivo EDI
            // ------------------------------------------------
            $fileEDI = "../edi_files/edi/".$sesIdUsuario."MSC_GATE_IN_ANAKOSTA".$fecD1.$fecD2.".edi";
            $fileName = $sesIdUsuario."MSC_GATE_IN_ANAKOSTA".$fecD1.$fecD2.".edi";
            $fp2 = fopen("$fileEDI", "w");
            $sepa = "\r\n";
            $codPatio = getValueTable("cod_patio", "USUARIO", "id_usuario", $sesIdUsuario);
            $codPatioName = getValueTable("cod_patio_name", "USUARIO", "id_usuario", $sesIdUsuario);
            $senderID = getValueTable("sender_id", "USUARIO", "id_usuario", $sesIdUsuario);
            $receiverID = "MSC";
            // ENCABEZADO
            $enc = "UNB+UNOA:1+$senderID+$receiverID+$fecD1:$fecD2+$fecD1$fecD2'".$sepa;
            $enc .= "UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'".$sepa;
            $enc .= "BGM+34+$fecD1$fecD2+9'".$sepa;
            //"TDT+20++1++MSC:172:166'".$sepa.
            //"NAD+CA+MXVRCDECECI:172:166'".$sepa;
            //$enc11=utf8_encode($enc);
            fputs($fp2, $enc);


            $tlConte = 0;
            $tlSegmentos = 4;

            // -------------------------------------------------
            // OBTENIENDO LA INFO
            // -------------------------------------------------
            $l=1;
            foreach ($datos as $dato => $x) {

                if( $l>5 ) {
                    $validFec=0;
                    $fecha = $x['FECHA'];

                    /*
                    if( preg_match("/(\d{2})\/(\d{2})\/(\d{4})/i",$fecha,$parts) ){
                        $d= $parts[1];
                        $m= $parts[2];
                        $a= $parts[3];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }
                    */
                    $fecha = $x['FECHA'];
                    if( preg_match("/(\d{4})-(\d{2})-(\d{2})$/i",$fecha,$parts) ){
                        $d= $parts[3];
                        $m= $parts[2];
                        $a= $parts[1];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }

                    $hora = $x['HORA'];
                    $hora = strtolower($hora);
                    $hora = str_replace(" ","",$hora);
                    $hora = str_replace(":","",$hora);
                    $hora = str_replace("-","",$hora);
                    $hora = str_replace(".","",$hora);
                    $hora = str_replace("pm","",$hora);
                    $hora = str_replace("am","",$hora);
                    $hora = str_pad($hora,4,"0", STR_PAD_LEFT);
                    $regHora = $hora;
                    // echo "$regHora <br>";

                    $conte = $x['CONTENEDOR'];
                    $equipo = $x['EQUIPO'];
                    $tipo = getEquipoTipo($equipo);
                    $eir = $x['EIR'];
                    if(!empty($eir)){
                        $eir="EIR: $eir ";
                    }
                    $maniobra = $x['MANIOBRA'];
                    $transportista = $x['TRANSPORTISTA'];
                    $cliente = $x['CLIENTE'];
                    $cliente = strtoupper($cliente);
                    $bkgNumber = $x['BOOKING'];
                    $bkgNumber = strtoupper($bkgNumber);
                    $bl = $x['BL'];
                    $sello = $x['SELLO'];
                    $impoExpo = $x['I_E'];

                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;

                        // ----------------------------------------------------------
                        // SI ES 2 EXPORT ó 3 IMPORT 
                        // ----------------------------------------------------------
                        if( $impoExpo=="E" ){
                            $stIE=2;
                        }
                        elseif( $impoExpo=="I" ){
                            $stIE=3;
                        }
                        elseif( empty($impoExpo) ){
                            $stIE=2;
                        }
                        // --------------------
                        // 4 EMPTY Ó 5 FULL
                        // --------------------
                        ($stConte=="E")?$stCo=4:$stCo=5;
                        
                        $conte = strtoupper($conte);
                        $enc2= "EQD+CN+$conte+$tipo++$stIE+$stCo'".$sepa;
                        $tlSegmentos++;
                        
                        // Este segmento solo aplica para Anakosta
                        if( !empty($bl) ){
                            $enc2.= "RFF+BM:$bl'".$sepa;
                            $tlSegmentos++;
                        }
                        if( !empty($bkgNumber) ) {
                            $enc2.= "RFF+BN:$bkgNumber'".$sepa;
                            $tlSegmentos++;
                        }
                        $enc2.="DTM+7:$regFecha$regHora:203'".$sepa;
                        $tlSegmentos++;
                        $enc2.="LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'".$sepa;
                        $tlSegmentos++;
                        if( !empty($sello) ) {
                            $enc2 .= "SEL+$sello+CA'\r\n";
                            $tlSegmentos++;
                        }
                        $enc2.="FTX+AAI+++$transportista|$eir'".$sepa;
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'".$sepa;
                        $tlSegmentos++;
                        fputs($fp2,$enc2);
                        $flgOkFile=1;
                    }

                }
                $l++;
            }

            // Pie de pagina
            // Total de contenedores
            $pie="CNT+16:$tlConte'".$sepa;
            $tlSegmentos++;
            $pie.="UNT+$tlSegmentos+$fecD1$fecD2'".$sepa;
            $pie.="UNZ+1+$fecD1$fecD2'";

            //$enc33=utf8_encode($enc3);
            fputs($fp2,$pie);
            fclose($fp2);

            unset($rowIterator);
            unset($datos);

            if( $flgOkFile==1 ) {
                if ($opcPro == "email") {
                    //sendNotifyOneFile($sesIdUsuario, 'MSC', $fileEDI, $fileName);
                } 
                elseif ($opcPro == "ftp") {
                    sentToFTP($fileEDI,'1CA','/CENTRO AMERICA/NICARAGUA/');
                }
                return $fileEDI;
            }
        }
    }
    // ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateOutMscExcelAnakosta($stConte,$sesIdUsuario,$idNav,$opcPro){

        // EXCEL 2007 o mayor
        // ---------------------------------------------------
        // PROCESO COPIAR ARCHIVO
        // ---------------------------------------------------
        if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
            copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/salidas$sesIdUsuario.xlsx");
        } else {
            echo "Error de archivo!.";
        }
        // -------------------------------------
        // GET-IN
        // -------------------------------------
        unset($fp);
        unset($data);
        unset($dataX);
        // ---------------------------------
        // ---- LEER ARCHIVO DE EXCEL ---
        // ---------------------------------
        $objReader = new PHPExcel_Reader_Excel2007();
        //$objReader = new PHPExcel_Reader_Excel5();
        $objPHPExcel = $objReader->load("../edi_files/csv/salidas$sesIdUsuario.xlsx");
        $objPHPExcel->setActiveSheetIndex(1);
        
        $validFile="True";
        $l=1;

        // ---------------------------------
        // Validar el encabezado
        // ----------------------------------
        $encC1= $objPHPExcel->getActiveSheet()->getCell("A5")->getValue();
        $encC2= $objPHPExcel->getActiveSheet()->getCell("B5")->getValue();
        $encC3= $objPHPExcel->getActiveSheet()->getCell("C5")->getValue();
        $encC4= $objPHPExcel->getActiveSheet()->getCell("D5")->getValue();
        $encC5= $objPHPExcel->getActiveSheet()->getCell("E5")->getValue();
        $encC6= $objPHPExcel->getActiveSheet()->getCell("F5")->getValue();
        $encC7= $objPHPExcel->getActiveSheet()->getCell("G5")->getValue();
        $encC8= $objPHPExcel->getActiveSheet()->getCell("H5")->getValue();
        $encC9= $objPHPExcel->getActiveSheet()->getCell("I5")->getValue();
        $encC10= $objPHPExcel->getActiveSheet()->getCell("J5")->getValue();
        $encC11= $objPHPExcel->getActiveSheet()->getCell("K5")->getValue();
        $encC12= $objPHPExcel->getActiveSheet()->getCell("L5")->getValue();
        $encC13= $objPHPExcel->getActiveSheet()->getCell("M5")->getValue();
        $encC14= $objPHPExcel->getActiveSheet()->getCell("N5")->getValue();
        $encC15= $objPHPExcel->getActiveSheet()->getCell("O5")->getValue();

        if( $encC1!="FECHA"){$validFile=0;echo "[Error Encabezado] FECHA<br>";}
        if( $encC2!="HORA" ){$validFile=0;echo "[Error Encabezado] HORA<br>";}
        if( $encC3!="CONTENEDOR" ){$validFile=0;echo "[Error Encabezado] CONTENEDOR<br>";}
        if( $encC4!="TIPO" ){$validFile=0;echo "[Error Encabezado] TIPO<br>";}
        if( $encC5!="EIR" ){$validFile=0;echo "[Error Encabezado] EIR<br>";}
        if( $encC6!="MANIOBRA" ){$validFile=0;echo "[Error Encabezado] MANIOBRA<br>";}
        if( $encC7!="TRANSPORTISTA" ){$validFile=0;echo "[Error Encabezado] TRANSPORTISTA<br>";}
        if( $encC8!="CLIENTE" ){$validFile=0;echo "[Error Encabezado] CLIENTE<br>";}
        if( $encC9!="AGENTE ADUANAL" ){$validFile=0;echo "[Error Encabezado] AGENTE ADUANAL <br>";}
        if( $encC10!="BL" ){$validFile=0;echo "[Error Encabezado] BL <br>";}
        if( $encC11!="BOOKING" ){$validFile=0;echo "[Error Encabezado] BOOKING <br>";}
        if( $encC12!="BUQUE" ){$validFile=0;echo "[Error Encabezado] BUQUE <br>";}
        if( $encC13!="VIAJE" ){$validFile=0;echo "[Error Encabezado] VIAJE <br>";}
        if( $encC14!="SELLO" ){$validFile=0;echo "[Error Encabezado] SELLO <br>";}
        if( $encC15!="I_E" ){$validFile=0;echo "[Error Encabezado] I_E <br>";}
        // ---- Fin de la validación ----


        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $array_data = array();
        foreach($rowIterator as $row){
            $lin = $row->getRowIndex ();
            //echo "fila ".$row->getRowIndex ();
            //echo "<br>";
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            //if(1 == $row->getRowIndex ()) continue; //skip first row la del encabezado
            $rowIndex = $row->getRowIndex ();
            //$array_data[$rowIndex] = array('A'=>'', 'B'=>'','C'=>'','D'=>'','E'=>'');

            foreach ($cellIterator as $cell) {
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn() ){
                    //$fecha = $cell->getValue();
                    $fecha = $cell->getFormattedValue();
                    // Validar Fecha : En caso de que los formatos sean 01-mes-15
                    unset($parts);
                    if( preg_match("/(\d{1,2})-(\w{3,3})-(\d{2,2})/i",$fecha,$parts) ){
                        // echo "FECHA:  $fecha OK <br>";
                        $dd = $parts[1];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $mes = $parts[2];
                        $aa = 2000 + $parts[3];
                        $mm = getOkMes($mes);
                        $fecha = $aa."-".$mm."-".$dd;
                    }
                    if( $lin>5 && !empty($fecha) && ( !preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha)  ) ){
                        echo "[Error] Lin: $lin | Fecha : $fecha | El formato es incorrecto, cambie el formato como -> aaaa-mm-dd  <br>";
                        $validFile=0;
                    }
                    $datos[$rowIndex]['FECHA'] = $fecha;
                }
                if('B' == $cell->getColumn()){
                    $hora = $cell->getFormattedValue();
                    unset($parts);
                    if( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) PM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        if( $hh<12 ){
                            $hh = $hh + 12;
                        }
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) AM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/^(\d{1,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    $datos[$rowIndex]['HORA'] = $hora;
                }
                if('C' == $cell->getColumn()){
                    $conte = $cell->getValue();
                    $conte = strtoupper($conte);
                    $datos[$rowIndex]['CONTENEDOR'] = $conte;
                }
                if('D' == $cell->getColumn()){
                    $datos[$rowIndex]['EQUIPO'] = $cell->getValue();
                }
                if('E' == $cell->getColumn()){
                    $datos[$rowIndex]['EIR'] = $cell->getValue();
                }
                if('F' == $cell->getColumn()){
                    $datos[$rowIndex]['MANIOBRA'] = $cell->getValue();
                }
                if('G' == $cell->getColumn()){
                    $datos[$rowIndex]['TRANSPORTISTA'] = $cell->getValue();
                }
                if('H' == $cell->getColumn()){
                    $datos[$rowIndex]['CLIENTE'] = $cell->getValue();
                }
                if('I' == $cell->getColumn()){
                    $datos[$rowIndex]['AGENTE ADUANAL'] = $cell->getValue();
                }
                if('J' == $cell->getColumn()){
                    $datos[$rowIndex]['BL'] = $cell->getValue();
                }
                if('K' == $cell->getColumn()){
                    $datos[$rowIndex]['BOOKING'] = $cell->getValue();
                }
                if('L' == $cell->getColumn()){
                    $datos[$rowIndex]['BUQUE'] = $cell->getValue();
                }
                if('M' == $cell->getColumn()){
                    $datos[$rowIndex]['VIAJE'] = $cell->getValue();
                }
                if('N' == $cell->getColumn()){
                    $datos[$rowIndex]['SELLO'] = $cell->getValue();
                }
                if('O' == $cell->getColumn()){
                    $datos[$rowIndex]['I_E'] = $cell->getValue();
                }

            }
        }

        if( $validFile=="True" ) {
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $flgRec = 1;

            $l = 1;
            $f = date(Ymd);

            // ------------------------------------------------
            // Generar archivo EDI OUT
            // ------------------------------------------------
            $fileEDI = "../edi_files/edi/".$sesIdUsuario."MSC_GATE_OUT_ANAKOSTA".$fecD1.$fecD2.".edi";
            $fileName = $sesIdUsuario."MSC_GATE_OUT_ANAKOSTA".$fecD1.$fecD2.".edi";
            $fp2 = fopen("$fileEDI", "w");
            $sepa = "\r\n";
            $codPatio = getValueTable("cod_patio", "USUARIO", "id_usuario", $sesIdUsuario);
            $codPatioName = getValueTable("cod_patio_name", "USUARIO", "id_usuario", $sesIdUsuario);
            $senderID = getValueTable("sender_id", "USUARIO", "id_usuario", $sesIdUsuario);
            $receiverID = "MSC";
            // ENCABEZADO
            $enc = "UNB+UNOA:1+$senderID+$receiverID+$fecD1:$fecD2+$fecD1$fecD2'" . $sepa;
            $enc .= "UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'" . $sepa;
            $enc .= "BGM+36+$fecD1$fecD2+9'" . $sepa;
            //"TDT+20++1++MSC:172:166'".$sepa.
            //"NAD+CA+MXVRCDECECI:172:166'".$sepa;
            //$enc11=utf8_encode($enc);
            fputs($fp2, $enc);


            $tlConte = 0;
            $tlSegmentos = 4;

            // -------------------------------------------------
            // OBTENIENDO LA INFO
            // -------------------------------------------------
            $l=1;
            foreach ($datos as $dato => $x) {

                if( $l>5 ) {
                    $validFec=0;
                    $fecha = $x['FECHA'];
                    if( preg_match("/(\d{4})-(\d{2})-(\d{2})$/i",$fecha,$parts) ){
                        $a= $parts[1];
                        $m= $parts[2];
                        $d= $parts[3];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }

                    $hora = $x['HORA'];
                    $hora = strtolower($hora);
                    $hora = str_replace(" ","",$hora);
                    $hora = str_replace(":","",$hora);
                    $hora = str_replace("-","",$hora);
                    $hora = str_replace(".","",$hora);
                    $hora = str_replace("pm","",$hora);
                    $hora = str_replace("am","",$hora);
                    $regHora = $hora;

                    $conte = $x['CONTENEDOR'];
                    $equipo = $x['EQUIPO'];
                    $tipo = getEquipoTipo($equipo);
                    // $eir = $x['EIR'];
                    $maniobra = $x['MANIOBRA'];
                    $transportista = $x['TRANSPORTISTA'];
                    $cliente = $x['CLIENTE'];
                    $cliente = strtoupper($cliente);
                    $bkgNumber = $x['BOOKING'];
                    $bkgNumber = strtoupper($bkgNumber);
                    $bl = $x['BL'];
                    $sello = $x[SELLO];
                    $impoExpo = $x[I_E];

                    if( preg_match("/^([0-9]+).*/",$bkgNumber,$parts) ){
                        $codBkg = $parts[1];
                        switch($codBkg){
                            case "192": $codOD = "LMR00";break;
                            case "364": $codOD = "ZLO00";break;
                            case "191": $codOD = "VER00";break;
                            case "404": $codOD = "MZT00";break;
                            case "697": $codOD = "GYM00";break;
                            case "620": $codOD = "LZC00";break;
                            case "622": $codOD = "PUM00";break;
                        }
                    }

                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;

                        // ---------------------------------------------------------
                        // COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                        // ----------------------------------------------------------
                        ($impoExpo=="E")?$stIE=2:$stIE=3;
                        ($stConte=="E")?$stCo=4:$stCo=5;
                        $conte = strtoupper($conte);
                        $enc2= "EQD+CN+$conte+$tipo++$stIE+$stCo'".$sepa;
                        $tlSegmentos++;
                        if( !empty($bl) ){
                            $enc2.= "RFF+BM:$bl'\r\n";
                            $tlSegmentos++;
                        }
                        if( !empty($bkgNumber) ) {
                            $enc2.= "RFF+BN:$bkgNumber'\r\n";
                            $tlSegmentos++;
                        }
                        $enc2.="DTM+7:$regFecha$regHora:203'\r\n";
                        $tlSegmentos++;
                        $enc2.= "LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'\r\n";
                        $tlSegmentos++;
                        if( !empty($sello) ){
                            $enc2.="SEL+$sello+CA'\r\n";
                            $tlSegmentos++;
                        }
                        $enc2.="FTX+AAI+++$bkgNumber|$cliente|$transportista'\r\n";
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'\r\n";
                        $tlSegmentos++;
                        // Cuando sean salidas es necesario agregar esta línea para cada movimiento,
                        // ya que con este se especifica el Destination Depot.
                        
                        // Solo para Anakosta.
                        $codOD=$senderID;
                        
                        $enc2.="LOC+99+$codOD'\r\n";   // 99 : Place of empty equipment return
                        $tlSegmentos++;
                        fputs($fp2,$enc2);
                        $flgOkFile=1;
                    }

                }
                $l++;
            }

            // Pie de pagina
            // Total de contenedores
            $pie="CNT+16:$tlConte'".$sepa;
            $tlSegmentos++;
            $pie.="UNT+$tlSegmentos+$fecD1$fecD2'".$sepa;
            $pie.="UNZ+1+$fecD1$fecD2'";

            fputs($fp2,$pie);
            fclose($fp2);

            unset($rowIterator);
            unset($datos);

            
            if( $flgOkFile==1 ) {
                
               
                if ($opcPro == "email") {
                    //sendNotifyOneFile($sesIdUsuario, 'MSC', $fileEDI, $fileName);
                } 
                elseif ($opcPro == "ftp") {
                    sentToFTP($fileEDI,'1CA','/CENTRO AMERICA/NICARAGUA/');
                }
                return $fileEDI;
                
            }
        }
    }
    // --------------------------------------------------------------------------
    function ediGatePosMscExcelAnakosta($stConte,$sesIdUsuario,$idNav,$opcPro){

        // POSICIONAMIENTOS : ANAKOSTA

        // EXCEL 2007 o mayor
        // ---------------------------------------------------
        // PROCESO COPIAR ARCHIVO
        // ---------------------------------------------------
        if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
            copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/pos$sesIdUsuario.xlsx");
        } else {
            echo "Error de archivo!.";
        }
        // -------------------------------------
        // GET-IN
        // -------------------------------------
        unset($fp);
        unset($data);
        unset($dataX);
        // ---------------------------------
        // ---- LEER ARCHIVO DE EXCEL ---
        // ---------------------------------
        $objReader = new PHPExcel_Reader_Excel2007();
        //$objReader = new PHPExcel_Reader_Excel5();
        $objPHPExcel = $objReader->load("../edi_files/csv/pos$sesIdUsuario.xlsx");
        $objPHPExcel->setActiveSheetIndex(2);
        

        $validFile="True";
        $l=1;

        // ---------------------------------
        // Validar el encabezado
        // ----------------------------------
        $encC1= $objPHPExcel->getActiveSheet()->getCell("A5")->getValue();
        $encC2= $objPHPExcel->getActiveSheet()->getCell("B5")->getValue();
        $encC3= $objPHPExcel->getActiveSheet()->getCell("C5")->getValue();
        $encC4= $objPHPExcel->getActiveSheet()->getCell("D5")->getValue();
        $encC5= $objPHPExcel->getActiveSheet()->getCell("E5")->getValue();
        $encC6= $objPHPExcel->getActiveSheet()->getCell("F5")->getValue();
        $encC7= $objPHPExcel->getActiveSheet()->getCell("G5")->getValue();
        $encC8= $objPHPExcel->getActiveSheet()->getCell("H5")->getValue();
        $encC9= $objPHPExcel->getActiveSheet()->getCell("I5")->getValue();
        $encC10= $objPHPExcel->getActiveSheet()->getCell("J5")->getValue();
        $encC11= $objPHPExcel->getActiveSheet()->getCell("K5")->getValue();
        $encC12= $objPHPExcel->getActiveSheet()->getCell("L5")->getValue();
        $encC13= $objPHPExcel->getActiveSheet()->getCell("M5")->getValue();
        $encC14= $objPHPExcel->getActiveSheet()->getCell("N5")->getValue();
        $encC15= $objPHPExcel->getActiveSheet()->getCell("O5")->getValue();
        $encC16= $objPHPExcel->getActiveSheet()->getCell("P5")->getValue();

        if( $encC1!="FECHA"){$validFile=0;echo "[Error Encabezado] FECHA<br>";}
        if( $encC2!="HORA" ){$validFile=0;echo "[Error Encabezado] HORA<br>";}
        if( $encC3!="CONTENEDOR" ){$validFile=0;echo "[Error Encabezado] CONTENEDOR<br>";}
        if( $encC4!="TIPO" ){$validFile=0;echo "[Error Encabezado] TIPO<br>";}
        if( $encC5!="EIR" ){$validFile=0;echo "[Error Encabezado] EIR<br>";}
        if( $encC6!="MANIOBRA" ){$validFile=0;echo "[Error Encabezado] MANIOBRA<br>";}
        if( $encC7!="TRANSPORTISTA" ){$validFile=0;echo "[Error Encabezado] TRANSPORTISTA<br>";}
        if( $encC8!="CLIENTE" ){$validFile=0;echo "[Error Encabezado] CLIENTE<br>";}
        if( $encC9!="AGENTE ADUANAL" ){$validFile=0;echo "[Error Encabezado] AGENTE ADUANAL<br>";}
        if( $encC10!="BL" ){$validFile=0;echo "[Error Encabezado] BL<br>";}
        if( $encC11!="BOOKING" ){$validFile=0;echo "[Error Encabezado] BOOKING<br>";}
        if( $encC12!="BUQUE" ){$validFile=0;echo "[Error Encabezado] BUQUE<br>";}
        if( $encC13!="VIAJE" ){$validFile=0;echo "[Error Encabezado] VIAJE<br>";}
        if( $encC14!="SELLO" ){$validFile=0;echo "[Error Encabezado] SELLO<br>";}
        if( $encC15!="DESTINO" ){$validFile=0;echo "[Error Encabezado] SELLO<br>";}
        if( $encC16!="I_E" ){$validFile=0;echo "[Error Encabezado] I_E<br>";}
        // ---- Fin de la validación ----

        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $array_data = array();
        foreach($rowIterator as $row){
            $lin = $row->getRowIndex ();
            //echo "fila ".$row->getRowIndex ();
            //echo "<br>";
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            //if(1 == $row->getRowIndex ()) continue; //skip first row la del encabezado
            $rowIndex = $row->getRowIndex ();
            //$array_data[$rowIndex] = array('A'=>'', 'B'=>'','C'=>'','D'=>'','E'=>'');

            foreach ($cellIterator as $cell) {
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn() ){
                    $fecha = $cell->getFormattedValue();
                    // Validar Fecha : En caso de que los formatos sean 01-mes-15
                    unset($parts);
                    if( preg_match("/(\d{1,2})-(\w{3,3})-(\d{2,2})/i",$fecha,$parts) ){
                        // echo "FECHA:  $fecha OK <br>";
                        $dd = $parts[1];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $mes = $parts[2];
                        $aa = 2000 + $parts[3];
                        $mm = getOkMes($mes);
                        $fecha = $aa."-".$mm."-".$dd;
                    }

                    if( $lin>5 && !empty($fecha) && ( !preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha)  ) ){
                        echo "[Error] Lin: $lin | Fecha : $fecha | El formato es incorrecto, cambie el formato como -> aaaa-mm-dd  <br>";
                        $validFile=0;
                    }
                    $datos[$rowIndex]['FECHA'] = $fecha;
                }
                if('B' == $cell->getColumn()){
                    $hora = $cell->getFormattedValue();
                    unset($parts);
                    if( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) PM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        if( $hh<12 ){
                            $hh = $hh + 12;
                        }
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) AM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/^(\d{1,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    $datos[$rowIndex]['HORA'] = $hora;
                }
                if('C' == $cell->getColumn()){
                    $conte = $cell->getValue();
                    $conte = strtoupper($conte);
                    $datos[$rowIndex]['CONTENEDOR'] = $conte;
                }
                if('D' == $cell->getColumn()){
                    $datos[$rowIndex]['EQUIPO'] = $cell->getValue();
                }
                if('E' == $cell->getColumn()){
                    $datos[$rowIndex]['EIR'] = $cell->getValue();
                }
                if('F' == $cell->getColumn()){
                    $datos[$rowIndex]['MANIOBRA'] = $cell->getValue();
                }
                if('G' == $cell->getColumn()){
                    $datos[$rowIndex]['TRANSPORTISTA'] = $cell->getValue();
                }
                if('H' == $cell->getColumn()){
                    $datos[$rowIndex]['CLIENTE'] = $cell->getValue();
                }
                if('I' == $cell->getColumn()){
                    $datos[$rowIndex]['AGENTE ADUANAL'] = $cell->getValue();
                }
                if('J' == $cell->getColumn()){
                    $datos[$rowIndex]['BL'] = $cell->getValue();
                }
                if('K' == $cell->getColumn()){
                    $datos[$rowIndex]['BOOKING'] = $cell->getValue();
                }
                if('L' == $cell->getColumn()){
                    $datos[$rowIndex]['BUQUE'] = $cell->getValue();
                }
                if('M' == $cell->getColumn()){
                    $datos[$rowIndex]['VIAJE'] = $cell->getValue();
                }
                if('N' == $cell->getColumn()){
                    $datos[$rowIndex]['SELLO'] = $cell->getValue();
                }
                if('O' == $cell->getColumn()){
                    $datos[$rowIndex]['DESTINO'] = $cell->getValue();
                }
                if('P' == $cell->getColumn()){
                    $datos[$rowIndex]['I_E'] = $cell->getValue();
                    $impoExpo = $cell->getValue();
                    if( empty($impoExpo) && !empty($conte) ){
                        echo "[ERROR] Lin: $lin | Import o Export (I,E) No especificado.<br>";
                    }
                }
            }
        }

        if( $validFile=="True" ) {
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $flgRec = 1;

            $l = 1;
            $f = date(Ymd);

            // ------------------------------------------------
            // Generar archivo EDI
            // ------------------------------------------------
            $codPatio = getValueTable("cod_patio", "USUARIO", "id_usuario", $sesIdUsuario);
            $fileEDI = "../edi_files/edi/".$sesIdUsuario."MSC_GATE_POS_ANAKOSTA".$codPatio."_".$fecD1.$fecD2.".edi";
            $fileName = str_replace("../edi_files/edi/","",$fileEDI);
            $fp2 = fopen("$fileEDI", "w");

            $codPatioName = $codPatio;
            $senderID = getValueTable("sender_id", "CODIGOS", "cod_patio", $codPatio);
            $receiverID = "MSC";

            // ENCABEZADO
            $enc= "UNB+UNOA:1+$senderID+$receiverID+$fecD1:$fecD2+$fecD1$fecD2'\r\n";
            $enc.= "UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'\r\n";
            // BGM35 : Es para posicionamientos
            $enc.= "BGM+35+$fecD1$fecD2+9'\r\n";
            //"TDT+20++1++MSC:172:166'".$sepa.
            //"NAD+CA+MXVRCDECECI:172:166'".$sepa;
            //$enc11=utf8_encode($enc);
            fputs($fp2, $enc);

            $tlConte = 0;
            $tlSegmentos = 4;
            // -------------------------------------------------
            // OBTENIENDO LA INFO
            // -------------------------------------------------
            $l=1;
            foreach ($datos as $dato => $x) {

                if( $l>5 ) {
                    $validFec=0;

                    $fecha = $x['FECHA'];
                    if( preg_match("/(\d{4})-(\d{2})-(\d{2})$/i",$fecha,$parts) ){
                        $a= $parts[1];
                        $m= $parts[2];
                        $d= $parts[3];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }
                    $hora = $x['HORA'];
                    $hora = strtolower($hora);
                    $hora = str_replace(" ","",$hora);
                    $hora = str_replace(":","",$hora);
                    $hora = str_replace("-","",$hora);
                    $hora = str_replace(".","",$hora);
                    $hora = str_replace("pm","",$hora);
                    $hora = str_replace("am","",$hora);
                    $hora = str_pad($hora,4,"0", STR_PAD_LEFT);
                    $regHora = $hora;

                    $conte = $x['CONTENEDOR'];
                    $equipo = $x['EQUIPO'];
                    $tipo = getEquipoTipo($equipo);
                    // $eir = $x['EIR'];
                    $maniobra = $x['MANIOBRA'];
                    $transportista = $x['TRANSPORTISTA'];
                    $cliente = $x['CLIENTE'];
                    $cliente = strtoupper($cliente);
                    $bkgNumber = $x['BOOKING'];
                    $bl = $x['BL'];
                    $bkgNumber = strtoupper($bkgNumber);
                    $sello = $x['SELLO'];
                    $destino = $x['DESTINO'];
                    $impoExpo = $x['I_E'];

                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;

                        // ---------------------------------------------------------
                        // COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                        // ----------------------------------------------------------
                        ( $impoExpo=="E" )?$stIE=2:$stIE=3;
                        ( $stConte=="E" )?$stCo=4:$stCo=5;
                        $conte = strtoupper($conte);
                        $enc2= "EQD+CN+$conte+$tipo++$stIE+$stCo'\r\n";
                        $tlSegmentos++;
                        if( !empty($bl) ){
                            $enc2.= "RFF+BM:$bl'\r\n";
                            $tlSegmentos++;
                        }
                        if( !empty($bkgNumber) ) {
                            $enc2.= "RFF+BN:$bkgNumber'\r\n";
                            $tlSegmentos++;
                        }
                        $enc2.="DTM+7:$regFecha$regHora:203'\r\n";
                        $tlSegmentos++;
                        $enc2.= "LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'\r\n";
                        $tlSegmentos++;
                        $enc2.="SEL+$sello+CA'\r\n";
                        $tlSegmentos++;
                        $enc2.="FTX+AAI+++$bkgNumber|$cliente|$transportista'\r\n";
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'\r\n";
                        $tlSegmentos++;
                        // Cuando sean salidas es necesario agregar esta línea para cada movimiento,
                        // ya que con este se especifica el Destination Depot.
                        //$enc2.="LOC+99+GYM00'\r\n";   // 99 : Place of empty equipment return
                        //$enc2.="LOC+99+$destino'\r\n";   // 99 : Place of empty equipment return
                        $enc2.="LOC+99+CIO00'\r\n";   // 99 : Place of empty equipment return
                        $tlSegmentos++;
                        fputs($fp2,$enc2);
                        $flgOkFile=1;
                    }

                }
                $l++;
            }

            // Pie de pagina
            // Total de contenedores
            $pie="CNT+16:$tlConte'\r\n";
            $tlSegmentos++;
            $pie.="UNT+$tlSegmentos+$fecD1$fecD2'\r\n";
            $pie.="UNZ+1+$fecD1$fecD2'";

            fputs($fp2,$pie);
            fclose($fp2);

            unset($rowIterator);
            unset($datos);

            
            if( $flgOkFile==1 ) {
                if ($opcPro == "email") {
                    // sendNotifyOneFile($sesIdUsuario, 'MSC', $fileEDI, $fileName);
                } 
                elseif ($opcPro == "ftp") {
                    sentToFTP($fileEDI,'1CA','/CENTRO AMERICA/NICARAGUA/');
                }
                return $fileEDI;
            }
        }
    }

    // fffffffffffffffffffff FIN ANAKOSTA Fffffffffffffffffffffffffffffffffffffff
    function ediGateInMscExcel($stConte,$sesIdUsuario,$formato,$idNav,$opcPro){

        if( $formato=="excel2007" ) {
            // EXCEL 2007
            // ---------------------------------------------------
            // PROCESO COPIAR ARCHIVO
            // ---------------------------------------------------
            if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/entradas$sesIdUsuario.xlsx");
            } else {
                echo "Error de archivo!.";
            }
            // -------------------------------------
            // GET-IN
            // -------------------------------------
            unset($fp);
            unset($data);
            unset($dataX);
            // ---------------------------------
            // ---- LEER ARCHIVO DE EXCEL ---
            // ---------------------------------
            $objReader = new PHPExcel_Reader_Excel2007();
            //$objReader = new PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader->load("../edi_files/csv/entradas$sesIdUsuario.xlsx");
            $objPHPExcel->setActiveSheetIndex(0);
        }
        elseif( $formato=="excel2005" ) {
            // EXCEL 2007
            // ---------------------------------------------------
            // PROCESO COPIAR ARCHIVO
            // ---------------------------------------------------
            if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/entradas$sesIdUsuario.xls");
            } else {
                echo "Error de archivo!.";
            }
            // -------------------------------------
            // GET-IN
            // -------------------------------------
            unset($fp);
            unset($data);
            unset($dataX);
            // ---------------------------------
            // ---- LEER ARCHIVO DE EXCEL ---
            // ---------------------------------
            $objReader = new PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader->load("../edi_files/csv/entradas$sesIdUsuario.xls");
            $objPHPExcel->setActiveSheetIndex(0);

        }

        $validFile="True";
        $l=1;

        // ------------------
        // Validacion campos
        // ------------------
        // ---------------------------------
        // Validar el encabezado
        // ----------------------------------
        $encC1= $objPHPExcel->getActiveSheet()->getCell("A5")->getValue();
        $encC2= $objPHPExcel->getActiveSheet()->getCell("B5")->getValue();
        $encC3= $objPHPExcel->getActiveSheet()->getCell("C5")->getValue();
        $encC4= $objPHPExcel->getActiveSheet()->getCell("D5")->getValue();
        $encC5= $objPHPExcel->getActiveSheet()->getCell("E5")->getValue();
        $encC6= $objPHPExcel->getActiveSheet()->getCell("F5")->getValue();
        $encC7= $objPHPExcel->getActiveSheet()->getCell("G5")->getValue();
        $encC8= $objPHPExcel->getActiveSheet()->getCell("H5")->getValue();
        $encC9= $objPHPExcel->getActiveSheet()->getCell("I5")->getValue();
        $encC10= $objPHPExcel->getActiveSheet()->getCell("J5")->getValue();
        $encC11= $objPHPExcel->getActiveSheet()->getCell("K5")->getValue();

        if( $encC1!="FECHA"){$validFile=0;echo "[Error Encabezado] <br>";}
        if( $encC2!="HORA" ){$validFile=0;echo "[Error Encabezado] HORA<br>";}
        if( $encC3!="CONTENEDOR"){$validFile=0;echo "[Error Encabezado] CONTENEDOR<br>";}
        if( $encC4!="TIPO" ){$validFile=0;echo "[Error Encabezado] TIPO<br>";}
        if( $encC5!="EIR" ){$validFile=0;echo "[Error Encabezado] EIR<br>";}
        if( $encC6!="MANIOBRA" ){$validFile=0;echo "[Error Encabezado] MANIOBRA<br>";}
        if( $encC7!="TRANSPORTISTA" ){$validFile=0;echo "[Error Encabezado] TRANSPORTISTA<br>";}
        if( $encC8!="CLIENTE" ){$validFile=0;echo "[Error Encabezado] CLIENTE <br>";}
        if( $encC9!="NOTA" ){$validFile=0;echo "[Error Encabezado] NOTA <br>";}
        if( $encC10!="SELLO" ){$validFile=0;echo "[Error Encabezado] SELLO <br>";}
        if( $encC11!="I_E" ){$validFile=0;echo "[Error Encabezado] I_E <br>";}
        // ---- Fin de la validación ----

        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $array_data = array();

        foreach($rowIterator as $row){
            $lin = $row->getRowIndex ();
            //echo "fila ".$row->getRowIndex ();
            //echo "<br>";
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            //if(1 == $row->getRowIndex ()) continue; //skip first row la del encabezado
            $rowIndex = $row->getRowIndex ();
            //$array_data[$rowIndex] = array('A'=>'', 'B'=>'','C'=>'','D'=>'','E'=>'');


            foreach ($cellIterator as $cell) {
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn() ){
                    //$fecha = $cell->getValue();
                    $fecha = $cell->getFormattedValue();
                    // Validar Fecha : En caso de que los formatos sean 01-mes-15
                    unset($parts);
                    if( preg_match("/(\d{1,2})-(\w{3,3})-(\d{2,2})/i",$fecha,$parts) ){
                        // echo "FECHA:  $fecha OK <br>";
                        $dd = $parts[1];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $mes = $parts[2];
                        $aa = 2000 + $parts[3];
                        $mm = getOkMes($mes);
                        $fecha = $aa."-".$mm."-".$dd;
                    }

                    if( $lin>5 && !empty($fecha) && ( !preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha)  ) ){
                        echo "[Error] Lin: $lin | Fecha : $fecha | El formato es incorrecto, cambie el formato como -> aaaa-mm-dd  <br>";
                        $validFile=0;
                    }
                    $datos[$rowIndex]['FECHA'] = $fecha;

                }
                if('B' == $cell->getColumn()){
                    $hora = $cell->getFormattedValue();
                    unset($parts);
                    if( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) PM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        if( $hh<12 ){
                            $hh = $hh + 12;
                        }
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) AM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/^(\d{1,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    $datos[$rowIndex]['HORA'] = $hora;

                }
                if('C' == $cell->getColumn()){
                    $conte = $cell->getValue();
                    $conte = strtoupper($conte);
                    $datos[$rowIndex]['CONTENEDOR'] = $conte;
                }
                if('D' == $cell->getColumn()){
                    $datos[$rowIndex]['EQUIPO'] = $cell->getValue();
                }
                if('E' == $cell->getColumn()){
                    $datos[$rowIndex]['EIR'] = $cell->getValue();
                }
                if('F' == $cell->getColumn()){
                    $datos[$rowIndex]['MANIOBRA'] = $cell->getValue();
                }
                if('G' == $cell->getColumn()){
                    $datos[$rowIndex]['TRANSPORTISTA'] = $cell->getValue();
                }
                if('H' == $cell->getColumn()){
                    $datos[$rowIndex]['CLIENTE'] = $cell->getValue();
                }
                if('I' == $cell->getColumn()){
                    $datos[$rowIndex]['NOTA'] = $cell->getValue();
                }
                if('J' == $cell->getColumn()){
                    $datos[$rowIndex]['SELLO'] = $cell->getValue();
                }
                if('K' == $cell->getColumn()){
                    $datos[$rowIndex]['I_E'] = $cell->getValue();
                }


            }

        }


        if( $validFile=="True" ) {
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $flgRec = 1;

            $l = 1;
            $f = date(Ymd);

            // ------------------------------------------------
            // Generar archivo EDI
            // ------------------------------------------------
            $fileEDI = "../edi_files/edi/".$sesIdUsuario."MSC_GATE_IN".$fecD1.$fecD2.".edi";
            $fileName = $sesIdUsuario."MSC_GATE_IN".$fecD1.$fecD2.".edi";
            $fp2 = fopen("$fileEDI", "w");
            $sepa = "\r\n";
            $codPatio = getValueTable("cod_patio", "USUARIO", "id_usuario", $sesIdUsuario);
            $codPatioName = getValueTable("cod_patio_name", "USUARIO", "id_usuario", $sesIdUsuario);
            $senderID = getValueTable("sender_id", "USUARIO", "id_usuario", $sesIdUsuario);
            $receiverID = "MSC";
            // ENCABEZADO
            $enc = "UNB+UNOA:1+$senderID+$receiverID+$fecD1:$fecD2+$fecD1$fecD2'".$sepa;
            $enc .= "UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'".$sepa;
            $enc .= "BGM+34+$fecD1$fecD2+9'".$sepa;
            //"TDT+20++1++MSC:172:166'".$sepa.
            //"NAD+CA+MXVRCDECECI:172:166'".$sepa;
            //$enc11=utf8_encode($enc);
            fputs($fp2, $enc);


            $tlConte = 0;
            $tlSegmentos = 4;

            // -------------------------------------------------
            // OBTENIENDO LA INFO
            // -------------------------------------------------
            $l=1;
            foreach ($datos as $dato => $x) {

                if( $l>5 ) {
                    $validFec=0;
                    $fecha = $x['FECHA'];

                    /*
                    if( preg_match("/(\d{2})\/(\d{2})\/(\d{4})/i",$fecha,$parts) ){
                        $d= $parts[1];
                        $m= $parts[2];
                        $a= $parts[3];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }
                    */
                    $fecha = $x['FECHA'];
                    if( preg_match("/(\d{4})-(\d{2})-(\d{2})$/i",$fecha,$parts) ){
                        $d= $parts[3];
                        $m= $parts[2];
                        $a= $parts[1];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }

                    $hora = $x['HORA'];
                    $hora = strtolower($hora);
                    $hora = str_replace(" ","",$hora);
                    $hora = str_replace(":","",$hora);
                    $hora = str_replace("-","",$hora);
                    $hora = str_replace(".","",$hora);
                    $hora = str_replace("pm","",$hora);
                    $hora = str_replace("am","",$hora);
                    $hora = str_pad($hora,4,"0", STR_PAD_LEFT);
                    $regHora = $hora;
                    // echo "$regHora <br>";

                    $conte = $x['CONTENEDOR'];
                    $equipo = $x['EQUIPO'];
                    $tipo = getEquipoTipo($equipo);
                    $eir = $x['EIR'];
                    if(!empty($eir)){
                        $eir="EIR: $eir ";
                    }
                    $maniobra = $x['MANIOBRA'];
                    $transportista = $x['TRANSPORTISTA'];
                    $cliente = $x['CLIENTE'];
                    $cliente = strtoupper($cliente);
                    //$bkgNumber = $x['BOOKING'];
                    //$bkgNumber = strtoupper($bkgNumber);
                    $bl = $x['BL'];
                    $sello = $x[SELLO];
                    $impoExpo = $x[I_E];

                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;

                        // ---------------------------------------------------------
                        // COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                        // ----------------------------------------------------------
                        /*
                        // Exepcion: ANAKOSTA, Necesitamos cambiar un código para Anakosta 
                        // (por ser TERMINAL y solo para ellos), cuando sea una entrada en lugar de ser expo debe ser impo.
                        if( $sesIdUsuario==19 ){
                            // Forzar a Import.(Solo para Anakosta)
                            if( $impoExpo=="E" ){
                                $stIE=2;
                            }
                            elseif( $impoExpo=="I" ){
                                $stIE=3;
                            }
                            elseif( empty($impoExpo) ){
                                $stIE=2;
                            }
                        }
                        else{
                            ($impoExpo=="E")?$stIE=2:$stIE=3;
                        }
                        */
                        ($impoExpo=="E")?$stIE=2:$stIE=3;
                        ($stConte=="E")?$stCo=4:$stCo=5;
                        $conte = strtoupper($conte);
                        $enc2= "EQD+CN+$conte+$tipo++$stIE+$stCo'".$sepa;
                        $tlSegmentos++;
                        $enc2.="DTM+7:$regFecha$regHora:203'".$sepa;
                        $tlSegmentos++;
                        $enc2.="LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'".$sepa;
                        $tlSegmentos++;
                        if( !empty($sello) ) {
                            $enc2 .= "SEL+$sello+CA'\r\n";
                            $tlSegmentos++;
                        }
                        $enc2.="FTX+AAI+++$transportista|$eir'".$sepa;
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'".$sepa;
                        $tlSegmentos++;
                        fputs($fp2,$enc2);
                        $flgOkFile=1;
                    }

                }
                $l++;
            }

            // Pie de pagina
            // Total de contenedores
            $pie="CNT+16:$tlConte'".$sepa;
            $tlSegmentos++;
            $pie.="UNT+$tlSegmentos+$fecD1$fecD2'".$sepa;
            $pie.="UNZ+1+$fecD1$fecD2'";

            //$enc33=utf8_encode($enc3);
            fputs($fp2,$pie);
            fclose($fp2);

            unset($rowIterator);
            unset($datos);

            // Los ususarios de CentroAmerica son forzados a enviarlos por FTP
            if ( $sesIdUsuario == 2 || $sesIdUsuario == 6 || $sesIdUsuario == 8 || $sesIdUsuario == 9 ) {
                // 2 Melara
                // 6 Trans America Cargo
                // 8 Usuario
                // 9 Edwin E. Rossell M.
                $opcPro = "ftp";
            }

          
            if( $flgOkFile==1 ) {
                if ($opcPro == "email") {
                    //sendNotifyOneFile($sesIdUsuario, 'MSC', $fileEDI, $fileName);
                } 
                elseif ($opcPro == "ftp") {
                    // -----------------------------
                    // FTP - GETIN
                    // -----------------------------
                    if ( $sesIdUsuario == 2 || $sesIdUsuario == 6 || $sesIdUsuario == 8 || $sesIdUsuario == 9 ) {
                        // El salvador
                        sentToFTP($fileEDI,'1CA','/CENTRO AMERICA/EL SALVADOR/');
                    }
                    else {
                        // Mexico
                        sentToFTP($fileEDI,$idNav,'/MEXICO/MEXICO/');
                    }
                }
                return $fileEDI;                
            }
        }

    }
    // ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateOutMscExcel($stConte,$sesIdUsuario,$formato,$idNav,$opcPro){


        if( $formato=="excel2007" ) {
            // EXCEL 2007 o mayor
            // ---------------------------------------------------
            // PROCESO COPIAR ARCHIVO
            // ---------------------------------------------------
            if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/salidas$sesIdUsuario.xlsx");
            } else {
                echo "Error de archivo!.";
            }
            // -------------------------------------
            // GET-IN
            // -------------------------------------
            unset($fp);
            unset($data);
            unset($dataX);
            // ---------------------------------
            // ---- LEER ARCHIVO DE EXCEL ---
            // ---------------------------------
            $objReader = new PHPExcel_Reader_Excel2007();
            //$objReader = new PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader->load("../edi_files/csv/salidas$sesIdUsuario.xlsx");
            $objPHPExcel->setActiveSheetIndex(1);
        }
        elseif( $formato=="excel2005" ) {
            // EXCEL 2007 o mayor
            // ---------------------------------------------------
            // PROCESO COPIAR ARCHIVO
            // ---------------------------------------------------
            if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/salidas$sesIdUsuario.xls");
            } else {
                echo "Error de archivo!.";
            }
            // -------------------------------------
            // GET-IN
            // -------------------------------------
            unset($fp);
            unset($data);
            unset($dataX);
            // ---------------------------------
            // ---- LEER ARCHIVO DE EXCEL ---
            // ---------------------------------
            $objReader = new PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader->load("../edi_files/csv/salidas$sesIdUsuario.xls");
            $objPHPExcel->setActiveSheetIndex(1);
        }

        $validFile="True";
        $l=1;

        // ---------------------------------
        // Validar el encabezado
        // ----------------------------------
        $encC1= $objPHPExcel->getActiveSheet()->getCell("A5")->getValue();
        $encC2= $objPHPExcel->getActiveSheet()->getCell("B5")->getValue();
        $encC3= $objPHPExcel->getActiveSheet()->getCell("C5")->getValue();
        $encC4= $objPHPExcel->getActiveSheet()->getCell("D5")->getValue();
        $encC5= $objPHPExcel->getActiveSheet()->getCell("E5")->getValue();
        $encC6= $objPHPExcel->getActiveSheet()->getCell("F5")->getValue();
        $encC7= $objPHPExcel->getActiveSheet()->getCell("G5")->getValue();
        $encC8= $objPHPExcel->getActiveSheet()->getCell("H5")->getValue();
        $encC9= $objPHPExcel->getActiveSheet()->getCell("I5")->getValue();
        $encC10= $objPHPExcel->getActiveSheet()->getCell("J5")->getValue();
        $encC11= $objPHPExcel->getActiveSheet()->getCell("K5")->getValue();
        $encC12= $objPHPExcel->getActiveSheet()->getCell("L5")->getValue();
        $encC13= $objPHPExcel->getActiveSheet()->getCell("M5")->getValue();
        $encC14= $objPHPExcel->getActiveSheet()->getCell("N5")->getValue();
        $encC15= $objPHPExcel->getActiveSheet()->getCell("O5")->getValue();

        if( $encC1!="FECHA"){$validFile=0;echo "[Error Encabezado] FECHA<br>";}
        if( $encC2!="HORA" ){$validFile=0;echo "[Error Encabezado] HORA<br>";}
        if( $encC3!="CONTENEDOR" ){$validFile=0;echo "[Error Encabezado] CONTENEDOR<br>";}
        if( $encC4!="TIPO" ){$validFile=0;echo "[Error Encabezado] TIPO<br>";}
        if( $encC5!="EIR" ){$validFile=0;echo "[Error Encabezado] EIR<br>";}
        if( $encC6!="MANIOBRA" ){$validFile=0;echo "[Error Encabezado] MANIOBRA<br>";}
        if( $encC7!="TRANSPORTISTA" ){$validFile=0;echo "[Error Encabezado] TRANSPORTISTA<br>";}
        if( $encC8!="CLIENTE" ){$validFile=0;echo "[Error Encabezado] CLIENTE<br>";}
        if( $encC9!="AGENTE ADUANAL" ){$validFile=0;echo "[Error Encabezado] AGENTE ADUANAL <br>";}
        if( $encC10!="BL" ){$validFile=0;echo "[Error Encabezado] BL <br>";}
        if( $encC11!="BOOKING" ){$validFile=0;echo "[Error Encabezado] BOOKING <br>";}
        if( $encC12!="BUQUE" ){$validFile=0;echo "[Error Encabezado] BUQUE <br>";}
        if( $encC13!="VIAJE" ){$validFile=0;echo "[Error Encabezado] VIAJE <br>";}
        if( $encC14!="SELLO" ){$validFile=0;echo "[Error Encabezado] SELLO <br>";}
        if( $encC15!="I_E" ){$validFile=0;echo "[Error Encabezado] I_E <br>";}
        // ---- Fin de la validación ----


        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $array_data = array();
        foreach($rowIterator as $row){
            $lin = $row->getRowIndex ();
            //echo "fila ".$row->getRowIndex ();
            //echo "<br>";
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            //if(1 == $row->getRowIndex ()) continue; //skip first row la del encabezado
            $rowIndex = $row->getRowIndex ();
            //$array_data[$rowIndex] = array('A'=>'', 'B'=>'','C'=>'','D'=>'','E'=>'');

            foreach ($cellIterator as $cell) {
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn() ){
                    //$fecha = $cell->getValue();
                    $fecha = $cell->getFormattedValue();
                    // Validar Fecha : En caso de que los formatos sean 01-mes-15
                    unset($parts);
                    if( preg_match("/(\d{1,2})-(\w{3,3})-(\d{2,2})/i",$fecha,$parts) ){
                        // echo "FECHA:  $fecha OK <br>";
                        $dd = $parts[1];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $mes = $parts[2];
                        $aa = 2000 + $parts[3];
                        $mm = getOkMes($mes);
                        $fecha = $aa."-".$mm."-".$dd;
                    }
                    if( $lin>5 && !empty($fecha) && ( !preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha)  ) ){
                        echo "[Error] Lin: $lin | Fecha : $fecha | El formato es incorrecto, cambie el formato como -> aaaa-mm-dd  <br>";
                        $validFile=0;
                    }
                    $datos[$rowIndex]['FECHA'] = $fecha;
                }
                if('B' == $cell->getColumn()){
                    $hora = $cell->getFormattedValue();
                    unset($parts);
                    if( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) PM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        if( $hh<12 ){
                            $hh = $hh + 12;
                        }
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) AM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/^(\d{1,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    $datos[$rowIndex]['HORA'] = $hora;
                }
                if('C' == $cell->getColumn()){
                    $conte = $cell->getValue();
                    $conte = strtoupper($conte);
                    $datos[$rowIndex]['CONTENEDOR'] = $conte;
                }
                if('D' == $cell->getColumn()){
                    $datos[$rowIndex]['EQUIPO'] = $cell->getValue();
                }
                if('E' == $cell->getColumn()){
                    $datos[$rowIndex]['EIR'] = $cell->getValue();
                }
                if('F' == $cell->getColumn()){
                    $datos[$rowIndex]['MANIOBRA'] = $cell->getValue();
                }
                if('G' == $cell->getColumn()){
                    $datos[$rowIndex]['TRANSPORTISTA'] = $cell->getValue();
                }
                if('H' == $cell->getColumn()){
                    $datos[$rowIndex]['CLIENTE'] = $cell->getValue();
                }
                if('I' == $cell->getColumn()){
                    $datos[$rowIndex]['AGENTE ADUANAL'] = $cell->getValue();
                }
                if('J' == $cell->getColumn()){
                    $datos[$rowIndex]['BL'] = $cell->getValue();
                }
                if('K' == $cell->getColumn()){
                    $datos[$rowIndex]['BOOKING'] = $cell->getValue();
                }
                if('L' == $cell->getColumn()){
                    $datos[$rowIndex]['BUQUE'] = $cell->getValue();
                }
                if('M' == $cell->getColumn()){
                    $datos[$rowIndex]['VIAJE'] = $cell->getValue();
                }
                if('N' == $cell->getColumn()){
                    $datos[$rowIndex]['SELLO'] = $cell->getValue();
                }
                if('O' == $cell->getColumn()){
                    $datos[$rowIndex]['I_E'] = $cell->getValue();
                }

            }
        }

        if( $validFile=="True" ) {
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $flgRec = 1;

            $l = 1;
            $f = date(Ymd);

            // ------------------------------------------------
            // Generar archivo EDI OUT
            // ------------------------------------------------
            $fileEDI = "../edi_files/edi/".$sesIdUsuario."MSC_GATE_OUT".$fecD1.$fecD2.".edi";
            $fileName = $sesIdUsuario."MSC_GATE_OUT".$fecD1.$fecD2.".edi";
            $fp2 = fopen("$fileEDI", "w");
            $sepa = "\r\n";
            $codPatio = getValueTable("cod_patio", "USUARIO", "id_usuario", $sesIdUsuario);
            $codPatioName = getValueTable("cod_patio_name", "USUARIO", "id_usuario", $sesIdUsuario);
            $senderID = getValueTable("sender_id", "USUARIO", "id_usuario", $sesIdUsuario);
            $receiverID = "MSC";
            // ENCABEZADO
            $enc = "UNB+UNOA:1+$senderID+$receiverID+$fecD1:$fecD2+$fecD1$fecD2'" . $sepa;
            $enc .= "UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'" . $sepa;
            $enc .= "BGM+36+$fecD1$fecD2+9'" . $sepa;
            //"TDT+20++1++MSC:172:166'".$sepa.
            //"NAD+CA+MXVRCDECECI:172:166'".$sepa;
            //$enc11=utf8_encode($enc);
            fputs($fp2, $enc);


            $tlConte = 0;
            $tlSegmentos = 4;

            // -------------------------------------------------
            // OBTENIENDO LA INFO
            // -------------------------------------------------
            $l=1;
            foreach ($datos as $dato => $x) {

                if( $l>5 ) {
                    $validFec=0;
                    $fecha = $x['FECHA'];
                    if( preg_match("/(\d{4})-(\d{2})-(\d{2})$/i",$fecha,$parts) ){
                        $a= $parts[1];
                        $m= $parts[2];
                        $d= $parts[3];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }

                    $hora = $x['HORA'];
                    $hora = strtolower($hora);
                    $hora = str_replace(" ","",$hora);
                    $hora = str_replace(":","",$hora);
                    $hora = str_replace("-","",$hora);
                    $hora = str_replace(".","",$hora);
                    $hora = str_replace("pm","",$hora);
                    $hora = str_replace("am","",$hora);
                    $regHora = $hora;

                    $conte = $x['CONTENEDOR'];
                    $equipo = $x['EQUIPO'];
                    $tipo = getEquipoTipo($equipo);
                    // $eir = $x['EIR'];
                    $maniobra = $x['MANIOBRA'];
                    $transportista = $x['TRANSPORTISTA'];
                    $cliente = $x['CLIENTE'];
                    $cliente = strtoupper($cliente);
                    $bkgNumber = $x['BOOKING'];
                    $bkgNumber = strtoupper($bkgNumber);
                    $bl = $x['BL'];
                    $sello = $x[SELLO];
                    $impoExpo = $x[I_E];

                    if( preg_match("/^([0-9]+).*/",$bkgNumber,$parts) ){
                        $codBkg = $parts[1];
                        switch($codBkg){
                            case "192": $codOD = "LMR00";break;
                            case "364": $codOD = "ZLO00";break;
                            case "191": $codOD = "VER00";break;
                            case "404": $codOD = "MZT00";break;
                            case "697": $codOD = "GYM00";break;
                            case "620": $codOD = "LZC00";break;
                            case "622": $codOD = "PUM00";break;
                        }
                    }

                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;

                        // ---------------------------------------------------------
                        // COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                        // ----------------------------------------------------------
                        ($impoExpo=="E")?$stIE=2:$stIE=3;
                        ($stConte=="E")?$stCo=4:$stCo=5;
                        $conte = strtoupper($conte);
                        $enc2= "EQD+CN+$conte+$tipo++$stIE+$stCo'".$sepa;
                        $tlSegmentos++;
                        if( !empty($bl) ){
                            $enc2.= "RFF+BM:$bl'\r\n";
                            $tlSegmentos++;
                        }
                        if( !empty($bkgNumber) ) {
                            $enc2.= "RFF+BN:$bkgNumber'\r\n";
                            $tlSegmentos++;
                        }
                        $enc2.="DTM+7:$regFecha$regHora:203'\r\n";
                        $tlSegmentos++;
                        $enc2.= "LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'\r\n";
                        $tlSegmentos++;
                        if( !empty($sello) ){
                            $enc2.="SEL+$sello+CA'\r\n";
                            $tlSegmentos++;
                        }
                        $enc2.="FTX+AAI+++$bkgNumber|$cliente|$transportista'\r\n";
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'\r\n";
                        $tlSegmentos++;
                        // Cuando sean salidas es necesario agregar esta línea para cada movimiento,
                        // ya que con este se especifica el Destination Depot.
                        
                        // Solo para Anakosta.
                        if( $sesIdUsuario==19 ){
                            $codOD=$senderID;
                        }

                        $enc2.="LOC+99+$codOD'\r\n";   // 99 : Place of empty equipment return
                        $tlSegmentos++;
                        fputs($fp2,$enc2);
                        $flgOkFile=1;
                    }

                }
                $l++;
            }

            // Pie de pagina
            // Total de contenedores
            $pie="CNT+16:$tlConte'".$sepa;
            $tlSegmentos++;
            $pie.="UNT+$tlSegmentos+$fecD1$fecD2'".$sepa;
            $pie.="UNZ+1+$fecD1$fecD2'";

            fputs($fp2,$pie);
            fclose($fp2);

            unset($rowIterator);
            unset($datos);

            
            if( $flgOkFile==1 ) {
                
                // Los ususarios de CentroAmerica son forzados a enviarlos por FTP
                if ( $sesIdUsuario == 2 || $sesIdUsuario == 6 || $sesIdUsuario == 8 || $sesIdUsuario == 9 ) {
                    // 2 Melara
                    // 6 Trans America Cargo
                    // 8 Usuario
                    // 9 Edwin E. Rossell M.
                    $opcPro = "ftp";
                }


                if ($opcPro == "email") {
                    //sendNotifyOneFile($sesIdUsuario, 'MSC', $fileEDI, $fileName);
                } 
                elseif ($opcPro == "ftp") {
                    // -----------------------------
                    // FTP - GETOUT
                    // -----------------------------
                    if ($sesIdUsuario == 2 || $sesIdUsuario == 6 || $sesIdUsuario == 8 || $sesIdUsuario == 9) {
                        // El Salvador
                        sentToFTP($fileEDI,'1CA','/CENTRO AMERICA/EL SALVADOR/');
                    } 
                    else {
                        sentToFTP($fileEDI,$idNav,'/MEXICO/MEXICO/');
                    }
                }
                return $fileEDI;
            }


        }

    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGatePosMscExcel($stConte,$sesIdUsuario,$formato,$idNav,$opcPro){

        // POSICIONAMIENTOS

        if( $formato=="excel2007" ) {
            // EXCEL 2007 o mayor
            // ---------------------------------------------------
            // PROCESO COPIAR ARCHIVO
            // ---------------------------------------------------
            if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/pos$sesIdUsuario.xlsx");
            } else {
                echo "Error de archivo!.";
            }
            // -------------------------------------
            // GET-IN
            // -------------------------------------
            unset($fp);
            unset($data);
            unset($dataX);
            // ---------------------------------
            // ---- LEER ARCHIVO DE EXCEL ---
            // ---------------------------------
            $objReader = new PHPExcel_Reader_Excel2007();
            //$objReader = new PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader->load("../edi_files/csv/pos$sesIdUsuario.xlsx");
            $objPHPExcel->setActiveSheetIndex(2);
        }
        elseif( $formato=="excel2005" ) {
            // EXCEL 2007 o mayor
            // ---------------------------------------------------
            // PROCESO COPIAR ARCHIVO
            // ---------------------------------------------------
            if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/pos$sesIdUsuario.xlsx");
            } else {
                echo "Error de archivo!.";
            }
            // -------------------------------------
            // GET-IN
            // -------------------------------------
            unset($fp);
            unset($data);
            unset($dataX);
            // ---------------------------------
            // ---- LEER ARCHIVO DE EXCEL ---
            // ---------------------------------
            $objReader = new PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader->load("../edi_files/csv/pos$sesIdUsuario.xls");
            $objPHPExcel->setActiveSheetIndex(2);
        }

        $validFile="True";
        $l=1;

        // ---------------------------------
        // Validar el encabezado
        // ----------------------------------
        $encC1= $objPHPExcel->getActiveSheet()->getCell("A5")->getValue();
        $encC2= $objPHPExcel->getActiveSheet()->getCell("B5")->getValue();
        $encC3= $objPHPExcel->getActiveSheet()->getCell("C5")->getValue();
        $encC4= $objPHPExcel->getActiveSheet()->getCell("D5")->getValue();
        $encC5= $objPHPExcel->getActiveSheet()->getCell("E5")->getValue();
        $encC6= $objPHPExcel->getActiveSheet()->getCell("F5")->getValue();
        $encC7= $objPHPExcel->getActiveSheet()->getCell("G5")->getValue();
        $encC8= $objPHPExcel->getActiveSheet()->getCell("H5")->getValue();
        $encC9= $objPHPExcel->getActiveSheet()->getCell("I5")->getValue();
        $encC10= $objPHPExcel->getActiveSheet()->getCell("J5")->getValue();
        $encC11= $objPHPExcel->getActiveSheet()->getCell("K5")->getValue();
        $encC12= $objPHPExcel->getActiveSheet()->getCell("L5")->getValue();
        $encC13= $objPHPExcel->getActiveSheet()->getCell("M5")->getValue();
        $encC14= $objPHPExcel->getActiveSheet()->getCell("N5")->getValue();
        $encC15= $objPHPExcel->getActiveSheet()->getCell("O5")->getValue();
        $encC16= $objPHPExcel->getActiveSheet()->getCell("P5")->getValue();

        if( $encC1!="FECHA"){$validFile=0;echo "[Error Encabezado] FECHA<br>";}
        if( $encC2!="HORA" ){$validFile=0;echo "[Error Encabezado] HORA<br>";}
        if( $encC3!="CONTENEDOR" ){$validFile=0;echo "[Error Encabezado] CONTENEDOR<br>";}
        if( $encC4!="TIPO" ){$validFile=0;echo "[Error Encabezado] TIPO<br>";}
        if( $encC5!="EIR" ){$validFile=0;echo "[Error Encabezado] EIR<br>";}
        if( $encC6!="MANIOBRA" ){$validFile=0;echo "[Error Encabezado] MANIOBRA<br>";}
        if( $encC7!="TRANSPORTISTA" ){$validFile=0;echo "[Error Encabezado] TRANSPORTISTA<br>";}
        if( $encC8!="CLIENTE" ){$validFile=0;echo "[Error Encabezado] CLIENTE<br>";}
        if( $encC9!="AGENTE ADUANAL" ){$validFile=0;echo "[Error Encabezado] AGENTE ADUANAL<br>";}
        if( $encC10!="BL" ){$validFile=0;echo "[Error Encabezado] BL<br>";}
        if( $encC11!="BOOKING" ){$validFile=0;echo "[Error Encabezado] BOOKING<br>";}
        if( $encC12!="BUQUE" ){$validFile=0;echo "[Error Encabezado] BUQUE<br>";}
        if( $encC13!="VIAJE" ){$validFile=0;echo "[Error Encabezado] VIAJE<br>";}
        if( $encC14!="SELLO" ){$validFile=0;echo "[Error Encabezado] SELLO<br>";}
        if( $encC15!="DESTINO" ){$validFile=0;echo "[Error Encabezado] SELLO<br>";}
        if( $encC16!="I_E" ){$validFile=0;echo "[Error Encabezado] I_E<br>";}
        // ---- Fin de la validación ----

        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $array_data = array();
        foreach($rowIterator as $row){
            $lin = $row->getRowIndex ();
            //echo "fila ".$row->getRowIndex ();
            //echo "<br>";
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            //if(1 == $row->getRowIndex ()) continue; //skip first row la del encabezado
            $rowIndex = $row->getRowIndex ();
            //$array_data[$rowIndex] = array('A'=>'', 'B'=>'','C'=>'','D'=>'','E'=>'');

            foreach ($cellIterator as $cell) {
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn() ){
                    $fecha = $cell->getFormattedValue();
                    // Validar Fecha : En caso de que los formatos sean 01-mes-15
                    unset($parts);
                    if( preg_match("/(\d{1,2})-(\w{3,3})-(\d{2,2})/i",$fecha,$parts) ){
                        // echo "FECHA:  $fecha OK <br>";
                        $dd = $parts[1];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $mes = $parts[2];
                        $aa = 2000 + $parts[3];
                        $mm = getOkMes($mes);
                        $fecha = $aa."-".$mm."-".$dd;
                    }

                    if( $lin>5 && !empty($fecha) && ( !preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha)  ) ){
                        echo "[Error] Lin: $lin | Fecha : $fecha | El formato es incorrecto, cambie el formato como -> aaaa-mm-dd  <br>";
                        $validFile=0;
                    }
                    $datos[$rowIndex]['FECHA'] = $fecha;
                }
                if('B' == $cell->getColumn()){
                    $hora = $cell->getFormattedValue();
                    unset($parts);
                    if( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) PM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        if( $hh<12 ){
                            $hh = $hh + 12;
                        }
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) AM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/^(\d{1,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    $datos[$rowIndex]['HORA'] = $hora;
                }
                if('C' == $cell->getColumn()){
                    $conte = $cell->getValue();
                    $conte = strtoupper($conte);
                    $datos[$rowIndex]['CONTENEDOR'] = $conte;
                }
                if('D' == $cell->getColumn()){
                    $datos[$rowIndex]['EQUIPO'] = $cell->getValue();
                }
                if('E' == $cell->getColumn()){
                    $datos[$rowIndex]['EIR'] = $cell->getValue();
                }
                if('F' == $cell->getColumn()){
                    $datos[$rowIndex]['MANIOBRA'] = $cell->getValue();
                }
                if('G' == $cell->getColumn()){
                    $datos[$rowIndex]['TRANSPORTISTA'] = $cell->getValue();
                }
                if('H' == $cell->getColumn()){
                    $datos[$rowIndex]['CLIENTE'] = $cell->getValue();
                }
                if('I' == $cell->getColumn()){
                    $datos[$rowIndex]['AGENTE ADUANAL'] = $cell->getValue();
                }
                if('J' == $cell->getColumn()){
                    $datos[$rowIndex]['BL'] = $cell->getValue();
                }
                if('K' == $cell->getColumn()){
                    $datos[$rowIndex]['BOOKING'] = $cell->getValue();
                }
                if('L' == $cell->getColumn()){
                    $datos[$rowIndex]['BUQUE'] = $cell->getValue();
                }
                if('M' == $cell->getColumn()){
                    $datos[$rowIndex]['VIAJE'] = $cell->getValue();
                }
                if('N' == $cell->getColumn()){
                    $datos[$rowIndex]['SELLO'] = $cell->getValue();
                }
                if('O' == $cell->getColumn()){
                    $datos[$rowIndex]['DESTINO'] = $cell->getValue();
                }
                if('P' == $cell->getColumn()){
                    $datos[$rowIndex]['I_E'] = $cell->getValue();
                    $impoExpo = $cell->getValue();
                    if( empty($impoExpo) && !empty($conte) ){
                        echo "[ERROR] Lin: $lin | Import o Export (I,E) No especificado.<br>";
                    }
                }
            }
        }

        if( $validFile=="True" ) {
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $flgRec = 1;

            $l = 1;
            $f = date(Ymd);

            // ------------------------------------------------
            // Generar archivo EDI
            // ------------------------------------------------
            $codPatio = getValueTable("cod_patio", "USUARIO", "id_usuario", $sesIdUsuario);
            $fileEDI = "../edi_files/edi/".$sesIdUsuario."MSC_GATE_POS_".$codPatio."_".$fecD1.$fecD2.".edi";
            $fileName = str_replace("../edi_files/edi/","",$fileEDI);
            $fp2 = fopen("$fileEDI", "w");

            $codPatioName = $codPatio;
            $senderID = getValueTable("sender_id", "CODIGOS", "cod_patio", $codPatio);
            $receiverID = "MSC";

            // ENCABEZADO
            $enc= "UNB+UNOA:1+$senderID+$receiverID+$fecD1:$fecD2+$fecD1$fecD2'\r\n";
            $enc.= "UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'\r\n";
            // BGM35 : Es para posicionamientos
            $enc.= "BGM+35+$fecD1$fecD2+9'\r\n";
            //"TDT+20++1++MSC:172:166'".$sepa.
            //"NAD+CA+MXVRCDECECI:172:166'".$sepa;
            //$enc11=utf8_encode($enc);
            fputs($fp2, $enc);

            $tlConte = 0;
            $tlSegmentos = 4;
            // -------------------------------------------------
            // OBTENIENDO LA INFO
            // -------------------------------------------------
            $l=1;
            foreach ($datos as $dato => $x) {

                if( $l>5 ) {
                    $validFec=0;

                    $fecha = $x['FECHA'];
                    if( preg_match("/(\d{4})-(\d{2})-(\d{2})$/i",$fecha,$parts) ){
                        $a= $parts[1];
                        $m= $parts[2];
                        $d= $parts[3];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }
                    $hora = $x['HORA'];
                    $hora = strtolower($hora);
                    $hora = str_replace(" ","",$hora);
                    $hora = str_replace(":","",$hora);
                    $hora = str_replace("-","",$hora);
                    $hora = str_replace(".","",$hora);
                    $hora = str_replace("pm","",$hora);
                    $hora = str_replace("am","",$hora);
                    $hora = str_pad($hora,4,"0", STR_PAD_LEFT);
                    $regHora = $hora;

                    $conte = $x['CONTENEDOR'];
                    $equipo = $x['EQUIPO'];
                    $tipo = getEquipoTipo($equipo);
                    // $eir = $x['EIR'];
                    $maniobra = $x['MANIOBRA'];
                    $transportista = $x['TRANSPORTISTA'];
                    $cliente = $x['CLIENTE'];
                    $cliente = strtoupper($cliente);
                    $bkgNumber = $x['BOOKING'];
                    $bl = $x['BL'];
                    $bkgNumber = strtoupper($bkgNumber);
                    $sello = $x[SELLO];
                    $destino = $x[DESTINO];
                    $impoExpo = $x[I_E];

                    if( preg_match("/^([0-9]+).*/",$bkgNumber,$parts) ){
                        $codBkg = $parts[1];
                        switch($codBkg){
                            case "192": $codOD = "LMR00";break;
                            case "364": $codOD = "ZLO00";break;
                            case "191": $codOD = "VER00";break;
                            case "404": $codOD = "MZT00";break;
                            case "697": $codOD = "GYM00";break;
                            case "620": $codOD = "LZC00";break;
                            case "622": $codOD = "PUM00";break;
                        }
                    }

                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;

                        // ---------------------------------------------------------
                        // COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                        // ----------------------------------------------------------
                        ( $impoExpo=="E" )?$stIE=2:$stIE=3;
                        ( $stConte=="E" )?$stCo=4:$stCo=5;
                        $conte = strtoupper($conte);
                        $enc2= "EQD+CN+$conte+$tipo++$stIE+$stCo'\r\n";
                        $tlSegmentos++;
                        if( !empty($bl) ){
                            $enc2.= "RFF+BM:$bl'\r\n";
                            $tlSegmentos++;
                        }
                        if( !empty($bkgNumber) ) {
                            $enc2.= "RFF+BN:$bkgNumber'\r\n";
                            $tlSegmentos++;
                        }
                        $enc2.="DTM+7:$regFecha$regHora:203'\r\n";
                        $tlSegmentos++;
                        $enc2.= "LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'\r\n";
                        $tlSegmentos++;
                        $enc2.="SEL+$sello+CA'\r\n";
                        $tlSegmentos++;
                        $enc2.="FTX+AAI+++$bkgNumber|$cliente|$transportista'\r\n";
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'\r\n";
                        $tlSegmentos++;
                        // Cuando sean salidas es necesario agregar esta línea para cada movimiento,
                        // ya que con este se especifica el Destination Depot.
                        //$enc2.="LOC+99+GYM00'\r\n";   // 99 : Place of empty equipment return
                        $enc2.="LOC+99+$destino'\r\n";   // 99 : Place of empty equipment return
                        $tlSegmentos++;
                        fputs($fp2,$enc2);
                        $flgOkFile=1;
                    }

                }
                $l++;
            }

            // Pie de pagina
            // Total de contenedores
            $pie="CNT+16:$tlConte'\r\n";
            $tlSegmentos++;
            $pie.="UNT+$tlSegmentos+$fecD1$fecD2'\r\n";
            $pie.="UNZ+1+$fecD1$fecD2'";

            fputs($fp2,$pie);
            fclose($fp2);

            unset($rowIterator);
            unset($datos);

            
            if( $flgOkFile==1 ) {
                
                // Los ususarios de CentroAmerica son forzados a enviarlos por FTP
                if ( $sesIdUsuario == 2 || $sesIdUsuario == 6 || $sesIdUsuario == 8 || $sesIdUsuario == 9 ) {
                    // 2 Melara
                    // 6 Trans America Cargo
                    // 8 Usuario
                    // 9 Edwin E. Rossell M.
                    $opcPro = "ftp";
                }

                if ($opcPro == "email") {
                    // sendNotifyOneFile($sesIdUsuario, 'MSC', $fileEDI, $fileName);
                } 
                elseif ($opcPro == "ftp") {
                    // -----------------------------
                    // FTP - GETIN
                    // -----------------------------
                    if ($sesIdUsuario == 2 || $sesIdUsuario == 6 || $sesIdUsuario == 8 || $sesIdUsuario == 9) {
                        // El Salvador
                        sentToFTP($fileEDI,'1CA','/CENTRO AMERICA/EL SALVADOR/');
                    } 
                    else {
                        sentToFTP($fileEDI,$idNav,'/MEXICO/MEXICO/');
                    }
                }
                return $fileEDI;
            }
        }
    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateInMscExcelGymCoarri($stConte,$impoExpo,$sesIdUsuario,$formato,$idNav,$opcPro){

        // COARRI
        // Se quedo como experimental por que esta mas complejo de armar.
        // Att. Nestor.

        if( $formato=="excel2007" ) {
            // EXCEL 2007
            // ---------------------------------------------------
            // PROCESO COPIAR ARCHIVO
            // ---------------------------------------------------
            if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/entradas$sesIdUsuario.xlsx");
            } else {
                echo "Error de archivo!.";
            }
            // -------------------------------------
            // GET-IN
            // -------------------------------------
            unset($fp);
            unset($data);
            unset($dataX);
            // ---------------------------------
            // ---- LEER ARCHIVO DE EXCEL ---
            // ---------------------------------
            $objReader = new PHPExcel_Reader_Excel2007();
            //$objReader = new PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader->load("../edi_files/csv/entradas$sesIdUsuario.xlsx");
            $objPHPExcel->setActiveSheetIndex(0);
        }
        elseif( $formato=="excel2005" ) {
            // EXCEL 2007
            // ---------------------------------------------------
            // PROCESO COPIAR ARCHIVO
            // ---------------------------------------------------
            if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/entradas$sesIdUsuario.xls");
            } else {
                echo "Error de archivo!.";
            }
            // -------------------------------------
            // GET-IN
            // -------------------------------------
            unset($fp);
            unset($data);
            unset($dataX);
            // ---------------------------------
            // ---- LEER ARCHIVO DE EXCEL ---
            // ---------------------------------
            $objReader = new PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader->load("../edi_files/csv/entradas$sesIdUsuario.xls");
            $objPHPExcel->setActiveSheetIndex(0);

        }

        $validFile="True";
        $l=1;

        // ------------------
        // Validacion campos
        // ------------------
        // ---------------------------------
        // Validar el encabezado
        // ----------------------------------
        $encC1= $objPHPExcel->getActiveSheet()->getCell("A5")->getValue();
        $encC2= $objPHPExcel->getActiveSheet()->getCell("B5")->getValue();
        $encC3= $objPHPExcel->getActiveSheet()->getCell("C5")->getValue();
        $encC4= $objPHPExcel->getActiveSheet()->getCell("D5")->getValue();
        $encC5= $objPHPExcel->getActiveSheet()->getCell("E5")->getValue();
        $encC6= $objPHPExcel->getActiveSheet()->getCell("F5")->getValue();
        $encC7= $objPHPExcel->getActiveSheet()->getCell("G5")->getValue();
        $encC8= $objPHPExcel->getActiveSheet()->getCell("H5")->getValue();
        $encC9= $objPHPExcel->getActiveSheet()->getCell("I5")->getValue();
        $encC10= $objPHPExcel->getActiveSheet()->getCell("J5")->getValue();
        $encC11= $objPHPExcel->getActiveSheet()->getCell("K5")->getValue();
        $encC12= $objPHPExcel->getActiveSheet()->getCell("L5")->getValue();
        $encC13= $objPHPExcel->getActiveSheet()->getCell("M5")->getValue();

        if( $encC1!="FECHA"){$validFile=0;echo "[Error Encabezado] FECHA<br>";}
        if( $encC2!="HORA" ){$validFile=0;echo "[Error Encabezado] HORA<br>";}
        if( $encC3!="CONTENEDOR"){$validFile=0;echo "[Error Encabezado] CONTENEDOR<br>";}
        if( $encC4!="TIPO" ){$validFile=0;echo "[Error Encabezado] TIPO<br>";}
        if( $encC5!="EIR" ){$validFile=0;echo "[Error Encabezado] EIR<br>";}
        if( $encC6!="MANIOBRA" ){$validFile=0;echo "[Error Encabezado] MANIOBRA<br>";}
        if( $encC7!="TRANSPORTISTA" ){$validFile=0;echo "[Error Encabezado] TRANSPORTISTA<br>";}
        if( $encC8!="CLIENTE" ){$validFile=0;echo "[Error Encabezado] CLIENTE<br>";}
        if( $encC9!="AGENTE ADUANAL" ){$validFile=0;echo "[Error Encabezado] AGENTE ADUANAL<br>";}
        if( $encC10!="BOOKING" ){$validFile=0;echo "[Error Encabezado] BOOKING<br>";}
        if( $encC11!="BUQUE" ){$validFile=0;echo "[Error Encabezado] BUQUE<br>";}
        if( $encC12!="VIAJE" ){$validFile=0;echo "[Error Encabezado] VIAJE<br>";}
        if( $encC13!="SELLO" ){$validFile=0;echo "[Error Encabezado] SELLO<br>";}

        // ---- Fin de la validación ----

        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $array_data = array();

        foreach($rowIterator as $row){
            $lin = $row->getRowIndex ();
            //echo "fila ".$row->getRowIndex ();
            // echo "<br>";
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            //if(1 == $row->getRowIndex ()) continue; //skip first row la del encabezado
            $rowIndex = $row->getRowIndex ();
            //$array_data[$rowIndex] = array('A'=>'', 'B'=>'','C'=>'','D'=>'','E'=>'');

            foreach ($cellIterator as $cell) {
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn() ){
                    $fecha = $cell->getValue();
                    // Validar Fecha
                    if( $lin>5 && !empty($fecha) && ( !preg_match("/(\d{2})\/(\d{2})\/(\d{4})/i",$fecha) && !preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha)  ) ){
                        echo "[Error] Lin: $lin | Fecha : $fecha | El formato es incorrecto, cambie el formato a texto -> dd/mm/aaaa  <br>";
                        $validFile=0;
                    }
                    $datos[$rowIndex]['FECHA'] = $fecha;
                }
                if('B' == $cell->getColumn()){
                    $hora = $cell->getValue();
                    // Validar Hora
                    if( $lin>5 && !empty($hora) && !preg_match("/\d{2,2}:\d{2,2}$/",$hora) ){
                        echo "[Error] Lin: $lin | Hora : $hora | El formato es incorrecto, cambie el formato de la columba a TEXTO, <br>";
                        $validFile=0;
                    }
                    $datos[$rowIndex]['HORA'] = $hora;
                }
                if('C' == $cell->getColumn()){
                    $conte = $cell->getValue();
                    $conte = strtoupper($conte);
                    $datos[$rowIndex]['CONTENEDOR'] = $conte;
                }
                if('D' == $cell->getColumn()){
                    $datos[$rowIndex]['EQUIPO'] = $cell->getValue();
                }
                if('E' == $cell->getColumn()){
                    $datos[$rowIndex]['EIR'] = $cell->getValue();
                }
                if('F' == $cell->getColumn()){
                    $datos[$rowIndex]['MANIOBRA'] = $cell->getValue();
                }
                if('G' == $cell->getColumn()){
                    $datos[$rowIndex]['TRANSPORTISTA'] = $cell->getValue();
                }
                // ---- Adiciionales Angeles.
                if('H' == $cell->getColumn()){
                    $datos[$rowIndex]['CLIENTE'] = $cell->getValue();
                }
                if('I' == $cell->getColumn()){
                    $datos[$rowIndex]['AGENTE ADUANAL'] = $cell->getValue();
                }
                if('J' == $cell->getColumn()){
                    $datos[$rowIndex]['BOOKING'] = $cell->getValue();
                }
                if('K' == $cell->getColumn()){
                    $datos[$rowIndex]['BUQUE'] = $cell->getValue();
                }
                if('L' == $cell->getColumn()){
                    $datos[$rowIndex]['VIAJE'] = $cell->getValue();
                }
                if('M' == $cell->getColumn()){
                    $datos[$rowIndex]['SELLO'] = $cell->getValue();
                }
            }

        }


        if( $validFile=="True" ) {
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $flgRec = 1;

            $l = 1;
            $f = date(Ymd);

            // ------------------------------------------------
            // Generar archivo EDI
            // ------------------------------------------------
            $fileEDI = "../edi_files/edi/".$sesIdUsuario."MSC_GATE_IN_MXGYM".$fecD1.$fecD2.".edi";
            $fileName = str_replace("../edi_files/edi/","",$fileEDI);
            $fp2 = fopen("$fileEDI", "w");

            $codPatio = getValueTable("cod_patio", "USUARIO", "id_usuario", $sesIdUsuario);
            $codPatioName = getValueTable("cod_patio_name", "USUARIO", "id_usuario", $sesIdUsuario);
            $senderID = getValueTable("sender_id", "USUARIO", "id_usuario", $sesIdUsuario);
            $receiverID = "MSC";
            // ENCABEZADO
            $enc = "UNB+UNOA:1+$senderID+$receiverID+$fecD1:$fecD2+$fecD1$fecD2'\r\n";
            $enc .= "UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'\r\n";
            $enc .= "BGM+34+$fecD1$fecD2+9'\r\n";
            $enc .= "LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'\r\n";
            //"TDT+20++1++MSC:172:166'\r\n".
            //"NAD+CA+MXVRCDECECI:172:166'\r\n";
            //$enc11=utf8_encode($enc);
            fputs($fp2, $enc);


            $tlConte = 0;
            $tlSegmentos = 4;

            // -------------------------------------------------
            // OBTENIENDO LA INFO
            // -------------------------------------------------
            $l=1;
            foreach ($datos as $dato => $x) {

                if( $l>5 ) {
                    $validFec=0;
                    $fecha = $x['FECHA'];
                    if( preg_match("/(\d{2})\/(\d{2})\/(\d{4})/i",$fecha,$parts) ){
                        $d= $parts[1];
                        $m= $parts[2];
                        $a= $parts[3];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }
                    elseif( preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha,$parts) ){
                        $d= $parts[3];
                        $m= $parts[2];
                        $a= $parts[1];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }


                    $hora = $x['HORA'];
                    $hora = strtolower($hora);
                    $hora = str_replace(" ","",$hora);
                    $hora = str_replace(":","",$hora);
                    $hora = str_replace("-","",$hora);
                    $hora = str_replace(".","",$hora);
                    $hora = str_replace("pm","",$hora);
                    $hora = str_replace("am","",$hora);
                    //$hora = str_pad($hora,4,"0", STR_PAD_LEFT);
                    $regHora = $hora;
                    // echo "$regHora <br>";

                    $conte = $x['CONTENEDOR'];
                    $equipo = $x['EQUIPO'];
                    $tipo = getEquipoTipo($equipo);
                    $eir = $x['EIR'];
                    if(!empty($eir)){
                        $eir="EIR: $eir ";
                    }
                    $maniobra = $x['MANIOBRA'];
                    $transportista = $x['TRANSPORTISTA'];

                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;

                        // ---------------------------------------------------------
                        // COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                        // ----------------------------------------------------------
                        ($impoExpo=="E")?$stIE=2:$stIE=3;
                        ($stConte=="E")?$stCo=4:$stCo=5;
                        $conte = strtoupper($conte);
                        $enc2= "EQD+CN+$conte+$tipo++$stIE+$stCo'\r\n";
                        $tlSegmentos++;
                        $enc2.="DTM+7:$regFecha$regHora:203'\r\n";
                        $tlSegmentos++;
                        $enc2.="FTX+AAI+++$transportista|$eir'\r\n";
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'\r\n";
                        $tlSegmentos++;
                        fputs($fp2,$enc2);
                    }

                }
                $l++;
            }

            // Pie de pagina
            // Total de contenedores
            $pie="CNT+16:$tlConte'\r\n";
            $tlSegmentos++;
            $pie.="UNT+$tlSegmentos+$fecD1$fecD2'\r\n";
            $pie.="UNZ+1+$fecD1$fecD2'";

            //$enc33=utf8_encode($enc3);
            fputs($fp2,$pie);
            fclose($fp2);

            unset($rowIterator);
            unset($datos);
            
            if( $opcPro=="email" ){
                sendNotifyOneFile($sesIdUsuario,'MSC',$fileEDI,$fileName);
            }
            elseif( $opcPro=="ftp" ){
                sentToFTP($fileEDI,$idNav);
                unlink($fileEDI);
            }

        }


    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateInMscExcelGym($stConte,$sesIdUsuario,$formato,$idNav,$opcPro,$codPatio){

        if( $formato=="excel2007" ) {
            // EXCEL 2007
            // ---------------------------------------------------
            // PROCESO COPIAR ARCHIVO
            // ---------------------------------------------------
            if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/entradas$sesIdUsuario.xlsx");
            } else {
                echo "Error de archivo!.";
            }
            // -------------------------------------
            // GET-IN
            // -------------------------------------
            unset($fp);
            unset($data);
            unset($dataX);
            // ---------------------------------
            // ---- LEER ARCHIVO DE EXCEL ---
            // ---------------------------------
            $objReader = new PHPExcel_Reader_Excel2007();
            //$objReader = new PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader->load("../edi_files/csv/entradas$sesIdUsuario.xlsx");
            $objPHPExcel->setActiveSheetIndex(0);
        }
        elseif( $formato=="excel2005" ) {
            // EXCEL 2007
            // ---------------------------------------------------
            // PROCESO COPIAR ARCHIVO
            // ---------------------------------------------------
            if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/entradas$sesIdUsuario.xls");
            } else {
                echo "Error de archivo!.";
            }
            // -------------------------------------
            // GET-IN
            // -------------------------------------
            unset($fp);
            unset($data);
            unset($dataX);
            // ---------------------------------
            // ---- LEER ARCHIVO DE EXCEL ---
            // ---------------------------------
            $objReader = new PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader->load("../edi_files/csv/entradas$sesIdUsuario.xls");
            $objPHPExcel->setActiveSheetIndex(0);

        }

        $validFile="True";
        $l=1;

        // ------------------
        // Validacion campos
        // ------------------
        // ---------------------------------
        // Validar el encabezado
        // ----------------------------------
        $encC1= $objPHPExcel->getActiveSheet()->getCell("A5")->getValue();
        $encC2= $objPHPExcel->getActiveSheet()->getCell("B5")->getValue();
        $encC3= $objPHPExcel->getActiveSheet()->getCell("C5")->getValue();
        $encC4= $objPHPExcel->getActiveSheet()->getCell("D5")->getValue();
        $encC5= $objPHPExcel->getActiveSheet()->getCell("E5")->getValue();
        $encC6= $objPHPExcel->getActiveSheet()->getCell("F5")->getValue();
        $encC7= $objPHPExcel->getActiveSheet()->getCell("G5")->getValue();
        $encC8= $objPHPExcel->getActiveSheet()->getCell("H5")->getValue();
        $encC9= $objPHPExcel->getActiveSheet()->getCell("I5")->getValue();
        $encC10= $objPHPExcel->getActiveSheet()->getCell("J5")->getValue();
        $encC11= $objPHPExcel->getActiveSheet()->getCell("K5")->getValue();
        $encC12= $objPHPExcel->getActiveSheet()->getCell("L5")->getValue();
        $encC13= $objPHPExcel->getActiveSheet()->getCell("M5")->getValue();
        $encC14= $objPHPExcel->getActiveSheet()->getCell("N5")->getValue();

        if( $encC1!="FECHA"){$validFile=0;echo "[Error Encabezado] FECHA<br>";}
        if( $encC2!="HORA" ){$validFile=0;echo "[Error Encabezado] HORA<br>";}
        if( $encC3!="CONTENEDOR"){$validFile=0;echo "[Error Encabezado] CONTENEDOR<br>";}
        if( $encC4!="TIPO" ){$validFile=0;echo "[Error Encabezado] TIPO<br>";}
        if( $encC5!="EIR" ){$validFile=0;echo "[Error Encabezado] EIR<br>";}
        if( $encC6!="MANIOBRA" ){$validFile=0;echo "[Error Encabezado] MANIOBRA<br>";}
        if( $encC7!="TRANSPORTISTA" ){$validFile=0;echo "[Error Encabezado] TRANSPORTISTA<br>";}
        if( $encC8!="CLIENTE" ){$validFile=0;echo "[Error Encabezado] CLIENTE<br>";}
        if( $encC9!="AGENTE ADUANAL" ){$validFile=0;echo "[Error Encabezado] AGENTE ADUANAL<br>";}
        if( $encC10!="BOOKING" ){$validFile=0;echo "[Error Encabezado] BOOKING<br>";}
        if( $encC11!="BUQUE" ){$validFile=0;echo "[Error Encabezado] BUQUE<br>";}
        if( $encC12!="VIAJE" ){$validFile=0;echo "[Error Encabezado] VIAJE<br>";}
        if( $encC13!="SELLO" ){$validFile=0;echo "[Error Encabezado] SELLO<br>";}
        if( $encC14!="I_E" ){$validFile=0;echo "[Error Encabezado] I_E<br>";}
        // ---- Fin de la validación ----

        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $array_data = array();

        foreach($rowIterator as $row){
            $lin = $row->getRowIndex ();
            //echo "fila ".$row->getRowIndex ();
            // echo "<br>";
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            //if(1 == $row->getRowIndex ()) continue; //skip first row la del encabezado
            $rowIndex = $row->getRowIndex ();
            //$array_data[$rowIndex] = array('A'=>'', 'B'=>'','C'=>'','D'=>'','E'=>'');

            foreach ($cellIterator as $cell) {
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn() ){
                    $fecha = $cell->getFormattedValue();
                    // Validar Fecha : En caso de que los formatos sean 01-mes-15
                    unset($parts);
                    if( preg_match("/(\d{1,2})-(\w{3,3})-(\d{2,2})/i",$fecha,$parts) ){
                        // echo "FECHA:  $fecha OK <br>";
                        $dd = $parts[1];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $mes = $parts[2];
                        $aa = 2000 + $parts[3];
                        $mm = getOkMes($mes);
                        $fecha = $aa."-".$mm."-".$dd;
                    }

                    if( $lin>5 && !empty($fecha) && ( !preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha)  ) ){
                        echo "[Error] Lin: $lin | Fecha : $fecha | El formato es incorrecto, cambie el formato como -> aaaa-mm-dd  <br>";
                        $validFile=0;
                    }
                    $datos[$rowIndex]['FECHA'] = $fecha;
                }
                if('B' == $cell->getColumn()){
                    $hora = $cell->getFormattedValue();
                    unset($parts);
                    if( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) PM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        if( $hh<12 ){
                            $hh = $hh + 12;
                        }
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) AM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/^(\d{1,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    $datos[$rowIndex]['HORA'] = $hora;
                }
                if('C' == $cell->getColumn()){
                    $conte = $cell->getValue();
                    $conte = strtoupper($conte);
                    $datos[$rowIndex]['CONTENEDOR'] = $conte;
                }
                if('D' == $cell->getColumn()){
                    $datos[$rowIndex]['EQUIPO'] = $cell->getValue();
                }
                if('E' == $cell->getColumn()){
                    $datos[$rowIndex]['EIR'] = $cell->getValue();
                }
                if('F' == $cell->getColumn()){
                    $datos[$rowIndex]['MANIOBRA'] = $cell->getValue();
                }
                if('G' == $cell->getColumn()){
                    $datos[$rowIndex]['TRANSPORTISTA'] = $cell->getValue();
                }
                // ---- Adiciionales Angeles.
                if('H' == $cell->getColumn()){
                    $datos[$rowIndex]['CLIENTE'] = $cell->getValue();
                }
                if('I' == $cell->getColumn()){
                    $datos[$rowIndex]['AGENTE ADUANAL'] = $cell->getValue();
                }
                if('J' == $cell->getColumn()){
                    $datos[$rowIndex]['BOOKING'] = $cell->getValue();
                }
                if('K' == $cell->getColumn()){
                    $datos[$rowIndex]['BUQUE'] = $cell->getValue();
                }
                if('L' == $cell->getColumn()){
                    $datos[$rowIndex]['VIAJE'] = $cell->getValue();
                }
                if('M' == $cell->getColumn()){
                    $datos[$rowIndex]['SELLO'] = $cell->getValue();
                }
                if('N' == $cell->getColumn()){
                    $datos[$rowIndex]['I_E'] = $cell->getValue();
                    $impoExpo = $cell->getValue();
                    if( empty($impoExpo) && !empty($conte) ){
                        echo "[ERROR] Lin: $lin | Import o Export (I,E) No especificado.<br>";
                    }

                }
            }

        }


        if( $validFile=="True" ) {
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $flgRec = 1;

            $l = 1;
            $f = date(Ymd);

            // ------------------------------------------------
            // Generar archivo EDI
            // ------------------------------------------------
            $fileEDI = "../edi_files/edi/".$sesIdUsuario."MSC_GATE_IN_".$codPatio."_".$fecD1.$fecD2.".edi";
            $fileName = str_replace("../edi_files/edi/","",$fileEDI);
            $fp2 = fopen("$fileEDI", "w");

            //$codPatio = getValueTable("cod_patio", "USUARIO", "id_usuario", $sesIdUsuario);
            $codPatioName = $codPatio;
            $senderID = getValueTable("sender_id", "CODIGOS", "cod_patio", $codPatio);
            $receiverID = "MSC";
            // ENCABEZADO
            $enc = "UNB+UNOA:1+$senderID+$receiverID+$fecD1:$fecD2+$fecD1$fecD2'\r\n";
            $enc .= "UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'\r\n";
            $enc .= "BGM+34+$fecD1$fecD2+9'\r\n";
            //"TDT+20++1++MSC:172:166'\r\n".
            //"NAD+CA+MXVRCDECECI:172:166'\r\n";
            //$enc11=utf8_encode($enc);
            fputs($fp2, $enc);


            $tlConte = 0;
            $tlSegmentos = 4;

            // -------------------------------------------------
            // OBTENIENDO LA INFO
            // -------------------------------------------------
            $l=1;
            foreach ($datos as $dato => $x) {

                if( $l>5 ) {
                    $validFec=0;

                    $fecha = $x['FECHA'];
                    if( preg_match("/(\d{4})-(\d{2})-(\d{2})$/i",$fecha,$parts) ){
                        $d= $parts[3];
                        $m= $parts[2];
                        $a= $parts[1];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }

                    $hora = $x['HORA'];
                    $hora = strtolower($hora);
                    $hora = str_replace(" ","",$hora);
                    $hora = str_replace(":","",$hora);
                    $hora = str_replace("-","",$hora);
                    $hora = str_replace(".","",$hora);
                    $hora = str_replace("pm","",$hora);
                    $hora = str_replace("am","",$hora);
                    $hora = str_pad($hora,4,"0", STR_PAD_LEFT);
                    $regHora = $hora;

                    $conte = $x['CONTENEDOR'];
                    $equipo = $x['EQUIPO'];
                    $tipo = getEquipoTipo($equipo);
                    $eir = $x['EIR'];
                    if(!empty($eir)){
                        $eir="EIR: $eir ";
                    }
                    $maniobra = $x['MANIOBRA'];
                    $transportista = $x['TRANSPORTISTA'];
                    $cliente = $x['CLIENTE'];
                    $cliente = strtoupper($cliente);
                    $bkgNumber = $x['BOOKING'];
                    $bl = $x['BL'];
                    $bkgNumber = strtoupper($bkgNumber);
                    $sello = $x[SELLO];
                    $impoExpo = $x[I_E];

                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;

                        // ---------------------------------------------------------
                        // COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                        // ----------------------------------------------------------
                        ($impoExpo=="E")?$stIE=2:$stIE=3;
                        ($stConte=="E")?$stCo=4:$stCo=5;
                        $conte = strtoupper($conte);
                        $enc2= "EQD+CN+$conte+$tipo++$stIE+$stCo'\r\n";
                        $tlSegmentos++;
                        $enc2.="DTM+7:$regFecha$regHora:203'\r\n";
                        $tlSegmentos++;
                        $enc2.= "LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'\r\n";
                        $tlSegmentos++;
                        $enc2.="SEL+$sello+CA'\r\n";
                        $tlSegmentos++;
                        $enc2.="FTX+AAI+++$transportista|$eir'\r\n";
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'\r\n";
                        $tlSegmentos++;
                        fputs($fp2,$enc2);
                        $flgOkFile=1;
                    }

                }
                $l++;
            }
            // Pie de pagina
            // Total de contenedores
            $pie="CNT+16:$tlConte'\r\n";
            $tlSegmentos++;
            $pie.="UNT+$tlSegmentos+$fecD1$fecD2'\r\n";
            $pie.="UNZ+1+$fecD1$fecD2'";

            //$enc33=utf8_encode($enc3);
            fputs($fp2,$pie);
            fclose($fp2);

            unset($rowIterator);
            unset($datos);

            
            if($flgOkFile==1) {
                if ($opcPro == "email") {
                    sendNotifyOneFile($sesIdUsuario, 'MSC', $fileEDI, $fileName);
                } elseif ($opcPro == "ftp") {
                    //echo "$fileEDI OK <br>";
                    // -----------------------------
                    // FTP - GETIN
                    // -----------------------------
                    $ftp_server = "187.174.238.23";
                    $ftp_user_name = "estefi";
                    $ftp_user_pass = "estefi2015";
                    $source_file = $fileEDI;
                    $destination_file = str_replace("../edi_files/edi/", "", $source_file);
                    $destination_file = "/MEXICO/GUAYMAS/".$destination_file;
                    // --
                    // --
                    $conn_id = ftp_connect($ftp_server);
                    $login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
                    // verificar la conexión
                    if ((!$conn_id) || (!$login_result)) {
                        echo "<font color=red>¡La conexión FTP ha fallado ponerse en contacto con Lic.Nestor Perez Tel:  / 5547262625 </font><br>";
                        echo "<font color=red>Se intentó conectar al $ftp_server</font><br>";
                        exit;
                    } else {
                        echo "<font color=blue>Conexión a $ftp_server realizada con éxito.</font><br>";
                    }
                    // subir un archivo
                    $upload = ftp_put($conn_id, $destination_file, $source_file, FTP_BINARY);
                    // comprobar el estado de la subida
                    if (!$upload) {
                        echo "<font color=red>La subida FTP ha fallado! </font><br>";
                    } else {
                        echo "<font color=blue>El Envio a FTP $ftp_server ha sido exitoso!. Archivo : $destination_file </font><br>";
                    }
                    ftp_close($conn_id);
                    unlink($fileEDI);
                }
            }
        }


    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateOutMscExcelGym($stConte,$sesIdUsuario,$formato,$idNav,$opcPro,$codPatio){

        if( $formato=="excel2007" ) {
            // EXCEL 2007 o mayor
            // ---------------------------------------------------
            // PROCESO COPIAR ARCHIVO
            // ---------------------------------------------------
            if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/salidas$sesIdUsuario.xlsx");
            } else {
                echo "Error de archivo!.";
            }
            // -------------------------------------
            // GET-IN
            // -------------------------------------
            unset($fp);
            unset($data);
            unset($dataX);
            // ---------------------------------
            // ---- LEER ARCHIVO DE EXCEL ---
            // ---------------------------------
            $objReader = new PHPExcel_Reader_Excel2007();
            //$objReader = new PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader->load("../edi_files/csv/salidas$sesIdUsuario.xlsx");
            $objPHPExcel->setActiveSheetIndex(1);
        }
        elseif( $formato=="excel2005" ) {
            // EXCEL 2007 o mayor
            // ---------------------------------------------------
            // PROCESO COPIAR ARCHIVO
            // ---------------------------------------------------
            if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/salidas$sesIdUsuario.xls");
            } else {
                echo "Error de archivo!.";
            }
            // -------------------------------------
            // GET-IN
            // -------------------------------------
            unset($fp);
            unset($data);
            unset($dataX);
            // ---------------------------------
            // ---- LEER ARCHIVO DE EXCEL ---
            // ---------------------------------
            $objReader = new PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader->load("../edi_files/csv/salidas$sesIdUsuario.xls");
            $objPHPExcel->setActiveSheetIndex(1);
        }

        $validFile="True";
        $l=1;

        // ---------------------------------
        // Validar el encabezado
        // ----------------------------------
        $encC1= $objPHPExcel->getActiveSheet()->getCell("A5")->getValue();
        $encC2= $objPHPExcel->getActiveSheet()->getCell("B5")->getValue();
        $encC3= $objPHPExcel->getActiveSheet()->getCell("C5")->getValue();
        $encC4= $objPHPExcel->getActiveSheet()->getCell("D5")->getValue();
        $encC5= $objPHPExcel->getActiveSheet()->getCell("E5")->getValue();
        $encC6= $objPHPExcel->getActiveSheet()->getCell("F5")->getValue();
        $encC7= $objPHPExcel->getActiveSheet()->getCell("G5")->getValue();
        $encC8= $objPHPExcel->getActiveSheet()->getCell("H5")->getValue();
        $encC9= $objPHPExcel->getActiveSheet()->getCell("I5")->getValue();
        $encC10= $objPHPExcel->getActiveSheet()->getCell("J5")->getValue();
        $encC11= $objPHPExcel->getActiveSheet()->getCell("K5")->getValue();
        $encC12= $objPHPExcel->getActiveSheet()->getCell("L5")->getValue();
        $encC13= $objPHPExcel->getActiveSheet()->getCell("M5")->getValue();
        $encC14= $objPHPExcel->getActiveSheet()->getCell("N5")->getValue();
        $encC15= $objPHPExcel->getActiveSheet()->getCell("O5")->getValue();

        if( $encC1!="FECHA"){$validFile=0;echo "[Error Encabezado] FECHA<br>";}
        if( $encC2!="HORA" ){$validFile=0;echo "[Error Encabezado] HORA<br>";}
        if( $encC3!="CONTENEDOR" ){$validFile=0;echo "[Error Encabezado] CONTENEDOR<br>";}
        if( $encC4!="TIPO" ){$validFile=0;echo "[Error Encabezado] TIPO<br>";}
        if( $encC5!="EIR" ){$validFile=0;echo "[Error Encabezado] EIR<br>";}
        if( $encC6!="MANIOBRA" ){$validFile=0;echo "[Error Encabezado] MANIOBRA<br>";}
        if( $encC7!="TRANSPORTISTA" ){$validFile=0;echo "[Error Encabezado] TRANSPORTISTA<br>";}
        if( $encC8!="CLIENTE" ){$validFile=0;echo "[Error Encabezado] CLIENTE<br>";}
        if( $encC9!="AGENTE ADUANAL" ){$validFile=0;echo "[Error Encabezado] AGENTE ADUANAL<br>";}
        if( $encC10!="BL" ){$validFile=0;echo "[Error Encabezado] BL<br>";}
        if( $encC11!="BOOKING" ){$validFile=0;echo "[Error Encabezado] BOOKING<br>";}
        if( $encC12!="BUQUE" ){$validFile=0;echo "[Error Encabezado] BUQUE<br>";}
        if( $encC13!="VIAJE" ){$validFile=0;echo "[Error Encabezado] VIAJE<br>";}
        if( $encC14!="SELLO" ){$validFile=0;echo "[Error Encabezado] SELLO<br>";}
        if( $encC15!="I_E" ){$validFile=0;echo "[Error Encabezado] I_E<br>";}
        // ---- Fin de la validación ----

        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $array_data = array();
        foreach($rowIterator as $row){
            $lin = $row->getRowIndex ();
            //echo "fila ".$row->getRowIndex ();
            //echo "<br>";
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            //if(1 == $row->getRowIndex ()) continue; //skip first row la del encabezado
            $rowIndex = $row->getRowIndex ();
            //$array_data[$rowIndex] = array('A'=>'', 'B'=>'','C'=>'','D'=>'','E'=>'');

            foreach ($cellIterator as $cell) {
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn() ){
                    $fecha = $cell->getFormattedValue();
                    // Validar Fecha : En caso de que los formatos sean 01-mes-15
                    unset($parts);
                    if( preg_match("/(\d{1,2})-(\w{3,3})-(\d{2,2})/i",$fecha,$parts) ){
                        // echo "FECHA:  $fecha OK <br>";
                        $dd = $parts[1];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $mes = $parts[2];
                        $aa = 2000 + $parts[3];
                        $mm = getOkMes($mes);
                        $fecha = $aa."-".$mm."-".$dd;
                    }

                    if( $lin>5 && !empty($fecha) && ( !preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha)  ) ){
                        echo "[Error] Lin: $lin | Fecha : $fecha | El formato es incorrecto, cambie el formato como -> aaaa-mm-dd  <br>";
                        $validFile=0;
                    }
                    $datos[$rowIndex]['FECHA'] = $fecha;
                }
                if('B' == $cell->getColumn()){
                    $hora = $cell->getFormattedValue();
                    unset($parts);
                    if( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) PM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        if( $hh<12 ){
                            $hh = $hh + 12;
                        }
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) AM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/^(\d{1,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    $datos[$rowIndex]['HORA'] = $hora;
                }
                if('C' == $cell->getColumn()){
                    $conte = $cell->getValue();
                    $conte = strtoupper($conte);
                    $datos[$rowIndex]['CONTENEDOR'] = $conte;
                }
                if('D' == $cell->getColumn()){
                    $datos[$rowIndex]['EQUIPO'] = $cell->getValue();
                }
                if('E' == $cell->getColumn()){
                    $datos[$rowIndex]['EIR'] = $cell->getValue();
                }
                if('F' == $cell->getColumn()){
                    $datos[$rowIndex]['MANIOBRA'] = $cell->getValue();
                }
                if('G' == $cell->getColumn()){
                    $datos[$rowIndex]['TRANSPORTISTA'] = $cell->getValue();
                }
                if('H' == $cell->getColumn()){
                    $datos[$rowIndex]['CLIENTE'] = $cell->getValue();
                }
                if('I' == $cell->getColumn()){
                    $datos[$rowIndex]['AGENTE ADUANAL'] = $cell->getValue();
                }
                if('J' == $cell->getColumn()){
                    $datos[$rowIndex]['BL'] = $cell->getValue();
                }
                if('K' == $cell->getColumn()){
                    $datos[$rowIndex]['BOOKING'] = $cell->getValue();
                }
                if('L' == $cell->getColumn()){
                    $datos[$rowIndex]['BUQUE'] = $cell->getValue();
                }
                if('M' == $cell->getColumn()){
                    $datos[$rowIndex]['VIAJE'] = $cell->getValue();
                }
                if('N' == $cell->getColumn()){
                    $datos[$rowIndex]['SELLO'] = $cell->getValue();
                }
                if('O' == $cell->getColumn()){
                    $datos[$rowIndex]['I_E'] = $cell->getValue();
                    $impoExpo = $cell->getValue();
                    if( empty($impoExpo) && !empty($conte) ){
                        echo "[ERROR] Lin: $lin | Import o Export (I,E) No especificado.<br>";
                    }
                }
            }
        }


        if( $validFile=="True" && is_array($datos) ) {
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $flgRec = 1;
            $l = 1;
            $f = date(Ymd);

            // ------------------------------------------------
            // Generar archivo EDI
            // ------------------------------------------------
            $fileEDI = "../edi_files/edi/".$sesIdUsuario."MSC_GATE_OUT_".$codPatio."_".$fecD1.$fecD2.".edi";
            $fileName = str_replace("../edi_files/edi/","",$fileEDI);
            $fp2 = fopen("$fileEDI", "w");

            // $codPatio = getValueTable("cod_patio", "USUARIO", "id_usuario", $sesIdUsuario);
            $codPatioName = $codPatio;
            $senderID = getValueTable("sender_id", "CODIGOS", "cod_patio", $codPatio);
            $receiverID = "MSC";

            // ENCABEZADO
            $enc= "UNB+UNOA:1+$senderID+$receiverID+$fecD1:$fecD2+$fecD1$fecD2'\r\n";
            $enc.= "UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'\r\n";
            $enc.= "BGM+36+$fecD1$fecD2+9'\r\n";
            //"TDT+20++1++MSC:172:166'".$sepa.
            //"NAD+CA+MXVRCDECECI:172:166'".$sepa;
            //$enc11=utf8_encode($enc);
            fputs($fp2, $enc);

            $tlConte = 0;
            $tlSegmentos = 4;
            // -------------------------------------------------
            // OBTENIENDO LA INFO
            // -------------------------------------------------
            $l=1;
            foreach ($datos as $dato => $x) {
                if( $l>5 ) {
                    $validFec=0;
                    $fecha = $x['FECHA'];
                    if( preg_match("/(\d{4})-(\d{2})-(\d{2})$/i",$fecha,$parts) ){
                        $a= $parts[1];
                        $m= $parts[2];
                        $d= $parts[3];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }
                    $hora = $x['HORA'];
                    $hora = strtolower($hora);
                    $hora = str_replace(" ","",$hora);
                    $hora = str_replace(":","",$hora);
                    $hora = str_replace("-","",$hora);
                    $hora = str_replace(".","",$hora);
                    $hora = str_replace("pm","",$hora);
                    $hora = str_replace("am","",$hora);
                    $hora = str_pad($hora,4,"0", STR_PAD_LEFT);
                    $regHora = $hora;

                    $conte = $x['CONTENEDOR'];
                    $equipo = $x['EQUIPO'];
                    $tipo = getEquipoTipo($equipo);
                    // $eir = $x['EIR'];
                    $maniobra = $x['MANIOBRA'];
                    $transportista = $x['TRANSPORTISTA'];
                    $cliente = $x['CLIENTE'];
                    $cliente = strtoupper($cliente);
                    $bkgNumber = $x['BOOKING'];
                    $bl = $x['BL'];
                    $bkgNumber = strtoupper($bkgNumber);
                    $sello = $x[SELLO];
                    $impoExpo = $x[I_E];

                    /*if( preg_match("/^([0-9]+).*",$bkgNumber,$parts) ){
                        $codBkg = $parts[1];
                        switch($codBkg){
                            case "192": $codOD = "LMR00";break;
                            case "364": $codOD = "ZLO00";break;
                            case "191": $codOD = "VER00";break;
                            case "404": $codOD = "MZT00";break;
                            case "697": $codOD = "GYM00";break;
                            case "620": $codOD = "LZC00";break;
                            case "622": $codOD = "PUM00";break;
                        }
                    }
                    */

                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;

                        // ---------------------------------------------------------
                        // COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                        // ----------------------------------------------------------
                        ($impoExpo=="E")?$stIE=2:$stIE=3;
                        ($stConte=="E")?$stCo=4:$stCo=5;
                        $conte = strtoupper($conte);
                        $enc2= "EQD+CN+$conte+$tipo++$stIE+$stCo'\r\n";
                        $tlSegmentos++;
                        if( !empty($bl) ){
                            $enc2.= "RFF+BM:$bl'\r\n";
                            $tlSegmentos++;
                        }
                        if( !empty($bkgNumber) ) {
                            $enc2.= "RFF+BN:$bkgNumber'\r\n";
                            $tlSegmentos++;
                        }
                        $enc2.="DTM+7:$regFecha$regHora:203'\r\n";
                        $tlSegmentos++;
                        $enc2.= "LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'\r\n";
                        $tlSegmentos++;
                        $enc2.="SEL+$sello+CA'\r\n";
                        $tlSegmentos++;
                        $enc2.="FTX+AAI+++$bkgNumber|$cliente|$transportista'\r\n";
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'\r\n";
                        $tlSegmentos++;
                        // Cuando sean salidas es necesario agregar esta línea para cada movimiento,
                        // ya que con este se especifica el Destination Depot.
                        //$enc2.="LOC+99+GYM00'\r\n";   // 99 : Place of empty equipment return
                        $enc2.="LOC+99+$codOD'\r\n";   // 99 : Place of empty equipment return
                        $tlSegmentos++;
                        fputs($fp2,$enc2);
                        $flgOkFile=1;
                    }

                }
                $l++;
            }

            // Pie de pagina
            // Total de contenedores
            $pie="CNT+16:$tlConte'\r\n";
            $tlSegmentos++;
            $pie.="UNT+$tlSegmentos+$fecD1$fecD2'\r\n";
            $pie.="UNZ+1+$fecD1$fecD2'";

            fputs($fp2,$pie);
            fclose($fp2);

            unset($rowIterator);
            unset($datos);

            
            if( $flgOkFile==1 ) {
                if ($opcPro == "email") {
                    sendNotifyOneFile($sesIdUsuario, 'MSC', $fileEDI, $fileName);
                } elseif ($opcPro == "ftp") {
                    // -----------------------------
                    // FTP - GETIN
                    // -----------------------------
                    $ftp_server = "187.174.238.23";
                    $ftp_user_name = "estefi";
                    $ftp_user_pass = "estefi2015";
                    $source_file = $fileEDI;
                    $destination_file = str_replace("../edi_files/edi/", "", $source_file);
                    $destination_file = "/MEXICO/GUAYMAS/".$destination_file;
                    // --
                    // --
                    $conn_id = ftp_connect($ftp_server);
                    $login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
                    // verificar la conexión
                    if ((!$conn_id) || (!$login_result)) {
                        echo "<font color=red>¡La conexión FTP ha fallado ponerse en contacto con Lic.Nestor Perez Tel:  / 5547262625 </font><br>";
                        echo "<font color=red>Se intentó conectar al $ftp_server</font><br>";
                        exit;
                    } else {
                        echo "<font color=blue>Conexión a $ftp_server realizada con éxito.</font><br>";
                    }
                    // subir un archivo
                    $upload = ftp_put($conn_id, $destination_file, $source_file, FTP_BINARY);
                    // comprobar el estado de la subida
                    if (!$upload) {
                        echo "<font color=red>La subida FTP ha fallado! </font><br>";
                    } else {
                        echo "<font color=blue>El Envio a FTP $ftp_server ha sido exitoso!. Archivo : $destination_file </font><br>";
                    }
                    ftp_close($conn_id);
                    unlink($fileEDI);
                }
            }
        }
    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGatePosMscExcelGym($stConte,$sesIdUsuario,$formato,$idNav,$opcPro,$codPatio){

        // POSICIONAMIENTOS

        if( $formato=="excel2007" ) {
            // EXCEL 2007 o mayor
            // ---------------------------------------------------
            // PROCESO COPIAR ARCHIVO
            // ---------------------------------------------------
            if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/pos$sesIdUsuario.xlsx");
            } else {
                echo "Error de archivo!.";
            }
            // -------------------------------------
            // GET-IN
            // -------------------------------------
            unset($fp);
            unset($data);
            unset($dataX);
            // ---------------------------------
            // ---- LEER ARCHIVO DE EXCEL ---
            // ---------------------------------
            $objReader = new PHPExcel_Reader_Excel2007();
            //$objReader = new PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader->load("../edi_files/csv/pos$sesIdUsuario.xlsx");
            $objPHPExcel->setActiveSheetIndex(2);
        }
        elseif( $formato=="excel2005" ) {
            // EXCEL 2007 o mayor
            // ---------------------------------------------------
            // PROCESO COPIAR ARCHIVO
            // ---------------------------------------------------
            if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/pos$sesIdUsuario.xlsx");
            } else {
                echo "Error de archivo!.";
            }
            // -------------------------------------
            // GET-IN
            // -------------------------------------
            unset($fp);
            unset($data);
            unset($dataX);
            // ---------------------------------
            // ---- LEER ARCHIVO DE EXCEL ---
            // ---------------------------------
            $objReader = new PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader->load("../edi_files/csv/pos$sesIdUsuario.xls");
            $objPHPExcel->setActiveSheetIndex(2);
        }

        $validFile="True";
        $l=1;

        // ---------------------------------
        // Validar el encabezado
        // ----------------------------------
        $encC1= $objPHPExcel->getActiveSheet()->getCell("A5")->getValue();
        $encC2= $objPHPExcel->getActiveSheet()->getCell("B5")->getValue();
        $encC3= $objPHPExcel->getActiveSheet()->getCell("C5")->getValue();
        $encC4= $objPHPExcel->getActiveSheet()->getCell("D5")->getValue();
        $encC5= $objPHPExcel->getActiveSheet()->getCell("E5")->getValue();
        $encC6= $objPHPExcel->getActiveSheet()->getCell("F5")->getValue();
        $encC7= $objPHPExcel->getActiveSheet()->getCell("G5")->getValue();
        $encC8= $objPHPExcel->getActiveSheet()->getCell("H5")->getValue();
        $encC9= $objPHPExcel->getActiveSheet()->getCell("I5")->getValue();
        $encC10= $objPHPExcel->getActiveSheet()->getCell("J5")->getValue();
        $encC11= $objPHPExcel->getActiveSheet()->getCell("K5")->getValue();
        $encC12= $objPHPExcel->getActiveSheet()->getCell("L5")->getValue();
        $encC13= $objPHPExcel->getActiveSheet()->getCell("M5")->getValue();
        $encC14= $objPHPExcel->getActiveSheet()->getCell("N5")->getValue();
        $encC15= $objPHPExcel->getActiveSheet()->getCell("O5")->getValue();
        $encC16= $objPHPExcel->getActiveSheet()->getCell("P5")->getValue();

        if( $encC1!="FECHA"){$validFile=0;echo "[Error Encabezado] FECHA<br>";}
        if( $encC2!="HORA" ){$validFile=0;echo "[Error Encabezado] HORA<br>";}
        if( $encC3!="CONTENEDOR" ){$validFile=0;echo "[Error Encabezado] CONTENEDOR<br>";}
        if( $encC4!="TIPO" ){$validFile=0;echo "[Error Encabezado] TIPO<br>";}
        if( $encC5!="EIR" ){$validFile=0;echo "[Error Encabezado] EIR<br>";}
        if( $encC6!="MANIOBRA" ){$validFile=0;echo "[Error Encabezado] MANIOBRA<br>";}
        if( $encC7!="TRANSPORTISTA" ){$validFile=0;echo "[Error Encabezado] TRANSPORTISTA<br>";}
        if( $encC8!="CLIENTE" ){$validFile=0;echo "[Error Encabezado] CLIENTE<br>";}
        if( $encC9!="AGENTE ADUANAL" ){$validFile=0;echo "[Error Encabezado] AGENTE ADUANAL<br>";}
        if( $encC10!="BL" ){$validFile=0;echo "[Error Encabezado] BL<br>";}
        if( $encC11!="BOOKING" ){$validFile=0;echo "[Error Encabezado] BOOKING<br>";}
        if( $encC12!="BUQUE" ){$validFile=0;echo "[Error Encabezado] BUQUE<br>";}
        if( $encC13!="VIAJE" ){$validFile=0;echo "[Error Encabezado] VIAJE<br>";}
        if( $encC14!="SELLO" ){$validFile=0;echo "[Error Encabezado] SELLO<br>";}
        if( $encC15!="DESTINO" ){$validFile=0;echo "[Error Encabezado] SELLO<br>";}
        if( $encC16!="I_E" ){$validFile=0;echo "[Error Encabezado] I_E<br>";}
        // ---- Fin de la validación ----

        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $array_data = array();
        foreach($rowIterator as $row){
            $lin = $row->getRowIndex ();
            //echo "fila ".$row->getRowIndex ();
            //echo "<br>";
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            //if(1 == $row->getRowIndex ()) continue; //skip first row la del encabezado
            $rowIndex = $row->getRowIndex ();
            //$array_data[$rowIndex] = array('A'=>'', 'B'=>'','C'=>'','D'=>'','E'=>'');

            foreach ($cellIterator as $cell) {
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn() ){
                    $fecha = $cell->getFormattedValue();
                    // Validar Fecha : En caso de que los formatos sean 01-mes-15
                    unset($parts);
                    if( preg_match("/(\d{1,2})-(\w{3,3})-(\d{2,2})/i",$fecha,$parts) ){
                        // echo "FECHA:  $fecha OK <br>";
                        $dd = $parts[1];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $mes = $parts[2];
                        $aa = 2000 + $parts[3];
                        $mm = getOkMes($mes);
                        $fecha = $aa."-".$mm."-".$dd;
                    }

                    if( $lin>5 && !empty($fecha) && ( !preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha)  ) ){
                        echo "[Error] Lin: $lin | Fecha : $fecha | El formato es incorrecto, cambie el formato como -> aaaa-mm-dd  <br>";
                        $validFile=0;
                    }
                    $datos[$rowIndex]['FECHA'] = $fecha;
                }
                if('B' == $cell->getColumn()){
                    $hora = $cell->getFormattedValue();
                    unset($parts);
                    if( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) PM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        if( $hh<12 ){
                            $hh = $hh + 12;
                        }
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) AM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/^(\d{1,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    $datos[$rowIndex]['HORA'] = $hora;
                }
                if('C' == $cell->getColumn()){
                    $conte = $cell->getValue();
                    $conte = strtoupper($conte);
                    $datos[$rowIndex]['CONTENEDOR'] = $conte;
                }
                if('D' == $cell->getColumn()){
                    $datos[$rowIndex]['EQUIPO'] = $cell->getValue();
                }
                if('E' == $cell->getColumn()){
                    $datos[$rowIndex]['EIR'] = $cell->getValue();
                }
                if('F' == $cell->getColumn()){
                    $datos[$rowIndex]['MANIOBRA'] = $cell->getValue();
                }
                if('G' == $cell->getColumn()){
                    $datos[$rowIndex]['TRANSPORTISTA'] = $cell->getValue();
                }
                if('H' == $cell->getColumn()){
                    $datos[$rowIndex]['CLIENTE'] = $cell->getValue();
                }
                if('I' == $cell->getColumn()){
                    $datos[$rowIndex]['AGENTE ADUANAL'] = $cell->getValue();
                }
                if('J' == $cell->getColumn()){
                    $datos[$rowIndex]['BL'] = $cell->getValue();
                }
                if('K' == $cell->getColumn()){
                    $datos[$rowIndex]['BOOKING'] = $cell->getValue();
                }
                if('L' == $cell->getColumn()){
                    $datos[$rowIndex]['BUQUE'] = $cell->getValue();
                }
                if('M' == $cell->getColumn()){
                    $datos[$rowIndex]['VIAJE'] = $cell->getValue();
                }
                if('N' == $cell->getColumn()){
                    $datos[$rowIndex]['SELLO'] = $cell->getValue();
                }
                if('O' == $cell->getColumn()){
                    $datos[$rowIndex]['DESTINO'] = $cell->getValue();
                }
                if('P' == $cell->getColumn()){
                    $datos[$rowIndex]['I_E'] = $cell->getValue();
                    $impoExpo = $cell->getValue();
                    if( empty($impoExpo) && !empty($conte) ){
                        echo "[ERROR] Lin: $lin | Import o Export (I,E) No especificado.<br>";
                    }
                }
            }
        }

        if( $validFile=="True" ) {
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $flgRec = 1;

            $l = 1;
            $f = date(Ymd);

            // ------------------------------------------------
            // Generar archivo EDI
            // ------------------------------------------------
            $fileEDI = "../edi_files/edi/".$sesIdUsuario."MSC_GATE_POS_".$codPatio."_".$fecD1.$fecD2.".edi";
            $fileName = str_replace("../edi_files/edi/","",$fileEDI);
            $fp2 = fopen("$fileEDI", "w");

            // $codPatio = getValueTable("cod_patio", "USUARIO", "id_usuario", $sesIdUsuario);
            $codPatioName = $codPatio;
            $senderID = getValueTable("sender_id", "CODIGOS", "cod_patio", $codPatio);
            $receiverID = "MSC";

            // ENCABEZADO
            $enc= "UNB+UNOA:1+$senderID+$receiverID+$fecD1:$fecD2+$fecD1$fecD2'\r\n";
            $enc.= "UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'\r\n";
            // BGM35 : Es para posicionamientos
            $enc.= "BGM+35+$fecD1$fecD2+9'\r\n";
            //"TDT+20++1++MSC:172:166'".$sepa.
            //"NAD+CA+MXVRCDECECI:172:166'".$sepa;
            //$enc11=utf8_encode($enc);
            fputs($fp2, $enc);

            $tlConte = 0;
            $tlSegmentos = 4;
            // -------------------------------------------------
            // OBTENIENDO LA INFO
            // -------------------------------------------------
            $l=1;
            foreach ($datos as $dato => $x) {

                if( $l>5 ) {
                    $validFec=0;

                    $fecha = $x['FECHA'];
                    if( preg_match("/(\d{4})-(\d{2})-(\d{2})$/i",$fecha,$parts) ){
                        $a= $parts[1];
                        $m= $parts[2];
                        $d= $parts[3];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }
                    $hora = $x['HORA'];
                    $hora = strtolower($hora);
                    $hora = str_replace(" ","",$hora);
                    $hora = str_replace(":","",$hora);
                    $hora = str_replace("-","",$hora);
                    $hora = str_replace(".","",$hora);
                    $hora = str_replace("pm","",$hora);
                    $hora = str_replace("am","",$hora);
                    $hora = str_pad($hora,4,"0", STR_PAD_LEFT);
                    $regHora = $hora;

                    $conte = $x['CONTENEDOR'];
                    $equipo = $x['EQUIPO'];
                    $tipo = getEquipoTipo($equipo);
                    // $eir = $x['EIR'];
                    $maniobra = $x['MANIOBRA'];
                    $transportista = $x['TRANSPORTISTA'];
                    $cliente = $x['CLIENTE'];
                    $cliente = strtoupper($cliente);
                    $bkgNumber = $x['BOOKING'];
                    $bl = $x['BL'];
                    $bkgNumber = strtoupper($bkgNumber);
                    $sello = $x[SELLO];
                    $destino = $x[DESTINO];
                    $impoExpo = $x[I_E];

                    if( preg_match("/^([0-9]+).*/",$bkgNumber,$parts) ){
                        $codBkg = $parts[1];
                        switch($codBkg){
                            case "192": $codOD = "LMR00";break;
                            case "364": $codOD = "ZLO00";break;
                            case "191": $codOD = "VER00";break;
                            case "404": $codOD = "MZT00";break;
                            case "697": $codOD = "GYM00";break;
                            case "620": $codOD = "LZC00";break;
                            case "622": $codOD = "PUM00";break;
                        }
                    }

                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;

                        // ---------------------------------------------------------
                        // COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                        // ----------------------------------------------------------
                        ( $impoExpo=="E" )?$stIE=2:$stIE=3;
                        ( $stConte=="E" )?$stCo=4:$stCo=5;
                        $conte = strtoupper($conte);
                        $enc2= "EQD+CN+$conte+$tipo++$stIE+$stCo'\r\n";
                        $tlSegmentos++;
                        if( !empty($bl) ){
                            $enc2.= "RFF+BM:$bl'\r\n";
                            $tlSegmentos++;
                        }
                        if( !empty($bkgNumber) ) {
                            $enc2.= "RFF+BN:$bkgNumber'\r\n";
                            $tlSegmentos++;
                        }
                        $enc2.="DTM+7:$regFecha$regHora:203'\r\n";
                        $tlSegmentos++;
                        $enc2.= "LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'\r\n";
                        $tlSegmentos++;
                        $enc2.="SEL+$sello+CA'\r\n";
                        $tlSegmentos++;
                        $enc2.="FTX+AAI+++$bkgNumber|$cliente|$transportista'\r\n";
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'\r\n";
                        $tlSegmentos++;
                        // Cuando sean salidas es necesario agregar esta línea para cada movimiento,
                        // ya que con este se especifica el Destination Depot.
                        //$enc2.="LOC+99+GYM00'\r\n";   // 99 : Place of empty equipment return
                        $enc2.="LOC+99+$destino'\r\n";   // 99 : Place of empty equipment return
                        $tlSegmentos++;
                        fputs($fp2,$enc2);
                        $flgOkFile=1;
                    }

                }
                $l++;
            }

            // Pie de pagina
            // Total de contenedores
            $pie="CNT+16:$tlConte'\r\n";
            $tlSegmentos++;
            $pie.="UNT+$tlSegmentos+$fecD1$fecD2'\r\n";
            $pie.="UNZ+1+$fecD1$fecD2'";

            fputs($fp2,$pie);
            fclose($fp2);

            unset($rowIterator);
            unset($datos);

            
            if( $flgOkFile==1 ) {
                if ($opcPro == "email") {
                    sendNotifyOneFile($sesIdUsuario, 'MSC', $fileEDI, $fileName);
                } elseif ($opcPro == "ftp") {
                    // -----------------------------
                    // FTP - GETIN
                    // -----------------------------
                    $ftp_server = "187.174.238.23";
                    $ftp_user_name = "estefi";
                    $ftp_user_pass = "estefi2015";
                    $source_file = $fileEDI;
                    $destination_file = str_replace("../edi_files/edi/", "", $source_file);
                    $destination_file = "/MEXICO/GUAYMAS/".$destination_file;
                    
                    // --
                    // --
                    $conn_id = ftp_connect($ftp_server);
                    $login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
                    // verificar la conexión
                    if ((!$conn_id) || (!$login_result)) {
                        echo "<font color=red>¡La conexión FTP ha fallado ponerse en contacto con Lic.Nestor Perez Tel:  / 5547262625 </font><br>";
                        //echo "<font color=red>Se intentó conectar al $ftp_server por el usuario $ftp_user_name</font><br>";
                        exit;
                    } else {
                        echo "<font color=blue>Conexión a $ftp_server realizada con éxito.</font><br>";
                    }
                    // subir un archivo
                    $upload = ftp_put($conn_id, $destination_file, $source_file, FTP_BINARY);
                    // comprobar el estado de la subida
                    if (!$upload) {
                        echo "<font color=red>La subida FTP ha fallado! </font><br>";
                    } else {
                        echo "<font color=blue>El Envio a FTP $ftp_server ha sido exitoso!. Archivo : $destination_file </font><br>";
                    }
                    ftp_close($conn_id);
                    unlink($fileEDI);
                }
            }


        }
    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateInMOLExcelTrane($stConte,$sesIdUsuario,$idNav,$opcPro){
        // MOL

        // EXCEL
        // ---------------------------------------------------
        // PROCESO COPIAR ARCHIVO
        // ---------------------------------------------------
        if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
            copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/entradasTrane.xlsx");
        }
        else {
            echo "Error de archivo!.";
        }

        // -------------------------------------
        // GET-IN
        // -------------------------------------
        unset($fp);
        unset($data);
        unset($dataX);


        // ---------------------------------
        // ---- LEER ARCHIVO DE EXCEL ---
        // ---------------------------------
        $objReader = new PHPExcel_Reader_Excel2007();
        //$objReader = new PHPExcel_Reader_Excel5();
        $objPHPExcel = $objReader->load('../edi_files/csv/entradasTrane.xlsx');
        $objPHPExcel->setActiveSheetIndex(0);


        $validFile="True";
        $l=1;

        // ------------------
        // Validacion campos
        // ------------------
        // ---------------------------------
        // Validar el encabezado
        // ----------------------------------
        $encC1= $objPHPExcel->getActiveSheet()->getCell("A9")->getValue();
        $encC2= $objPHPExcel->getActiveSheet()->getCell("B9")->getValue();
        $encC3= $objPHPExcel->getActiveSheet()->getCell("C9")->getValue();
        $encC4= $objPHPExcel->getActiveSheet()->getCell("D9")->getValue();
        $encC5= $objPHPExcel->getActiveSheet()->getCell("E9")->getValue();
        $encC6= $objPHPExcel->getActiveSheet()->getCell("F9")->getValue();
        $encC7= $objPHPExcel->getActiveSheet()->getCell("G9")->getValue();
        $encC8= $objPHPExcel->getActiveSheet()->getCell("H9")->getValue();
        $encC9= $objPHPExcel->getActiveSheet()->getCell("I9")->getValue();
        $encC10= $objPHPExcel->getActiveSheet()->getCell("J9")->getValue();

        if( $encC1!="Fecha de Ingreso"){$validFile=0;echo "[Error Encabezado] Fecha de Ingreso<br>";}
        if( $encC2!="Hora (24hrs)"){$validFile=0;echo "[Error Encabezado] Hora (24hrs)<br>";}
        if( $encC3!="Num. Contenedor" ){$validFile=0;echo "[Error Encabezado] Num. Contenedor<br>";}
        if( $encC5!="CALIDAD" ){$validFile=0;echo "[Error Encabezado] CALIDAD <br>";}
        if( $encC7!="Ag. Aduanal / Transportista" ){$validFile=0;echo "[Error Encabezado] Ag. Aduanal / Transportista <br>";}
        if( $encC8!="No.- EIR" ){$validFile=0;echo "[Error Encabezado] No.- EIR <br>";}
        if( $encC9!="Maniobra por cuenta de Merchant / carrier" ){$validFile=0;echo "[Error Encabezado] Maniobra por cuenta de Merchant / carrier<br>";}
        if( $encC10!="I_E" ){$validFile=0;echo "[Error Encabezado] I_E <br>";}
        // ---- Fin de la validación ----

        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $array_data = array();
        foreach($rowIterator as $row){
            $lin = $row->getRowIndex ();
            //echo "fila ".$row->getRowIndex ();
            //echo "<br>";
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            //if(1 == $row->getRowIndex ()) continue; //skip first row la del encabezado
            $rowIndex = $row->getRowIndex ();
            //$array_data[$rowIndex] = array('A'=>'', 'B'=>'','C'=>'','D'=>'','E'=>'');

            foreach ($cellIterator as $cell) {
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn() ){
                    $fecha = $cell->getFormattedValue();
                    $fecha = str_replace("/","-",$fecha);
                    // Validar Fecha : En caso de que los formatos sean 01-mes-15
                    unset($parts);
                    if( preg_match("/(\d{1,2})-(\w{3,3})-(\d{2,2})/i",$fecha,$parts) ){
                        // echo "FECHA:  $fecha OK <br>";
                        $dd = $parts[1];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $mes = $parts[2];
                        $aa = 2000 + $parts[3];
                        $mm = getOkMes($mes);
                        $fecha = $aa."-".$mm."-".$dd;
                    }

                    if( $lin>9 && !empty($fecha) && ( !preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha)  ) ){
                        echo "[Error] Lin: $lin | Fecha : $fecha | El formato es incorrecto, cambie el formato como -> aaaa-mm-dd  <br>";
                        $validFile=0;
                    }
                    $datos[$rowIndex]['FECHA'] = $fecha;
                }
                if('B' == $cell->getColumn()){
                    $hora = $cell->getFormattedValue();
                    unset($parts);
                    if( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) PM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        if( $hh<12 ){
                            $hh = $hh + 12;
                        }
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) AM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/^(\d{1,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }

                    $datos[$rowIndex]['HORA'] = $hora;
                }
                /*// Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn()){
                    $datos[$rowIndex]['FECHAHORA'] = $cell->getValue();
                    if( $datos[$rowIndex]['FECHAHORA']=="Fecha y hora Ingreso" ){
                        $rowStar=$rowIndex;
                    }
                }
                */
                if('C' == $cell->getColumn()){
                    $conte = $cell->getValue();
                    $conte = strtoupper($conte);
                    $datos[$rowIndex]['CONTENEDOR'] = $conte;
                }
                if('D' == $cell->getColumn()){
                    $datos[$rowIndex]['EQUIPO'] = $cell->getValue();
                }
                if('H' == $cell->getColumn()){
                    $datos[$rowIndex]['EIR'] = $cell->getValue();
                }
                if('I' == $cell->getColumn()){
                    $datos[$rowIndex]['MANIOBRA'] = $cell->getValue();
                }
                if('G' == $cell->getColumn()){
                    $datos[$rowIndex]['TRANSPORTISTA'] = $cell->getValue();
                }
                if('J' == $cell->getColumn()){
                    $datos[$rowIndex]['I_E'] = $cell->getValue();
                }
            }
        }

        if( $validFile=="True" ) {
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $flgRec = 1;

            $l = 1;
            $f = date(Ymd);

            // ------------------------------------------------
            // Generar archivo EDI
            // ------------------------------------------------
            $fileEDI = "../edi_files/edi/TRANE_MOL_GATE_IN".$fecD1.$fecD2.".edi";
            $fileName = "TRANE_MOL_GATE_IN".$fecD1.$fecD2.".edi";
            $fp2 = fopen("$fileEDI", "w");
            $sepa = "\r\n";
            $codPatio = getValueTable("cod_patio", "USUARIO", "id_usuario", $sesIdUsuario);
            $codPatioName = getValueTable("cod_patio_name", "USUARIO", "id_usuario", $sesIdUsuario);
            $senderID = "MXMTYTR";
            $receiverID = "MOL";
            // ENCABEZADO
            $enc = "UNB+UNOA:2+$senderID+$receiverID+$fecD1:$fecD2+$fecD1$fecD2'" . $sepa;
            $enc .= "UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'" . $sepa;
            $enc .= "BGM+34+$fecD1$fecD2+9'" . $sepa;
            $enc .= "LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'" . $sepa;
            fputs($fp2, $enc);


            $tlConte = 0;
            $tlSegmentos = 4;

            // -------------------------------------------------
            // OBTENIENDO LA INFO
            // -------------------------------------------------
            $l=1;
            foreach ($datos as $dato => $x) {

                if( $l>9 ) {
                    $validFec=0;
                    /*
                    $fechaHora = $x['FECHAHORA'];
                    if( preg_match("/(\d{2})\/(\d{2})\/(\d{4}) (\d{2}):(\d{2})/i",$fechaHora,$parts) ){
                        $d= $parts[1];
                        $m= $parts[2];
                        $a= $parts[3];
                        $hr = $parts[4];
                        $min = $parts[5];
                        $regFecha= $a.$m.$d;
                        $regHora=$hr.$min;
                        $validFec=1;
                    }
                    */
                    $fecha = $x['FECHA'];
                    if( preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha,$parts) ){
                        $d= $parts[3];
                        $m= $parts[2];
                        $a= $parts[1];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }

                    $hora = $x['HORA'];
                    $hora = strtolower($hora);
                    $hora = str_replace(" ","",$hora);
                    $hora = str_replace(":","",$hora);
                    $hora = str_replace("-","",$hora);
                    $hora = str_replace(".","",$hora);
                    $hora = str_replace("pm","",$hora);
                    $hora = str_replace("am","",$hora);
                    $hora = str_pad($hora,4,"0", STR_PAD_LEFT);
                    $regHora = $hora;

                    $conte = $x['CONTENEDOR'];
                    $equipo = $x['EQUIPO'];
                    $tipo = getEquipoTipo($equipo);
                    $eir = $x['EIR'];
                    if(!empty($eir)){
                        $eir="EIR: $eir ";
                    }
                    $maniobra = $x['MANIOBRA'];
                    $transportista = $x['TRANSPORTISTA'];
                    $impoExpo = $x[I_E];

                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;

                        // ---------------------------------------------------------
                        // COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                        // ----------------------------------------------------------
                        ($impoExpo=="E")?$stIE=2:$stIE=3;
                        ($stConte=="E")?$stCo=4:$stCo=5;
                        $conte = strtoupper($conte);
                        $enc2= "EQD+CN+$conte+$tipo++$stIE+$stCo'".$sepa;
                        $tlSegmentos++;
                        $enc2.="DTM+7:$regFecha$regHora:203'".$sepa;
                        $tlSegmentos++;
                        $enc2.="FTX+AAI+++$transportista|$eir'".$sepa;
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'".$sepa;
                        $tlSegmentos++;
                        fputs($fp2,$enc2);
                        $flgOkFile=1; // El archivo contiene info.
                    }

                }
                $l++;
            }

            // Pie de pagina
            // Total de contenedores
            $pie="CNT+16:$tlConte'".$sepa;
            $tlSegmentos++;
            $pie.="UNT+$tlSegmentos+$fecD1$fecD2'".$sepa;
            $pie.="UNZ+1+$fecD1$fecD2'";
            fputs($fp2,$pie);
            fclose($fp2);
            unset($rowIterator);
            unset($datos);

            if($flgOkFile==1) {
                if ($opcPro == "email") {
                    //sendNotifyOneFile($sesIdUsuario, 'MOL', $fileEDI, $fileName);
                }
                elseif ($opcPro == "ftp") {
                    sentToFTP($fileEDI,$idNav);
                }
                return $fileEDI;
            }


        }

    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateOutMOLExcelTrane($stConte,$sesIdUsuario,$idNav,$opcPro){

        // EXCEL
        // ---------------------------------------------------
        // PROCESO COPIAR ARCHIVO
        // ---------------------------------------------------
        if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
            copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/salidasTrane.xlsx");
        }
        else {
            echo "Error de archivo!.";
        }

        // -------------------------------------
        // GET-IN
        // -------------------------------------
        unset($fp);
        unset($data);
        unset($dataX);


        // ---------------------------------
        // ---- LEER ARCHIVO DE EXCEL ---
        // ---------------------------------
        $objReader = new PHPExcel_Reader_Excel2007();
        //$objReader = new PHPExcel_Reader_Excel5();
        $objPHPExcel = $objReader->load('../edi_files/csv/salidasTrane.xlsx');
        $objPHPExcel->setActiveSheetIndex(1);


        $validFile="True";
        $l=1;

        // ---------------------------------
        // Validar el encabezado
        // ----------------------------------
        $encC1= $objPHPExcel->getActiveSheet()->getCell("A9")->getValue();
        $encC2= $objPHPExcel->getActiveSheet()->getCell("B9")->getValue();
        $encC3= $objPHPExcel->getActiveSheet()->getCell("C9")->getValue();
        $encC4= $objPHPExcel->getActiveSheet()->getCell("D9")->getValue();
        $encC5= $objPHPExcel->getActiveSheet()->getCell("E9")->getValue();
        $encC6= $objPHPExcel->getActiveSheet()->getCell("F9")->getValue();
        $encC7= $objPHPExcel->getActiveSheet()->getCell("G9")->getValue();
        $encC8= $objPHPExcel->getActiveSheet()->getCell("H9")->getValue();
        $encC9= $objPHPExcel->getActiveSheet()->getCell("I9")->getValue();
        $encC10= $objPHPExcel->getActiveSheet()->getCell("J9")->getValue();
        $encC11= $objPHPExcel->getActiveSheet()->getCell("K9")->getValue();
        $encC12= $objPHPExcel->getActiveSheet()->getCell("L9")->getValue();

        if( $encC1!="Fecha de Ingreso"){$validFile=0;echo "[Error Encabezado] Fecha de Ingreso<br>";}
        if( $encC2!="Hora (24hrs)" ){$validFile=0;echo "[Error Encabezado] Hora (24hrs)<br>";}
        if( $encC3!="Num. Contenedor"){$validFile=0;echo "[Error Encabezado] Num. Contenedor<br>";}
        //if( $encC4!="TIPO/TAMAÑO" ){$validFile=0;echo "[Error Encabezado] TIPO/TAMAÑO<br>";}
        if( $encC5!="Booking" ){$validFile=0;echo "[Error Encabezado] Booking <br>";}
        if( $encC6!="Cliente" ){$validFile=0;echo "[Error Encabezado] Cliente <br>";}
        if( $encC7!="Transportista" ){$validFile=0;echo "[Error Encabezado] Transportista <br>";}
        if( $encC8!="CALIDAD" ){$validFile=0;echo "[Error Encabezado] CALIDAD<br>";}
        if( $encC9!="No.- EIR" ){$validFile=0;echo "[Error Encabezado] No.- EIR <br>";}
        if( $encC10!="Sello Salida" ){$validFile=0;echo "[Error Encabezado] Sello Salida <br>";}
        if( $encC11!="Maniobra por cuenta de Merchant  //  carrier" ){$validFile=0;echo "[Error Encabezado] Maniobra por cuenta de Merchant  //  carrier <br>";}
        if( $encC12!="I_E" ){$validFile=0;echo "[Error Encabezado] I_E <br>";}
        // ---- Fin de la validación ----

        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $array_data = array();
        foreach($rowIterator as $row){
            $lin = $row->getRowIndex ();
            //echo "fila ".$row->getRowIndex ();
            //echo "<br>";
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            //if(1 == $row->getRowIndex ()) continue; //skip first row la del encabezado
            $rowIndex = $row->getRowIndex ();
            //$array_data[$rowIndex] = array('A'=>'', 'B'=>'','C'=>'','D'=>'','E'=>'');

            foreach ($cellIterator as $cell) {
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn() ){
                    $fecha = $cell->getFormattedValue();
                    $fecha = str_replace("/","-",$fecha);
                    // Validar Fecha : En caso de que los formatos sean 01-mes-15
                    unset($parts);
                    if( preg_match("/(\d{1,2})-(\w{3,3})-(\d{2,2})/i",$fecha,$parts) ){
                        // echo "FECHA:  $fecha OK <br>";
                        $dd = $parts[1];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $mes = $parts[2];
                        $aa = 2000 + $parts[3];
                        $mm = getOkMes($mes);
                        $fecha = $aa."-".$mm."-".$dd;
                    }

                    if( $lin>9 && !empty($fecha) && ( !preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha)  ) ){
                        echo "[Error] Lin: $lin | Fecha : $fecha | El formato es incorrecto, cambie el formato como -> aaaa-mm-dd  <br>";
                        $validFile=0;
                    }
                    $datos[$rowIndex]['FECHA'] = $fecha;
                }
                if('B' == $cell->getColumn()){
                    $hora = $cell->getFormattedValue();
                    unset($parts);
                    if( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) PM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        if( $hh<12 ){
                            $hh = $hh + 12;
                        }
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) AM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/^(\d{1,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    $datos[$rowIndex]['HORA'] = $hora;
                }

                if('C' == $cell->getColumn()){
                    $conte = $cell->getValue();
                    $conte = strtoupper($conte);
                    $datos[$rowIndex]['CONTENEDOR'] = $conte;
                }
                if('D' == $cell->getColumn()){
                    $datos[$rowIndex]['EQUIPO'] = $cell->getValue();
                }
                if('E' == $cell->getColumn()){
                    $datos[$rowIndex]['BOOKING'] = $cell->getValue();
                }
                if('F' == $cell->getColumn()){
                    $datos[$rowIndex]['CLIENTE'] = $cell->getValue();
                }
                if('G' == $cell->getColumn()){
                    $datos[$rowIndex]['TRANSPORTISTA'] = $cell->getValue();
                }
                if('I' == $cell->getColumn()){
                    $datos[$rowIndex]['EIR'] = $cell->getValue();
                }
                if('J' == $cell->getColumn()){
                    $datos[$rowIndex]['SELLO'] = $cell->getValue();
                }
                if('K' == $cell->getColumn()){
                    $datos[$rowIndex]['MANIOBRA'] = $cell->getValue();
                }
                if('L' == $cell->getColumn()){
                    $datos[$rowIndex]['I_E'] = $cell->getValue();
                }

            }
        }

        if( $validFile=="True" ) {
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $flgRec = 1;

            $l = 1;
            $f = date(Ymd);

            // ------------------------------------------------
            // Generar archivo EDI
            // ------------------------------------------------
            $fileEDI = "../edi_files/edi/TRANE_MOL_GATE_OUT".$fecD1.$fecD2.".edi";
            $fileName = "TRANE_MOL_GATE_OUT".$fecD1.$fecD2.".edi";
            $fp2 = fopen("$fileEDI", "w");
            $sepa = "\r\n";
            $codPatio = getValueTable("cod_patio", "USUARIO", "id_usuario", $sesIdUsuario);
            $codPatioName = getValueTable("cod_patio_name", "USUARIO", "id_usuario", $sesIdUsuario);
            $senderID = "MXMTYTR";
            $receiverID = "MOL";
            // ENCABEZADO
            $enc = "UNB+UNOA:2+$senderID+$receiverID+$fecD1:$fecD2+$fecD1$fecD2'" . $sepa;
            $enc .= "UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'" . $sepa;
            $enc .= "BGM+36+$fecD1$fecD2+9'" . $sepa;
            $enc .= "LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'" . $sepa;
            fputs($fp2, $enc);


            $tlConte = 0;
            $tlSegmentos = 4;

            // -------------------------------------------------
            // OBTENIENDO LA INFO
            // -------------------------------------------------
            $l=1;
            foreach ($datos as $dato => $x) {

                if( $l>9 ) {
                    $validFec=0;

                    $fecha = $x['FECHA'];
                    if( preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha,$parts) ){
                        $d= $parts[3];
                        $m= $parts[2];
                        $a= $parts[1];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }

                    $hora = $x['HORA'];
                    $hora = strtolower($hora);
                    $hora = str_replace(" ","",$hora);
                    $hora = str_replace(":","",$hora);
                    $hora = str_replace("-","",$hora);
                    $hora = str_replace(".","",$hora);
                    $hora = str_replace("pm","",$hora);
                    $hora = str_replace("am","",$hora);
                    $hora = str_pad($hora,4,"0", STR_PAD_LEFT);
                    $regHora = $hora;

                    $conte = $x['CONTENEDOR'];
                    $equipo = $x['EQUIPO'];
                    $tipo = getEquipoTipo($equipo);
                    $eir = $x['EIR'];
                    $maniobra = $x['MANIOBRA'];
                    $transportista = $x['TRANSPORTISTA'];
                    $cliente = $x['CLIENTE'];
                    $cliente = strtoupper($cliente);
                    $bkgNumber = $x['BOOKING'];
                    $bkgNumber = strtoupper($bkgNumber);
                    $bl = $x['BL'];
                    $bkgNumber = strtoupper($bkgNumber);
                    $sello = $x[SELLO];
                    $impoExpo = $x[I_E];


                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;

                        // ---------------------------------------------------------
                        // COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                        // ----------------------------------------------------------
                        ($impoExpo=="E")?$stIE=2:$stIE=3;
                        ($stConte=="E")?$stCo=4:$stCo=5;
                        $conte = strtoupper($conte);
                        $enc2= "EQD+CN+$conte+$tipo++$stIE+$stCo'".$sepa;
                        $tlSegmentos++;
                        if( !empty($bl) ){
                            $enc2.= "RFF+BM:$bl'\r\n";
                            $tlSegmentos++;
                        }
                        if( !empty($bkgNumber) ) {
                            $enc2.= "RFF+BN:$bkgNumber'\r\n";
                            $tlSegmentos++;
                        }
                        $enc2.="DTM+7:$regFecha$regHora:203'".$sepa;
                        $tlSegmentos++;
                        $enc2.="FTX+AAI+++$bkgNumber|$cliente|$transportista'".$sepa;
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'".$sepa;  // 3 : Equivale a Camion y 1. Es Tren.
                        $tlSegmentos++;
                        // Cuando sean salidas es necesario agregar esta línea para cada movimiento,
                        // ya que con este se especifica el Destination Depot.
                        $enc2.="LOC+99+'".$sepa;   // 99 : Place of empty equipment return
                        $tlSegmentos++;

                        /*$enc2.= "LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'\r\n";
                        $tlSegmentos++;
                        $enc2.="SEL+$sello+CA'\r\n";
                        $tlSegmentos++;
                        $enc2.="FTX+AAI+++$bkgNumber/$cliente/$transportista'".$sepa;
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'".$sepa;
                        $tlSegmentos++;
                        // Cuando sean salidas es necesario agregar esta línea para cada movimiento,
                        // ya que con este se especifica el Destination Depot.
                        $enc2.="LOC+99+$codOD'".$sepa;   // 99 : Place of empty equipment return
                        $tlSegmentos++;
                        */

                        fputs($fp2,$enc2);
                        // Validando que el archivo por lo menos contiene un registro.
                        $flgOkFile=1;
                    }

                }
                $l++;
            }

            // Pie de pagina
            // Total de contenedores
            $pie="CNT+16:$tlConte'".$sepa;
            $tlSegmentos++;
            $pie.="UNT+$tlSegmentos+$fecD1$fecD2'".$sepa;
            $pie.="UNZ+1+$fecD1$fecD2'";

            fputs($fp2,$pie);
            fclose($fp2);

            unset($rowIterator);
            unset($datos);


            if( $flgOkFile==1) {
                if ($opcPro == "email") {
                    sendNotifyOneFile($sesIdUsuario, 'MOL', $fileEDI, $fileName);
                }
                elseif ($opcPro == "ftp") {
                    sentToFTP($fileEDI,$idNav);
                }
                return $fileEDI;
            }
        }

    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateInMscExcelTrane($stConte,$sesIdUsuario,$idNav,$opcPro){


        // EXCEL
        // ---------------------------------------------------
        // PROCESO COPIAR ARCHIVO
        // ---------------------------------------------------
        if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
            copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/entradasTrane.xlsx");
        }
        else {
            echo "Error de archivo!.";
        }

        // -------------------------------------
        // GET-IN
        // -------------------------------------
        unset($fp);
        unset($data);
        unset($dataX);


        // ---------------------------------
        // ---- LEER ARCHIVO DE EXCEL ---
        // ---------------------------------
        $objReader = new PHPExcel_Reader_Excel2007();
        //$objReader = new PHPExcel_Reader_Excel5();
        $objPHPExcel = $objReader->load('../edi_files/csv/entradasTrane.xlsx');
        $objPHPExcel->setActiveSheetIndex(0);


        $validFile="True";
        $l=1;

        // ------------------
        // Validacion campos
        // ------------------
        // ---------------------------------
        // Validar el encabezado
        // ----------------------------------
        $encC1= $objPHPExcel->getActiveSheet()->getCell("A9")->getValue();
        $encC2= $objPHPExcel->getActiveSheet()->getCell("B9")->getValue();
        $encC3= $objPHPExcel->getActiveSheet()->getCell("C9")->getValue();
        $encC4= $objPHPExcel->getActiveSheet()->getCell("D9")->getValue();
        $encC5= $objPHPExcel->getActiveSheet()->getCell("E9")->getValue();
        $encC6= $objPHPExcel->getActiveSheet()->getCell("F9")->getValue();
        $encC7= $objPHPExcel->getActiveSheet()->getCell("G9")->getValue();
        $encC8= $objPHPExcel->getActiveSheet()->getCell("H9")->getValue();
        $encC9= $objPHPExcel->getActiveSheet()->getCell("I9")->getValue();
        $encC10= $objPHPExcel->getActiveSheet()->getCell("J9")->getValue();

        if( $encC1!="Fecha de Ingreso"){$validFile=0;echo "[Error Encabezado] Fecha de Ingreso<br>";}
        if( $encC2!="Hora (24hrs)"){$validFile=0;echo "[Error Encabezado] Hora (24hrs)<br>";}
        if( $encC3!="Num. Contenedor" ){$validFile=0;echo "[Error Encabezado] Num. Contenedor<br>";}
        if( $encC5!="CALIDAD" ){$validFile=0;echo "[Error Encabezado] CALIDAD <br>";}
        if( $encC7!="Ag. Aduanal / Transportista" ){$validFile=0;echo "[Error Encabezado] Ag. Aduanal / Transportista <br>";}
        if( $encC8!="No.- EIR" ){$validFile=0;echo "[Error Encabezado] No.- EIR <br>";}
        if( $encC9!="Maniobra por cuenta de Merchant / carrier" ){$validFile=0;echo "[Error Encabezado] Maniobra por cuenta de Merchant / carrier<br>";}
        if( $encC10!="I_E" ){$validFile=0;echo "[Error Encabezado] I_E <br>";}
        // ---- Fin de la validación ----

        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $array_data = array();
        foreach($rowIterator as $row){
            $lin = $row->getRowIndex ();
            //echo "fila ".$row->getRowIndex ();
            //echo "<br>";
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            //if(1 == $row->getRowIndex ()) continue; //skip first row la del encabezado
            $rowIndex = $row->getRowIndex ();
            //$array_data[$rowIndex] = array('A'=>'', 'B'=>'','C'=>'','D'=>'','E'=>'');

            foreach ($cellIterator as $cell) {
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn() ){
                    $fecha = $cell->getFormattedValue();
                    $fecha = str_replace("/","-",$fecha);
                    // Validar Fecha : En caso de que los formatos sean 01-mes-15
                    unset($parts);
                    if( preg_match("/(\d{1,2})-(\w{3,3})-(\d{2,2})/i",$fecha,$parts) ){
                        // echo "FECHA:  $fecha OK <br>";
                        $dd = $parts[1];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $mes = $parts[2];
                        $aa = 2000 + $parts[3];
                        $mm = getOkMes($mes);
                        $fecha = $aa."-".$mm."-".$dd;
                    }

                    if( $lin>9 && !empty($fecha) && ( !preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha)  ) ){
                        echo "[Error] Lin: $lin | Fecha : $fecha | El formato es incorrecto, cambie el formato como -> aaaa-mm-dd  <br>";
                        $validFile=0;
                    }
                    $datos[$rowIndex]['FECHA'] = $fecha;
                }
                if('B' == $cell->getColumn()){
                    $hora = $cell->getFormattedValue();
                    unset($parts);
                    if( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) PM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        if( $hh<12 ){
                            $hh = $hh + 12;
                        }
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) AM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/^(\d{1,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }

                    $datos[$rowIndex]['HORA'] = $hora;
                }
                /*// Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn()){
                    $datos[$rowIndex]['FECHAHORA'] = $cell->getValue();
                    if( $datos[$rowIndex]['FECHAHORA']=="Fecha y hora Ingreso" ){
                        $rowStar=$rowIndex;
                    }
                }
                */
                if('C' == $cell->getColumn()){
                    $conte = $cell->getValue();
                    $conte = strtoupper($conte);
                    $datos[$rowIndex]['CONTENEDOR'] = $conte;
                }
                if('D' == $cell->getColumn()){
                    $datos[$rowIndex]['EQUIPO'] = $cell->getValue();
                }
                if('H' == $cell->getColumn()){
                    $datos[$rowIndex]['EIR'] = $cell->getValue();
                }
                if('I' == $cell->getColumn()){
                    $datos[$rowIndex]['MANIOBRA'] = $cell->getValue();
                }
                if('G' == $cell->getColumn()){
                    $datos[$rowIndex]['TRANSPORTISTA'] = $cell->getValue();
                }
                if('J' == $cell->getColumn()){
                    $datos[$rowIndex]['I_E'] = $cell->getValue();
                }
            }
        }

        if( $validFile=="True" ) {
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $flgRec = 1;

            $l = 1;
            $f = date(Ymd);

            // ------------------------------------------------
            // Generar archivo EDI
            // ------------------------------------------------
            $fileEDI = "../edi_files/edi/TRANE_MSC_GATE_IN".$fecD1.$fecD2.".edi";
            $fileName = "TRANE_MSC_GATE_IN".$fecD1.$fecD2.".edi";
            $fp2 = fopen("$fileEDI", "w");
            $sepa = "\r\n";
            $codPatio = getValueTable("cod_patio", "USUARIO", "id_usuario", $sesIdUsuario);
            $codPatioName = getValueTable("cod_patio_name", "USUARIO", "id_usuario", $sesIdUsuario);
            $senderID = getValueTable("sender_id", "USUARIO", "id_usuario", $sesIdUsuario);
            $receiverID = "MSC";
            // ENCABEZADO
            $enc = "UNB+UNOA:1+$senderID+$receiverID+$fecD1:$fecD2+$fecD1$fecD2'" . $sepa;
            $enc .= "UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'" . $sepa;
            $enc .= "BGM+34+$fecD1$fecD2+9'" . $sepa;
            $enc .= "LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'" . $sepa;
            //"TDT+20++1++MSC:172:166'".$sepa.
            //"NAD+CA+MXVRCDECECI:172:166'".$sepa;
            //$enc11=utf8_encode($enc);
            fputs($fp2, $enc);


            $tlConte = 0;
            $tlSegmentos = 4;

            // -------------------------------------------------
            // OBTENIENDO LA INFO
            // -------------------------------------------------
            $l=1;
            foreach ($datos as $dato => $x) {

                if( $l>9 ) {
                    $validFec=0;
                    /*
                    $fechaHora = $x['FECHAHORA'];
                    if( preg_match("/(\d{2})\/(\d{2})\/(\d{4}) (\d{2}):(\d{2})/i",$fechaHora,$parts) ){
                        $d= $parts[1];
                        $m= $parts[2];
                        $a= $parts[3];
                        $hr = $parts[4];
                        $min = $parts[5];
                        $regFecha= $a.$m.$d;
                        $regHora=$hr.$min;
                        $validFec=1;
                    }
                    */
                    $fecha = $x['FECHA'];
                    if( preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha,$parts) ){
                        $d= $parts[3];
                        $m= $parts[2];
                        $a= $parts[1];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }

                    $hora = $x['HORA'];
                    $hora = strtolower($hora);
                    $hora = str_replace(" ","",$hora);
                    $hora = str_replace(":","",$hora);
                    $hora = str_replace("-","",$hora);
                    $hora = str_replace(".","",$hora);
                    $hora = str_replace("pm","",$hora);
                    $hora = str_replace("am","",$hora);
                    $hora = str_pad($hora,4,"0", STR_PAD_LEFT);
                    $regHora = $hora;

                    $conte = $x['CONTENEDOR'];
                    $equipo = $x['EQUIPO'];
                    $tipo = getEquipoTipo($equipo);
                    $eir = $x['EIR'];
                    if(!empty($eir)){
                        $eir="EIR: $eir ";
                    }
                    $maniobra = $x['MANIOBRA'];
                    $transportista = $x['TRANSPORTISTA'];
                    $impoExpo = $x[I_E];

                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;

                        // ---------------------------------------------------------
                        // COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                        // ----------------------------------------------------------
                        ($impoExpo=="E")?$stIE=2:$stIE=3;
                        ($stConte=="E")?$stCo=4:$stCo=5;
                        $conte = strtoupper($conte);
                        $enc2= "EQD+CN+$conte+$tipo++$stIE+$stCo'".$sepa;
                        $tlSegmentos++;
                        $enc2.="DTM+7:$regFecha$regHora:203'".$sepa;
                        $tlSegmentos++;
                        $enc2.="FTX+AAI+++$transportista|$eir'".$sepa;
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'".$sepa;
                        $tlSegmentos++;
                        //$enc2.="LOC+99+$codePatio+::ZZZ'".$sepa;   // 99 : Place of empty equipment return
                        //$tlSegmentos++;
                        fputs($fp2,$enc2);
                        $flgOkFile=1;
                    }

                }
                $l++;
            }

            // Pie de pagina
            // Total de contenedores
            $pie="CNT+16:$tlConte'".$sepa;
            $tlSegmentos++;
            $pie.="UNT+$tlSegmentos+$fecD1$fecD2'".$sepa;
            $pie.="UNZ+1+$fecD1$fecD2'";

            //$enc33=utf8_encode($enc3);
            fputs($fp2,$pie);
            fclose($fp2);

            unset($rowIterator);
            unset($datos);

            if($flgOkFile==1) {
                if ($opcPro == "email") {
                    //sendNotifyOneFile($sesIdUsuario, 'MSC', $fileEDI, $fileName);
                } elseif ($opcPro == "ftp") {
                    sentToFTP($fileEDI,$idNav,'/MEXICO/MEXICO/');
                }
                return $fileEDI;
            }


        }

    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateOutMscExcelTrane($stConte,$sesIdUsuario,$idNav,$opcPro){

        // EXCEL
        // ---------------------------------------------------
        // PROCESO COPIAR ARCHIVO
        // ---------------------------------------------------
        if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
            copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/salidasTrane.xlsx");
        }
        else {
            echo "Error de archivo!.";
        }

        // -------------------------------------
        // GET-IN
        // -------------------------------------
        unset($fp);
        unset($data);
        unset($dataX);


        // ---------------------------------
        // ---- LEER ARCHIVO DE EXCEL ---
        // ---------------------------------
        $objReader = new PHPExcel_Reader_Excel2007();
        //$objReader = new PHPExcel_Reader_Excel5();
        $objPHPExcel = $objReader->load('../edi_files/csv/salidasTrane.xlsx');
        $objPHPExcel->setActiveSheetIndex(1);


        $validFile="True";
        $l=1;

        // ---------------------------------
        // Validar el encabezado
        // ----------------------------------
        $encC1= $objPHPExcel->getActiveSheet()->getCell("A9")->getValue();
        $encC2= $objPHPExcel->getActiveSheet()->getCell("B9")->getValue();
        $encC3= $objPHPExcel->getActiveSheet()->getCell("C9")->getValue();
        $encC4= $objPHPExcel->getActiveSheet()->getCell("D9")->getValue();
        $encC5= $objPHPExcel->getActiveSheet()->getCell("E9")->getValue();
        $encC6= $objPHPExcel->getActiveSheet()->getCell("F9")->getValue();
        $encC7= $objPHPExcel->getActiveSheet()->getCell("G9")->getValue();
        $encC8= $objPHPExcel->getActiveSheet()->getCell("H9")->getValue();
        $encC9= $objPHPExcel->getActiveSheet()->getCell("I9")->getValue();
        $encC10= $objPHPExcel->getActiveSheet()->getCell("J9")->getValue();
        $encC11= $objPHPExcel->getActiveSheet()->getCell("K9")->getValue();
        $encC12= $objPHPExcel->getActiveSheet()->getCell("L9")->getValue();

        if( $encC1!="Fecha de Ingreso"){$validFile=0;echo "[Error Encabezado] Fecha de Ingreso<br>";}
        if( $encC2!="Hora (24hrs)" ){$validFile=0;echo "[Error Encabezado] Hora (24hrs)<br>";}
        if( $encC3!="Num. Contenedor"){$validFile=0;echo "[Error Encabezado] Num. Contenedor<br>";}
        //if( $encC4!="TIPO/TAMAÑO" ){$validFile=0;echo "[Error Encabezado] TIPO/TAMAÑO<br>";}
        if( $encC5!="Booking" ){$validFile=0;echo "[Error Encabezado] Booking <br>";}
        if( $encC6!="Cliente" ){$validFile=0;echo "[Error Encabezado] Cliente <br>";}
        if( $encC7!="Transportista" ){$validFile=0;echo "[Error Encabezado] Transportista <br>";}
        if( $encC8!="CALIDAD" ){$validFile=0;echo "[Error Encabezado] CALIDAD<br>";}
        if( $encC9!="No.- EIR" ){$validFile=0;echo "[Error Encabezado] No.- EIR <br>";}
        if( $encC10!="Sello Salida" ){$validFile=0;echo "[Error Encabezado] Sello Salida <br>";}
        if( $encC11!="Maniobra por cuenta de Merchant  //  carrier" ){$validFile=0;echo "[Error Encabezado] Maniobra por cuenta de Merchant  //  carrier <br>";}
        if( $encC12!="I_E" ){$validFile=0;echo "[Error Encabezado] I_E <br>";}
        // ---- Fin de la validación ----

        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $array_data = array();
        foreach($rowIterator as $row){
            $lin = $row->getRowIndex ();
            //echo "fila ".$row->getRowIndex ();
            //echo "<br>";
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            //if(1 == $row->getRowIndex ()) continue; //skip first row la del encabezado
            $rowIndex = $row->getRowIndex ();
            //$array_data[$rowIndex] = array('A'=>'', 'B'=>'','C'=>'','D'=>'','E'=>'');

            foreach ($cellIterator as $cell) {
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn() ){
                    $fecha = $cell->getFormattedValue();
                    $fecha = str_replace("/","-",$fecha);
                    // Validar Fecha : En caso de que los formatos sean 01-mes-15
                    unset($parts);
                    if( preg_match("/(\d{1,2})-(\w{3,3})-(\d{2,2})/i",$fecha,$parts) ){
                        // echo "FECHA:  $fecha OK <br>";
                        $dd = $parts[1];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $mes = $parts[2];
                        $aa = 2000 + $parts[3];
                        $mm = getOkMes($mes);
                        $fecha = $aa."-".$mm."-".$dd;
                    }

                    if( $lin>9 && !empty($fecha) && ( !preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha)  ) ){
                        echo "[Error] Lin: $lin | Fecha : $fecha | El formato es incorrecto, cambie el formato como -> aaaa-mm-dd  <br>";
                        $validFile=0;
                    }
                    $datos[$rowIndex]['FECHA'] = $fecha;
                }
                if('B' == $cell->getColumn()){
                    $hora = $cell->getFormattedValue();
                    unset($parts);
                    if( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) PM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        if( $hh<12 ){
                            $hh = $hh + 12;
                        }
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) AM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/^(\d{1,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    $datos[$rowIndex]['HORA'] = $hora;
                }

                if('C' == $cell->getColumn()){
                    $conte = $cell->getValue();
                    $conte = strtoupper($conte);
                    $datos[$rowIndex]['CONTENEDOR'] = $conte;
                }
                if('D' == $cell->getColumn()){
                    $datos[$rowIndex]['EQUIPO'] = $cell->getValue();
                }
                if('E' == $cell->getColumn()){
                    $datos[$rowIndex]['BOOKING'] = $cell->getValue();
                }
                if('F' == $cell->getColumn()){
                    $datos[$rowIndex]['CLIENTE'] = $cell->getValue();
                }
                if('G' == $cell->getColumn()){
                    $datos[$rowIndex]['TRANSPORTISTA'] = $cell->getValue();
                }
                if('I' == $cell->getColumn()){
                    $datos[$rowIndex]['EIR'] = $cell->getValue();
                }
                if('J' == $cell->getColumn()){
                    $datos[$rowIndex]['SELLO'] = $cell->getValue();
                }
                if('K' == $cell->getColumn()){
                    $datos[$rowIndex]['MANIOBRA'] = $cell->getValue();
                }
                if('L' == $cell->getColumn()){
                    $datos[$rowIndex]['I_E'] = $cell->getValue();
                }

            }
        }

        if( $validFile=="True" ) {
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $flgRec = 1;

            $l = 1;
            $f = date(Ymd);

            // ------------------------------------------------
            // Generar archivo EDI
            // ------------------------------------------------
            $fileEDI = "../edi_files/edi/TRANE_MSC_GATE_OUT".$fecD1.$fecD2.".edi";
            $fileName = "TRANE_MSC_GATE_OUT".$fecD1.$fecD2.".edi";
            $fp2 = fopen("$fileEDI", "w");
            $sepa = "\r\n";
            $codPatio = getValueTable("cod_patio", "USUARIO", "id_usuario", $sesIdUsuario);
            $codPatioName = getValueTable("cod_patio_name", "USUARIO", "id_usuario", $sesIdUsuario);
            $senderID = getValueTable("sender_id", "USUARIO", "id_usuario", $sesIdUsuario);
            $receiverID = "MSC";
            // ENCABEZADO
            $enc = "UNB+UNOA:1+$senderID+$receiverID+$fecD1:$fecD2+$fecD1$fecD2'" . $sepa;
            $enc .= "UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'" . $sepa;
            $enc .= "BGM+36+$fecD1$fecD2+9'" . $sepa;
            $enc .= "LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'" . $sepa;
            //"TDT+20++1++MSC:172:166'".$sepa.
            //"NAD+CA+MXVRCDECECI:172:166'".$sepa;
            //$enc11=utf8_encode($enc);
            fputs($fp2, $enc);


            $tlConte = 0;
            $tlSegmentos = 4;

            // -------------------------------------------------
            // OBTENIENDO LA INFO
            // -------------------------------------------------
            $l=1;
            foreach ($datos as $dato => $x) {

                if( $l>9 ) {
                    $validFec=0;

                    $fecha = $x['FECHA'];
                    if( preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha,$parts) ){
                        $d= $parts[3];
                        $m= $parts[2];
                        $a= $parts[1];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }

                    $hora = $x['HORA'];
                    $hora = strtolower($hora);
                    $hora = str_replace(" ","",$hora);
                    $hora = str_replace(":","",$hora);
                    $hora = str_replace("-","",$hora);
                    $hora = str_replace(".","",$hora);
                    $hora = str_replace("pm","",$hora);
                    $hora = str_replace("am","",$hora);
                    $hora = str_pad($hora,4,"0", STR_PAD_LEFT);
                    $regHora = $hora;

                    $conte = $x['CONTENEDOR'];
                    $equipo = $x['EQUIPO'];
                    $tipo = getEquipoTipo($equipo);
                    $eir = $x['EIR'];
                    $maniobra = $x['MANIOBRA'];
                    $transportista = $x['TRANSPORTISTA'];
                    $cliente = $x['CLIENTE'];
                    $cliente = strtoupper($cliente);
                    $bkgNumber = $x['BOOKING'];
                    $bkgNumber = strtoupper($bkgNumber);
                    $bl = $x['BL'];
                    $bkgNumber = strtoupper($bkgNumber);
                    $sello = $x[SELLO];
                    $impoExpo = $x[I_E];


                    /*
                    if( preg_match("/^([0-9]+).* ",$bkgNumber,$parts) ){
                        $codBkg = $parts[1];
                        switch($codBkg){
                            case "192": $codOD = "LMR00";break;
                            case "364": $codOD = "ZLO00";break;
                            case "191": $codOD = "VER00";break;
                            case "404": $codOD = "MZT00";break;
                            case "697": $codOD = "GYM00";break;
                            case "620": $codOD = "LZC00";break;
                            case "622": $codOD = "PUM00";break;
                        }
                    }
                    */
                    // Segun Lemuel este no importa, por que no sabemos el destino.
                    $codOD="";


                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;

                        // ---------------------------------------------------------
                        // COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                        // ----------------------------------------------------------
                        ($impoExpo=="E")?$stIE=2:$stIE=3;
                        ($stConte=="E")?$stCo=4:$stCo=5;
                        $conte = strtoupper($conte);
                        $enc2= "EQD+CN+$conte+$tipo++$stIE+$stCo'".$sepa;
                        $tlSegmentos++;
                        if( !empty($bl) ){
                            $enc2.= "RFF+BM:$bl'\r\n";
                            $tlSegmentos++;
                        }
                        if( !empty($bkgNumber) ) {
                            $enc2.= "RFF+BN:$bkgNumber'\r\n";
                            $tlSegmentos++;
                        }
                        $enc2.="DTM+7:$regFecha$regHora:203'".$sepa;
                        $tlSegmentos++;
                        $enc2.= "LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'\r\n";
                        $tlSegmentos++;
                        $enc2.="SEL+$sello+CA'\r\n";
                        $tlSegmentos++;
                        $enc2.="FTX+AAI+++$bkgNumber|$cliente|$transportista'".$sepa;
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'".$sepa;
                        $tlSegmentos++;
                        // Cuando sean salidas es necesario agregar esta línea para cada movimiento,
                        // ya que con este se especifica el Destination Depot.
                        $enc2.="LOC+99+$codOD'".$sepa;   // 99 : Place of empty equipment return
                        $tlSegmentos++;
                        fputs($fp2,$enc2);
                        // Validando que el archivo por lo menos contiene un registro.
                        $flgOkFile=1;
                    }

                }
                $l++;
            }

            // Pie de pagina
            // Total de contenedores
            $pie="CNT+16:$tlConte'".$sepa;
            $tlSegmentos++;
            $pie.="UNT+$tlSegmentos+$fecD1$fecD2'".$sepa;
            $pie.="UNZ+1+$fecD1$fecD2'";

            fputs($fp2,$pie);
            fclose($fp2);

            unset($rowIterator);
            unset($datos);
            
            if( $flgOkFile==1) {
                if ($opcPro == "email") {
                    //sendNotifyOneFile($sesIdUsuario, 'MSC', $fileEDI, $fileName);
                } elseif ($opcPro == "ftp") {
                    sentToFTP($fileEDI,$idNav,'/MEXICO/MEXICO/');
                }
                return $fileEDI;
            }
        }

    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateInZIMExcelTrane($stConte,$sesIdUsuario,$idNav,$opcPro){
        // ZIM

        // EXCEL
        // ---------------------------------------------------
        // PROCESO COPIAR ARCHIVO
        // ---------------------------------------------------
        if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
            copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/entradasTrane.xlsx");
        }
        else {
            echo "Error de archivo!.";
        }

        // -------------------------------------
        // GET-IN
        // -------------------------------------
        unset($fp);
        unset($data);
        unset($dataX);


        // ---------------------------------
        // ---- LEER ARCHIVO DE EXCEL ---
        // ---------------------------------
        $objReader = new PHPExcel_Reader_Excel2007();
        //$objReader = new PHPExcel_Reader_Excel5();
        $objPHPExcel = $objReader->load('../edi_files/csv/entradasTrane.xlsx');
        $objPHPExcel->setActiveSheetIndex(0);


        $validFile="True";
        $l=1;

        // ------------------
        // Validacion campos
        // ------------------
        // ---------------------------------
        // Validar el encabezado
        // ----------------------------------
        $encC1= $objPHPExcel->getActiveSheet()->getCell("A9")->getValue();
        $encC2= $objPHPExcel->getActiveSheet()->getCell("B9")->getValue();
        $encC3= $objPHPExcel->getActiveSheet()->getCell("C9")->getValue();
        $encC4= $objPHPExcel->getActiveSheet()->getCell("D9")->getValue();
        $encC5= $objPHPExcel->getActiveSheet()->getCell("E9")->getValue();
        $encC6= $objPHPExcel->getActiveSheet()->getCell("F9")->getValue();
        $encC7= $objPHPExcel->getActiveSheet()->getCell("G9")->getValue();
        $encC8= $objPHPExcel->getActiveSheet()->getCell("H9")->getValue();
        $encC9= $objPHPExcel->getActiveSheet()->getCell("I9")->getValue();
        $encC10= $objPHPExcel->getActiveSheet()->getCell("J9")->getValue();

        if( $encC1!="Fecha de Ingreso"){$validFile=0;echo "[Error Encabezado] Fecha de Ingreso<br>";}
        if( $encC2!="Hora (24hrs)"){$validFile=0;echo "[Error Encabezado] Hora (24hrs)<br>";}
        if( $encC3!="Num. Contenedor" ){$validFile=0;echo "[Error Encabezado] Num. Contenedor<br>";}
        if( $encC5!="CALIDAD" ){$validFile=0;echo "[Error Encabezado] CALIDAD <br>";}
        if( $encC7!="Ag. Aduanal / Transportista" ){$validFile=0;echo "[Error Encabezado] Ag. Aduanal / Transportista <br>";}
        if( $encC8!="No.- EIR" ){$validFile=0;echo "[Error Encabezado] No.- EIR <br>";}
        if( $encC9!="Maniobra por cuenta de Merchant / carrier" ){$validFile=0;echo "[Error Encabezado] Maniobra por cuenta de Merchant / carrier<br>";}
        if( $encC10!="I_E" ){$validFile=0;echo "[Error Encabezado] I_E <br>";}
        // ---- Fin de la validación ----

        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $array_data = array();
        foreach($rowIterator as $row){
            $lin = $row->getRowIndex ();
            //echo "fila ".$row->getRowIndex ();
            //echo "<br>";
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            //if(1 == $row->getRowIndex ()) continue; //skip first row la del encabezado
            $rowIndex = $row->getRowIndex ();
            //$array_data[$rowIndex] = array('A'=>'', 'B'=>'','C'=>'','D'=>'','E'=>'');

            foreach ($cellIterator as $cell) {
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn() ){
                    $fecha = $cell->getFormattedValue();
                    $fecha = str_replace("/","-",$fecha);
                    // Validar Fecha : En caso de que los formatos sean 01-mes-15
                    unset($parts);
                    if( preg_match("/(\d{1,2})-(\w{3,3})-(\d{2,2})/i",$fecha,$parts) ){
                        // echo "FECHA:  $fecha OK <br>";
                        $dd = $parts[1];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $mes = $parts[2];
                        $aa = 2000 + $parts[3];
                        $mm = getOkMes($mes);
                        $fecha = $aa."-".$mm."-".$dd;
                    }

                    if( $lin>9 && !empty($fecha) && ( !preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha)  ) ){
                        echo "[Error] Lin: $lin | Fecha : $fecha | El formato es incorrecto, cambie el formato como -> aaaa-mm-dd  <br>";
                        $validFile=0;
                    }
                    $datos[$rowIndex]['FECHA'] = $fecha;
                }
                if('B' == $cell->getColumn()){
                    $hora = $cell->getFormattedValue();
                    unset($parts);
                    if( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) PM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        if( $hh<12 ){
                            $hh = $hh + 12;
                        }
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) AM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/^(\d{1,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }

                    $datos[$rowIndex]['HORA'] = $hora;
                }
                /*// Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn()){
                    $datos[$rowIndex]['FECHAHORA'] = $cell->getValue();
                    if( $datos[$rowIndex]['FECHAHORA']=="Fecha y hora Ingreso" ){
                        $rowStar=$rowIndex;
                    }
                }
                */
                if('C' == $cell->getColumn()){
                    $conte = $cell->getValue();
                    $conte = strtoupper($conte);
                    $datos[$rowIndex]['CONTENEDOR'] = $conte;
                }
                if('D' == $cell->getColumn()){
                    $datos[$rowIndex]['EQUIPO'] = $cell->getValue();
                }
                if('H' == $cell->getColumn()){
                    $datos[$rowIndex]['EIR'] = $cell->getValue();
                }
                if('I' == $cell->getColumn()){
                    $datos[$rowIndex]['MANIOBRA'] = $cell->getValue();
                }
                if('G' == $cell->getColumn()){
                    $datos[$rowIndex]['TRANSPORTISTA'] = $cell->getValue();
                }
                if('J' == $cell->getColumn()){
                    $datos[$rowIndex]['I_E'] = $cell->getValue();
                }
            }
        }

        if( $validFile=="True" ) {
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $flgRec = 1;

            $l = 1;
            $f = date(Ymd);

            // ------------------------------------------------
            // Generar archivo EDI GATE-IN
            // ------------------------------------------------
            $fileEDI = "../edi_files/edi/EDICODECO_ZIM_IN_".$fecD1.$fecD2.".edi";
            $fileName = "EDICODECO_ZIM_IN_".$fecD1.$fecD2.".edi";
            $fp2 = fopen("$fileEDI", "w");
            $sepa = "\r\n";
            $codPatio = getValueTable("cod_patio", "USUARIO", "id_usuario", $sesIdUsuario);
            $codPatioName = getValueTable("cod_patio_name", "USUARIO", "id_usuario", $sesIdUsuario);
            // $senderID = getValueTable("sender_id", "USUARIO", "id_usuario", $sesIdUsuario);
            $receiverID = "ZIM";
            // ENCABEZADO
            $enc = "UNB+UNOA:3+MXMTYDTR+ZIM+$fecD1:$fecD2+$fecD1$fecD2'" . $sepa;
            $enc .= "UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'" . $sepa;
            $enc .= "BGM+34+$fecD1$fecD2+9'" . $sepa;
            //$enc .= "LOC+165+$codPatio:139:6+$codPatioName:ZZZ:ZZZ'".$sepa;
            //$enc11=utf8_encode($enc);
            fputs($fp2, $enc);


            $tlConte = 0;
            $tlSegmentos = 4;

            // -------------------------------------------------
            // OBTENIENDO LA INFO
            // -------------------------------------------------
            $l=1;
            foreach ($datos as $dato => $x) {

                if( $l>9 ) {
                    $validFec=0;
                    /*
                    $fechaHora = $x['FECHAHORA'];
                    if( preg_match("/(\d{2})\/(\d{2})\/(\d{4}) (\d{2}):(\d{2})/i",$fechaHora,$parts) ){
                        $d= $parts[1];
                        $m= $parts[2];
                        $a= $parts[3];
                        $hr = $parts[4];
                        $min = $parts[5];
                        $regFecha= $a.$m.$d;
                        $regHora=$hr.$min;
                        $validFec=1;
                    }
                    */
                    $fecha = $x['FECHA'];
                    if( preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha,$parts) ){
                        $d= $parts[3];
                        $m= $parts[2];
                        $a= $parts[1];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }

                    $hora = $x['HORA'];
                    $hora = strtolower($hora);
                    $hora = str_replace(" ","",$hora);
                    $hora = str_replace(":","",$hora);
                    $hora = str_replace("-","",$hora);
                    $hora = str_replace(".","",$hora);
                    $hora = str_replace("pm","",$hora);
                    $hora = str_replace("am","",$hora);
                    $hora = str_pad($hora,4,"0", STR_PAD_LEFT);
                    $regHora = $hora;

                    $conte = $x['CONTENEDOR'];
                    $equipo = $x['EQUIPO'];
                    $tipo = getEquipoTipo($equipo);
                    $eir = $x['EIR'];
                    if(!empty($eir)){
                        $eir="EIR: $eir ";
                    }
                    $maniobra = $x['MANIOBRA'];
                    $transportista = $x['TRANSPORTISTA'];
                    $impoExpo = $x[I_E];

                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;

                        // ---------------------------------------------------------
                        // COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                        // ----------------------------------------------------------
                        ($impoExpo=="E")?$stIE=2:$stIE=3;
                        ($stConte=="E")?$stCo=4:$stCo=5;
                        $conte = strtoupper($conte);
                        $enc2= "EQD+CN+$conte+$tipo++$stIE+$stCo'".$sepa;
                        $tlSegmentos++;
                        $enc2.="DTM+7:$regFecha$regHora:203'".$sepa;
                        $tlSegmentos++;
                        $enc2.="LOC+165+MXMTY:139:6+MXMTYDTR:TER:ZZZ'".$sepa;
                        $tlSegmentos++;
                        $enc2.="FTX+AAI+++$transportista|$eir'".$sepa;
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'".$sepa;
                        $tlSegmentos++;
                        fputs($fp2,$enc2);
                        // Esto indicara que el archivo se esta produciendo correctamente.
                        $flgOkFile=1;
                    }

                }
                $l++;
            }

            // Pie de pagina
            // Total de contenedores
            $pie="CNT+16:$tlConte'".$sepa;
            $pie.="UNT+$tlSegmentos+$fecD1$fecD2'".$sepa;
            $pie.="UNZ+1+$fecD1$fecD2'";

            //$enc33=utf8_encode($enc3);
            fputs($fp2,$pie);
            fclose($fp2);

            unset($rowIterator);
            unset($datos);


            if( $flgOkFile==1) {
                if ( $opcPro == "email" ) {
                    // sendNotifyOneFile($sesIdUsuario, 'ZIM', $fileEDI, $fileName);
                }
                elseif ( $opcPro == "ftp" ) {
                    sentToFTP($fileEDI,$idNav);
                }
                return $fileEDI;
            }


        }

    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateOutZIMExcelTrane($stConte,$sesIdUsuario,$idNav,$opcPro){
        // ZIM
        // ---------------------------------------------------
        // PROCESO COPIAR ARCHIVO
        // ---------------------------------------------------
        if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
            copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/salidasTrane.xlsx");
        }
        else {
            echo "Error de archivo!.";
        }

        // -------------------------------------
        // GET-IN
        // -------------------------------------
        unset($fp);
        unset($data);
        unset($dataX);


        // ---------------------------------
        // ---- LEER ARCHIVO DE EXCEL ---
        // ---------------------------------
        $objReader = new PHPExcel_Reader_Excel2007();
        //$objReader = new PHPExcel_Reader_Excel5();
        $objPHPExcel = $objReader->load('../edi_files/csv/salidasTrane.xlsx');
        $objPHPExcel->setActiveSheetIndex(1);


        $validFile="True";
        $l=1;

        // ---------------------------------
        // Validar el encabezado
        // ----------------------------------
        $encC1= $objPHPExcel->getActiveSheet()->getCell("A9")->getValue();
        $encC2= $objPHPExcel->getActiveSheet()->getCell("B9")->getValue();
        $encC3= $objPHPExcel->getActiveSheet()->getCell("C9")->getValue();
        $encC4= $objPHPExcel->getActiveSheet()->getCell("D9")->getValue();
        $encC5= $objPHPExcel->getActiveSheet()->getCell("E9")->getValue();
        $encC6= $objPHPExcel->getActiveSheet()->getCell("F9")->getValue();
        $encC7= $objPHPExcel->getActiveSheet()->getCell("G9")->getValue();
        $encC8= $objPHPExcel->getActiveSheet()->getCell("H9")->getValue();
        $encC9= $objPHPExcel->getActiveSheet()->getCell("I9")->getValue();
        $encC10= $objPHPExcel->getActiveSheet()->getCell("J9")->getValue();
        $encC11= $objPHPExcel->getActiveSheet()->getCell("K9")->getValue();
        $encC12= $objPHPExcel->getActiveSheet()->getCell("L9")->getValue();

        if( $encC1!="Fecha de Ingreso"){$validFile=0;echo "[Error Encabezado] Fecha de Ingreso<br>";}
        if( $encC2!="Hora (24hrs)" ){$validFile=0;echo "[Error Encabezado] Hora (24hrs)<br>";}
        if( $encC3!="Num. Contenedor"){$validFile=0;echo "[Error Encabezado] Num. Contenedor<br>";}
        //if( $encC4!="TIPO/TAMAÑO" ){$validFile=0;echo "[Error Encabezado] TIPO/TAMAÑO<br>";}
        if( $encC5!="Booking" ){$validFile=0;echo "[Error Encabezado] Booking <br>";}
        if( $encC6!="Cliente" ){$validFile=0;echo "[Error Encabezado] Cliente <br>";}
        if( $encC7!="Transportista" ){$validFile=0;echo "[Error Encabezado] Transportista <br>";}
        if( $encC8!="CALIDAD" ){$validFile=0;echo "[Error Encabezado] CALIDAD<br>";}
        if( $encC9!="No.- EIR" ){$validFile=0;echo "[Error Encabezado] No.- EIR <br>";}
        if( $encC10!="Sello Salida" ){$validFile=0;echo "[Error Encabezado] Sello Salida <br>";}
        if( $encC11!="Maniobra por cuenta de Merchant  //  carrier" ){$validFile=0;echo "[Error Encabezado] Maniobra por cuenta de Merchant  //  carrier <br>";}
        if( $encC12!="I_E" ){$validFile=0;echo "[Error Encabezado] I_E <br>";}
        // ---- Fin de la validación ----

        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $array_data = array();
        foreach($rowIterator as $row){
            $lin = $row->getRowIndex ();
            //echo "fila ".$row->getRowIndex ();
            //echo "<br>";
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            //if(1 == $row->getRowIndex ()) continue; //skip first row la del encabezado
            $rowIndex = $row->getRowIndex ();
            //$array_data[$rowIndex] = array('A'=>'', 'B'=>'','C'=>'','D'=>'','E'=>'');

            foreach ($cellIterator as $cell) {
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn() ){
                    $fecha = $cell->getFormattedValue();
                    $fecha = str_replace("/","-",$fecha);
                    // Validar Fecha : En caso de que los formatos sean 01-mes-15
                    unset($parts);
                    if( preg_match("/(\d{1,2})-(\w{3,3})-(\d{2,2})/i",$fecha,$parts) ){
                        // echo "FECHA:  $fecha OK <br>";
                        $dd = $parts[1];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $mes = $parts[2];
                        $aa = 2000 + $parts[3];
                        $mm = getOkMes($mes);
                        $fecha = $aa."-".$mm."-".$dd;
                    }

                    if( $lin>9 && !empty($fecha) && ( !preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha)  ) ){
                        echo "[Error] Lin: $lin | Fecha : $fecha | El formato es incorrecto, cambie el formato como -> aaaa-mm-dd  <br>";
                        $validFile=0;
                    }
                    $datos[$rowIndex]['FECHA'] = $fecha;
                }
                if('B' == $cell->getColumn()){
                    $hora = $cell->getFormattedValue();
                    unset($parts);
                    if( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) PM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        if( $hh<12 ){
                            $hh = $hh + 12;
                        }
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) AM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/^(\d{1,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    $datos[$rowIndex]['HORA'] = $hora;
                }

                if('C' == $cell->getColumn()){
                    $conte = $cell->getValue();
                    $conte = strtoupper($conte);
                    $datos[$rowIndex]['CONTENEDOR'] = $conte;
                }
                if('D' == $cell->getColumn()){
                    $datos[$rowIndex]['EQUIPO'] = $cell->getValue();
                }
                if('E' == $cell->getColumn()){
                    $datos[$rowIndex]['BOOKING'] = $cell->getValue();
                }
                if('F' == $cell->getColumn()){
                    $datos[$rowIndex]['CLIENTE'] = $cell->getValue();
                }
                if('G' == $cell->getColumn()){
                    $datos[$rowIndex]['TRANSPORTISTA'] = $cell->getValue();
                }
                if('I' == $cell->getColumn()){
                    $datos[$rowIndex]['EIR'] = $cell->getValue();
                }
                if('J' == $cell->getColumn()){
                    $datos[$rowIndex]['SELLO'] = $cell->getValue();
                }
                if('K' == $cell->getColumn()){
                    $datos[$rowIndex]['MANIOBRA'] = $cell->getValue();
                }
                if('L' == $cell->getColumn()){
                    $datos[$rowIndex]['I_E'] = $cell->getValue();
                }

            }
        }

        if( $validFile=="True" ) {
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $flgRec = 1;

            $l = 1;
            $f = date(Ymd);

            // ------------------------------------------------
            // Generar archivo EDI GATE-OUT
            // ------------------------------------------------
            $fileEDI = "../edi_files/edi/EDICODECO_ZIM_OUT_".$fecD1.$fecD2.".edi";
            $fileName = "EDICODECO_ZIM_OUT_".$fecD1.$fecD2.".edi";
            $fp2 = fopen("$fileEDI", "w");
            $sepa = "\r\n";
            $codPatio = getValueTable("cod_patio", "USUARIO", "id_usuario", $sesIdUsuario);
            $codPatioName = getValueTable("cod_patio_name", "USUARIO", "id_usuario", $sesIdUsuario);
            //$senderID = getValueTable("sender_id", "USUARIO", "id_usuario", $sesIdUsuario);
            $receiverID = "ZIM";
            // ENCABEZADO
            $enc = "UNB+UNOA:3+MXMTYDTR+ZIM+$fecD1:$fecD2+$fecD1$fecD2'" . $sepa;
            $enc .= "UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'" . $sepa;
            $enc .= "BGM+36+$fecD1$fecD2+9'" . $sepa;
            //$enc .= "LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'" . $sepa;
            //"TDT+20++1++MSC:172:166'".$sepa.
            //"NAD+CA+MXVRCDECECI:172:166'".$sepa;
            //$enc11=utf8_encode($enc);
            fputs($fp2, $enc);


            $tlConte = 0;
            $tlSegmentos = 4;

            // -------------------------------------------------
            // OBTENIENDO LA INFO
            // -------------------------------------------------
            $l=1;
            foreach ($datos as $dato => $x) {

                if( $l>9 ) {
                    $validFec=0;

                    $fecha = $x['FECHA'];
                    if( preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha,$parts) ){
                        $d= $parts[3];
                        $m= $parts[2];
                        $a= $parts[1];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }

                    $hora = $x['HORA'];
                    $hora = strtolower($hora);
                    $hora = str_replace(" ","",$hora);
                    $hora = str_replace(":","",$hora);
                    $hora = str_replace("-","",$hora);
                    $hora = str_replace(".","",$hora);
                    $hora = str_replace("pm","",$hora);
                    $hora = str_replace("am","",$hora);
                    $hora = str_pad($hora,4,"0", STR_PAD_LEFT);
                    $regHora = $hora;

                    $conte = $x['CONTENEDOR'];
                    $equipo = $x['EQUIPO'];
                    $tipo = getEquipoTipo($equipo);
                    $eir = $x['EIR'];
                    $maniobra = $x['MANIOBRA'];
                    $transportista = $x['TRANSPORTISTA'];
                    $cliente = $x['CLIENTE'];
                    $cliente = strtoupper($cliente);
                    $bkgNumber = $x['BOOKING'];
                    $bkgNumber = strtoupper($bkgNumber);
                    $bl = $x['BL'];
                    $bkgNumber = strtoupper($bkgNumber);
                    $sello = $x[SELLO];
                    $impoExpo = $x[I_E];

                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;

                        // ---------------------------------------------------------
                        // COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                        // ----------------------------------------------------------
                        ($impoExpo=="E")?$stIE=2:$stIE=3;
                        ($stConte=="E")?$stCo=4:$stCo=5;
                        $conte = strtoupper($conte);
                        $enc2= "EQD+CN+$conte+$tipo++$stIE+$stCo'".$sepa;
                        $tlSegmentos++;
                        if( !empty($bl) ){
                            $enc2.= "RFF+BM:$bl'\r\n";
                            $tlSegmentos++;
                        }
                        if( !empty($bkgNumber) ) {
                            $enc2.= "RFF+BN:$bkgNumber'\r\n";
                            $tlSegmentos++;
                        }
                        $enc2.="DTM+7:$regFecha$regHora:203'".$sepa;
                        $tlSegmentos++;
                        $enc2.= "LOC+165+MXMTY:139:6+MXMTYDTR:TER:ZZZ'\r\n";
                        $tlSegmentos++;
                        $enc2.="FTX+AAI+++$bkgNumber|$cliente|$transportista'".$sepa;
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'".$sepa;
                        $tlSegmentos++;
                        /*
                        $enc2.= "LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'\r\n";
                        $tlSegmentos++;
                        $enc2.="SEL+$sello+CA'\r\n";
                        $tlSegmentos++;
                        $enc2.="FTX+AAI+++$bkgNumber/$cliente/$transportista'".$sepa;
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'".$sepa;
                        $tlSegmentos++;
                        */
                        // Cuando sean salidas es necesario agregar esta línea para cada movimiento,
                        // ya que con este se especifica el Destination Depot.
                        //$enc2.="LOC+99+'".$sepa;   // 99 : Place of empty equipment return
                        //$tlSegmentos++;
                        fputs($fp2,$enc2);
                        // Validando que el archivo por lo menos contiene un registro.
                        $flgOkFile=1;
                    }

                }
                $l++;
            }

            // Pie de pagina
            // Total de contenedores
            $pie="CNT+16:$tlConte'".$sepa;
            $pie.="UNT+$tlSegmentos+$fecD1$fecD2'".$sepa;
            $pie.="UNZ+1+$fecD1$fecD2'";

            fputs($fp2,$pie);
            fclose($fp2);

            unset($rowIterator);
            unset($datos);

            
            if( $flgOkFile==1 ) {
                if ($opcPro == "email") {
                    // sendNotifyOneFile($sesIdUsuario, 'ZIM', $fileEDI, $fileName);
                }
                elseif ($opcPro == "ftp") {
                    sentToFTP($fileEDI,$idNav);
                }
                return $fileEDI;
            }
        }

    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateInHSExcelTrane($stConte,$sesIdUsuario,$idNav,$opcPro){
        // Hamburg Sud

        // EXCEL
        // ---------------------------------------------------
        // PROCESO COPIAR ARCHIVO
        // ---------------------------------------------------
        if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
            copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/entradasTrane.xlsx");
        }
        else {
            echo "Error de archivo!.";
        }

        // -------------------------------------
        // GET-IN
        // -------------------------------------
        unset($fp);
        unset($data);
        unset($dataX);


        // ---------------------------------
        // ---- LEER ARCHIVO DE EXCEL ---
        // ---------------------------------
        $objReader = new PHPExcel_Reader_Excel2007();
        //$objReader = new PHPExcel_Reader_Excel5();
        $objPHPExcel = $objReader->load('../edi_files/csv/entradasTrane.xlsx');
        $objPHPExcel->setActiveSheetIndex(0);


        $validFile="True";
        $l=1;

        // ------------------
        // Validacion campos
        // ------------------
        // ---------------------------------
        // Validar el encabezado
        // ----------------------------------
        $encC1= $objPHPExcel->getActiveSheet()->getCell("A9")->getValue();
        $encC2= $objPHPExcel->getActiveSheet()->getCell("B9")->getValue();
        $encC3= $objPHPExcel->getActiveSheet()->getCell("C9")->getValue();
        $encC4= $objPHPExcel->getActiveSheet()->getCell("D9")->getValue();
        $encC5= $objPHPExcel->getActiveSheet()->getCell("E9")->getValue();
        $encC6= $objPHPExcel->getActiveSheet()->getCell("F9")->getValue();
        $encC7= $objPHPExcel->getActiveSheet()->getCell("G9")->getValue();
        $encC8= $objPHPExcel->getActiveSheet()->getCell("H9")->getValue();
        $encC9= $objPHPExcel->getActiveSheet()->getCell("I9")->getValue();
        $encC10= $objPHPExcel->getActiveSheet()->getCell("J9")->getValue();
        $encC11= $objPHPExcel->getActiveSheet()->getCell("K9")->getValue();

        if( $encC1!="Fecha de Ingreso"){$validFile=0;echo "[Error Encabezado] Fecha de Ingreso<br>";}
        if( $encC2!="Hora (24hrs)"){$validFile=0;echo "[Error Encabezado] Hora (24hrs)<br>";}
        if( $encC3!="Num. Contenedor" ){$validFile=0;echo "[Error Encabezado] Num. Contenedor<br>";}
        if( $encC5!="CALIDAD" ){$validFile=0;echo "[Error Encabezado] CALIDAD <br>";}
        if( $encC7!="Ag. Aduanal / Transportista" ){$validFile=0;echo "[Error Encabezado] Ag. Aduanal / Transportista <br>";}
        if( $encC8!="No.- EIR" ){$validFile=0;echo "[Error Encabezado] No.- EIR <br>";}
        if( $encC9!="Maniobra por cuenta de Merchant / carrier" ){$validFile=0;echo "[Error Encabezado] Maniobra por cuenta de Merchant / carrier<br>";}
        if( $encC10!="I_E" ){$validFile=0;echo "[Error Encabezado] I_E <br>";}
        if( $encC11!="Tipo_Ingreso" ){$validFile=0;echo "[Error Encabezado] Tipo_Ingreso <br>";}
        // ---- Fin de la validación ----

        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $array_data = array();
        foreach($rowIterator as $row){
            $lin = $row->getRowIndex ();
            //echo "fila ".$row->getRowIndex ();
            //echo "<br>";
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            //if(1 == $row->getRowIndex ()) continue; //skip first row la del encabezado
            $rowIndex = $row->getRowIndex ();
            //$array_data[$rowIndex] = array('A'=>'', 'B'=>'','C'=>'','D'=>'','E'=>'');

            foreach ($cellIterator as $cell) {
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn() ){
                    $fecha = $cell->getFormattedValue();
                    $fecha = str_replace("/","-",$fecha);
                    // Validar Fecha : En caso de que los formatos sean 01-mes-15
                    unset($parts);
                    if( preg_match("/(\d{1,2})-(\w{3,3})-(\d{2,2})/i",$fecha,$parts) ){
                        // echo "FECHA:  $fecha OK <br>";
                        $dd = $parts[1];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $mes = $parts[2];
                        $aa = 2000 + $parts[3];
                        $mm = getOkMes($mes);
                        $fecha = $aa."-".$mm."-".$dd;
                    }

                    if( $lin>9 && !empty($fecha) && ( !preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha)  ) ){
                        echo "[Error] Lin: $lin | Fecha : $fecha | El formato es incorrecto, cambie el formato como -> aaaa-mm-dd  <br>";
                        $validFile=0;
                    }
                    $datos[$rowIndex]['FECHA'] = $fecha;
                }
                if('B' == $cell->getColumn()){
                    $hora = $cell->getFormattedValue();
                    unset($parts);
                    if( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) PM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        if( $hh<12 ){
                            $hh = $hh + 12;
                        }
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) AM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/^(\d{1,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }

                    $datos[$rowIndex]['HORA'] = $hora;
                }
                /*// Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn()){
                    $datos[$rowIndex]['FECHAHORA'] = $cell->getValue();
                    if( $datos[$rowIndex]['FECHAHORA']=="Fecha y hora Ingreso" ){
                        $rowStar=$rowIndex;
                    }
                }
                */
                if('C' == $cell->getColumn()){
                    $conte = $cell->getValue();
                    $conte = strtoupper($conte);
                    $datos[$rowIndex]['CONTENEDOR'] = $conte;
                }
                if('D' == $cell->getColumn()){
                    $datos[$rowIndex]['EQUIPO'] = $cell->getValue();
                }
                if('H' == $cell->getColumn()){
                    $datos[$rowIndex]['EIR'] = $cell->getValue();
                }
                if('I' == $cell->getColumn()){
                    $datos[$rowIndex]['MANIOBRA'] = $cell->getValue();
                }
                if('G' == $cell->getColumn()){
                    $datos[$rowIndex]['TRANSPORTISTA'] = $cell->getValue();
                }
                if('J' == $cell->getColumn()){
                    $datos[$rowIndex]['I_E'] = $cell->getValue();
                }
                if('K' == $cell->getColumn()){
                    $datos[$rowIndex]['TIPO_INGRESO'] = $cell->getValue();
                }
            }
        }

        // ------------------------------------------------
        // Validar datos despues del encabezado.
        // ------------------------------------------------
        $l=1;
        foreach ($datos as $dato => $x) {
            if( $l>9 ) {
                $validFec=0;
                if( !empty($x['CONTENEDOR']) ){
                    $tIngresoT = $x['TIPO_INGRESO'];
                    $tIngresoT = strtoupper($tIngresoT);
                    if( $tIngresoT!='GC' && $tIngresoT!='FG' && $tIngresoT!='DG' ){
                        echo "<b><font color=red>[ERROR]</b> Linea $l : Tipo de Ingreso no especificado (GC/FG/DG)...<br></font>";
                        $validFile="False";
                    }
                }
            }
            $l++;
        }


        if( $validFile=="True" ) {
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $flgRec = 1;

            $l = 1;
            $f = date(Ymd);

            // ------------------------------------------------
            // Generar archivo EDI
            // ------------------------------------------------
            $fileEDI = "../edi_files/edi/TRANE_HSD_GATE_IN".$fecD1.$fecD2.".edi";
            $fileName = "TRANE_HSD_GATE_IN".$fecD1.$fecD2.".edi";
            $fp2 = fopen("$fileEDI", "w");
            $codPatio = getValueTable("cod_patio", "USUARIO", "id_usuario", $sesIdUsuario);
            $codPatioName = getValueTable("cod_patio_name", "USUARIO", "id_usuario", $sesIdUsuario);
            //$senderID = getValueTable("sender_id", "USUARIO", "id_usuario", $sesIdUsuario);
            //$receiverID = "HSUD";
            
            // ENCABEZADO
            // // MENSAJES CODIFICACION
            // 872 = Return empty container to facility / Regreso de vacio a la instalacón.
            // 135 = Empty container from facility to customer
            //$unbCodFin = 872;
            $unbCodFin = 135;

            $sepa = "\r\n";
            $lin= "UNB+UNOA:2+MTYMA+HSUD+$fecD1:$fecD2+$unbCodFin'\r\n";
            fputs($fp2, $lin);
            $tlSegmentos++;
            $lin= "UNH+1+CODECO:D:95B:UN:ITG14'".$sepa;
            fputs($fp2, $lin);
            $tlSegmentos++;
            $lin= "BGM+34+$unbCodFin+9'".$sepa;
            fputs($fp2, $lin);
            $tlSegmentos++;
            // Ej, NAD+CF+HSD:172'  // Tal vez el 172 cambie
            $lin= "NAD+MS+MTYMA:172:20'\r\n";
            fputs($fp2, $lin);
            $tlSegmentos++;
            
            $tlConte = 0;
            // -------------------------------------------------
            // CICLO POR CONTENEDOR. OBTENIENDO LA INFO POR CADA CONTENEDOR
            // -------------------------------------------------
            $l=1;
            foreach ($datos as $dato => $x) {

                if( $l>9 ) {
                    $validFec=0;
                    $fecha = $x['FECHA'];
                    if( preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha,$parts) ){
                        $d= $parts[3];
                        $m= $parts[2];
                        $a= $parts[1];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }

                    $hora = $x['HORA'];
                    $hora = strtolower($hora);
                    $hora = str_replace(" ","",$hora);
                    $hora = str_replace(":","",$hora);
                    $hora = str_replace("-","",$hora);
                    $hora = str_replace(".","",$hora);
                    $hora = str_replace("pm","",$hora);
                    $hora = str_replace("am","",$hora);
                    $hora = str_pad($hora,4,"0", STR_PAD_LEFT);
                    $regHora = $hora;

                    $conte = $x['CONTENEDOR'];
                    $conte = strtoupper($conte);
                    $conte = str_replace("-","",$conte);
                    $conte = str_replace("/","",$conte);
                    $conte = str_replace(" ","",$conte);
                    $equipo = $x['EQUIPO'];
                    $tipo = getEquipoTipo($equipo);
                    $eir = $x['EIR'];
                    if(!empty($eir)){
                        $eir="EIR: $eir ";
                    }
                    $maniobra = $x['MANIOBRA'];
                    $transportista = $x['TRANSPORTISTA'];
                    $impoExpo = $x['I_E'];
                    $tipoIngreso = $x['TIPO_INGRESO'];

                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;

                        // ---------------------------------------------------------
                        // COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                        // ----------------------------------------------------------
                        ($impoExpo=="E")?$stIE=2:$stIE=3;
                        ($stConte=="E")?$stCo=4:$stCo=5;
                        $conte = strtoupper($conte);                        
                        $lin= "EQD+CN+$conte+$tipo:102:5++$stIE+$stCo'".$sepa;
                        fputs($fp2,$lin);
                        $tlSegmentos++;
                        $lin="DTM+7:$regFecha$regHora:203'".$sepa;
                        fputs($fp2,$lin);
                        $tlSegmentos++;                        
                        // Pendiente de agregar en plantilla-> DAMAGE = OK / DM 
                        if($tipoIngreso=="DG"){
                            $damage="DM";
                        }
                        else{
                            $damage="OK";
                        }                        
                        //$lin = "FTX+DAR++$damage::184'".$sepa;
                        //fputs($fp2, $lin);
                        //$tlSegmentos++;
                        // Pendiente de agregar a la plantilla.
                        // Tipo de ingreso : GC = General Cargo, FG = Food Grade, DG = Dañado.
                        $lin = "FTX+DAR+++$tipoIngreso'".$sepa;
                        fputs($fp2, $lin);
                        $tlSegmentos++;

                        if( $damage=="DM" || $tipoIngreso=="DG" ) {
                            // ----- DAÑADOS Inicia Segmento -----
                            // Ej. 3 lineas en caso de ingresar dañados.
                            // FTX+DAR++OK:130:184'
                            // FTX+DAR+++DG'
                            // DAM+1+98:::Para Ser Limpiados'
                            // --------------------------------
                            // $lin = "FTX+DAR++$damage:ZZZ:184'\r\n";
                            // fputs($fp2, $lin);
                            // $tlSegmentos++;
                            // $lin = "FTX+DAR+++$claseHS'\r\n";
                            // fputs($fp2, $lin);
                            // $tlSegmentos++;
                            // $lin = "DAM+1+98:::$danoNota'\r\n";
                            $lin = "DAM+1'".$sepa;
                            fputs($fp2, $lin);
                            $tlSegmentos++;
                            // --------- DAÑADOS Finaliza Segmento ----------
                        }
                        $placas = str_replace(".", "", $placas);
                        if( empty($placas) ){
                            $placas="";
                        }                    
                        $operador = normaliza($operador);
                        //$lin= "TDT+30++3++:::$transportista-$operador++$placas'\r\n";
                        $operador = substr($operador, 0,35);
                        $placas = str_replace("-", "", $placas);
                        $placas = str_replace(".", "", $placas);
                        $placas = str_replace(" ", "", $placas);                    
                        $lin= "TDT+1++3+++++$placas'".$sepa;
                        fputs($fp2, $lin);
                        $tlSegmentos++;
                        $flgOkFile=1;
                    }
                }
                $l++;
            }

             $lin = "CNT+16:1'\r\n";
            fputs($fp2, $lin);
            $tlSegmentos++;
            $lin = "UNT+$tlSegmentos+1'\r\n";
            fputs($fp2, $lin);
            $lin = "UNZ+1+$unbCodFin'";
            fputs($fp2, $lin);
            fclose($fp2);
            
            unset($rowIterator);
            unset($datos);

            
            if( $flgOkFile==1) {
                if ($opcPro == "email") {
                    // sendNotifyOneFile($sesIdUsuario, 'HAMBURG SUD', $fileEDI, $fileName);
                }
                elseif ($opcPro == "ftp") {
                    sentToFTP($fileEDI,$idNav,'/from/');
                }
                return $fileEDI;
            }
            

        }

    }
    // ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateOutHSExcelTrane($stConte,$sesIdUsuario,$idNav,$opcPro){
        // Hamburg Sud
        // EXCEL
        // ---------------------------------------------------
        // PROCESO COPIAR ARCHIVO
        // ---------------------------------------------------
        if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
            copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/salidasTrane.xlsx");
        }
        else {
            echo "Error de archivo!.";
        }

        // -------------------------------------
        // GET-IN
        // -------------------------------------
        unset($fp);
        unset($data);
        unset($dataX);


        // ---------------------------------
        // ---- LEER ARCHIVO DE EXCEL ---
        // ---------------------------------
        $objReader = new PHPExcel_Reader_Excel2007();
        //$objReader = new PHPExcel_Reader_Excel5();
        $objPHPExcel = $objReader->load('../edi_files/csv/salidasTrane.xlsx');
        $objPHPExcel->setActiveSheetIndex(1);


        $validFile="True";
        $l=1;

        // ---------------------------------
        // Validar el encabezado
        // ----------------------------------
        $encC1= $objPHPExcel->getActiveSheet()->getCell("A9")->getValue();
        $encC2= $objPHPExcel->getActiveSheet()->getCell("B9")->getValue();
        $encC3= $objPHPExcel->getActiveSheet()->getCell("C9")->getValue();
        $encC4= $objPHPExcel->getActiveSheet()->getCell("D9")->getValue();
        $encC5= $objPHPExcel->getActiveSheet()->getCell("E9")->getValue();
        $encC6= $objPHPExcel->getActiveSheet()->getCell("F9")->getValue();
        $encC7= $objPHPExcel->getActiveSheet()->getCell("G9")->getValue();
        $encC8= $objPHPExcel->getActiveSheet()->getCell("H9")->getValue();
        $encC9= $objPHPExcel->getActiveSheet()->getCell("I9")->getValue();
        $encC10= $objPHPExcel->getActiveSheet()->getCell("J9")->getValue();
        $encC11= $objPHPExcel->getActiveSheet()->getCell("K9")->getValue();
        $encC12= $objPHPExcel->getActiveSheet()->getCell("L9")->getValue();

        if( $encC1!="Fecha de Ingreso"){$validFile=0;echo "[Error Encabezado] Fecha de Ingreso<br>";}
        if( $encC2!="Hora (24hrs)" ){$validFile=0;echo "[Error Encabezado] Hora (24hrs)<br>";}
        if( $encC3!="Num. Contenedor"){$validFile=0;echo "[Error Encabezado] Num. Contenedor<br>";}
        //if( $encC4!="TIPO/TAMAÑO" ){$validFile=0;echo "[Error Encabezado] TIPO/TAMAÑO<br>";}
        if( $encC5!="Booking" ){$validFile=0;echo "[Error Encabezado] Booking <br>";}
        if( $encC6!="Cliente" ){$validFile=0;echo "[Error Encabezado] Cliente <br>";}
        if( $encC7!="Transportista" ){$validFile=0;echo "[Error Encabezado] Transportista <br>";}
        if( $encC8!="CALIDAD" ){$validFile=0;echo "[Error Encabezado] CALIDAD<br>";}
        if( $encC9!="No.- EIR" ){$validFile=0;echo "[Error Encabezado] No.- EIR <br>";}
        if( $encC10!="Sello Salida" ){$validFile=0;echo "[Error Encabezado] Sello Salida <br>";}
        if( $encC11!="Maniobra por cuenta de Merchant  //  carrier" ){$validFile=0;echo "[Error Encabezado] Maniobra por cuenta de Merchant  //  carrier <br>";}
        if( $encC12!="I_E" ){$validFile=0;echo "[Error Encabezado] I_E <br>";}
        // ---- Fin de la validación ----

        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $array_data = array();
        foreach($rowIterator as $row){
            $lin = $row->getRowIndex ();
            //echo "fila ".$row->getRowIndex ();
            //echo "<br>";
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            //if(1 == $row->getRowIndex ()) continue; //skip first row la del encabezado
            $rowIndex = $row->getRowIndex ();
            //$array_data[$rowIndex] = array('A'=>'', 'B'=>'','C'=>'','D'=>'','E'=>'');

            foreach ($cellIterator as $cell) {
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn() ){
                    $fecha = $cell->getFormattedValue();
                    $fecha = str_replace("/","-",$fecha);
                    // Validar Fecha : En caso de que los formatos sean 01-mes-15
                    unset($parts);
                    if( preg_match("/(\d{1,2})-(\w{3,3})-(\d{2,2})/i",$fecha,$parts) ){
                        // echo "FECHA:  $fecha OK <br>";
                        $dd = $parts[1];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $mes = $parts[2];
                        $aa = 2000 + $parts[3];
                        $mm = getOkMes($mes);
                        $fecha = $aa."-".$mm."-".$dd;
                    }

                    if( $lin>9 && !empty($fecha) && ( !preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha)  ) ){
                        echo "[Error] Lin: $lin | Fecha : $fecha | El formato es incorrecto, cambie el formato como -> aaaa-mm-dd  <br>";
                        $validFile=0;
                    }
                    $datos[$rowIndex]['FECHA'] = $fecha;
                }
                if('B' == $cell->getColumn()){
                    $hora = $cell->getFormattedValue();
                    unset($parts);
                    if( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) PM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        if( $hh<12 ){
                            $hh = $hh + 12;
                        }
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) AM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/^(\d{1,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    $datos[$rowIndex]['HORA'] = $hora;
                }

                if('C' == $cell->getColumn()){
                    $conte = $cell->getValue();
                    $conte = strtoupper($conte);
                    $datos[$rowIndex]['CONTENEDOR'] = $conte;
                }
                if('D' == $cell->getColumn()){
                    $datos[$rowIndex]['EQUIPO'] = $cell->getValue();
                }
                if('E' == $cell->getColumn()){
                    $datos[$rowIndex]['BOOKING'] = $cell->getValue();
                }
                if('F' == $cell->getColumn()){
                    $datos[$rowIndex]['CLIENTE'] = $cell->getValue();
                }
                if('G' == $cell->getColumn()){
                    $datos[$rowIndex]['TRANSPORTISTA'] = $cell->getValue();
                }
                if('I' == $cell->getColumn()){
                    $datos[$rowIndex]['EIR'] = $cell->getValue();
                }
                if('J' == $cell->getColumn()){
                    $datos[$rowIndex]['SELLO'] = $cell->getValue();
                }
                if('K' == $cell->getColumn()){
                    $datos[$rowIndex]['MANIOBRA'] = $cell->getValue();
                }
                if('L' == $cell->getColumn()){
                    $datos[$rowIndex]['I_E'] = $cell->getValue();
                }

            }
        }

        if( $validFile=="True" ) {
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $flgRec = 1;

            $l = 1;
            $f = date(Ymd);

            // ------------------------------------------------
            // Generar archivo EDI
            // ------------------------------------------------
            $fileEDI = "../edi_files/edi/TRANE_HSD_GATE_OUT".$fecD1.$fecD2.".edi";
            $fileName = "TRANE_HSD_GATE_OUT".$fecD1.$fecD2.".edi";
            $fp2 = fopen("$fileEDI", "w");
            $sepa = "\r\n";
            $codPatio = getValueTable("cod_patio", "USUARIO", "id_usuario", $sesIdUsuario);
            $codPatioName = getValueTable("cod_patio_name", "USUARIO", "id_usuario", $sesIdUsuario);
            //$senderID = getValueTable("sender_id", "USUARIO", "id_usuario", $sesIdUsuario);
            //$receiverID = "HSUD";
            
            // ENCABEZADO
            // MENSAJES CODIFICACION
            // 135 = Empty container from facility to customer
            $unbCodFin = "135";
            $lin= "UNB+UNOA:2+MTYMA+HSUD+$fecD1:$fecD2+$unbCodFin'\r\n";
            fputs($fp2, $lin);
            $tlSegmentos++;
            $lin= "UNH+1+CODECO:D:95B:UN:ITG14'\r\n";
            fputs($fp2, $lin);
            $tlSegmentos++;
            $lin= "BGM+36+$unbCodFin+9'\r\n";
            fputs($fp2, $lin);
            $tlSegmentos++;
            $lin= "NAD+MS+MTYMA:172:20'\r\n";
            fputs($fp2, $lin);
            $tlSegmentos++;          
            $tlConte = 0;


            // -------------------------------------------------
            // OBTENIENDO LA INFO
            // -------------------------------------------------
            $l=1;
            foreach ($datos as $dato => $x) {

                if( $l>9 ) {
                    $validFec=0;

                    $fecha = $x['FECHA'];
                    if( preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha,$parts) ){
                        $d= $parts[3];
                        $m= $parts[2];
                        $a= $parts[1];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }

                    $hora = $x['HORA'];
                    $hora = strtolower($hora);
                    $hora = str_replace(" ","",$hora);
                    $hora = str_replace(":","",$hora);
                    $hora = str_replace("-","",$hora);
                    $hora = str_replace(".","",$hora);
                    $hora = str_replace("pm","",$hora);
                    $hora = str_replace("am","",$hora);
                    $hora = str_pad($hora,4,"0", STR_PAD_LEFT);
                    $regHora = $hora;

                    $conte = $x['CONTENEDOR'];
                    $conte = strtoupper($conte);
                    $conte = str_replace("-","",$conte);
                    $conte = str_replace("/","",$conte);
                    $conte = str_replace(" ","",$conte);

                    $equipo = $x['EQUIPO'];
                    $tipo = getEquipoTipo($equipo);
                    $eir = $x['EIR'];
                    $maniobra = $x['MANIOBRA'];
                    $transportista = $x['TRANSPORTISTA'];
                    $cliente = $x['CLIENTE'];
                    $cliente = strtoupper($cliente);
                    $bkgNumber = $x['BOOKING'];
                    $bkgNumber = strtoupper($bkgNumber);
                    $bl = $x['BL'];
                    $bl = strtoupper($bl);
                    $sello = $x[SELLO];
                    $impoExpo = $x[I_E];

                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;

                        // ---------------------------------------------------------
                        // COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                        // ----------------------------------------------------------
                        // Raul de HS lo interpreta asi :  2 Vacio 4 al Embarcador.
                        //($impoExpo=="E")?$stIE=2:$stIE=3;
                        //($stConte=="E")?$stCo=4:$stCo=5;

                        //$lin= "EQD+CN+$conte+$tipo:102:5++$stIE+$stCo'".$sepa;
                        $lin= "EQD+CN+$conte+$tipo:102:5++2+4'".$sepa;
                        fputs($fp2, $lin);
                        $tlSegmentos++;
                        
                        if (!empty($bkgNumber)) {
                            $bkgNumber = str_replace(" ", "", $bkgNumber);
                            $bkgNumber = str_replace(".", "", $bkgNumber);
                            $bkgNumber = str_replace("/", "", $bkgNumber);
                            $bkgNumber = str_replace("-", "", $bkgNumber);
                            $lin= "RFF+BN:$bkgNumber'\r\n";
                            fputs($fp2, $lin);
                            $tlSegmentos++;
                        }
                        
                        $lin= "DTM+7:$regFecha$regHora:203'\r\n";
                        fputs($fp2, $lin);
                        $tlSegmentos++;
                        // Pendiente, analizar si tiene lugar para los sellos la plantilla.
                        $lin="SEL+$sello+CA'\r\n";
                        fputs($fp2, $lin);
                        $tlSegmentos++;
                       
                        $placas = str_replace(".", "", $placas);
                        if( empty($placas) ){
                            $placas="";
                        }
                        $operador = normaliza($operador);
                        $operador = substr($operador, 0,35);
                        $placas = str_replace("-", "", $placas);
                        $placas = str_replace(".", "", $placas);
                        $placas = str_replace(" ", "", $placas);                    
                        $lin= "TDT+1++3+++++$placas'\r\n";
                        fputs($fp2, $lin);
                        $tlSegmentos++;
                        $flgOkFile=1;
                    }

                }
                $l++;
            }

            $lin = "CNT+16:1'\r\n";
            fputs($fp2, $lin);
            $tlSegmentos++;
            $lin = "UNT+$tlSegmentos+1'\r\n";
            fputs($fp2, $lin);
            $lin = "UNZ+1+$unbCodFin'";
            fputs($fp2, $lin);
            // Cierre de archivo.
            fclose($fp2);
            unset($rowIterator);
            unset($datos);


            if( $flgOkFile==1 ) {
                if ($opcPro == "email") {
                    // sendNotifyOneFile($sesIdUsuario, 'HAMBURG SUD', $fileEDI, $fileName);
                }
                elseif ($opcPro == "ftp") {
                    sentToFTP($fileEDI,$idNav,'/from/');
                }
                return $fileEDI;
            }
        }

    }

    // ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateInHLExcel($stConte,$sesIdUsuario,$formato,$idNav,$opcPro){


        if( $formato=="excel2007" ) {
            // EXCEL 2010 >
            // ---------------------------------------------------
            // HL
            // ---------------------------------------------------
            if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/entradas$sesIdUsuario.xlsx");
            } else {
                echo "Error de archivo!.";
            }
            // -------------------------------------
            // GET-IN
            // -------------------------------------
            unset($fp);
            unset($data);
            unset($dataX);
            // ---------------------------------
            // ---- LEER ARCHIVO DE EXCEL ---
            // ---------------------------------
            //$objReader = new PHPExcel_Reader_Excel5();
            $objReader = new PHPExcel_Reader_Excel2007();
            $objPHPExcel = $objReader->load("../edi_files/csv/entradas$sesIdUsuario.xlsx");
            $objPHPExcel->setActiveSheetIndex(0);
        }
        elseif( $formato=="excel2005" ) {
            // EXCEL 2005 >
            // ---------------------------------------------------
            // HL
            // ---------------------------------------------------
            if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/entradas$sesIdUsuario.xls");
            } else {
                echo "Error de archivo!.";
            }
            // -------------------------------------
            // GET-IN
            // -------------------------------------
            unset($fp);
            unset($data);
            unset($dataX);
            // ---------------------------------
            // ---- LEER ARCHIVO DE EXCEL ---
            // ---------------------------------
            $objReader = new PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader->load("../edi_files/csv/entradas$sesIdUsuario.xls");
            $objPHPExcel->setActiveSheetIndex(0);

        }

        $validFile="True";
        $l=1;

        // ------------------
        // Validacion campos
        // ------------------
        // ---------------------------------
        // Validar el encabezado
        // ----------------------------------
        $encC1= $objPHPExcel->getActiveSheet()->getCell("A5")->getValue();
        $encC2= $objPHPExcel->getActiveSheet()->getCell("B5")->getValue();
        $encC3= $objPHPExcel->getActiveSheet()->getCell("C5")->getValue();
        $encC4= $objPHPExcel->getActiveSheet()->getCell("D5")->getValue();
        $encC5= $objPHPExcel->getActiveSheet()->getCell("E5")->getValue();
        $encC6= $objPHPExcel->getActiveSheet()->getCell("F5")->getValue();
        $encC7= $objPHPExcel->getActiveSheet()->getCell("G5")->getValue();

        if( $encC1!="FECHA"){$validFile=0;echo "[Error Encabezado] <br>";}
        if( $encC2!="HORA" ){$validFile=0;echo "[Error Encabezado] HORA<br>";}
        if( $encC3!="CONTENEDOR"){$validFile=0;echo "[Error Encabezado] CONTENEDOR<br>";}
        if( $encC4!="TIPO" ){$validFile=0;echo "[Error Encabezado] TIPO<br>";}
        if( $encC5!="EIR" ){$validFile=0;echo "[Error Encabezado] EIR<br>";}
        if( $encC6!="MANIOBRA" ){$validFile=0;echo "[Error Encabezado] MANIOBRA<br>";}
        if( $encC7!="TRANSPORTISTA" ){$validFile=0;echo "[Error Encabezado] TRANSPORTISTA<br>";}
        // ---- Fin de la validación ----

        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        foreach($rowIterator as $row){
            $lin = $row->getRowIndex ();
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            $rowIndex = $row->getRowIndex ();

            foreach ($cellIterator as $cell) {
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn() ){
                    $fecha = $cell->getFormattedValue();
                    // Validar Fecha : En caso de que los formatos sean 01-mes-15
                    unset($parts);
                    if( preg_match("/(\d{1,2})-(\w{3,3})-(\d{2,2})/i",$fecha,$parts) ){
                        // echo "FECHA:  $fecha OK <br>";
                        $dd = $parts[1];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $mes = $parts[2];
                        $aa = 2000 + $parts[3];
                        $mm = getOkMes($mes);
                        $fecha = $aa."-".$mm."-".$dd;
                    }

                    if( $lin>5 && !empty($fecha) && ( !preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha)  ) ){
                        echo "[Error] Lin: $lin | Fecha : $fecha | El formato es incorrecto, cambie el formato como -> aaaa-mm-dd  <br>";
                        $validFile=0;
                    }
                    $datos[$rowIndex]['FECHA'] = $fecha;
                }
                if('B' == $cell->getColumn()){
                    $hora = $cell->getFormattedValue();
                    unset($parts);
                    if( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) PM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        if( $hh<12 ){
                            $hh = $hh + 12;
                        }
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) AM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/^(\d{1,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    $datos[$rowIndex]['HORA'] = $hora;

                }
                if('C' == $cell->getColumn()){
                    $conte = $cell->getValue();
                    $conte = strtoupper($conte);
                    $datos[$rowIndex]['CONTENEDOR'] = $conte;
                }
                if('D' == $cell->getColumn()){
                    $datos[$rowIndex]['EQUIPO'] = $cell->getValue();
                }
                if('E' == $cell->getColumn()){
                    $datos[$rowIndex]['EIR'] = $cell->getValue();
                }
                if('F' == $cell->getColumn()){
                    $datos[$rowIndex]['MANIOBRA'] = $cell->getValue();
                }
                if('G' == $cell->getColumn()){
                    $datos[$rowIndex]['TRANSPORTISTA'] = $cell->getValue();
                }

            }
        }

        if( $validFile=="True" ) {
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $flgRec = 1;

            $l = 1;
            $f = date(Ymd);

            // ------------------------------------------------
            // Generar archivo EDI
            // ------------------------------------------------
            $fileEDI = "../edi_files/edi/".$sesIdUsuario."HL_GATE_IN".$fecD1.$fecD2.".edi";
            $fileName = $sesIdUsuario."HL_GATE_IN".$fecD1.$fecD2.".edi";
            $fp2 = fopen("$fileEDI", "w");
            $sepa = "\r\n";
            $codPatio = getValueTable("cod_patio", "USUARIO", "id_usuario", $sesIdUsuario);
            $codPatioName = getValueTable("cod_patio_name", "USUARIO", "id_usuario", $sesIdUsuario);
            $oficina = getValueTable("oficina", "USUARIO", "id_usuario", $sesIdUsuario);

            // ----------------------------
            // Sender & Reciber Code
            // ----------------------------
            if( $sesIdUsuario==12 ){
                // Terraports-Mexico
                $senderID="TRATER005";
                $codPatioName = $senderID;
            }
            elseif( $sesIdUsuario==13 ){
                // Terraports-Veracruz
                $senderID="TRATER004";
                $codPatioName = $senderID;
            }
            else{
                $senderID = getValueTable("sender_id", "USUARIO", "id_usuario", $sesIdUsuario);
            }
            $receiverID="HLCU";

            $nad = "NAD+CF+HSD:172'";  // 173 = Carrier code.
            //$locCode = "MXMTY"; // Lugar del patio
            $locCode = getValueTable("loc_code","USUARIO","id_usuario",$sesIdUsuario);  // Lugar del patio
            $razonSocial = getValueTable("razon_social","USUARIO","id_usuario",$sesIdUsuario);  // Lugar del patio
            $razonSocial = strtoupper($razonSocial);

            // ENCABEZADO
            $enc= "UNB+UNOA:1+$senderID+$receiverID+$fecD1:$fecD2+$fecD1$fecD2'".$sepa;
            $enc.= "UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'".$sepa;
            $enc.= "BGM+34+$fecD1$fecD2+9'".$sepa;
            $enc.=$nad.$sepa;
            $tlConte=0;
            $tlSegmentos =4;
            $flgLoc165 = true;
            fputs($fp2,$enc);

            // -------------------------------------------------
            // OBTENIENDO LA INFO
            // -------------------------------------------------
            $l=1;
            foreach ($datos as $dato => $x) {

                if( $l>5 ) {
                    $validFec=0;
                    $fecha = $x['FECHA'];
                    /*
                    if( preg_match("/(\d{2})\/(\d{2})\/(\d{4})/i",$fecha,$parts) ){
                        $d= $parts[1];
                        $m= $parts[2];
                        $a= $parts[3];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }
                    */

                    if( preg_match("/(\d{4})-(\d{2})-(\d{2})$/i",$fecha,$parts) ){
                        $d= $parts[3];
                        $m= $parts[2];
                        $a= $parts[1];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }

                    $hora = $x['HORA'];
                    $hora = strtolower($hora);
                    $hora = str_replace(" ","",$hora);
                    $hora = str_replace(":","",$hora);
                    $hora = str_replace("-","",$hora);
                    $hora = str_replace(".","",$hora);
                    $hora = str_replace("pm","",$hora);
                    $hora = str_replace("am","",$hora);
                    //$hora = str_pad($hora,4,"0", STR_PAD_LEFT);
                    $regHora = $hora;
                    $conte = $x['CONTENEDOR'];
                    $equipo = $x['EQUIPO'];
                    $tipo = getEquipoTipo($equipo);
                    $eir = $x['EIR'];
                    if(!empty($eir)){
                        $eir="EIR: $eir ";
                    }
                    $maniobra = $x['MANIOBRA'];
                    $transportista = $x['TRANSPORTISTA'];

                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;

                        // ---------------------------------------------------------
                        // COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                        // ----------------------------------------------------------
                        //($impoExpo=="E")?$stIE=2:$stIE=3;
                        ($stConte=="E")?$stCo=4:$stCo=5;
                        $conte = strtoupper($conte);
                        $enc2= "EQD+CN+$conte+$tipo++3+$stCo'".$sepa;
                        $tlSegmentos++;
                        $enc2.="DTM+7:$regFecha$regHora:203'".$sepa;
                        $tlSegmentos++;
                        if( $flgLoc165 == true ){
                            // El LOC+165 solo se debe imprimir la primera vez. Segun HapagL.
                            $enc2.="LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'".$sepa;
                            $tlSegmentos++;
                            $flgLoc165 = false;
                        }
                        $enc2.="FTX+AAI+++$transportista $eir'".$sepa;
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'".$sepa;
                        $tlSegmentos++;
                        fputs($fp2,$enc2);
                        $flgOkFile=1;
                    }

                }
                $l++;
            }

            // Pie de pagina
            // Total de contenedores
            $pie="CNT+16:$tlConte'".$sepa;
            $tlSegmentos++;
            $pie.="UNT+$tlSegmentos+$fecD1$fecD2'".$sepa;
            $pie.="UNZ+1+$fecD1$fecD2'";

            //$enc33=utf8_encode($enc3);
            fputs($fp2,$pie);
            fclose($fp2);

            unset($rowIterator);
            unset($datos);

            
            if( $flgOkFile==1 ) {
                if ($opcPro == "ftp") {
                    sentToFTP($fileEDI,$idNav);
                }
                else if ($opcPro == "email") {
                    //sendNotifyOneFile($sesIdUsuario, 'HAPAG LOYD', $fileEDI, $fileName);
                }
                return $fileEDI;
            }
        }
    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateOutHLExcel($stConte,$sesIdUsuario,$formato,$idNav,$opcPro){

        if( $formato=="excel2007" ) {
            // EXCEL 2007 o mayor
            // ---------------------------------------------------
            // PROCESO COPIAR ARCHIVO
            // ---------------------------------------------------
            if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/salidas$sesIdUsuario.xlsx");
            } else {
                echo "Error de archivo!.";
            }
            // -------------------------------------
            // GET-IN
            // -------------------------------------
            unset($fp);
            unset($data);
            unset($dataX);
            // ---------------------------------
            // ---- LEER ARCHIVO DE EXCEL ---
            // ---------------------------------
            $objReader = new PHPExcel_Reader_Excel2007();
            //$objReader = new PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader->load("../edi_files/csv/salidas$sesIdUsuario.xlsx");
            $objPHPExcel->setActiveSheetIndex(1);
        }
        elseif( $formato=="excel2005" ) {
            // EXCEL 2005
            // ---------------------------------------------------
            // PROCESO COPIAR ARCHIVO
            // ---------------------------------------------------
            if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                copy($_FILES['userfile']['tmp_name'], "../edi_files/csv/salidas$sesIdUsuario.xls");
            }
            else {
                echo "Error de archivo!.";
            }
            // -------------------------------------
            // GET-IN
            // -------------------------------------
            unset($fp);
            unset($data);
            unset($dataX);
            // ---------------------------------
            // ---- LEER ARCHIVO DE EXCEL ---
            // ---------------------------------
            $objReader = new PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader->load("../edi_files/csv/salidas$sesIdUsuario.xls");
            $objPHPExcel->setActiveSheetIndex(1);
        }


        $validFile="True";
        $l=1;

        // ---------------------------------
        // Validar el encabezado
        // ----------------------------------
        $encC1= $objPHPExcel->getActiveSheet()->getCell("A5")->getValue();
        $encC2= $objPHPExcel->getActiveSheet()->getCell("B5")->getValue();
        $encC3= $objPHPExcel->getActiveSheet()->getCell("C5")->getValue();
        $encC4= $objPHPExcel->getActiveSheet()->getCell("D5")->getValue();
        $encC5= $objPHPExcel->getActiveSheet()->getCell("E5")->getValue();
        $encC6= $objPHPExcel->getActiveSheet()->getCell("F5")->getValue();
        $encC7= $objPHPExcel->getActiveSheet()->getCell("G5")->getValue();
        $encC8= $objPHPExcel->getActiveSheet()->getCell("H5")->getValue();
        $encC9= $objPHPExcel->getActiveSheet()->getCell("K5")->getValue();

        if( $encC1!="FECHA"){$validFile=0;echo "[Error Encabezado] FECHA<br>";}
        if( $encC2!="HORA" ){$validFile=0;echo "[Error Encabezado] HORA<br>";}
        if( $encC3!="CONTENEDOR" ){$validFile=0;echo "[Error Encabezado] CONTENEDOR<br>";}
        if( $encC4!="TIPO" ){$validFile=0;echo "[Error Encabezado] TIPO<br>";}
        if( $encC5!="EIR" ){$validFile=0;echo "[Error Encabezado] EIR<br>";}
        if( $encC6!="MANIOBRA" ){$validFile=0;echo "[Error Encabezado] MANIOBRA<br>";}
        if( $encC7!="TRANSPORTISTA" ){$validFile=0;echo "[Error Encabezado] TRANSPORTISTA<br>";}
        if( $encC8!="CLIENTE" ){$validFile=0;echo "[Error Encabezado] CLIENTE<br>";}
        if( $encC9!="BOOKING" ){$validFile=0;echo "[Error Encabezado] BOOKING<br>";}


        // ---- Fin de la validación ----

        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $array_data = array();
        foreach($rowIterator as $row){
            $lin = $row->getRowIndex ();
            //echo "fila ".$row->getRowIndex ();
            //echo "<br>";
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            //if(1 == $row->getRowIndex ()) continue; //skip first row la del encabezado
            $rowIndex = $row->getRowIndex ();
            //$array_data[$rowIndex] = array('A'=>'', 'B'=>'','C'=>'','D'=>'','E'=>'');

            foreach ($cellIterator as $cell) {
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn() ){
                    $fecha = $cell->getFormattedValue();
                    // Validar Fecha : En caso de que los formatos sean 01-mes-15
                    unset($parts);
                    if( preg_match("/(\d{1,2})-(\w{3,3})-(\d{2,2})/i",$fecha,$parts) ){
                        // echo "FECHA:  $fecha OK <br>";
                        $dd = $parts[1];
                        $dd = str_pad($dd,2,"0", STR_PAD_LEFT);
                        $mes = $parts[2];
                        $aa = 2000 + $parts[3];
                        $mm = getOkMes($mes);
                        $fecha = $aa."-".$mm."-".$dd;
                    }

                    if( $lin>5 && !empty($fecha) && ( !preg_match("/(\d{4})-(\d{2})-(\d{2})/i",$fecha)  ) ){
                        echo "[Error] Lin: $lin | Fecha : $fecha | El formato es incorrecto, cambie el formato como -> aaaa-mm-dd  <br>";
                        $validFile=0;
                    }
                    $datos[$rowIndex]['FECHA'] = $fecha;
                }
                if('B' == $cell->getColumn()){
                    $hora = $cell->getFormattedValue();
                    unset($parts);
                    if( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) PM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        if( $hh<12 ){
                            $hh = $hh + 12;
                        }
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/(\d{1,2}):(\d{2,2}):(\d{2,2}) AM$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    elseif( preg_match("/^(\d{1,2}):(\d{2,2})$/",$hora,$parts) ){
                        $hh = $parts[1];
                        $hh = str_pad($hh,2,"0", STR_PAD_LEFT);
                        $mm = $parts[2];
                        $hora = $hh.$mm;
                    }
                    $datos[$rowIndex]['HORA'] = $hora;
                }
                if('C' == $cell->getColumn()){
                    $conte = $cell->getValue();
                    $conte = strtoupper($conte);
                    $datos[$rowIndex]['CONTENEDOR'] = $conte;
                }
                if('D' == $cell->getColumn()){
                    $datos[$rowIndex]['EQUIPO'] = $cell->getValue();
                }
                if('E' == $cell->getColumn()){
                    $datos[$rowIndex]['EIR'] = $cell->getValue();
                }
                if('F' == $cell->getColumn()){
                    $datos[$rowIndex]['MANIOBRA'] = $cell->getValue();
                }
                if('G' == $cell->getColumn()){
                    $datos[$rowIndex]['TRANSPORTISTA'] = $cell->getValue();
                }
                if('H' == $cell->getColumn()){
                    $datos[$rowIndex]['CLIENTE'] = $cell->getValue();
                }
                if('K' == $cell->getColumn()){
                    $datos[$rowIndex]['BOOKING'] = $cell->getValue();
                }
            }
        }

        if( $validFile=="True" ) {
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $flgRec = 1;

            $l = 1;
            $f = date(Ymd);

            // ------------------------------------------------
            // Generar archivo EDI
            // ------------------------------------------------
            $fileEDI = "../edi_files/edi/".$sesIdUsuario."HL_GATE_OUT".$fecD1.$fecD2.".edi";
            $fileName = $sesIdUsuario."HL_GATE_OUT".$fecD1.$fecD2.".edi";
            $fp2 = fopen("$fileEDI", "w");
            $sepa = "\r\n";
            $codPatio = getValueTable("cod_patio", "USUARIO", "id_usuario", $sesIdUsuario);
            $codPatioName = getValueTable("cod_patio_name", "USUARIO", "id_usuario", $sesIdUsuario);
            //$oficina = getValueTable("oficina", "USUARIO", "id_usuario", $sesIdUsuario);

            // ----------------------------
            // Sender & Reciber Code
            // ----------------------------
            if( $sesIdUsuario==12 ){
                // Terraports-Mexico
                $senderID="TRATER005";
                $codPatioName = $senderID;

            }
            elseif( $sesIdUsuario==13 ){
                // Terraports Veracruz
                $senderID="TRATER004";
                $codPatioName = $senderID;
            }
            else{
                $senderID = getValueTable("sender_id", "USUARIO", "id_usuario", $sesIdUsuario);
            }
            $receiverID="HLCU";

            $nad = "NAD+CF+HSD:172'";  // 173 = Carrier code.

            $locCode = getValueTable("loc_code","USUARIO","id_usuario",$sesIdUsuario);  // Lugar del patio
            $razonSocial = getValueTable("razon_social","USUARIO","id_usuario",$sesIdUsuario);  // Lugar del patio
            $razonSocial = strtoupper($razonSocial);

            // ENCABEZADO
            $enc = "UNB+UNOA:1+$senderID+$receiverID+$fecD1:$fecD2+$fecD1$fecD2'" . $sepa;
            $enc .= "UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'".$sepa;
            $enc .= "BGM+36+$fecD1$fecD2+9'".$sepa;
            $enc.= $nad.$sepa;
            fputs($fp2,$enc);

            $flgLoc165 = true;  // Bandera poder imprimir el "LOC+165" una vez. 1 = Activado.
            $tlConte = 0;
            $tlSegmentos = 4;

            // -------------------------------------------------
            // OBTENIENDO LA INFO
            // -------------------------------------------------
            $l=1;
            foreach ($datos as $dato => $x) {

                if( $l>5 ) {
                    $validFec=0;

                    $fecha = $x['FECHA'];
                    /*
                    if( preg_match("/(\d{2})\/(\d{2})\/(\d{4})/i",$fecha,$parts) ){
                        $d= $parts[1];
                        $m= $parts[2];
                        $a= $parts[3];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }
                    */
                    if( preg_match("/(\d{4})-(\d{2})-(\d{2})$/i",$fecha,$parts) ){
                        $d= $parts[3];
                        $m= $parts[2];
                        $a= $parts[1];
                        $regFecha= $a.$m.$d;
                        $validFec=1;
                    }

                    $hora = $x['HORA'];
                    $hora = strtolower($hora);
                    $hora = str_replace(" ","",$hora);
                    $hora = str_replace(":","",$hora);
                    $hora = str_replace("-","",$hora);
                    $hora = str_replace(".","",$hora);
                    $hora = str_replace("pm","",$hora);
                    $hora = str_replace("am","",$hora);
                    //$hora = str_pad($hora,4,"0", STR_PAD_LEFT);
                    $regHora = $hora;

                    $conte = $x['CONTENEDOR'];
                    $equipo = $x['EQUIPO'];
                    $tipo = getEquipoTipo($equipo);
                    // $eir = $x['EIR'];
                    $maniobra = $x['MANIOBRA'];
                    $transportista = $x['TRANSPORTISTA'];
                    $cliente = $x['CLIENTE'];
                    $cliente = strtoupper($cliente);
                    $bkgNumber = $x['BOOKING'];
                    $bkgNumber = strtoupper($bkgNumber);
                    $bl = $x['BL'];
                    $sello = $x[SELLO];
                    $impoExpo = $x[I_E];


                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;

                        //---------------------------------------------------------
                        // IMPOEXPO : 2 EXPORT ó 3 IMPORT
                        // TIPOMOV : 4 (E)MPTY Ó 5 (F)ULL
                        //----------------------------------------------------------
                        // FTX : Remarks +AAI = General Information
                        ($impoExpo=="E")?$stIE=2:$stIE=3;
                        ($stConte=="E")?$stCo=4:$stCo=5;
                        $conte = strtoupper($conte);
                        $enc2= "EQD+CN+$conte+$tipo++$stIE+$stCo'".$sepa;
                        $tlSegmentos++;
                        if( !empty($bl) ){
                            $enc2.= "RFF+BM:$bl'\r\n";
                            $tlSegmentos++;
                        }
                        if( !empty($bkgNumber) ) {
                            $enc2.= "RFF+BN:$bkgNumber'\r\n";
                            $tlSegmentos++;
                        }
                        $enc2.="DTM+7:".$regFecha.$regHora.":203'".$sepa;
                        if( $flgLoc165 == true ){
                            $tlSegmentos++;
                            // El LOC+165 solo se debe imprimir la primera vez. Segun HapagL.
                            $enc2.="LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'".$sepa;
                            $flgLoc165 = false;
                        }
                        $tlSegmentos++;
                        $enc2.="FTX+AAI+++$bkgNumber|$cliente|$transportista'".$sepa;
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'".$sepa;  // 3 : Equivale a Camion y 1. Es Tren.
                        $tlSegmentos++;
                        // Cuando sean salidas es necesario agregar esta línea para cada movimiento,
                        // ya que con este se especifica el Destination Depot.
                        $enc2.="LOC+99+$codPatio"."+::ZZZ'".$sepa;   // 99 : Place of empty equipment return
                        $tlSegmentos++;
                        fputs($fp2,$enc2);
                        $flgOkFile=1;

                    }

                }
                $l++;
            }

            // Pie de pagina
            // Total de contenedores
            $pie="CNT+16:$tlConte'".$sepa;
            $tlSegmentos++;
            $pie.="UNT+$tlSegmentos+$fecD1$fecD2'".$sepa;
            $pie.="UNZ+1+$fecD1$fecD2'";

            fputs($fp2,$pie);
            fclose($fp2);

            unset($rowIterator);
            unset($datos);


            if( $flgOkFile==1 ) {
                if ( $opcPro == "ftp" ) {
                    sentToFTP($fileEDI,$idNav);
                }
                else if ($opcPro == "email") {
                    //sendNotifyOneFile($sesIdUsuario, 'HAPAG LOYD', $fileEDI, $fileName);
                }
                return $fileEDI;
            }
        }
    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    // ----------------------------------------------------------------
    
    $modo = $_POST['modo'];
    $deli = $_POST['delimitador'];
    $idNav =  $_POST['idNav'];
    $fileType =  $_POST['fileType'];
    $impoExpo = $_POST['impoExpo'];
    $stConte = $_POST['stConte'];
    $opcPro = $_POST['opcPro'];
    $codOD = $_POST['codOD'];
    $sesIdUsuario = $_SESSION['sesIdUsuario'];
    $formato = $_POST['formato'];
    $encodeType = $_POST['encodeType'];
    $codPatio = $_POST['codPatio'];

    switch($modo){
        case "aceptar":

            // -------------------------------
            // VALIDACION
            // -------------------------------
            if( empty($idNav) ){
                $msg[]="<font color=red>[ ERROR ] NAVIERA no especificada.</font>";
            }
            /*
             if( empty($impoExpo) ){
                $msg[]="<font color=red>[ ERROR ] IMPO/EXPO no especificado.</font>";
            }
            */
            if( empty($stConte) ){
                $msg[]="<font color=red>[ ERROR ] STATUS CONTE no especificado.</font>";
            }
            if( empty($opcPro) ){
                $msg[]="<font color=red>[ ERROR ] TIPO DE ENVIO no especificado.</font>";
            }
            /*
            if( empty($encodeType) ){
                $msg[]="<font color=red>[ ERROR ] TIPO DE CODIFICACION no especificado.</font>";
            }
            */
            if( $sesIdUsuario==14 ){
                if( $codPatio=="-" ){
                    $msg[]="<font color=red>[ ERROR ] DEPOSITO no especificado.</font>";
                }
            }

            // --------------------------------
            if( count($msg)>0 ){
                showForm($arr_request,$msg);
            }
            else{
                if ($sesIdUsuario==17 ) {
                    // -------------------------------------
                    // USUARIO: ALPASA 
                    // -------------------------------------
                    if( $idNav=="1" ){
                        // MSC
                        $file1 = ediGateInMscExcelAlpasa($stConte,$sesIdUsuario,$idNav,$opcPro);
                        $file2 = ediGateOutMscExcelAlpasa($stConte,$sesIdUsuario,$idNav,$opcPro);
                        if( file_exists($file1) || file_exists($file2) ){
                            sendNotify2F($file1,$file2,'MSC',$opcPro);
                            if( file_exists($file1) ){ unlink($file1); }
                            if( file_exists($file2) ){ unlink($file2); }
                        }
                        else{
                            sendEmailError('MSC',$opcPro);
                        }
                    }

                }
                elseif( $sesIdUsuario==10 ){
                    // ----------------------------------------------------------------
                    // USUARIO: TRANE
                    // ----------------------------------------------------------------
                    if( $idNav=="1" ){
                        // MSC
                        $naviera = "MSC";
                        $file1 = ediGateInMscExcelTrane($stConte,$sesIdUsuario,$idNav,$opcPro);
                        $file2 = ediGateOutMscExcelTrane($stConte,$sesIdUsuario,$idNav,$opcPro);
                    }
                    elseif( $idNav=="3" ){
                        // MOL
                        $naviera = "MOL";
                        $file1 = ediGateInMOLExcelTrane($stConte,$sesIdUsuario,$idNav,$opcPro);
                        $file2 = ediGateOutMOLExcelTrane($stConte,$sesIdUsuario,$idNav,$opcPro);
                    }
                    elseif( $idNav=="4" ) {
                        // Hamburg Sud
                        $naviera = "HAMBURG SUD";
                        $file1= ediGateInHSExcelTrane($stConte, $sesIdUsuario, $idNav, $opcPro);
                        $file2= ediGateOutHSExcelTrane($stConte, $sesIdUsuario, $idNav, $opcPro);
                    }
                    elseif( $idNav=="5" ) {
                        // ZIM
                        $naviera = "ZIM";
                        $file1= ediGateInZIMExcelTrane($stConte, $sesIdUsuario, $idNav, $opcPro);
                        $file2= ediGateOutZIMExcelTrane($stConte, $sesIdUsuario, $idNav, $opcPro);
                    }

                    // AQUI : Enviar notificación.
                    if( file_exists($file1) || file_exists($file2) ){
                        sendNotify2F($file1,$file2,$naviera,$opcPro);
                        if( file_exists($file1) ){unlink($file1);}
                        if( file_exists($file2) ){unlink($file2);}
                    }
                    else{
                            sendEmailError($naviera,$opcPro);
                        }

                }
                elseif( $sesIdUsuario=='14' ){
                    // ----------------------------------------------------------------
                    // USUARIO: MSC GUAYMAS
                    // Nota : Sus envios solo son a MSC
                    // ----------------------------------------------------------------
                    if( $idNav=="1" ) {

                        if( $encodeType=="CODECO" ) {
                            // EDICODECO
                            ediGateInMscExcelGym($stConte,$sesIdUsuario, $formato, $idNav, $opcPro,$codPatio);
                            ediGateOutMscExcelGym($stConte,$sesIdUsuario, $formato, $idNav, $opcPro,$codPatio);
                            ediGatePosMscExcelGym($stConte,$sesIdUsuario, $formato, $idNav, $opcPro,$codPatio);
                        }
                        elseif( $encodeType=="COARRI" ){
                            // Experimental
                            // ediGateInMscExcelGymCoarri($stConte, $impoExpo, $sesIdUsuario, $formato, $idNav, $opcPro);
                        }
                    }
                }
                elseif( $sesIdUsuario=='19' ){
                    // ------------------------------
                    // USUARIO : ANAKOSTA
                    // ------------------------------
                    if( $idNav=="1" ){
                        // -------------
                        // MSC
                        // ------------- 
                        $file1= ediGateInMscExcelAnakosta($stConte,$sesIdUsuario,$idNav,$opcPro);
                        $file2= ediGateOutMscExcelAnakosta($stConte,$sesIdUsuario,$idNav,$opcPro);
                        $file3= ediGatePosMscExcelAnakosta($stConte,$sesIdUsuario,$idNav,$opcPro);

                        echo "file1: $file1 <br> file2: $file2 <br> file3: $file3 <br>";
                        if( file_exists($file1) || file_exists($file2) || file_exists($file3) ){
                            sendNotifyMSC($sesIdUsuario, $idNav,$file1,$file2,$file3,$opcPro);
                            if( file_exists($file1) ){ unlink($file1); }
                            if( file_exists($file2) ){ unlink($file2); }
                            if( file_exists($file3) ){ unlink($file3); }
                        }
                        else{
                            sendEmailError('MSC',$opcPro);
                        }
                    }
                }
                else {
                    // ----------------------------------------------------------------
                    // TODOS LOS DEMAS USUARIOS
                    // ----------------------------------------------------------------
                    if( $idNav=="1" ){
                        // -------------
                        // MSC
                        // -------------
                        $file1= ediGateInMscExcel($stConte,$sesIdUsuario,$formato,$idNav,$opcPro);
                        $file2= ediGateOutMscExcel($stConte,$sesIdUsuario,$formato,$idNav,$opcPro);
                        $file3= ediGatePosMscExcel($stConte,$sesIdUsuario, $formato, $idNav, $opcPro);

                        // Se omite que solo unos usarios recibieran notificación.
                        // Ahora todos recibiran la notificación con el adjunto aun cuanto lo manden por FTP.
                        //if( $sesIdUsuario=='2' || $sesIdUsuario=='12' || $sesIdUsuario=='13' || $sesIdUsuario=='19' ) {
                        //echo "file1: $file1 <br>file2: $file2 <br> file3: $file3 <br>";
                        if( file_exists($file1) || file_exists($file2) || file_exists($file3) ){
                            sendNotifyMSC($sesIdUsuario, $idNav,$file1,$file2,$file3,$opcPro);
                            if( file_exists($file1) ){ unlink($file1); }
                            if( file_exists($file2) ){ unlink($file2); }
                            if( file_exists($file3) ){ unlink($file3); }
                        }
                        else{
                            sendEmailError('MSC',$opcPro);
                        }   
                        
                    }
                    if( $idNav=="2" ){
                        // -------------
                        // HL
                        // -------------
                        $file1= ediGateInHLExcel($stConte,$sesIdUsuario,$formato,$idNav,$opcPro);
                        $file2= ediGateOutHLExcel($stConte,$sesIdUsuario,$formato,$idNav,$opcPro);
                        if( file_exists($file1) || file_exists($file2) ){
                            sendNotifyHL($sesIdUsuario, $idNav,$file1,$file2,$opcPro);
                            // Eliminar archivos para evitar basura.
                            if( file_exists($file1) ){ unlink($file1); }
                            if( file_exists($file2) ){ unlink($file2); }
                        }
                        else{
                            sendEmailError('Hapag-Lloyd',$opcPro);
                        }

                    }
                    if( $idNav=="3" ){
                        // -------------
                        // MOL (Mutsui O.S.K. Lines)
                        // -------------
                        $fileEDIE= ediGateInMOL($stConte,$sesIdUsuario,$formato,$idNav,$opcPro);
                        $fileEDIS= ediGateOutMOL($stConte,$sesIdUsuario,$formato,$idNav,$opcPro);
                        if( file_exists($fileEDIE) || file_exists($fileEDIS) ){
                            // Enviar notificacion con los 2 adjuntos.
                            sendNotify2F($fileEDIE,$fileEDIS,'MOL',$opcPro);
                            if( file_exists($file1) ){unlink($fileEDIE);}
                            if( file_exists($file1) ){unlink($fileEDIS);}
                        }
                        else{
                            sendEmailError('MOL',$opcPro);
                        }
                    }
                    if( $idNav=="4" ){
                        // -------------
                        // Hamburg Sud
                        // -------------
                        $file1 = ediGateInHS($stConte,$sesIdUsuario,$formato,$idNav,$opcPro);
                        $file2 = ediGateOutHS($stConte,$sesIdUsuario,$formato,$idNav,$opcPro);
                        if( file_exists($file1) || file_exists($file2) ){
                            sendNotify2F($file1,$file2,'Hamburg Sud',$opcPro);
                            // Eliminar archivos para evitar basura.
                            if( file_exists($file1) ){ unlink($file1); }
                            if( file_exists($file2) ){ unlink($file2); }
                        }
                        else{
                            sendEmailError('Hamburg Sud',$opcPro);
                        }
                    }

                }
                showForm($arr_request,$msg);
            }
            break;
        default:
            showForm();
            break;
    }

}
else{
    $t->set_file("page", "accesoDenegado.inc.html");
    $t->pparse("out","page");
}


?>