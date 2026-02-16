<?php
// testar_buscar_ordem_manual.php
header('Content-Type: text/plain; charset=utf-8');
$num_ordem = isset($_GET['num_ordem']) ? $_GET['num_ordem'] : '18254562';

$resp = file_get_contents("http://localhost/PROJETOS%20DESENVOLVEDOR/Cofelma/Mercado/Gest%C3%A3o%20Filas%20v1.1/buscar_ordem_manual.php?num_ordem=" . urlencode($num_ordem));
echo "Resposta bruta do buscar_ordem_manual.php:\n\n";
echo $resp;

$json = json_decode($resp, true);
echo "\n\n---\n\n";
echo "JSON decodificado:\n";
var_export($json);
