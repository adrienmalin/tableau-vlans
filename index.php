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
        echo "<ul>\n";
        foreach (scandir($path) as $filename) {
            if (substr($filename, 0, 1) != '.') {
                $fullpath = $path ."/". $filename;
                if (is_dir($fullpath)) {
                    echo "<li>\n<details>\n<summary>", $filename, "</summary>\n";
                    recursive_ls($fullpath);
                    echo "</details>\n</li>\n";
                } elseif (substr($filename, -4) == ".cfg") {
                    echo "<li><a href='vlans.php?switch=", str_replace($basedir.'/', "", $fullpath), "'>$filename</a></li>\n";
                }
            }
        }
        echo "</ul>\n";
    }

    recursive_ls($basedir);
?>
</main>
</body>
</html>
