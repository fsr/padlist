<?php
session_start();

// error_reporting(E_ALL);
// ini_set('display_errors', 1);

$provider = require 'providerConfig.php';

// Check if user is not logged in
if (!isset($_SESSION['user'])) {
    // User is not logged in, initiate the OAuth login process
    $authorizationUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();

    header('Location: ' . $authorizationUrl);
    exit;
}

$shortName = explode(" ", $_SESSION['user'])[0];

$host = '/run/postgresql';
$dbname = 'hedgedoc';
$user = 'hedgedoc';

try {
    $dbh = new PDO("pgsql:host=$host;dbname=$dbname", $user);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    die();
}

$query = 'SELECT "Notes".title, "Notes"."updatedAt", "Notes"."shortid", "Users".profile
          FROM "Notes"
          JOIN "Users" ON "Notes"."ownerId" = "Users".id
          WHERE (permission = \'freely\' OR permission = \'editable\' OR permission = \'limited\')
            AND strpos(content, \'tags: listed\') > 0
          ORDER BY "Notes"."updatedAt" DESC';
try {
    $stmt = $dbh->query($query);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    die();
}

function formatDateString($stringDate)
{
    $datetime = DateTime::createFromFormat('Y-m-d H:i:s.uP', $stringDate);
    return $datetime->format('d.m.Y H:i');
}
?>

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pad lister</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
</head>

<body>
    <div class="container">
        <br>
        <h6>Willkommen <?= htmlspecialchars($shortName, ENT_QUOTES, 'UTF-8') ?> ✨</h6>

        <small>
            <label>
                <input type="checkbox" id="hideUntitled" checked>
                Unbenannte Pads ausblenden
            </label>
        </small>
        <br>

        <table id="padsTable">
            <thead>
                <tr>
                    <th>Titel</th>
                    <th>Owner</th>
                    <th>Last edit</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="pad-title">
                            <a href="https://pad.jo11.dev/<?= htmlspecialchars($row['shortid'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') ?></a>
                        </td>
                        <td>
                            <?= htmlspecialchars(json_decode($row['profile'])->username, ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?= formatDateString($row['updatedAt']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <br><br>
        <a href="logout.php">Logout</a>
        <br><br>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkbox = document.getElementById('hideUntitled');
            const table = document.getElementById('padsTable');
            const rows = table.querySelectorAll('tbody tr');

            function updateVisibility() {
                rows.forEach(row => {
                    const titleCell = row.querySelector('.pad-title a');
                    const titleText = titleCell ? titleCell.textContent.trim() : '';
                    // hide if checkbox checked AND title is exactly 'Untitled'
                    row.style.display = (checkbox.checked && titleText === 'Untitled') ? 'none' : '';
                });
            }

            // initial state
            updateVisibility();
            // toggle on change
            checkbox.addEventListener('change', updateVisibility);
        });
    </script>
</body>

</html>
