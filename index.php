<?php
/*
    Sand Table - Web Interface
*/

// Read in configuration variables for remote server and port
$config = null;
$config_filepath = realpath(__DIR__ . '/../../../data/credentials') . '/sand-table-config.json';
if (file_exists($config_filepath)) {
    $config = json_decode(file_get_contents($config_filepath));
}

// Issue "GET" request to device to see if it's operating
if ($_SERVER['REQUEST_METHOD'] == 'GET') {

    if (preg_match('@^/status@i', $_SERVER['REQUEST_URI'])) {

        // Make request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://' . $config->server . ':' . $config->port . '/status');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $response = curl_exec($ch);
        $curl_info = curl_getinfo($ch);
        curl_close($ch);

        // Set response content type
        header('Content-Type: application/json');

        // Return as 'true' if the device is on and false otherwise
        if ($curl_info['http_code'] > 0) {
            print 'true';
        } else {
            print 'false';
        }

        // Exit script
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sand Table</title>
    <meta name="viewport" content="width = device-width, initial-scale = 1, maximum-scale = 1" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    <style type="text/css">

        /* http://www.paulirish.com/2012/box-sizing-border-box-ftw/ */

        html {
            box-sizing: border-box;
        }

        *, *:before, *:after {
            box-sizing: inherit;
        }

        /* --- */

        html{
            background-color: rgb(0,0,0);
            font-family: Helvetica, Arial, Sans-serif;
            color: rgb(255,255,255);
        }

        h1 {
            text-align: center;
        }

        .device-status {
            position: fixed;
            left: 0;
            bottom: 0;
            width: 100%;
            padding: 0.5em;
            text-align: center;
            color: rgba(200,184,142, 0.5);
        }

        div.lights {
            background-color: white;
            color: black;
            width: 320px;
            margin: 0 auto;
            padding: 1em;
        }

        div.lights h2 {
            text-align: center;
            margin-top: 0;
        }

        #color_preview {
            width: 100px;
            height: 100px;
            margin: 0 auto 1em auto;
            border-radius: 100%;
            background-color: #CCC;
            border: 1px solid black;
        }

    </style>
</head>
<body>

    <h1>Sand Table</h1>

    <div class="device-status">Device Status: <span id="status-text">Unknown</span></div>

    <script>
        /**
         * Check if the remote device is active
         * @return Update DOM to reflect status
         */
        function check_device_status(){

            var xhr = new XMLHttpRequest();

            // Without CORS support
            xhr.open('GET', '/status', true);
            xhr.send();

            xhr.onload = function() {
                if (xhr.responseText === "true") { // TODO: Requires verification
                    document.getElementById('status-text').innerHTML = "On";
                    document.getElementById('status-text').style.color = "rgb(0,255,0)";
                } else {
                    document.getElementById('status-text').innerHTML = "Off";
                    document.getElementById('status-text').style.color = "rgb(255,0,0)";
                }
            };
        }
        check_device_status();
    </script>
</body>
</html>