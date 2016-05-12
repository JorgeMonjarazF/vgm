<?php

include_once("../include/template.inc");
include_once("../include/confGral.php");
include_once("../include/acceso.class.php");
include_once("../include/class.phpmailer.php");


function testMail(){
        global $sesIdUsuario;


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
        $opcPro = strtoupper($opcPro);
        $mail->Body = "
        <!DOCTYPE HTML>
        <html>
        <head>
            <meta charset=\"ISO-8859-1\">
        </head>
        
        <body>
        <b>
        EDICODECO-D95<br>
        Test Mail<br>
        </b>
        <p>
        El sistema Web (www.edifactory.org) le ha enviado una prueba de correo.<br>
        <br>
        <br>
        <i>Att. Robot - Edifactory<br></i>
        <p>
        <hr>
        <font color=\"red\" size=\"2\">
        <i>
        Nota : 
        Este es un correo de envió automático generado por el sistema www.edifactory.org, por favor NO responda este email ya que no será contestado.
        </i>
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
        $mail->AddAddress ("nestor@nesoftware.net");

        // COPIA A:
        //$usrEmailCC="";
        if( !empty($usrEmailCC) ){
            $arrDirDestino = explode("\n",$usrEmailCC);
            foreach ( $arrDirDestino as $emailDestino ) {
                if (! empty ( $emailDestino )) {
                    $mail->AddCC( $emailDestino );
                    $usrEmailCC= $usrEmailCC . $emailDestino."<br>";
                }
            }
        }

        // BCC :
        //$mail->AddBCC("nestor@nesoftware.net");
        
        //Incluir Attach.
        //$fileINSR = str_replace("../edi_files/edi/","",$fileIN);
        //$fileOUTSR = str_replace("../edi_files/edi/","",$fileOUT);
        //$mail->AddAttachment($fileIN,$fileINSR);
        //$mail->AddAttachment($fileOUT,$fileOUTSR);
        $mail->Subject = "[EDIFACTORY][TEST] Notificación de envió EDICODECO";

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
            echo "[ <font color=green><b>Email enviado</b></font> ]  <br>";
        }
}

testMail();


?>

