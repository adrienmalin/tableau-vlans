<!DOCTYPE HTML>
<html lang='fr'>

<head>
    <title>Schémas des VLANs</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="icon" type="image/svg" href="favicon.svg">
</head>

<body>
    <h1>Schémas des VLANs</h1>
    <div class="file-list">
        <ul>
            <?php
            $basedir = __DIR__ . "/confs";

            function recursive_ls($path)
            {
                global $basedir;

                if (substr(basename($path), 0, 1) == '.') {
                    return "";
                }

                if (is_dir($path)) {
                    $str = "";
                    foreach (scandir($path) as $filename) {
                        $str .= recursive_ls("$path/$filename");
                    }
                    if ($str == "") {
                        return "";
                    } else {
                        if ($path == $basedir) {
                            return "<ul>\n$str\n</ul>\n";
                        } else {
                            return "<li>\n<details>\n<summary>" . basename($path) . "</summary>\n<ul>\n" . $str . "</ul>\n</details>\n</li>\n";
                        }
                    }
                }

                if (substr($path, -4) == ".cfg") {
                    return "<li><a href='show.php?switch=" . str_replace("$basedir/", "", $path) . "' target='_blank'>" . basename($path) . "</a></li>\n";
                }

                return "";
            }

            echo recursive_ls($basedir);
            ?>
        </ul>
    </div>
</body>

</html>
