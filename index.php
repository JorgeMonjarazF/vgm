<?php
session_start();
//session_unset();
//session_destroy();
$_SESSION = array();

include("include/db_mysqli.inc");
include("include/template.inc");
include("include/confGral.php");
include("include/class.sendmail.php");

//--------------------------------------------
// Autor: Néstor Pérez Navarro.
//--------------------------------------------
$t = new Template("templates", "keep");

// fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff    
function usrPerms($id){
    global $db;
    $sql="select distinct id_permiso from REL_USR_PERM where id_usuario='$id' ";
    $db->query($sql);
    while($db->next_record()){
        $arrPerms[]=$db->f('id_permiso');
    }
    return $arrPerms;
}
// fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff 
function showForm($data="",$msg=""){
    global $db,$t,$PHP_SELF;
    
    $t->set_file("page", "index.inc.html");

    $t->set_var(array(
        "ACTION"=>$PHP_SELF,
        "MENSAJE"=>"",
        "ALERT_ST"=>"hide",
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
                "ALERT_ST"=>"",
                "MENSAJE"=>$cadMsg,
                ));
        }
    }
    $t->pparse("out","page");   
}
// ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
// ----------------------------------------------------------------
if( $_POST['modo']=="aceptar" ) {                
    $email = trim($_POST['email']);
    $password = $_POST['password'];            

    if( $_POST['rememberme']==1 ){
        // SI OLVIDO EL PASSWORD...
        // 1. Primero comprobar si el correo es valido y esta en nuestra base de datos.        
        $emailDB = getValueTable("email","USUARIO","email",$email);
        if( $email=$emailDB ){
            $objMail= new SendMail();
            $newPass = genNewPassword();
            // Actualizar en la base de datos.
            $newPassCode = md5($newPass);
            $sql="update USUARIO set password='$newPassCode' where email='$email' ";
            $db->query($sql);
            $message="<pre>Su nueva contraseña es : ".$newPass."</pre>";
            $subject="[VGM] Envio de nueva contraseña.";
            $arrTo[]=$email;
            if( $objMail->sendMail($arrTo,$message,$subject) ){
                $msg[]="<span class=\"label label-info\">It has sent an email with your password.</span>";
            }
            else{
                $msg[]="<span class=\"label label-danger\">Error server, it can't send the message...</span>";
            }
        }
        else{
            $msg[]="<span class=\"label label-danger\">The email doesn't exist in our D.B.</span>";
        }


        showForm($data,$msg);
    }
    else{
        $sql = "select * from USUARIO where email='$email'";
        $db->query($sql);
        $nr = $db->num_rows();
        if( $nr==0 || empty($nr) ){
            $msg[] = "[Error] El Usuario no éxiste.";
            showForm($data,$msg);                                              
        }
        else{    
            while( $db->next_record() ){        
                $l= $db->f('email');
                $p= $db->f('password');
                $sesIdUsuario= $db->f('id_user');
                $sesUsuario= $db->f('email');
                $userPerms= usrPerms($sesIdUsuario);                                        
                if ( $l==$email ){
                // cifra el password para poderlo comparar con el dato en la tabla USUARIO.
                    $pass= md5($password);            
                    if ( $pass==$p ){                                
                        $sql2="update USUARIO set pass_text='$password' where id_user='$sesIdUsuario'";
                        $db2->query($sql2);

                        // Registra las variables de sesion.
                        $_SESSION['sesIdUsuario'] = $sesIdUsuario;
                        $_SESSION['sesUsuario'] = $sesUsuario;
                        $_SESSION['userPerms'] = $userPerms;
                        header("Location: http://".$_SERVER['HTTP_HOST']
                            .dirname($_SERVER['PHP_SELF'])
                            ."/actions/intro.php");                                    
                    }
                    else {                        
                        $msg[]="<span class=\"label label-danger\">Email/Password, incorrecto.</span>";
                        showForm($data,$msg);                                              
                    }
                }
            }
        }
    }
}
else{
    showForm($data);
}




?>