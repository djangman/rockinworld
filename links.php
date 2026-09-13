<?php
// links.php
$pdo2 = new PDO('mysql:host=localhost;dbname=skwazlwj_notesync;charset=utf8', 'skwazlwj', 'z7PXBkkJ4JYE');

$pdo2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo2->exec("CREATE TABLE IF NOT EXISTS rockinworld_link_sections (
  section_key VARCHAR(64) NOT NULL,
  links_text TEXT NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (section_key)
)");

$sectionKeys = [
    'cool_cribs' => 'Cool Cribs',
    'awesome_ride' => 'My Awesome Ride!',
    'watches_style' => 'Watches & Style',
    'fitness' => 'Women and my Fitness',
    'nightbreath' => 'Great sleep'
];

$linkSections = [
    'nightbreath' => [
        'title' => 'Great sleep',
        'links' => [
            'https://www.zillow.com' => 'Berg'
        ],
    ],
    'cool_cribs' => [
        'title' => 'Cool Cribs',
        'links' => [
            'https://www.zillow.com' => 'Zillow',
            'https://www.realtor.com' => 'Realtor.com',
            'https://www.airbnb.com' => 'Airbnb',
            'https://www.trulia.com' => 'Trulia',
        ],
    ],
    'awesome_ride' => [
        'title' => 'My Awesome Ride!',
        'links' => [
            'https://www.hyundaiusa.com' => 'Hyundai',
            'https://www.kia.com' => 'Kia',
            'https://www.mazda.com' => 'Mazda',
            'https://www.cars.com' => 'Compare Prices',
        ],
    ],
    'watches_style' => [
        'title' => 'Watches & Style',
        'links' => [
            'https://www.livwatches.com' => 'livwatches.com',
            'https://www.fratello.com' => 'Fratello',
            'https://www.huckberry.com' => 'Huckberry',
            'https://www.jcrew.com/mens' => 'J.Crew Menswear',
            'https://aged-bead-b64.notion.site/Visual-Browse-3bcadbd4854680d5b851f523071f6398?source=copy_link' => 'Visual Browse',
        ],
    ],
    'fitness' => [
        'title' => 'Women and my Fitness',
        'links' => [
            'https://www.tnaboard.com/community/forums/id-provider-posts.379/' => 'TNABOARD',
            'https://adultsearch.com/us/id/boise' => 'AdultSearch Boise',
            'https://www.mexicolindobar.com/' => 'Mexico Lindo Bar',
            'https://www.cumintj.com/' => 'CumInTJ',
            'https://www.humaniplex.com/classifieds/tags/?trid=371CumInTJ' => 'Humaniplex',
        ],
    ],
];

$savedLinks = array_fill_keys(array_keys($sectionKeys), '');

$placeholders = implode(',', array_fill(0, count($sectionKeys), '?'));
$stmt = $pdo2->prepare('SELECT section_key, links_text FROM rockinworld_link_sections WHERE section_key IN (' . $placeholders . ')');
$stmt->execute(array_keys($sectionKeys));
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $savedLinks[$row['section_key']] = (string) $row['links_text'];
}

function parseLinkEntries($text) {
    $lines = preg_split('/\r\n|\r|\n/', (string) $text);
    $entries = [];

    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line !== '') {
            $entries[] = $line;
        }
    }

    return array_values(array_unique($entries));
}

function normalizeLinkValue($value) {
    $clean = trim((string) $value);
    if ($clean === '') {
        return '';
    }

    if (!preg_match('#^https?://#i', $clean) && !preg_match('#^www\.#i', $clean)) {
        $clean = 'https://' . $clean;
    }

    return $clean;
}

function persistLinkSection(PDO $pdo, $sectionKey, $value) {
    $stmt = $pdo->prepare('INSERT INTO rockinworld_link_sections (section_key, links_text) VALUES (:section_key, :links_text)
        ON DUPLICATE KEY UPDATE links_text = VALUES(links_text)');
    $stmt->execute([
        ':section_key' => $sectionKey,
        ':links_text' => $value,
    ]);
}

function renderSavedLinkList($rawText, $sectionKey) {
    $lines = parseLinkEntries($rawText);
    $items = [];

    foreach ($lines as $line) {
        $href = normalizeLinkValue($line);
        $display = preg_replace('#^https?://#i', '', $href);
        $display = preg_replace('#^www\.#i', '', $display);

        $items[] = '<li>
            <div class="card__saved-row">
                <a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($display, ENT_QUOTES, 'UTF-8') . '</a>
                <button type="submit" name="remove_link[' . htmlspecialchars($sectionKey, ENT_QUOTES, 'UTF-8') . '][]" value="' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '" class="card__remove-button">Remove</button>
            </div>
        </li>';
    }

    if (empty($items)) {
        return '<div class="card__saved-empty">No saved links yet.</div>';
    }

    return '<ul class="card__saved-list">' . implode('', $items) . '</ul>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($sectionKeys as $key => $label) {
        $entries = parseLinkEntries($savedLinks[$key] ?? '');

        $removeValues = $_POST['remove_link'][$key] ?? [];
        if (is_string($removeValues)) {
            $removeValues = [$removeValues];
        }

        foreach ((array) $removeValues as $removed) {
            $removed = trim((string) $removed);
            if ($removed === '') {
                continue;
            }

            $entries = array_values(array_filter($entries, function ($entry) use ($removed) {
                return trim((string) $entry) !== trim((string) $removed);
            }));
        }

        $addValue = trim((string) ($_POST['add_link'][$key] ?? ''));
        if ($addValue !== '') {
            $candidate = normalizeLinkValue($addValue);
            if (!in_array($candidate, $entries, true)) {
                $entries[] = $candidate;
            }
        }

        $savedLinks[$key] = implode("\n", $entries);
        persistLinkSection($pdo2, $key, $savedLinks[$key]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Link Hub</title>
<style>
  :root {
    --accent: #0b5fff;
    --accent-dark: #0842b0;
    --text-dark: #101828;
    --text-muted: #667085;
    --border: #e4e7ec;
    --bg: #f7f8fa;
    --card-bg: #ffffff;
  }

  * { box-sizing: border-box; }

  body {
    margin: 0;
    padding: 48px 24px;
    background: var(--bg);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    color: var(--text-dark);
  }

  .email-wrap {
    max-width: 1200px;
    margin: 0 auto;
  }

  .grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(320px, 1fr));
    gap: 16px;
    max-width: 1200px;
    margin: 0 auto;
  }

  @media (max-width: 1024px) {
    .grid { grid-template-columns: 1fr; }
  }

  @media (max-width: 600px) {
    .grid { grid-template-columns: 1fr; }
  }

  .card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 28px 24px;
    box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
    transition: box-shadow 0.2s ease, transform 0.2s ease;
  }

  .card:hover {
    box-shadow: 0 8px 24px rgba(16, 24, 40, 0.08);
    transform: translateY(-2px);
  }

  .card__header {
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--accent);
    margin: 0 0 4px 0;
  }

  .card__subline {
    height: 3px;
    width: 32px;
    background: var(--accent);
    border-radius: 2px;
    margin-bottom: 20px;
  }

  .card__list {
    list-style: none;
    margin: 0;
    padding: 0;
  }

  .card__list li {
    border-bottom: 1px solid var(--border);
  }

  .card__list li:last-child {
    border-bottom: none;
  }

  .card__list a {
    display: block;
    padding: 12px 2px;
    color: var(--text-dark);
    text-decoration: none;
    font-size: 15px;
    font-weight: 500;
    transition: color 0.15s ease, padding-left 0.15s ease;
  }

  .card__list a:hover {
    color: var(--accent-dark);
    padding-left: 6px;
  }

  .card__saved {
    margin-top: 16px;
    padding-top: 12px;
    border-top: 1px solid var(--border);
  }

  .card__saved-label {
    margin: 0 0 8px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--text-muted);
  }

  .card__saved-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .card__saved-list li {
    line-height: 1.35;
  }

  .card__saved-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
  }

  .card__saved-list a {
    color: var(--accent-dark);
    text-decoration: none;
    word-break: break-word;
    flex: 1 1 auto;
  }

  .card__saved-list a:hover {
    text-decoration: underline;
  }

  .card__remove-form {
    margin: 0;
  }

  .card__remove-button {
    border: 1px solid var(--border);
    background: #fff;
    color: var(--text-dark);
    padding: 4px 8px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
  }

  .card__saved-empty {
    color: var(--text-muted);
    font-size: 13px;
    font-style: italic;
  }

  .link-editor {
    margin-top: 12px;
  }

  .link-editor__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 8px;
    color: var(--text-muted);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }

  .link-input-row {
    display: flex;
    gap: 8px;
    margin-bottom: 10px;
  }

  .link-input-row input {
    flex: 1;
    min-width: 0;
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 9px 10px;
    font: inherit;
  }

  .link-input-row button {
    border: none;
    background: var(--accent);
    color: #fff;
    border-radius: 8px;
    padding: 9px 12px;
    font-weight: 600;
    cursor: pointer;
  }
</style>
</head>
<body>

<div class="email-wrap">
  <form method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
    <div class="grid">
      <?php foreach ($linkSections as $sectionKey => $section): ?>
        <div class="card">
        <p class="card__header"><?php echo htmlspecialchars($section['title']); ?></p>
        <div class="card__subline"></div>
        <ul class="card__list">
          <?php foreach ($section['links'] as $url => $label): ?>
            <li><a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></a></li>
          <?php endforeach; ?>
        </ul>

        <div class="link-editor">
          <div class="link-editor__header">
            <span>Custom links</span>
            <small>One URL per line</small>
          </div>

          <div class="link-input-row">
            <input type="text" name="add_link[<?php echo htmlspecialchars($sectionKey, ENT_QUOTES, 'UTF-8'); ?>]" placeholder="Add a link...">
            <button type="submit" name="add_button[<?php echo htmlspecialchars($sectionKey, ENT_QUOTES, 'UTF-8'); ?>]" value="1">Add</button>
          </div>

          <div class="card__saved">
            <p class="card__saved-label">Saved links</p>
            <?php echo renderSavedLinkList($savedLinks[$sectionKey] ?? '', $sectionKey); ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
  </form>
</div>

</body>
</html>