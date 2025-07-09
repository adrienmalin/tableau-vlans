<?php

$path = realpath($basedir . DIRECTORY_SEPARATOR . ltrim(urldecode($_SERVER["QUERY_STRING"]), '/'));

if (
    strpos($path, $basedir) !== 0
    || substr($path, -4) != ".cfg"
    || !file_exists($path)
) {
    http_response_code(404);
    die("Fichier non trouvé");
}

$conf = file_get_contents($path);

if ($conf === false) {
    http_response_code(404);
    die("Fichier non trouvé");
}

preg_match("/ sysname ([\w-]+)/", $conf, $sysname);
preg_match("/ip address ([\d.]+)/", $conf, $address);
$startPtn           = "(?<=[\r\n])";
$NL                     = "(?:[\r\n]+)";
$vlanPvidPtn        = "vlan (?P<pvid>\d+)$NL";
$vlanNamePtn        = "$startPtn name (?P<name>.+)$NL";
$vlanDescriptionPtn = "$startPtn description (?P<description>.+)$NL";
$otherPtn           = "$startPtn .*$NL";
$endPtn             = "(?<!#)";
preg_match_all("/$startPtn$vlanPvidPtn(?:$vlanNamePtn|$vlanDescriptionPtn|$otherPtn)*$endPtn/", $conf, $vlans, PREG_SET_ORDER);
$interfaceAddressPtn = "interface [\w-]+(?P<member>\d+)\/0\/(?P<port>\d+)$NL";
$pvidPtn             = "$startPtn port (?:access|trunk pvid|hybrid pvid) vlan (?P<pvid>\d+)$NL";
$portHybridPtn       = "$startPtn port hybrid vlan (?:(?P<tagged>\d+)(?: (?:to|\d+))* tagged|(?P<untagged>\d+)(?: \d+)* untagged)$NL";
$voiceVlanPtn        = "$startPtn voice-vlan (?P<voice_vlan>\d+) enable$NL";
preg_match_all("/$startPtn$interfaceAddressPtn(?:$pvidPtn|$portHybridPtn|$voiceVlanPtn|$otherPtn)*$endPtn/", $conf, $interfaces, PREG_SET_ORDER);

$stack = array();
foreach ($interfaces as $interface) {
    if ($interface["member"] == 0) continue;
    if (!isset($stack[$interface["member"]])) $stack[$interface["member"]] = [[], []];
    $interface["style"] = "";
    if (!empty($interface["pvid"])) $interface["style"] .= "--pvid: {$interface["pvid"]};";
    if (!empty($interface["tagged"])) $interface["style"] .= " --tagged: {$interface["tagged"]};";
    if (!empty($interface["untagged"])) $interface["style"] .= " --untagged: {$interface["untagged"]};";
    if (!empty($interface["voice_vlan"])) $interface["style"] .= " --voice-vlan: {$interface["voice_vlan"]};";
    $stack[$interface["member"]][1 - $interface["port"] % 2][$interface["port"]] = $interface;
}
?>
<!DOCTYPE HTML>
<html lang='fr'>

<head>
    <title><?= $sysname[1] ?? "Switch sans nom" ?> - Schéma des VLANs</title>
    <link rel="icon" type="image/svg" href="favicon.svg">
    <link href="style.css" rel="stylesheet" />
    <link href="custom.css" rel="stylesheet" />
    <style id="customColors"></style>
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
                        echo "<tr title='{$vlan[0]}'><td class='interface vlan {$vlan["pvid"]}' style='--pvid: {$vlan["pvid"]};'>{$vlan["pvid"]}<input type='color' oninput='changeColor({$vlan["pvid"]}, this.value)' title='Changer la couleur' /></td><td>$name</td><td>$description</td></tr>";
                    }
                }
                ?>
                <tr>
                    <td class='interface trunk'></td>
                    <td colspan='2'>Trunk</td>
                </tr>
                <!--<tr>
                    <td class='interface hybrid' style='--tagged:60; --untagged:0'></td>
                    <td colspan='2'>Hybride (PVID / tagged)</td>
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
        <label for="colorSlider" class="no-print">Changer les couleurs (ou cliquez dans la légende)</label>
        <input id="colorSlider" type="range" min="0" max="360" step="0.000000001" value="58.3"
            oninput="document.documentElement.style.setProperty('--hue', this.value);" class="no-print" />
        <a href="<?= str_replace(__DIR__ . "/", "", $path) ?>" target="_blank" class="link no-print">Télécharger la configuration</a>
        <a href="." class="link no-print">← Retour à la liste</a>
    </footer>
    <script>
        function changeColor(pvid, color) {
            for (let i = customColors.sheet.cssRules.length - 1; i >= 0; i--) {
                if (
                    (customColors.sheet.cssRules[i].selectorText == `[style*="--pvid: ${pvid};"]`) ||
                    (customColors.sheet.cssRules[i].selectorText == `[style*="--tagged: ${pvid};"]`) ||
                    (customColors.sheet.cssRules[i].selectorText == `[style*="--untagged: ${pvid};"]`)
                ) {
                    customColors.sheet.deleteRule(i)
                }
            }
            customColors.sheet.insertRule(`[style*="--pvid: ${pvid};"] { --pvid-color: ${color} }`)
            customColors.sheet.insertRule(`[style*="--tagged: ${pvid};"] { --tagged-color: ${color} }`)
            customColors.sheet.insertRule(`[style*="--untagged: ${pvid};"] { --untagged-color: ${color} }`)
        }
    </script>
</body>

</html>