<!DOCTYPE HTML>
<html lang='fr'>
<head>
<title>Tableau des VLANs</title>
<style>
ul {
    list-style: none;
}
</style>
</head>
<body>
<header>
<h1>Tableau des VLANs</h1>
</header>
<main>
<?php
$basedir = __DIR__ . "/confs";

function recursive_ls($path) {
    global $basedir;
    foreach (scandir($path) as $filename) {
        if (substr($filename, 0, 1) != '.') {
            $fullpath = $path ."/". $filename;
            if (is_dir($fullpath)) {
                $confs = $files = glob($fullpath . '/*.cfg');
                if (count($confs)) {
                    echo "<li>\n<details>\n<summary>", str_replace($basedir.'/', "", $fullpath), "</summary>\n<ul>\n";
                    foreach ($confs as $conf) {
                        echo "<li><a href='vlans.php?switch=", str_replace($basedir.'/', "", $conf), "'>" . basename($conf) . "</a></li>\n";
                    }
                    echo "</ul>\n</details>\n</li>\n";
                }
                recursive_ls($fullpath);
            }
        }
    }
}

echo "<ul>\n";
recursive_ls($basedir);
echo "</ul>\n";
?>
</main>
</body>
</html>
