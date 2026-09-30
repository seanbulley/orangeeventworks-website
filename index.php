<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/includes/environment.php';

function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function posted(string $key): string {
    return trim((string)($_POST[$key] ?? ''));
}

$dbHost = (string)($config['database']['host'] ?? 'localhost');
$dbName = (string)($config['database']['name'] ?? '');
$dbUser = (string)($config['database']['user'] ?? '');
$dbPass = (string)($config['database']['password'] ?? '');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$errors = [];

$allowedInterests = [
    'ORANGE Hub',
    'ORANGE Live',
    'ORANGE Pitch',
    'ORANGE Crew',
    'ORANGE Control',
    'ORANGE Kit',
    'ORANGE Talent',
    'The wider EventWorks ecosystem',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = posted('name');
    $email = posted('email');
    $organisation = posted('organisation');
    $notes = posted('notes');
    $honeypot = posted('website');
    $consent = isset($_POST['consent']);
    $csrf = (string)($_POST['csrf_token'] ?? '');
    $submittedInterests = $_POST['interests'] ?? [];

    if (!hash_equals((string)$_SESSION['csrf_token'], $csrf)) {
        $errors[] = 'Your session expired. Please refresh the page and try again.';
    }

    if ($honeypot !== '') {
        $errors[] = 'We could not process that submission.';
    }

    $lastSubmit = (int)($_SESSION['last_submit_at'] ?? 0);
    if ($lastSubmit > 0 && (time() - $lastSubmit) < 8) {
        $errors[] = 'Please wait a few seconds before submitting again.';
    }

    if ($name === '' || mb_strlen($name) > 120) {
        $errors[] = 'Please enter your name.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (mb_strlen($organisation) > 160) {
        $errors[] = 'Organisation name is too long.';
    }

    if (mb_strlen($notes) > 1000) {
        $errors[] = 'Please keep your note under 1,000 characters.';
    }

    if (!$consent) {
        $errors[] = 'Please confirm that we can contact you about ORANGE EventWorks.';
    }

    if (!is_array($submittedInterests)) {
        $submittedInterests = [];
    }

    $submittedInterests = array_values(array_intersect(
        $allowedInterests,
        array_map('strval', $submittedInterests)
    ));

    if (!$errors) {
        if ($dbName === '' || $dbUser === '' || $dbName === 'CHANGE_ME' || $dbUser === 'CHANGE_ME') {
            $errors[] = 'The waiting list is not configured yet. Please try again shortly.';
        } else {
            try {
                $dsn = sprintf(
                    'mysql:host=%s;dbname=%s;charset=utf8mb4',
                    $dbHost,
                    $dbName
                );

                $pdo = new PDO($dsn, $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);

                $pdo->exec(
                    'CREATE TABLE IF NOT EXISTS waiting_list (
                        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(120) NOT NULL,
                        email VARCHAR(254) NOT NULL,
                        organisation VARCHAR(160) NULL,
                        interests VARCHAR(500) NULL,
                        notes TEXT NULL,
                        environment VARCHAR(20) NOT NULL,
                        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        UNIQUE KEY uniq_waiting_email (email)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
                );

                $statement = $pdo->prepare(
                    'INSERT INTO waiting_list
                        (name, email, organisation, interests, notes, environment)
                     VALUES
                        (:name, :email, :organisation, :interests, :notes, :environment)
                     ON DUPLICATE KEY UPDATE
                        name = VALUES(name),
                        organisation = VALUES(organisation),
                        interests = VALUES(interests),
                        notes = VALUES(notes),
                        environment = VALUES(environment),
                        updated_at = CURRENT_TIMESTAMP'
                );

                $statement->execute([
                    ':name' => $name,
                    ':email' => strtolower($email),
                    ':organisation' => $organisation !== '' ? $organisation : null,
                    ':interests' => $submittedInterests ? implode(', ', $submittedInterests) : null,
                    ':notes' => $notes !== '' ? $notes : null,
                    ':environment' => $isDemo ? 'demo' : 'production',
                ]);

                $_SESSION['last_submit_at'] = time();
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'message' => "You're on the list. We'll send more information as ORANGE EventWorks develops.",
                ];

                header('Location: ' . $siteBase . '/#waitlist', true, 303);
                exit;
            } catch (Throwable $exception) {
                error_log('EventWorks waiting list error: ' . $exception->getMessage());
                $errors[] = 'We could not save your details just now. Please try again shortly.';
            }
        }
    }
}

$selectedInterests = $_POST['interests'] ?? [];
if (!is_array($selectedInterests)) {
    $selectedInterests = [];
}
?>
<!doctype html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#101A3E">
    <meta name="description" content="ORANGE EventWorks is one operating ecosystem for live events: connected software, shared equipment and talent services built around how events actually run.">
    <?php if ($isDemo): ?><meta name="robots" content="noindex,nofollow,noarchive,nosnippet"><?php endif; ?>
    <title>ORANGE EventWorks | One operating ecosystem for live events</title>

    <link rel="icon" href="<?= e($assetBase) ?>/orange-icon.png" type="image/png">
    <link rel="apple-touch-icon" href="<?= e($assetBase) ?>/orange-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e($assetBase) ?>/site.css">
    <script src="<?= e($assetBase) ?>/site.js" defer></script>
</head>
<body>

<?php require __DIR__ . '/includes/header.php'; ?>

<main>
    <section class="hero">
        <div class="hero-grid"></div>
        <div class="orbit one" data-parallax="-0.05"></div>
        <div class="orbit two" data-parallax="-0.09"></div>
        <div class="orbit three" data-parallax="0.04"></div>

        <div class="wrap hero-inner">
            <div>
                <div class="eyebrow">ORANGE EventWorks</div>
                <h1>One operating ecosystem for <span>live events.</span></h1>
                <p class="hero-lead">Connected software, shared equipment and talent services, built around the real operational problems event teams deal with every day.</p>
                <div class="hero-actions">
                    <a class="button" href="#projects">Explore the projects →</a>
                    <a class="button secondary" href="#waitlist">Join the waiting list</a>
                </div>
            </div>

            <aside class="hero-panel">
                <small>One ecosystem</small>
                <strong>Technology, kit and people that work together.</strong>
                <ul>
                    <li><b>ORANGE Hub</b> — one account for four operational workflows.</li>
                    <li><b>ORANGE Kit</b> — shared professional equipment for community events.</li>
                    <li><b>ORANGE Talent</b> — artist and talent booking for live events.</li>
                </ul>
            </aside>
        </div>
    </section>

    <section class="story">
        <div class="wrap">
            <div class="section-head">
                <div class="eyebrow">Why EventWorks exists</div>
                <h2>Events still run on too many disconnected tools.</h2>
                <p>Stages, traders, staffing, incident logs, equipment and talent are often managed across separate forms, spreadsheets, point solutions and messaging apps. ORANGE EventWorks is being built as a connected alternative.</p>
            </div>

            <div class="story-grid">
                <article class="story-card dark">
                    <div class="signal"></div>
                    <h3>Built by operators.</h3>
                    <p>The products come from real delivery problems, not a theoretical software brief. The aim is to make event-day operations simpler, clearer and easier to scale.</p>
                </article>

                <article class="story-card">
                    <h3>More than one product.</h3>
                    <p>ORANGE EventWorks is the umbrella for a group of connected projects. Software sits at the centre, with equipment and talent services creating a wider operating ecosystem around it.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="ecosystem" id="ecosystem">
        <div class="wrap">
            <div class="section-head">
                <div class="eyebrow">The ecosystem</div>
                <h2>Different parts of event delivery. One ORANGE family.</h2>
                <p>Each project can solve a specific problem on its own, while the wider group is designed to create useful connections between technology, equipment and delivery.</p>
            </div>

            <div class="ecosystem-map">
                <article class="eco-node">
                    <span>Equipment</span>
                    <div>
                        <h3>ORANGE Kit</h3>
                        <p>Shared, professional event equipment at community-focused rates.</p>
                    </div>
                </article>

                <article class="eco-node primary">
                    <span>Core platform</span>
                    <div>
                        <h3>ORANGE Hub</h3>
                        <p>One login connecting stage management, traders, staffing and event control.</p>
                    </div>
                </article>

                <article class="eco-node">
                    <span>People</span>
                    <div>
                        <h3>ORANGE Talent</h3>
                        <p>Artist and talent booking designed around the needs of live events.</p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="hub-section" id="hub">
        <div class="wrap hub-layout">
            <div class="hub-copy">
                <div class="eyebrow">ORANGE Hub</div>
                <h2>One login. Four operational workflows.</h2>
                <p>ORANGE Hub is the core scalable platform: four focused products that can work independently or together under one account.</p>
            </div>

            <div class="module-grid">
                <article class="module">
                    <span class="code">Stages</span>
                    <h3>ORANGE Live</h3>
                    <p>Run sheets, artists, schedules, advancing, stage calls, guest information and live show management.</p>
                </article>

                <article class="module">
                    <span class="code">Traders</span>
                    <h3>ORANGE Pitch</h3>
                    <p>Applications, approvals, invoicing, documents, compliance, pitch allocation, communications and check-in.</p>
                </article>

                <article class="module">
                    <span class="code">Teams</span>
                    <h3>ORANGE Crew</h3>
                    <p>Recruitment, approvals, training, rotas, accreditation, briefings, sign-in and emergency check-ins.</p>
                </article>

                <article class="module">
                    <span class="code">Operations</span>
                    <h3>ORANGE Control</h3>
                    <p>Incident logging, operational timelines, radio traffic, tasking, escalation records and post-event reporting.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="projects" id="projects">
        <div class="wrap">
            <div class="section-head">
                <div class="eyebrow">Beyond the software</div>
                <h2>The wider EventWorks projects.</h2>
                <p>ORANGE Hub is the software core, but the EventWorks brand also brings together practical services that event organisers repeatedly need.</p>
            </div>

            <div class="project-grid">
                <article class="project-card kit">
                    <span class="tag">Community equipment</span>
                    <h3 class="sr-only">ORANGE Kit</h3>
                    <img class="project-logo" src="<?= e($assetBase) ?>/orange-kit-logo.png" alt="ORANGE Kit">
                    <p>A shared pool of professional, event-ready equipment for Pride and community organisations, reducing the need for every event to buy the same specialist kit for a handful of days each year.</p>
                    <a class="project-link" href="https://orangekit.co.uk/" target="_blank" rel="noopener noreferrer">Visit ORANGE Kit →</a>
                </article>

                <article class="project-card talent">
                    <span class="tag">Artist & talent booking</span>
                    <h3 class="sr-only">ORANGE Talent</h3>
                    <img class="project-logo" src="<?= e($assetBase) ?>/orange-talent-logo.png" alt="ORANGE Talent">
                    <p>A talent and artist booking service designed for events, helping organisers find, book and manage performers with the wider operational context in mind.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="audience">
        <div class="wrap">
            <div class="section-head">
                <div class="eyebrow">Where we start</div>
                <h2>Built from Pride. Designed to go wider.</h2>
                <p>Pride organisers are the first focused audience because they often run complex, volunteer-heavy events with stages, traders, temporary teams and significant control-room needs. The same problems exist well beyond Pride.</p>
            </div>

            <div class="audience-row" aria-label="EventWorks audiences">
                <span class="audience-chip">Pride organisations</span>
                <span class="audience-chip">Festivals</span>
                <span class="audience-chip">Council events</span>
                <span class="audience-chip">Community events</span>
                <span class="audience-chip">Venues</span>
                <span class="audience-chip">Event agencies</span>
                <span class="audience-chip">Production companies</span>
            </div>
        </div>
    </section>

    <section class="waitlist" id="waitlist">
        <div class="wrap">
            <div class="waitlist-shell">
                <div class="waitlist-copy">
                    <div class="eyebrow">Details coming soon</div>
                    <h2>Want to hear when EventWorks opens up?</h2>
                    <p>We're still building. Join the waiting list and we'll keep you updated as the products, demos and launch plans become ready to share.</p>
                </div>

                <div class="form-card">
                    <?php if ($flash && ($flash['type'] ?? '') === 'success'): ?>
                        <div class="alert success"><?= e((string)$flash['message']) ?></div>
                    <?php endif; ?>

                    <?php if ($errors): ?>
                        <div class="alert error">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?= e($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?= e($siteBase) ?>/#waitlist" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= e((string)$_SESSION['csrf_token']) ?>">

                        <div class="honeypot" aria-hidden="true">
                            <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                        </div>

                        <div class="form-grid">
                            <div class="field">
                                <label for="name">Name</label>
                                <input id="name" name="name" type="text" maxlength="120" autocomplete="name" required value="<?= e(posted('name')) ?>">
                            </div>

                            <div class="field">
                                <label for="email">Email</label>
                                <input id="email" name="email" type="email" maxlength="254" autocomplete="email" required value="<?= e(posted('email')) ?>">
                            </div>

                            <div class="field full">
                                <label for="organisation">Organisation <span style="font-weight:500;color:#667085">(optional)</span></label>
                                <input id="organisation" name="organisation" type="text" maxlength="160" autocomplete="organization" value="<?= e(posted('organisation')) ?>">
                            </div>

                            <div class="field full">
                                <div class="fieldset-label">What are you interested in?</div>
                                <div class="check-grid">
                                    <?php foreach ($allowedInterests as $interest): ?>
                                        <label class="check">
                                            <input type="checkbox" name="interests[]" value="<?= e($interest) ?>" <?= in_array($interest, $selectedInterests, true) ? 'checked' : '' ?>>
                                            <span><?= e($interest) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="field full">
                                <label for="notes">Anything useful to know? <span style="font-weight:500;color:#667085">(optional)</span></label>
                                <textarea id="notes" name="notes" maxlength="1000" placeholder="Tell us about your organisation, event or what you'd like to see."><?= e(posted('notes')) ?></textarea>
                            </div>

                            <div class="field full">
                                <label class="consent">
                                    <input type="checkbox" name="consent" value="1" <?= isset($_POST['consent']) ? 'checked' : '' ?> required>
                                    <span>I agree that ORANGE EventWorks can use these details to contact me about the project and future launch information.</span>
                                </label>
                            </div>

                            <div class="field full">
                                <button class="button" type="submit">Join the waiting list →</button>
                                <p class="form-note">No spam. Just updates when there is something useful to share.</p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>

<footer>
    <div class="wrap">
        <div class="footer-row">
            <div class="footer-brand">
                <a class="brand-lockup" href="<?= e($siteBase) ?>/" aria-label="ORANGE EventWorks home">
                    <img class="brand-logo" src="<?= e($assetBase) ?>/orange-eventworks-logo.png" alt="ORANGE EventWorks">
                </a>
                <p>One operating ecosystem for live events. Details coming soon.</p>
            </div>
            <p>ORANGE Hub · ORANGE Kit · ORANGE Talent</p>
        </div>
        <div class="footer-small">© <?= date('Y') ?> ORANGE EventWorks. Website preview and waiting list.</div>
    </div>
</footer>

</body>
</html>
