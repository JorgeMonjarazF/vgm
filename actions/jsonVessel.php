<?php

if( isset($_POST['query']) ){


	$host = "localhost";
	$user = "nesoftwa_root";
	$password = ";L9Nehbfaxts";
	$database = "nesoftwa_VGM";
	//$param = $_GET["term"];

	//make connection
	$server = mysqli_connect($host, $user, $password,$database);
	if (!$server) {
		echo "Error: No se pudo conectar a MySQL." . PHP_EOL;
		echo "errno de depuración: " . mysqli_connect_errno() . PHP_EOL;
		echo "error de depuración: " . mysqli_connect_error() . PHP_EOL;
		exit;
	}

	$query = $_POST['query'];
	$sql= mysqli_query($server,"select distinct vessel,voyage from VESSEL WHERE vessel like '%{$query}%' order by vessel");
	$array = array();
	while( $row = mysqli_fetch_assoc($sql)){		
		$buque = $row['vessel'];
		$viaje = $row['voyage'];
		$array[] = "$buque - $viaje";
		//$array[] = $row['vessel'];

	}
	echo json_encode($array);
	mysqli_close($server);


}

?>
