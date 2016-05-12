<?php
// ---------------------------------------------------------------------
// EdiAuto : 
// Es que reconozca la carpeta ../edi_files/csv/
// y por cada archivo CSV genere el EDI en la carpeta ../edi_files/edi/
// despues tendra que hacer envio por email cada 2 horas.
// Att. Nestor
// ---------------------------------------------------------------------
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
    Buen día se adjunta los archivos EDI para sus fines que considere necesarios.<br>        
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
    $arrDirDestino[] ="lrodriguez@mscmx.mscgva.ch";    
    $arrDirDestino[] ="auditoria@tytintermodales.com";    

    foreach ( $arrDirDestino as $emailDestino ) {
        if (! empty ( $emailDestino )) {
            $mail->AddAddress ( $emailDestino );
            $emailDesTxt .= "$emailDestino,";
        }
    }    
    // BCC :
    $mail->AddBCC("nestor@tpsol.net");
    // $mail->AddBCC("auditoria@tytintermodales.com");

    // Subject :
    $mail->Subject = "[EDI] Terraports :: PRUEBAS ";

    // Incluir Attach.                    
    $mail->AddAttachment("../edi_files/edi/entradas.edi","entradas.edi");
    $mail->AddAttachment("../edi_files/edi/salidas.edi","salidas.edi");

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
    unlink("../edi_files/csv/salidas.csv");
    unlink("../edi_files/edi/entradas.edi");
    unlink("../edi_files/edi/salidas.edi");      


}
// fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff 
function showForm($form="",$error=""){
    global $db,$db2,$t,$PHP_SELF;
    $t->set_file("page", "ediAuto.inc.html");

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
// fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff
function scanfiles(){
    // Reconocer los archivos  del directorio ../edi_files/csv/
    // y registrarlos en la base de datos;                 
    global $db,$HTTP_POST_FILES,$hoy;

    // Leer los archivos CSV

    $directorio = opendir("../edi_files/csv/"); //ruta actual
    while ($archivo = readdir($directorio)) //obtenemos un archivo y luego otro sucesivamente
    {
        if (is_dir($archivo))//verificamos si es o no un directorio
        {
            // echo "[".$archivo . "]<br />"; //de ser un directorio lo envolvemos entre corchetes
        }
        else
        {
            //echo $archivo . "<br />";
            // Registrarlo en la B.D.
            $sql="select csv_file from EDI_CONTROL where csv_file='$archivo' ";
            $db->query($sql);
            $nr = $db->num_rows();
            if( $nr==0 || empty($nr) ){
                $sql="insert into EDI_CONTROL (";
                $sql.="csv_file";
                $sql.=") values (";
                $sql.="'$archivo'";
                $sql.=")";
                $db->query($sql);
                echo "[Registrado]$archivo<br>";
            }

        }
    }        

}
// fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff    
function processEDI($deli){        
    global $db,$db2,$HTTP_POST_FILES,$hoy;

    // Buscar los archivos nuevos para generar el EDI y mandar email.    
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
            if($dataX[0]!="NAVIERA"){$validFile="False"; echo "[Error] NAVIERA "; break;}
            if($dataX[1]!="CONTENEDOR"){$validFile="False"; echo "[Error] CONTENEDOR "; break;}
            if($dataX[2]!="TAM"){$validFile="False"; echo "[Error] TAM "; break;}
            if($dataX[3]!="CLASIFICACION"){$validFile="False"; echo "[Error] CLASIFICACION "; break;}
            if($dataX[4]!="EIR"){$validFile="False"; echo "[Error] EIR "; break;}
            if($dataX[5]!="FECHA ENTRADA"){$validFile="False"; echo "[Error] FECHA ENTRADA "; break;}
            if($dataX[6]!="HR"){$validFile="False"; echo "[Error] HR "; break;}
            if($dataX[7]!="MANIOBRA"){$validFile="False"; echo "[Error] MANIOBRA  "; break;}
            if($dataX[8]!="COSTO"){$validFile="False"; echo "[Error] COSTO "; break;}
            if($dataX[9]!="FACTURA"){$validFile="False"; echo "[Error] FACTURA "; break;}
            if($dataX[10]!="TRANSPORTISTA"){$validFile="False"; echo "[Error] TRANSPORTISTA "; break;}
            if($dataX[11]!="CLIENTE"){$validFile="False"; echo "[Error] CLIENTE "; break;}
            break;

            /*// ANTERIOR
            if($dataX[0]!="Fecha / Hora"){$validFile="False"; echo "[Error] Fecha / Hora "; break;}
            if($dataX[1]!="Contenedor"){$validFile="False"; echo "[Error] Contenedor "; break;}
            if($dataX[2]!="Tipo"){$validFile="False";echo "[Error] Tipo"; break;}
            if($dataX[3]!="Calidad"){$validFile="False";echo "[Error] Calidad"; break;}
            if($dataX[4]!="Condiciones"){$validFile="False";echo "[Error] Condiciones"; break;}
            if($dataX[5]!="Agencia Aduanal / Transporte"){$validFile="False";echo "[Error] Agencia Aduanal / Transporte"; break;}
            if($dataX[6]!="EIR"){$validFile="False";echo "[Error] EIR "; break;}
            if($dataX[7]!="Maniobra"){$validFile="False";echo "[Error] Maniobra "; break;}                
            break;
            */

        }
        $l++;
    }
    fclose($fp);                                            


    if( $validFile=="True" ){                    


        $fecD1 = date("ymd");
        $fecD2 = date("Hi");
        //$fileName = "MSCE".$fecD1.$fecD2.".edi";
        $fileName = substr($csvFile,0,-4).".edi";
        $flgRec=1;
        $fp= fopen("../edi_files/csv/entradas.csv","r");
        $l=1;                        
        $f=date(Ymd);            
        //$archivo2 = "../edi_files/edi/$fileName";                
        $archivo2 = "../edi_files/edi/entradas.edi";
        $fp2 = fopen("$archivo2","w");
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

                //$fecha= $dataX[0];
                $fecha= $dataX[5];                
                $fecha = str_replace("-","",$fecha);
                $flgAnoOk=0;
                if( preg_match("/^201.*/",$fecha) ) {
                    //201307011620                    
                    $a= substr($fecha,0,4);                    
                    $m= substr($fecha,4,2);
                    $d= substr($fecha,6,2);                    
                    $hr= substr($fecha,8,2);
                    $min= substr($fecha,10,2);
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

                    // Hora:Minuto Formato 24 hrs.
                    $hora= $dataX[6];                
                    unset($parts);
                    if( preg_match("/(\d{2}):(\d{2})/",$hora,$parts) ){
                        $hr= $parts[1];
                        $min= $parts[2];
                    }                     
                    $flgAnoOk=1;        
                }
                $conte= $dataX[1];

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
                    $enc2= "EQD+CN+$conte+$tipo++2+4'".$sepa;
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

        // si no hay registros en el archivo no tiene que creear el archivo, estos es para evitar enviar basura.            
        if( $tlConte==0 ){
            unlink("../edi_files/edi/entradas.edi");
            $existEDIE=0;                
        }
        else{
            $existEDIE=1;                
        }


    }     

    // ----------------------------------------
    // Salidas
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
        if($l==3){                                        
            foreach($data as $campo){
                $campo=addslashes($campo);
                $campo= str_replace("\n","",$campo);
                $campo= str_replace("\r","",$campo);
                $dataX[]=$campo;
            }                        

            // Nuevo Formato de Salidas
            if($dataX[0]!="NAVIERA"){$validFile="False"; echo "[Error] NAVIERA "; break;}
            if($dataX[1]!="CONTENEDOR"){$validFile="False"; echo "[Error] CONTENEDOR "; break;}
            if($dataX[2]!="TAM"){$validFile="False"; echo "[Error] TAM "; break;}
            if($dataX[3]!="CALIDAD"){$validFile="False"; echo "[Error] CALIDAD "; break;}            
            if($dataX[4]!="FACTURA ACON."){$validFile="False"; echo "[Error] FACTURA ACON."; break;}
            if($dataX[5]!="COSTO DE ACON."){$validFile="False"; echo "[Error] COSTO DE ACON. "; break;}
            if($dataX[6]!="EIR"){$validFile="False"; echo "[Error] EIR  "; break;}
            if($dataX[7]!="FECHA "){$validFile="False"; echo "[Error] FECHA"; break;}
            if($dataX[8]!="HR"){$validFile="False"; echo "[Error] HR "; break;}
            if($dataX[9]!="MANIOBRA"){$validFile="False"; echo "[Error] MANIOBRA "; break;}
            if($dataX[10]!="COSTO"){$validFile="False"; echo "[Error] COSTO "; break;}
            if($dataX[11]!="FACTURA"){$validFile="False"; echo "[Error] FACTURA "; break;}
            if($dataX[12]!="TRANSPORTISTA"){$validFile="False"; echo "[Error] TRANSPORTISTA "; break;}
            if($dataX[13]!="CLIENTE"){$validFile="False"; echo "[Error] CLIENTE "; break;}
            if($dataX[14]!="BOOKING"){$validFile="False"; echo "[Error] BOOKING "; break;}

            /*
            // Validacion campos (Anterior)
            if($dataX[0]!="Fecha / Hora"){$validFile="False"; echo "[Error] $csvFile : Fecha / Hora ". $dataX[0] ."<br>"; break;}
            if($dataX[1]!="Contenedor"){$validFile="False"; echo "[Error] $csvFile : Contenedor <br>"; break;}
            if($dataX[2]!="Tipo"){$validFile="False";echo "[Error] $csvFile : Tipo <br>"; break;}
            if($dataX[3]!="Booking"){$validFile="False";echo "[Error] $csvFile : Booking <br>"; break;}
            if($dataX[4]!="Cliente"){$validFile="False";echo "[Error] $csvFile : Cliente <br>"; break;}
            if($dataX[5]!="Transportista"){$validFile="False";echo "[Error] $csvFile : Transportista <br>"; break;}
            if($dataX[6]!="Calidad"){$validFile="False";echo "[Error] $csvFile : Calidad <br>"; break;}
            if($dataX[7]!="EIR"){$validFile="False";echo "[Error] $csvFile : EIR <br>"; break;}
            if($dataX[8]!="Sello"){$validFile="False";echo "[Error] $csvFile : Sello <br>"; break;}
            if($dataX[9]!="Maniobra"){$validFile="False";echo "[Error] $csvFile : Maniobra <br>"; break;}                
            break; 
            */


        }
        $l++;
    }
    fclose($fp); 

    if( $validFile=="True" ){                    
        $fecD1 = date("ymd");
        $fecD2 = date("Hi");
        $fileName = substr($csvFile,0,-4).".edi";
        $flgRec=1;
        $fp= fopen("../edi_files/csv/salidas.csv","r");
        $l=1;                               
        $f=date(Ymd);                    
        //$archivo2 = "../edi_files/edi/$fileName";
        $archivo2 = "../edi_files/edi/salidas.edi";
        $fp2 = fopen("$archivo2","w");
        $sepa="\n";                

        // Terraports tiene el sig, codigo segun MSCA.
        $codePatio = "VER07";
        $patioNom = "TERRAPORTS";

        // ENCABEZADO
        //$enc= "UNB+UNOA:1+TERRAPORTS++$fecD1:$fecD2+$fecD1$fecD2'".$sepa;
        $enc= "UNB+UNOA:1+++$fecD1:$fecD2+$fecD1$fecD2'".$sepa;
        $enc.="UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'".$sepa;                                
        $enc.="BGM+36+$fecD1$fecD2+9'".$sepa;                
        $enc.="LOC+165+$codePatio:139:6+$patioNom:TER:ZZZ'".$sepa;                
        //"NAD+CA+MXVRCDECECI:172:166'".$sepa;
        //"TDT+20++1++MSC:172:166'".$sepa.                              
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
                $fecha= $dataX[7];
                $hora= $dataX[8];
                $fecha= str_replace("-","",$fecha);                
                $flgAnoOk=0;
                if( preg_match("/^201.*/",$fecha) ) {
                    $a= substr($fecha,0,4);                    
                    $m= substr($fecha,4,2);
                    $d= substr($fecha,6,2);                    
                    $hr= substr($fecha,8,2);
                    $min= substr($fecha,10,2);
                    $flgAnoOk=1;
                }
                elseif( preg_match("/(\d{2})(\d{2})(13)/",$fecha,$parts) ){
                    $d = $parts[1];
                    $m = $parts[2];                    
                    $a= "20".$parts[3];                                        
                    $flgAnoOk=1;
                }
                elseif( preg_match("/(\d{2})(\w{3})(\d{2})/",$fecha,$parts) ){
                    $d= $parts[1];                    
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
                    $flgAnoOk=1;        
                }   

                // Hora:Minuto Formato 24 hrs.
                unset($parts);
                if( preg_match("/(\d{2}):(\d{2})/",$hora,$parts) ){
                    $hr= $parts[1];
                    $min= $parts[2];
                }       

                $conte= $dataX[1];
                if( !empty($conte) && ( $flgAnoOk==1 )  ){                
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
                    //COMO SABESMOS SI ES 2 EXPORT ó 3 IMPORT // 4 EMPTY Ó 5 FULL
                    //----------------------------------------------------------
                    // FTX : Remarks +AAI = General Information
                    $enc2= "EQD+CN+$conte+$tipo++3+4'".$sepa;
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
                    $enc2.="LOC+99+$codePatio'".$sepa;   // 99 : Place of empty equipment return
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

        // si no hay registros en el archivo no tiene que creear el archivo, estos es para evitar enviar basura.            
        if( $tlConte==0 ){
            unlink("../edi_files/edi/salidas.edi");
            $existEDIS=0;
        }
        else{
            $existEDIS=1;
        }


    }

    if( $existEDIE==1 || $existEDIS==1 ){
        return 1;
    }
    else{
        return 0;
    }
}   
// fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff    
// ----------------------------------------------------------------
$modo = $_POST['modo'];
// $deli = $_POST['delimitador'];
$deli = ",";
switch($modo){
    case "enviarXXXX":                        
        break;
    default:

        // scanFiles();
        if( processEDI($deli) ){
            sendMail();    
        }               

        showForm();
        break;


}

?>