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
        
        $t->set_file("page", "vesselNew.inc.html");
        
        
        /*        
        $idVessel= $data['idVessel'];
        // Si existe entonces realizar consulta.
        if(empty($flgErr)){
            $sql="select * from VESSEL where id_vessel='$idVessel' ";
            $db->query($sql);
            while ($db->next_record()) {
                $data['company'] = $db->f('company');
                $data['senderId'] = $db->f('sender_id');
                $data['emailTO'] = $db->f('email');
                $data['emailCC'] = $db->f('email_cc');
                $data['name'] = $db->f('name');
            }
        }
        */


        $t->set_var(array(
            "ACTION"=>$PHP_SELF,
            "MENSAJE"=>"",
            "MENSAJE_PS"=>"",
            "VESSEL"=>$data['vessel'],
            "VOYAGE"=>$data['voyage'],
            "POL"=>$data['pol'],
            "POD"=>$data['pod'],            
            "ETA"=>$data['eta'],
            "ETD"=>$data['etd'],

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
    function existVessel($vessel,$voyage,$eta){
        // Detectar si existe duplicado.
        global $dbf;

        $sql="
        select * from VESSEL where 
        vessel='$vessel' and voyage='$voyage' and eta='$eta'
        ";
        $dbf->query($sql);
        $nr= $dbf->num_rows();
        if( $nr>0 ){
            return true;
        }
        else{
            return false;
        }


    }
    // ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    // ----------------------------------------------------------------
    $modo = $_POST['modo'];
    //$idUsr = $_GET['idUsr'];

    switch($modo){
        case "aceptar":
        $vessel = $_POST['vessel'];
        $voyage = $_POST['voyage'];
        $pol = $_POST['pol'];
        $pod = $_POST['pod'];
        $eta = $_POST['eta'];
        $etd = $_POST['etd'];

        // PROCESO DE VALIDACION
        if( empty($vessel) ){
            $msg[] = "<span class=\"label label-danger\">[Vessel] Missing info...</span>";
        }
        if( empty($voyage) ){
            $msg[]="<span class=\"label label-danger\">[Voyage] Missing info ...</span>";
        }
        if( empty($pol) ){
            $msg[] = "<span class=\"label label-danger\">[POL] Missing info...</span>";
        }   
        if( empty($pod) ){
            $msg[] = "<span class=\"label label-danger\">[POD] Missing info...</span>";
        }        
        if( empty($eta) ){
            $msg[] = "<span class=\"label label-danger\">[ETA] Missing info...</span>";
        }
        // Comprobar que no exista el mismo registro.
        if( existVessel($vessel,$voyage,$eta) ){
            $msg[] = "<span class=\"label label-danger\">[VESSEL] Exist! You must capture other record.</span>";
        }
        
        // Si no hay errores entonces actualizar.
        if( count($msg)==0 ){                
            $vessel = strtoupper($vessel);
            $voyage = strtoupper($voyage);
            $pol = strtoupper($pol);
            $pod = strtoupper($pod);       

            $sql="insert into VESSEL (
            vessel,voyage,pol,pod,eta,etd
            ) values (
            '$vessel','$voyage','$pol','$pod','$eta','$etd'    
            )
            ";
            $db->query($sql);
            $msg[]="<span class=\"label label-success\">[OK] The Vessel was added.</span>";            
            
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