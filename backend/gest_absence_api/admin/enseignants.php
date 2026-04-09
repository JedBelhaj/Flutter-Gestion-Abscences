<?php
// TODO API specs (ADMIN enseignants)
// 1) function: listEnseignants
//    method: GET
//    input: token
//    output: { success:true, data:[{id, utilisateur_id, nom, prenom, email, specialite}] } or { success:false, message }
//
// 2) function: createEnseignant
//    method: POST
//    input: token, nom, prenom, email, password, specialite
//    output: { success:true, data:{enseignant_id, utilisateur_id} } or { success:false, message }
//
// 3) function: updateEnseignant
//    method: POST
//    input: token, enseignant_id, nom?, prenom?, email?, password?, specialite?
//    output: { success:true, message } or { success:false, message }

