<?php
require_once __DIR__ . '/environment.php';
$escapedSiteBase = htmlspecialchars($siteBase, ENT_QUOTES, 'UTF-8');
$escapedAssetBase = htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8');
?>
<header class="site-header">
    <div class="wrap nav">
        <a class="brand-lockup" href="<?= $escapedSiteBase ?>/" aria-label="ORANGE EventWorks home">
            <span class="brand-mark" aria-hidden="true"><span></span></span>
            <span class="brand-words"><strong>ORANGE</strong><em>EventWorks</em></span>
        </a>

        <nav class="nav-links" id="navLinks" aria-label="Primary navigation">
            <a href="#ecosystem">The ecosystem</a>
            <a href="#hub">Orange Hub</a>
            <a href="#projects">Projects</a>
            <a href="#waitlist" class="nav-cta">Join the waiting list</a>
        </nav>

        <button class="menu-btn" id="menuBtn" type="button" aria-expanded="false" aria-controls="navLinks" aria-label="Open menu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M4 7h16M4 12h16M4 17h16"/>
            </svg>
        </button>
    </div>
</header>
