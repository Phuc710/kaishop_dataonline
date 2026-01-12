<?php
/**
 * Favicon Helper Function
 * Generates complete favicon HTML tags for all platforms
 */

if (!function_exists('render_favicon_tags')) {
    /**
     * Render complete favicon tags
     * @return string HTML favicon tags
     */
    function render_favicon_tags()
    {
        ob_start();
        ?>
        <!-- Favicons - Multi-Platform Support -->
        <!-- Standard Favicons -->
        <link rel="icon" type="image/x-icon" href="<?= asset('images/favicons/favicon.ico') ?>">
        <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('images/favicons/favicon-16x16.png') ?>">
        <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('images/favicons/favicon-32x32.png') ?>">
        <link rel="icon" type="image/png" sizes="48x48" href="<?= asset('images/favicons/favicon-48x48.png') ?>">

        <!-- Apple Touch Icon (iOS) -->
        <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('images/favicons/apple-touch-icon.png') ?>">

        <!-- Android Chrome Icons -->
        <link rel="icon" type="image/png" sizes="192x192" href="<?= asset('images/favicons/android-chrome-192x192.png') ?>">
        <link rel="icon" type="image/png" sizes="512x512" href="<?= asset('images/favicons/android-chrome-512x512.png') ?>">

        <!-- Web App Manifest -->
        <link rel="manifest" href="<?= url('site.webmanifest') ?>">

        <!-- Theme Colors -->
        <meta name="theme-color" content="#0606d4">
        <meta name="msapplication-TileColor" content="#0606d4">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <?php
        return ob_get_clean();
    }
}
