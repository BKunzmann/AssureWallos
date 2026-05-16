<?php
/**
 * About-Seite: AssureWallos-Version und Links zum Fork-Repository.
 * Erwartet: $i18n, optional $demoMode (aus includes/header.php).
 */
$assureDemoSuffix = (!empty($demoMode)) ? ' Demo' : '';
?>
            <div>
                <h3>
                    <?= htmlspecialchars(assure_version_display(), ENT_QUOTES, 'UTF-8') ?><?= $assureDemoSuffix ?>
                </h3>
                <span>
                    <?= translate('release_notes', $i18n) ?>
                    <a href="<?= htmlspecialchars(assure_release_notes_url(), ENT_QUOTES, 'UTF-8') ?>" target="_blank"
                        title="<?= translate('external_url', $i18n) ?>" rel="noreferrer">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                </span>
            </div>
            <div>
                <h3><?= translate('assurewallos_issues', $i18n) ?></h3>
                <span>
                    GitHub
                    <a href="<?= htmlspecialchars(assure_issues_url(), ENT_QUOTES, 'UTF-8') ?>" target="_blank"
                        title="<?= translate('external_url', $i18n) ?>" rel="noreferrer">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                </span>
            </div>
