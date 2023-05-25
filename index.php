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
        $strdir = "";
        foreach (scandir($path) as $filename) {
            if (substr($filename, 0, 1) != '.') {
                $fullpath = $path ."/". $filename;
                if (is_dir($fullpath)) {
                    $str = "";
                    $cfgPaths = glob($fullpath . '/*.cfg');
                    if (count($cfgPaths)) {
                        $str .= "<ul>\n";
                        foreach ( as $conf) {
                            $str .= "<li><a href='vlans.php?switch=" . str_replace($basedir.'/', "", $conf) . "'>" . basename($conf) . "</a></li>\n";
                        }
                        $str .= "</ul>\n";
                    }
                    $str = recursive_ls($fullpath) . $str;
                    if ($str != "") $strdir .= "<li>\n<details>\n<summary>" . $filename . "</summary>\n" . $str . "</details>\n</li>\n";;
                }
            }
        }
        if ($strdir != "") $strdir = "<ul>\n" . $strdir . "</ul>\n";
        return $strdir;
    }

    echo recursive_ls($basedir);
?>
</main>
</body>
</html>
