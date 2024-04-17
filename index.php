<?php

session_start();

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$validUser = $_ENV['USERNAME'];
$validPassword = $_ENV['PASSWORD'];

if (
  !isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) ||
  $_SERVER['PHP_AUTH_USER'] !== $validUser || $_SERVER['PHP_AUTH_PW'] !== $validPassword
) {
  header('WWW-Authenticate: Basic realm="My Protected Area"');
  header('HTTP/1.0 401 Unauthorized');
  exit;
}

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
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://unpkg.com/htmx.org@1.9.11"></script>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 1rem;
      width: 100%;
      padding: 2rem;
      height: calc(100vh - env(safe-area-inset-bottom));
      overflow: hidden;
      font-family: sans-serif;
      max-width: 1024px;
      margin: auto;
    }

    header {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 1rem;
      width: 100%;
    }

    header menu {
      width: 100%;
      display: flex;
      justify-content: space-between;
      align-items: center;
      list-style: none;
    }

    header menu button {
      border: 1px solid black;
      border-radius: 5px;
      padding: 0.25rem;
      font-size: 1rem;
    }

    header h1 {
      font-family: monospace;
    }

    main {
      flex: 1;
      gap: 1rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      overflow: auto;
      width: 100%;
    }

    form {
      display: flex;
      gap: 0.25rem;
    }

    form #input {
      border: 1px solid black;
      border-radius: 5px;
      padding: 0.25rem;
      flex: 1;
      font-size: 1rem;
      resize: none;
      font-family: sans-serif;
    }

    form #submit {
      border: 1px solid black;
      border-radius: 5px;
      padding: 0.25rem;
      font-size: 1rem;
    }

    #response-container {
      flex: 1;
      padding: 0.5rem;
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      width: 100%;
      overflow-y: scroll;
      border: 1px solid black;
      border-radius: 5px;
    }

    .response {
      border: 1px solid black;
      border-radius: 5px;
      padding: 0.25rem;
      display: flex;
      gap: 0.5rem;
    }

    .role {
      display: flex;
      justify-content: center;
      align-items: flex-start;
      font-family: monospace;
      font-size: 1rem;
    }

    .message {
      word-wrap: break-word;
      flex: 1;
    }

    #spinner {
      display: none;
    }

    #spinner.htmx-request {
      display: block;
    }
  </style>
  <title>AI CHAT</title>
</head>

<body>
  <header>
    <menu>
      <li>
        <button disabled>
          <span>History</span>
        </button>
      </li>
      <li>
        <h1>AI CHAT</h1>
      </li>
      <li>
        <button hx-on:click="document.getElementById('response-container').innerHTML = ''" hx-delete="index.php" hx-swap="none">
          <span>New</span>
        </button>
      </li>
    </menu>
  </header>
  <main>
    <section id="response-container">
      <?php
      if (isset($_SESSION['chat_history'])) {
        foreach ($_SESSION['chat_history'] as $chatMessage) {
          $escapedRole = nl2br(htmlspecialchars(strtoupper($chatMessage['role']), ENT_QUOTES, 'UTF-8'));
          $escapedContent = nl2br(htmlspecialchars($chatMessage['content'], ENT_QUOTES, 'UTF-8'));
          echo "<div class='response'><div class='role'><strong>" . $escapedRole . "</strong></div><div class='message'>" . $escapedContent . "</div></div>";
        }
      }
      ?>
    </section>
    <form hx-post="index.php" hx-target="#response-container" hx-swap="innerHTML" hx-on::before-request="const input = document.getElementById('input');console.log(input.value); const submit = document.getElementById('submit'); input.disabled = true; input.value = ''; input.placeholder = 'Loading...'; submit.disabled = true;" hx-on::after-request="const input = document.getElementById('input'); const submit = document.getElementById('submit'); input.disabled = false; input.placeholder = 'Type a message'; submit.disabled = false; input.focus();">
      <textarea name="message" id="input" placeholder="Type a message"></textarea>
      <button type="submit" id="submit">
        <span>Send</span>
        </div>
    </form>
  </main>
</body>

</html>