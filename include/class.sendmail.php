<?php
//include_once("class.phpmailer.php");
//include_once("class.smtp.php");

require 'PHPMailer-master/PHPMailerAutoload.php';

class SendMail {

	private static $cantidadInstancias = 0;

	public function __construct(){
		// Se crea un constructor para que cuando se realice la instancia no mande un email en blanco.
		self::$cantidadInstancias++;
	}

	function sendMail($arrTo='',$message='',$subject='',$arrCC='',$replayTo='',$arrAdjuntos=''){

        // ------------------------------------------------
        // CONFIGURAR EMAIL.
        // ------------------------------------------------
		$mail = new PHPMailer();
		//$mail->SMTPDebug = 3; USAR EN CASO DE ERROR.
        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = "vishnu.hosting-mexico.net";
		$mail->SMTPAuth = true;                               // Enable SMTP authentication
		$mail->Username = 'netsion@nesoftware.net';                 // SMTP username
		$mail->Password = '!netsion16';                           // SMTP password
		$mail->SMTPSecure = 'tls';  			// Enable TLS encryption, `ssl` also accepted
		$mail->setFrom('netsion@nesoftware.net', 'Robot VGM');         
		$mail->Port = 587;                                    // TCP port to connect to
		$mail->Subject = $subject;
		$mail->isHTML(true);  
		if(!empty($replayTo))$mail->addReplyTo($replayTo, 'Solicitante');
		
		// Adjuntos
		if( is_array($arrAdjuntos) ){
			foreach ($arrAdjuntos as $file) {
				$fileName = str_replace("files/","",$file);
				$mail->addAttachment($file,$fileName);    // Optional name
			}
		}


        // --------------------
        // FORMATO HTML
        // --------------------
		trim($message);
		$mail->Body = "
		<!DOCTYPE HTML>
		<html lang=\"es\">
		<head>
			<meta charset=\"iso8859-1\">
			<style>
				body {
					text-color: #ffffff;
					font-family: \"Arial\", Georgia, Serif;
				}
			</style>
		</head>
		<body>
			<center><font color=red>VGM System</font><hr></center>";
		$mail->Body.=$message;
		$mail->Body.="
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

        // TO :
		foreach ($arrTo as $to) {
			$mail->addAddress ( $to );         	
		}
		// TO :
		if( is_array($arrCC)){
			foreach ($arrCC as $cc) {
				$mail->addCC ( $cc );         	
			} 
		}


        /*// COPIA A:        
        if( $usrEmailCC!="" ){
            $arrDirDestino = explode("\n",$usrEmailCC);
            foreach ( $arrDirDestino as $emailDestino ) {
                if (! empty ( $emailDestino )) {
                    $mail->addCC( $emailDestino );
                    $usrEmailCC= $usrEmailCC . $emailDestino."<br>";
                }
            }
        }
        */

        // BCC :
        //$mail->addBCC("nestor@nesoftware.net");
        

        if( $mail->send() ){
        	return true;
        } 
        else{
        	return false;
        }

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

        /*
        if( !$exito ){
            //echo "[ <font color=red><b>ERROR</b>] Problema de envío Email : ".$mail->ErrorInfo."<hr>";
            return false;
        }
        else{
            //echo "[<b><font color=green>OK</b></font>]  Email enviado a : $usrEmail , CC: $usrEmailCC <hr>";
            return true;
        }
        */

    }



}

?>