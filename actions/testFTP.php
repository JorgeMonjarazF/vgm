<?php

//$ftp_server = "187.174.238.23";
// Con VPN se debe utilizar esto : 
$ftp_server = "ftp.hlcl.com";
$ftp_user_name = "mxtra02";
$ftp_user_pass = "ZngU854V";

//$conn_id = ftp_connect($ftp_server);
$conn_id = ftp_connect($ftp_server) or die("Couldn't connect to $ftp_server - $ftp_user_name  ");     
$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
// ftp_pasv($conn_id, true);
if ((!$conn_id) || (!$login_result)) {
    echo "<font color=red>¡La conexión FTP ha fallado ponerse en contacto con Lic.Nestor Perez Tel: 5091-7636 / 5532224753 </font><br>";
    echo "<font color=red>Se intentó conectar al $ftp_server.</font><br>";
    exit;
} 
else {
    echo "<font color=blue>Conexión a $ftp_server realizada con éxito.</font><br>";

    // Cargar archivo
    $upload = ftp_put($conn_id, "test.txt", "../masters/test.txt", FTP_BINARY);
    if (!$upload) {
            echo "<font color=red>La subida FTP ha fallado! </font><br>";
    } 
    else {
        echo "<font color=blue>El Envio a FTP $ftp_server ha sido exitoso!. Archivo : $destination_file </font><br>";
    }
    ftp_close($conn_id);
}

?>

