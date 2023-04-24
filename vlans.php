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
preg_match_all("/(?<=\n)interface [\w-]+(?P<member>\d+)\/0\/(?P<port>\d+)[\r\n]+(?: port hybrid vlan (?P<tagged>\d+) tagged[\r\n]+| port hybrid vlan (?P<untagged>\d+)(?: \d+)* untagged[\r\n]+| port (?P<linktype>access|trunk pvid|hybrid pvid) vlan (?P<pvid>\d+)[\r\n]+| (?P<shutdown>shutdown)[\r\n]+| .*[\r\n]+)*/", $conf, $interfaces, PREG_SET_ORDER);
$stack = array();
foreach ($interfaces as $interface) {
    if (!isset($stack[$interface["member"]])) {
        $stack[$interface["member"]] = array();
    }
    $stack[$interface["member"]][$interface["port"]] = $interface;
}

echo "<!--\n";
print_r($vlans);
print_r($stack);
echo "-->\n";
?>
<!DOCTYPE HTML>
<html lang='fr'>
<head>
<title>Tableau des VLANs - <?=$sysname[1] ?? "Switch sans nom"?></title>
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
function display_port($interface, $odd) {
    if ($interface["port"] % 2 == $odd) {
        $shutdown = $interface["shutdown"] ?? "";
        $linktype = $interface["linktype"] ?? "";
        $tagged = $interface["tagged"] ?? "0";
        $untagged = $interface["untagged"] ?? "0";
        $pvid = $interface["pvid"] ?? "0";
        echo "<td class='number $shutdown $linktype' title='${interface[0]}' style='--pvid: $pvid; --tagged: $tagged; --untagged: $untagged;'>${interface["port"]}</td>\n";
    }
}

foreach ($stack as $member => $interfaces) {
    echo "<tr>\n<th>$member</th>\n<td>\n<table class='member'>\n<tbody>\n<tr>\n";
    foreach ($interfaces as $interface) display_port($interface, 1);
    echo "</tr>\n<tr>\n";
    foreach ($interfaces as $interface) display_port($interface, 0);
    echo "</tr>\n</tbody>\n</table>\n</td>\n</tr>\n";
}
?>
</tbody>
</table>
<table class='vlans'>
<caption><h2>Légende</h2></caption>
<thead><tr><th>PVID</th><th>Nom</th><th>Description</th></tr></thead>
<tbody>
<?php
foreach ($vlans as $vlan) {
    if (isset($vlan["pvid"]) and $vlan["pvid"] != 1) {
        $name = $vlan["name"] ?? "";
        $description = $vlan["description"] ?? "";
        echo "<tr title='${vlan[0]}'><td class='number pvid' style='--pvid: ${vlan["pvid"]}'>${vlan["pvid"]}</td><td>$name</td><td>$description</td></tr>";
    }
}
?>
<tr><td class='number trunk'>T</td><td colspan='2'>Trunk</td></tr>
<tr><td class='number hybrid' style='--tagged:60; --untagged:0'>H</td><td colspan='2'>Hybride (tagged/untagged)</td></tr>
<tr><td class='number shutdown'>S</td><td colspan='2'>Interface désactivée</td></tr>
</tbody>
</table>
</main>
</body>
</html>
