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
//include_once("../include/PHPExcel/Reader/Excel2007.php");

$usuario=new Acceso;
$t = new Template("../templates", "keep");
$sesIdUsuario = $_SESSION[sesIdUsuario];
//$sesOficina =  $_SESSION[sesOficina];

// Reservar memoria en servidor PHP
//   Si el archivo final tiene 5Mb, reservar 500Mb
//   Por cada operación, phpExcel mapea en memoria la imagen del archivo y esto satura la mamoria
// ini_set("memory_limit","1024M");

//    if( $usuario->havePerm("1,4",$_SESSION['sesArrPerms'] )){
if( isset($sesIdUsuario) ){


    function showForm($data='',$msg="",$flgErr=''){
        global $db,$t,$PHP_SELF,$sesIdUsuario;


        $t->set_file("pageH", "header.inc.html");
        $t->set_var("SESUSUARIO",$_SESSION['sesUsuario']);
        $t->pparse("out","pageH");
        
        $t->set_file("page", "vesselsDel.inc.html");
        
        
        $idVessel= $data['idVessel'];
        // Si existe entonces realizar consulta.
        if(empty($flgErr)){
            $sql="select * from VESSEL where id_vessel='$idVessel' ";
            $db->query($sql);
            while ($db->next_record()) {
                $data['vessel'] = $db->f('vessel');
                $data['voyage'] = $db->f('voyage');
                $data['eta'] = $db->f('eta');
                $data['etd'] = $db->f('etd');
                $data['pol'] = $db->f('pol');
                $data['pod'] = $db->f('pod');
            }
        }


        $t->set_var(array(
            "ACTION"=>$PHP_SELF,
            "MENSAJE"=>"",
            "MENSAJE_PS"=>"",
            "VESSEL"=>$data['vessel'],
            "VOYAGE"=>$data['voyage'],
            "POL"=>$data['pol'],
            "POD"=>$data['pod'],
            ));


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
        $t->set_file("pageF", "footer.inc.html");$t->pparse("out","pageF");    
    }
    
    // ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    // ----------------------------------------------------------------
    $modo = $_POST['modo'];
    $idVessel = $_GET['idVessel'];
    $data['idVessel']= $idVessel;

    switch($modo){
        case "aceptar":        
        
        $company = strtoupper($company);
        $name = strtoupper($name);
        $emailCC= str_replace(",","\n",$emailCC);

        $sql="delete from VESSEL
        where id_vessel='$idVessel'
        ";
        $db->query($sql);
        $msg[]="<span class=\"label label-success\">[OK] The user was deleted.</span>";            
        showForm($arr_request,$msg);
        
        break;
        default:                
        showForm($data);
        break;
    }


}
else{
    $t->set_file("page", "accesoDenegado.inc.html");
    $t->pparse("out","page");
}




?>