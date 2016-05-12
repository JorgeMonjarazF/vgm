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
        
        $t->set_file("page", "usersRights.inc.html");
        
        
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
                $data['uname'] = $db->f('name');
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
            "UNAME"=>$data['uname'],
            "IDUSR"=>$data['idUsr'],
            ));


        // Rellenar combo de permiso X, para poder agregar permisos al usuario.
        $sql="select p.permiso as permiso,p.id_permiso as id_permiso  
        from PERMISO p
        LEFT JOIN REL_USR_PERM r ON r.id_permiso = p.id_permiso and r.id_usuario='$idUsr'
        WHERE
        r.id_permiso is null 
        order by p.permiso";
        $db->query($sql);
        $t->set_block("page","blqPermisoX","linPermisoX");
        while($db->next_record()){
            $t->set_var(array(
                "PERMISO_X"=>$db->f(permiso),
                "ID_PERMISO_X"=>$db->f(id_permiso),
                ));
            $t->parse("linPermisoX","blqPermisoX",true);
        }


        // Rellenar la tabla de los permisos del usuario
        $sql="select * from REL_USR_PERM r, PERMISO p where r.id_usuario='$idUsr' and ";
        $sql.="r.id_permiso=p.id_permiso order by p.permiso";
        $db->query($sql);
        $nr=$db->num_rows();
        $t->set_block("page","blqPermiso","linPermiso");
        while($db->next_record()){
            $idRelUsrPerm=$db->f(id_rel_usr_perm);
            $idPermiso=$db->f(id_permiso);
            $permiso=getValueTable("permiso","PERMISO","id_permiso",$idPermiso);
            $t->set_var("ID_REL_USR_PER",$idRelUsrPerm);
            $t->set_var("PERMISO",$permiso);
            $t->set_var("ELIMINAR","<a href=\"javascript:usrDelPermiso(document.frmCaptura,'$PHP_SELF')\">Eliminar seleccionados</a>");
            $t->parse("linPermiso","blqPermiso",true);
        }
        if(empty($nr)){
            $t->set_var("linPermiso","");
        }



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

        // Footer
        $t->set_file("pageF", "footer.inc.html");
        $t->pparse("out","pageF");
    }
    
    // ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    // ----------------------------------------------------------------
    $modo = $_GET['modo'];
    $arrIdPermiso = $_POST['arrIdPermiso'];
    $arrRel = $_POST['arrRel'];
    
    if( isset($_GET['idUsr']) ){
        $idUsr = $_GET['idUsr'];
        $data['idUsr']=$idUsr;
    }
    elseif( isset($_POST['idUsr']) ){
        $idUsr = $_POST['idUsr'];
        $data['idUsr']=$idUsr;
    }   

    switch($modo){
       case "add":
       if( is_array($arrIdPermiso) ){
        foreach($arrIdPermiso as $idPermiso){
            $sql="select * from REL_USR_PERM where id_usuario='$idUsr' ";
            $sql.="and id_permiso='$idPermiso'";
            $db->query($sql);
            $nr=$db->num_rows();
            if(empty($nr)){
                $sql="insert into REL_USR_PERM (";
                $sql.="id_usuario,id_permiso";
                $sql.=") values (";
                $sql.="'$idUsr','$idPermiso'";
                $sql.=")";
                $db->query($sql);
            }
        }
    }
    showForm($data);
    break;
    case "del":
    if( is_array($arrRel) ){
        foreach($arrRel as $idRelUsrPerm){
            $sql="delete from REL_USR_PERM where id_rel_usr_perm='$idRelUsrPerm' ";
            $db->query($sql);
        }
    }    
    showForm($data);
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