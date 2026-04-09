<?php
// TODO API specs (ENSEIGNANT absences)
// 1) function: getSeanceEtudiants
//    method: GET
//    input: token, seance_id
//    output: { success:true, data:[{etudiant_id, nom, prenom, statut}] } or { success:false, message }
//
// 2) function: saveAppel
//    method: POST
//    input: token, seance_id, absences:[{etudiant_id, statut}]
//    output: { success:true, message, affected_rows } or { success:false, message }

