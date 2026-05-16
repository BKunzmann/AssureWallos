<?php
require_once 'includes/header.php';

$wallosIsUpToDate = true;
if (!is_null($settings['latest_version'])) {
    $latestVersion = $settings['latest_version'];
    if (version_compare($version, $latestVersion) == -1) {
        $wallosIsUpToDate = false;
    }
}
?>

<section class="contain">

    <section class="account-section">
        <header>
            <h2><?= translate('about', $i18n) ?></h2>
        </header>
        <div class="credits-list">
            <?php
            // START ASSUREWALLOS MOD
            require_once __DIR__ . '/includes/insurance/assure_brand.php';
            include __DIR__ . '/includes/insurance/ui/about_assure.php';
            include __DIR__ . '/includes/insurance/ui/about_upstream.php';
            // END ASSUREWALLOS MOD
            ?>
        </div>
    </section>

    <section class="account-section">
        <header>
            <h2><?= translate("credits", $i18n) ?></h2>
        </header>
        <div class="credits-list">
            <div>
                <h3><?= translate('icons', $i18n) ?></h3>
                <span>
                    https://www.streamlinehq.com/freebies/plump-flat-free
                    <a href="https://www.streamlinehq.com/freebies/plump-flat-free" target="_blank"
                        title="<?= translate('external_url', $i18n) ?>" rel="noreferrer">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                </span>
            </div>
            <div>
                <h3><?= translate('payment_icons', $i18n) ?></h3>
                <span>
                    https://www.figma.com/file/5IMW8JfoXfB5GRlPNdTyeg/Credit-Cards-and-Payment-Methods-Icons-(Community)
                    <a href="https://www.figma.com/file/5IMW8JfoXfB5GRlPNdTyeg/Credit-Cards-and-Payment-Methods-Icons-(Community)"
                        target="_blank" title="<?= translate('external_url', $i18n) ?>" rel="noreferrer">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                </span>
            </div>
            <div>
                <h3>Chart.js</h3>
                <span>
                    https://www.chartjs.org/
                    <a href="https://www.chartjs.org/" target="_blank" title="<?= translate('external_url', $i18n) ?>"
                        rel="noreferrer">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                </span>
            </div>
            <div>
                <h3>QRCode.js</h3>
                <span>
                    https://github.com/davidshimjs/qrcodejs
                    <a href="https://github.com/davidshimjs/qrcodejs" target="_blank"
                        title="<?= translate('external_url', $i18n) ?>" rel="noreferrer">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                </span>
            </div>
            <div>
                <h3>Icons by icons8</h3>
                <span>
                    https://icons8.com/
                    <a href="https://icons8.com/" target="_blank" title="<?= translate('external_url', $i18n) ?>"
                        rel="noreferrer">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                </span>
            </div>
        </div>
    </section>

</section>

<?php
require_once 'includes/footer.php';
?>
