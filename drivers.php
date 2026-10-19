<?php
// drivers.php — Formula 1 Drivers Roster

$pageTitle = "Drivers";

require_once __DIR__ . '/includes/header.php';


/* =========================================================
   SEARCH / FILTER
========================================================= */

$search = trim($_GET['search'] ?? '');
$teamId = (int)($_GET['team_id'] ?? 0);


/* =========================================================
   IMAGE PATH HELPER
   Supports filename, assets path, or full URL
========================================================= */

$resolveImage = function ($value, $folder) {

    $value = trim((string)$value);

    if ($value === '') {
        return '';
    }

    // Full URL
    if (
        strpos($value, 'http://') === 0 ||
        strpos($value, 'https://') === 0
    ) {
        return $value;
    }

    // Already an assets path
    if (
        strpos($value, 'assets/') === 0 ||
        strpos($value, '/assets/') === 0
    ) {
        return ltrim($value, '/');
    }

    // Only filename stored in database
    return 'assets/images/' . trim($folder, '/') . '/' . basename($value);
};


/* =========================================================
   DRIVERS QUERY
   TEAM LOGO INCLUDED
========================================================= */

$sql = "
    SELECT
        d.*,
        t.name AS team_name,
        t.logo AS team_logo
    FROM drivers d
    LEFT JOIN teams t
        ON d.team_id = t.id
    WHERE 1=1
";

$params = [];


/* SEARCH */

if ($search !== '') {

    $sql .= "
        AND (
            d.name LIKE ?
            OR d.nationality LIKE ?
        )
    ";

    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}


/* TEAM FILTER */

if ($teamId > 0) {

    $sql .= " AND d.team_id = ?";

    $params[] = $teamId;
}


/* SORT */

$sql .= " ORDER BY d.points DESC";


$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$drivers = $stmt->fetchAll();


/* =========================================================
   TEAMS
========================================================= */

$teams = $pdo->query("
    SELECT *
    FROM teams
    ORDER BY name ASC
")->fetchAll();

?>


<!-- =========================================================
     PREMIUM DRIVERS HERO
========================================================= -->

<section class="drivers-hero">

    <div class="drivers-hero-inner">

        <div class="drivers-hero-content">

            <div class="drivers-breadcrumb">
                <span>/</span>
                HOME / DRIVERS
            </div>

            <h1>
                FORMULA 1
                <strong>DRIVERS</strong>
            </h1>

            <p>
                Explore official 2026 driver line-up,
                career stats, and profiles.
            </p>

        </div>

    </div>

</section>


<!-- =========================================================
     FILTER AREA
========================================================= -->

<section class="drivers-filter-section">

    <div class="container">

        <form
            method="GET"
            action="drivers.php"
            class="drivers-filter-form"
        >

            <!-- TEAM -->

            <div class="drivers-filter-team">

                <select
                    name="team_id"
                    onchange="this.form.submit()"
                    class="form-control"
                >

                    <option value="0">
                        All Teams
                    </option>

                    <?php foreach ($teams as $team): ?>

                        <option
                            value="<?php echo (int)$team['id']; ?>"
                            <?php
                            echo $teamId == $team['id']
                                ? 'selected'
                                : '';
                            ?>
                        >
                            <?php echo e($team['name']); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- SEARCH -->

            <div class="drivers-filter-search">

                <span class="search-icon">⌕</span>

                <input
                    type="text"
                    name="search"
                    value="<?php echo e($search); ?>"
                    placeholder="Search driver name..."
                    class="form-control"
                >

            </div>


            <!-- BUTTON -->

            <button
                type="submit"
                class="drivers-filter-btn"
            >
                
                SEARCH
            </button>

        </form>

    </div>

</section>


<!-- =========================================================
     DRIVERS
========================================================= -->

<section class="drivers-roster-section">

    <div class="container">

        <div class="grid-3">

            <?php if (!empty($drivers)): ?>

                <?php foreach ($drivers as $d): ?>

                    <?php

                    /* DRIVER IMAGE */

                    $driverImage = '';

                    if (!empty($d['image'])) {

                        $driverImage = $resolveImage(
                            $d['image'],
                            'drivers'
                        );
                    }


                    /* TEAM LOGO */

                    $teamLogo = '';

                    if (!empty($d['team_logo'])) {

                        $teamLogo = $resolveImage(
                            $d['team_logo'],
                            'teams'
                        );
                    }


                    /* FLAG */

                    $flagImage = '';

                    if (!empty($d['flag'])) {

                        $flagImage = $resolveImage(
                            $d['flag'],
                            'flags'
                        );
                    }

                    ?>

                    <!-- =================================================
                         DRIVER CARD
                    ================================================= -->

                    <div
                        class="driver-card driver-card--visual"
                        style="
                            --driver-bg:
                            url('assets/images/driver-bg/team-<?php echo (int)$d['team_id']; ?>.svg');
                        "
                    >

                        <!-- DRIVER IMAGE -->

                        <div class="driver-img-box">

                            <div class="driver-img-wrap">

                                <span class="driver-number">
                                    #<?php echo e($d['number']); ?>
                                </span>


                                <?php if ($driverImage !== ''): ?>

                                    <img
                                        src="<?php echo e($driverImage); ?>"
                                        alt="<?php echo e($d['name']); ?>"
                                        class="driver-img"
                                        loading="lazy"
                                        onerror="
                                            this.style.display='none';
                                            this.closest('.driver-img-wrap')
                                                .classList.add('image-missing');
                                        "
                                    >

                                <?php endif; ?>

                            </div>

                        </div>


                        <!-- =================================================
                             DRIVER DETAILS
                        ================================================= -->

                        <div class="driver-details">


                            <!-- TEAM -->

                           <div class="driver-team-name">

    <span class="driver-team-logo-wrap">

        <?php if ($teamLogo !== ''): ?>

            <img
                src="<?php echo e($teamLogo); ?>"
                alt="<?php echo e($d['team_name'] ?? 'Team'); ?>"
                class="driver-team-logo"
                loading="lazy"
            >

        <?php endif; ?>

    </span>

    <span>
        <?php echo e($d['team_name'] ?? 'Team'); ?>
    </span>

</div>

                            <!-- DRIVER NAME -->

                            <h3 class="driver-name">
                                <?php echo e($d['name']); ?>
                            </h3>


                            <!-- NATIONALITY + FLAG -->

                            <div class="driver-nationality">

                                <?php if ($flagImage !== ''): ?>

                                    <img
                                        src="<?php echo e($flagImage); ?>"
                                        alt="<?php echo e($d['nationality']); ?>"
                                        class="driver-flag"
                                        loading="lazy"
                                    >

                                <?php else: ?>

                                    <span class="driver-flag-placeholder">
                                        ▪
                                    </span>

                                <?php endif; ?>


                                <span>
                                    <?php echo e($d['nationality']); ?>
                                </span>

                            </div>


                            <!-- STATS -->

                            <div class="driver-stats-row">

                                <div class="stat-item">

                                    <div class="stat-val">
                                        <?php echo e($d['points']); ?>
                                    </div>

                                    <div class="stat-lbl">
                                        PTS
                                    </div>

                                </div>


                                <div class="stat-item">

                                    <div class="stat-val">
                                        <?php echo e($d['wins']); ?>
                                    </div>

                                    <div class="stat-lbl">
                                        WINS
                                    </div>

                                </div>


                                <div class="stat-item">

                                    <div class="stat-val">
                                        <?php echo e($d['podiums']); ?>
                                    </div>

                                    <div class="stat-lbl">
                                        PODIUMS
                                    </div>

                                </div>

                            </div>


                            <!-- PROFILE -->

                         <a
    href="driver-profile.php?id=<?php echo (int)$d['id']; ?>"
    class="btn-primary driver-profile-btn"
>
    VIEW PROFILE
</a>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="drivers-empty">

                    <h3>
                        NO DRIVERS FOUND
                    </h3>

                    <p>
                        Try another driver name or team.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </div>

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>



<style>
.drivers-hero {
    position: relative;
    min-height: 290px;
    padding: 48px 0;
    display: flex;
    align-items: center;
    overflow: hidden;

    background:
        linear-gradient(
            90deg,
            rgba(2,5,8,.97) 0%,
            rgba(2,5,8,.84) 40%,
            rgba(2,5,8,.25) 75%,
            rgba(2,5,8,.65) 100%
        ),
        url("assets/images/drivers_hero.png") right 30% / cover no-repeat;

    border-bottom: 1px solid #252d36;
}

.drivers-hero::after {
    content: "";
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 1px;

    background: #e10600;
    opacity: .65;
}

.drivers-hero-inner {
    width: var(--container-width);
    max-width: var(--container-max);
    margin: 0 auto;
    padding: 0 var(--container-padding);
}

.drivers-breadcrumb {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
    color: #e10600;
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 1.2px;
    text-transform: uppercase;
}

.drivers-breadcrumb span {
    width: 14px;
    height: 3px;
    background: #e10600;
    display: inline-block;
}

.drivers-hero h1 {
    margin: 0;
    color: #f4f6f8;
    font-size: clamp(2.2rem, 3.8vw, 3.2rem);
    font-weight: 900;
    font-style: italic;
    line-height: 1.1;
    letter-spacing: -0.01em;
}

.drivers-hero h1 strong {
    color: #e10600;
}

.drivers-hero p {
    margin: 12px 0 0;
    color: var(--text-muted);
    font-size: 0.95rem;
    line-height: 1.6;
    max-width: 600px;
}


/* =========================================================
   FILTER AREA
========================================================= */

.drivers-filter-section {
    padding: 18px 0;

    background: #05080c;
    border-bottom: 1px solid #161e26;
}

.drivers-filter-form {
    display: grid;

    grid-template-columns:
        235px
        1fr
        105px;

    gap: 12px;
    align-items: center;
}

.drivers-filter-team select,
.drivers-filter-search input {
    width: 100%;
    height: 38px;
    box-sizing: border-box;

    background: #070c12;
    border: 1px solid #34404b;

    color: #dfe4e9;

    border-radius: 4px;

    font-size: 11px;

    outline: none;

    transition:
        border-color .2s ease,
        background .2s ease,
        box-shadow .2s ease;
}

.drivers-filter-team select {
    padding: 0 12px;
}

.drivers-filter-search {
    position: relative;
}

.drivers-filter-search input {
    padding: 0 14px 0 38px;
}

.drivers-filter-search input::placeholder {
    color: #74808d;
}

.drivers-filter-team select:focus,
.drivers-filter-search input:focus {
    border-color: rgba(225,6,0,.65);

    background: #0a0f15;

    box-shadow:
        0 0 0 2px rgba(225,6,0,.07);
}

.search-icon {
    position: absolute;

    left: 14px;
    top: 50%;

    transform: translateY(-50%);

    color: #8d99a6;
    font-size: 18px;

    z-index: 2;
}


/* =========================================================
   SEARCH BUTTON
========================================================= */

.drivers-filter-btn {
    height: 38px;

    border: none;

    background:
        linear-gradient(
            135deg,
            #e10600,
            #b90500
        );

    color: #fff;

    font-size: 9px;
    font-weight: 900;

    letter-spacing: .08em;

    cursor: pointer;

    clip-path:
        polygon(
            7px 0,
            100% 0,
            calc(100% - 7px) 100%,
            0 100%
        );

    transition:
        background .2s ease,
        box-shadow .2s ease,
        transform .2s ease;
}

.drivers-filter-btn:hover {
    background:
        linear-gradient(
            135deg,
            #ff1710,
            #d90700
        );

    box-shadow:
        0 0 20px rgba(225,6,0,.25);

    transform: translateY(-1px);
}


/* =========================================================
   ROSTER SECTION
========================================================= */
.drivers-roster-section {
    padding: 25px 0 0;
    background: radial-gradient(...), #030609;
}


/* =========================================================
   DRIVER CARD
========================================================= */

.driver-card--visual {
    position: relative;
    isolation: isolate;

    display: flex;
    flex-direction: column;

    overflow: hidden;

    background:
        linear-gradient(
            145deg,
            #20262d 0%,
            #171c22 48%,
            #101419 100%
        );

    border: 1px solid rgba(255,255,255,.11);

    border-radius: 5px;

    box-shadow:
        0 14px 35px rgba(0,0,0,.38),
        inset 0 1px 0 rgba(255,255,255,.065);

    transition:
        transform .3s ease,
        border-color .3s ease,
        box-shadow .3s ease;
}


/* =========================================================
   TEAM BACKGROUND
========================================================= */

.driver-card--visual {
    background-image:
        linear-gradient(
            135deg,
            rgba(27,32,39,.78),
            rgba(8,11,15,.90)
        ),
        var(--driver-bg);

    background-position:
        center,
        center;

    background-size:
        cover,
        cover;

    background-repeat:
        no-repeat;
}


/* =========================================================
   RED CINEMATIC CORNER LIGHT
========================================================= */

.driver-card--visual::before {
    content: "";

    position: absolute;

    width: 220px;
    height: 220px;

    top: -120px;
    right: -90px;

    background:
        radial-gradient(
            circle,
            rgba(225,6,0,.25) 0%,
            rgba(225,6,0,.09) 36%,
            transparent 68%
        );

    filter: blur(5px);

    z-index: 1;

    pointer-events: none;
}


/* =========================================================
   LEFT RED RACING EDGE
========================================================= */

.driver-card--visual::after {
    content: "";

    position: absolute;

    left: 0;
    top: 15px;
    bottom: 15px;

    width: 2px;

    background:
        linear-gradient(
            180deg,
            transparent,
            #e10600 30%,
            #ff2119 50%,
            #e10600 70%,
            transparent
        );

    opacity: .75;

    box-shadow:
        0 0 10px rgba(225,6,0,.22);

    z-index: 30;

    pointer-events: none;
}


/* =========================================================
   CARD HOVER
========================================================= */

.driver-card--visual:hover {
    transform: translateY(-5px);

    border-color:
        rgba(225,6,0,.38);

    box-shadow:
        0 22px 48px rgba(0,0,0,.50),
        0 0 25px rgba(225,6,0,.07),
        inset 0 1px 0 rgba(255,255,255,.09);
}


/* =========================================================
   DRIVER IMAGE AREA
   IMPORTANT — NO CROPPING
========================================================= */

.driver-card--visual .driver-img-box {
    position: relative;

    height: 235px;
    flex: 0 0 235px;

    overflow: hidden;

    background:
        radial-gradient(
            ellipse at 75% 28%,
            rgba(225,6,0,.18),
            transparent 42%
        ),

        radial-gradient(
            ellipse at 50% 20%,
            rgba(255,255,255,.09),
            transparent 46%
        ),

        linear-gradient(
            145deg,
            #252b33 0%,
            #1c2229 45%,
            #12171d 100%
        );

    border-bottom:
        1px solid rgba(255,255,255,.09);

    box-shadow:
        inset 0 -35px 45px rgba(0,0,0,.20);

    z-index: 2;
}


/* =========================================================
   IMAGE WRAPPER
========================================================= */

.driver-card--visual .driver-img-wrap {
    position: relative;

    width: 100%;
    height: 100%;

    overflow: hidden;

    display: flex;

    align-items: flex-end;
    justify-content: center;
}


/* =========================================================
   DRIVER IMAGE
   FULL IMAGE — HEAD WILL NOT BE CUT
========================================================= */

.driver-card--visual .driver-img {
    position: relative;

    width: 100%;
    height: 100%;

    max-width: 100%;
    max-height: 100%;

    object-fit: contain;
    object-position: center bottom;

    display: block;

    transform: none;

    filter:
        drop-shadow(
            0 12px 18px rgba(0,0,0,.45)
        );

    z-index: 3;

    transition:
        filter .3s ease;
}


/* NO IMAGE ZOOM */

.driver-card--visual:hover .driver-img {
    transform: none;

    filter:
        drop-shadow(
            0 15px 21px rgba(0,0,0,.52)
        );
}


/* =========================================================
   DRIVER NUMBER
========================================================= */

.driver-card--visual .driver-number {
    position: absolute;

    top: 12px;
    left: 13px;

    padding: 5px 8px;

    background:
        rgba(12,16,21,.82);

    border:
        1px solid rgba(255,255,255,.12);

    border-left:
        2px solid #e10600;

    border-radius: 3px;

    color: #f3f5f7;

    font-size: 10px;
    font-weight: 900;

    letter-spacing: .07em;

    backdrop-filter: blur(8px);

    box-shadow:
        0 5px 14px rgba(0,0,0,.25);

    z-index: 15;

    pointer-events: none;
}


/* =========================================================
   DETAILS AREA
========================================================= */

.driver-card--visual .driver-details {
    position: relative;

    flex: 0 0 auto;

    margin: 0;

    padding: 14px !important;

    background:
        linear-gradient(
            180deg,
            #1b2026 0%,
            #151a20 55%,
            #11151a 100%
        ) !important;

    border-top:
        1px solid rgba(255,255,255,.075);

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.03);

    z-index: 10;
}


/* =========================================================
   TEAM NAME
========================================================= */

.driver-card--visual .driver-team-name {
    display: flex !important;

    align-items: center;

    gap: 7px;

    min-height: 20px;

    color: #c0c8d0 !important;

    font-size: 9px;

    font-weight: 800;

    letter-spacing: .07em;

    text-transform: uppercase;
}


/* remove accidental lines */

.driver-card--visual .driver-team-name::before,
.driver-card--visual .driver-team-name::after {
    content: none !important;
    display: none !important;
}


/* =========================================================
   TEAM LOGO
========================================================= */

.driver-card--visual .driver-team-logo-wrap {
    width: 27px;
    height: 20px;

    display: flex;

    align-items: center;
    justify-content: center;

    flex-shrink: 0;

    background:
        linear-gradient(
            145deg,
            rgba(255,255,255,.08),
            rgba(255,255,255,.025)
        );

    border:
        1px solid rgba(255,255,255,.09);

    border-radius: 3px;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.04);
}

.driver-card--visual .driver-team-logo {
    width: 24px;
    height: 18px;

    object-fit: contain;

    display: block;

    margin: 0;
}


/* =========================================================
   DRIVER NAME
========================================================= */

.driver-card--visual .driver-name {
    margin-top: 9px !important;
    margin-bottom: 9px !important;

    color: #ffffff !important;

    font-size: 21px !important;

    font-weight: 900;

    line-height: 1;

    letter-spacing: -.035em;

    text-shadow:
        0 2px 9px rgba(0,0,0,.40);
}


/* =========================================================
   NATIONALITY
========================================================= */

.driver-card--visual .driver-nationality {
    display: flex;

    align-items: center;

    gap: 7px;

    margin-bottom: 13px !important;

    color: #a2acb6 !important;

    font-size: 10px;

    font-weight: 600;

    letter-spacing: .025em;
}


/* =========================================================
   FLAG
========================================================= */

.driver-card--visual .driver-flag {
    width: 20px;
    height: 13px;

    display: block;

    object-fit: cover;

    border:
        1px solid rgba(255,255,255,.12);

    border-radius: 1px;
}

.driver-card--visual .driver-flag-placeholder {
    width: 20px;

    color: #6c7682;
}


/* =========================================================
   STATS ROW
========================================================= */

.driver-card--visual .driver-stats-row {
    display: grid !important;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 5px;

    padding: 6px;

    background:
        rgba(255,255,255,.035);

    border:
        1px solid rgba(255,255,255,.07);

    border-radius: 4px;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.025);
}


/* =========================================================
   STAT ITEM
========================================================= */

.driver-card--visual .stat-item {
    position: relative;

    padding: 7px 4px;

    background:
        linear-gradient(
            145deg,
            rgba(255,255,255,.065),
            rgba(255,255,255,.025)
        );

    border:
        1px solid rgba(255,255,255,.055);

    border-radius: 3px;

    text-align: center;

    transition:
        background .2s ease,
        border-color .2s ease;
}

.driver-card--visual .stat-item:hover {
    background:
        linear-gradient(
            145deg,
            rgba(225,6,0,.09),
            rgba(255,255,255,.025)
        );

    border-color:
        rgba(225,6,0,.22);
}


/* =========================================================
   STAT VALUE
========================================================= */

.driver-card--visual .stat-val {
    color: #ffffff !important;

    font-size: 16px;

    font-weight: 900;

    line-height: 1;
}


/* =========================================================
   STAT LABEL
========================================================= */

.driver-card--visual .stat-lbl {
    margin-top: 4px;

    color: #89939e !important;

    font-size: 7px;

    font-weight: 900;

    letter-spacing: .12em;
}


/* =========================================================
   FIRST STAT RED ACCENT
========================================================= */

.driver-card--visual .stat-item:first-child::before {
    content: "";

    position: absolute;

    left: 0;
    top: 6px;
    bottom: 6px;

    width: 2px;

    background: #e10600;

    opacity: .75;
}


/* =========================================================
   VIEW PROFILE BUTTON
========================================================= */

.driver-card--visual .driver-profile-btn {
    position: relative;

    display: block;

    width: 100%;

    box-sizing: border-box;

    margin-top: 12px !important;

    padding: 9px 0 !important;

    background:
        linear-gradient(
            100deg,
            #1a1f25,
            #20252c,
            rgba(225,6,0,.13)
        ) !important;

    border:
        1px solid rgba(225,6,0,.48) !important;

    border-radius: 3px;

    color: #ffffff !important;

    text-align: center;

    text-decoration: none;

    font-size: 9px !important;

    font-weight: 900;

    letter-spacing: .10em;

    clip-path: none;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.05);

    transition:
        background .25s ease,
        border-color .25s ease,
        box-shadow .25s ease,
        transform .25s ease;
}


/* Arrow */

.driver-card--visual .driver-profile-btn::after {
    content: "→";

    display: inline-block;

    margin-left: 8px;

    color: #e10600;

    font-size: 13px;

    transition:
        transform .25s ease;
}


/* Hover */

.driver-card--visual .driver-profile-btn:hover {
    background:
        linear-gradient(
            100deg,
            #22282f,
            #242a31,
            rgba(225,6,0,.22)
        ) !important;

    border-color:
        #e10600 !important;

    box-shadow:
        0 0 18px rgba(225,6,0,.14);

    transform:
        translateY(-1px);
}

.driver-card--visual .driver-profile-btn:hover::after {
    transform:
        translateX(4px);
}


/* =========================================================
   DETAILS BOTTOM ACCENT
========================================================= */

.driver-card--visual .driver-details::after {
    content: "";

    position: absolute;

    left: 20%;
    right: 20%;
    bottom: 0;

    height: 1px;

    background:
        linear-gradient(
            90deg,
            transparent,
            rgba(225,6,0,.45),
            transparent
        );

    box-shadow:
        0 0 8px rgba(225,6,0,.12);

    pointer-events: none;
}


/* =========================================================
   EMPTY STATE
========================================================= */

.drivers-empty {
    padding: 45px 25px;

    background:
        linear-gradient(
            180deg,
            #171c22,
            #0d1116
        );

    border:
        1px solid rgba(255,255,255,.08);

    border-radius: 5px;

    text-align: center;

    box-shadow:
        0 15px 35px rgba(0,0,0,.25);
}

.drivers-empty h3 {
    margin: 0 0 8px;

    color: #ffffff;
}

.drivers-empty p {
    margin: 0;

    color: #7f8a96;

    font-size: 12px;
}


/* =========================================================
   ROUTE SECTION
========================================================= */

.drivers-route {
    position: relative;

    height: 105px;

    overflow: hidden;

    background:
        linear-gradient(
            180deg,
            #030609,
            #05080c
        );

    border-top:
        1px solid #151d25;

    border-bottom:
        1px solid #e10600;
}

.drivers-route::before {
    content: "";

    position: absolute;

    inset: 0;

    background:
        repeating-linear-gradient(
            90deg,
            transparent 0 45px,
            rgba(255,255,255,.018) 46px,
            transparent 47px
        );

    opacity: .5;
}

.drivers-route-line {
    position: absolute;

    height: 2px;

    background: #e10600;

    box-shadow:
        0 0 8px rgba(225,6,0,.55),
        0 0 18px rgba(225,6,0,.20);

    border-radius: 50%;

    opacity: .55;
}

.route-line-one {
    width: 45%;

    left: -5%;
    top: 72px;

    transform: rotate(-7deg);
}

.route-line-two {
    width: 38%;

    right: -4%;
    top: 42px;

    transform: rotate(8deg);
}

.route-line-three {
    width: 27%;

    left: 37%;
    top: 82px;

    transform: rotate(-3deg);
}

.drivers-route-content {
    position: absolute;

    left: 50%;
    top: 50%;

    transform:
        translate(-50%, -50%);

    display: flex;

    flex-direction: column;

    align-items: center;

    z-index: 5;

    text-align: center;
}

.drivers-route-content span {
    color: #e10600;

    font-size: 8px;

    font-weight: 800;

    letter-spacing: .15em;
}

.drivers-route-content strong {
    margin-top: 5px;

    color: #dce2e8;

    font-size: 12px;

    letter-spacing: .10em;
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 800px) {

    .drivers-hero-inner {
        width: calc(100% - 30px);
    }

    .drivers-hero {
        min-height: 185px;
    }

    .drivers-hero h1 {
        font-size: 32px;
    }

    .drivers-filter-form {
        grid-template-columns: 1fr;
    }

    .drivers-filter-btn {
        width: 120px;
    }

    .driver-card--visual .driver-img-box {
        height: 235px;
        flex-basis: 235px;
    }

    .driver-card--visual .driver-name {
        font-size: 19px !important;
    }

    .driver-card--visual .driver-details {
        padding: 13px 12px 14px !important;
    }
}
/* =========================================================
   DRIVER IMAGE — FULL HEAD + SHOULDERS, BOTTOM CROPPED
========================================================= */
.driver-card--visual .driver-img-box {
    height: 235px;
    position: relative;
    overflow: hidden;
}

.driver-card--visual .driver-img-wrap {
    width: 100%;
    height: 100%;
    position: relative;
    overflow: hidden;
}

.driver-card--visual .driver-img {
    width: 100%;
    height: 100%;

    object-fit: cover;
    object-position: center top;

    transform: none;
    display: block;
}
</style>
