<?php
session_start();

//--------------------------------------------
// Fecha: 20051017
//--------------------------------------------
include_once("../include/template.inc");
include_once("../include/confGral.php");
include_once("../include/acceso.class.php");    
include_once("../include/class.phpmailer.php");

$usuario=new Acceso;
$t = new Template("../templates", "keep");
$sesIdUsuario = $_SESSION[sesIdUsuario];


//    if( $usuario->havePerm("1,4",$_SESSION['sesArrPerms'] )){
// ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
function sendMail($numEmail=""){
    global $hoy,$db,$db2;

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
    $mail->Username = "robot@tpsol.net";
    $mail->Password = "robottpsol2013";
    $mail->From = "robot@tpsol.net";
    $mail->FromName = "Robot Terraports";

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
    <font size=\"4\"><b>Terraports S.A. de C.V.</b></font><br>
    <font size=\"2\"><b>( <i>Electronic Data Interchange with Terraports & MSC México</i>)</b></font><br>
    <hr>        
    <p>        
    A quien corresponda : <br>        
    Buen día se adjunta los archivos EDI-ENTRADA para sus fines que considere necesarios.<br>        
    Para cualquier duda favor de contactarnos.
    <p>            
    <i>
    Att. Robot - Terraports <br>            
    </i>
    <p>                
    <hr>            
    <font color=\"red\" size=\"2\">
    <i>Este es un correo de envio automático generado por nuestro sistema www.tpsol.net, por favor no responda este email.<br></i>        
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
    /*
    $arrDirDestino[] ="ehernandez@mscmx.mscgva.ch";
    $arrDirDestino[] ="amartinez@mscmx.mscgva.ch";
    $arrDirDestino[] ="amendez@mscmx.mscgva.ch";        
    $arrDirDestino[] ="gtapia@mscmx.mscgva.ch";        
    $arrDirDestino[] ="llopez@mscmx.mscgva.ch";        
    $arrDirDestino[] ="lmoreno@mscmx.mscgva.ch";        
    $arrDirDestino[] ="aramirez@mscmx.mscgva.ch";        
    $arrDirDestino[] ="gfuentes@mscmx.mscgva.ch";        
    $arrDirDestino[] ="ggarcia@mscmx.mscgva.ch";                
    */
    $arrDirDestino[] ="eqc_mscmxver@mscmx.mscgva.ch";    
    $arrDirDestino[] ="auditoria@tytintermodales.com";    
    //$arrDirDestino[] ="nezmazter@gmail.com";    

    foreach ( $arrDirDestino as $emailDestino ) {
        if (! empty ( $emailDestino )) {
            $mail->AddAddress ( $emailDestino );
            $emailDesTxt .= "$emailDestino,";
        }
    }    
    // BCC :
    $mail->AddBCC("nestor@tpsol.net");
    $mail->AddBCC("lrodriguez@mscmx.mscgva.ch");
    // $mail->AddBCC("auditoria@tytintermodales.com");

    // Subject :
    $mail->Subject = "TYT VERACRUZ // ARCHIVOS EDI";

    // Incluir Attach.                    
    $mail->AddAttachment("../edi_files/edi/entradas.edi","entradas.edi");
    //$mail->AddAttachment("../edi_files/edi/salidas.edi","salidas.edi");

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
        echo "[ <font color=red><b>Problema de envio</b></font> ] $emailDestino -> $valor".$mail->ErrorInfo."<br>";
    }
    else{
        echo "[ <font color=green><b>Enviado</b></font> ] <br>";
        /*
        // Acutalizar bandera a enviado
        foreach( $arrEdiFile as $fileEdi ){
        $sql="update EDI_CONTROL set flg_enviado='1' where edi_file='$fileEdi'";                
        $db->query($sql);
        }                       
        */
    } 

    // ---------------------------------------------------------
    // ELIMINAR los archivos CSV una vez enviados.
    // ---------------------------------------------------------        
    unlink("../edi_files/csv/entradas.csv");
    //unlink("../edi_files/csv/salidas.csv");
    unlink("../edi_files/edi/entradas.edi");
    //unlink("../edi_files/edi/salidas.edi");      


}
// fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff 
function showForm($form="",$error=""){
    global $db,$db2,$t,$PHP_SELF;
    $t->set_file("page", "ediE.inc.html");

    // inicializar vars
    $t->set_var("ACTION",$PHP_SELF);                        
    $t->set_var("MENSAJE","");                        


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
// fffffffffffffffffffffffffffffffffffffffffffffffffffffffff
function almacenarCSV($deli){
    global $db,$db2,$t,$HTTP_POST_FILES,$hoy;

    // $hoy=date("Y-m-d");
    $hoy = date("Y-m-d H:i");
    // Copia el archivo del usuario al directorio ../files
    if (is_uploaded_file($HTTP_POST_FILES['userfile']['tmp_name'])) {
        copy($HTTP_POST_FILES['userfile']['tmp_name'], "../edi_files/csv/getIn.csv");
    }
    else {
        $msg="<h1><font color=\"red\">Error en el envio del archivo, vuela a intentarlo! " . $HTTP_POST_FILES['userfile']['name'];
        $msg.="</font></h1>";
        echo $msg;
    }

    // ---------------------------------------
    // Validar encabezado del archivo CSV.
    // ---------------------------------------
    $fp = fopen("../edi_files/csv/getIn.csv","r+");
    $validFile="True";
    // Linea del encabezado
    $l=1;            
    while ( $data = fgetcsv($fp,1000,"$deli") ) {
        if($l==8){                                        
            foreach($data as $campo){
                $campo=addslashes($campo);
                $campo= str_replace("\n","",$campo);
                $campo= str_replace("\r","",$campo);
                $dataX[]=$campo;
            }
            // Validacion campos
            if($dataX[0]!="Fecha / Hora"){$validFile="False"; echo "[Error] Fecha"; break;}
            if($dataX[1]!="Contenedor"){$validFile="False"; echo "[Error] Contenedor "; break;}
            if($dataX[2]!="Tipo"){$validFile="False";echo "[Error] Tipo"; break;}
            if($dataX[3]!="Calidad"){$validFile="False";echo "[Error] Calidad"; break;}
            if($dataX[4]!="Condiciones"){$validFile="False";echo "[Error] Condiciones"; break;}
            if($dataX[5]!="Agencia Aduanal / Transporte"){$validFile="False";echo "[Error] Agencia Aduanal / Transporte"; break;}
            if($dataX[6]!="EIR"){$validFile="False";echo "[Error] EIR "; break;}
            if($dataX[7]!="Maniobra"){$validFile="False";echo "[Error] Maniobra "; break;}                
            break;
        }
        $l++;
    }
    fclose($fp);             

    if( empty($data) ){
        $validFile="False";
    }            

    if($validFile=="False"){
        $msg="Archivo corrupto: Posibles causas <p>1. Los encabezados han cambiado.<br>2. El delimitador es incorrecto<br> 3. El archivo no tiene formato CSV.<br>";
        $error[archivo]=$msg;
        echo "<font color=\"red\" size=\"3\">$msg<p></p></font>";
        return $error;
    }
    else{
        // ------------------------------------------
        // PROCESO.-Consultar todos los datos de EXPO usando el contenedor de base
        // ------------------------------------------

        $fecD1 = date("ymd");
        $fecD2 = date("Hi");
        $fileName = "MSCE".$fecD1.$fecD2.".edi";                


        $flgRec=1;
        $fp= fopen("../edi_files/csv/getIn.csv","r+");
        $l=1;                        

        $f=date(Ymd);            
        $fileEDI = "../edi_files/edi/$fileName";
        $fp2 = fopen("$fileEDI","w");
        $sepa="\n";                

        // Terraports tiene el sig, codigo segun MSCA.
        $codePatio = "VER07";
        $patioNom = "TERRAPORTS";

        // ENCABEZADO
        $enc= "UNB+UNOA:1+++$fecD1:$fecD2+$fecD1$fecD2'".$sepa;
        $enc.="UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'".$sepa;                                
        $enc.="BGM+34+$fecD1$fecD2+9'".$sepa;                
        $enc.="LOC+165+$codePatio:139:6+$patioNom:TER:ZZZ'".$sepa;                
        //"TDT+20++1++MSC:172:166'".$sepa.                  
        //"NAD+CA+MXVRCDECECI:172:166'".$sepa;
        //$enc11=utf8_encode($enc);
        fputs($fp2,$enc);
        //$e=0;  // elemento del arreglo.

        $tlConte=0;
        $tlSegmentos =4;
        while ($data = fgetcsv($fp,1000,"$deli")) {
            if( $l > 8 ){
                unset($dataX);
                foreach($data as $campo){
                    $campo=addslashes($campo);
                    $campo= str_replace("\n","",$campo);
                    $campo= str_replace("\r","",$campo);
                    $dataX[]=$campo;
                }
                $tlConte++;                
                $fecha= $dataX[0];
                $conte= $dataX[1];
                $tamano= $dataX[2];
                $calidad= $dataX[3];
                $condConte= $dataX[4];
                $transportista= $dataX[5];
                $transportista = strtoupper($transportista);
                $eir= $dataX[6];
                $maniobra= $dataX[7];
                $maniobra = strtoupper($maniobra);
                $procedencia= $dataX[8];
                $sello= $dataX[9];                        
                $d= substr($fecha,0,2);
                $m= substr($fecha,3,2);
                $a= substr($fecha,6,4);
                $hr= substr($fecha,11,2);
                $min= substr($fecha,14,2);
                $regFecha= "$a$m$d";                        
                $regHora="$hr$min";                                                                        
                $conte=trim($conte);
                //$conte=preg_replace($pattern,$replacement,$conte);
                $tamano= strtoupper($tamano);
                $tamano = str_replace(" ","",$tamano);
                switch($tamano){
                    case "20DC":
                        $tipo="22G0";
                        break;
                    case "20FL":
                        $tipo="22P3";
                        break;
                    case "20RF":
                        $tipo="22R1";
                        break;
                    case "20TK":
                        $tipo="22T3";
                        break;
                    case "20OT":
                        $tipo="22U1";
                        break;
                    case "40DC":
                        $tipo="42G0";
                        break;
                    case "40HC":
                        $tipo="45G0";
                        break;
                    case "40RH":
                        $tipo="45R1";
                        break;
                    case "40OT":
                        $tipo="42UT";
                        break;
                    case "40FL":
                        $tipo="42P3";
                        break;
                    case "40RF":
                        $tipo="42R1";
                        break;
                    case "40TK":
                        $tipo="42T0";
                        break;
                }

                //---------------------------------------------------------
                //COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                //----------------------------------------------------------
                // FTX : Remarks +AAI = General Information
                ($tipoMov=="E")?$tmov=4:$tmov=5;                    
                $enc2= "EQD+CN+$conte+$tipo++3+$tmov'".$sepa;
                $tlSegmentos++;                
                $enc2.="DTM+7:$regFecha$regHora:203'".$sepa;
                $tlSegmentos++;                
                $enc2.="FTX+AAI+++$transportista/$eir'".$sepa;
                $tlSegmentos++;                
                $enc2.="TDT+1+$maniobra+3'".$sepa;
                $tlSegmentos++;                
                $enc2.="LOC+99+$codePatio+::ZZZ'".$sepa;   // 99 : Place of empty equipment return
                $tlSegmentos++;                
                fputs($fp2,$enc2);

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
}    
// fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
function getEdiMSC($deli,$tipoMov,$impoExpo){        
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


    // -------------------------------------
    // ENTRADAS
    // -------------------------------------          
    unset($fp);
    unset($data);
    unset($dataX);
    $fp = fopen("../edi_files/csv/entradas.csv","r");
    $validFile="True";                
    $l=1;                            
    while ( $data = fgetcsv($fp,1000,"$deli") ) {                    
        if($l==3){                                        
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
        $fecD1 = date("ymd");
        $fecD2 = date("Hi");
        //$fileName = "MSCE".$fecD1.$fecD2.".edi";
        $fileName = substr($csvFile,0,-4).".edi";
        $flgRec=1;
        $fp= fopen("../edi_files/csv/entradas.csv","r");
        $l=1;                        
        $f=date(Ymd);            
        //$fileEDI = "../edi_files/edi/$fileName";                
        $fileEDI = "../edi_files/edi/entradas.edi";
        $fp2 = fopen("$fileEDI","w");
        $sepa="\n";                
        // Terraports tiene el sig, codigo segun MSCA.
        $codePatio = "VER07";
        $patioNom = "TERRAPORTS";
        // ENCABEZADO
        $enc= "UNB+UNOA:1+++$fecD1:$fecD2+$fecD1$fecD2'".$sepa;
        $enc.="UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'".$sepa;                                
        $enc.="BGM+34+$fecD1$fecD2+9'".$sepa;                
        $enc.="LOC+165+$codePatio:139:6+$patioNom:TER:ZZZ'".$sepa;                
        //"TDT+20++1++MSC:172:166'".$sepa.                  
        //"NAD+CA+MXVRCDECECI:172:166'".$sepa;
        //$enc11=utf8_encode($enc);
        fputs($fp2,$enc);
        //$e=0;  // elemento del arreglo.
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
                //$fecha= $dataX[0];
                $fecha= $dataX[5];                                
                $fecha = str_replace("-","",$fecha);                                                
                $flgAnoOk=0;
                if( preg_match("/^201.*/",$fecha) ) {
                    //201307011620                    
                    $a= substr($fecha,0,4);                    
                    $m= substr($fecha,4,2);
                    $d= substr($fecha,6,2);                                        
                    $flgAnoOk=1;
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
                $hora= $dataX[6];                
                unset($parts);
                if( preg_match("/(\d{2}):(\d{2})/",$hora,$parts) ){
                    $hr= $parts[1];
                    if($hr=="24"){$hr="23";};
                    $min= $parts[2];
                }                     
                $flgAnoOk=1;                            


                // -------------
                // Validacion
                // -------------
                $thisYear = date("Y");
                if( $a<>$thisYear ){
                    $msgErr[] = "<font color=red>[Error][$conte] Año incorrecto, favor de corregir el formato de la fecha en su archivo CSV.</font>";
                    $flgAnoOk=0;                            
                }
                if( empty($hr) || empty($min) ){
                    $msgErr[] = "<font color=red>[Error][$conte] Hora o Minuto incorrecto, favor de corregir el formato a 24 hrs.</font>";
                    $flgAnoOk=0;                            
                }
                // -----------------


                if( !empty($conte) && ($flgAnoOk==1)  ){
                    $tlConte++;                
                    $tamano= $dataX[2];
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
                    switch($tamano){
                        case "20DC":
                            $tipo="22G0";
                            break;
                        case "20FL":
                            $tipo="22P3";
                            break;
                        case "20RF":
                            $tipo="22R1";
                            break;
                        case "20TK":
                            $tipo="22T3";
                            break;
                        case "20OT":
                            $tipo="22U1";
                            break;
                        case "40DC":
                            $tipo="42G0";
                            break;
                        case "40HC":
                            $tipo="45G0";
                            break;
                        case "40RH":
                            $tipo="45R1";
                            break;
                        case "40OT":
                            $tipo="42UT";
                            break;
                        case "40FL":
                            $tipo="42P3";
                            break;
                        case "40RF":
                            $tipo="42R1";
                            break;
                        case "40TK":
                            $tipo="42T0";
                            break;
                    }

                    // echo "$conte : $regHora <br>";


                    //---------------------------------------------------------
                    //COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT + 4 EMPTY Ó 5 FULL
                    //----------------------------------------------------------
                    // FTX : Remarks +AAI = General Information                    
                    ($impoExpo=="E")?$stIE=2:$stIE=3;                    
                    ($tipoMov=="E")?$tmov=4:$tmov=5;                    
                    $enc2= "EQD+CN+$conte+$tipo++$stIE+$tmov'".$sepa;
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

        /*
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
        */


        // -------------------
        // Mensajes de error:
        // -------------------        
        if( count($msgErr)>0 ){
            foreach( $msgErr as $msgY ){
                echo "<font color=red>[<b>Error</b>] $msgY</font><br>";
            }    
        }
        else{
            echo "<font color=green>[<b>OK</b>] Archivo sin Errores</font>";
        }




    }     
}   
// fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff    
// ----------------------------------------------------------------
$modo = $_POST['modo'];
$deli = $_POST['delimitador'];
$ori = $_POST['ori'];
$tipoMov = $_POST['tipoMov'];
$impoExpo = $_POST['impoExpo'];

switch($modo){
    case "validar":
        //almacenarCSV($deli);        
        showForm();
        getEdiMSC($deli,$tipoMov,$impoExpo);        
        break;
    case "email":        
        sendMail();
        showForm();
        echo "<font color=\"blue\">[ Email ] Enviado.</font><br>";
        break;
    default:
        showForm();
        break;
}

/*
} // fin havePerm()
else{
$t->set_file("page", "accesoDenegado.inc.html");
$t->pparse("out","page");
}

*/


?>