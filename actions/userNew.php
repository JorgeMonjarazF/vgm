<?php
session_start();

//--------------------------------------------
// Fecha: 20051017
//--------------------------------------------
include_once("../include/template.inc");
include_once("../include/confGral.php");
include_once("../include/acceso.class.php");
include_once("../include/class.sendmail.php");
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
        
        $t->set_file("page", "userNew.inc.html");
        
        
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
            "PASS"=>$data['password'],

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
    function notify($name='',$email='',$emailCC='',$company='',$pass=''){
        
        $objMail= new SendMail();
                
        $message="
        <pre>
        Hi $name :: $company welcome to VGM System.
        
        Please follow the instruction to access the system.
        1. Webpage : http://www.vgm.com
        2. Login : $email
        3. Pass : $pass

        </pre>
        ";
        
        $subject="[VGM] New account for VGM System ";
        $arrTo[]=$email;
        if( $objMail->sendMail($arrTo,$message,$subject) ){
            $msg="<span class=\"label label-info\">It has sent an email.</span>";
        }
        else{
            $msg="<span class=\"label label-danger\">Error server, it can't send the message... $email </span>";
        }
        
        return $msg;

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
        $name = $_POST['name'];
        $flgNotify = $_POST['flgNotify'];
        $pass = $_POST['password'];


        // PROCESO DE VALIDACION
        if( empty($name) ){
            $msg[] = "<span class=\"label label-danger\">[Name] Missing info...</span>";
        }
        if( empty($company) ){
            $msg[]="<span class=\"label label-danger\">[Company] Missing info ...</span>";
        }
        if( empty($senderId) ){
            $msg[] = "<span class=\"label label-danger\">[SenderId] Missing info...</span>";
        }        
        if( empty($emailTO) ){
            $msg[]="<span class=\"label label-danger\">[emailTO] Missing info ...</span>";
        }
        if( !empty($emailTO) ){
            // Validar que no exista el email. 
            $idUsrDB = getValueTable("id_user","USUARIO","email",$emailTO);
            if( $idUsrDB>0 ){
                // Existente
                $msg[] = "<span class=\"label label-danger\">[email] The email account exist in database, please try with other email account.</span>";
            }
        }
        
        // Si no hay errores entonces actualizar.
        if( count($msg)==0 ){                
            $company = strtoupper($company);
            $name = strtoupper($name);
            $emailCC= str_replace(",","\n",$emailCC);
            
            $passMd5 = md5($pass);

            $sql="insert into USUARIO (
            company,name,email,email_cc,sender_id,password
            ) values (
            '$company','$name','$emailTO','$emailCC','$senderId','$passMd5'    
            )
            ";
            $db->query($sql);
            $msg[]="<span class=\"label label-success\">[OK] The account was added.</span>";            
            
            // Notificar al usuario
            if( $flgNotify==1 ){                
                $msg[] = notify($name,$emailTO,$emailCC,$company,$pass);
            }
            showForm($arr_request,$msg);
        }
        else{
         showForm($arr_request,$msg,'error');   
     }
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