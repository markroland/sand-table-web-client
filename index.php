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

    <div class="lights">

        <h2>Lights</h2>

        <div id="color_preview"></div>

        <form name="form_color" method="POST" action="/color">
            <label>Red <input name="red" type="range" min="0" max="255" value="128" class="slider" id="red_slider"></label><span id="red_value">128</span>
            <br />
            <label>Green <input name="green" type="range" min="0" max="255" value="128" class="slider" id="green_slider"></label><span id="green_value">128</span>
            <br />
            <label>Blue <input name="blue" type="range" min="0" max="255" value="128" class="slider" id="blue_slider"></label><span id="blue_value">128</span>
            <br />
            <label>Intensity <input name="intensity" type="range" min="0.0" max="1.0" step="0.1" value="0.5" class="slider" id="intensity_slider"></label><span id="intensity_value">0.5</span>
            <br />
            <input type="submit" value="Submit">
        </form>

        <br />

        <form name="form_color" method="POST" action="/status">
            <input type="submit" value="On">
            <input type="submit" value="Off">
        </form>

    </div>

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

        // Add event listener to change color when Red slider is changed
        document.getElementById('red_slider').addEventListener('input', function () {
            document.getElementById('red_value').innerHTML = this.value;
            document.getElementById("color_preview").style.backgroundColor = "rgb("
                + document.getElementById('red_slider').value + ","
                + document.getElementById('green_slider').value + ","
                + document.getElementById('blue_slider').value
                + ")";
        }, false);

        // Add event listener to change color when Green slider is changed
        document.getElementById('green_slider').addEventListener('input', function () {
            document.getElementById('green_value').innerHTML = this.value;
            document.getElementById("color_preview").style.backgroundColor = "rgb("
                + document.getElementById('red_slider').value + ","
                + document.getElementById('green_slider').value + ","
                + document.getElementById('blue_slider').value
                + ")";
        }, false);

        // Add event listener to change color when Blue slider is changed
        document.getElementById('blue_slider').addEventListener('input', function () {
            document.getElementById('blue_value').innerHTML = this.value;
            document.getElementById("color_preview").style.backgroundColor = "rgb("
                + document.getElementById('red_slider').value + ","
                + document.getElementById('green_slider').value + ","
                + document.getElementById('blue_slider').value
                + ")";
        }, false);

        // Add event listener to change color when Intensity slider is changed
        document.getElementById('intensity_slider').addEventListener('input', function () {
            document.getElementById('intensity_value').innerHTML = this.value;
            document.getElementById("color_preview").style.opacity = this.value;
        }, false);

        // Send request
        document.querySelector('form[name="form_color"]').addEventListener("submit", function(e){
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