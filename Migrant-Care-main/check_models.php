<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Available Google AI Models</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; padding: 20px; }
        .model { border: 1px solid #ccc; border-radius: 8px; padding: 15px; margin-bottom: 15px; background-color: #f9f9f9; }
        .model h3 { margin-top: 0; color: #333; }
        .model code { background-color: #eee; padding: 3px 6px; border-radius: 4px; }
        .error { color: red; font-weight: bold; }
        .supported { color: green; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Available Google AI Models</h1>
<?php
// Your API key
$apiKey = "AIzaSyDiwHoQKqZRaMBG2C5rKhEZkF9A_gwvGEo";

// The API endpoint to list models
$apiUrl = "https://generativelanguage.googleapis.com/v1/models?key=" . $apiKey;

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "<div class='error'>Error connecting to the API. HTTP Code: {$httpCode}</div>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
} else {
    $result = json_decode($response, true);
    if (isset($result['models'])) {
        foreach ($result['models'] as $model) {
            echo "<div class='model'>";
            echo "<h3>Model Name: <code>" . htmlspecialchars($model['name']) . "</code></h3>";
            echo "<strong>Display Name:</strong> " . htmlspecialchars($model['displayName']) . "<br>";
            
            // Check if 'generateContent' is a supported method
            $is_supported = false;
            foreach ($model['supportedGenerationMethods'] as $method) {
                if (strtolower($method) === 'generatecontent') {
                    $is_supported = true;
                    break;
                }
            }
            
            if ($is_supported) {
                echo "<strong class='supported'>✅ Supports 'generateContent' method. This is a good candidate!</strong>";
            } else {
                echo "<strong class='error'>❌ Does NOT support 'generateContent'.</strong>";
            }
            echo "</div>";
        }
    } else {
        echo "<div class='error'>Could not find a list of models in the API response.</div>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    }
}
?>
</body>
</html>