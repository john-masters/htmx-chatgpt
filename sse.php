<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['message'])) {
    $message = $_POST['message'];

    if (!isset($_SESSION['chat_history'])) {
      $_SESSION['chat_history'] = [
        [
          "role" => "system",
          "content" => "You are a helpful assistant."
        ]
      ];
    }

    $_SESSION['chat_history'][] = ["role" => "user", "content" => $message];


    $api_key = $_ENV['OPENAI_API_KEY'];
    $api_endpoint = "https://api.openai.com/v1/chat/completions";

    $headers = [
      "Content-Type: application/json",
      "Authorization: Bearer $api_key"
    ];

    $payload = json_encode([
      "model" => "gpt-4",
      "messages" => $_SESSION['chat_history']
    ]);

    $ch = curl_init($api_endpoint);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
      $responseData = json_decode($response, true);
      $aiMessage = $responseData['choices'][0]['message']['content'];
      $aiRole = $responseData['choices'][0]['message']['role'];

      $_SESSION['chat_history'][] = ["role" => $aiRole, "content" => $aiMessage];

      foreach ($_SESSION['chat_history'] as $chatMessage) {
        $escapedRole = nl2br(htmlspecialchars(strtoupper($chatMessage['role']), ENT_QUOTES, 'UTF-8'));
        $escapedContent = nl2br(htmlspecialchars($chatMessage['content'], ENT_QUOTES, 'UTF-8'));
        echo "<div class='response'><div class='role'><strong>" . $escapedRole . "</strong></div><div class='message'>" . $escapedContent . "</div></div>";
      }
    } else {
      echo "<div>Error retrieving response from API.</div>";
    }
    die();
  }
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
  session_destroy();
}
