<?php
//debug stuff
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

// Database configuration
$db_host = 'DATABASEHOST'; // Database host (e.g. 127.0.0.1)
$db_port = DATABASEPORT; // Database port
$db_name = 'SCHEMA_NAME'; // Database schema name to store table
$db_user = 'USERNAME'; // Database username
$db_pass = 'PASSWORD'; // Database password
$db_table = 'TABLE';  // This should be your table where you store the user's info

// RPC configuration
$rpc_url = 'URL';  // RPC URL with port (e.g. http://127.0.0.1:33700/)
$rpc_user = 'USERNAME';  // RPC username
$rpc_pass = 'PASSWORD';  // RPC password

// CAPTCHA V2 configuration
$secretKey = 'SECRET_KEY'; // CAPTCHA V2 secret key
$siteKey = "SITEKEY"; //CAPTCHA V2 site key

// URL Shortener configuration
$api_token = 'API_TOKEN'; //API token to birdURLs / any other shortener
$site_location = 'SITE_LOCATION'; //The webpage loction in the server (e.g. https://example.com/faucet/ or https://example.com/faucet/coin.php)

//Do not change
$processed = false;
$message = '';
$custom_amount = 0;

// Create a new database connection
$db = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($db->connect_error) {
    die('Database connection failed: ' . $db->connect_error);
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the crypto address from the form
    $crypto_address = $_POST['crypto_address'];

    // Verify the CAPTCHA response
    $recaptchaResponse = $_POST['g-recaptcha-response'];
    
    // Try to detect user's ip address
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ip = $_SERVER['REMOTE_ADDR'];
	}


    // Check if the user has verified CAPTCHA within the last 24 hours
    $stmt = $db->prepare("SELECT timestamp FROM {$db_table} WHERE ip = ? AND timestamp > NOW() - INTERVAL 24 HOUR"); //Change this if the user can use the faucet more than once every 24 hrs
    $stmt->bind_param('s', $ip);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $message = 'You have already verified CAPTCHA within the last 24 hours.';
        $processed = true;
    }

    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = array(
        'secret' => $secretKey,
        'response' => $recaptchaResponse,
        'remoteip' => $ip
    );

    $options = array(
        'http' => array(
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        )
    );

    $context = stream_context_create($options);
    $verify = file_get_contents($url, false, $context);
    $response = json_decode($verify);

    if ($response->success) {
    // CAPTCHA verification succeeded

    // Create a unique identifier
    $unique_id = uniqid();

    // Get the user's country information
    $ip_info = file_get_contents("http://ip-api.com/json/{$ip}");
    $ip_info = json_decode($ip_info, true);
    $country = $ip_info['country'];

    //Generate a shortened URL for redirection
    $long_url = urlencode("{$site_location}?id={$unique_id}");
    $api_url = "https://birdurls.com/api?api={$api_token}&url={$long_url}&format=text"; // Can change to pretty much any other shortlink service, they all have the same api.
    $result = @file_get_contents($api_url);
    // To skip the shortlink comment out above and uncomment below ($site_location is still required so the button to submit functions)
    //$result = '{$site_location}?id={$unique_id}';
    if($result) {
        //Log the IP, the current timestamp, unique id and the crypto address
        $stmt = $db->prepare("INSERT INTO {$db_table} (ip, timestamp, unique_id, crypto_address, country) VALUES (?, NOW(), ?, ?, ?)");
        $stmt->bind_param('ssss', $ip, $unique_id, $crypto_address, $country);
        $stmt->execute();

        header('Location: ' . $result);
        exit();
    } else {
        $message = 'Error: Could not generate shortened URL';
		$processed = true;
    }
} else {
    // CAPTCHA verification failed
    $message = 'CAPTCHA verification failed. Please try again.';
    $processed = true;
}

}
else {
    if(isset($_GET['id'])) {
    $unique_id = $_GET['id'];
    // Check if the unique id is valid
    $stmt = $db->prepare("SELECT * FROM {$db_table} WHERE unique_id = ? AND used = 0");
    $stmt->bind_param('s', $unique_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $crypto_address = $row['crypto_address'];
        $country = $row['country'];  // Get the country

        // Determine the payment amount based on the country. FORMAT: CPM / 1000 / pricePerCoin Add more cases as needed
        switch ($country) {
    case 'Monaco':
        $custom_amount = 15.00 / 1000 / 0.004;  // Amount for Monaco
        break;
    case 'Faroe Islands':
        $custom_amount = 14.00 / 1000 / 0.004;  // Amount for Faroe Islands
        break;
    case 'Liechtenstein':
        $custom_amount = 14.00 / 1000 / 0.004;  // Amount for Liechtenstein
        break;
    case 'United States':
        $custom_amount = 12.00 / 1000 / 0.004;  // Amount for United States
        break;
    case 'United Kingdom':
        $custom_amount = 10.00 / 1000 / 0.004;  // Amount for United Kingdom
        break;
    case 'Canada':
        $custom_amount = 9.00 / 1000 / 0.004;  // Amount for Canada
        break;
    case 'Australia':
        $custom_amount = 8.00 / 1000 / 0.004;  // Amount for Australia
        break;
    case 'Switzerland':
        $custom_amount = 7.00 / 1000 / 0.004;  // Amount for Switzerland
        break;
    case 'Sweden':
        $custom_amount = 7.00 / 1000 / 0.004;  // Amount for Sweden
        break;
    case 'Finland':
        $custom_amount = 7.00 / 1000 / 0.004;  // Amount for Finland
        break;
    case 'Norway':
        $custom_amount = 7.00 / 1000 / 0.004;  // Amount for Norway
        break;
    case 'New Zealand':
        $custom_amount = 7.00 / 1000 / 0.004;  // Amount for New Zealand
        break;
    case 'Germany':
        $custom_amount = 6.00 / 1000 / 0.004;  // Amount for Germany
        break;
    case 'France':
        $custom_amount = 6.00 / 1000 / 0.004;  // Amount for France
        break;
    case 'Netherlands':
        $custom_amount = 6.00 / 1000 / 0.004;  // Amount for Netherlands
        break;
    case 'Austria':
        $custom_amount = 6.00 / 1000 / 0.004;  // Amount for Austria
        break;
    case 'Italy':
        $custom_amount = 6.00 / 1000 / 0.004;  // Amount for Italy
        break;
    case 'Spain':
        $custom_amount = 6.00 / 1000 / 0.004;  // Amount for Spain
        break;
    case 'Denmark':
        $custom_amount = 6.00 / 1000 / 0.004;  // Amount for Denmark
        break;
    case 'Singapore':
        $custom_amount = 5.00 / 1000 / 0.004; // Amount for Singapore
        break;
	case 'Russia':
        $custom_amount = 0; // Amount for Russia
        break;
    default:
        $custom_amount = 3.50 / 1000 / 0.004;  // Default amount
        break;
}



        // Set up the request data
        $post_data = array(
            'jsonrpc' => '1.0', 
            'id' => 'curltest',
            'method' => 'sendtoaddress',
            'params' => array($crypto_address, $custom_amount, 'donation', 'user')
        );

        // Set up cURL
        $ch = curl_init($rpc_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $rpc_user . ':' . $rpc_pass);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));

        // Send the request
        $result = curl_exec($ch);
        
        // Check the response
        if ($result === false) {
            $message = 'Error: Could not send Katkoyn. ' . curl_error($ch);
			$processed = true;
        } else {
            $response = json_decode($result);

            if (isset($response->error) && $response->error != null) {
                $message = 'Error: ' . $response->error->message;
				$processed = true;
            } else {
                $message = 'CAPTCHA verification succeeded. Katkoyns were sent.';
				$processed = true;

                // Mark the unique id as used
                $stmt = $db->prepare("UPDATE {$db_table} SET used = 1 WHERE unique_id = ?");
                $stmt->bind_param('s', $unique_id);
                $stmt->execute();
            }
        }

        // Close cURL
        curl_close($ch);
    } else {
        $message = 'Invalid URL or URL has already been used.';
		$processed = true;
    }
}

}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Katkoyn Faucet - Claim Every 24 Hours!</title>
	<link rel="shortcut icon" type="image/x-icon" href="logo.png">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        body {
			background-color: #333;
			color: #fff;
			font-family: Arial, sans-serif;
			display: flex;
			flex-direction: column;
			justify-content: center;
			align-items: center;
			height: 100vh;
			margin: 0;
			padding: 0;
		}
        form {
            background-color: #444;
            padding: 2em;
            border-radius: 10px;
            text-align: center;
        }
        input[type="text"] {
            border: none;
            padding: 10px;
            border-radius: 5px;
            width: 300px;
        }
        button {
            background-color: #f8a500;
            border: none;
            color: white;
            padding: 15px 32px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 10px 2px;
            cursor: pointer;
            border-radius: 5px;
        }
		.message {
            background-color: #444;
            padding: 2em;
            border-radius: 10px;
            text-align: center;
            width: 300px;
        }
		.footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            height: 2.5rem;
            background-color: #444;
            text-align: center;
            padding-top: 10px;
        }
        .footer p {
            color: #fff;
            font-size: 14px;
        }
		.ads {
        margin-top: 20px; 
        margin-bottom: 20px;
		}
        #logo {
        display: block;
        max-width: 200px; 
        height: auto; 
        margin: 0 auto;
    }
    </style>
</head>
<body>
	<?php if (!$processed): ?>
    <form method="POST" action="">
        <img src="logo.png" id="logo">
        <h1>Katkoyn Faucet</h1>
        <p>Claim 0.875 - 3.75 KAT Every 24 Hours!</p>
        <input type="text" name="crypto_address" placeholder="Enter your Katkoyn address">
        <div class="g-recaptcha" data-sitekey="<?php echo $siteKey; ?>"></div>
        <button type="submit">Submit</button>
    </form>
	<?php else: ?>
        <div class="message"><?php echo $message; ?></div>
	<?php endif; ?>
	<div class="ads">
        <!-- space for an AD -->
    </div>
	<div class="footer">
        <p>© 2023 Enspiredjack.com | All Rights Reserved | Version 1.1 | BirdURLs Edition</p>
    </div>
</body>
<!-- Made by Enspiredjack -->
</html>







