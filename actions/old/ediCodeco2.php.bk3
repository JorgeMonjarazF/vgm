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
ini_set("memory_limit","1024M");

//    if( $usuario->havePerm("1,4",$_SESSION['sesArrPerms'] )){
if( isset($sesIdUsuario) ){

    // ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function getEquipoTipo( $tamano ){
        // 20' DRY VAN
        if( $tamano=="22.10" || $tamano=="22.1" || $tamano=="20DC" || $tamano=="20DV" ){$tipo="22G0";}
        // 20' FLAT COLLAPSIBLE
        if( $tamano=="22.63" || $tamano=="20FL" ){$tipo="22P3";}
        // 20' REEFER
        if( $tamano=="22.32" || $tamano=="20RF" ){$tipo="22R1";}
        // 20CT TANK CONTAINER
        if( $tamano=="22.70" || $tamano=="22.7" || $tamano=="20TK" ){$tipo="22T3";}
        // 20OT OPEN TOP
        if( $tamano=="22.51" || $tamano=="20OT" ){$tipo="22U1";}
        // 40' DRY VAN
        if( $tamano=="43.10" || $tamano=="43.1" || $tamano=="40DC" ){$tipo="42G0";}
        // 40' HIGH CUBE
        if( $tamano=="45.10" || $tamano=="45.1" || $tamano=="40HC" ){$tipo="45G0";}
        // 40' OPEN TOP
        if( $tamano=="43.51" || $tamano=="40OT" ){$tipo="42U1";}
        // RF4/40' REEFER
        if( $tamano=="43.32" || $tamano=="40RF" ){$tipo="42R1";}
        // 40CT TANK CONTAINER
        if( $tamano=="43.70" || $tamano=="40TK" ){$tipo="42T0";}

        return $tipo;

    }

    // ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function sendMailMSC($fileEDI,$ediType,$fileName){
        global $hoy,$db,$db2,$sesIdUsuario;

        // Email del usuario
        $usrEmail = getValueTable("email","USUARIO","id_usuario",$sesIdUsuario);
        $usrEmailCC = getValueTable("email_cc","USUARIO","id_usuario",$sesIdUsuario);

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

        /*
        // ++ COOCE ++
        $mail->Host = "cooce.com.mx";
        $mail->SMTPAuth = true;
        $mail->Username = "sion@cooce.com.mx";
        $mail->Password = "nestor";
        $mail->From = "robot.sion@mscmx.mscgva.ch";
        $mail->FromName = "MSC - Customer Service";
        */

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
        <font size=\"4\"><b>www.edifactory.org</b></font><br>
        <font size=\"2\"><b>Notificación de envio de EDI (<b>$ediType</b>)</b></font><br>
        <hr>
        <p>
        Buen día : <br>
        <br>
        Este mensaje lleva un adjunto -> $fileName, Es un archivo tipo EDI-CODECO.<br>
        <br>
        Para cualquier duda favor de contactarnos.
        <p>
        <i>
        Att. Robot EDI-Factory <br>
        </i>
        <p>
        <hr>
        <font color=\"red\" size=\"2\">
        <i>Este es un correo de envio automático generado por nuestro sistema www.edifactory.org, por favor no responda este email.<br></i>
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
        if( !empty($usrEmailCC) ){
            $arrDirDestino = explode("\n",$usrEmailCC);
            foreach ( $arrDirDestino as $emailDestino ) {
                if (! empty ( $emailDestino )) {
                    $mail->AddCC( $emailDestino );
                }
            }
        }

        // BCC :
        //$mail->AddBCC("nperez@mscmexico.com");
        $mail->AddBCC("lrodriguez@mscmexico.com");

        // Subject :
        $mail->Subject = "[EDIFACTORY] Notificación EDI :: $fileName ";

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
            echo "[ <font color=red><b>Problema de envio</b></font> ] ".$mail->ErrorInfo."<br>";
        }
        else{
            echo "[ <font color=green><b>OK, E-Mail enviado.</b></font> ] <br>";
        }

        // ---------------------------------------------------------
        // ELIMINAR los archivos CSV una vez enviados.
        // ---------------------------------------------------------
        //unlink("../edi_files/csv/entradas.csv");
        //unlink("../edi_files/edi/entradas.edi");


    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function sendNotify($idNav,$sesIdUsuario){
        global $hoy,$db,$db2;

        // Email del usuario
        $usrEmail = getValueTable("email","USUARIO","id_usuario",$sesIdUsuario);
        $usrEmailCC = getValueTable("email_cc","USUARIO","id_usuario",$sesIdUsuario);

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
        <font size=\"4\"><b>www.edifactory.org</b></font><br>
        <font size=\"2\"><b>Notificación de envio de EDI-CODECO </b></font><br>
        <hr>
        <p>
        Buen día : <br>
        <br>
        Este mensaje lleva dos adjuntos tipo EDI-CODECO.<br>
        <br>
        Para cualquier duda favor de contactarnos.
        <p>
        <i>
        Att. Robot EDI-Factory <br>
        </i>
        <p>
        <hr>
        <font color=\"red\" size=\"2\">
        <i>Este es un correo de envio automático generado por nuestro sistema www.edifactory.org, por favor no responda este email.<br></i>
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
        if( !empty($usrEmailCC) ){
            $arrDirDestino = explode("\n",$usrEmailCC);
            foreach ( $arrDirDestino as $emailDestino ) {
                if (! empty ( $emailDestino )) {
                    $mail->AddCC( $emailDestino );
                }
            }
        }

        // BCC :
        //$mail->AddBCC("nperez@mscmexico.com");
        //$mail->AddBCC("lrodriguez@mscmexico.com");

        // Subject :
        $mail->Subject = "[EDIFACTORY] Archivos EDI-CODECO";

        //Incluir Attach.
        // $mail->AddAttachment($fileEDI,$fileName);

        // Leer un directorio
        $path ='../edi_files/edi/';
        $directorio = opendir($path); //ruta actual
        while ( $archivo = readdir($directorio)){

            if( $sesIdUsuario==10 ){
                // TRANE
                if( preg_match("/TRANE/i",$archivo) ){
                    $tamanio = filesize($path.$archivo);
                    if( $tamanio > 50 ){
                        $mail->AddAttachment($path.$archivo,$archivo);
                    }
                }
            }
        }


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
            echo "[ <font color=red><b>Problema de envio</b></font> ] ".$mail->ErrorInfo."<br>";
        }
        else{
            echo "[ <font color=green><b>OK, E-Mail enviado.</b></font> ] <br>";
        }

        // ---------------------------------------------------------
        // ELIMINAR los archivos CSV una vez enviados.
        // ---------------------------------------------------------
        $path ='../edi_files/edi/';
        $directorio = opendir($path); //ruta actual
        while ( $archivo = readdir($directorio)){

            if( $sesIdUsuario==10 ){
                // TRANE
                if( preg_match("/TRANE/i",$archivo) ){
                    unlink($path.$archivo);
                }
            }
        }



    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function showForm($form="",$msg=""){
        global $db,$db2,$t,$PHP_SELF,$sesOficina,$sesIdUsuario;

        // Si es TRANE es caso especial
        if( $sesIdUsuario==10 || $sesIdUsuario==17 ){
            $t->set_file("page", "accesoDenegado.html");
        }
        else{
            if( $sesIdUsuario!=14 ) {
                $t->set_file("page", "ediCodeco.inc.html");
            }
        }
        if( $sesIdUsuario!=10 && $sesIdUsuario!=17 ) {

            // inicializar vars
            $t->set_var("ACTION", $PHP_SELF);
            $t->set_var("MENSAJE", "");
            $t->set_var("OFICINA", $sesOficina);

            // ------------------------------------------
            // COMBO DE CODIGO DE DESTINO
            // ------------------------------------------
            $pais = getValueTable("pais", "USUARIO", "id_usuario", $sesIdUsuario);
            if ($pais == "MX") {
                $t->set_block("page", "blqCodDes", lnCodDes);
                $t->set_var("COD_DES", "LMR00");
                $t->set_var("COD_NAME", "ALTAMIRA");
                $t->parse("lnCodDes", "blqCodDes", true);
                $t->set_var("COD_DES", "LZC00");
                $t->set_var("COD_NAME", "LAZARO CARDENAS");
                $t->parse("lnCodDes", "blqCodDes", true);
                $t->set_var("COD_DES", "ZLO00");
                $t->set_var("COD_NAME", "MANZANILLO");
                $t->parse("lnCodDes", "blqCodDes", true);
                $t->set_var("COD_DES", "MEX00");
                $t->set_var("COD_NAME", "MEXICO");
                $t->parse("lnCodDes", "blqCodDes", true);
                $t->set_var("COD_DES", "VER00");
                $t->set_var("COD_NAME", "VERACRUZ");
                $t->parse("lnCodDes", "blqCodDes", true);
            } elseif ($pais == "SV") {
                $t->set_block("page", "blqCodDes", lnCodDes);
                $t->set_var("COD_DES", "AQJ00");
                $t->set_var("COD_NAME", "ACAJUTLA");
                $t->parse("lnCodDes", "blqCodDes", true);
                $t->set_var("COD_DES", "GTP01");
                $t->set_var("COD_NAME", "PUERTO BARRIOS");
                $t->parse("lnCodDes", "blqCodDes", true);
            }
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


        $t->pparse("out","page");
    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateInMscJose($deli,$stConte,$impoExpo,$sesIdUsuario,$codOD,$opcPro){
        global $db,$db2,$HTTP_POST_FILES,$hoy,$t;

        // --------------------------------------------
        // Proceso de copiado.
        // --------------------------------------------
        $hoy = date("Y-m-d H:i");
        // Copia el archivo del usuario al directorio ../files
        if ( is_uploaded_file($HTTP_POST_FILES['userfile']['tmp_name']) ) {
            copy($HTTP_POST_FILES['userfile']['tmp_name'], "../edi_files/csv/entradas.csv");
        }
        else {
            $msg="<font color=\"red\">[Error] en el envio del archivo, vuela a intentarlo! " . $HTTP_POST_FILES['userfile']['name'];
            $msg.="</font><br>";
            echo $msg;
        }

        // -------------------------------------------------
        // Leer el archvo CSV para convertirlo en EDI
        // -------------------------------------------------
        /*
        $fp = fopen("../edi_files/csv/entradas.csv","r");
        $l=1;
        unset($data);
        unset($dataX);
        while ( $data = fgetcsv($fp,1000,$deli) ) {
        if($l==7){
        foreach($data as $campo){
        $campo= addslashes($campo);
        $campo= str_replace("\n","",$campo);
        $campo= str_replace("\r","",$campo);
        $dataX[]=$campo;
        }
        // Validacion campos
        $entSal= $dataX[0];
        break;
        }
        $l++;
        }
        fclose($fp);
        $entSal= strtoupper($entSal);
        */

        // -------------------------------------
        // GET-IN
        // -------------------------------------
        unset($fp);
        unset($data);
        unset($dataX);
        $fp = fopen("../edi_files/csv/entradas.csv","r");
        $validFile="True";
        $l=1;
        while ( $data = fgetcsv($fp,1000,"$deli") ) {
            if($l==5){
                foreach($data as $campo){
                    $campo=addslashes($campo);
                    $campo= str_replace("\n","",$campo);
                    $campo= str_replace("\r","",$campo);
                    $dataX[]=$campo;
                }
                // Validacion campos
                // Nuevo Formato
                if($dataX[0]!="NAVIERA"){$validFile="False"; $msg[] =  "[Error][Encabezado] NAVIERA, esta incorrecto."; }
                if($dataX[1]!="CONTENEDOR"){$validFile="False"; $msg[] =  "[Error][Encabezado] CONTENEDOR, esta incorrecto."; }
                if($dataX[2]!="TAM"){$validFile="False"; $msg[] =  "[Error][Encabezado] TAM, esta incorrecto."; }
                if($dataX[3]!="CLASIFICACION"){$validFile="False"; $msg[] =  "[Error][Encabezado] CLASIFICACION, esta incorrecto."; }
                if($dataX[4]!="EIR"){$validFile="False"; $msg[] =  "[Error][Encabezado] EIR, esta incorrecto."; }
                if($dataX[5]!="FECHA ENTRADA"){$validFile="False"; $msg[] =  "[Error][Encabezado] FECHA ENTRADA, esta incorrecto."; }
                if($dataX[6]!="HR"){$validFile="False"; $msg[] =  "[Error][Encabezado] HR, esta incorrecto."; }
                if($dataX[7]!="MANIOBRA"){$validFile="False"; $msg[] =  "[Error][Encabezado] MANIOBRA, esta incorrecto. "; }
                if($dataX[8]!="COSTO"){$validFile="False"; $msg[] =  "[Error][Encabezado] COSTO, esta incorrecto."; }
                if($dataX[9]!="FACTURA"){$validFile="False"; $msg[] =  "[Error][Encabezado] FACTURA, esta incorrecto."; }
                if($dataX[10]!="TRANSPORTISTA"){$validFile="False"; $msg[] =  "[Error][Encabezado] TRANSPORTISTA, esta incorrecto."; }
                if($dataX[11]!="CLIENTE"){$validFile="False"; $msg[] =  "[Error][Encabezado] CLIENTE, esta incorrecto. "; }

            }
            $l++;
        }
        fclose($fp);

        // IMPRIMIR ERRORES
        if( is_array($msg) ){
            if( count($msg)>0 ){
                foreach( $msg as $msgX ){
                    echo "<font color=red>$msgX</font><br>";
                }
            }
        }

        if( $validFile=="True" ){
            if( $sesOficina=="VERACRUZ" ){
                $ofiCod="VR";
            }
            elseif($sesOficina=="MEXICO"){
                $ofiCod="MX";
            }
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            //$fileName = "MSCE".$fecD1.$fecD2.".edi";
            $fileName = substr($csvFile,0,-4).".edi";
            $flgRec=1;
            $fp= fopen("../edi_files/csv/entradas.csv","r");
            $l=1;
            $f=date(Ymd);
            $fileEDI = "../edi_files/edi/GATE-IN".$ofiCod.$fecD1.$fecD2.".edi";
            $fp2 = fopen("$fileEDI","w");
            $sepa="\n";

            $codPatio = getValueTable("cod_patio","USUARIO","id_usuario",$sesIdUsuario);
            $codPatioName = getValueTable("cod_patio_name","USUARIO","id_usuario",$sesIdUsuario);
            $senderID = getValueTable("sender_id","USUARIO","id_usuario",$sesIdUsuario);
            $receiverID="MSC";
            // ENCABEZADO
            $enc= "UNB+UNOA:1+$senderID+$receiverID+$fecD1:$fecD2+$fecD1$fecD2'".$sepa;
            $enc.="UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'".$sepa;
            $enc.="BGM+34+$fecD1$fecD2+9'".$sepa;
            //$enc.="LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'".$sepa;

            fputs($fp2,$enc);
            //$e=0;  // elemento del arreglo.
            $tlConte=0;
            $tlSegmentos =4;
            while ($data = fgetcsv($fp,1000,"$deli")) {
                if( $l > 5 ){
                    unset($dataX);
                    foreach($data as $campo){
                        $campo=addslashes($campo);
                        $campo= str_replace("\n","",$campo);
                        $campo= str_replace("\r","",$campo);
                        $dataX[]=$campo;
                    }
                    $conte= $dataX[1];

                    // ---------------------
                    // FECHA HORA MINUTO
                    // ---------------------
                    //$fecha= $dataX[0];
                    $fecha= $dataX[5];

                    // * para lo que tenga -> /
                    $validFec=0;
                    if( preg_match("/(\d{2})\/(\d{2})\/(\d{4})/i",$fecha,$parts) ){
                        $d= $parts[1];
                        $m= $parts[2];
                        $a= $parts[3];
                        $validFec=1;
                    }
                    elseif( preg_match("/(\d{4})\/(\d{2})\/(\d{2)/i",$fecha,$parts) ){
                        $d= $parts[3];
                        $m= $parts[2];
                        $a= $parts[1];
                        $validFec=1;
                    }
                    elseif( preg_match("/(\d{2})-(\d{2})-(\d{4})/i",$fecha,$parts) ){
                        $d= $parts[1];
                        $m= $parts[2];
                        $a= $parts[3];
                        $validFec=1;
                    }
                    // Hora:Minuto Formato 24 hrs.
                    $hora= $dataX[6];
                    unset($parts);
                    if( preg_match("/(\d{2}):(\d{2})/",$hora,$parts) ){
                        $hr= $parts[1];
                        if($hr=="24"){$hr="23";};
                        $min= $parts[2];
                    }

                    // -------------
                    // Validacion
                    // -------------
                    if( (!empty($conte)) && ($validFec==0) ){
                        $msgErr[] = "<font color=red>[Error][$conte] La fecha es incorrecta.</font>";
                    }

                    /*
                    $a= trim($a);
                    $thisYear = date("Y");
                    if( $a<>$thisYear ){
                    $msgErr[] = "<font color=red>[Error][$conte] Año incorrecto, favor de corregir el formato de la fecha en su archivo CSV.</font>";
                    $validFec=0;
                    }
                    */
                    if( (empty($hr) || empty($min)) && (!empty($conte)) ){
                        $msgErr[] = "<font color=red>[Error][$conte] Hora o Minuto incorrecto, favor de corregir el formato a 24 hrs.</font>";
                        $validFec=0;
                    }
                    // -----------------


                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;
                        $tamano= $dataX[2];
                        // Clasificación
                        $calidad= $dataX[3];
                        //$condConte= $dataX[4];
                        $transportista= $dataX[10];
                        $transportista = strtoupper($transportista);
                        $eir= $dataX[4];
                        $maniobra= $dataX[7];
                        $maniobra = strtoupper($maniobra);
                        $regFecha= $a.$m.$d;
                        $regHora=$hr.$min;
                        //echo "hora: $regHora<br>";
                        //echo "ano: $a mes; $mes= $m dia; $d <br>";
                        $conte = trim($conte);
                        $conte = str_replace(" ","",$conte);
                        //$conte=preg_replace($pattern,$replacement,$conte);
                        $tamano = strtoupper($tamano);
                        $tamano = str_replace(" ","",$tamano);

                        // 20' DRY VAN
                        if( $tamano=="22.10" || $tamano=="22.1" || $tamano=="20DC" || $tamano=="20DV" ){$tipo="22G0";}
                        // 20' FLAT COLLAPSIBLE
                        if( $tamano=="22.63" || $tamano=="20FL" ){$tipo="22P3";}
                        // 20' REEFER
                        if( $tamano=="22.32" || $tamano=="20RF" ){$tipo="22R1";}
                        // 20CT TANK CONTAINER
                        if( $tamano=="22.70" || $tamano=="22.7" || $tamano=="20TK" ){$tipo="22T3";}
                        // 20OT OPEN TOP
                        if( $tamano=="22.51" || $tamano=="20OT" ){$tipo="22U1";}
                        // 40' DRY VAN
                        if( $tamano=="43.10" || $tamano=="43.1" || $tamano=="40DC" ){$tipo="42G0";}
                        // 40' HIGH CUBE
                        if( $tamano=="45.10" || $tamano=="45.1" || $tamano=="40HC" ){$tipo="45G0";}
                        // 40' OPEN TOP
                        if( $tamano=="43.51" || $tamano=="40OT" ){$tipo="42U1";}
                        // RF4/40' REEFER
                        if( $tamano=="43.32" || $tamano=="40RF" ){$tipo="42R1";}
                        // 40CT TANK CONTAINER
                        if( $tamano=="43.70" || $tamano=="40TK" ){$tipo="42T0";}

                        // Cod.Deposito
                        // PENDIENTE : PASAR TODO A LA FUNCION ORIGINAL... PARA NO USAR LA FUNCION JOSE...
                        $remarks = $dataX[12];
                        $codDep = $dataX[13];
                        if( empty($codDep) ){
                            $codDep = $codPatio;
                        }
                        $bkgNumber = $dataX[14];
                        $bl = $dataX[15];
                        $enc2="LOC+165+$codDep:139:6+:TER:ZZZ'".$sepa;

                        //---------------------------------------------------------
                        //COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                        //----------------------------------------------------------
                        // FTX : Remarks +AAI = General Information
                        ($impoExpo=="E")?$stIE=2:$stIE=3;
                        ($stConte=="E")?$stCo=4:$stCo=5;
                        $conte = strtoupper($conte);
                        $enc2.= "EQD+CN+$conte+$tipo++$stIE+$stCo'".$sepa;
                        $tlSegmentos++;
                        $enc2.="DTM+7:$regFecha$regHora:203'".$sepa;
                        $tlSegmentos++;
                        if( !empty($bkgNumber) ){
                            $enc2.="RFF+BN:$bkgNumber'".$sepa;
                        }
                        $tlSegmentos++;
                        $enc2.="FTX+AAI+++$transportista/$eir/$remarks'".$sepa;
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'".$sepa;
                        $tlSegmentos++;
                        //$enc2.="LOC+99+$codePatio+::ZZZ'".$sepa;   // 99 : Place of empty equipment return
                        //$tlSegmentos++;
                        fputs($fp2,$enc2);
                    }

                }
                $l++;
            } // fin del while


            // Pie de pagina
            // Total de contenedores
            $pie="CNT+16:$tlConte'".$sepa;
            $tlSegmentos++;
            $pie.="UNT+$tlSegmentos+$fecD1$fecD2'".$sepa;
            $pie.="UNZ+1+$fecD1$fecD2'";

            //$enc33=utf8_encode($enc3);
            fputs($fp2,$pie);
            fclose($fp2);
            fclose($fp);

            // -------------------
            // Mensajes de error:
            // -------------------
            if( count($msgErr)>0 ){
                foreach( $msgErr as $msgY ){
                    echo "<font color=red>[<b>Error</b>] $msgY</font><br>";
                }
            }
            else{
                if( $opcPro=="saveAs" ){
                    // -----------------------------
                    // SALVAR COMO... O ABRIR EN AUTO.
                    // (No modificar)
                    // -----------------------------
                    if( file_exists("$fileEDI") ){
                        header('Content-Description: File Transfer');
                        header('Content-Type: application/octet-stream');
                        header('Content-Disposition: attachment; filename='.basename($fileEDI));
                        header('Content-Transfer-Encoding: binary');
                        header('Expires: 0');
                        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                        header('Pragma: public');
                        header('Content-Length: ' . filesize($fileEDI));
                        ob_clean();
                        flush();
                        readfile("$fileEDI");
                        exit;
                    }
                }
                elseif( $opcPro=="email" ){
                    $fileName="GATE-IN".$ofiCod.$fecD1.$fecD2.".edi";
                    sendMailMSC($fileEDI,'GATE-IN',$fileName);
                }
                elseif( $opcPro=="ftp" ){
                    // -----------------------------
                    // FTP - GETIN
                    // -----------------------------
                    $ftp_server = "187.174.238.23";
                    switch( $codOD ){
                        case "LMR00":
                            $ftp_user_name = "terralmr";
                            $ftp_user_pass = "lmr03";
                            break;
                        case "LZC00":
                            $ftp_user_name = "terralzc";
                            $ftp_user_pass = "lzc03";
                            break;
                        case "ZLO00":
                            $ftp_user_name = "terrazlo";
                            $ftp_user_pass = "zlo03";
                            break;
                        case "VER00":
                            $ftp_user_name = "terraver";
                            $ftp_user_pass = "ver03";
                            break;
                    }
                    $source_file = $fileEDI;
                    $destination_file = str_replace("../edi_files/edi/","",$source_file);
                    // --
                    // --
                    $conn_id = ftp_connect($ftp_server);
                    $login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
                    // verificar la conexión
                    if ((!$conn_id) || (!$login_result)) {
                        $msg[] = "<font color=red>¡La conexión FTP ha fallado ponerse en contacto con Lic.Nestor Perez Tel: 5091-7636 / 5532224753 </font>";
                        $msg[] = "<font color=red>Se intentó conectar al $ftp_server por el usuario $ftp_user_name</font>";
                        exit;
                    } else {
                        $msg[]= "<font color=blue>Conexión a $ftp_server realizada con éxito, por el usuario $ftp_user_name</font>";
                    }
                    // subir un archivo
                    $upload = ftp_put($conn_id, $destination_file, $source_file, FTP_BINARY);
                    // comprobar el estado de la subida
                    if (!$upload) {
                        $msg[] = "<font color=red>La subida FTP ha fallado! </font>";
                    } else {
                        $msg[] = "<font color=blue>El Envio a FTP $ftp_server ha sido exitoso!. Archivo : $destination_file </font>";
                        //sendMailMSC('GETA-IN');
                    }
                    // cerrar la conexión ftp
                    ftp_close($conn_id);
                }
            }
        }
        return $msg;
    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateOutMscExcelTrane($stConte,$impoExpo,$sesIdUsuario,$codOD,$opcPro){
        global $db,$db2,$HTTP_POST_FILES,$hoy,$t;

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

        if( $encC1!="Fecha y hora salida"){$validFile=0;echo "[Error Encabezado] Fecha y hora salida<br>";}
        if( $encC2!="Num. Contenedor" ){$validFile=0;echo "[Error Encabezado] Num. Contenedor<br>";}
        // if( $encC3!="TIPO/TAMAÑO"){$validFile=0;echo "[Error Encabezado] TIPO/TAMAÑO<br>";}
        if( $encC4!="Booking" ){$validFile=0;echo "[Error Encabezado] Booking <br>";}
        if( $encC5!="Cliente" ){$validFile=0;echo "[Error Encabezado] Cliente <br>";}
        if( $encC6!="Transportista" ){$validFile=0;echo "[Error Encabezado] Transportista <br>";}
        if( $encC7!="CALIDAD" ){$validFile=0;echo "[Error Encabezado] CALIDAD<br>";}
        if( $encC8!="No.- EIR" ){$validFile=0;echo "[Error Encabezado] No.- EIR <br>";}
        if( $encC9!="Sello Salida" ){$validFile=0;echo "[Error Encabezado] Sello Salida <br>";}
        if( $encC10!="Maniobra por cuenta de Merchant  //  carrier" ){$validFile=0;echo "[Error Encabezado] Maniobra por cuenta de Merchant  //  carrier <br>";}
        // ---- Fin de la validación ----

        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $array_data = array();
        foreach($rowIterator as $row){
            //echo "fila ".$row->getRowIndex ();
            //echo "<br>";
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            //if(1 == $row->getRowIndex ()) continue; //skip first row la del encabezado
            $rowIndex = $row->getRowIndex ();
            //$array_data[$rowIndex] = array('A'=>'', 'B'=>'','C'=>'','D'=>'','E'=>'');

            foreach ($cellIterator as $cell) {
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn()){
                    $datos[$rowIndex]['FECHAHORA'] = $cell->getValue();
                    if( $datos[$rowIndex]['FECHAHORA']=="Fecha y hora Ingreso" ){
                        $rowStar=$rowIndex;
                    }
                }
                if('B' == $cell->getColumn()){
                    $conte = $cell->getValue();
                    $conte = strtoupper($conte);
                    $datos[$rowIndex]['CONTENEDOR'] = $conte;
                }
                if('C' == $cell->getColumn()){
                    $datos[$rowIndex]['EQUIPO'] = $cell->getValue();
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
            $fileEDI = "../edi_files/edi/TRANE_GATE-OUT".$fecD1.$fecD2.".edi";
            $fp2 = fopen("$fileEDI", "w");
            $sepa = "\n";
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
                        $enc2.="DTM+7:$regFecha$regHora:203'".$sepa;
                        $tlSegmentos++;
                        $enc2.="RFF+BN:$bkgNumber'".$sepa;
                        $tlSegmentos++;
                        $enc2.="FTX+AAI+++$bkgNumber/$cliente/$transportista'".$sepa;
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'".$sepa;
                        $tlSegmentos++;
                        // Cuando sean salidas es necesario agregar esta línea para cada movimiento,
                        // ya que con este se especifica el Destination Depot.
                        $enc2.="LOC+99+$codOD'".$sepa;   // 99 : Place of empty equipment return
                        $tlSegmentos++;
                        fputs($fp2,$enc2);
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

            /*
            // ------------------------------
            // GENERACION DE ARCHIVO
            // ------------------------------
            if( $opcPro=="saveAs" ){
                // -----------------------------
                // SALVAR COMO... O ABRIR EN AUTO.
                // (No modificar)
                // -----------------------------
                if( file_exists("$fileEDI") ){
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename='.basename($fileEDI));
                    header('Content-Transfer-Encoding: binary');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($fileEDI));
                    ob_clean();
                    flush();
                    readfile("$fileEDI");
                    exit;
                }
            }
            elseif( $opcPro=="email" ){
                $fileName="GATE-OUT".$ofiCod.$fecD1.$fecD2.".edi";
                sendMailMSC($fileEDI,'GATE-OUT',$fileName);
            }
            elseif( $opcPro=="ftp" ){
                // -----------------------------
                // FTP - GETIN
                // -----------------------------
                $ftp_server = "187.174.238.23";
                switch( $codOD ){
                    case "LMR00":
                        $ftp_user_name = "terralmr";
                        $ftp_user_pass = "lmr03";
                        break;
                    case "LZC00":
                        $ftp_user_name = "terralzc";
                        $ftp_user_pass = "lzc03";
                        break;
                    case "ZLO00":
                        $ftp_user_name = "terrazlo";
                        $ftp_user_pass = "zlo03";
                        break;
                    case "VER00":
                        $ftp_user_name = "terraver";
                        $ftp_user_pass = "ver03";
                        break;
                }
                $source_file = $fileEDI;
                $destination_file = str_replace("../edi_files/edi/","",$source_file);
                // --
                // --
                $conn_id = ftp_connect($ftp_server);
                $login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
                // verificar la conexión
                if ((!$conn_id) || (!$login_result)) {
                    $msg[] = "<font color=red>¡La conexión FTP ha fallado ponerse en contacto con Lic.Nestor Perez Tel: 5091-7636 / 5532224753 </font>";
                    $msg[] = "<font color=red>Se intentó conectar al $ftp_server por el usuario $ftp_user_name</font>";
                    exit;
                } else {
                    $msg[]= "<font color=blue>Conexión a $ftp_server realizada con éxito, por el usuario $ftp_user_name</font>";
                }
                // subir un archivo
                $upload = ftp_put($conn_id, $destination_file, $source_file, FTP_BINARY);
                // comprobar el estado de la subida
                if (!$upload) {
                    $msg[] = "<font color=red>La subida FTP ha fallado! </font>";
                } else {
                    $msg[] = "<font color=blue>El Envio a FTP $ftp_server ha sido exitoso!. Archivo : $destination_file </font>";
                    //sendMailMSC('GETA-IN');
                }
                // cerrar la conexión ftp
                ftp_close($conn_id);
            }
            */

        }

    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateInMscExcelTrane($stConte,$impoExpo,$sesIdUsuario,$codOD,$opcPro){
        global $db,$db2,$HTTP_POST_FILES,$hoy,$t;

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
        $encC5= $objPHPExcel->getActiveSheet()->getCell("F9")->getValue();
        $encC6= $objPHPExcel->getActiveSheet()->getCell("G9")->getValue();
        $encC7= $objPHPExcel->getActiveSheet()->getCell("H9")->getValue();
        if( $encC1!="Fecha y hora Ingreso"){$validFile=0;echo "[Error Encabezado] Fecha y hora Ingreso<br>";}
        if( $encC2!="Num. Contenedor" ){$validFile=0;echo "[Error Encabezado] Num. Contenedor<br>";}
        // if( $encC3!="TIPO/TAMAÑO"){$validFile=0;echo "[Error Encabezado] TIPO/TAMAÑO<br>";}
        if( $encC4!="CALIDAD" ){$validFile=0;echo "[Error Encabezado]CALIDAD <br>";}
        if( $encC5!="Ag. Aduanal / Transportista" ){$validFile=0;echo "[Error Encabezado] Ag. Aduanal / Transportista <br>";}
        if( $encC6!="No.- EIR" ){$validFile=0;echo "[Error Encabezado] No.- EIR <br>";}
        if( $encC7!="Maniobra por cuenta de Merchant / carrier" ){$validFile=0;echo "[Error Encabezado] Maniobra por cuenta de Merchant / carrier<br>";}
        // ---- Fin de la validación ----

        // ------------------------------------
        // Extracción de Celdas Multiples
        // -----------------------------------
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $array_data = array();
        foreach($rowIterator as $row){
            //echo "fila ".$row->getRowIndex ();
            //echo "<br>";
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
            //if(1 == $row->getRowIndex ()) continue; //skip first row la del encabezado
            $rowIndex = $row->getRowIndex ();
            //$array_data[$rowIndex] = array('A'=>'', 'B'=>'','C'=>'','D'=>'','E'=>'');

            foreach ($cellIterator as $cell) {
                // Empezar a leer desde la columna "No."
                if('A' == $cell->getColumn()){
                    $datos[$rowIndex]['FECHAHORA'] = $cell->getValue();
                    if( $datos[$rowIndex]['FECHAHORA']=="Fecha y hora Ingreso" ){
                        $rowStar=$rowIndex;
                    }
                }
                if('B' == $cell->getColumn()){
                    $conte = $cell->getValue();
                    $conte = strtoupper($conte);
                    $datos[$rowIndex]['CONTENEDOR'] = $conte;
                }
                if('C' == $cell->getColumn()){
                    $datos[$rowIndex]['EQUIPO'] = $cell->getValue();
                }
                if('G' == $cell->getColumn()){
                    $datos[$rowIndex]['EIR'] = $cell->getValue();
                }
                if('H' == $cell->getColumn()){
                    $datos[$rowIndex]['MANIOBRA'] = $cell->getValue();
                }
                if('K' == $cell->getColumn()){
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
            $fileEDI = "../edi_files/edi/TRANE_GATE-IN".$fecD1.$fecD2.".edi";
            $fp2 = fopen("$fileEDI", "w");
            $sepa = "\n";
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
                    $conte = $x['CONTENEDOR'];
                    $equipo = $x['EQUIPO'];
                    $tipo = getEquipoTipo($equipo);
                    $eir = $x['EIR'];
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
                        $enc2= "EQD+CN+$conte+$tipo++$stIE+$stCo'".$sepa;
                        $tlSegmentos++;
                        $enc2.="DTM+7:$regFecha$regHora:203'".$sepa;
                        $tlSegmentos++;
                        $enc2.="FTX+AAI+++$transportista/$eir'".$sepa;
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'".$sepa;
                        $tlSegmentos++;
                        //$enc2.="LOC+99+$codePatio+::ZZZ'".$sepa;   // 99 : Place of empty equipment return
                        //$tlSegmentos++;
                        fputs($fp2,$enc2);
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

            /*
            /// ------------------------------
            // GENERACION DE ARCHIVO
            // ------------------------------
            if( $opcPro=="saveAs" ){
                // -----------------------------
                // SALVAR COMO... O ABRIR EN AUTO.
                // (No modificar)
                // -----------------------------
                if( file_exists("$fileEDI") ){
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename='.basename($fileEDI));
                    header('Content-Transfer-Encoding: binary');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($fileEDI));
                    ob_clean();
                    flush();
                    readfile("$fileEDI");
                    exit;
                }
            }
            elseif( $opcPro=="email" ){
                $fileName="GATE-IN".$ofiCod.$fecD1.$fecD2.".edi";
                sendMailMSC($fileEDI,'GATE-IN',$fileName);
            }
            elseif( $opcPro=="ftp" ){
                // -----------------------------
                // FTP - GETIN
                // -----------------------------
                $ftp_server = "187.174.238.23";
                switch( $codOD ){
                    case "LMR00":
                        $ftp_user_name = "terralmr";
                        $ftp_user_pass = "lmr03";
                        break;
                    case "LZC00":
                        $ftp_user_name = "terralzc";
                        $ftp_user_pass = "lzc03";
                        break;
                    case "ZLO00":
                        $ftp_user_name = "terrazlo";
                        $ftp_user_pass = "zlo03";
                        break;
                    case "VER00":
                        $ftp_user_name = "terraver";
                        $ftp_user_pass = "ver03";
                        break;
                }
                $source_file = $fileEDI;
                $destination_file = str_replace("../edi_files/edi/","",$source_file);
                // --
                // --
                $conn_id = ftp_connect($ftp_server);
                $login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
                // verificar la conexión
                if ((!$conn_id) || (!$login_result)) {
                    $msg[] = "<font color=red>¡La conexión FTP ha fallado ponerse en contacto con Lic.Nestor Perez Tel: 5091-7636 / 5532224753 </font>";
                    $msg[] = "<font color=red>Se intentó conectar al $ftp_server por el usuario $ftp_user_name</font>";
                    exit;
                } else {
                    $msg[]= "<font color=blue>Conexión a $ftp_server realizada con éxito, por el usuario $ftp_user_name</font>";
                }
                // subir un archivo
                $upload = ftp_put($conn_id, $destination_file, $source_file, FTP_BINARY);
                // comprobar el estado de la subida
                if (!$upload) {
                    $msg[] = "<font color=red>La subida FTP ha fallado! </font>";
                } else {
                    $msg[] = "<font color=blue>El Envio a FTP $ftp_server ha sido exitoso!. Archivo : $destination_file </font>";
                    //sendMailMSC('GETA-IN');
                }
                // cerrar la conexión ftp
                ftp_close($conn_id);
            }
            */

        }

    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateOutMscJose($deli,$stConte,$impoExpo,$sesIdUsuario,$codOD,$opcPro){
        global $db,$db2,$HTTP_POST_FILES,$hoy,$t;


        // --------------------------------------------
        // Proceso de copiado.
        // --------------------------------------------
        $hoy = date("Y-m-d H:i");
        // Copia el archivo del usuario al directorio ../files
        if ( is_uploaded_file($HTTP_POST_FILES['userfile']['tmp_name']) ) {
            copy($HTTP_POST_FILES['userfile']['tmp_name'], "../edi_files/csv/salidas.csv");
        }
        else {
            $msg="<h1><font color=\"red\">Error en el envio del archivo, vuela a intentarlo! " . $HTTP_POST_FILES['userfile']['name'];
            $msg.="</font></h1>";
            echo $msg;
        }

        // ----------------------------------------
        // GATE OUT
        // ----------------------------------------
        // Validar encabezados.
        unset($fp);
        unset($data);
        unset($dataX);
        $fp = fopen("../edi_files/csv/salidas.csv","r");
        $validFile="True";
        // Linea del encabezado
        $l=1;
        while ( $data = fgetcsv($fp,1000,"$deli") ) {
            if($l==5){
                foreach($data as $campo){
                    $campo=addslashes($campo);
                    $campo= str_replace("\n","",$campo);
                    $campo= str_replace("\r","",$campo);
                    $dataX[]=$campo;
                }

                // Nuevo Formato de Salidas
                if( $dataX[0]!="NAVIERA"){$validFile="False"; $msg[] =  "[Error][Encabezado] NAVIERA, esta incorrecto."; }
                if( $dataX[1]!="CONTENEDOR"){$validFile="False"; $msg[] =  "[Error][Encabezado] CONTENEDOR, esta incorrecto. "; }
                if( $dataX[2]!="TAM"){$validFile="False"; $msg[] =  "[Error][Encabezado] TAM, esta incorrecto. "; }
                if( $dataX[3]!="CALIDAD"){$validFile="False"; $msg[] =  "[Error][Encabezado] CALIDAD, esta incorrecto. "; }
                if( $dataX[4]!="FACTURA ACON."){$validFile="False"; $msg[] =  "[Error][Encabezado] FACTURA ACON., esta incorrecto."; }
                if( $dataX[5]!="COSTO DE ACON."){$validFile="False"; $msg[] =  "[Error][Encabezado] COSTO DE ACON., esta incorrecto. "; }
                if( $dataX[6]!="EIR"){$validFile="False"; $msg[] =  "[Error][Encabezado] EIR, esta incorrecto.  "; }
                if( $dataX[7]!="FECHA "){$validFile="False"; $msg[] =  "[Error][Encabezado] FECHA, esta incorrecto."; }
                if( $dataX[8]!="HR"){$validFile="False"; $msg[] =  "[Error][Encabezado] HR "; }
                if( $dataX[9]!="MANIOBRA"){$validFile="False"; $msg[] =  "[Error][Encabezado] MANIOBRA, esta incorrecto. "; }
                if( $dataX[10]!="COSTO"){$validFile="False"; $msg[] =  "[Error][Encabezado] COSTO, esta incorrecto. "; }
                if( $dataX[11]!="FACTURA"){$validFile="False"; $msg[] =  "[Error][Encabezado] FACTURA, esta incorrecto. "; }
                if( $dataX[12]!="TRANSPORTISTA"){$validFile="False"; $msg[] =  "[Error][Encabezado] TRANSPORTISTA, esta incorrecto. "; }
                if( $dataX[13]!="CLIENTE"){$validFile="False"; $msg[] =  "[Error][Encabezado] CLIENTE, esta incorrecto. "; }
                if( $dataX[14]!="BOOKING"){$validFile="False"; $msg[] =  "[Error][Encabezado] BOOKING, esta incorrecto. "; }

            }
            $l++;
        }
        fclose($fp);

        // IMPRIMIR ERRORES
        if( is_array($msg) ){
            if( count($msg)>0 ){
                foreach( $msg as $msgX ){
                    echo "<font color=red>$msgX</font><br>";
                }
            }
        }

        if( $validFile=="True" ){


            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $fileName = substr($csvFile,0,-4).".edi";
            $flgRec=1;
            $fp= fopen("../edi_files/csv/salidas.csv","r");
            $l=1;
            $f=date(Ymd);
            //$fileEDI = "../edi_files/edi/$fileName";
            $fileEDI = "../edi_files/edi/GATE-OUT".$fecD1.$fecD2.".edi";
            $fp2 = fopen("$fileEDI","w");
            $sepa="\n";

            $codPatio = getValueTable("cod_patio","USUARIO","id_usuario",$sesIdUsuario);
            $codPatioName = getValueTable("cod_patio_name","USUARIO","id_usuario",$sesIdUsuario);
            $senderID = getValueTable("sender_id","USUARIO","id_usuario",$sesIdUsuario);
            $receiverID="MSC";
            // ENCABEZADO
            //$enc= "UNB+UNOA:1+TERRAPORTS++$fecD1:$fecD2+$fecD1$fecD2'".$sepa;
            $enc= "UNB+UNOA:1+$senderID+$receiverID+$fecD1:$fecD2+$fecD1$fecD2'".$sepa;
            $enc.="UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'".$sepa;
            $enc.="BGM+36+$fecD1$fecD2+9'".$sepa;
            //$enc.="LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'".$sepa;
            //"NAD+CA+MXVRCDECECI:172:166'".$sepa;
            //"TDT+20++1++MSC:172:166'".$sepa.
            //$enc11=utf8_encode($enc);
            fputs($fp2,$enc);
            //$e=0;  // elemento del arreglo.
            $tlConte=0;
            $tlSegmentos =4;
            while ($data = fgetcsv($fp,1000,"$deli")) {
                if( $l > 5 ){
                    unset($dataX);
                    foreach($data as $campo){
                        $campo=addslashes($campo);
                        $campo= str_replace("\n","",$campo);
                        $campo= str_replace("\r","",$campo);
                        $dataX[]=$campo;
                    }
                    $conte= $dataX[1];

                    // ---------------------
                    // FECHA HORA MINUTO
                    // ---------------------
                    $fecha= $dataX[7];

                    $validFec=0;
                    if( preg_match("/(\d{2})\/(\d{2})\/(\d{4})/i",$fecha,$parts) ){
                        $d= $parts[1];
                        $m= $parts[2];
                        $a= $parts[3];
                        $validFec=1;
                    }
                    elseif( preg_match("/(\d{4})\/(\d{2})\/(\d{2)/i",$fecha,$parts) ){
                        $d= $parts[3];
                        $m= $parts[2];
                        $a= $parts[1];
                        $validFec=1;
                    }
                    elseif( preg_match("/(\d{2})-(\d{2})-(\d{4})/i",$fecha,$parts) ){
                        $d= $parts[1];
                        $m= $parts[2];
                        $a= $parts[3];
                        $validFec=1;
                    }
                    // Hora:Minuto Formato 24 hrs.
                    $hora= $dataX[8];
                    unset($parts);
                    if( preg_match("/(\d{2}):(\d{2})/",$hora,$parts) ){
                        $hr= $parts[1];
                        if($hr=="24"){$hr="23";};
                        $min= $parts[2];
                    }

                    // -------------
                    // Validacion
                    // -------------
                    if( (!empty($conte)) && ($validFec==0) ){
                        $msgErr[] = "<font color=red>[Error][$conte] La fecha es incorrecta.</font>";
                    }

                    /*
                    $a= trim($a);
                    $thisYear = date("Y");
                    if( $a<>$thisYear ){
                    $msgErr[] = "<font color=red>[Error][$conte] Año incorrecto, favor de corregir el formato de la fecha en su archivo CSV.</font>";
                    $validFec=0;
                    }
                    */
                    if( !empty($conte) && ( empty($hr) || empty($min) )  ){
                        $msgErr[] = "<font color=red>[Error][$conte] Hora ($hr) o Minuto ($min) incorrecto, favor de corregir el formato a 24 hrs.</font>";
                        $validFec=0;
                    }
                    // -----------------

                    if( !empty($conte) && ( $validFec==1 )  ){
                        $tlConte++;
                        $tamano= $dataX[2];
                        $bkgNumber = $dataX[14];
                        $bkgNumber = strtoupper($bkgNumber);
                        $cliente = $dataX[13];
                        $cliente = strtoupper($cliente);
                        $transportista= $dataX[12];
                        $transportista = strtoupper($transportista);
                        $calidad = $dataX[3];
                        $eir= $dataX[6];
                        //$sello= $dataX[];
                        $maniobra= $dataX[9];
                        $maniobra = strtoupper($maniobra);
                        $regFecha= $a.$m.$d;
                        $regHora= $hr.$min;
                        $conte= trim($conte);
                        //$conte=preg_replace($pattern,$replacement,$conte);
                        $tamano= strtoupper($tamano);
                        $tamano = str_replace(" ","",$tamano);

                        // 20' DRY VAN
                        if( $tamano=="22.10" || $tamano=="22.1" || $tamano=="20DC" || $tamano=="20DV" ){$tipo="22G0";}
                        // 20' FLAT COLLAPSIBLE
                        if( $tamano=="22.63" || $tamano=="20FL" ){$tipo="22P3";}
                        // 20' REEFER
                        if( $tamano=="22.32" || $tamano=="20RF" ){$tipo="22R1";}
                        // 20CT TANK CONTAINER
                        if( $tamano=="22.70" || $tamano=="22.7" || $tamano=="20TK" ){$tipo="22T3";}
                        // 20OT OPEN TOP
                        if( $tamano=="22.51" || $tamano=="20OT" ){$tipo="22U1";}
                        // 40' DRY VAN
                        if( $tamano=="43.10" || $tamano=="43.1" || $tamano=="40DC" ){$tipo="42G0";}
                        // 40' HIGH CUBE
                        if( $tamano=="45.10" || $tamano=="45.1" || $tamano=="40HC" ){$tipo="45G0";}
                        // 40' OPEN TOP
                        if( $tamano=="43.51" || $tamano=="40OT" ){$tipo="42U1";}
                        // RF4/40' REEFER
                        if( $tamano=="43.32" || $tamano=="40RF" ){$tipo="42R1";}
                        // 40CT TANK CONTAINER
                        if( $tamano=="43.70" || $tamano=="40TK" ){$tipo="42T0";   }


                        $remarks = $dataX[17];
                        $codDep = $dataX[16];
                        if( empty($codDep) ){
                            $codDep = $codPatio;
                        }

                        // Cod.Deposito
                        //
                        $enc2="LOC+165+$codDep:139:6+:TER:ZZZ'".$sepa;


                        //---------------------------------------------------------
                        // IMPOEXPO : 2 EXPORT ó 3 IMPORT
                        // TIPOMOV : 4 (E)MPTY Ó 5 (F)ULL
                        //----------------------------------------------------------
                        // FTX : Remarks +AAI = General Information
                        ($impoExpo=="E")?$stIE=2:$stIE=3;
                        ($stConte=="E")?$stCo=4:$stCo=5;
                        $conte = strtoupper($conte);
                        $enc2.= "EQD+CN+$conte+$tipo++$stIE+$stCo'".$sepa;
                        $tlSegmentos++;
                        $enc2.="DTM+7:$regFecha$regHora:203'".$sepa;
                        $tlSegmentos++;
                        $enc2.="RFF+BN:$bkgNumber'".$sepa;
                        $tlSegmentos++;
                        $enc2.="FTX+AAI+++$bkgNumber/$cliente/$transportista/$remarks'".$sepa;
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'".$sepa;  // 3 : Equivale a Camion y 1. Es Tren.
                        $tlSegmentos++;
                        // Cuando sean salidas es necesario agregar esta línea para cada movimiento,
                        // ya que con este se especifica el Destination Depot.
                        $enc2.="LOC+99+$codOD'".$sepa;   // 99 : Place of empty equipment return
                        $tlSegmentos++;
                        fputs($fp2,$enc2);
                    }
                }
                $l++;
            } // fin del while

            // Pie de pagina
            // Total de contenedores
            $pie="CNT+16:$tlConte'".$sepa;
            $tlSegmentos++;
            $pie.="UNT+$tlSegmentos+$fecD1$fecD2'".$sepa;
            $pie.="UNZ+1+$fecD1$fecD2'";

            fputs($fp2,$pie);
            fclose($fp2);
            fclose($fp);


            // -------------------
            // Mensajes de error:
            // -------------------
            if( count($msgErr)>0 ){
                foreach( $msgErr as $msgY ){
                    echo "<font color=red>[<b>Error</b>] $msgY</font><br>";
                }
            }
            else{
                // echo "<font color=green>[<b>OK</b>] Archivo sin Errores</font>";
                if( $opcPro=="saveAs" ){
                    // -----------------------------
                    // SALVAR COMO... O ABRIR EN AUTO.
                    // (No modificar)
                    // -----------------------------
                    if(file_exists("$fileEDI")){
                        header('Content-Description: File Transfer');
                        header('Content-Type: application/octet-stream');
                        header('Content-Disposition: attachment; filename='.basename($fileEDI));
                        header('Content-Transfer-Encoding: binary');
                        header('Expires: 0');
                        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                        header('Pragma: public');
                        header('Content-Length: ' . filesize($fileEDI));
                        ob_clean();
                        flush();
                        readfile("$fileEDI");
                        exit;
                    }
                }
                elseif( $opcPro=="email" ){
                    $fileName="GATE-OUT".$ofiCod.$fecD1.$fecD2.".edi";
                    sendMailMSC($fileEDI,'GATE-OUT',$fileName);
                }
                elseif( $opcPro=="ftp" ){
                    // -----------------------------
                    // FTP - GETOUT
                    // -----------------------------
                    $ftp_server = "187.174.238.23";
                    switch( $codOD ){
                        case "LMR00":
                            $ftp_user_name = "terralmr";
                            $ftp_user_pass = "lmr03";
                            break;
                        case "LZC00":
                            $ftp_user_name = "terralzc";
                            $ftp_user_pass = "lzc03";
                            break;
                        case "ZLO00":
                            $ftp_user_name = "terrazlo";
                            $ftp_user_pass = "zlo03";
                            break;
                        case "VER00":
                            $ftp_user_name = "terraver";
                            $ftp_user_pass = "ver03";
                            break;
                    }
                    $source_file = $fileEDI;
                    $destination_file = str_replace("../edi_files/edi/","",$source_file);
                    // --
                    $conn_id = ftp_connect($ftp_server);
                    $login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
                    // verificar la conexión
                    if ((!$conn_id) || (!$login_result)) {
                        $msg[] = "<font color=red>¡La conexión FTP ha fallado ponerse en contacto con Lic.Nestor Perez Tel: 5091-7636 / 5532224753 </font>";
                        $msg[] = "<font color=red>Se intentó conectar al $ftp_server por el usuario $ftp_user_name</font>";
                        exit;
                    } else {
                        $msg[]= "<font color=blue>Conexión a $ftp_server realizada con éxito, por el usuario $ftp_user_name</font>";
                    }
                    // subir un archivo
                    $upload = ftp_put($conn_id, $destination_file, $source_file, FTP_BINARY);
                    // comprobar el estado de la subida
                    if (!$upload) {
                        $msg[] = "<font color=red>La subida FTP ha fallado! </font>";
                    } else {
                        $msg[] = "<font color=blue>El Envio a FTP $ftp_server ha sido exitoso!. Archivo : $destination_file </font>";
                        //sendMailMSC('GET-OUT');
                    }
                    // cerrar la conexión ftp
                    ftp_close($conn_id);
                }
            }
        }
        return $msg;
    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateOutMsc($deli,$stConte,$impoExpo,$sesIdUsuario,$codOD,$opcPro){
        global $db,$db2,$HTTP_POST_FILES,$hoy,$t;


        // --------------------------------------------
        // Proceso de copiado.
        // --------------------------------------------
        $hoy = date("Y-m-d H:i");
        // Copia el archivo del usuario al directorio ../files
        if ( is_uploaded_file($HTTP_POST_FILES['userfile']['tmp_name']) ) {
            copy($HTTP_POST_FILES['userfile']['tmp_name'], "../edi_files/csv/salidas.csv");
        }
        else {
            $msg="<h1><font color=\"red\">Error en el envio del archivo, vuela a intentarlo! " . $HTTP_POST_FILES['userfile']['name'];
            $msg.="</font></h1>";
            echo $msg;
        }

        // ----------------------------------------
        // GATE OUT
        // ----------------------------------------
        // Validar encabezados.
        unset($fp);
        unset($data);
        unset($dataX);
        $fp = fopen("../edi_files/csv/salidas.csv","r");
        $validFile="True";
        // Linea del encabezado
        $l=1;
        while ( $data = fgetcsv($fp,1000,"$deli") ) {
            if($l==5){
                foreach($data as $campo){
                    $campo=addslashes($campo);
                    $campo= str_replace("\n","",$campo);
                    $campo= str_replace("\r","",$campo);
                    $dataX[]=$campo;
                }

                // Nuevo Formato de Salidas
                if( $dataX[0]!="NAVIERA"){$validFile="False"; $msg[] =  "[Error][Encabezado] NAVIERA, esta incorrecto."; }
                if( $dataX[1]!="CONTENEDOR"){$validFile="False"; $msg[] =  "[Error][Encabezado] CONTENEDOR, esta incorrecto. "; }
                if( $dataX[2]!="TAM"){$validFile="False"; $msg[] =  "[Error][Encabezado] TAM, esta incorrecto. "; }
                if( $dataX[3]!="CALIDAD"){$validFile="False"; $msg[] =  "[Error][Encabezado] CALIDAD, esta incorrecto. "; }
                if( $dataX[4]!="FACTURA ACON."){$validFile="False"; $msg[] =  "[Error][Encabezado] FACTURA ACON., esta incorrecto."; }
                if( $dataX[5]!="COSTO DE ACON."){$validFile="False"; $msg[] =  "[Error][Encabezado] COSTO DE ACON., esta incorrecto. "; }
                if( $dataX[6]!="EIR"){$validFile="False"; $msg[] =  "[Error][Encabezado] EIR, esta incorrecto.  "; }
                if( $dataX[7]!="FECHA "){$validFile="False"; $msg[] =  "[Error][Encabezado] FECHA, esta incorrecto."; }
                if( $dataX[8]!="HR"){$validFile="False"; $msg[] =  "[Error][Encabezado] HR "; }
                if( $dataX[9]!="MANIOBRA"){$validFile="False"; $msg[] =  "[Error][Encabezado] MANIOBRA, esta incorrecto. "; }
                if( $dataX[10]!="COSTO"){$validFile="False"; $msg[] =  "[Error][Encabezado] COSTO, esta incorrecto. "; }
                if( $dataX[11]!="FACTURA"){$validFile="False"; $msg[] =  "[Error][Encabezado] FACTURA, esta incorrecto. "; }
                if( $dataX[12]!="TRANSPORTISTA"){$validFile="False"; $msg[] =  "[Error][Encabezado] TRANSPORTISTA, esta incorrecto. "; }
                if( $dataX[13]!="CLIENTE"){$validFile="False"; $msg[] =  "[Error][Encabezado] CLIENTE, esta incorrecto. "; }
                if( $dataX[14]!="BOOKING"){$validFile="False"; $msg[] =  "[Error][Encabezado] BOOKING, esta incorrecto. "; }

            }
            $l++;
        }
        fclose($fp);

        // IMPRIMIR ERRORES
        if( is_array($msg) ){
            if( count($msg)>0 ){
                foreach( $msg as $msgX ){
                    echo "<font color=red>$msgX</font><br>";
                }
            }
        }

        if( $validFile=="True" ){


            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $fileName = substr($csvFile,0,-4).".edi";
            $flgRec=1;
            $fp= fopen("../edi_files/csv/salidas.csv","r");
            $l=1;
            $f=date(Ymd);
            //$fileEDI = "../edi_files/edi/$fileName";
            $fileEDI = "../edi_files/edi/GATE-OUT".$fecD1.$fecD2.".edi";
            $fp2 = fopen("$fileEDI","w");
            $sepa="\n";

            $codPatio = getValueTable("cod_patio","USUARIO","id_usuario",$sesIdUsuario);
            $codPatioName = getValueTable("cod_patio_name","USUARIO","id_usuario",$sesIdUsuario);
            $senderID = getValueTable("sender_id","USUARIO","id_usuario",$sesIdUsuario);
            $receiverID="MSC";
            // ENCABEZADO
            //$enc= "UNB+UNOA:1+TERRAPORTS++$fecD1:$fecD2+$fecD1$fecD2'".$sepa;
            $enc= "UNB+UNOA:1+$senderID+$receiverID+$fecD1:$fecD2+$fecD1$fecD2'".$sepa;
            $enc.="UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'".$sepa;
            $enc.="BGM+36+$fecD1$fecD2+9'".$sepa;
            $enc.="LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'".$sepa;
            //"NAD+CA+MXVRCDECECI:172:166'".$sepa;
            //"TDT+20++1++MSC:172:166'".$sepa.
            //$enc11=utf8_encode($enc);
            fputs($fp2,$enc);
            //$e=0;  // elemento del arreglo.
            $tlConte=0;
            $tlSegmentos =4;
            while ($data = fgetcsv($fp,1000,"$deli")) {
                if( $l > 5 ){
                    unset($dataX);
                    foreach($data as $campo){
                        $campo=addslashes($campo);
                        $campo= str_replace("\n","",$campo);
                        $campo= str_replace("\r","",$campo);
                        $dataX[]=$campo;
                    }
                    $conte= $dataX[1];

                    // ---------------------
                    // FECHA HORA MINUTO
                    // ---------------------
                    $fecha= $dataX[7];

                    $validFec=0;
                    if( preg_match("/(\d{2})\/(\d{2})\/(\d{4})/i",$fecha,$parts) ){
                        $d= $parts[1];
                        $m= $parts[2];
                        $a= $parts[3];
                        $validFec=1;
                    }
                    elseif( preg_match("/(\d{4})\/(\d{2})\/(\d{2)/i",$fecha,$parts) ){
                        $d= $parts[3];
                        $m= $parts[2];
                        $a= $parts[1];
                        $validFec=1;
                    }
                    elseif( preg_match("/(\d{2})-(\d{2})-(\d{4})/i",$fecha,$parts) ){
                        $d= $parts[1];
                        $m= $parts[2];
                        $a= $parts[3];
                        $validFec=1;
                    }
                    // Hora:Minuto Formato 24 hrs.
                    $hora= $dataX[8];
                    unset($parts);
                    if( preg_match("/(\d{2}):(\d{2})/",$hora,$parts) ){
                        $hr= $parts[1];
                        if($hr=="24"){$hr="23";};
                        $min= $parts[2];
                    }

                    // -------------
                    // Validacion
                    // -------------
                    if( (!empty($conte)) && ($validFec==0) ){
                        $msgErr[] = "<font color=red>[Error][$conte] La fecha es incorrecta.</font>";
                    }

                    /*
                    $a= trim($a);
                    $thisYear = date("Y");
                    if( $a<>$thisYear ){
                    $msgErr[] = "<font color=red>[Error][$conte] Año incorrecto, favor de corregir el formato de la fecha en su archivo CSV.</font>";
                    $validFec=0;
                    }
                    */
                    if( !empty($conte) && ( empty($hr) || empty($min) )  ){
                        $msgErr[] = "<font color=red>[Error][$conte] Hora ($hr) o Minuto ($min) incorrecto, favor de corregir el formato a 24 hrs.</font>";
                        $validFec=0;
                    }
                    // -----------------

                    if( !empty($conte) && ( $validFec==1 )  ){
                        $tlConte++;
                        $tamano= $dataX[2];
                        $bkgNumber = $dataX[14];
                        $bkgNumber = strtoupper($bkgNumber);
                        $cliente = $dataX[13];
                        $cliente = strtoupper($cliente);
                        $transportista= $dataX[12];
                        $transportista = strtoupper($transportista);
                        $calidad = $dataX[3];
                        $eir= $dataX[6];
                        //$sello= $dataX[];
                        $maniobra= $dataX[9];
                        $maniobra = strtoupper($maniobra);
                        $regFecha= $a.$m.$d;
                        $regHora= $hr.$min;
                        $conte= trim($conte);
                        //$conte=preg_replace($pattern,$replacement,$conte);
                        $tamano= strtoupper($tamano);
                        $tamano = str_replace(" ","",$tamano);

                        // 20' DRY VAN
                        if( $tamano=="22.10" || $tamano=="22.1" || $tamano=="20DC" || $tamano=="20DV" ){$tipo="22G0";}
                        // 20' FLAT COLLAPSIBLE
                        if( $tamano=="22.63" || $tamano=="20FL" ){$tipo="22P3";}
                        // 20' REEFER
                        if( $tamano=="22.32" || $tamano=="20RF" ){$tipo="22R1";}
                        // 20CT TANK CONTAINER
                        if( $tamano=="22.70" || $tamano=="22.7" || $tamano=="20TK" ){$tipo="22T3";}
                        // 20OT OPEN TOP
                        if( $tamano=="22.51" || $tamano=="20OT" ){$tipo="22U1";}
                        // 40' DRY VAN
                        if( $tamano=="43.10" || $tamano=="43.1" || $tamano=="40DC" ){$tipo="42G0";}
                        // 40' HIGH CUBE
                        if( $tamano=="45.10" || $tamano=="45.1" || $tamano=="40HC" ){$tipo="45G0";}
                        // 40' OPEN TOP
                        if( $tamano=="43.51" || $tamano=="40OT" ){$tipo="42U1";}
                        // RF4/40' REEFER
                        if( $tamano=="43.32" || $tamano=="40RF" ){$tipo="42R1";}
                        // 40CT TANK CONTAINER
                        if( $tamano=="43.70" || $tamano=="40TK" ){$tipo="42T0";   }


                        // --------------------------------------------------------------------------------------------------------------
                        // CODIGO SOLO TRANE S.A. DE C.V.
                        // --------------------------------------------------------------------------------------------------------------
                        // Solo para trene, el codigo LOC+99 debe ser variable, se debe reconocer el no.Booking para determinar el codigo.
                        // 192 = LMR00
                        // 364 = ZLO00
                        // 191 = VER00
                        // 404 = MZT00
                        // 697 = GYM00
                        // 620 = LZC00
                        // 622 = PUM00
                        $login = getValueTable("login","USUARIO","id_usuario",$sesIdUsuario);
                        $login = strtoupper($login);
                        unset($parts);
                        if( $login=="TRANE" ){
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
                        }




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
                        $enc2.="DTM+7:$regFecha$regHora:203'".$sepa;
                        $tlSegmentos++;
                        $enc2.="RFF+BN:$bkgNumber'".$sepa;
                        $tlSegmentos++;
                        $enc2.="FTX+AAI+++$bkgNumber/$cliente/$transportista'".$sepa;
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'".$sepa;  // 3 : Equivale a Camion y 1. Es Tren.
                        $tlSegmentos++;
                        // Cuando sean salidas es necesario agregar esta línea para cada movimiento,
                        // ya que con este se especifica el Destination Depot.
                        $enc2.="LOC+99+$codOD'".$sepa;   // 99 : Place of empty equipment return
                        $tlSegmentos++;
                        fputs($fp2,$enc2);
                    }
                }
                $l++;
            } // fin del while

            // Pie de pagina
            // Total de contenedores
            $pie="CNT+16:$tlConte'".$sepa;
            $tlSegmentos++;
            $pie.="UNT+$tlSegmentos+$fecD1$fecD2'".$sepa;
            $pie.="UNZ+1+$fecD1$fecD2'";

            fputs($fp2,$pie);
            fclose($fp2);
            fclose($fp);


            // -------------------
            // Mensajes de error:
            // -------------------
            if( count($msgErr)>0 ){
                foreach( $msgErr as $msgY ){
                    echo "<font color=red>[<b>Error</b>] $msgY</font><br>";
                }
            }
            else{
                // echo "<font color=green>[<b>OK</b>] Archivo sin Errores</font>";
                if( $opcPro=="saveAs" ){
                    // -----------------------------
                    // SALVAR COMO... O ABRIR EN AUTO.
                    // (No modificar)
                    // -----------------------------
                    if(file_exists("$fileEDI")){
                        header('Content-Description: File Transfer');
                        header('Content-Type: application/octet-stream');
                        header('Content-Disposition: attachment; filename='.basename($fileEDI));
                        header('Content-Transfer-Encoding: binary');
                        header('Expires: 0');
                        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                        header('Pragma: public');
                        header('Content-Length: ' . filesize($fileEDI));
                        ob_clean();
                        flush();
                        readfile("$fileEDI");
                        exit;
                    }
                }
                elseif( $opcPro=="email" ){
                    $fileName="GATE-OUT".$ofiCod.$fecD1.$fecD2.".edi";
                    sendMailMSC($fileEDI,'GATE-OUT',$fileName);
                }
                elseif( $opcPro=="ftp" ){
                    // -----------------------------
                    // FTP - GETOUT
                    // -----------------------------
                    $ftp_server = "187.174.238.23";
                    switch( $codOD ){
                        case "LMR00":
                            $ftp_user_name = "terralmr";
                            $ftp_user_pass = "lmr03";
                            break;
                        case "LZC00":
                            $ftp_user_name = "terralzc";
                            $ftp_user_pass = "lzc03";
                            break;
                        case "ZLO00":
                            $ftp_user_name = "terrazlo";
                            $ftp_user_pass = "zlo03";
                            break;
                        case "VER00":
                            $ftp_user_name = "terraver";
                            $ftp_user_pass = "ver03";
                            break;
                    }
                    $source_file = $fileEDI;
                    $destination_file = str_replace("../edi_files/edi/","",$source_file);
                    // --
                    $conn_id = ftp_connect($ftp_server);
                    $login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
                    // verificar la conexión
                    if ((!$conn_id) || (!$login_result)) {
                        $msg[] = "<font color=red>¡La conexión FTP ha fallado ponerse en contacto con Lic.Nestor Perez Tel: 5091-7636 / 5532224753 </font>";
                        $msg[] = "<font color=red>Se intentó conectar al $ftp_server por el usuario $ftp_user_name</font>";
                        exit;
                    } else {
                        $msg[]= "<font color=blue>Conexión a $ftp_server realizada con éxito, por el usuario $ftp_user_name</font>";
                    }
                    // subir un archivo
                    $upload = ftp_put($conn_id, $destination_file, $source_file, FTP_BINARY);
                    // comprobar el estado de la subida
                    if (!$upload) {
                        $msg[] = "<font color=red>La subida FTP ha fallado! </font>";
                    } else {
                        $msg[] = "<font color=blue>El Envio a FTP $ftp_server ha sido exitoso!. Archivo : $destination_file </font>";
                        //sendMailMSC('GET-OUT');
                    }
                    // cerrar la conexión ftp
                    ftp_close($conn_id);
                }
            }
        }
        return $msg;
    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediPosMsc($deli,$stConte,$impoExpo,$sesOficina,$codOD,$opcPro){
        global $db,$db2,$HTTP_POST_FILES,$hoy,$t;


        // --------------------------------------------
        // Proceso de copiado.
        // --------------------------------------------
        $hoy = date("Y-m-d H:i");
        // Copia el archivo del usuario al directorio ../files
        if ( is_uploaded_file($HTTP_POST_FILES['userfile']['tmp_name']) ) {
            copy($HTTP_POST_FILES['userfile']['tmp_name'], "../edi_files/csv/positions.csv");
        }
        else {
            $msg="<h1><font color=\"red\">Error en el envio del archivo, vuela a intentarlo! " . $HTTP_POST_FILES['userfile']['name'];
            $msg.="</font></h1>";
            echo $msg;
        }

        // ----------------------------------------
        // Salidas
        // ----------------------------------------
        // Validar encabezados.
        unset($fp);
        unset($data);
        unset($dataX);
        $fp = fopen("../edi_files/csv/positions.csv","r");
        $validFile="True";
        // Linea del encabezado
        $l=1;
        while ( $data = fgetcsv($fp,1000,"$deli") ) {
            if($l==3){
                foreach($data as $campo){
                    $campo=addslashes($campo);
                    $campo= str_replace("\n","",$campo);
                    $campo= str_replace("\r","",$campo);
                    $dataX[]=$campo;
                }

                // Nuevo Formato de Salidas
                if( $dataX[0]!="NAVIERA"){$validFile="False"; $msg[] =  "[Error][Encabezado] NAVIERA, esta incorrecto."; }
                if( $dataX[1]!="CONTENEDOR"){$validFile="False"; $msg[] =  "[Error][Encabezado] CONTENEDOR, esta incorrecto. "; }
                if( $dataX[2]!="TAM"){$validFile="False"; $msg[] =  "[Error][Encabezado] TAM, esta incorrecto. "; }
                if( $dataX[3]!="CALIDAD"){$validFile="False"; $msg[] =  "[Error][Encabezado] CALIDAD, esta incorrecto. "; }
                if( $dataX[4]!="FACTURA ACON."){$validFile="False"; $msg[] =  "[Error][Encabezado] FACTURA ACON., esta incorrecto."; }
                if( $dataX[5]!="COSTO DE ACON."){$validFile="False"; $msg[] =  "[Error][Encabezado] COSTO DE ACON., esta incorrecto. "; }
                if( $dataX[6]!="EIR"){$validFile="False"; $msg[] =  "[Error][Encabezado] EIR, esta incorrecto.  "; }
                if( $dataX[7]!="FECHA "){$validFile="False"; $msg[] =  "[Error][Encabezado] FECHA, esta incorrecto."; }
                if( $dataX[8]!="HR"){$validFile="False"; $msg[] =  "[Error][Encabezado] HR "; }
                if( $dataX[9]!="MANIOBRA"){$validFile="False"; $msg[] =  "[Error][Encabezado] MANIOBRA, esta incorrecto. "; }
                if( $dataX[10]!="COSTO"){$validFile="False"; $msg[] =  "[Error][Encabezado] COSTO, esta incorrecto. "; }
                if( $dataX[11]!="FACTURA"){$validFile="False"; $msg[] =  "[Error][Encabezado] FACTURA, esta incorrecto. "; }
                if( $dataX[12]!="TRANSPORTISTA"){$validFile="False"; $msg[] =  "[Error][Encabezado] TRANSPORTISTA, esta incorrecto. "; }
                if( $dataX[13]!="CLIENTE"){$validFile="False"; $msg[] =  "[Error][Encabezado] CLIENTE, esta incorrecto. "; }
                if( $dataX[14]!="BOOKING"){$validFile="False"; $msg[] =  "[Error][Encabezado] BOOKING, esta incorrecto. "; }
                $l++;
            }
            fclose($fp);

            // IMPRIMIR ERRORES
            if( is_array($msg) ){
                if( count($msg)>0 ){
                    foreach( $msg as $msgX ){
                        echo "<font color=red>$msgX</font><br>";
                    }
                }
            }

            if( $validFile=="True" ){
                if( $sesOficina=="VERACRUZ" ){
                    $ofiCod="VR";
                }
                elseif($sesOficina=="MEXICO"){
                    $ofiCod="MX";
                }

                $fecD1 = date("ymd");
                $fecD2 = date("Hi");
                $fileName = substr($csvFile,0,-4).".edi";
                $flgRec=1;
                $fp= fopen("../edi_files/csv/positions.csv","r");
                $l=1;
                $f=date(Ymd);
                //$fileEDI = "../edi_files/edi/$fileName";
                $fileEDI = "../edi_files/edi/POSITIONS".$ofiCod.$fecD1.$fecD2.".edi";
                $fp2 = fopen("$fileEDI","w");
                $sepa="\n";

                if( $sesOficina=="VERACRUZ" ){
                    $codePatio = "VER07";
                }
                elseif( $sesOficina=="MEXICO" ){
                    $codePatio = "MEX03";
                }
                $patioNom = "TERRAPORTS";

                $senderID = getValueTable("sender_id","USUARIO","id_usuario",$sesIdUsuario);
                $receiverID="MSC";

                // ENCABEZADO
                //$enc= "UNB+UNOA:1+TERRAPORTS++$fecD1:$fecD2+$fecD1$fecD2'".$sepa;
                $enc= "UNB+UNOA:1+$senderID+$receiverID+$fecD1:$fecD2+$fecD1$fecD2'".$sepa;
                $enc.="UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'".$sepa;
                $enc.="BGM+36+$fecD1$fecD2+9'".$sepa;
                $enc.="LOC+165+$codePatio:139:6+$patioNom:TER:ZZZ'".$sepa;
                fputs($fp2,$enc);
                $tlConte=0;
                $tlSegmentos =4;
                while ($data = fgetcsv($fp,1000,"$deli")) {
                    if( $l > 3 ){
                        unset($dataX);
                        foreach($data as $campo){
                            $campo=addslashes($campo);
                            $campo= str_replace("\n","",$campo);
                            $campo= str_replace("\r","",$campo);
                            $dataX[]=$campo;
                        }
                        $conte= $dataX[1];

                        // ---------------------
                        // FECHA HORA MINUTO
                        // ---------------------
                        $fecha= $dataX[7];
                        $fecha= str_replace("-","",$fecha);
                        $validFec=0;
                        if( preg_match("/^201.*/",$fecha) ) {
                            $a= substr($fecha,0,4);
                            $m= substr($fecha,4,2);
                            $d= substr($fecha,6,2);
                            $validFec=1;
                        }
                        elseif( preg_match("/(\d{2})(\d{2})(13)/",$fecha,$parts) ){
                            $d = $parts[1];
                            $m = $parts[2];
                            $a= "20".$parts[3];
                            $validFec=1;
                        }
                        elseif( preg_match("/(\d{2})(\w{3})(\d{2})/",$fecha,$parts) ){
                            $mes= strtoupper($parts[2]);
                            if($mes=="ENE")$m="01";
                            if($mes=="FEB")$m="02";
                            if($mes=="MAR")$m="03";
                            if($mes=="ABR")$m="04";
                            if($mes=="MAY")$m="05";
                            if($mes=="JUN")$m="06";
                            if($mes=="JUL")$m="07";
                            if($mes=="AGO")$m="08";
                            if($mes=="SEP")$m="09";
                            if($mes=="OCT")$m="10";
                            if($mes=="NOV")$m="11";
                            if($mes=="DIC")$m="12";
                            $a= "20".$parts[3];
                            $d= $parts[1];
                        }

                        // Hora:Minuto Formato 24 hrs.
                        $hora= $dataX[8];
                        unset($parts);
                        if( preg_match("/(\d{2}):(\d{2})/",$hora,$parts) ){
                            $hr= $parts[1];
                            if($hr=="24"){$hr="23";};
                            $min= $parts[2];
                        }
                        $validFec=1;


                        // -------------
                        // Validacion
                        // -------------
                        $thisYear = date("Y");
                        if( $a<>$thisYear ){
                            $msgErr[] = "<font color=red>[Error][$conte] Año incorrecto, favor de corregir el formato de la fecha en su archivo CSV.</font>";
                            $validFec=0;
                        }
                        if( empty($hr) || empty($min) ){
                            $msgErr[] = "<font color=red>[Error][$conte] Hora ($hr) o Minuto ($min) incorrecto, favor de corregir el formato a 24 hrs.</font>";
                            $validFec=0;
                        }
                        // -----------------

                        if( !empty($conte) && ( $validFec==1 )  ){
                            $tlConte++;
                            $tamano= $dataX[2];
                            $bkgNumber = $dataX[14];
                            $bkgNumber = strtoupper($bkgNumber);
                            $cliente = $dataX[13];
                            $cliente = strtoupper($cliente);
                            $transportista= $dataX[12];
                            $transportista = strtoupper($transportista);
                            $calidad = $dataX[3];
                            $eir= $dataX[6];
                            //$sello= $dataX[];
                            $maniobra= $dataX[9];
                            $maniobra = strtoupper($maniobra);
                            $regFecha= $a.$m.$d;
                            $regHora= $hr.$min;
                            $conte= trim($conte);
                            //$conte=preg_replace($pattern,$replacement,$conte);
                            $tamano= strtoupper($tamano);
                            $tamano = str_replace(" ","",$tamano);

                            // 20' DRY VAN
                            if( $tamano=="22.10" || $tamano=="22.1" || $tamano=="20DC" || $tamano=="20DV" ){$tipo="22G0";}
                            // 20' FLAT COLLAPSIBLE
                            if( $tamano=="22.63" || $tamano=="20FL" ){$tipo="22P3";}
                            // 20' REEFER
                            if( $tamano=="22.32" || $tamano=="20RF" ){$tipo="22R1";}
                            // 20CT TANK CONTAINER
                            if( $tamano=="22.70" || $tamano=="22.7" || $tamano=="20TK" ){$tipo="22T3";}
                            // 20OT OPEN TOP
                            if( $tamano=="22.51" || $tamano=="20OT" ){$tipo="22U1";}
                            // 40' DRY VAN
                            if( $tamano=="43.10" || $tamano=="43.1" || $tamano=="40DC" ){$tipo="42G0";}
                            // 40' HIGH CUBE
                            if( $tamano=="45.10" || $tamano=="45.1" || $tamano=="40HC" ){$tipo="45G0";}
                            // 40' OPEN TOP
                            if( $tamano=="43.51" || $tamano=="40OT" ){$tipo="42U1";}
                            // RF4/40' REEFER
                            if( $tamano=="43.32" || $tamano=="40RF" ){$tipo="42R1";}
                            // 40CT TANK CONTAINER
                            if( $tamano=="43.70" || $tamano=="40TK" ){$tipo="42T0";   }

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
                            $enc2.="DTM+7:$regFecha$regHora:203'".$sepa;
                            $tlSegmentos++;
                            $enc2.="RFF+BN:$bkgNumber'".$sepa;
                            $tlSegmentos++;
                            $enc2.="FTX+AAI+++$bkgNumber/$cliente/$transportista'".$sepa;
                            $tlSegmentos++;
                            $enc2.="TDT+1+$maniobra+3'".$sepa;  // 3 : Equivale a Camion y 1. Es Tren.
                            $tlSegmentos++;
                            // Cuando sean salidas es necesario agregar esta línea para cada movimiento,
                            // ya que con este se especifica el Destination Depot.
                            $enc2.="LOC+99+$codOD'".$sepa;   // 99 : Place of empty equipment return
                            $tlSegmentos++;
                            fputs($fp2,$enc2);
                        }
                    }
                    $l++;
                } // fin del while

                // Pie de pagina
                // Total de contenedores
                $pie="CNT+16:$tlConte'".$sepa;
                $tlSegmentos++;
                $pie.="UNT+$tlSegmentos+$fecD1$fecD2'".$sepa;
                $pie.="UNZ+1+$fecD1$fecD2'";

                fputs($fp2,$pie);
                fclose($fp2);
                fclose($fp);


                // -------------------
                // Mensajes de error:
                // -------------------
                if( count($msgErr)>0 ){
                    foreach( $msgErr as $msgY ){
                        echo "<font color=red>[<b>Error</b>] $msgY</font><br>";
                    }
                }
                else{
                    // echo "<font color=green>[<b>OK</b>] Archivo sin Errores</font>";
                    if( $opcPro=="saveAs" ){
                        // -----------------------------
                        // SALVAR COMO... O ABRIR EN AUTO.
                        // (No modificar)
                        // -----------------------------
                        if(file_exists("$fileEDI")){
                            header('Content-Description: File Transfer');
                            header('Content-Type: application/octet-stream');
                            header('Content-Disposition: attachment; filename='.basename($fileEDI));
                            header('Content-Transfer-Encoding: binary');
                            header('Expires: 0');
                            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                            header('Pragma: public');
                            header('Content-Length: ' . filesize($fileEDI));
                            ob_clean();
                            flush();
                            readfile("$fileEDI");
                            exit;
                        }
                    }
                    elseif( $opcPro=="email" ){
                        $fileName="POSITIONS".$ofiCod.$fecD1.$fecD2.".edi";
                        sendMailMSC($fileEDI,'POSITIONS',$fileName);
                    }
                    elseif( $opcPro=="ftp" ){
                        // -----------------------------
                        // FTP - POSICIONAMIENTOS
                        // -----------------------------
                        $ftp_server = "187.174.238.23";
                        switch( $codOD ){
                            case "LMR00":
                                $ftp_user_name = "terralmr";
                                $ftp_user_pass = "lmr03";
                                break;
                            case "LZC00":
                                $ftp_user_name = "terralzc";
                                $ftp_user_pass = "lzc03";
                                break;
                            case "ZLO00":
                                $ftp_user_name = "terrazlo";
                                $ftp_user_pass = "zlo03";
                                break;
                            case "VER00":
                                $ftp_user_name = "terraver";
                                $ftp_user_pass = "ver03";
                                break;
                        }
                        $source_file = $fileEDI;
                        $destination_file = str_replace("../edi_files/edi/","",$source_file);
                        // --
                        $conn_id = ftp_connect($ftp_server);
                        $login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
                        // verificar la conexión
                        if ((!$conn_id) || (!$login_result)) {
                            $msg[] = "<font color=red>¡La conexión FTP ha fallado ponerse en contacto con Lic.Nestor Perez Tel: 5091-7636 / 5532224753 </font>";
                            $msg[] = "<font color=red>Se intentó conectar al $ftp_server por el usuario $ftp_user_name</font>";
                            exit;
                        } else {
                            $msg[]= "<font color=blue>Conexión a $ftp_server realizada con éxito, por el usuario $ftp_user_name</font>";
                        }
                        // subir un archivo
                        $upload = ftp_put($conn_id, $destination_file, $source_file, FTP_BINARY);
                        // comprobar el estado de la subida
                        if (!$upload) {
                            $msg[] = "<font color=red>La subida FTP ha fallado! </font>";
                        } else {
                            $msg[] = "<font color=blue>El Envio a FTP $ftp_server ha sido exitoso!. Archivo : $destination_file </font>";
                            //sendMailMSC('POSICIONAMIENTOS');
                        }
                        // cerrar la conexión ftp
                        ftp_close($conn_id);
                    }
                }
            }
            return $msg;
        }
    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateInMsc($deli,$stConte,$impoExpo,$sesIdUsuario,$codOD,$opcPro){
        global $db,$db2,$HTTP_POST_FILES,$hoy,$t;

        // --------------------------------------------
        // Proceso de copiado.
        // --------------------------------------------
        $hoy = date("Y-m-d H:i");
        // Copia el archivo del usuario al directorio ../files
        if ( is_uploaded_file($HTTP_POST_FILES['userfile']['tmp_name']) ) {
            copy($HTTP_POST_FILES['userfile']['tmp_name'], "../edi_files/csv/entradas.csv");
        }
        else {
            $msg="<font color=\"red\">[Error] en el envio del archivo, vuela a intentarlo! " . $HTTP_POST_FILES['userfile']['name'];
            $msg.="</font><br>";
            echo $msg;
        }


        // -------------------------------------
        // GET-IN
        // -------------------------------------
        unset($fp);
        unset($data);
        unset($dataX);
        $fp = fopen("../edi_files/csv/entradas.csv","r");
        $validFile="True";
        $l=1;
        while ( $data = fgetcsv($fp,1000,"$deli") ) {
            if($l==5){
                foreach($data as $campo){
                    $campo=addslashes($campo);
                    $campo= str_replace("\n","",$campo);
                    $campo= str_replace("\r","",$campo);
                    $dataX[]=$campo;
                }
                // Validacion campos
                // Nuevo Formato
                if($dataX[0]!="NAVIERA"){$validFile="False"; $msg[] =  "[Error][Encabezado] NAVIERA, esta incorrecto."; }
                if($dataX[1]!="CONTENEDOR"){$validFile="False"; $msg[] =  "[Error][Encabezado] CONTENEDOR, esta incorrecto."; }
                if($dataX[2]!="TAM"){$validFile="False"; $msg[] =  "[Error][Encabezado] TAM, esta incorrecto."; }
                if($dataX[3]!="CLASIFICACION"){$validFile="False"; $msg[] =  "[Error][Encabezado] CLASIFICACION, esta incorrecto."; }
                if($dataX[4]!="EIR"){$validFile="False"; $msg[] =  "[Error][Encabezado] EIR, esta incorrecto."; }
                if($dataX[5]!="FECHA ENTRADA"){$validFile="False"; $msg[] =  "[Error][Encabezado] FECHA ENTRADA, esta incorrecto."; }
                if($dataX[6]!="HR"){$validFile="False"; $msg[] =  "[Error][Encabezado] HR, esta incorrecto."; }
                if($dataX[7]!="MANIOBRA"){$validFile="False"; $msg[] =  "[Error][Encabezado] MANIOBRA, esta incorrecto. "; }
                if($dataX[8]!="COSTO"){$validFile="False"; $msg[] =  "[Error][Encabezado] COSTO, esta incorrecto."; }
                if($dataX[9]!="FACTURA"){$validFile="False"; $msg[] =  "[Error][Encabezado] FACTURA, esta incorrecto."; }
                if($dataX[10]!="TRANSPORTISTA"){$validFile="False"; $msg[] =  "[Error][Encabezado] TRANSPORTISTA, esta incorrecto."; }
                if($dataX[11]!="CLIENTE"){$validFile="False"; $msg[] =  "[Error][Encabezado] CLIENTE, esta incorrecto. "; }

            }
            $l++;
        }
        fclose($fp);

        // IMPRIMIR ERRORES
        if( is_array($msg) ){
            if( count($msg)>0 ){
                foreach( $msg as $msgX ){
                    echo "<font color=red>$msgX</font><br>";
                }
            }
        }

        if( $validFile=="True" ){
            if( $sesOficina=="VERACRUZ" ){
                $ofiCod="VR";
            }
            elseif($sesOficina=="MEXICO"){
                $ofiCod="MX";
            }
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            //$fileName = "MSCE".$fecD1.$fecD2.".edi";
            $fileName = substr($csvFile,0,-4).".edi";
            $flgRec=1;
            $fp= fopen("../edi_files/csv/entradas.csv","r");
            $l=1;
            $f=date(Ymd);
            $fileEDI = "../edi_files/edi/GATE-IN".$ofiCod.$fecD1.$fecD2.".edi";
            $fp2 = fopen("$fileEDI","w");
            $sepa="\n";

            $codPatio = getValueTable("cod_patio","USUARIO","id_usuario",$sesIdUsuario);
            $codPatioName = getValueTable("cod_patio_name","USUARIO","id_usuario",$sesIdUsuario);
            $senderID = getValueTable("sender_id","USUARIO","id_usuario",$sesIdUsuario);
            $receiverID="MSC";
            // ENCABEZADO
            $enc= "UNB+UNOA:1+$senderID+$receiverID+$fecD1:$fecD2+$fecD1$fecD2'".$sepa;
            $enc.="UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'".$sepa;
            $enc.="BGM+34+$fecD1$fecD2+9'".$sepa;
            $enc.="LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'".$sepa;
            //"TDT+20++1++MSC:172:166'".$sepa.
            //"NAD+CA+MXVRCDECECI:172:166'".$sepa;
            //$enc11=utf8_encode($enc);
            fputs($fp2,$enc);
            //$e=0;  // elemento del arreglo.
            $tlConte=0;
            $tlSegmentos =4;
            while ($data = fgetcsv($fp,1000,"$deli")) {
                if( $l > 5 ){
                    unset($dataX);
                    foreach($data as $campo){
                        $campo=addslashes($campo);
                        $campo= str_replace("\n","",$campo);
                        $campo= str_replace("\r","",$campo);
                        $dataX[]=$campo;
                    }
                    $conte= $dataX[1];

                    // ---------------------
                    // FECHA HORA MINUTO
                    // ---------------------
                    //$fecha= $dataX[0];
                    $fecha= $dataX[5];

                    // * para lo que tenga -> /
                    $validFec=0;
                    if( preg_match("/(\d{2})\/(\d{2})\/(\d{4})/i",$fecha,$parts) ){
                        $d= $parts[1];
                        $m= $parts[2];
                        $a= $parts[3];
                        $validFec=1;
                    }
                    elseif( preg_match("/(\d{4})\/(\d{2})\/(\d{2)/i",$fecha,$parts) ){
                        $d= $parts[3];
                        $m= $parts[2];
                        $a= $parts[1];
                        $validFec=1;
                    }
                    elseif( preg_match("/(\d{2})-(\d{2})-(\d{4})/i",$fecha,$parts) ){
                        $d= $parts[1];
                        $m= $parts[2];
                        $a= $parts[3];
                        $validFec=1;
                    }
                    // Hora:Minuto Formato 24 hrs.
                    $hora= $dataX[6];
                    unset($parts);
                    if( preg_match("/(\d{2}):(\d{2})/",$hora,$parts) ){
                        $hr= $parts[1];
                        if($hr=="24"){$hr="23";};
                        $min= $parts[2];
                    }

                    // -------------
                    // Validacion
                    // -------------
                    if( (!empty($conte)) && ($validFec==0) ){
                        $msgErr[] = "<font color=red>[Error][$conte] La fecha es incorrecta.</font>";
                    }

                    /*
                    $a= trim($a);
                    $thisYear = date("Y");
                    if( $a<>$thisYear ){
                    $msgErr[] = "<font color=red>[Error][$conte] Año incorrecto, favor de corregir el formato de la fecha en su archivo CSV.</font>";
                    $validFec=0;
                    }
                    */
                    if( (empty($hr) || empty($min)) && (!empty($conte)) ){
                        $msgErr[] = "<font color=red>[Error][$conte] Hora o Minuto incorrecto, favor de corregir el formato a 24 hrs.</font>";
                        $validFec=0;
                    }
                    // -----------------


                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;
                        $tamano= $dataX[2];
                        // Clasificación
                        $calidad= $dataX[3];
                        //$condConte= $dataX[4];
                        $transportista= $dataX[10];
                        $transportista = strtoupper($transportista);
                        $eir= $dataX[4];
                        $maniobra= $dataX[7];
                        $maniobra = strtoupper($maniobra);
                        $regFecha= $a.$m.$d;
                        $regHora=$hr.$min;
                        //echo "hora: $regHora<br>";
                        //echo "ano: $a mes; $mes= $m dia; $d <br>";
                        $conte = trim($conte);
                        $conte = str_replace(" ","",$conte);
                        //$conte=preg_replace($pattern,$replacement,$conte);
                        $tamano = strtoupper($tamano);
                        $tamano = str_replace(" ","",$tamano);

                        // 20' DRY VAN
                        if( $tamano=="22.10" || $tamano=="22.1" || $tamano=="20DC" || $tamano=="20DV" ){$tipo="22G0";}
                        // 20' FLAT COLLAPSIBLE
                        if( $tamano=="22.63" || $tamano=="20FL" ){$tipo="22P3";}
                        // 20' REEFER
                        if( $tamano=="22.32" || $tamano=="20RF" ){$tipo="22R1";}
                        // 20CT TANK CONTAINER
                        if( $tamano=="22.70" || $tamano=="22.7" || $tamano=="20TK" ){$tipo="22T3";}
                        // 20OT OPEN TOP
                        if( $tamano=="22.51" || $tamano=="20OT" ){$tipo="22U1";}
                        // 40' DRY VAN
                        if( $tamano=="43.10" || $tamano=="43.1" || $tamano=="40DC" ){$tipo="42G0";}
                        // 40' HIGH CUBE
                        if( $tamano=="45.10" || $tamano=="45.1" || $tamano=="40HC" ){$tipo="45G0";}
                        // 40' OPEN TOP
                        if( $tamano=="43.51" || $tamano=="40OT" ){$tipo="42U1";}
                        // RF4/40' REEFER
                        if( $tamano=="43.32" || $tamano=="40RF" ){$tipo="42R1";}
                        // 40CT TANK CONTAINER
                        if( $tamano=="43.70" || $tamano=="40TK" ){$tipo="42T0";}

                        // Cod.Deposito si lo espe





                        //---------------------------------------------------------
                        //COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                        //----------------------------------------------------------
                        // FTX : Remarks +AAI = General Information
                        ($impoExpo=="E")?$stIE=2:$stIE=3;
                        ($stConte=="E")?$stCo=4:$stCo=5;
                        $conte = strtoupper($conte);
                        $enc2= "EQD+CN+$conte+$tipo++$stIE+$stCo'".$sepa;
                        $tlSegmentos++;
                        $enc2.="DTM+7:$regFecha$regHora:203'".$sepa;
                        $tlSegmentos++;
                        $enc2.="FTX+AAI+++$transportista/$eir'".$sepa;
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'".$sepa;
                        $tlSegmentos++;
                        //$enc2.="LOC+99+$codePatio+::ZZZ'".$sepa;   // 99 : Place of empty equipment return
                        //$tlSegmentos++;
                        fputs($fp2,$enc2);
                    }

                }
                $l++;
            } // fin del while


            // Pie de pagina
            // Total de contenedores
            $pie="CNT+16:$tlConte'".$sepa;
            $tlSegmentos++;
            $pie.="UNT+$tlSegmentos+$fecD1$fecD2'".$sepa;
            $pie.="UNZ+1+$fecD1$fecD2'";

            //$enc33=utf8_encode($enc3);
            fputs($fp2,$pie);
            fclose($fp2);
            fclose($fp);

            // -------------------
            // Mensajes de error:
            // -------------------
            if( count($msgErr)>0 ){
                foreach( $msgErr as $msgY ){
                    echo "<font color=red>[<b>Error</b>] $msgY</font><br>";
                }
            }
            else{
                if( $opcPro=="saveAs" ){
                    // -----------------------------
                    // SALVAR COMO... O ABRIR EN AUTO.
                    // (No modificar)
                    // -----------------------------
                    if( file_exists("$fileEDI") ){
                        header('Content-Description: File Transfer');
                        header('Content-Type: application/octet-stream');
                        header('Content-Disposition: attachment; filename='.basename($fileEDI));
                        header('Content-Transfer-Encoding: binary');
                        header('Expires: 0');
                        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                        header('Pragma: public');
                        header('Content-Length: ' . filesize($fileEDI));
                        ob_clean();
                        flush();
                        readfile("$fileEDI");
                        exit;
                    }
                }
                elseif( $opcPro=="email" ){
                    $fileName="GATE-IN".$ofiCod.$fecD1.$fecD2.".edi";
                    sendMailMSC($fileEDI,'GATE-IN',$fileName);
                }
                elseif( $opcPro=="ftp" ){
                    // -----------------------------
                    // FTP - GETIN
                    // -----------------------------
                    $ftp_server = "187.174.238.23";
                    switch( $codOD ){
                        case "LMR00":
                            $ftp_user_name = "terralmr";
                            $ftp_user_pass = "lmr03";
                            break;
                        case "LZC00":
                            $ftp_user_name = "terralzc";
                            $ftp_user_pass = "lzc03";
                            break;
                        case "ZLO00":
                            $ftp_user_name = "terrazlo";
                            $ftp_user_pass = "zlo03";
                            break;
                        case "VER00":
                            $ftp_user_name = "terraver";
                            $ftp_user_pass = "ver03";
                            break;
                    }
                    $source_file = $fileEDI;
                    $destination_file = str_replace("../edi_files/edi/","",$source_file);
                    // --
                    // --
                    $conn_id = ftp_connect($ftp_server);
                    $login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
                    // verificar la conexión
                    if ((!$conn_id) || (!$login_result)) {
                        $msg[] = "<font color=red>¡La conexión FTP ha fallado ponerse en contacto con Lic.Nestor Perez Tel: 5091-7636 / 5532224753 </font>";
                        $msg[] = "<font color=red>Se intentó conectar al $ftp_server por el usuario $ftp_user_name</font>";
                        exit;
                    } else {
                        $msg[]= "<font color=blue>Conexión a $ftp_server realizada con éxito, por el usuario $ftp_user_name</font>";
                    }
                    // subir un archivo
                    $upload = ftp_put($conn_id, $destination_file, $source_file, FTP_BINARY);
                    // comprobar el estado de la subida
                    if (!$upload) {
                        $msg[] = "<font color=red>La subida FTP ha fallado! </font>";
                    } else {
                        $msg[] = "<font color=blue>El Envio a FTP $ftp_server ha sido exitoso!. Archivo : $destination_file </font>";
                        //sendMailMSC('GETA-IN');
                    }
                    // cerrar la conexión ftp
                    ftp_close($conn_id);
                }
            }
        }
        return $msg;
    }




    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateInHL($deli,$stConte,$impoExpo,$sesIdUsuario,$codOD,$opcPro){
        global $db,$db2,$HTTP_POST_FILES,$hoy,$t;

        // Conf.Globales
        $codPatio = getValueTable("cod_patio","USUARIO","id_usuario",$sesIdUsuario);
        $codPatioName = getValueTable("cod_patio_name","USUARIO","id_usuario",$sesIdUsuario);
        $senderID = getValueTable("sender_id","USUARIO","id_usuario",$sesIdUsuario);
        $receiverID="HLCU";
        $nad = "NAD+CF+HSD:172'";  // 173 = Carrier code.
        //$locCode = "MXMTY"; // Lugar del patio
        $locCode = getValueTable("loc_code","USUARIO","id_usuario",$sesIdUsuario);  // Lugar del patio
        $razonSocial = getValueTable("razon_social","USUARIO","id_usuario",$sesIdUsuario);  // Lugar del patio
        $razonSocial = strtoupper($razonSocial);

        // --------------------------------------------
        // Proceso de copiado.
        // --------------------------------------------
        $hoy = date("Y-m-d H:i");
        // Copia el archivo del usuario al directorio ../files
        if ( is_uploaded_file($HTTP_POST_FILES['userfile']['tmp_name']) ) {
            copy($HTTP_POST_FILES['userfile']['tmp_name'], "../edi_files/csv/entradas.csv");
        }
        else {
            $msg="<font color=\"red\">[Error] en el envio del archivo, vuela a intentarlo! " . $HTTP_POST_FILES['userfile']['name'];
            $msg.="</font><br>";
            echo $msg;
        }


        // -------------------------------------
        // GET-IN
        // -------------------------------------
        unset($fp);
        unset($data);
        unset($dataX);
        $fp = fopen("../edi_files/csv/entradas.csv","r");
        $validFile="True";
        $l=1;
        while ( $data = fgetcsv($fp,1000,"$deli") ) {
            if($l==5){
                foreach($data as $campo){
                    $campo=addslashes($campo);
                    $campo= str_replace("\n","",$campo);
                    $campo= str_replace("\r","",$campo);
                    $dataX[]=$campo;
                }
                // Validacion campos
                // Nuevo Formato
                if($dataX[0]!="NAVIERA"){$validFile="False"; $msg[] =  "[Error][Encabezado] NAVIERA, esta incorrecto."; }
                if($dataX[1]!="CONTENEDOR"){$validFile="False"; $msg[] =  "[Error][Encabezado] CONTENEDOR, esta incorrecto."; }
                if($dataX[2]!="TAM"){$validFile="False"; $msg[] =  "[Error][Encabezado] TAM, esta incorrecto."; }
                if($dataX[3]!="CLASIFICACION"){$validFile="False"; $msg[] =  "[Error][Encabezado] CLASIFICACION, esta incorrecto."; }
                if($dataX[4]!="EIR"){$validFile="False"; $msg[] =  "[Error][Encabezado] EIR, esta incorrecto."; }
                if($dataX[5]!="FECHA ENTRADA"){$validFile="False"; $msg[] =  "[Error][Encabezado] FECHA ENTRADA, esta incorrecto."; }
                if($dataX[6]!="HR"){$validFile="False"; $msg[] =  "[Error][Encabezado] HR, esta incorrecto."; }
                if($dataX[7]!="MANIOBRA"){$validFile="False"; $msg[] =  "[Error][Encabezado] MANIOBRA, esta incorrecto. "; }
                if($dataX[8]!="COSTO"){$validFile="False"; $msg[] =  "[Error][Encabezado] COSTO, esta incorrecto."; }
                if($dataX[9]!="FACTURA"){$validFile="False"; $msg[] =  "[Error][Encabezado] FACTURA, esta incorrecto."; }
                if($dataX[10]!="TRANSPORTISTA"){$validFile="False"; $msg[] =  "[Error][Encabezado] TRANSPORTISTA, esta incorrecto."; }
                if($dataX[11]!="CLIENTE"){$validFile="False"; $msg[] =  "[Error][Encabezado] CLIENTE, esta incorrecto. "; }

            }
            $l++;
        }
        fclose($fp);

        // IMPRIMIR ERRORES
        if( is_array($msg) ){
            if( count($msg)>0 ){
                foreach( $msg as $msgX ){
                    echo "<font color=red>$msgX</font><br>";
                }
            }
        }

        if( $validFile=="True" ){
            if( $sesOficina=="VERACRUZ" ){
                $ofiCod="VR";
            }
            elseif($sesOficina=="MEXICO"){
                $ofiCod="MX";
            }
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            //$fileName = "MSCE".$fecD1.$fecD2.".edi";
            $fileName = substr($csvFile,0,-4).".edi";
            $flgRec=1;
            $fp= fopen("../edi_files/csv/entradas.csv","r");
            $l=1;
            $f=date(Ymd);
            $fileEDI = "../edi_files/edi/GATE-IN".$ofiCod.$fecD1.$fecD2.".edi";
            $fp2 = fopen("$fileEDI","w");
            $sepa="\n";

            // ENCABEZADO
            $enc= "UNB+UNOA:1+$senderID+$receiverID+$fecD1:$fecD2+$fecD1$fecD2'".$sepa;
            $enc.="UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'".$sepa;
            $enc.="BGM+34+$fecD1$fecD2+9'".$sepa;
            $enc.=$nad.$sepa;
            $tlConte=0;
            $tlSegmentos =4;
            $flgLoc165 = true;
            fputs($fp2,$enc);
            while ($data = fgetcsv($fp,1000,"$deli")) {
                if( $l > 5 ){
                    unset($dataX);
                    foreach($data as $campo){
                        $campo=addslashes($campo);
                        $campo= str_replace("\n","",$campo);
                        $campo= str_replace("\r","",$campo);
                        $dataX[]=$campo;
                    }
                    $conte= $dataX[1];

                    // ---------------------
                    // FECHA HORA MINUTO
                    // ---------------------
                    //$fecha= $dataX[0];
                    $fecha= $dataX[5];

                    // * para lo que tenga -> /
                    $validFec=0;
                    if( preg_match("/(\d{2})\/(\d{2})\/(\d{4})/i",$fecha,$parts) ){
                        $d= $parts[1];
                        $m= $parts[2];
                        $a= $parts[3];
                        $validFec=1;
                    }
                    elseif( preg_match("/(\d{4})\/(\d{2})\/(\d{2)/i",$fecha,$parts) ){
                        $d= $parts[3];
                        $m= $parts[2];
                        $a= $parts[1];
                        $validFec=1;
                    }
                    elseif( preg_match("/(\d{2})-(\d{2})-(\d{4})/i",$fecha,$parts) ){
                        $d= $parts[1];
                        $m= $parts[2];
                        $a= $parts[3];
                        $validFec=1;
                    }
                    // Hora:Minuto Formato 24 hrs.
                    $hora= $dataX[6];
                    unset($parts);
                    if( preg_match("/(\d{2}):(\d{2})/",$hora,$parts) ){
                        $hr= $parts[1];
                        if($hr=="24"){$hr="23";};
                        $min= $parts[2];
                    }

                    // -------------
                    // Validacion
                    // -------------
                    if( (!empty($conte)) && ($validFec==0) ){
                        $msgErr[] = "<font color=red>[Error][$conte] La fecha es incorrecta.</font>";
                    }

                    /*
                    $a= trim($a);
                    $thisYear = date("Y");
                    if( $a<>$thisYear ){
                    $msgErr[] = "<font color=red>[Error][$conte] Año incorrecto, favor de corregir el formato de la fecha en su archivo CSV.</font>";
                    $validFec=0;
                    }
                    */
                    if( (empty($hr) || empty($min)) && (!empty($conte)) ){
                        $msgErr[] = "<font color=red>[Error][$conte] Hora o Minuto incorrecto, favor de corregir el formato a 24 hrs.</font>";
                        $validFec=0;
                    }
                    // -----------------


                    if( !empty($conte) && ($validFec==1)  ){
                        $tlConte++;
                        $tamano= $dataX[2];
                        // Clasificación
                        $calidad= $dataX[3];
                        //$condConte= $dataX[4];
                        $transportista= $dataX[10];
                        $transportista = strtoupper($transportista);
                        $eir= $dataX[4];
                        $maniobra= $dataX[7];
                        $maniobra = strtoupper($maniobra);
                        $regFecha= $a.$m.$d;
                        $regHora=$hr.$min;
                        //echo "hora: $regHora<br>";
                        //echo "ano: $a mes; $mes= $m dia; $d <br>";
                        $conte = trim($conte);
                        $conte = str_replace(" ","",$conte);
                        //$conte=preg_replace($pattern,$replacement,$conte);
                        $tamano = strtoupper($tamano);
                        $tamano = str_replace(" ","",$tamano);

                        // 20' DRY VAN
                        if( $tamano=="22.10" || $tamano=="22.1" || $tamano=="20DC" || $tamano=="20DV" ){$tipo="22G0";}
                        // 20' FLAT COLLAPSIBLE
                        if( $tamano=="22.63" || $tamano=="20FL" ){$tipo="22P3";}
                        // 20' REEFER
                        if( $tamano=="22.32" || $tamano=="20RF" ){$tipo="22R1";}
                        // 20CT TANK CONTAINER
                        if( $tamano=="22.70" || $tamano=="22.7" || $tamano=="20TK" ){$tipo="22T3";}
                        // 20OT OPEN TOP
                        if( $tamano=="22.51" || $tamano=="20OT" ){$tipo="22U1";}
                        // 40' DRY VAN
                        if( $tamano=="43.10" || $tamano=="43.1" || $tamano=="40DC" ){$tipo="42G0";}
                        // 40' HIGH CUBE
                        if( $tamano=="45.10" || $tamano=="45.1" || $tamano=="40HC" ){$tipo="45G0";}
                        // 40' OPEN TOP
                        if( $tamano=="43.51" || $tamano=="40OT" ){$tipo="42U1";}
                        // RF4/40' REEFER
                        if( $tamano=="43.32" || $tamano=="40RF" ){$tipo="42R1";}
                        // 40CT TANK CONTAINER
                        if( $tamano=="43.70" || $tamano=="40TK" ){$tipo="42T0";}

                        // Cod.Deposito si lo espe





                        //---------------------------------------------------------
                        //COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                        //----------------------------------------------------------
                        // FTX : Remarks +AAI = General Information
                        ($impoExpo=="E")?$stIE=2:$stIE=3;
                        ($stConte=="E")?$stCo=4:$stCo=5;
                        $conte = strtoupper($conte);
                        $enc2= "EQD+CN+$conte+$tipo++$stIE+$stCo'".$sepa;
                        $tlSegmentos++;
                        $enc2.="DTM+7:$regFecha$regHora:203'".$sepa;
                        if( $flgLoc165 == true ){
                            $tlSegmentos++;
                            // El LOC+165 solo se debe imprimir la primera vez. Segun HapagL.
                            $enc2.="LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'".$sepa;
                            $flgLoc165 = false;
                        }
                        $tlSegmentos++;
                        $enc2.="FTX+AAI+++$transportista/$eir'".$sepa;
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'".$sepa;
                        $tlSegmentos++;
                        fputs($fp2,$enc2);
                    }

                }
                $l++;
            } // fin del while


            // Pie de pagina
            // Total de contenedores
            $pie="CNT+16:$tlConte'".$sepa;
            $tlSegmentos++;
            $pie.="UNT+$tlSegmentos+$fecD1$fecD2'".$sepa;
            $pie.="UNZ+1+$fecD1$fecD2'";

            //$enc33=utf8_encode($enc3);
            fputs($fp2,$pie);
            fclose($fp2);
            fclose($fp);

            // -------------------
            // Mensajes de error:
            // -------------------
            if( count($msgErr)>0 ){
                foreach( $msgErr as $msgY ){
                    echo "<font color=red>[<b>Error</b>] $msgY</font><br>";
                }
            }
            else{
                if( $opcPro=="saveAs" ){
                    // -----------------------------
                    // SALVAR COMO... O ABRIR EN AUTO.
                    // (No modificar)
                    // -----------------------------
                    if( file_exists("$fileEDI") ){
                        header('Content-Description: File Transfer');
                        header('Content-Type: application/octet-stream');
                        header('Content-Disposition: attachment; filename='.basename($fileEDI));
                        header('Content-Transfer-Encoding: binary');
                        header('Expires: 0');
                        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                        header('Pragma: public');
                        header('Content-Length: ' . filesize($fileEDI));
                        ob_clean();
                        flush();
                        readfile("$fileEDI");
                        exit;
                    }
                }
                elseif( $opcPro=="email" ){
                    $fileName="GATE-IN".$ofiCod.$fecD1.$fecD2.".edi";
                    // sendMailMSC($fileEDI,'GATE-IN',$fileName);
                    echo "Opcion no disponible...";
                }
                elseif( $opcPro=="ftp" ){
                    echo "Opcion no disponible...";
                }
            }
        }
        return $msg;
    }
    // fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
    function ediGateOutHL($deli,$stConte,$impoExpo,$sesIdUsuario,$codOD,$opcPro){
        global $db,$db2,$HTTP_POST_FILES,$hoy,$t;

        // Conf.Globales
        $codPatio = getValueTable("cod_patio","USUARIO","id_usuario",$sesIdUsuario);
        $codPatioName = getValueTable("cod_patio_name","USUARIO","id_usuario",$sesIdUsuario);
        $senderID = getValueTable("sender_id","USUARIO","id_usuario",$sesIdUsuario);
        $receiverID="HLCU";
        $nad = "NAD+CF+HSD:172'";  // 173 = Carrier code.
        //$locCode = "MXMTY"; // Lugar del patio
        $locCode = getValueTable("loc_code","USUARIO","id_usuario",$sesIdUsuario);  // Lugar del patio
        $razonSocial = getValueTable("razon_social","USUARIO","id_usuario",$sesIdUsuario);  // Lugar del patio
        $razonSocial = strtoupper($razonSocial);

        // --------------------------------------------
        // Proceso de copiado.
        // --------------------------------------------
        $hoy = date("Y-m-d H:i");
        // Copia el archivo del usuario al directorio ../files
        if ( is_uploaded_file($HTTP_POST_FILES['userfile']['tmp_name']) ) {
            copy($HTTP_POST_FILES['userfile']['tmp_name'], "../edi_files/csv/salidas.csv");
        }
        else {
            $msg="<h1><font color=\"red\">Error en el envio del archivo, vuela a intentarlo! " . $HTTP_POST_FILES['userfile']['name'];
            $msg.="</font></h1>";
            echo $msg;
        }

        // ----------------------------------------
        // GATE OUT
        // ----------------------------------------
        // Validar encabezados.
        unset($fp);
        unset($data);
        unset($dataX);
        $fp = fopen("../edi_files/csv/salidas.csv","r");
        $validFile="True";
        // Linea del encabezado
        $l=1;
        while ( $data = fgetcsv($fp,1000,"$deli") ) {
            if($l==5){
                foreach($data as $campo){
                    $campo=addslashes($campo);
                    $campo= str_replace("\n","",$campo);
                    $campo= str_replace("\r","",$campo);
                    $dataX[]=$campo;
                }

                // Nuevo Formato de Salidas
                if( $dataX[0]!="NAVIERA"){$validFile="False"; $msg[] =  "[Error][Encabezado] NAVIERA, esta incorrecto."; }
                if( $dataX[1]!="CONTENEDOR"){$validFile="False"; $msg[] =  "[Error][Encabezado] CONTENEDOR, esta incorrecto. "; }
                if( $dataX[2]!="TAM"){$validFile="False"; $msg[] =  "[Error][Encabezado] TAM, esta incorrecto. "; }
                if( $dataX[3]!="CALIDAD"){$validFile="False"; $msg[] =  "[Error][Encabezado] CALIDAD, esta incorrecto. "; }
                if( $dataX[4]!="FACTURA ACON."){$validFile="False"; $msg[] =  "[Error][Encabezado] FACTURA ACON., esta incorrecto."; }
                if( $dataX[5]!="COSTO DE ACON."){$validFile="False"; $msg[] =  "[Error][Encabezado] COSTO DE ACON., esta incorrecto. "; }
                if( $dataX[6]!="EIR"){$validFile="False"; $msg[] =  "[Error][Encabezado] EIR, esta incorrecto.  "; }
                if( $dataX[7]!="FECHA "){$validFile="False"; $msg[] =  "[Error][Encabezado] FECHA, esta incorrecto."; }
                if( $dataX[8]!="HR"){$validFile="False"; $msg[] =  "[Error][Encabezado] HR "; }
                if( $dataX[9]!="MANIOBRA"){$validFile="False"; $msg[] =  "[Error][Encabezado] MANIOBRA, esta incorrecto. "; }
                if( $dataX[10]!="COSTO"){$validFile="False"; $msg[] =  "[Error][Encabezado] COSTO, esta incorrecto. "; }
                if( $dataX[11]!="FACTURA"){$validFile="False"; $msg[] =  "[Error][Encabezado] FACTURA, esta incorrecto. "; }
                if( $dataX[12]!="TRANSPORTISTA"){$validFile="False"; $msg[] =  "[Error][Encabezado] TRANSPORTISTA, esta incorrecto. "; }
                if( $dataX[13]!="CLIENTE"){$validFile="False"; $msg[] =  "[Error][Encabezado] CLIENTE, esta incorrecto. "; }
                if( $dataX[14]!="BOOKING"){$validFile="False"; $msg[] =  "[Error][Encabezado] BOOKING, esta incorrecto. "; }

            }
            $l++;
        }
        fclose($fp);

        // IMPRIMIR ERRORES
        if( is_array($msg) ){
            if( count($msg)>0 ){
                foreach( $msg as $msgX ){
                    echo "<font color=red>$msgX</font><br>";
                }
            }
        }

        if( $validFile=="True" ){


            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $fileName = substr($csvFile,0,-4).".edi";
            $flgRec=1;
            $fp= fopen("../edi_files/csv/salidas.csv","r");
            $l=1;
            $f=date(Ymd);
            //$fileEDI = "../edi_files/edi/$fileName";
            $fileEDI = "../edi_files/edi/GATE-OUT".$fecD1.$fecD2.".edi";
            $fp2 = fopen("$fileEDI","w");
            $sepa="\n";


            // ENCABEZADO
            //$enc= "UNB+UNOA:1+TERRAPORTS++$fecD1:$fecD2+$fecD1$fecD2'".$sepa;
            $enc= "UNB+UNOA:1+$senderID+$receiverID+$fecD1:$fecD2+$fecD1$fecD2'".$sepa;
            $enc.="UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'".$sepa;
            $enc.="BGM+36+$fecD1$fecD2+9'".$sepa;
            $enc.=$nad.$sepa;
            $tlConte=0;
            $tlSegmentos =4;
            $flgLoc165 = true;  // Bandera poder imprimir el "LOC+165" una vez. 1 = Activado.
            fputs($fp2,$enc);
            while ($data = fgetcsv($fp,1000,"$deli")) {
                if( $l > 5 ){
                    unset($dataX);
                    foreach($data as $campo){
                        $campo=addslashes($campo);
                        $campo= str_replace("\n","",$campo);
                        $campo= str_replace("\r","",$campo);
                        $dataX[]=$campo;
                    }
                    $conte= $dataX[1];

                    // ---------------------
                    // FECHA HORA MINUTO
                    // ---------------------
                    $fecha= $dataX[7];

                    $validFec=0;
                    if( preg_match("/(\d{2})\/(\d{2})\/(\d{4})/i",$fecha,$parts) ){
                        $d= $parts[1];
                        $m= $parts[2];
                        $a= $parts[3];
                        $validFec=1;
                    }
                    elseif( preg_match("/(\d{4})\/(\d{2})\/(\d{2)/i",$fecha,$parts) ){
                        $d= $parts[3];
                        $m= $parts[2];
                        $a= $parts[1];
                        $validFec=1;
                    }
                    elseif( preg_match("/(\d{2})-(\d{2})-(\d{4})/i",$fecha,$parts) ){
                        $d= $parts[1];
                        $m= $parts[2];
                        $a= $parts[3];
                        $validFec=1;
                    }
                    // Hora:Minuto Formato 24 hrs.
                    $hora= $dataX[8];
                    unset($parts);
                    if( preg_match("/(\d{2}):(\d{2})/",$hora,$parts) ){
                        $hr= $parts[1];
                        if($hr=="24"){$hr="23";};
                        $min= $parts[2];
                    }

                    // -------------
                    // Validacion
                    // -------------
                    if( (!empty($conte)) && ($validFec==0) ){
                        $msgErr[] = "<font color=red>[Error][$conte] La fecha es incorrecta.</font>";
                    }

                    /*
                    $a= trim($a);
                    $thisYear = date("Y");
                    if( $a<>$thisYear ){
                    $msgErr[] = "<font color=red>[Error][$conte] Año incorrecto, favor de corregir el formato de la fecha en su archivo CSV.</font>";
                    $validFec=0;
                    }
                    */
                    if( !empty($conte) && ( empty($hr) || empty($min) )  ){
                        $msgErr[] = "<font color=red>[Error][$conte] Hora ($hr) o Minuto ($min) incorrecto, favor de corregir el formato a 24 hrs.</font>";
                        $validFec=0;
                    }
                    // -----------------

                    if( !empty($conte) && ( $validFec==1 )  ){
                        $tlConte++;
                        $tamano= $dataX[2];
                        $bkgNumber = $dataX[14];
                        $bkgNumber = strtoupper($bkgNumber);
                        $cliente = $dataX[13];
                        $cliente = strtoupper($cliente);
                        $transportista= $dataX[12];
                        $transportista = strtoupper($transportista);
                        $calidad = $dataX[3];
                        $eir= $dataX[6];
                        //$sello= $dataX[];
                        $maniobra= $dataX[9];
                        $maniobra = strtoupper($maniobra);
                        $regFecha= $a.$m.$d;
                        $regHora= $hr.$min;
                        $conte= trim($conte);
                        //$conte=preg_replace($pattern,$replacement,$conte);
                        $tamano= strtoupper($tamano);
                        $tamano = str_replace(" ","",$tamano);

                        // 20' DRY VAN
                        if( $tamano=="22.10" || $tamano=="22.1" || $tamano=="20DC" || $tamano=="20DV" ){$tipo="22G0";}
                        // 20' FLAT COLLAPSIBLE
                        if( $tamano=="22.63" || $tamano=="20FL" ){$tipo="22P3";}
                        // 20' REEFER
                        if( $tamano=="22.32" || $tamano=="20RF" ){$tipo="22R1";}
                        // 20CT TANK CONTAINER
                        if( $tamano=="22.70" || $tamano=="22.7" || $tamano=="20TK" ){$tipo="22T3";}
                        // 20OT OPEN TOP
                        if( $tamano=="22.51" || $tamano=="20OT" ){$tipo="22U1";}
                        // 40' DRY VAN
                        if( $tamano=="43.10" || $tamano=="43.1" || $tamano=="40DC" ){$tipo="42G0";}
                        // 40' HIGH CUBE
                        if( $tamano=="45.10" || $tamano=="45.1" || $tamano=="40HC" ){$tipo="45G0";}
                        // 40' OPEN TOP
                        if( $tamano=="43.51" || $tamano=="40OT" ){$tipo="42U1";}
                        // RF4/40' REEFER
                        if( $tamano=="43.32" || $tamano=="40RF" ){$tipo="42R1";}
                        // 40CT TANK CONTAINER
                        if( $tamano=="43.70" || $tamano=="40TK" ){$tipo="42T0";   }

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
                        $enc2.="RFF+BN:$bkgNumber'".$sepa;
                        $tlSegmentos++;
                        $enc2.="DTM+7:".$regFecha.$regHora.":203'".$sepa;
                        if( $flgLoc165 == true ){
                            $tlSegmentos++;
                            // El LOC+165 solo se debe imprimir la primera vez. Segun HapagL.
                            $enc2.="LOC+165+$codPatio:139:6+$codPatioName:TER:ZZZ'".$sepa;
                            $flgLoc165 = false;
                        }
                        $tlSegmentos++;
                        $enc2.="FTX+AAI+++$bkgNumber/$cliente/$transportista'".$sepa;
                        $tlSegmentos++;
                        $enc2.="TDT+1+$maniobra+3'".$sepa;  // 3 : Equivale a Camion y 1. Es Tren.
                        $tlSegmentos++;
                        // Cuando sean salidas es necesario agregar esta línea para cada movimiento,
                        // ya que con este se especifica el Destination Depot.
                        $enc2.="LOC+99+$codPatio"."+::ZZZ'".$sepa;   // 99 : Place of empty equipment return
                        $tlSegmentos++;
                        fputs($fp2,$enc2);
                    }
                }
                $l++;
            } // fin del while

            // Pie de pagina
            // Total de contenedores
            $pie="CNT+16:$tlConte'".$sepa;
            $tlSegmentos++;
            $pie.="UNT+$tlSegmentos+$fecD1$fecD2'".$sepa;
            $pie.="UNZ+1+$fecD1$fecD2'";

            fputs($fp2,$pie);
            fclose($fp2);
            fclose($fp);


            // -------------------
            // Mensajes de error:
            // -------------------
            if( count($msgErr)>0 ){
                foreach( $msgErr as $msgY ){
                    echo "<font color=red>[<b>Error</b>] $msgY</font><br>";
                }
            }
            else{
                // echo "<font color=green>[<b>OK</b>] Archivo sin Errores</font>";
                if( $opcPro=="saveAs" ){
                    // -----------------------------
                    // SALVAR COMO... O ABRIR EN AUTO.
                    // (No modificar)
                    // -----------------------------
                    if(file_exists("$fileEDI")){
                        header('Content-Description: File Transfer');
                        header('Content-Type: application/octet-stream');
                        header('Content-Disposition: attachment; filename='.basename($fileEDI));
                        header('Content-Transfer-Encoding: binary');
                        header('Expires: 0');
                        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                        header('Pragma: public');
                        header('Content-Length: ' . filesize($fileEDI));
                        ob_clean();
                        flush();
                        readfile("$fileEDI");
                        exit;
                    }
                }
                elseif( $opcPro=="email" ){
                    $fileName="GATE-OUT".$ofiCod.$fecD1.$fecD2.".edi";
                    // sendMailMSC($fileEDI,'GATE-OUT',$fileName);
                }
                elseif( $opcPro=="ftp" ){
                    echo "Opcion no disponible por el momento...";
                }
            }
        }
        return $msg;
    }
    // ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
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

    switch($modo){
        case "aceptar":

            // -------------------------------
            // VALIDACION
            // -------------------------------
            if( empty($idNav) ){
                $msg[]="<font color=red>[ ERROR ] NAVIERA no especificada.</font>";
            }
            if( empty($impoExpo) ){
                $msg[]="<font color=red>[ ERROR ] IMPO/EXPO no especificado.</font>";
            }
            if( empty($stConte) ){
                $msg[]="<font color=red>[ ERROR ] STATUS CONTE no especificado.</font>";
            }
            if( ($fileType=="GETOUT") && (empty($codOD)) ){
                $msg[]="<font color=red>[ ERROR ] DESTINO no especificado.</font>";
            }
            // --------------------------------
            if( count($msg)>0 ){
                showForm($arr_request,$msg);
            }
            else{
                // ------------------------------
                // MSC
                // ------------------------------
                if( $sesIdUsuario==10 ){
                    // TRANE
                    if( $idNav=="1" ){
                        // MSC
                        ediGateInMscExcelTrane($stConte,$impoExpo,$sesIdUsuario,$codOD,$opcPro);
                        ediGateOutMscExcelTrane($stConte,$impoExpo,$sesIdUsuario,$codOD,$opcPro);
                        // enviar notificación
                        sendNotify($idNav,$sesIdUsuario);
                    }
                }
                elseif ($sesIdUsuario==17) {
                    echo "Cambie a Convertidor 2 por favor desde el Menu.";
                }
                else {

                    if (($idNav == "1") && ($fileType == "GATE-IN")) {
                        if ($sesIdUsuario == 5) {
                            $msg = ediGateInMscJose($deli, $stConte, $impoExpo, $sesIdUsuario, $codOD, $opcPro);
                        } else {
                            $msg = ediGateInMsc($deli, $stConte, $impoExpo, $sesIdUsuario, $codOD, $opcPro);
                        }


                    } elseif ($idNav == "1" && $fileType == "GATE-OUT") {
                        if ($sesIdUsuario == 5) {
                            $msg = ediGateOutMscJose($deli, $stConte, $impoExpo, $sesIdUsuario, $codOD, $opcPro);
                        } else {
                            $msg = ediGateOutMsc($deli, $stConte, $impoExpo, $sesIdUsuario, $codOD, $opcPro);
                        }

                    } elseif ($idNav == "1" && $fileType == "POSITIONS") {
                        // Posicionamientos
                        $msg = ediPosMsc($deli, $stConte, $impoExpo, $sesIdUsuario, $codOD, $opcPro);
                    } elseif ($idNav == "2" && $fileType == "GATE-IN") {
                        // HapagLloyd
                        $msg = ediGateInHL($deli, $stConte, $impoExpo, $sesIdUsuario, $codOD, $opcPro);
                    } elseif ($idNav == "2" && $fileType == "GATE-OUT") {
                        // HapagLloyd
                        $msg = ediGateOutHL($deli, $stConte, $impoExpo, $sesIdUsuario, $codOD, $opcPro);
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