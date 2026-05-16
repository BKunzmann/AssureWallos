<?php
/**
 * Logo-Block für Auth-Seiten (Login, Registrierung, …).
 */
$assureLogoTitle = assure_logo_title_attr(isset($i18n) ? $i18n : null);
?>
                <div class="logo-image" title="<?= $assureLogoTitle ?>">
                    <?php include __DIR__ . '/logo_inner.php'; ?>
                </div>
