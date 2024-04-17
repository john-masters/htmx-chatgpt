<?php
session_start();

require __DIR__ . '/vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['message'])) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

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

      $_SESSION['chat_history'][] = ["role" => "system", "content" => $aiMessage];

      foreach ($_SESSION['chat_history'] as $chatMessage) {
        echo "<div class='response'><div class='role'><strong>" . strtoupper($chatMessage['role']) . "</strong></div><div class='message'>" . $chatMessage['content'] . "</div></div>";
      }
    } else {
      echo "<div>Error retrieving response from API.</div>";
    }
    die();
  }
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
      height: 100vh;
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
      /* justify-content: flex-end; */
      gap: 0.5rem;
      width: 100%;
      overflow: scroll;
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
        <button hx-on:click="document.getElementById('response-container').innerHTML = ''">
          <span>New</span>
        </button>
      </li>
    </menu>
  </header>
  <main>
    <section id="response-container">
    </section>
    <form hx-post="index.php" hx-target="#response-container" hx-swap="innerHTML" hx-on::before-request="const input = document.getElementById('input'); const submit = document.getElementById('submit'); input.disabled = true; input.value = ''; input.placeholder = 'Loading...'; submit.disabled = true;" hx-on::after-request="const input = document.getElementById('input'); const submit = document.getElementById('submit'); input.disabled = false; input.placeholder = 'Type a message'; submit.disabled = false; input.focus();">
      <input type="text" name="message" id="input" placeholder="Type a message">
      <button type="submit" id="submit">
        <span>Send</span>
        </div>
    </form>
  </main>
</body>

</html>