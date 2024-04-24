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

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💬</text></svg>" />
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
      height: 100svh;
      overflow: hidden;
      font-family: sans-serif;
      max-width: 1024px;
      margin: auto;
      background-color: black;
      color: white;
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
      border: 1px solid white;
      background-color: black;
      color: white;
      border-radius: 5px;
      padding: 0.25rem;
      flex: 1;
      font-size: 1rem;
      resize: none;
      font-family: sans-serif;
    }

    button {
      border: 1px solid white;
      background-color: black;
      color: white;
      border-radius: 5px;
      padding: 0.25rem;
      font-size: 1rem;
    }

    button:disabled,
    button[disabled] {
      border: 1px solid darkgray;
      background-color: dimgray;
      color: darkgray;
    }

    #response-container {
      flex: 1;
      padding: 0.5rem;
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      width: 100%;
      overflow-y: scroll;
      border: 1px solid white;
      border-radius: 5px;
    }

    .response {
      border: 1px solid white;
      border-radius: 5px;
      padding: 0.25rem;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
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
  </style>
  <title>chat.giving</title>
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
        <h1>chat.giving</h1>
      </li>
      <li>
        <button disabled>
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
    <form>
      <textarea name="message" id="input" placeholder="Type a message"></textarea>
      <button type="submit" id="submit">
        <span>Send</span>
      </button>
    </form>
  </main>
</body>

</html>