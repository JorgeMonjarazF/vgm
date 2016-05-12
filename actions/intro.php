<?php
session_start();

//--------------------------------------------
// Fecha: 20051017
//--------------------------------------------
include_once("../include/template.inc");
include_once("../include/confGral.php");
include_once("../include/acceso.class.php");


$usuario=new Acceso;
$t = new Template("../templates", "keep");
$sesIdUsuario = $_SESSION[sesIdUsuario];

function showForm($sesIdUsuario){
	global $t,$db,$PHP_SELF;

	$t->set_file("pageH", "header.inc.html");
	$t->set_var("SESUSUARIO",$_SESSION['sesUsuario']);
	$t->pparse("out","pageH");


	$t->set_file("page", "intro.inc.html"); 


    // inicializar vars
	$t->set_var("ACTION",$PHP_SELF);
	$t->set_var("MENSAJE","");

    // Reconcer si el usuario es un usuario activo con su pago,
    // Si es true, entonces no debe aparecer el cuadro de que debe realizar su suscripcion.


	$t->pparse("out","page");
	$t->set_file("pageF", "footer.inc.html");$t->pparse("out","pageF");    
}
// ----------------------------------------------------------------

showForm($sesIdUsuario);


?>