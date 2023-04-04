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
preg_match_all("/\nvlan (?P<pvid>\d+)(?:[\r\n]+ name (?P<name>.+))?(?:[\r\n]+ description (?P<description>.*))?/", $conf, $vlans, PREG_SET_ORDER);
preg_match_all("/\n(?P<conf>(?P<name>interface [\w-]+(?:[\r\n]+ .*)*(?P<member>\d+)\/0\/(?P<port>\d+))[\r\n]+(?: description (?P<description>.*))?[\r\n]+(?P<shutdown> shutdown[\r\n]+)?(?: port access vlan (?P<pvid>\d+)[\r\n]+| .*[\r\n]+)*)/", $conf, $interfaces, PREG_SET_ORDER);

$stack = array();
foreach ($interfaces as $interface) {
    if (!$stack[$interface["member"]]) {
        $stack[$interface["member"]] = array();
    }
    $stack[$interface["member"]][$interface["port"]] = $interface;
}

?>
<!DOCTYPE HTML>
<html lang='fr'>
<head>
<title>Tableau des VLANs - <?=$sysname[1]?></title>
<link href="style.css" rel="stylesheet" />
</head>
<body>
<header>
<h1>
<div><?=$sysname[1]?></div>
<div><small><a href="https://<?=$address[1]?>" target="_blank"><?=$address[1]?></a></small></div>
</h1>
</header>
<main>
<table>
<caption><h2>Interfaces</h2></caption>
<tbody>
<?php
foreach ($stack as $member => $interfaces) {
    echo "<tr>\n<th>$member</th>\n<td>\n<table class='member'>\n<tbody>\n<tr>\n";
    foreach ($interfaces as $interface) {
        if ($interface["port"] % 2) {
            echo "<td class='number ".($interface["shutdown"]? "shutdown" : "pvid")."' title='".$interface["conf"]."' style='--pvid: ".$interface["pvid"]."'>".$interface["port"]."</td>\n";
        }
    }
    echo "</tr>\n<tr>\n";
    foreach ($interfaces as $interface) {
        if ($interface["port"] % 2 == 0) {
            echo "<td class='number ".($interface["shutdown"]? "shutdown" : "pvid")."' title='".$interface["conf"]."' style='--pvid: ".$interface["pvid"]."'>".$interface["port"]."</td>\n";
        }
    }
    echo "</tr>\n</tbody>\n</table>\n</td>\n</tr>\n";
}
?>
</tbody>
</table>
<table class='vlans'>
<caption><h2>VLANs</h2></caption>
<thead><tr><th>PVID</th><th>Nom</th><th>Description</th></tr></thead>
<tbody>
<?php
foreach ($vlans as $vlan) {
    if ($vlan["pvid"] != 1) {
        echo "<tr><td class='number pvid' style='--pvid: ${vlan["pvid"]}'>${vlan["pvid"]}</td><td>${vlan["name"]}</td><td>${vlan["description"]}</td></tr>";
    }
}
?>
<tr><td class='number shutdown'></td><td colspan='2'>Interface désactivée</td></tr>
</tbody>
</table>
</main>
</body>
</html>
