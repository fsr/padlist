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

// Get current user's ID from session using preferred_username
$currentUserId = null;
$preferredUsername = $_SESSION['preferred_username'] ?? '';

if ($preferredUsername) {
    $userQuery = 'SELECT id FROM "Users" WHERE profile::json->>\'username\' = :username';
    $userStmt = $dbh->prepare($userQuery);
    $userStmt->bindParam(':username', $preferredUsername);
    $userStmt->execute();
    $userResult = $userStmt->fetch(PDO::FETCH_ASSOC);
    $currentUserId = $userResult['id'] ?? null;
}

// Query for public/listed pads
$publicQuery = 'SELECT "Notes".title, "Notes"."updatedAt", "Notes"."shortid", "Users".profile, \'public\' as pad_type
          FROM "Notes"
          JOIN "Users" ON "Notes"."ownerId" = "Users".id
          WHERE (permission = \'freely\' OR permission = \'editable\' OR permission = \'limited\')
            AND strpos(content, \'tags: listed\') > 0
          ORDER BY "Notes"."updatedAt" DESC';

// Query for private pads of current user
$privateQuery = 'SELECT "Notes".title, "Notes"."updatedAt", "Notes"."shortid", "Users".profile, \'private\' as pad_type
          FROM "Notes"
          JOIN "Users" ON "Notes"."ownerId" = "Users".id
          WHERE "Notes"."ownerId" = :userId
            AND permission = \'private\'
          ORDER BY "Notes"."updatedAt" DESC';

try {
    // Fetch public pads
    $publicStmt = $dbh->query($publicQuery);
    $publicRows = $publicStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch private pads if user ID is available
    $privateRows = [];
    if ($currentUserId) {
        $privateStmt = $dbh->prepare($privateQuery);
        $privateStmt->bindParam(':userId', $currentUserId);
        $privateStmt->execute();
        $privateRows = $privateStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Combine all rows
    $allRows = array_merge($publicRows, $privateRows);

    // Sort by updatedAt DESC
    usort($allRows, function($a, $b) {
        return strtotime($b['updatedAt']) - strtotime($a['updatedAt']);
    });

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    die();
}

function formatDateString($stringDate)
{
    $datetime = DateTime::createFromFormat('Y-m-d H:i:s.uP', $stringDate);
    if ($datetime) {
        return $datetime->format('d.m.Y H:i');
    }
    return $stringDate;
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
    <style>
        .private-pad {
            background-color: rgba(255, 193, 7, 0.1);
        }

    </style>
</head>

<body>
    <div class="container">
        <br>
        <h6>Willkommen <?= htmlspecialchars($shortName, ENT_QUOTES, 'UTF-8') ?> ✨</h6>

        <div style="display: flex; align-items: center; gap: 1.5rem; margin-bottom: 1rem;">
            <small>
                <label>
                    <input type="checkbox" id="hideUntitled" checked>
                    Unbenannte Pads ausblenden
                </label>
            </small>
            <small>
                <label>
                    <input type="checkbox" id="showPrivate">
                    Meine privaten Pads anzeigen
                </label>
            </small>
        </div>


        <table id="padsTable">
            <thead>
                <tr>
                    <th>Titel</th>
                    <th>Owner</th>
                    <th>Last edit</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allRows as $row): ?>
                    <tr class="<?= $row['pad_type'] === 'private' ? 'private-pad' : '' ?>" data-type="<?= htmlspecialchars($row['pad_type'], ENT_QUOTES, 'UTF-8') ?>">
                        <td class="pad-title">
                            <a href="https://pad.jo11.dev/<?= urlencode($row['shortid']) ?>"><?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') ?></a>
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
            const hideUntitledCheckbox = document.getElementById('hideUntitled');
            const showPrivateCheckbox = document.getElementById('showPrivate');
            const table = document.getElementById('padsTable');
            const rows = table.querySelectorAll('tbody tr');

            function updateVisibility() {
                rows.forEach(row => {
                    const titleCell = row.querySelector('.pad-title a');
                    const titleText = titleCell ? titleCell.textContent.trim() : '';
                    const padType = row.getAttribute('data-type');

                    let shouldHide = false;

                    // Hide if checkbox checked AND title is exactly 'Untitled'
                    if (hideUntitledCheckbox.checked && titleText === 'Untitled') {
                        shouldHide = true;
                    }

                    // Hide private pads if showPrivate is not checked
                    if (padType === 'private' && !showPrivateCheckbox.checked) {
                        shouldHide = true;
                    }

                    row.style.display = shouldHide ? 'none' : '';
                });
            }

            // Initial state - hide private pads by default
            updateVisibility();

            // Toggle on change
            hideUntitledCheckbox.addEventListener('change', updateVisibility);
            showPrivateCheckbox.addEventListener('change', updateVisibility);
        });
    </script>
</body>

</html>
