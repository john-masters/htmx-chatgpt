<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['message'])) {
    $message = $_POST['message'];
    echo "<div class='response'><div>user</div><div>" . $message . "</div></div>";
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

    main {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      overflow: auto;
      width: 100%;
    }

    form {
      align-self: center;
    }

    #response-container {
      display: flex;
      flex-direction: column;
      gap: 1rem;
      padding: 1rem;
      width: 100%;
      min-height: 0;
      overflow: scroll;
    }

    .response {
      display: flex;
      gap: 1rem;
    }
  </style>
  <title>ai chat</title>
</head>

<body>
  <header>
    <menu>
      <li>
        <button>
          <span>History</span>
        </button>
      </li>
      <li>
        <h1>ai chat</h1>
      </li>
      <li>
        <button>
          <span>New chat</span>
        </button>
      </li>
    </menu>
  </header>
  <main>
    <section id="response-container">
    </section>
    <form hx-post=" index.php" hx-target="#response-container" hx-swap="beforeend">
      <input type="text" name="message" id="message" placeholder="Type a message">
      <button type="submit">
        <span>Send</span>
      </button>
    </form>
  </main>
</body>

</html>