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

if( $usuario->havePerm("1,2",$_SESSION['userPerms'] )){

    function showForm($form="",$msg=""){
        global $db,$db2,$t,$PHP_SELF,$sesOficina,$sesIdUsuario;

        $t->set_file("pageH", "header.inc.html");
        $t->set_var("SESUSUARIO",$_SESSION['sesUsuario']);
        $t->pparse("out","pageH");
        
        $t->set_file("page", "micuenta.inc.html");
        
        $sql="select * from USUARIO where id_user='$sesIdUsuario' ";
        $db->query($sql);
        while ($db->next_record()) {
            $rs = $db->f('company');
            $login = $db->f('login');
            $pais = $db->f('pais');
            $senderId = $db->f('sender_id');
            $emailTO = $db->f('email');
            $emailCC = $db->f('email_cc');
        }

        $t->set_var(array(
            "ACTION"=>$PHP_SELF,
            "MENSAJE"=>"",
            "MENSAJE_PS"=>"",
            "COMPANY"=>$rs,
            "LOGIN"=>$login,
            "PAIS"=>$pais,
            "SENDERID"=>$senderId,
            "EMAILTO"=>$emailTO,
            "EMAILCC"=>$emailCC,
            

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
    $sesIdUsuario = $_SESSION['sesIdUsuario'];

    switch($modo){
        case "updatePass":
        $newPass = $_POST['newPass'];
        $newPass2 =  $_POST['newPass2'];
        if( ($newPass == $newPass2) && (!empty($newPass) && !empty($newPass2)) ){
            $newPassMD5 = md5($newPass);
            $sql="update USUARIO set password='$newPassMD5',pass_text='$newPass' where id_user='$sesIdUsuario'";
            $db->query($sql);
            $msg2[]="<span class=\"label label-success\">The password has been updated.</span>";
        }
        else{
            $msg2[]="<span class=\"label label-danger\">Error, the password is different try again please.</span>";
            
        }
        showForm($arr_request,$msg2);
        break;

        case "aceptar":
        
        $pais = $_POST['pais'];
        $senderId = $_POST['senderId'];
        $emailTO = $_POST['emailTO'];
        $emailCC = $_POST['emailCC'];

            // PROCESO DE VALIDACION
            /*
            if( empty($pais) ){
                $msg[]="[Error][Pais] Faltan datos...";
            }
            if( empty($senderId) ){
                $msg[]="[Error][SenderId] Faltan datos...";
            }
            */
            if( empty($emailTO) ){
                $msg[]="<span class=\"label label-danger\">[emailTO] Missing info ...</span>";
            }
            if( empty($emailCC) ){
                $msg[]="<span class=\"label label-danger\">[emailCC] Missing info ...</span>";
            }

            // Si no hay errores entonces actualizar.
            if( count($msg)==0 ){                
                $emailCC= str_replace(",","\n",$emailCC);

                $sql="update USUARIO 
                set email='$emailTO',
                email_cc='$emailCC'
                where id_user='$sesIdUsuario'
                ";
                $db->query($sql);
                $msg[]="<span class=\"label label-success\">[OK] The data has been updated...</span>";
            }

            showForm($arr_request,$msg);
            break;
            default:
            showForm();
            break;
        }


    }
    else{
        // Header
       $t->set_file("pageH", "header.inc.html");
       $t->set_var("SESUSUARIO",$_SESSION['sesUsuario']);
       $t->pparse("out","pageH");

       $t->set_file("page", "accesoDenegado.inc.html");
       $t->pparse("out","page");

        // Footer
       $t->set_file("pageF", "footer.inc.html");    
       $t->pparse("out","pageF");
   }




   ?>