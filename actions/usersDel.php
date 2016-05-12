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
        
        $t->set_file("page", "usersDel.inc.html");
        
        
        $idUsr= $data['idUsr'];
        // Si existe entonces realizar consulta.
        if(empty($flgErr)){
            $sql="select * from USUARIO where id_user='$idUsr' ";
            $db->query($sql);
            while ($db->next_record()) {
                $data['company'] = $db->f('company');
                $data['senderId'] = $db->f('sender_id');
                $data['emailTO'] = $db->f('email');
                $data['emailCC'] = $db->f('email_cc');
                $data['name'] = $db->f('name');
            }
        }


        $t->set_var(array(
            "ACTION"=>$PHP_SELF,
            "MENSAJE"=>"",
            "MENSAJE_PS"=>"",
            "COMPANY"=>$data['company'],
            "SENDERID"=>$data['senderId'],
            "EMAILTO"=>$data['emailTO'],
            "EMAILCC"=>$data['emailCC'],
            "NAME"=>$data['name'],            

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
    $idUsr = $_GET['idUsr'];

    switch($modo){
        case "aceptar":
        $company = $_POST['company'];
        $emailTO = $_POST['emailTO'];
        $emailCC = $_POST['emailCC'];
        $senderId = $_POST['senderId'];

        //if( count($msg)==0 ){                
            $company = strtoupper($company);
            $name = strtoupper($name);
            $emailCC= str_replace(",","\n",$emailCC);

            $sql="delete from USUARIO
            where id_user='$idUsr'
            ";
            $db->query($sql);
            $msg[]="<span class=\"label label-success\">[OK] The user was deleted.</span>";            
            showForm($arr_request,$msg);
        //}
        //else{
           //showForm($arr_request,$msg,'error');   
       //}
       break;
       default:
       $data['idUsr']=$idUsr;
       showForm($data);
       break;
   }


}
else{
    $t->set_file("page", "accesoDenegado.inc.html");
    $t->pparse("out","page");
}




?>