<?php
// TODO API specs (ADMIN seances)
// 1) function: listSeances
//    method: GET
//    input: token, enseignant_id?, classe_id?, date?
//    output: { success:true, data:[{id, enseignant_id, classe_id, matiere_id, date_seance, heure_debut, heure_fin}] } or { success:false, message }
//
// 2) function: createSeance
//    method: POST
//    input: token, enseignant_id, classe_id, matiere_id, date_seance, heure_debut, heure_fin
//    output: { success:true, data:{seance_id} } or { success:false, message }
//
// 3) function: updateSeance
//    method: POST
//    input: token, seance_id, enseignant_id?, classe_id?, matiere_id?, date_seance?, heure_debut?, heure_fin?
//    output: { success:true, message } or { success:false, message }

