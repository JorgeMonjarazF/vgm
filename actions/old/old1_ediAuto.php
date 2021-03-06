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
        //Definimos las propiedades y llamamos a los m�todos
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
        <font size=\"2\"><b>( <i>Electronic Data Interchange with Terraports & MSC M�xico</i>)</b></font><br>
        <hr>        
        <p>        
        A quien corresponda : <br>        
        Buen d�a se adjunta los archivos EDI para sus fines que considere necesarios.<br>        
        Para cualquier duda favor de contactarnos.
        <p>            
        <i>
        Att. Robot - Terraports <br>            
        </i>
        <p>                
        <hr>            
        <font color=\"red\" size=\"2\">
        <i>Este es un correo de envio autom�tico generado por nuestro sistema www.tpsol.net, por favor no responda este email.<br></i>        
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
        $arrDirDestino[] ="ehernandez@mscmx.mscgva.ch";
        $arrDirDestino[] ="amartinez@mscmx.mscgva.ch";
        $arrDirDestino[] ="amendez@mscmx.mscgva.ch";        
        $arrDirDestino[] ="gtapia@mscmx.mscgva.ch";        
        $arrDirDestino[] ="llopez@mscmx.mscgva.ch";        
        $arrDirDestino[] ="lmoreno@mscmx.mscgva.ch";        
        $arrDirDestino[] ="aramirez@mscmx.mscgva.ch";        
        $arrDirDestino[] ="gfuentes@mscmx.mscgva.ch";        
        $arrDirDestino[] ="ggarcia@mscmx.mscgva.ch";                
        $arrDirDestino[] ="lrodriguez@mscmx.mscgva.ch";                

        foreach ( $arrDirDestino as $emailDestino ) {
            if (! empty ( $emailDestino )) {
                $mail->AddAddress ( $emailDestino );
                $emailDesTxt .= "$emailDestino,";
            }
        }    
        // BCC :
        $mail->AddBCC("nestor@tpsol.net");

        // Subject :
        $mail->Subject = "[EDI] Terraports :: PRUEBAS ";

        // Incluir Attach.                
        /*
        unset($arrEdiFile);
        $sql="select edi_file from EDI_CONTROL where flg_enviado='0'";
        $db->query($sql);
        while( $db->next_record() ){
        $ediFile = $db->f(edi_file);            
        $arrEdiFile[] = $db->f(edi_file);            
        $mail->AddAttachment("../edi_files/edi/$ediFile","$ediFile");                                    
        }  
        */

        $mail->AddAttachment("../edi_files/edi/msc_entradas.edi","msc_entradas.edi");
        $mail->AddAttachment("../edi_files/edi/msc_salidas.edi","msc_salidas.edi");

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
        //$sql="select csv_file from EDI_CONTROL where flg_enviado='0'";
        //$db->query($sql);
        //while( $db->next_record() ){
        // $csvFile = $db->f(csv_file);            
        //}

        // ---------------------------------
        // --- nuevo -----
        // ---------------------------------
        // Proceso de codificaci�n EDI
        // - Detectar si es Entradas o Salidas
        // - Procesar codificaci�n.
        // ---------------------------------
        //$fp = fopen("../edi_files/csv/$csvFile","r");
        $fp = fopen("../edi_files/csv/msc_entradas.csv","r");
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
        //if( $entSal=="ENTRADAS" ){
        // -----------------------
        // Validar encabezados.
        // -----------------------
        unset($fp);
        unset($data);
        unset($dataX);
        $fp = fopen("../edi_files/csv/msc_entradas.csv","r");
        $validFile="True";                
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
                if($dataX[0]!="Fecha / Hora"){$validFile="False"; echo "[Error] Fecha / Hora "; break;}
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


        if( $validFile=="True" ){                    
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            //$fileName = "MSCE".$fecD1.$fecD2.".edi";
            $fileName = substr($csvFile,0,-4).".edi";
            $flgRec=1;
            $fp= fopen("../edi_files/csv/msc_entradas.csv","r");
            $l=1;                        
            $f=date(Ymd);            
            //$archivo2 = "../edi_files/edi/$fileName";                
            $archivo2 = "../edi_files/edi/msc_entradas.edi";
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
                    //COMO SABESMOS SI ES 2 EXPORT � 3 IMPORT + 4 EMPTY � 5 FULL
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
        }     

        // Grabar el nombre del EDI en la B.D.
        // $sql2="update EDI_CONTROL set edi_file='$fileName' where csv_file='$csvFile'";
        // $db2->query($sql2);             

        //}
        //elseif( $entSal=="SALIDAS" ){
        // ----------------------------------------
        // Salidas
        // ----------------------------------------                

        // Validar encabezados.                                    
        unset($fp);
        unset($data);
        unset($dataX);
        $fp = fopen("../edi_files/csv/msc_salidas.csv","r");
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
            }
            $l++;
        }
        fclose($fp); 

        if( $validFile=="True" ){                    
            $fecD1 = date("ymd");
            $fecD2 = date("Hi");
            $fileName = substr($csvFile,0,-4).".edi";
            $flgRec=1;
            $fp= fopen("../edi_files/csv/msc_salidas.csv","r");
            $l=1;                               
            $f=date(Ymd);                    
            //$archivo2 = "../edi_files/edi/$fileName";
            $archivo2 = "../edi_files/edi/msc_salidas.edi";
            $fp2 = fopen("$archivo2","w");
            $sepa="\n";                

            // Terraports tiene el sig, codigo segun MSCA.
            $codePatio = "VER07";
            $patioNom = "TERRAPORTS";

            // ENCABEZADO
            $enc= "UNB+UNOA:1+++$fecD1:$fecD2+$fecD1$fecD2'".$sepa;
            $enc.="UNH+$fecD1$fecD2+CODECO:D:95B:UN:ITG13'".$sepa;                                
            $enc.="BGM+36+$fecD1$fecD2+9'".$sepa;                
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
                    $bkgNumber = $dataX[3];
                    $bkgNumber = strtoupper($bkgNumber);
                    $cliente = $dataX[4];
                    $cliente = strtoupper($cliente);
                    $transportista= $dataX[5];
                    $transportista = strtoupper($transportista);                    
                    $calidad = $dataX[6];                    
                    $eir= $dataX[7];
                    $sello= $dataX[8];
                    $maniobra= $dataX[9];
                    $maniobra = strtoupper($maniobra);                                       

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
                    //COMO SABESMOS SI ES 2 EXPORT � 3 IMPORT + 4 EMPTY � 5 FULL
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
                    $enc2.="TDT+1+$maniobra+3'".$sepa;
                    $tlSegmentos++;                
                    $enc2.="LOC+99+$codePatio'".$sepa;   // 99 : Place of empty equipment return
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
            //    } 

            // Grabar el nombre del EDI en la B.D.
            // $sql2="update EDI_CONTROL set edi_file='$fileName' where csv_file='$csvFile'";
            // $db2->query($sql2);            
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
            showForm();
            // scanFiles();
            processEDI($deli);
            sendMail();
            break;
    }

?>