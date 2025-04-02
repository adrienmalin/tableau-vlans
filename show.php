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
$startPattern           = "(?<=\n)";
$NLP                    = "[\r\n]+";
$vlanPvidPattern        = "vlan (?P<pvid>\d+)$NLP";
$vlanNamePattern        = " name (?P<name>.+)$NLP";
$vlanDescriptionPattern = " description (?P<description>.+)$NLP";
$otherPattern           = " .*$NLP";
$endPattern             = "(?<!#)";
preg_match_all("/$startPattern$vlanPvidPattern(?:$vlanNamePattern|$vlanDescriptionPattern|$otherPattern)*$endPattern/", $conf, $vlans, PREG_SET_ORDER);
$interfaceAddressPattern = "interface [\w-]+(?P<member>\d+)\/0\/(?P<port>\d+)$NLP";
$portAccessPattern       = " port (?:access |trunk |hybrid |pvid |vlan )*(?P<pvid>\d+)$NLP";
$portHybridPattern       = " port hybrid (?:pvid )?vlan (?:(?P<tagged>\d+)(?: [0-9a-z ]*)? tagged|(?P<untagged>\d+)(?: \d+)* untagged)$NLP";
$voiceVlanPattern        = " voice-vlan (?P<voice_vlan>\d+) enable$NLP";
preg_match_all("/$startPattern$interfaceAddressPattern(?:$portAccessPattern|$portHybridPattern|$voiceVlanPattern|$otherPattern)*$endPattern/", $conf, $interfaces, PREG_SET_ORDER);

$stack = array();
foreach ($interfaces as $interface) {
    if (!isset($stack[$interface["member"]])) $stack[$interface["member"]] = [[], []];
    $interface["style"] = "";
    if (!empty($interface["pvid"])) $interface["style"] .= "--pvid: {$interface["pvid"]}; ";
    if (!empty($interface["tagged"])) $interface["style"] .= "--tagged: {$interface["tagged"]}; ";
    if (!empty($interface["untagged"])) $interface["style"] .= "--untagged: {$interface["untagged"]}; ";
    if (!empty($interface["voice_vlan"])) $interface["style"] .= "--voice-vlan: {$interface["voice_vlan"]}; ";
    $stack[$interface["member"]][1 - $interface["port"] % 2][$interface["port"]] = $interface;
}

/*echo ("<!--");
var_dump($stack);
echo ("-->");*/
?>
<!DOCTYPE HTML>
<html lang='fr'>

<head>
    <title><?= $sysname[1] ?? "Switch sans nom" ?> - Tableau des VLANs</title>
    <link href="style.css" rel="stylesheet" />
</head>

<body>
    <header>
        <h1>
            <div><?= $sysname[1] ?? "Switch sans nom" ?></div>
            <small><a href="https://<?= $address[1] ?>" target="_blank" class="link"><?= $address[1] ?></a></small>
        </h1>
    </header>

    <main>
        <div class="stack">
            <h2>Interfaces</h2>
            <?php
            foreach ($stack as $member_id => $lines) {
                echo "<div class='member'>\n<span class='member-id'>$member_id</span>\n<table class='interfaces'>\n<tbody>\n";
                foreach ($lines as $interfaces) {
                    ksort($interfaces);
                    echo "<tr>\n";
                    foreach ($interfaces as $interface) {
                        echo "<td class='{$interface[0]}" . (isset($interface["voice_vlan"]) ? " voice_vlan" : "") . "' title='{$interface[0]}' style='{$interface["style"]}'>{$interface["port"]}</td>\n";
                    };
                    echo "</tr>\n";
                }
                echo "</tr>\n</tbody>\n</table>\n</div>\n";
            }
            ?>
        </div>
        <table class='legend'>
            <caption>
                <h2>Légende</h2>
            </caption>
            <thead>
                <tr>
                    <th>PVID</th>
                    <th>Nom</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($vlans as $vlan) {
                    if (isset($vlan["pvid"]) and $vlan["pvid"] != 1) {
                        $name = $vlan["name"] ?? "";
                        $description = $vlan["description"] ?? "";
                        echo "<tr title='{$vlan[0]}'><td class='interface vlan' style='--pvid: {$vlan["pvid"]}'>{$vlan["pvid"]}</td><td>$name</td><td>$description</td></tr>";
                    }
                }
                ?>
                <tr>
                    <td class='interface trunk'></td>
                    <td colspan='2'>Trunk</td>
                </tr>
                <!--<tr>
                    <td class='interface hybrid' style='--tagged:60; --untagged:0'></td>
                    <td colspan='2'>Hybride (tagged/untagged)</td>
                </tr>-->
                <tr>
                    <td class='interface poe'></td>
                    <td colspan='2'>Power on Ethernet</td>
                </tr>
                <tr>
                    <td class='interface voice_vlan'></td>
                    <td colspan='2'>ToIP (voice-vlan)</td>
                </tr>
                <tr>
                    <td class='interface shutdown'></td>
                    <td colspan='2'>Interface désactivée</td>
                </tr>
            </tbody>
        </table>
    </main>
    <footer>
        <label id="colorSliderLabel" for="colorSlider" class="no-print">Changer les couleurs</label>
        <input id="colorSlider" type="range" min="0" max="360" step="0.000000001" value="58.3"
            oninput="document.documentElement.style.setProperty('--hue', this.value);" class="no-print" />
        <a href="<?= str_replace(__DIR__ . "/", "", $path) ?>" target="_blank" class="link no-print">Télécharger la configuration</a>
        <a href="index.php" class="link no-print">← Retour à la liste</a>
    </footer>
</body>

</html>