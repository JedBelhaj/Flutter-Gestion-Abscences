param(
    [string]$BaseUrl = 'http://localhost/backend/gest_absence_api'
)

$ErrorActionPreference = 'Stop'
$script:Failures = New-Object System.Collections.ArrayList

function Add-Failure {
    param([string]$Message)
    Write-Host "[FAIL] $Message" -ForegroundColor Red
    [void]$script:Failures.Add($Message)
}

function Add-Pass {
    param([string]$Message)
    Write-Host "[PASS] $Message" -ForegroundColor Green
}

function Assert-Condition {
    param(
        [bool]$Condition,
        [string]$Message
    )

    if ($Condition) {
        Add-Pass $Message
    } else {
        Add-Failure $Message
    }
}

function Require-Condition {
    param(
        [bool]$Condition,
        [string]$Message
    )

    Assert-Condition -Condition $Condition -Message $Message
    if (-not $Condition) {
        throw "Requirement failed: $Message"
    }
}

function Build-QueryString {
    param([hashtable]$Query)

    if ($null -eq $Query -or $Query.Count -eq 0) {
        return ''
    }

    $pairs = @()
    foreach ($k in $Query.Keys) {
        $v = $Query[$k]
        if ($null -ne $v -and "$v" -ne '') {
            $pairs += ([uri]::EscapeDataString([string]$k) + '=' + [uri]::EscapeDataString([string]$v))
        }
    }

    if ($pairs.Count -eq 0) {
        return ''
    }

    return '?' + ($pairs -join '&')
}

function To-Array {
    param($Value)

    if ($null -eq $Value) {
        return @()
    }

    if ($Value -is [System.Array]) {
        return $Value
    }

    return @($Value)
}

function Get-Prop {
    param(
        $Object,
        [string]$Name
    )

    if ($null -eq $Object) {
        return $null
    }

    $prop = $Object.PSObject.Properties[$Name]
    if ($null -eq $prop) {
        return $null
    }

    return $prop.Value
}

function Invoke-Api {
    param(
        [ValidateSet('GET','POST')]
        [string]$Method,
        [string]$Path,
        [hashtable]$Query,
        [hashtable]$Headers,
        $Body
    )

    $uri = $BaseUrl.TrimEnd('/') + $Path + (Build-QueryString -Query $Query)

    $requestHeaders = @{}
    if ($null -ne $Headers) {
        foreach ($key in $Headers.Keys) {
            $requestHeaders[$key] = $Headers[$key]
        }
    }

    $invokeArgs = @{
        Uri             = $uri
        Method          = $Method
        Headers         = $requestHeaders
        TimeoutSec      = 60
        UseBasicParsing = $true
    }

    if ($null -ne $Body) {
        if (-not $requestHeaders.ContainsKey('Content-Type')) {
            $requestHeaders['Content-Type'] = 'application/json'
        }
        $invokeArgs['Body'] = ($Body | ConvertTo-Json -Depth 12)
    }

    try {
        $response = Invoke-WebRequest @invokeArgs
        $statusCode = [int]$response.StatusCode
        $content = [string]$response.Content
    }
    catch {
        if ($null -eq $_.Exception.Response) {
            throw
        }

        $resp = $_.Exception.Response
        $statusCode = [int]$resp.StatusCode
        $stream = $resp.GetResponseStream()
        $reader = New-Object System.IO.StreamReader($stream)
        $content = $reader.ReadToEnd()
        $reader.Dispose()
    }

    $parsed = $null
    try {
        $parsed = $content | ConvertFrom-Json
    }
    catch {
        $parsed = $content
    }

    return [pscustomobject]@{
        Method = $Method
        Uri    = $uri
        Status = $statusCode
        Body   = $parsed
        Raw    = $content
    }
}

function Invoke-AdminApi {
    param(
        [ValidateSet('GET','POST')]
        [string]$Method,
        [string]$Path,
        [hashtable]$Query,
        [string]$Token,
        $Body
    )

    $q = @{}
    if ($null -ne $Query) {
        foreach ($k in $Query.Keys) {
            $q[$k] = $Query[$k]
        }
    }
    $q['token'] = $Token

    return Invoke-Api -Method $Method -Path $Path -Query $q -Headers @{ Authorization = ('Bearer ' + $Token) } -Body $Body
}

function Assert-Status {
    param(
        $Response,
        [int]$Expected,
        [string]$Context
    )

    Require-Condition ($Response.Status -eq $Expected) "$Context (expected HTTP $Expected, got $($Response.Status))"
}

function Assert-ApiSuccess {
    param(
        $Response,
        [string]$Context
    )

    $success = Get-Prop -Object $Response.Body -Name 'success'
    Require-Condition ($success -eq $true) "$Context (success=true)"
}

Write-Host "Starting backend integration tests against $BaseUrl" -ForegroundColor Cyan

try {
    $setupResponse = Invoke-Api -Method GET -Path '/db_setup.php' -Query @{} -Headers @{} -Body $null
    Assert-Status -Response $setupResponse -Expected 200 -Context 'DB setup endpoint reachable'
    Require-Condition (($setupResponse.Raw -like '*Setup complete*') -or ($setupResponse.Raw -like '*Database*ready*')) 'DB setup completed'

    $badLogin = Invoke-Api -Method POST -Path '/auth/login.php' -Query @{} -Headers @{} -Body @{ email = 'admin@school.tn'; password = 'wrong-password' }
    Assert-Status -Response $badLogin -Expected 401 -Context 'Invalid login rejected'

    $adminLogin = Invoke-Api -Method POST -Path '/auth/login.php' -Query @{} -Headers @{} -Body @{ email = 'admin@school.tn'; password = 'admin123' }
    Assert-Status -Response $adminLogin -Expected 200 -Context 'Admin login accepted'
    Assert-ApiSuccess -Response $adminLogin -Context 'Admin login response'

    $adminToken = Get-Prop -Object $adminLogin.Body -Name 'token'
    Require-Condition (-not [string]::IsNullOrWhiteSpace([string]$adminToken)) 'Admin token returned'

    $whoAmI = Invoke-Api -Method GET -Path '/auth/login.php' -Query @{ token = $adminToken } -Headers @{ Authorization = ('Bearer ' + $adminToken) } -Body $null
    Assert-Status -Response $whoAmI -Expected 200 -Context 'Token introspection endpoint accepted'
    Assert-ApiSuccess -Response $whoAmI -Context 'Token introspection response'

    $unauthClasses = Invoke-Api -Method GET -Path '/admin/classes.php' -Query @{} -Headers @{} -Body $null
    Assert-Status -Response $unauthClasses -Expected 401 -Context 'Admin endpoint rejects missing token'

    $classesList = Invoke-AdminApi -Method GET -Path '/admin/classes.php' -Query @{} -Token $adminToken -Body $null
    Assert-Status -Response $classesList -Expected 200 -Context 'Admin classes list'
    Assert-ApiSuccess -Response $classesList -Context 'Admin classes list payload'

    $stamp = [DateTimeOffset]::UtcNow.ToUnixTimeSeconds()
    $className = 'CI2-AUTO-' + $stamp
    $classLevel = 'Auto Level ' + $stamp

    $createClass = Invoke-AdminApi -Method POST -Path '/admin/classes.php' -Query @{} -Token $adminToken -Body @{ nom = $className; niveau = $classLevel }
    Assert-Status -Response $createClass -Expected 201 -Context 'Admin class create'
    Assert-ApiSuccess -Response $createClass -Context 'Admin class create payload'

    $classData = Get-Prop -Object $createClass.Body -Name 'data'
    $classId = [int](Get-Prop -Object $classData -Name 'id')
    Require-Condition ($classId -gt 0) 'Created class id returned'

    $updatedClassName = $className + '-UPD'
    $updateClass = Invoke-AdminApi -Method POST -Path '/admin/classes.php' -Query @{} -Token $adminToken -Body @{ classe_id = $classId; nom = $updatedClassName }
    Assert-Status -Response $updateClass -Expected 200 -Context 'Admin class update'
    Assert-ApiSuccess -Response $updateClass -Context 'Admin class update payload'

    $getClass = Invoke-AdminApi -Method GET -Path '/admin/classes.php' -Query @{ classe_id = $classId } -Token $adminToken -Body $null
    Assert-Status -Response $getClass -Expected 200 -Context 'Admin class fetch by id'
    Assert-ApiSuccess -Response $getClass -Context 'Admin class fetch payload'
    $fetchedClass = Get-Prop -Object $getClass.Body -Name 'data'
    Require-Condition ((Get-Prop -Object $fetchedClass -Name 'nom') -eq $updatedClassName) 'Class update persisted'

    $teachersList = Invoke-AdminApi -Method GET -Path '/admin/enseignants.php' -Query @{} -Token $adminToken -Body $null
    Assert-Status -Response $teachersList -Expected 200 -Context 'Admin teachers list'
    Assert-ApiSuccess -Response $teachersList -Context 'Admin teachers list payload'

    $teacherEmail = 'auto.teacher.' + $stamp + '@school.tn'
    $teacherPassword = 'Prof!23456'
    $createTeacher = Invoke-AdminApi -Method POST -Path '/admin/enseignants.php' -Query @{} -Token $adminToken -Body @{
        nom = 'AutoTeacherNom'
        prenom = 'AutoTeacherPrenom'
        email = $teacherEmail
        password = $teacherPassword
        specialite = 'Automation'
    }
    Assert-Status -Response $createTeacher -Expected 201 -Context 'Admin teacher create'
    Assert-ApiSuccess -Response $createTeacher -Context 'Admin teacher create payload'

    $teacherData = Get-Prop -Object $createTeacher.Body -Name 'data'
    $teacherId = [int](Get-Prop -Object $teacherData -Name 'enseignant_id')
    Require-Condition ($teacherId -gt 0) 'Created teacher id returned'

    $updateTeacher = Invoke-AdminApi -Method POST -Path '/admin/enseignants.php' -Query @{} -Token $adminToken -Body @{
        enseignant_id = $teacherId
        specialite = 'Automation-Updated'
    }
    Assert-Status -Response $updateTeacher -Expected 200 -Context 'Admin teacher update'
    Assert-ApiSuccess -Response $updateTeacher -Context 'Admin teacher update payload'

    $getTeacher = Invoke-AdminApi -Method GET -Path '/admin/enseignants.php' -Query @{ enseignant_id = $teacherId } -Token $adminToken -Body $null
    Assert-Status -Response $getTeacher -Expected 200 -Context 'Admin teacher fetch by id'
    Assert-ApiSuccess -Response $getTeacher -Context 'Admin teacher fetch payload'

    $studentEmail = 'auto.student.' + $stamp + '@school.tn'
    $studentPassword = 'Stu!23456'
    $createStudent = Invoke-AdminApi -Method POST -Path '/admin/etudiants.php' -Query @{} -Token $adminToken -Body @{
        nom = 'AutoStudentNom'
        prenom = 'AutoStudentPrenom'
        email = $studentEmail
        password = $studentPassword
        classe_id = $classId
    }
    Assert-Status -Response $createStudent -Expected 201 -Context 'Admin student create'
    Assert-ApiSuccess -Response $createStudent -Context 'Admin student create payload'

    $studentData = Get-Prop -Object $createStudent.Body -Name 'data'
    $studentId = [int](Get-Prop -Object $studentData -Name 'etudiant_id')
    Require-Condition ($studentId -gt 0) 'Created student id returned'

    $updateStudent = Invoke-AdminApi -Method POST -Path '/admin/etudiants.php' -Query @{} -Token $adminToken -Body @{
        etudiant_id = $studentId
        nom = 'AutoStudentNomUpdated'
    }
    Assert-Status -Response $updateStudent -Expected 200 -Context 'Admin student update'
    Assert-ApiSuccess -Response $updateStudent -Context 'Admin student update payload'

    $studentsByClass = Invoke-AdminApi -Method GET -Path '/admin/etudiants.php' -Query @{ classe_id = $classId } -Token $adminToken -Body $null
    Assert-Status -Response $studentsByClass -Expected 200 -Context 'Admin students list by class'
    Assert-ApiSuccess -Response $studentsByClass -Context 'Admin students list payload'
    $studentsRows = To-Array (Get-Prop -Object $studentsByClass.Body -Name 'data')
    $matchedStudent = $studentsRows | Where-Object { [int](Get-Prop -Object $_ -Name 'etudiant_id') -eq $studentId }
    Require-Condition (@($matchedStudent).Count -ge 1) 'Created student visible in class filter'

    $sessionDate = (Get-Date).ToString('yyyy-MM-dd')
    $createSession = Invoke-AdminApi -Method POST -Path '/admin/seances.php' -Query @{} -Token $adminToken -Body @{
        enseignant_id = $teacherId
        classe_id = $classId
        matiere_id = 1
        date_seance = $sessionDate
        heure_debut = '09:00'
        heure_fin = '10:00'
    }
    Assert-Status -Response $createSession -Expected 201 -Context 'Admin session create'
    Assert-ApiSuccess -Response $createSession -Context 'Admin session create payload'

    $sessionData = Get-Prop -Object $createSession.Body -Name 'data'
    $sessionId = [int](Get-Prop -Object $sessionData -Name 'seance_id')
    Require-Condition ($sessionId -gt 0) 'Created session id returned'

    $updateSession = Invoke-AdminApi -Method POST -Path '/admin/seances.php' -Query @{} -Token $adminToken -Body @{
        seance_id = $sessionId
        heure_fin = '10:30'
    }
    Assert-Status -Response $updateSession -Expected 200 -Context 'Admin session update'
    Assert-ApiSuccess -Response $updateSession -Context 'Admin session update payload'

    $sessionsList = Invoke-AdminApi -Method GET -Path '/admin/seances.php' -Query @{ enseignant_id = $teacherId; classe_id = $classId; date = $sessionDate } -Token $adminToken -Body $null
    Assert-Status -Response $sessionsList -Expected 200 -Context 'Admin sessions list with filters'
    Assert-ApiSuccess -Response $sessionsList -Context 'Admin sessions list payload'
    $sessionRows = To-Array (Get-Prop -Object $sessionsList.Body -Name 'data')
    $matchedSession = $sessionRows | Where-Object { [int](Get-Prop -Object $_ -Name 'id') -eq $sessionId }
    Require-Condition (@($matchedSession).Count -ge 1) 'Created session visible in filtered list'

    $teacherOwnSessions = Invoke-Api -Method GET -Path '/enseignant/seances.php' -Query @{ enseignant_id = $teacherId; date_from = $sessionDate; date_to = $sessionDate } -Headers @{} -Body $null
    Assert-Status -Response $teacherOwnSessions -Expected 200 -Context 'Enseignant sessions list'
    Assert-ApiSuccess -Response $teacherOwnSessions -Context 'Enseignant sessions payload'

    $saveAttendance = Invoke-Api -Method POST -Path '/enseignant/absences.php' -Query @{} -Headers @{} -Body @{
        enseignant_id = $teacherId
        seance_id = $sessionId
        absences = @(
            @{ etudiant_id = $studentId; statut = 'absent' }
        )
    }
    Assert-Status -Response $saveAttendance -Expected 200 -Context 'Enseignant attendance save'
    Assert-ApiSuccess -Response $saveAttendance -Context 'Enseignant attendance save payload'

    $attendanceList = Invoke-Api -Method GET -Path '/enseignant/absences.php' -Query @{ enseignant_id = $teacherId; seance_id = $sessionId } -Headers @{} -Body $null
    Assert-Status -Response $attendanceList -Expected 200 -Context 'Enseignant attendance list'
    Assert-ApiSuccess -Response $attendanceList -Context 'Enseignant attendance list payload'
    $attendanceRows = To-Array (Get-Prop -Object $attendanceList.Body -Name 'data')
    $studentAttendance = $attendanceRows | Where-Object { [int](Get-Prop -Object $_ -Name 'etudiant_id') -eq $studentId }
    Require-Condition (@($studentAttendance).Count -ge 1) 'Attendance row exists for created student'
    Require-Condition ((Get-Prop -Object $studentAttendance[0] -Name 'statut') -eq 'absent') 'Attendance status persisted as absent'

    $studentProfile = Invoke-Api -Method GET -Path '/etudiant/profil.php' -Query @{ etudiant_id = $studentId } -Headers @{} -Body $null
    Assert-Status -Response $studentProfile -Expected 200 -Context 'Etudiant profile fetch'
    Assert-ApiSuccess -Response $studentProfile -Context 'Etudiant profile payload'

    $studentAbsences = Invoke-Api -Method GET -Path '/etudiant/absences.php' -Query @{ etudiant_id = $studentId; date_from = $sessionDate; date_to = $sessionDate; matiere_id = 1 } -Headers @{} -Body $null
    Assert-Status -Response $studentAbsences -Expected 200 -Context 'Etudiant absences list'
    Assert-ApiSuccess -Response $studentAbsences -Context 'Etudiant absences payload'
    $studentAbsRows = To-Array (Get-Prop -Object $studentAbsences.Body -Name 'data')
    $matchedAbsence = $studentAbsRows | Where-Object { [int](Get-Prop -Object $_ -Name 'seance_id') -eq $sessionId }
    Require-Condition (@($matchedAbsence).Count -ge 1) 'Created absence visible for student'

    $teacherLogin = Invoke-Api -Method POST -Path '/auth/login.php' -Query @{} -Headers @{} -Body @{ email = $teacherEmail; password = $teacherPassword }
    Assert-Status -Response $teacherLogin -Expected 200 -Context 'Teacher login accepted'
    Assert-ApiSuccess -Response $teacherLogin -Context 'Teacher login payload'

    $studentLogin = Invoke-Api -Method POST -Path '/auth/login.php' -Query @{} -Headers @{} -Body @{ email = $studentEmail; password = $studentPassword }
    Assert-Status -Response $studentLogin -Expected 200 -Context 'Student login accepted'
    Assert-ApiSuccess -Response $studentLogin -Context 'Student login payload'
}
catch {
    Add-Failure ("Fatal test interruption: " + $_.Exception.Message)
}

if ($script:Failures.Count -gt 0) {
    Write-Host ''
    Write-Host 'Backend integration tests finished with failures:' -ForegroundColor Red
    foreach ($failure in $script:Failures) {
        Write-Host (' - ' + $failure) -ForegroundColor Red
    }
    exit 1
}

Write-Host ''
Write-Host 'All backend integration tests passed.' -ForegroundColor Green
exit 0
