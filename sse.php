<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$data = json_decode(file_get_contents('php://input'));

$message = $data->message;

$api_key = $_ENV['OPENAI_API_KEY'];
$api_endpoint = "https://api.openai.com/v1/chat/completions";

$headers = [
  "Content-Type: application/json",
  "Authorization: Bearer $api_key"
];

$payload = json_encode([
  "model" => "gpt-4",
  "messages" => [
    [
      "role" => "system",
      "content" => "You are a helpful assistant."
    ],
    [
      "role" => "user",
      "content" => $message
    ]
  ],
  "stream" => true
]);

$ch = curl_init($api_endpoint);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');

curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($curl, $data) {
  $dataArray = explode("\n\n", $data);

  foreach ($dataArray as $chunk) {
    if ($chunk) {
      try {
        $jsonData = json_decode(substr($chunk, 6), true);
        if ($jsonData && isset($jsonData['choices'][0]['delta']['content'])) {
          $content = $jsonData['choices'][0]['delta']['content'];
          echo $content;
          ob_flush();
          flush();
        }
        if (isset($jsonData['choices'][0]['finish_reason']) && $jsonData['choices'][0]['finish_reason'] == "stop") {
          break;
        }
      } catch (Exception $e) {
        echo "data: Failed to process chunk\n\n";
        ob_flush();
        flush();
      }
    }
  }

  return strlen($data);
});

$response = curl_exec($ch);
if (curl_errno($ch)) {
  echo 'Curl error: ' . curl_error($ch);
}

curl_close($ch);

die();
