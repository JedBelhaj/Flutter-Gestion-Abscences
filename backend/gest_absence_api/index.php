<?php
// TODO API specs (Entrypoint)
// 1) function: dispatchRequest
//    method: GET
//    input: route, action
//    output: { success, data|message }
//
// 2) function: validateAuthToken
//    method: POST
//    input: token
//    output: { success, user_id, role } or { success:false, message }
//
// 3) function: jsonResponse
//    method: GET
//    input: status_code, payload
//    output: HTTP JSON response with consistent format

