<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $obj = json_decode(file_get_contents('php://input'));

  $api_key = $_ENV['OPENAI_API_KEY'];
  $api_endpoint = "https://api.openai.com/v1/chat/completions";

  $headers = [
    "Content-Type: application/json",
    "Authorization: Bearer $api_key"
  ];

  $payload = json_encode([
    "model" => "gpt-4",
    "messages" => $obj->messages,
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
            echo nl2br($content);
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
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💬</text></svg>" />
  <title>chat.giving</title>
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
  <script defer>
    function updateMessages(messages) {
      messages.forEach((message) => {
        const responseDiv = document.createElement("div");
        const roleDiv = document.createElement("div");
        const roleStrong = document.createElement("strong");
        const messageDiv = document.createElement("div");

        document
          .querySelector("#response-container")
          .appendChild(responseDiv);
        responseDiv.appendChild(roleDiv);
        responseDiv.appendChild(messageDiv);
        roleDiv.appendChild(roleStrong);

        responseDiv.classList.add("response");
        roleDiv.classList.add("role");
        messageDiv.classList.add("message");

        roleDiv.textContent = message.role;
        messageDiv.innerHTML = message.content;
      });
    }

    document.addEventListener("DOMContentLoaded", () => {
      let messages = JSON.parse(localStorage.getItem("messages")) || [{
        role: "system",
        content: "You are a helpful assistant.",
      }, ];
      localStorage.setItem("messages", JSON.stringify(messages));

      updateMessages(messages);

      const form = document.querySelector("form");
      const newButton = document.querySelector("#newButton");

      form.addEventListener("submit", async (event) => {
        event.preventDefault();
        if (!event.target.message.value) return;

        const responseContainer = document.querySelector(
          "#response-container"
        );

        messages.push({
          role: "user",
          content: event.target.message.value,
        });
        localStorage.setItem("messages", JSON.stringify(messages));

        updateMessages(messages);

        const response = await fetch("index.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            messages: messages,
          }),
        });

        event.target.message.value = "";

        const reader = response.body.getReader();
        const decoder = new TextDecoder();

        const responseDiv = document.createElement("div");
        const roleDiv = document.createElement("div");
        const roleStrong = document.createElement("strong");
        const messageDiv = document.createElement("div");

        responseContainer.appendChild(responseDiv);
        responseDiv.appendChild(roleDiv);
        responseDiv.appendChild(messageDiv);
        roleDiv.appendChild(roleStrong);

        responseDiv.classList.add("response");
        roleDiv.classList.add("role");
        messageDiv.classList.add("message");
        roleDiv.textContent = "assistant";

        while (true) {
          const {
            done,
            value
          } = await reader.read();
          if (done) {
            console.log("Stream complete");
            break;
          }
          const text = decoder.decode(value);
          console.log("text", text);
          messageDiv.innerHTML += text;

          responseContainer.scrollTop = responseContainer.scrollHeight;
        }
        messages.push({
          role: "assistant",
          content: messageDiv.innerHTML,
        });
        localStorage.setItem("messages", JSON.stringify(messages));
      });

      newButton.addEventListener("click", () => {
        document.querySelector("#response-container").innerHTML = "";
        localStorage.removeItem("messages");
        const messages = JSON.parse(localStorage.getItem("messages")) || [{
          role: "system",
          content: "You are a helpful assistant.",
        }, ];
        localStorage.setItem("messages", JSON.stringify(messages));

        updateMessages(messages);
      });
    });
  </script>
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
        <button id="newButton">
          <span>New</span>
        </button>
      </li>
    </menu>
  </header>
  <main>
    <section id="response-container"></section>
    <form>
      <textarea name="message" id="input" placeholder="Type a message"></textarea>
      <button type="submit" id="submit">
        <span>Send</span>
      </button>
    </form>
  </main>
</body>

</html>