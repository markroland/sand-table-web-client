<?php
/*
    Sand Table - Web Interface
*/

// Read in configuration variables for remote server and port
$config = null;
$config_filepath = realpath(__DIR__ . '/../') . '/sand-table-config.json';
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (preg_match('@^/color@i', $_SERVER['REQUEST_URI'])) {

        $postdata = http_build_query($_POST);

        // Make request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://' . $config->server . ':' . $config->port . '/color');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        $response = curl_exec($ch);
        curl_close($ch);

        // Display response
        print($response);
    }

    // Exit script
    exit;
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
    <link rel="apple-touch-icon" href="apple-touch-icon.png" />
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
            /*position: fixed;*/
            left: 0;
            bottom: 0;
            width: 100%;
            padding: 0.5em;
            text-align: center;
            color: rgba(200,184,142, 0.5);
            background-color: rgba(0,0,0, 0.5);
        }

        div.lights {
            background-color: white;
            color: black;
            width: 320px;
            margin: 0 auto 1em auto;
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

        #picker {
            width: 240px;
            margin: 0 auto 1em auto;
        }

        div.patterns {
            background-color: white;
            color: black;
            width: 720px;
            margin: 0 auto 1em auto;
            padding: 1em;
        }

        .pattern-cards {
            display: grid;
            grid-template-columns: 220px 220px 220px;
            grid-gap: 10px;
            color: #444;
            margin: 0 auto;
            text-align: center;
        }

        .pattern-card {
            background-color: rgb(70, 70, 70);
            color: #fff;
            border-radius: 5px;
            padding: 5px;
            font-size: 150%;
        }

        .pattern-card img {
            max-width: 200px;
        }

        .pattern-card p {
            font-size: 0.5em;
            margin: 0;
        }

    </style>
    <script src="https://cdn.jsdelivr.net/npm/@jaames/iro@5"></script>
</head>
<body>

    <h1>Sand Table</h1>

    <div class="lights">

        <h2>Light</h2>

        <div id="picker"></div>
        <script>
            var colorPicker = new iro.ColorPicker('#picker', {
              width: 240,
              color: "#FFFFFF"
            });

            colorPicker.on('color:change', function(color) {
              document.querySelector("#red").value = color.rgb.r
              document.querySelector("#green").value = color.rgb.g
              document.querySelector("#blue").value = color.rgb.b
              document.querySelector("#intensity").value = color.value / 100
            });
        </script>

        <form name="solid" method="POST" action="/color">
            <input type="hidden" name="red" id="red" value="255" />
            <input type="hidden" name="green" id="green" value="255" />
            <input type="hidden" name="blue" id="blue" value="255" />
            <input type="hidden" name="intensity" id="intensity" value="1.0" />
            <input type="submit" value="Submit" />
        </form>

        <form name="sequence" method="POST" action="/sequence">
            <select name="sequence">
                <option value="breathe">Breathe</option>
                <option value="random">Random</option>
            </select>
            <br />
            <input type="hidden" name="intensity" id="intensity" value="1.0" />
            <input type="submit" value="Submit" />
        </form>

        <!--
        <form name="status" method="POST" action="/status">
            <input type="submit" value="On">
            <input type="submit" value="Off">
        </form>
        -->

    </div>

    <div class="patterns">
        <h2>Pattern</h2>
        <div class="pattern-cards">
            <?php
            foreach (glob(realpath(__DIR__ . "/assets/images/patterns") . "/*.png") as $filename) {
                $basename = basename($filename);
                echo '<div class="pattern-card">
                <a href="assets/images/patterns/' . $basename . '"><img src="assets/images/patterns/' . $basename . '" alt="' . $basename . '" target="_blank" rel="noopener noreferrer" /></a>
                <p>' . $basename . '</p>
            </div>';
            }
            ?>
        </div>
    </div>

    <div class="device-status">
        <div>Device Status: <span id="status-text">Unknown</span></div>
        <div>© Mark Roland, <?php print(date('Y')); ?>. <a href="https://markroland.com/portfolio/sand-table" target="_blank" rel="noopener noreferrer">Project Documentation</a>. <a href="https://iro.js.org" target="_blank" rel="noopener noreferrer">Uses iro.js</a></div>
    </div>

    <script>
        /**
         * Check if the remote device is active
         * @return Update DOM to reflect status
         */
        function check_device_status(){

            var xhr = new XMLHttpRequest();

            // Without CORS support
            xhr.open('GET', '<?php print('http://' . $config->server . ':' . $config->port . '/status'); ?>', true);
            xhr.send();

            xhr.onload = function() {
                if (xhr.status == 200) {
                // if (xhr.responseText === "true") { // TODO: Requires verification
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

    <script>

        /*!
         * Serialize all form data into a query string
         * (c) 2018 Chris Ferdinandi, MIT License, https://gomakethings.com
         *
         * https://vanillajstoolkit.com/helpers/serialize/
         *
         * @param  {Node}   form The form to serialize
         * @return {String}      The serialized form data
         */
        var serialize = function (form) {

            // Setup our serialized data
            var serialized = [];

            // Loop through each field in the form
            for (var i = 0; i < form.elements.length; i++) {

                var field = form.elements[i];

                // Don't serialize fields without a name, submits, buttons, file and reset inputs, and disabled fields
                if (!field.name || field.disabled || field.type === 'file' || field.type === 'reset' || field.type === 'submit' || field.type === 'button') continue;

                // If a multi-select, get all selections
                if (field.type === 'select-multiple') {
                    for (var n = 0; n < field.options.length; n++) {
                        if (!field.options[n].selected) continue;
                        serialized.push(encodeURIComponent(field.name) + "=" + encodeURIComponent(field.options[n].value));
                    }
                }

                // Convert field data to a query string
                else if ((field.type !== 'checkbox' && field.type !== 'radio') || field.checked) {
                    serialized.push(encodeURIComponent(field.name) + "=" + encodeURIComponent(field.value));
                }
            }

            return serialized.join('&');

        };

        // Send request
        document.querySelector('form[name="solid"]').addEventListener("submit", function(e){
            e.preventDefault();

            var xhr = new XMLHttpRequest();

            xhr.open(this.getAttribute("method"), this.getAttribute("action"));

            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            // xhr.onload = function() {
            //     if (xhr.status === 200) {
            //         console.log(xhr.responseText);
            //     }
            // };
            xhr.onreadystatechange = function() {
                if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
                    console.log(xhr.responseText);
                }
            }

            xhr.send(serialize(this));
        });

        // Send request
        document.querySelector('form[name="sequence"]').addEventListener("submit", function(e){
            e.preventDefault();

            var xhr = new XMLHttpRequest();

            xhr.open(this.getAttribute("method"), this.getAttribute("action"));

            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            // xhr.onload = function() {
            //     if (xhr.status === 200) {
            //         console.log(xhr.responseText);
            //     }
            // };
            xhr.onreadystatechange = function() {
                if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
                    console.log(xhr.responseText);
                }
            }

            xhr.send(serialize(this));
        });

    </script>

</body>
</html>