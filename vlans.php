<?php
$basedir = __DIR__ . "/confs/";
$path = realpath($basedir . filter_input(INPUT_GET, "switch", FILTER_SANITIZE_STRING));
if (strpos($path, $basedir) !== 0 || substr($path, -4) != ".cfg") {
    header('HTTP/1.1 404 Not Found');
    die();
}

$conf = file_get_contents($path);

preg_match("/ sysname ([\w-]+)/", $conf, $sysname);
preg_match("/ip address ([\d.]+)/", $conf, $address);
preg_match_all("/(?<=\n)vlan (?P<pvid>\d+)[\r\n]+(?: name (?P<name>.+)[\r\n]+| description (?P<description>.+)[\r\n]+| .*[\r\n]+)*/", $conf, $vlans, PREG_SET_ORDER);
preg_match_all("/(?<=\n)interface [\w-]+(?P<member>\d+)\/0\/(?P<port>\d+)[\r\n]+(?: port hybrid (?:pvid )?vlan (?:(?P<tagged>\d+) tagged|(?P<untagged>\d+)(?: \d+)* untagged)[\r\n]+| port (?:access |trunk |hybrid |pvid |vlan )*(?P<pvid>\d+)[\r\n]+| .*[\r\n]+)*(?<!#)/", $conf, $interfaces, PREG_SET_ORDER);
$stack = array();
foreach ($interfaces as $interface) {
    if (!isset($stack[$interface["member"]])) $stack[$interface["member"]] = array();
    $interface["style"] = "";
    if (!empty($interface["pvid"])) $interface["style"] .= "--pvid: ${interface["pvid"]}; ";
    if (!empty($interface["tagged"])) $interface["style"] .= "--tagged: ${interface["tagged"]}; ";
    if (!empty($interface["untagged"])) $interface["style"] .= "--untagged: ${interface["untagged"]}; ";
    $stack[$interface["member"]][$interface["port"]] = $interface;
}

/* echo ("<!--");
var_dump($stack);
echo ("-->"); */

?>
<!DOCTYPE HTML>
<html lang='fr'>
<head>
<title><?=$sysname[1] ?? "Switch sans nom"?> - Tableau des VLANs</title>
<link href="style.css" rel="stylesheet" />
</head>
<body>
<header>
<h1>
<div><?=$sysname[1] ?? "Switch sans nom"?></div>
<small><a href="https://<?=$address[1]?>" target="_blank"><?=$address[1]?></a></small>
</h1>
</header>
<main>
<table>
<caption><h2>Interfaces</h2></caption>
<tbody>
<?php
function display_interface($interface, $odd) {
    if ($interface["port"] % 2 == $odd) {
        echo "<td class='${interface[0]}' title='${interface[0]}' style='${interface["style"]}'>${interface["port"]}</td>\n";
    }
}

foreach ($stack as $member => $interfaces) {
    echo "<tr>\n<th>$member</th>\n<td>\n<table class='member'>\n<tbody>\n<tr>\n";
    foreach ($interfaces as $interface) display_interface($interface, 1);
    echo "</tr>\n<tr>\n";
    foreach ($interfaces as $interface) display_interface($interface, 0);
    echo "</tr>\n</tbody>\n</table>\n</td>\n</tr>\n";
}
?>
</tbody>
</table>
<table class='legend'>
<caption><h2>Légende</h2></caption>
<thead><tr><th>PVID</th><th>Nom</th><th>Description</th></tr></thead>
<tbody>
<?php
foreach ($vlans as $vlan) {
    if (isset($vlan["pvid"]) and $vlan["pvid"] != 1) {
        $name = $vlan["name"] ?? "";
        $description = $vlan["description"] ?? "";
        echo "<tr title='${vlan[0]}'><td class='interface vlan' style='--pvid: ${vlan["pvid"]}'>${vlan["pvid"]}</td><td>$name</td><td>$description</td></tr>";
    }
}
?>
<tr><td class='interface trunk'></td><td colspan='2'>Trunk</td></tr>
<tr><td class='interface hybrid' style='--tagged:60; --untagged:0'></td><td colspan='2'>Hybride (tagged/untagged)</td></tr>
<tr><td class='interface poe'></td><td colspan='2'>Power on Ethernet</td></tr>
<tr><td class='interface voice-vlan'></td><td colspan='2'>Voice-VLAN</td></tr>
<tr><td class='interface shutdown'></td><td colspan='2'>Interface désactivée</td></tr>
</tbody>
</table>
</main>
<footer>  
<label id="colorSliderLabel" for="colorSlider">Changer les couleurs</label>
<input id="colorSlider" type="range" min="0" max="2000000" step="0.000000001" value="1353651.53435435"
    oninput="document.documentElement.style.setProperty('--k', this.value);">
</footer>
</body>
</html>