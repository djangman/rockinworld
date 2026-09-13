<?php
$goodsleep = '';
$goodsleepStatus = '';

try {
  $pdo = new PDO(
    'mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('DB_NAME') ?: 'skwazlwj_notesync') . ';charset=utf8mb4',
    getenv('DB_USER') ?: 'skwazlwj',
    getenv('DB_PASSWORD') ?: 'z7PXBkkJ4JYE',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
  );

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['goodsleep'])) {
    $goodsleep = trim($_POST['goodsleep']);
    error_log('[rockinworld] goodsleep POST received; length=' . strlen($goodsleep));

    $rowExists = (bool)$pdo->query('SELECT 1 FROM paragraph_sections LIMIT 1')->fetchColumn();
    if ($rowExists) {
      $statement = $pdo->prepare('UPDATE paragraph_sections SET goodsleep = ?');
      $statement->execute([$goodsleep]);
    } else {
      $statement = $pdo->prepare('INSERT INTO paragraph_sections (goodsleep) VALUES (?)');
      $statement->execute([$goodsleep]);
    }
    $affectedRows = $statement->rowCount();
    error_log('[rockinworld] goodsleep UPDATE succeeded; affected_rows=' . $affectedRows);

    $savedGoodsleep = $pdo->query('SELECT goodsleep FROM paragraph_sections LIMIT 1')->fetchColumn();
    if ($savedGoodsleep === false) {
      error_log('[rockinworld] verification failed: paragraph_sections returned no rows');
      $goodsleepStatus = 'Save failed: no paragraph_sections row found.';
    } elseif ((string)$savedGoodsleep !== $goodsleep) {
      error_log('[rockinworld] verification mismatch; expected_length=' . strlen($goodsleep) . ', actual_length=' . strlen((string)$savedGoodsleep));
      $goodsleepStatus = 'Save failed: verification mismatch.';
    } else {
      $goodsleepStatus = 'Saved. (' . $affectedRows . ' row(s) changed)';
    }
  } else {
    $goodsleep = (string)($pdo->query('SELECT goodsleep FROM paragraph_sections LIMIT 1')->fetchColumn() ?: '');
  }
} catch (Throwable $e) {
  $goodsleepStatus = 'Database connection failed.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex"/>
<title></title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="books.css"/>
<style>
  *, *::before, *::after { box-sizing: border-box; }
  body { margin: 0; padding: 0; background: #fff; font-family: Montserrat, Trebuchet MS, Tahoma, sans-serif; }

  /* ── Layout wrapper ── */
  .email-wrap { max-width: 600px; margin: 0 auto; background: #fff; }

  /* ── Basics Section ── */
  .basics-section {
    background: linear-gradient(135deg, #f0f9ff 0%, #e8f4fd 100%);
    padding: 40px 30px 44px;
    text-align: center;
  }
  .basics-heading {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 35px;
    font-weight: 700;
    color: #1a3a5c;
    margin: 0 0 10px;
    line-height: 1.3;
  }
  .hint-text {
    font-size: 11px;
    color: #8aafc7;
    margin: 0 0 22px;
    letter-spacing: 0.3px;
  }
  .sentence-item { margin-bottom: 18px; }
  .sentence-text {
    font-size: 22px;
    font-weight: 600;
    color: blue;
    line-height: 1.55;
    cursor: pointer;
    border-radius: 6px;
    padding: 6px 10px;
    display: inline-block;
    transition: color .2s, background .2s;
  }
  
  .pri-sentence-text {
    border: 6px green solid;
    font-size: 22px;
    font-weight: 600;
    color: blue;
    background: yellow;
    line-height: 1.55;
    cursor: pointer;
    border-radius: 10px;
    padding: 6px 10px;
    display: inline-block;
    transition: color .2s, background .2s;
  }
  .sentence-text:hover { color: #1a3a5c; background: rgba(44,95,138,.07); }
  .sentence-edit-row {
    display: none;
    align-items: center;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
  }
  .sentence-input {
    font-family: Montserrat, sans-serif;
    font-size: 18px;
    font-weight: 600;
    color: #1a3a5c;
    border: 2px solid #2c5f8a;
    border-radius: 8px;
    padding: 8px 14px;
    width: 100%;
    max-width: 420px;
    outline: none;
    background: #fff;
    transition: border-color .2s, box-shadow .2s;
  }
  .sentence-input:focus { border-color: #1a3a5c; box-shadow: 0 0 0 3px rgba(44,95,138,.15); }
  .save-btn {
    font-family: Montserrat, sans-serif;
    font-size: 14px;
    font-weight: 700;
    background: #2c5f8a;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 10px 20px;
    cursor: pointer;
    white-space: nowrap;
    transition: background .2s, transform .1s;
  }
  .save-btn:hover { background: #1a3a5c; transform: translateY(-1px); }
  .save-btn:active { transform: translateY(0); }

  /* ── Hero / priorities ── */
  .hero {
    background-image: url('images/main_background.png');
    background-position: top center;
    background-repeat: no-repeat;
    background-color: #b2e5f4;
    padding: 5px 0 35px;
  }
  .hero img.hero-img { width: 100%; max-width: 600px; display: block; margin: 0 auto; }
  .hero-text {
    color: #476d77;
    font-size: 34px;
    font-weight: 700;
    text-align: center;
    padding: 5px;
    line-height: 1.5;
  }
  .hero-text ul { text-align: left; margin: 10px 0 0; padding-left: 30px; }
  .hero-text ul li { margin-bottom: 8px; }

  /* ── Separator ── */
  .separator { text-align: center; padding: 5px 0; }
  .separator img { max-width: 150px; display: inline-block; }
  .separator-wide img { max-width: 180px; }

  /* ── Two-col rows ── */
  .two-col { display: flex; flex-wrap: wrap; max-width: 600px; margin: 0 auto; }
  .two-col .col { width: 50%; min-width: 280px; padding: 15px 10px; }
  .two-col .col img { width: 100%; max-width: 260px; display: block; margin: 20px auto; }

  /* ── Text helpers ── */
  .purple { color: #ba4ab6; }
  .gray   { color: #656565; }

  /* ── Bought/Doing sections ── */
  .feat-title { font-size: 22px; font-weight: 700; line-height: 1.2; margin: 0 0 8px; }
  .feat-body  { font-size: 20px; font-weight: 700; line-height: 1.5; }

  /* ── Thank you list ── */
  .thankyou-col { padding: 30px 10px 15px 0; }
  .thankyou-heading { font-size: 18px; font-weight: 700; color: #ff6600; text-align: center; margin: 0 0 10px; }
  #thankYouList { list-style: disc; padding-left: 24px; margin: 0; }
  #thankYouList li { font-size: 18px; font-weight: 700; line-height: 1.4; margin-bottom: 6px; color: #ba4ab6; }

  /* ── Orange creator band ── */
  .creator-band { background: #f28017; padding: 10px 0 15px; }
  .creator-band .two-col .col { color: #fff; }
  .creator-band .col img { max-width: 270px; margin: 30px auto 15px; }
  .creator-title { font-size: 24px; font-weight: 700; color: #fff; margin: 0 0 10px; }
  .creator-list  { list-style: square; padding-left: 20px; margin: 0; }
  .creator-list li { font-size: 20px; font-weight: 700; color: #e6e6e6; line-height: 1.5; margin-bottom: 6px; }

  /* ── Affirmation block ── */
  .affirmation { padding: 40px 20px; text-align: center; }
  .affirmation p { margin: 0 0 12px; line-height: 1.3; }
  .affirm-main { font-size: 22px; font-weight: 700; color: #000080; }
  .affirm-note  { font-size: 25px; font-weight: 700; color: #08084b; }
  .affirm-credit { font-size: 18px; font-weight: 700; color: #656565; margin-top: 16px; }
  .affirm-avatar { text-align: center; margin-top: 16px; }
  .affirm-avatar img { height: 64px; display: inline-block; }
  .affirm-avatar p { font-size: 14px; color: #656565; margin: 4px 0 0; }

  /* ── Questions band ── */
  .questions-band {
    background-image: url('images/discountbg.png');
    background-position: top left;
    background-repeat: no-repeat;
    background-color: #b2e5f4;
    padding: 30px 5px 20px;
    text-align: center;
  }
  .questions-band p { font-size: 22px; font-weight: 700; color: #ba4ab6; margin: 0 0 12px; line-height: 1.2; }

  /* ── Footer ── */
  .footer { text-align: center; padding: 20px 10px; }
  .footer img.logo { max-width: 120px; display: inline-block; margin-bottom: 8px; }
  .footer .copyright { font-size: 12px; color: #7b7b7b; margin: 0 0 10px; }
  .social-icons { display: flex; justify-content: center; gap: 20px; margin: 10px 0; }
  .social-icons img { width: 32px; height: 32px; display: block; }
  .bee-credit { display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 16px; }
  .bee-credit img { height: 32px; }
  .bee-credit a { font-size: 15px; color: #9d9d9d; text-decoration: none; }

  /* ── Responsive ── */
  @media (max-width: 600px) {
    .two-col .col { width: 100%; }
    .basics-heading { font-size: 22px; }
    .sentence-text  { font-size: 16px; }
    .sentence-input { font-size: 15px; }
    .hero-text { font-size: 22px; }
  }

  #identity-section li { margin-bottom: 6px; }

.sub-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    padding: 1rem 0;
    font-family: 'Georgia', sans-serif;
}
.sub-card {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    overflow: hidden;
}
.sub-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 18px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}
.sub-header h2 {
    font-size: 1rem;
    font-weight: 700;
    margin: 0;
    letter-spacing: 0.04em;
}
.kindle-header  { background: rgba(239, 159, 39, 0.15); }
.kindle-header h2 { color: #f5c56a; }
.audible-header { background: rgba(55, 138, 221, 0.15); }
.audible-header h2 { color: #7dc4f7; }

.date-list { list-style: none; margin: 0; padding: 6px 0; }

.date-row {
    display: flex;
    align-items: baseline;
    gap: 12px;
    padding: 10px 18px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    transition: background 0.15s ease;
}
.date-row:last-child { border-bottom: none; }
.date-row:hover { background: rgba(255,255,255,0.04); }

.date-label {
    font-size: 0.82rem;
    font-weight: 600;
    color: #b0aac8;
    min-width: 72px;
    white-space: nowrap;
}
.date-note {
    font-size: 0.90rem;
    color: #6b6580;
    line-height: 1.35;
}

/* Past dates — struck through and dimmed */
.date-row.past .date-label,
.date-row.past .date-note {
    color: #3e3b50;
    text-decoration: line-through;
    text-decoration-color: #3e3b50;
}

/* Highlighted next upcoming date */
.date-row.next-up {
    background: rgba(29, 158, 117, 0.12);
    border-left: 3px solid #1d9e75;
    padding-left: 15px;
}
.date-row.next-up .date-label { color: #5dcaa5; font-weight: 700; }
.date-row.next-up .date-note  { color: #5dcaa5; font-weight: 600; }

.next-badge {
    margin-left: auto;
    font-size: 0.68rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 20px;
    background: #1d9e75;
    color: #fff;
    white-space: nowrap;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

@media (max-width: 560px) {
    .sub-grid { grid-template-columns: 1fr; }
}


</style>
<link rel="icon" type="image/svg+xml" href="favicon.svg"/>

</head>
<body>
<div class="email-wrap">

  <!-- ── BASICS SECTION ───────────────────────────────── -->
  <div class="basics-section">
    <h1 class="basics-heading-top">Your life is a vacation with bits of non-mandatory hobby / self-improvement bouts - and fun ones</h1>
    <h1 class="basics-heading">Beautiful Basics to Deserve Tonight's Bedtime (even if very small quick task)</h1>
    <p class="hint-text">Double-click any sentence to edit it</p>
    <div id="sentences-container">
      <p style="color:#8aafc7;">Loading…</p>
    </div>
  </div>

  <p style="margin: 12px 0 18px; text-align: center;">
    <a href="#links-section" style="color: #1a3a5c; font-weight: 700; text-decoration: underline;">Jump to Links</a>
  </p>

  <div class="sub-grid">
  <div class="hint-text" style="grid-column: 1 / -1; color: blue;font-size: 22px; font-family: 'Montserrat', 'Segoe UI', Tahoma, Geneva, sans-serif; font-weight: 600;">Today: <?php echo date('m/d/Y'); ?></div>
    <div class="sub-card">
        <div class="sub-header kindle-header">
            <h2>📖 Kindle Unlimited</h2>
        </div>
        <ul class="date-list" id="kindle-list"></ul>
    </div>

    <div class="sub-card">
        <div class="sub-header audible-header">
            <h2>🎧 Audible</h2>
        </div>
        <ul class="date-list" id="audible-list"></ul>
    </div>

</div>

  

  <?php require('accordion.php'); ?>

  <!-- vertical spacer between accordion and amazon list -->
  <div style="height:20px; width:100%; display:block;"></div>

  <div style="background: linear-gradient(135deg, rgba(138, 175, 199, 0.1), rgba(200, 220, 240, 0.1)); border-left: 4px solid #8aafc7; padding: 24px; margin: 20px 0; border-radius: 8px; font-family: 'Segoe UI', Tahoma, Geneva, sans-serif;">
    <form method="post">
      <input type="hidden" name="goodsleep" id="goodsleep-value" value="<?php echo htmlspecialchars($goodsleep, ENT_QUOTES, 'UTF-8'); ?>">
      <div id="goodsleep-display" title="Double-click to edit" style="width:100%; min-height:190px; box-sizing:border-box; white-space:pre-wrap; font-size:19px; line-height:1.8; color:#2c3e50; margin:0; font-weight:500; font-family:inherit; cursor:text;"><?php echo htmlspecialchars($goodsleep, ENT_QUOTES, 'UTF-8'); ?></div>
      <button type="submit" style="margin-top:10px; padding:8px 18px; cursor:pointer;">Save</button>
      <?php if ($goodsleepStatus !== ''): ?>
        <span style="margin-left:10px; color:#2c3e50;"><?php echo htmlspecialchars($goodsleepStatus, ENT_QUOTES, 'UTF-8'); ?></span>
      <?php endif; ?>
    </form>
  </div>

  <script>
    (function () {
      const display = document.getElementById('goodsleep-display');
      const hiddenValue = document.getElementById('goodsleep-value');

      display.addEventListener('dblclick', function () {
        const textarea = document.createElement('textarea');
        textarea.rows = 9;
        textarea.value = display.textContent;
        textarea.style.cssText = 'width:100%; font-size:19px; line-height:1.8; color:#2c3e50; margin:0; font-weight:500; font-family:inherit;';
        textarea.addEventListener('input', function () {
          hiddenValue.value = textarea.value;
        });
        display.replaceWith(textarea);
        textarea.focus();
      });
    }());
  </script>

  <?php require('amazonlist.php'); ?>


  <!-- ── HERO / PRIORITIES ────────────────────────────── -->
  <div class="hero" id="identity-section">
    <div style="padding-left: 23px; margin-bottom: 10px; background-image: linear-gradient(rgba(255,255,255,0.7), rgba(255,255,255,0.7)), url('images/2021-02-19_18-32-17.png'); background-size: cover; background-position: center; height: fit-content;">
        <h2>
            <span style="color: #000080; font-size: 30px; font-weight: 700;">Identity</span>
        </h2>
        <ol>
            <li style="color: #000080; font-size: 20px; font-weight: 700;">
                Being DeTached from the need to feel pressure around Trading success due to already being financially successful and really killin' it.
                But it's just fun as fuck, to further hone my skill and perception-power in trading with 
                various attitudes and perspectives. Just pure fun, and a powerful sense of 
                Winning and turning chart-watching and clicking for a massive flow of money to me.
                
            </li>   
            <li style="color: #000080; font-size: 20px; font-weight: 700;">
                Further organizing my Trading Setup Menu and/or Playbook
            </li>
            <li style="color: #000080; font-size: 20px; font-weight: 700;">
                Happy and content with my amount of wealth and my ability to make more money easily, and to enjoy it all. 
            </li>
            <li style="color: #000080; font-size: 20px; font-weight: 700;">
                So completely interested in my trading and reading the remainder of my books, 
                that I don't require the dopamine hit of youtube or other social media.  
                
            </li>
            <li style="color: #000080; font-size: 20px; font-weight: 700;">
                I'm always interested in looking up the next type of vacation to take - 
                the next experience to express appreciation for, rather than wanting to 
                watch how mad someone gets when they get arrested by police on a youtube
            </li>
            <li style="color: #000080; font-size: 20px; font-weight: 700;">
                Have no need to continue spending my energy being negative about Valon and what the heck is going on with that.
                There's just a feeling of gratitude that I can get away from them by me being single and very flexible with where to live.
                
            </li>
        </ol>
    </div>
    <div class="hero-text">
      <strong style="color: blue;">The Most Important Priorities</strong>
      <ul>
        <li>Continuing the body reshaping</li>
        <li>Nofap and Meditation</li>
        <li>Being the Identity of The Fulfilled Steve that is on a perpetual Vacation, with the Fun, Big Stuff even if the outside is not pleasing today!</li>
        <li>There is no concern over the outcome of any actions I'm taking. All I need to do is feel grateful for the end outcome, and take action in the NOW with bliss and peace and joy.</li>
        <li>My happiness and outward circumstances are thankfully not reliant on google, companies, recruiters, mortgage companies, landlords, women out there ... nope! I decide baby and I love that.</li>
      </ul>
    </div>
  </div>

  <!-- ── SEPARATOR ─────────────────────────────────────── -->
  <div class="separator"><img src="images/separator1.png" alt=""/></div>

  
  <!-- ── links page ──────────────────────────────── -->
  <div id="links-section">
    <?php require('links.php'); ?>
  </div>

  <!-- ── BOUGHT WITH CASH ──────────────────────────────── -->
  <div class="two-col">
    <div class="col" style="text-align:center;">
      <img src="images/pexels-photo-6694495.jpeg" alt="Enhance driving performance"/>
    </div>
    <div class="col">
      <p class="feat-title purple">Bought it with Cash</p>
      <p class="feat-body gray">This Feeling That Money is so Easy is rather Wonderful.. and it's even okay that I've only just Captured it in my Fifties</p>
    </div>
  </div>

  <!-- ── DOING THE WORK ────────────────────────────────── -->
  <div class="two-col" style="flex-direction: row-reverse;">
    <div class="col" style="text-align:center;">
      <img src="images/blackass.gif" alt="Subtle styling differences"/>
    </div>
    <div class="col">
      <p class="feat-title purple">Doing the Work, both on physical level and with Energy gets me This. :) No need for WT</p>
    </div>
  </div>

  <!-- ── THANK YOU + IMAGE ─────────────────────────────── -->
  <div class="two-col" style="flex-direction: row-reverse;">
    <div class="col thankyou-col">
      <p class="thankyou-heading">Thank you for:</p>
      <ul id="thankYouList">
        <li>My great decisions, such as SR, volume-oriented weights and removing carbs</li>
        <li>My current 180 Streak will be without immersing myself in horny energy </li>
        <li>The work I do in trading, proactive and studios and work ethic-like, taking notes from books and videos in a powerfully organized way!!</li>
        <li>Still have Dodson new book and benefitting from the simple IFS book!</li>
        <li>My Neville reading queue</li>
        <li>Love all the amazing netflix that keeps getting added.</li>
        <li>Healthy, More and More Attractive Body and Face !!</li>
        <li>My new life in Nampa in a fucking rental!!  Places to walk!!  Anonymous as fuck!</li>
        <li>Tijuana eventually again!  But, soon, at least Massaged by women with my attractive strong back!</li>
      </ul>
    </div>
    <div class="col" style="text-align:center;">
      <img src="images/Capture.PNG" alt="" style="max-width:250px;"/>
    </div>
  </div>

  <!-- ── SEPARATOR ─────────────────────────────────────── -->
  <div class="separator"><img src="images/separator_2.png" alt=""/></div>

  <!-- ── CREATOR BAND ──────────────────────────────────── -->
  <div class="creator-band">
    <div class="two-col">
      <div class="col" style="text-align:center;">
        <img src="images/pexels-photo-302899.jpeg" alt="Electrifying good looks"/>
        <img src="secondaryimages/2026-05-25_14-51-24.png" alt="Electrifying good looks"/>
        <img src="secondaryimages/2026-05-25_14-57-01.png" alt="Electrifying good looks"/>
        <img src="secondaryimages/2026-05-25_15-02-48.png" alt="Electrifying good looks"/>
        <img src="secondaryimages/2026-05-25_15-40-58.png" alt="Electrifying good looks"/>
        
        <img src="secondaryimages/2026-05-25_14-52-18.png" alt="Electrifying good looks"/>
        <img src="secondaryimages/2026-05-25_15-03-21.png" alt="Electrifying good looks"/>
        <img src="secondaryimages/2026-05-25_14-58-18.png" alt="Electrifying good looks"/>
        <img src="secondaryimages/2026-05-25_14-52-48.png" alt="Electrifying good looks"/>

      </div>
      <div class="col">
        <p class="creator-title">My Passion for Setting up Systems</p>
        <ul class="creator-list">
          <li>Organizing Trading Knowledge in my own Executable Way</li>
          <li>My work ethic when I have access to so much juicy good information.</li>
          <li>All my various alacarte apps that save me time and effort, and are fun to use!</li>
          <li>Youtube - An organized system to download youtubes to my phone for all the uses I need</li>
          <LI>My love for sharing what I learn and do with others, and my ability to do it in a way that is enjoyable and inspiring.</li>
          <LI>The fun and organized feeling of having slickrun's and autohotkey scripts all set up to do whatever I need.</li>
          <LI>Cheat carnivore food on road trip: cheese, beef sticks, mixed nuts, diet pepsi</LI>
          <LI style="font-weight: bold; color:yellow;">Pics on Left - Best Western Driftwood Inn in Idaho Falls - nice watery views and places to walk </li>
        </ul>
      </div>
    </div>
  </div>

  <!-- ── SEPARATOR ─────────────────────────────────────── -->
  <div class="separator separator-wide"><img src="images/separator_3.png" alt=""/></div>

  <!-- ── AFFIRMATION ───────────────────────────────────── -->
  <div class="affirmation">
    <p class="affirm-main">I AM. I AM NOW. I AM FULFILLED NOW. I DECIDE TO BE IT ALL NOW, AND WHO WOULD I THEN BE TODAY ??</p>
    <p class="affirm-note">Take advantage of your transition moments during the day to think from the desired Identity!!  NO excuse not to!  LEVERAGE THAT IN A MASSIVE WAY!</p>
    
    
  </div>

  <!-- ── SEPARATOR ─────────────────────────────────────── -->
  <div class="separator separator-wide"><img src="images/separator_4.png" alt=""/></div>

  <!-- ── QUESTIONS BAND ────────────────────────────────── -->
  <div class="questions-band">
    <p>Why do Ideas to make money flow easily into my mind?</p>
    <p>Why am I so grateful for my constant supply of money?</p>
    <p>Why is it so easy to make my body healthier and more attractive month after month?</p>
  </div>

  <!-- ── FOOTER ────────────────────────────────────────── -->
  <div class="footer">
    <a href="http://www.example.com"><img class="logo" src="images/pexels-photo-2417842.jpeg" alt="Your Logo"/></a>
    <p class="copyright">© 2021 Your Brand. All Rights Reserved.</p>
    <div class="social-icons">
      <a href="http://www.example.com"><img src="images/facebook2x.png" alt="Facebook"/></a>
      <a href="http://www.example.com"><img src="images/instagram2x.png" alt="Instagram"/></a>
      <a href="https://www.snapchat.com"><img src="images/snapchat2x.png" alt="Snapchat"/></a>
    </div>
    <div class="bee-credit">
      <a href="https://www.designedwithbee.com/"><img src="images/bee.png" alt="Designed with BEE"/></a>
      <a href="https://www.designedwithbee.com/">Designed with BEE</a>
    </div>
  </div>

</div><!-- /.email-wrap -->

<!-- ═══════════════════════════════════════════════════════
     SHARED EDITABLE LIST UTILITY
     Used by both the basics sentences and the book list.

     EditableList(config) where config = {
       type        : 'sentences' | 'books'   — passed to API
       containerId : id of the container element
       itemTag     : 'div' | 'li'            — wrapper element
       itemClass   : CSS class for the wrapper
       textClass   : CSS class for the display span
       editClass   : CSS class for the edit row
       inputClass  : CSS class for the text input
       btnClass    : CSS class for the save button
       displayStyle: 'inline-block' | 'inline' — restored after edit
       errorHTML   : HTML shown on load failure
     }
     ═══════════════════════════════════════════════════════ -->
<script>

    (function () {
    const kindleDates = [
        { date: '2026-07-28', note: '6 weeks before Kindle sub ends' },
        { date: '2026-08-04', note: '4 weeks before Kindle sub ends' },
        { date: '2026-08-18', note: '2 weeks before Kindle sub ends' },
        { date: '2026-09-01', note: 'End the subscription today!' },
    ];
    const audibleDates = [
        { date: '2026-07-28', note: '8 weeks before Audible sub ends' },
        { date: '2026-08-11', note: '6 weeks before Audible sub ends' },
        { date: '2026-08-25', note: '4 weeks before Audible sub ends' },
        { date: '2026-09-08', note: '2 weeks before Audible sub ends' },
        { date: '2026-09-22', note: 'End sub today (or by Sep 29)' },
    ];

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    function parseDate(str) {
        const [y, m, d] = str.split('-').map(Number);
        const dt = new Date(y, m - 1, d);
        dt.setHours(0, 0, 0, 0);
        return dt;
    }

    function fmt(str) {
        return parseDate(str).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }

    function render(rows, listId) {
        const ul = document.getElementById(listId);
        let nextSet = false;

        rows.forEach(({ date, note }) => {
            const dt     = parseDate(date);
            const isPast = dt < today;
            const isNext = !isPast && !nextSet;
            if (isNext) nextSet = true;

            const li = document.createElement('li');
            li.className = 'date-row' + (isPast ? ' past' : isNext ? ' next-up' : '');
            li.innerHTML = `
                <span class="date-label">${fmt(date)}</span>
                <span class="date-note">${note}</span>
                ${isNext ? '<span class="next-badge">Next</span>' : ''}
            `;
            ul.appendChild(li);
        });
    }

    render(kindleDates, 'kindle-list');
    render(audibleDates, 'audible-list');
})();

   // ===================

  const API = 'api/sentences.php';

  function EditableList(cfg) {
    const container = document.getElementById(cfg.containerId);

    /* ── Load ── */
    async function load() {
      try {
        const res  = await fetch(API + '?type=' + cfg.type);
        const rows = await res.json();
        container.innerHTML = '';
        if (!rows.length) {
          container.innerHTML = cfg.errorHTML;
          return;
        }
        rows.forEach(row => container.appendChild(buildItem(row)));
      } catch (e) {
        container.innerHTML = cfg.errorHTML;
      }
    }

    /* ── Build item ── */
    function buildItem({ id, text }) {
      const wrap = document.createElement(cfg.itemTag);
      wrap.className = cfg.itemClass;

      const span = document.createElement('span');
      // if type begins with "aaa" then use priTextClass, else use textClass
      span.className = text.startsWith('GBC') ? cfg.priTextClass : cfg.textClass;
      span.textContent = text;
      span.title = 'Double-click to edit';
      span.addEventListener('dblclick', () => enterEdit(span, editRow, input));

      const editRow = document.createElement('div');
      editRow.className    = cfg.editClass;
      editRow.style.display = 'none';

      const input = document.createElement('input');
      input.type      = 'text';
      input.className = cfg.inputClass;
      input.value     = text;
      input.addEventListener('keydown', e => {
        if (e.key === 'Enter')  saveBtn.click();
        if (e.key === 'Escape') exitEdit(span, editRow);
      });

      const saveBtn = document.createElement('button');
      saveBtn.className   = cfg.btnClass;
      saveBtn.textContent = 'Save';
      saveBtn.addEventListener('click', () => save(id, input, span, editRow));

      editRow.appendChild(input);
      editRow.appendChild(saveBtn);
      wrap.appendChild(span);
      wrap.appendChild(editRow);
      return wrap;
    }

    function enterEdit(span, editRow, input) {
      span.style.display    = 'none';
      editRow.style.display = 'flex';
      input.focus();
      input.select();
    }

    function exitEdit(span, editRow) {
      editRow.style.display = 'none';
      span.style.display    = cfg.displayStyle;
    }

    /* ── Save ── */
    async function save(id, input, span, editRow) {
      const newText = input.value.trim();
      if (!newText) return;
      const btn = editRow.querySelector('.' + cfg.btnClass);
      btn.textContent = 'Saving…';
      btn.disabled    = true;
      try {
        const res  = await fetch(API, {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify({ type: cfg.type, id, text: newText })
        });
        const data = await res.json();
        if (data.success) {
          span.textContent = newText;
          exitEdit(span, editRow);
        } else {
          alert('Save failed: ' + (data.error || 'unknown error'));
        }
      } catch (e) {
        alert('Network error – could not save.');
      } finally {
        btn.textContent = 'Save';
        btn.disabled    = false;
      }
    }

    load();
  }

  /* ── Init: Basics Sentences ── */
  document.addEventListener('DOMContentLoaded', () => {

    const libraryImages = ['images/library1.png', 'images/library2.png', 'images/library3.png', 'images/library4.png', 'images/library5.png'];
    const randomImage   = libraryImages[Math.floor(Math.random() * libraryImages.length)];

    const panel = document.querySelector('.accordion__panel-inner');
    panel.style.backgroundImage = `
        linear-gradient(rgba(13, 27, 42, 0.01), rgba(13, 27, 42, 0.78)),
        url('${randomImage}')
    `;


    EditableList({
      type        : 'sentences',
      containerId : 'sentences-container',
      itemTag     : 'div',
      itemClass   : 'sentence-item',
      textClass   : 'sentence-text',
      priTextClass   : 'pri-sentence-text',
      editClass   : 'sentence-edit-row',
      inputClass  : 'sentence-input',
      btnClass    : 'save-btn',
      displayStyle: 'inline-block',
      errorHTML   : '<p style="color:#c0392b;">Could not load sentences. Check API connection.</p>'
    });

    /* ── Init: Book List (accordion.php calls this too via DOMContentLoaded) ── */
    EditableList({
      type        : 'books',
      containerId : 'book-list',
      itemTag     : 'li',
      itemClass   : 'book-item',
      textClass   : 'book-text',
      editClass   : 'book-edit-row',
      inputClass  : 'book-input',
      btnClass    : 'book-save-btn',
      displayStyle: 'inline',
      errorHTML   : '<li class="empty">No books found.</li>'
    });


    EditableList({
      type        : 'amazon',
      containerId : 'amazon-list',
      itemTag     : 'li',
      itemClass   : 'amazon-item',
      textClass   : 'amazon-text',
      editClass   : 'amazon-edit-row',
      inputClass  : 'amazon-input',
      btnClass    : 'amazon-save-btn',
      displayStyle: 'inline',
      errorHTML   : '<li class="empty">No amazon items found.</li>'
    });

  });
</script>
</body>
</html>