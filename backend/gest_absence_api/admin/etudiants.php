<?php
// TODO API specs (ADMIN etudiants)
// 1) function: listEtudiants
//    method: GET
//    input: token, classe_id?
//    output: { success:true, data:[{id, utilisateur_id, nom, prenom, email, classe_id, classe_nom}] } or { success:false, message }
//
// 2) function: createEtudiant
//    method: POST
//    input: token, nom, prenom, email, password, classe_id
//    output: { success:true, data:{etudiant_id, utilisateur_id} } or { success:false, message }
//
// 3) function: updateEtudiant
//    method: POST
//    input: token, etudiant_id, nom?, prenom?, email?, password?, classe_id?
//    output: { success:true, message } or { success:false, message }

