<?php
session_start();
    // Descripcion: Controla el menu del sistema
    // Autor: Néstor Pérez Navarro.
    // Fecha: 20121001
    //--------------------------------------------            
include_once("../include/template.inc");
include_once("../include/confGral.php");
$t = new Template("../templates", "keep");       

$idUsr = $_SESSION['sesIdUsuario'];
$user = $_SESSION['sesUsuario'];                                    

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title></title>
    <base target="slave">
    <meta charset="ISO8859-1">
    <!-- Importante de incluir -->
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0,minumum-scale=1.0">
    <!-- Bootstrap -->
    <link rel="stylesheet" href="../css/bootstrap.min.css" media="screen">
</head>
<body background="../images/lamina.jpg">

    <?php                
    // Nombre del Patio.                    
    $patioNom = getValueTable("razon_social","USUARIO","id_usuario",$idUsr);
    $rs = "VGM";        
    $web = "www.proyect.xxx";
    //$logo = "../images/mopsaLogoGris.png";                   
    ?>

    <!-- NAVBAR -->
    <nav class="navbar navbar-inverse">
        <div class="container-fluid">
            <a class="navbar-brand" >VGM System V.1.0</a>
            <ul class="nav navbar-nav">
                <li><a href="#">Link</a></li>

                <li class="dropdown">
                  <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Dropdown <span class="caret"></span></a>
                  <ul class="dropdown-menu">
                    <li><a href="#">Action</a></li>
                    <li><a href="#">Another action</a></li>
                    <li><a href="#">Something else here</a></li>
                    <li role="separator" class="divider"></li>
                    <li><a href="#">Separated link</a></li>
                </ul>
            </li>

        </ul>


    </div>
</nav>


<!-- ESTAS 2 ETIQUETAS SIEMPRE VAN AQUI - NO MOVER -->
<script src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>
</body>
</html>