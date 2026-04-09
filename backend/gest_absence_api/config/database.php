<?php
// connxion MySQL
error_reporting(E_ALL);
ini_set('display_errors', 1);

$cnx = mysqli_connect("localhost", "root", "", "gest_absence");

// TODO API/helper specs
// 1) function: getDbConnection
//    method: GET
//    input: none
//    output: mysqli connection or JSON error
//
// 2) function: requireRole
//    method: POST
//    input: token, allowed_roles[]
//    output: { success, user } or { success:false, message }
//
// 3) function: runQuery
//    method: POST
//    input: sql, params
//    output: { success, rows|affected_rows|message }
?>