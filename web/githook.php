<?php
error_reporting(E_ALL);

if(!isset($_POST['payload'])) {
    die('No payload');
}

$payload = json_decode( $_POST['payload'] );
$repo = $payload->repository->name;
if( empty($repo) || $repo != 'UKMambassador_public' ) { 
    die('Invalid payload');
}

$exec = "/home/ukmno/private_shell/github-pull.sh /home/ukmno/public_subdomains/ambassador/UKMambassador_public/";

error_log('GITHUB: '. $exec);
$output = shell_exec($exec);

echo($output);
?>
