<?php
$pageTitle = "Race Calendar";

require_once __DIR__ . '/includes/header.php';

/* =========================================================
   FILTER + SEARCH
========================================================= */

$filter = strtoupper(trim($_GET['filter'] ?? 'ALL'));
$search = trim($_GET['search'] ?? '');

$allowedFilters = ['ALL', 'UPCOMING', 'COMPLETED'];

if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'ALL';
}


/* =========================================================
   GET ALL RACES
========================================================= */

$sql = "SELECT * FROM races WHERE 1=1";
$params = [];


/* Status filter */

if ($filter === 'UPCOMING') {
    $sql .= " AND status = 'Upcoming'";
}

if ($filter === 'COMPLETED') {
    $sql .= " AND status = 'Completed'";
}


/* Search */

if ($search !== '') {

    $sql .= " AND (
        grand_prix LIKE :search
        OR country LIKE :search
        OR circuit LIKE :search
    )";

    $params[':search'] = '%' . $search . '%';
}


$sql .= " ORDER BY race_date ASC, race_time ASC, id ASC";


$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$races = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   CREATE REAL ROUND NUMBERS
========================================================= */

$roundStmt = $pdo->query("
    SELECT id
    FROM races
    ORDER BY race_date ASC, race_time ASC, id ASC
");

$allRaceIds = $roundStmt->fetchAll(PDO::FETCH_COLUMN);

$roundMap = [];

foreach ($allRaceIds as $index => $raceId) {
    $roundMap[(string)$raceId] = $index + 1;
}


/* =========================================================
   SEASON YEAR
========================================================= */

$seasonYear = date('Y');

if (!empty($races)) {

    $firstDate = strtotime($races[0]['race_date']);

    if ($firstDate) {
        $seasonYear = date('Y', $firstDate);
    }
}


/* =========================================================
   FEATURED RACE
========================================================= */

$featuredRace = null;
$featuredIndex = null;


/*
   Prefer first Upcoming race.
   If no upcoming race exists,
   use first available race.
*/

foreach ($races as $index => $race) {

    if (strcasecmp($race['status'], 'Upcoming') === 0) {

        $featuredRace = $race;
        $featuredIndex = $index;

        break;
    }
}


if ($featuredRace === null && !empty($races)) {

    $featuredRace = $races[0];
    $featuredIndex = 0;
}


/* =========================================================
   TOTAL RACES
========================================================= */

$totalRaces = count($races);


/* =========================================================
   HELPER
========================================================= */

function race_image($image)
{
    $image = trim((string)$image);

    if ($image === '') {
        return '';
    }

    return $image;
}

?>

<main class="races-page">


    <!-- =====================================================
         HERO
    ====================================================== -->

    <section class="race-hero">

        <div class="race-hero__bg"></div>
        <div class="race-hero__overlay"></div>

        <div class="race-container race-hero__content">


            <!-- HERO TEXT -->

            <div class="race-hero__left">

                <div class="race-eyebrow">

                    <span>FORMULA 1</span>

                    <b>•</b>

                    <span>
                        <?php echo e($seasonYear); ?> SEASON
                    </span>

                </div>


                <h1>
                    RACE
                    <strong>CALENDAR</strong>
                </h1>


                <p>
                    Follow every Grand Prix of the
                    <?php echo e($seasonYear); ?>
                    Formula 1 season, from circuit details
                    to official race results.
                </p>

            </div>


            <!-- FILTER PANEL -->

            <div class="race-filter-panel">


                <!-- TABS -->

                <div class="race-filter-tabs">

                    <a
                        href="races.php?filter=ALL"
                        class="<?php echo $filter === 'ALL' ? 'active' : ''; ?>"
                    >
                        ALL
                    </a>


                    <a
                        href="races.php?filter=UPCOMING"
                        class="<?php echo $filter === 'UPCOMING' ? 'active' : ''; ?>"
                    >
                        UPCOMING
                    </a>


                    <a
                        href="races.php?filter=COMPLETED"
                        class="<?php echo $filter === 'COMPLETED' ? 'active' : ''; ?>"
                    >
                        COMPLETED
                    </a>

                </div>


                <!-- SEARCH -->

                <form
                    method="GET"
                    action="races.php"
                    class="race-search"
                >

                    <input
                        type="hidden"
                        name="filter"
                        value="<?php echo e($filter); ?>"
                    >


                    <span class="race-search__icon">
                        ⌕
                    </span>


                    <input
                        type="text"
                        name="search"
                        value="<?php echo e($search); ?>"
                        placeholder="Search Grand Prix..."
                    >


                    <button type="submit">
                        SEARCH
                    </button>

                </form>

            </div>

        </div>

    </section>
<!-- SEASON STATS STRIP -->
<section class="race-info-strip" aria-label="Race calendar overview">

    <div class="container race-info-grid">

        <!-- =================================================
             RACES SHOWN
        ================================================== -->
        <div class="race-info-item">

            <span class="race-info-icon" aria-hidden="true">
                <svg viewBox="0 0 32 32">
                    <!-- Racing Flag -->
                    <path d="M7 5v23"/>
                    <path d="M8 6h17l-3 5 3 5H8z"/>
                    <path d="M8 6h6v5H8z"/>
                    <path d="M20 11h5"/>
                    <path d="M14 11h6v5h-6z"/>
                </svg>
            </span>

            <div class="race-info-content">
                <strong><?php echo $totalRaces; ?></strong>
                <span>RACES SHOWN</span>
            </div>

        </div>


        <!-- =================================================
             F1 SEASON
        ================================================== -->
        <div class="race-info-item">

            <span class="race-info-icon" aria-hidden="true">
                <svg viewBox="0 0 32 32">
                    <!-- Season / Championship -->
                    <circle cx="16" cy="16" r="10"/>
                    <circle cx="16" cy="16" r="4"/>
                    <path d="M16 6v6"/>
                    <path d="M16 20v6"/>
                    <path d="M6 16h6"/>
                    <path d="M20 16h6"/>
                </svg>
            </span>

            <div class="race-info-content">
                <strong><?php echo e($seasonYear); ?></strong>
                <span>F1 SEASON</span>
            </div>

        </div>


        <!-- =================================================
             OFFICIAL GRAND PRIX SCHEDULE
        ================================================== -->
        <div class="race-info-item race-info-item--schedule">

            <span class="race-info-icon" aria-hidden="true">
                <svg viewBox="0 0 32 32">
                    <!-- Racing Helmet -->
                    <path d="M7 21c0-7 4-13 10-13s10 5 10 12v3H11c-2 0-4-1-4-2z"/>
                    <path d="M8 20h14l5 3"/>
                    <path d="M12 14h13"/>
                    <path d="M17 8v6"/>
                    <path d="M11 24v4h11"/>
                </svg>
            </span>

            <div class="race-info-content">
                <strong >OFFICIAL GRAND PRIX SCHEDULE</strong>
                <span >CIRCUIT INFORMATION • RACE RESULTS</span>
            </div>

        </div>

    </div>

</section>


    <!-- =====================================================
         FEATURED RACE
    ====================================================== -->

    <?php if ($featuredRace !== null): ?>

        <?php

        $featuredId = $featuredRace['id'];

        $featuredRound =
            $roundMap[(string)$featuredId] ?? 1;

        $featuredStatus =
            strtoupper($featuredRace['status']);

       $featuredImage = trim($featuredRace['circuit_image'] ?? '');

if ($featuredImage !== '') {
    if (
        strpos($featuredImage, 'http://') !== 0 &&
        strpos($featuredImage, 'https://') !== 0 &&
        strpos($featuredImage, '/') !== 0
    ) {
        $featuredImage = 'assets/images/circuit_image/' . basename($featuredImage);
    }
}
        $featuredDate =
            !empty($featuredRace['race_date'])
                ? format_date($featuredRace['race_date'])
                : '';

        $featuredTime =
            !empty($featuredRace['race_time'])
                ? format_time($featuredRace['race_time'])
                : '';

        ?>


        <section class="race-container race-featured-section">

            <article class="featured-race">


                <!-- IMAGE -->

                <div class="featured-race__image">

                    <?php if ($featuredImage !== ''): ?>

                        <img
                            src="<?php echo e($featuredImage); ?>"
                            alt="<?php echo e($featuredRace['grand_prix']); ?>"
                        >

                    <?php endif; ?>

                </div>


                <div class="featured-race__image-vignette"></div>


                <div class="featured-race__shade"></div>


                <!-- RED EDGE -->

                <div class="featured-race__red-edge"></div>


                <!-- HUGE NUMBER -->

                <div class="featured-race__number">

                    <?php
                    echo str_pad(
                        $featuredRound,
                        2,
                        '0',
                        STR_PAD_LEFT
                    );
                    ?>

                </div>


                <!-- TOP -->

                <div class="featured-race__top">


                    <span class="next-race-label">

                        <?php

                        echo $featuredStatus === 'UPCOMING'
                            ? 'NEXT RACE'
                            : 'FEATURED RACE';

                        ?>

                    </span>


                    <span
                        class="race-status race-status--<?php echo strtolower($featuredStatus); ?>"
                    >

                        <i></i>

                        <?php echo e($featuredStatus); ?>

                    </span>

                </div>



                <!-- CONTENT -->

                <div class="featured-race__content">


                    <div class="featured-race__country">

                        <?php
                        echo e(
                            strtoupper(
                                $featuredRace['country']
                            )
                        );
                        ?>

                    </div>


                    <h2>
                        <?php
                        echo e(
                            $featuredRace['grand_prix']
                        );
                        ?>
                    </h2>


                    <div class="featured-race__circuit">

                        <span>●</span>

                        <?php
                        echo e(
                            $featuredRace['circuit']
                        );
                        ?>

                    </div>


                    <div class="featured-race__meta">


                        <?php if ($featuredDate !== ''): ?>

                            <span>
                                ▣
                                <?php echo e($featuredDate); ?>
                            </span>

                        <?php endif; ?>


                        <?php if ($featuredTime !== ''): ?>

                            <span>
                                ◷
                                <?php echo e($featuredTime); ?>
                            </span>

                        <?php endif; ?>


                    </div>



                    <?php if (!empty($featuredRace['description'])): ?>

                        <p>
                            <?php
                            echo e(
                                $featuredRace['description']
                            );
                            ?>
                        </p>

                    <?php endif; ?>



                    <div class="featured-race__actions">


                        <a
                            href="race-details.php?id=<?php echo (int)$featuredRace['id']; ?>"
                            class="race-btn race-btn--primary"
                        >

                            RACE DETAILS

                            <span>→</span>

                        </a>



                        <?php if ($featuredStatus === 'COMPLETED'): ?>

                            <a
                                href="results.php?race_id=<?php echo (int)$featuredRace['id']; ?>"
                                class="race-btn race-btn--secondary"
                            >
                                VIEW RESULTS
                            </a>

                        <?php else: ?>

                            <a
                                href="races.php"
                                class="race-btn race-btn--secondary"
                            >
                                VIEW ALL RACES
                            </a>

                        <?php endif; ?>


                    </div>

                </div>



                <!-- ROUND -->

                <div class="featured-race__round">

                    ROUND

                    <?php
                    echo str_pad(
                        $featuredRound,
                        2,
                        '0',
                        STR_PAD_LEFT
                    );
                    ?>

                </div>


            </article>

        </section>

    <?php endif; ?>



    <!-- =====================================================
         ALL RACES
    ====================================================== -->

    <section class="race-container races-list-section">


        <div class="races-section-heading">


            <div>

                <span>
                    <?php echo e($seasonYear); ?> SEASON
                </span>

                <h2>
                    ALL
                    <strong>GRAND PRIX</strong>
                </h2>

            </div>



            <div class="races-count">

                <?php echo $totalRaces; ?>

                RACES

                <span>
                    →
                </span>

            </div>


        </div>



        <!-- =================================================
             RACE GRID
        ================================================== -->

        <?php if (!empty($races)): ?>


            <div class="race-grid">


                <?php foreach ($races as $index => $race): ?>


                    <?php

                    /*
                     * Don't show featured race again
                     */

                    if (
                        $featuredIndex !== null &&
                        $index === $featuredIndex
                    ) {
                        continue;
                    }


                    $raceId = $race['id'];


                    $round =
                        $roundMap[(string)$raceId]
                        ?? ($index + 1);


                    $status =
                        strtoupper($race['status']);


                    $image = trim($race['circuit_image'] ?? '');

if ($image !== '') {

    // Agar database mein sirf filename hai
    if (
        strpos($image, 'http://') !== 0 &&
        strpos($image, 'https://') !== 0 &&
        strpos($image, '/') !== 0
    ) {
        $image = 'assets/images/circuit_image/' . basename($image);
    }
}


                    $date =
                        !empty($race['race_date'])
                            ? format_date($race['race_date'])
                            : '';


                    $time =
                        !empty($race['race_time'])
                            ? format_time($race['race_time'])
                            : '';

                    ?>


                    <!-- =================================================
                         RACE CARD
                    ================================================== -->

                    <article
                        class="race-card race-card--<?php echo strtolower($status); ?>"
                    >


                        <!-- IMAGE -->

                        <div class="race-card__image">

                            <?php if ($image !== ''): ?>

                                <img
                                    src="<?php echo e($image); ?>"
                                    alt="<?php echo e($race['grand_prix']); ?>"
                                    loading="lazy"
                                >

                            <?php endif; ?>

                        </div>


                        <!-- CINEMATIC OVERLAY -->

                        <div class="race-card__shade"></div>

                        <div class="race-card__vignette"></div>



                        <!-- RED ANGULAR CORNER -->

                        <div class="race-card__corner-glow"></div>


                        <!-- RED LEFT EDGE -->

                        <div class="race-card__side-accent"></div>



                        <!-- HUGE ROUND -->

                        <div class="race-card__ghost-number">

                            <?php
                            echo str_pad(
                                $round,
                                2,
                                '0',
                                STR_PAD_LEFT
                            );
                            ?>

                        </div>



                        <!-- TOP -->

                        <div class="race-card__top">


                            <span class="race-round">

                                ROUND

                                <?php
                                echo str_pad(
                                    $round,
                                    2,
                                    '0',
                                    STR_PAD_LEFT
                                );
                                ?>

                            </span>



                            <span
                                class="race-status race-status--<?php echo strtolower($status); ?>"
                            >

                                <i></i>

                                <?php echo e($status); ?>

                            </span>


                        </div>



                        <!-- CONTENT -->

                        <div class="race-card__content">


                            <!-- COUNTRY -->

                            <div class="race-card__country">

                                <?php
                                echo e(
                                    strtoupper(
                                        $race['country']
                                    )
                                );
                                ?>

                            </div>



                            <!-- TITLE -->

                            <h3>

                                <?php
                                echo e(
                                    $race['grand_prix']
                                );
                                ?>

                            </h3>



                            <!-- CIRCUIT -->

                            <div class="race-card__circuit">

                                <span>●</span>

                                <?php
                                echo e(
                                    $race['circuit']
                                );
                                ?>

                            </div>



                            <!-- BOTTOM -->

                            <div class="race-card__bottom">


                                <div class="race-card__date">


                                    <?php if ($date !== ''): ?>

                                        <span>
                                            ▣
                                            <?php echo e($date); ?>
                                        </span>

                                    <?php endif; ?>


                                    <?php if ($time !== ''): ?>

                                        <span>
                                            ◷
                                            <?php echo e($time); ?>
                                        </span>

                                    <?php endif; ?>


                                </div>



                                <!-- ACTUAL BUTTON -->

                                <?php if ($status === 'COMPLETED'): ?>

                                    <a
                                        href="results.php?race_id=<?php echo (int)$raceId; ?>"
                                        class="race-card__link"
                                    >

                                        <span class="race-card__link-text">
                                            RESULTS
                                        </span>

                                        <span class="race-card__link-arrow">
                                            →
                                        </span>

                                    </a>

                                <?php else: ?>

                                    <a
                                        href="race-details.php?id=<?php echo (int)$raceId; ?>"
                                        class="race-card__link"
                                    >

                                        <span class="race-card__link-text">
                                            DETAILS
                                        </span>

                                        <span class="race-card__link-arrow">
                                            →
                                        </span>

                                    </a>

                                <?php endif; ?>


                            </div>


                        </div>



                        <!-- BOTTOM RED LINE -->

                        <div class="race-card__accent"></div>


                    </article>


                <?php endforeach; ?>


            </div>



        <?php else: ?>


            <!-- EMPTY STATE -->

            <div class="races-empty">


                <div class="races-empty__icon">
                    ⌕
                </div>


                <h3>
                    No races found
                </h3>


                <p>
                    Try another search or change the race filter.
                </p>


                <a href="races.php">
                    RESET FILTERS →
                </a>


            </div>


        <?php endif; ?>


    </section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

</main>





<style>

/* =========================================================
   FORMULAHUB — PREMIUM RACE CALENDAR
========================================================= */

.races-page {
    --race-red: #ff1717;
    --race-red-bright: #ff2a2a;
    --race-red-dark: #c90000;

    --race-black: #040507;
    --race-dark: #07090d;
    --race-panel: #0b0e13;

    --race-border: rgba(255,255,255,.12);
    --race-muted: #858c96;
    --race-white: #ffffff;

    min-height: 100vh;
    overflow: hidden;

    color: var(--race-white);

    background:
        radial-gradient(
            circle at 50% 8%,
            rgba(255,20,20,.075),
            transparent 35%
        ),
        radial-gradient(
            circle at 10% 80%,
            rgba(255,20,20,.035),
            transparent 30%
        ),
        #050608;
}


/* =========================================================
   CONTAINER
========================================================= */

.race-container {
    width: var(--container-width);
    max-width: var(--container-max);
    margin: 0 auto;
    padding: 0 var(--container-padding);
}


/* =========================================================
   HERO
========================================================= */

.race-hero {
    position: relative;

    min-height: 340px;

    padding: 42px 0 38px;

    display: flex;
    align-items: center;

    overflow: hidden;

    border-bottom: 1px solid rgba(255,20,20,.35);
}


.race-hero__bg {
    position: absolute;
    inset: 0;

    background:
        linear-gradient(
            90deg,
            rgba(3,5,8,.99) 0%,
            rgba(3,5,8,.84) 32%,
            rgba(3,5,8,.30) 72%,
            rgba(3,5,8,.72) 100%
        ),
        url("assets/images/race_hero.png")
        center / cover no-repeat;

    transform: scale(1.025);

    filter: saturate(1.08);
}


.race-hero__overlay {
    position: absolute;
    inset: 0;

    background:
        linear-gradient(
            180deg,
            rgba(0,0,0,.05),
            rgba(0,0,0,.72)
        );

    pointer-events: none;
}


.race-hero__content {
    position: relative;
    z-index: 2;

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 36px;

    padding-top: 10px;
    padding-bottom: 10px;
}


/* =========================================================
   HERO TEXT
========================================================= */

.race-hero__left {
    max-width: 590px;
}


.race-eyebrow {
    display: flex;
    align-items: center;

    gap: 8px;

    margin-bottom: 12px;

    color: #cbd0d7;

    font-size: 0.75rem;
    font-weight: 800;

    letter-spacing: 2px;
    text-transform: uppercase;
}


.race-eyebrow b {
    color: var(--race-red);
    font-size: 14px;
}


.race-hero h1 {
    margin: 0;

    font-size: clamp(2.4rem, 4vw, 3.4rem);

    line-height: 1.05;

    font-weight: 900;
    font-style: italic;

    letter-spacing: -0.02em;

    text-transform: uppercase;
}


.race-hero h1 strong {
    display: block;

    color: var(--race-red);

    font-weight: 900;

    text-shadow:
        0 0 35px
        rgba(255,20,20,.12);
}


.race-hero__left p {
    max-width: 520px;

    margin: 12px 0 0;

    color: var(--text-muted);

    font-size: 0.95rem;
    line-height: 1.6;
}
    line-height: 1.7;
}


/* =========================================================
   FILTER PANEL
========================================================= */

.race-filter-panel {
    width: 390px;

    padding: 14px;

    background:
        linear-gradient(
            145deg,
            rgba(20,23,29,.94),
            rgba(5,7,10,.97)
        );

    border: 1px solid rgba(255,255,255,.12);

    border-radius: 10px;

    box-shadow:
        0 25px 70px rgba(0,0,0,.6),
        0 0 35px rgba(255,20,20,.025),
        inset 0 1px rgba(255,255,255,.05);

    backdrop-filter: blur(15px);
}


.race-filter-tabs {
    display: grid;

    grid-template-columns: repeat(3,1fr);

    gap: 5px;

    padding: 4px;

    background: #07090d;

    border: 1px solid rgba(255,255,255,.08);

    border-radius: 6px;
}


.race-filter-tabs a {
    padding: 11px 8px;

    color: #9ba1a9;

    text-align: center;
    text-decoration: none;

    font-size: 10px;
    font-weight: 900;

    letter-spacing: .8px;

    border-radius: 4px;

    transition:
        color .25s ease,
        background .25s ease,
        box-shadow .25s ease;
}


.race-filter-tabs a:hover {
    color: #fff;
}


.race-filter-tabs a.active {
    color: #fff;

    background: var(--race-red);

    box-shadow:
        0 5px 20px rgba(255,23,23,.25),
        inset 0 1px rgba(255,255,255,.15);
}


/* =========================================================
   SEARCH
========================================================= */

.race-search {
    display: flex;

    height: 44px;

    margin-top: 12px;

    overflow: hidden;

    background: #080b10;

    border: 1px solid rgba(255,255,255,.1);

    border-radius: 5px;
}


.race-search__icon {
    width: 42px;

    display: flex;
    align-items: center;
    justify-content: center;

    color: var(--race-red);

    font-size: 20px;
}


.race-search input {
    flex: 1;

    min-width: 0;

    border: 0;
    outline: 0;

    background: transparent;

    color: #fff;

    font-size: 12px;
}


.race-search input::placeholder {
    color: #606873;
}


.race-search button {
    width: 78px;

    border: 0;

    background: var(--race-red);

    color: #fff;

    font-size: 10px;
    font-weight: 900;

    cursor: pointer;

    transition: background .2s ease;
}


.race-search button:hover {
    background: #ff3333;
}


/* =========================================================
   RACE INFO STRIP
   CLEAN 3-COLUMN VERSION
========================================================= */

.race-info-strip {
    position: relative;

    background: #05070b;

    border-bottom: 1px solid var(--border-color);

    overflow: hidden;

    margin: 0;
}




/* 3 EQUAL COLUMNS */

.race-info-grid {
    min-height: 90px;

    display: grid;

    grid-template-columns:
        repeat(3, minmax(0, 1fr));

    align-items: center;
}


/* EACH COLUMN */

.race-info-item {
    min-height: 58px;

    display: flex;

    align-items: center;

    justify-content: flex-start;

    gap: 14px;

    padding: 0 35px;

    border-right: 1px solid #30343d;

    box-sizing: border-box;
}


.race-info-item:last-child {
    border-right: none;
}


/* ICON */

.race-info-icon {
    width: 30px;
    height: 30px;

    display: flex;

    align-items: center;
    justify-content: center;

    flex-shrink: 0;

    color: #ffffff;

    filter:
        grayscale(1)
        brightness(1.8);
}


.race-info-icon svg {
    width: 29px;
    height: 29px;

    fill: none;

    stroke: currentColor;

    stroke-width: 1.6;

    stroke-linecap: round;
    stroke-linejoin: round;
}


/* TEXT */

.race-info-content {
    min-width: 0;

    display: flex;

    flex-direction: column;
}


.race-info-content strong {
    display: block;

    font-family: var(--font-heading);

    font-size: 1.65rem;

    line-height: 1;

    color: #ffffff;
}


.race-info-content span {
    display: block;

    color: #9da3af;

    font-size: .65rem;

    font-weight: 700;

    margin-top: 4px;

    line-height: 1.2;
}


/* THIRD COLUMN */

.race-info-item--schedule {
    gap: 14px;
}


.race-info-item--schedule
.race-info-content strong {
    font-family: var(--font-heading);

    font-size: .90rem;

    line-height: 1.15;

    letter-spacing: .01em;
}


.race-info-item--schedule
.race-info-content span {
    color: #9da3af;

    font-family: var(--font-heading);

    font-size: .64rem;

    font-weight: 700;

    margin-top: 4px;

    white-space: nowrap;
}


/* SUBTLE HOVER */

.race-info-item {
    transition: background .25s ease;
}


.race-info-item:hover {
    background: rgba(255,255,255,.015);
}


/* =========================================================
   GAP AFTER INFO STRIP
========================================================= */

.race-featured-section {
    margin-top: 24px;
}


/* =========================================================
   FEATURED RACE
========================================================= */

.featured-race {
    position: relative;

    min-height: 330px;

    overflow: hidden;

    background: #080a0d;

    border: 1px solid rgba(255,28,28,.48);

    border-radius: 7px;

    box-shadow:
        0 25px 60px rgba(0,0,0,.45),
        0 0 35px rgba(255,20,20,.045);
}


.featured-race::before {
    content: "";

    position: absolute;

    left: -1px;

    top: 0;
    bottom: 0;

    width: 4px;

    background:
        linear-gradient(
            180deg,
            #ff3434,
            #ff0000 50%,
            #c50000
        );

    z-index: 10;

    box-shadow:
        0 0 18px
        rgba(255,20,20,.35);
}


.featured-race__image {
    position: absolute;

    inset: 0;

    width: 57%;

    overflow: hidden;
}


.featured-race__image img {
    width: 100%;
    height: 100%;

    object-fit: cover;

    transition:
        transform .8s ease,
        filter .8s ease;
}


.featured-race:hover
.featured-race__image img {
    transform: scale(1.055);

    filter: saturate(1.1);
}


.featured-race__image::after {
    content: "";

    position: absolute;

    inset: 0;

    background:
        linear-gradient(
            90deg,
            transparent 42%,
            rgba(7,9,12,.98) 100%
        );
}


.featured-race__image-vignette {
    position: absolute;

    inset: 0;

    z-index: 1;

    pointer-events: none;

    box-shadow:
        inset 0 0 90px
        rgba(0,0,0,.65);
}


.featured-race__shade {
    position: absolute;

    inset: 0;

    background:
        linear-gradient(
            90deg,
            transparent 18%,
            rgba(5,7,10,.72) 53%,
            #080a0d 79%
        );

    z-index: 2;
}


.featured-race__red-edge {
    position: absolute;

    left: 0;
    bottom: 0;

    width: 48%;
    height: 2px;

    background:
        linear-gradient(
            90deg,
            #ff1717,
            transparent
        );

    z-index: 8;

    box-shadow:
        0 0 15px
        rgba(255,23,23,.55);
}


/* =========================================================
   FEATURED NUMBER
========================================================= */

.featured-race__number {
    position: absolute;

    left: 40px;
    bottom: 12px;

    font-size: 170px;

    line-height: 1;

    font-weight: 900;

    letter-spacing: -12px;

    color: rgba(255,255,255,.11);

    z-index: 2;

    pointer-events: none;
}


/* =========================================================
   FEATURED TOP
========================================================= */

.featured-race__top {
    position: absolute;

    top: 18px;

    left: 38px;
    right: 20px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    z-index: 6;
}


.next-race-label {
    padding: 7px 13px;

    color: #fff;

    border: 1px solid rgba(255,255,255,.18);

    background: rgba(0,0,0,.55);

    border-radius: 3px;

    font-size: 9px;

    font-weight: 900;

    letter-spacing: 1px;

    backdrop-filter: blur(6px);
}


/* =========================================================
   STATUS
========================================================= */

.race-status {
    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding: 7px 10px;

    background: rgba(0,0,0,.65);

    border: 1px solid rgba(255,255,255,.12);

    border-radius: 4px;

    color: #fff;

    font-size: 8px;

    font-weight: 900;

    letter-spacing: .6px;

    backdrop-filter: blur(6px);
}


.race-status i {
    width: 6px;
    height: 6px;

    display: block;

    flex-shrink: 0;

    border-radius: 50%;

    background: var(--race-red);

    box-shadow:
        0 0 8px
        rgba(255,23,23,.8);
}


.race-status--completed {
    border-color:
        rgba(0,220,120,.3);
}


.race-status--completed i {
    background: #00d878;

    box-shadow:
        0 0 8px
        rgba(0,216,120,.7);
}


/* =========================================================
   FEATURED CONTENT
========================================================= */

.featured-race__content {
    position: absolute;

    left: 51%;
    right: 38px;

    bottom: 28px;

    z-index: 6;
}


.featured-race__country {
    margin-bottom: 5px;

    color: var(--race-red);

    font-size: 12px;

    font-weight: 900;

    letter-spacing: 1.5px;
}


.featured-race__content h2 {
    margin: 0;

    font-size: clamp(28px, 3vw, 43px);

    line-height: 1;

    font-weight: 900;

    text-transform: uppercase;

    letter-spacing: -1.5px;
}


.featured-race__circuit {
    margin-top: 13px;

    color: #d0d4d9;

    font-size: 12px;
}


.featured-race__circuit span {
    margin-right: 6px;

    color: var(--race-red);
}


.featured-race__meta {
    display: flex;

    gap: 18px;

    margin-top: 14px;

    color: #b5bbc2;

    font-size: 10px;

    font-weight: 700;
}


.featured-race__meta span {
    display: flex;

    align-items: center;

    gap: 6px;
}


.featured-race__content p {
    max-width: 500px;

    margin: 13px 0 0;

    color: #8e959f;

    font-size: 11px;

    line-height: 1.6;
}


/* =========================================================
   FEATURED BUTTONS
========================================================= */

.featured-race__actions {
    display: flex;

    gap: 10px;

    margin-top: 18px;
}


.race-btn {
    min-height: 38px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 10px;

    padding: 0 17px;

    text-decoration: none;

    font-size: 9px;

    font-weight: 900;

    letter-spacing: .5px;

    border-radius: 4px;

    transition:
        transform .25s ease,
        background .25s ease,
        border-color .25s ease;
}


.race-btn--primary {
    color: #fff;

    background: var(--race-red);

    box-shadow:
        0 8px 25px
        rgba(255,20,20,.2);
}


.race-btn--primary:hover {
    background: #ff3333;

    transform: translateY(-2px);
}


.race-btn--secondary {
    color: #dce0e5;

    border: 1px solid rgba(255,255,255,.16);

    background: rgba(255,255,255,.03);
}


.race-btn--secondary:hover {
    color: #fff;

    border-color:
        rgba(255,255,255,.35);

    background:
        rgba(255,255,255,.07);
}


/* =========================================================
   FEATURED ROUND
========================================================= */

.featured-race__round {
    position: absolute;

    left: 38px;
    bottom: 20px;

    padding: 8px 13px;

    color: #fff;

    background:
        rgba(0,0,0,.72);

    border:
        1px solid
        rgba(255,255,255,.15);

    border-radius: 3px;

    font-size: 10px;

    font-weight: 900;

    z-index: 7;
}


/* =========================================================
   SECTION HEADING
========================================================= */

.races-list-section {
    padding-top: 43px;
    padding-bottom: 80px;

    background: transparent;
}


.races-section-heading {
    display: flex;

    align-items: end;

    justify-content: space-between;

    margin-bottom: 20px;

    padding-left: 16px;

    border-left:
        2px solid
        var(--race-red);
}


.races-section-heading
> div:first-child
> span {
    color: #777f89;

    font-size: 9px;

    font-weight: 900;

    letter-spacing: 2px;
}


.races-section-heading h2 {
    margin: 5px 0 0;

    font-size: 31px;

    line-height: 1;

    font-weight: 900;

    text-transform: uppercase;

    letter-spacing: -1px;
}


.races-section-heading h2 strong {
    color: var(--race-red);
}


.races-count {
    display: flex;

    align-items: center;

    gap: 12px;

    color: #737a84;

    font-size: 9px;

    font-weight: 900;

    letter-spacing: 1px;
}


.races-count span {
    width: 31px;
    height: 31px;

    display: flex;

    align-items: center;
    justify-content: center;

    color: #fff;

    border:
        1px solid
        rgba(255,255,255,.16);

    border-radius: 50%;

    font-size: 14px;

    transition:
        border-color .25s ease,
        background .25s ease;
}


.races-count:hover span {
    border-color: var(--race-red);

    background:
        rgba(255,23,23,.08);
}


/* =========================================================
   RACE GRID
========================================================= */

.race-grid {
    display: grid;

    grid-template-columns:
        repeat(3,1fr);

    gap: 18px;
}


/* =========================================================
   RACE CARD
========================================================= */

.race-card {
    position: relative;

    min-height: 315px;

    overflow: hidden;

    background: #080a0e;

    border:
        1px solid
        rgba(255,255,255,.12);

    border-radius: 7px;

    isolation: isolate;

    transition:
        transform .35s ease,
        border-color .35s ease,
        box-shadow .35s ease;
}


.race-card:hover {
    transform: translateY(-7px);

    border-color:
        rgba(255,35,35,.62);

    box-shadow:
        0 20px 45px rgba(0,0,0,.58),
        0 0 28px rgba(255,20,20,.075);
}


/* =========================================================
   CARD IMAGE
========================================================= */

.race-card__image {
    position: absolute;

    inset: 0;

    overflow: hidden;

    z-index: 0;
}


.race-card__image img {
    width: 100%;
    height: 100%;

    object-fit: cover;

    transition:
        transform .7s ease,
        filter .6s ease;
}


.race-card:hover
.race-card__image img {
    transform: scale(1.08);

    filter:
        saturate(1.12)
        contrast(1.03);
}


/* =========================================================
   CARD SHADE
========================================================= */

.race-card__shade {
    position: absolute;

    inset: 0;

    z-index: 1;

    background:
        linear-gradient(
            180deg,
            rgba(4,6,9,.26) 0%,
            rgba(4,6,9,.10) 28%,
            rgba(4,6,9,.52) 54%,
            rgba(4,6,9,.91) 78%,
            rgba(4,6,9,.99) 100%
        );

    pointer-events: none;
}


/* =========================================================
   CARD VIGNETTE
========================================================= */

.race-card__vignette {
    position: absolute;

    inset: 0;

    z-index: 2;

    pointer-events: none;

    background:
        radial-gradient(
            ellipse at center,
            transparent 42%,
            rgba(0,0,0,.20) 75%,
            rgba(0,0,0,.55) 100%
        );
}


/* =========================================================
   CORNER GLOW
========================================================= */

.race-card__corner-glow {
    display: none !important;
}


/* =========================================================
   SIDE RED ACCENT
========================================================= */

.race-card__side-accent {
    position: absolute;

    left: 0;

    top: 25px;
    bottom: 24px;

    width: 2px;

    z-index: 8;

    background:
        linear-gradient(
            180deg,
            transparent,
            rgba(255,23,23,.9) 30%,
            #ff1717 72%,
            transparent
        );

    box-shadow:
        0 0 9px
        rgba(255,23,23,.35);

    opacity: .75;

    pointer-events: none;

    transition:
        opacity .3s ease,
        box-shadow .3s ease;
}


.race-card:hover
.race-card__side-accent {
    opacity: 1;

    box-shadow:
        0 0 15px
        rgba(255,23,23,.6);
}


/* =========================================================
   GHOST NUMBER
========================================================= */

.race-card__ghost-number {
    position: absolute;

    right: 3px;
    bottom: 43px;

    font-size: 105px;

    line-height: .8;

    font-weight: 900;

    letter-spacing: -8px;

    color:
        rgba(255,255,255,.09);

    z-index: 3;

    pointer-events: none;

    transition:
        color .35s ease,
        transform .35s ease;
}


.race-card:hover
.race-card__ghost-number {
    color:
        rgba(255,25,25,.13);

    transform:
        translateX(-5px);
}


/* =========================================================
   CARD TOP
========================================================= */

.race-card__top {
    position: absolute;

    top: 13px;

    left: 13px;
    right: 13px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 8px;

    z-index: 7;
}


.race-round {
    padding: 6px 9px;

    color: #fff;

    background:
        rgba(5,7,10,.72);

    border:
        1px solid
        rgba(255,255,255,.17);

    border-radius: 3px;

    font-size: 8px;

    font-weight: 900;

    letter-spacing: .8px;

    backdrop-filter: blur(7px);

    box-shadow:
        inset 0 1px
        rgba(255,255,255,.04);
}


/* =========================================================
   CARD CONTENT
========================================================= */

.race-card__content {
    position: absolute;

    left: 17px;
    right: 17px;

    bottom: 15px;

    z-index: 7;
}


.race-card__country {
    margin-bottom: 5px;

    color: var(--race-red);

    font-size: 9px;

    font-weight: 900;

    letter-spacing: 1.1px;
}


.race-card__content h3 {
    max-width: 90%;

    margin: 0;

    color: #fff;

    font-size: 17px;

    line-height: 1.08;

    font-weight: 900;

    text-transform: uppercase;

    letter-spacing: -.3px;

    text-shadow:
        0 2px 12px
        rgba(0,0,0,.55);
}


.race-card__circuit {
    display: flex;

    align-items: center;

    margin-top: 9px;

    color: #b5bbc3;

    font-size: 9px;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}


.race-card__circuit span {
    margin-right: 6px;

    color: var(--race-red);

    font-size: 7px;

    text-shadow:
        0 0 7px
        rgba(255,23,23,.7);
}


/* =========================================================
   CARD BOTTOM
========================================================= */

.race-card__bottom {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 8px;

    margin-top: 13px;

    padding-top: 11px;

    border-top:
        1px solid
        rgba(255,255,255,.12);
}


.race-card__date {
    display: flex;

    align-items: center;

    gap: 9px;

    min-width: 0;

    color: #c2c7ce;

    font-size: 9px;

    font-weight: 700;
}


.race-card__date span {
    white-space: nowrap;
}


/* =========================================================
   DETAILS / RESULTS BUTTON
========================================================= */

.race-card__link {
    flex-shrink: 0;

    min-height: 30px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    padding: 0 10px;

    color: #fff;

    text-decoration: none;

    background:
        rgba(5,7,10,.60);

    border:
        1px solid
        rgba(255,255,255,.20);

    border-radius: 4px;

    font-size: 8px;

    font-weight: 900;

    letter-spacing: .7px;

    backdrop-filter: blur(5px);

    box-shadow:
        inset 0 1px
        rgba(255,255,255,.035);

    transition:
        color .25s ease,
        border-color .25s ease,
        background .25s ease,
        transform .25s ease,
        box-shadow .25s ease;
}


.race-card__link:hover {
    color: #fff;

    border-color:
        rgba(255,35,35,.75);

    background:
        rgba(255,23,23,.12);

    transform:
        translateY(-1px);

    box-shadow:
        0 5px 15px
        rgba(255,20,20,.12),
        inset 0 0 10px
        rgba(255,20,20,.04);
}


.race-card__link-text {
    line-height: 1;
}


.race-card__link-arrow {
    color: var(--race-red);

    font-size: 13px;

    line-height: 1;

    transition:
        transform .25s ease;
}


.race-card__link:hover
.race-card__link-arrow {
    transform:
        translateX(3px);
}


/* COMPLETED */

.race-card--completed
.race-card__link:hover {
    border-color:
        rgba(0,216,120,.45);

    background:
        rgba(0,216,120,.08);
}


.race-card--completed
.race-card__link-arrow {
    color: #00d878;
}


/* =========================================================
   CARD BOTTOM RED LINE
========================================================= */

.race-card__accent {
    position: absolute;

    left: 0;
    bottom: 0;

    width: 0;
    height: 3px;

    z-index: 10;

    background:
        linear-gradient(
            90deg,
            #ff1717,
            #ff3333 45%,
            transparent
        );

    box-shadow:
        0 0 13px
        rgba(255,23,23,.5);

    transition:
        width .45s ease;
}


.race-card:hover
.race-card__accent {
    width: 100%;
}


/* =========================================================
   EMPTY STATE
========================================================= */

.races-empty {
    min-height: 300px;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;

    text-align: center;

    background: #080a0e;

    border:
        1px solid
        rgba(255,255,255,.1);

    border-radius: 7px;
}


.races-empty__icon {
    width: 55px;
    height: 55px;

    display: flex;

    align-items: center;
    justify-content: center;

    margin-bottom: 15px;

    color: var(--race-red);

    border:
        1px solid
        rgba(255,25,25,.3);

    border-radius: 50%;

    font-size: 25px;
}


.races-empty h3 {
    margin: 0;

    font-size: 22px;

    text-transform: uppercase;
}


.races-empty p {
    margin: 8px 0 20px;

    color: #777f89;

    font-size: 12px;
}


.races-empty a {
    color: var(--race-red);

    text-decoration: none;

    font-size: 9px;

    font-weight: 900;

    letter-spacing: 1px;
}


/* =========================================================
   TABLET
========================================================= */

@media (max-width: 1000px) {

    .race-hero__content {
        flex-direction: column;
        align-items: flex-start;
    }


    .race-filter-panel {
        width: min(100%, 500px);
    }


    .race-grid {
        grid-template-columns:
            repeat(2,1fr);
    }


    .featured-race__content {
        left: 45%;
    }
}


/* =========================================================
   INFO STRIP — TABLET
========================================================= */

@media (max-width: 900px) {

    .race-info-grid {
        min-height: 88px;

        grid-template-columns:
            repeat(3, minmax(0,1fr));
    }


    .race-info-item {
        gap: 10px;

        padding: 0 20px;
    }


    .race-info-icon {
        width: 28px;
        height: 28px;
    }


    .race-info-icon svg {
        width: 27px;
        height: 27px;
    }


    .race-info-content strong {
        font-size: 1.4rem;
    }


    .race-info-content span {
        font-size: .60rem;
    }


    .race-info-item--schedule
    .race-info-content strong {
        font-size: .76rem;
    }


    .race-info-item--schedule
    .race-info-content span {
        font-size: .54rem;
    }
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 700px) {

    .race-container {
        width:
            min(
                calc(100% - 24px),
                1180px
            );
    }


    .race-hero {
        min-height: 600px;
    }


    .race-hero__content {
        padding-top: 70px;
        padding-bottom: 40px;

        gap: 35px;
    }


    .race-hero h1 {
        font-size: 53px;

        letter-spacing: -3px;
    }


    .race-eyebrow {
        font-size: 9px;

        letter-spacing: 3px;
    }


    .race-filter-panel {
        width: 100%;
    }


    /* =============================================
       INFO STRIP MOBILE
    ============================================= */

    .race-info-grid {
        grid-template-columns: 1fr;

        min-height: auto;

        padding: 5px 0;
    }


    .race-info-item {
        min-height: 60px;

        justify-content: flex-start;

        padding: 0 20px;

        border-right: none;

        border-bottom:
            1px solid
            #30343d;
    }


    .race-info-item:last-child {
        border-bottom: none;
    }


    .race-info-item--schedule {
        padding-left: 20px;
    }


    .race-info-content strong {
        font-size: 1.4rem;
    }


    .race-info-item--schedule
    .race-info-content strong {
        font-size: .88rem;
    }


    .race-info-item--schedule
    .race-info-content span {
        font-size: .58rem;

        white-space: normal;
    }


    /* =============================================
       FEATURED
    ============================================= */

    .race-featured-section {
        margin-top: 20px;
    }


    .featured-race {
        min-height: 520px;
    }


    .featured-race__image {
        width: 100%;
        height: 55%;
    }


    .featured-race__image::after {
        background:
            linear-gradient(
                180deg,
                transparent 30%,
                rgba(5,7,10,.95) 100%
            );
    }


    .featured-race__shade {
        background:
            linear-gradient(
                180deg,
                transparent 20%,
                rgba(5,7,10,.94) 60%,
                #080a0d 100%
            );
    }


    .featured-race__top {
        left: 20px;
        right: 15px;
    }


    .featured-race__number {
        left: 20px;

        bottom: 100px;

        font-size: 120px;
    }


    .featured-race__content {
        left: 20px;
        right: 20px;

        bottom: 25px;
    }


    .featured-race__content h2 {
        font-size: 29px;
    }


    .featured-race__content p {
        display: none;
    }


    .featured-race__round {
        display: none;
    }


    .featured-race__actions {
        margin-top: 14px;
    }


    .race-btn {
        padding: 0 12px;

        font-size: 8px;
    }


    /* =============================================
       SECTION
    ============================================= */

    .races-section-heading {
        align-items: flex-end;
    }


    .races-section-heading h2 {
        font-size: 24px;
    }


    .races-count {
        display: none;
    }


    /* =============================================
       GRID
    ============================================= */

    .race-grid {
        grid-template-columns: 1fr;

        gap: 14px;
    }


    .race-card {
        min-height: 330px;
    }


    .race-card__link {
        min-height: 31px;

        padding: 0 11px;
    }
}


/* =========================================================
   VERY SMALL MOBILE
========================================================= */

@media (max-width: 430px) {

    .race-hero h1 {
        font-size: 45px;
    }


    .race-filter-tabs a {
        font-size: 8px;
    }


    .race-card__content h3 {
        font-size: 16px;
    }


    .race-card__date {
        font-size: 7px;

        gap: 5px;
    }


    .race-card__link {
        font-size: 7px;

        padding: 0 9px;
    }


    .race-card__link-arrow {
        font-size: 11px;
    }
}

</style>