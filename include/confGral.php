<?php
// Todas los valores que se guardan en la variables Globales HTTP_GET_VARS y
// HTTP_POST_VARS se agrupan en un solo array $arr_request.
// Todas los valores que se guardan en la variables Globales HTTP_GET_VARS y
// HTTP_POST_VARS se agrupan en un solo array $arr_request.
if (count($_GET)){
    while (list($key, $value) = each ($_GET)){
        $arr_request[$key] = trim($value);
    }
}
if (count($_POST)){
    while (list($key, $value) = each ($_POST)){
        $arr_request[$key] = trim($value);
    }
}
include_once("db_mysqli.inc");
    /// Fecha actual.
$hoy= date("Y-m-d H:i:s");
$hoyF= date("Y-m-d");

$db = new DB_Sql;
$db->connect("nesoftwa_VGM", "localhost", "nesoftwa_root", ";L9Nehbfaxts");
$db2 = new DB_Sql;
$db2->connect("nesoftwa_VGM", "localhost", "nesoftwa_root", ";L9Nehbfaxts");
$db3 = new DB_Sql;
$db3->connect("nesoftwa_VGM", "localhost", "nesoftwa_root", ";L9Nehbfaxts");
$dbX = new DB_Sql;
$dbX->connect("nesoftwa_VGM","localhost", "nesoftwa_root", ";L9Nehbfaxts");
$dbf = new DB_Sql;
$dbf->connect("nesoftwa_VGM","localhost", "nesoftwa_root", ";L9Nehbfaxts");


function totalDias($fec1, $fec2) {
        // La funcion regresa el numero de dias transcurridos desde el
        // primer parametro al segundo.


        // Si tiene el fomato de fecha 9999-99-99, se tiene que convertir a "mktime".
    if (preg_match ( "/-/", $fec1 )) {
            // Fecha1
        $fec1MM = getFecha ( $fec1, 'mes' );
        $fec1DD = getFecha ( $fec1, 'dia' );
        $fec1AA = getFecha ( $fec1, 'ano' );
        $fec1TS = mktime ( 0, 0, 0, $fec1MM, $fec1DD, $fec1AA );
            // Fecha2
        $fec2MM = getFecha ( $fec2, 'mes' );
        $fec2DD = getFecha ( $fec2, 'dia' );
        $fec2AA = getFecha ( $fec2, 'ano' );
        $fec2TS = mktime ( 0, 0, 0, $fec2MM, $fec2DD, $fec2AA );                        
    }   
    $div = 60 * 60 * 24;
    $dias = (($fec2TS - $fec1TS) / $div + (0));
    if (is_double ( $dias )) {
            // Esto es porque existia un error que se comia un dia, debido a que el
            // total de dias arroja una cantidad fraccionaria ejemplo 4.98733664 Esto
            // debia ser 5 dias y decia que eran 4. Pero con saber si es double sumamos
            // 4 +1 = 5 y ya esta.
            //echo $dias."<br>";
        if (preg_match ( "/[0-9]{1,}\.([0-9]{2})/", $dias, $parts )) {
            $decimal = $parts [1];
                //echo "parts: ".$parts[1];
            if ($decimal > 50) {
                $dias += 1;
                    //echo "+1";
            }
        }

    }
    $dias = intval ( $dias );
    return $dias;
}
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
function getValueTable($campo,$tabla,$idTabla,$idEnviado){
    global $dbX;
    $sql="select $campo from $tabla where $idTabla='$idEnviado'";
    $dbX->query($sql);
    while($dbX->next_record()){
        $valor=$dbX->f($campo);
        return $valor;
    }
}
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff*/
function validaDate($date){
    if (isset($date)){
        $date=ereg_replace('-','', $date);
        $date=ereg_replace('/','', $date);

        $date_arr["year"]=substr($date,0,4);
        $date_arr["month"]=substr($date,4,2);
        $date_arr["day"]=substr($date,6,2);

            //echo "FECHANUEVA:::::". $date_arr[year] . $date_arr[month]. $date_arr[day]."<br>";

        if( checkdate($date_arr[month],$date_arr[day],$date_arr[year]) ){
            return true;			
        }
        else{
            return false;
        }
    }
    else{
        return false;
    }
}
    // ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
function getFecha($fecha,$op){

        // 2011-04-29 17:52:18

    switch($op){
        case "dia" :
        $x = substr($fecha,8,2);
        return $x;
        break;
        case "mes":
        $x = substr($fecha,5,2);
        return $x;
        break;
        case "ano":
        $x = substr($fecha,0,4);
        return $x;
        break;
        case "hor":
        $x = substr($fecha,11,2);
        return $x;
        break;
        case "min":
        $x = substr($fecha,14,2);
        return $x;
        break;
        case "seg":
        $x = substr($fecha,17,2);
        return $x;
        break;
    }
}
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
function genNewPassword(){

    $key = "";
    $caracteres = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        //aquí podemos incluir incluso caracteres especiales pero cuidado con las ‘ y “ y algunos otros
    $length = 10;
    $max = strlen($caracteres) - 1;
    for ($i=0;$i<$length;$i++) {
        $key .= substr($caracteres, rand(0, $max), 1);
    }
    return $key;
}

?>
