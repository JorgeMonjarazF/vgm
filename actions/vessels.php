<?php
session_start();

//--------------------------------------------
// Fecha: 20051017
//--------------------------------------------
include_once("../include/template.inc");
include_once("../include/confGral.php");
include_once("../include/acceso.class.php");  
include_once("../include/paging_class.php");
//include_once("../include/class.phpmailer.php");
//include_once("../include/PHPExcel/PHPExcel.php");
//include_once("../include/PHPExcel/Reader/Excel2007.php");
$paging=new paging(10,5, "<< prev", "next >>", "(%%number%%)");
$paging->db("localhost","nesoftwa_root",";L9Nehbfaxts","nesoftwa_VGM");
$usuario=new Acceso;
$t = new Template("../templates", "keep");
$sesIdUsuario = $_SESSION[sesIdUsuario];

// Reservar memoria en servidor PHP
//   Si el archivo final tiene 5Mb, reservar 500Mb
//   Por cada operación, phpExcel mapea en memoria la imagen del archivo y esto satura la mamoria
// ini_set("memory_limit","1024M");

if( $usuario->havePerm("1",$_SESSION['userPerms'] )){

	// fffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function explode_keyword($q){
        //trim
        $q=trim($q);        
        $q=preg_replace("/[\s]+/"," ",$q);
        $mode="AND ";
        $q2=explode(" ",$q);                
        for ($i=0;$i<count($q2);$i++) {
            $condition=$condition."vessel"." like '%$q2[$i]%' "
                //. "AND Shipper like '%$q3[$i]%' "
            . $mode ;
        }
        // $condition=substr($condition,0,-4);
        return $condition;
    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function explode_keyword2($q){                  
        $q=trim($q);        
        $q=preg_replace("/[\s]+/"," ",$q);
        $mode="AND ";
        $q2=explode(" ",$q);
        for ($i=0;$i<count($q2);$i++) {
            $condition=$condition."voyage"." like '%$q2[$i]%' "
            . $mode ;
        }           
        $condition=substr($condition,0,-4);
        return $condition;
    }   
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffff    
    /*
    function explode_keyword3($q){
        $q=trim($q);        
        $q=preg_replace("/[\s]+/"," ",$q);
        $mode="AND ";
        $q3= explode(" ",$q);
        for ($i=0;$i<count($q3);$i++) {
            $condition=$condition."company"." like '%$q3[$i]%' "
            .$mode ;
        }           
        $condition=substr($condition,0,-4);
        return $condition;
    }
    */
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function showForm($form="",$msg=""){
        global $t,$PHP_SELF,$sesIdUsuario,$paging;

        // Header
        $t->set_file("pageH", "header.inc.html");
        $t->set_var("SESUSUARIO",$_SESSION['sesUsuario']);
        $t->pparse("out","pageH");
        // Body
        $t->set_file("page", "vessels.inc.html");
        
        
        // ----------------------------------------------------------------
        // Pagin 
        // Importante. Si realiza 2 busquedas sobre campos bastante grandes,
        // alenta la consulta. De preferencia utilice una consulta.
        // -----------------------------------------------------------------
        $qw = explode_keyword($_GET[keyword]);
        $qw2= explode_keyword2($_GET[keyword2]);                            
        // $qw3= explode_keyword3($_GET[keyword3]);                                    
        $sql="select * from VESSEL where $qw $qw2";      
        $paging->query($sql);
        $page=$paging->print_info();

        // Control de paginación.
        if (!empty($page["keyword"]))           
            $t->set_var("INFODATA","Keyword : <b>$page[keyword]</b>");          
        if (empty($page["total"])) {            
            $t->set_var("INFODATA","<u>Not Found</u>");
        } else {            
            $t->set_var("INFODATA","Data $page[start] - $page[end] of $page[total] [Total $page[total_pages] Pages]");          
        }   

        $t->set_block("page","blqTupla","lnTupla");
        while ($result=$paging->result_assoc()){
            $color=$paging->print_color("#dadada","#ffffff");
            $t->set_var("COLOR_ROW",$color);
            
            $idVessel= $result['id_vessel'];
            $vessel= $result['vessel'];
            $voyage = $result['voyage'];
            $pol= $result['pol'];
            $pod= $result['pod'];
            $eta= $result['eta'];
            $etd= $result['etd'];
            $idUsr = $result['id_usr'];
            $fCap = $result['f_captura'];
            $nl++;
            $t->set_var(array(          
                "ID"=>$nl,
                "VESSEL"=>$vessel,
                "VOYAGE"=>$voyage,
                "POL"=>$pol,
                "POD"=>$pod,
                "ETA"=>$eta,
                "ETD"=>$etd,
                
                "OPC1"=>"<a href=\"vesselsEdit.php?idVessel=$idVessel\">Edit</a>",                
                "OPC2"=>"<a href=\"vesselsDel.php?idVessel=$idVessel\"><span class=\"glyphicon glyphicon-trash\"></span></a>",
                ));                 
            
            $t->parse("lnTupla","blqTupla",true);
        }

        // Control de paginación
        $control= $paging->print_link();
        $t->set_var("CONTROL",$control);


        $t->set_var(array(
            "ACTION"=>$PHP_SELF,
            "MENSAJE"=>"",
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

        // Footer
        $t->pparse("out","page");
        $t->set_file("pageF", "footer.inc.html");$t->pparse("out","pageF");    
    }


	// ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    // ----------------------------------------------------------------
    showForm();
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