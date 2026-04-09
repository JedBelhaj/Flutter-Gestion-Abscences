<?php
header("Access-Control-Allow-Origin: *");
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("db_connect.php"); // this must define $cnx (mysqli)

$response = array();

if (isset($_POST["nom"]) && isset($_POST["prenom"]) && isset($_POST["mail"]) && !empty($_POST["nom"]) && !empty($_POST["prenom"]) && !empty($_POST["mail"])) {

    $nom = $_POST["nom"];
    $prenom = $_POST["prenom"];
    $mail = $_POST["mail"];

    $query = "INSERT INTO users (nom, prenom, mail) VALUES ('$nom', '$prenom', '$mail')";

    $req = mysqli_query($cnx, $query);

    if ($req) {
        $response["success"] = 1;
        $response["message"] = "User added successfully";
    } else {
        $response["success"] = 0;
        $response["message"] = "Error: " . mysqli_error($cnx);
    }

} else {
    $response["success"] = 0;
    $response["message"] = "Missing fields";
}

echo json_encode($response);
?>