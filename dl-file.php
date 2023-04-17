<?php

/*
 * dl-file.php
 *
 * Protect uploaded files with login.
 * 
 * @link https://github.com/NixVerstanden/paranoid-password-protection
 * 
 * @author NixVerstanden <https://github.com/NixVerstanden>
 * @license GPL-3.0+
 * 
 * easy add following line in your htaccess-file
 * RewriteCond %{REQUEST_FILENAME} -s
 * RewriteRule ^wp-content/uploads/(.*)$ dl-file.php?file=$1 [QSA,L]
 */

// Known bugs
// images are not printable via browser

process_file_restriction();

function process_file_restriction() {
    if (is_file_available()) {
        require_once('wp-load.php');
        get_and_render_file_as_content();
    } else {
        die_of_404();
    }
}

function is_file_available() {
    //is_user_logged_in() ||  auth_redirect();
    session_start();
    if ($_SESSION['ppp_is_active'] !== true) {
        return true;
    } else {
        return ($_SESSION['ppp_is_logged_in'] === true );
    }
}

function get_and_render_file_as_content() {
    $file = get_file_path();
    if (is_file_valid($file)) {
        render_file_as_content($file);
    } else {
        die_of_404();
    }
}

function is_file_valid($file) {
    return ($file && is_file($file));
}

function die_of_404() {
    header('HTTP/1.0 404 Not Found', true, 404);
    echo "\n";
    die();
}

function get_file_path() {
    list($basedir) = array_values(array_intersect_key(wp_upload_dir(), array('basedir' => 1))) + array(NULL);

    if (!$basedir) {
        return false;
    }

    $args = explode('/uploads', $basedir);
    $splitter = $args[0]; //'/usr/www/users/faqyou/wp-content';
    $file = $splitter . '/uploads/' . str_replace('..', '', isset($_GET['file']) ? $_GET['file'] : '');

    return $file;
}

function render_file_as_content($file) {
    $mimetype = get_mimetype($file);
    write_header($file, $minetype);
    readfile($file);
}

function get_mimetype($file) {
    $mime = wp_check_filetype($file);
    if (false === $mime['type'] && function_exists('mime_content_type')) {
        $mime['type'] = mime_content_type($file);
    }

    if ($mime['type']) {
        $mimetype = $mime['type'];
    } else {
        $mimetype = 'image/' . substr($file, strrpos($file, '.') + 1);
    }
}

function write_header($file, $minetype) {
    header('Content-Type: ' . $mimetype);
    if (false === strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS')) {
        header('Content-Length: ' . filesize($file));
    }

    $last_modified = gmdate('D, d M Y H:i:s', filemtime($file));
    $etag = '"' . md5($last_modified) . '"';
    header("Last-Modified: $last_modified GMT");
    header('ETag: ' . $etag);
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 100000000) . ' GMT');

    // Support for Conditional GET
    $client_etag = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) : false;

    if (!isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = false;

    $client_last_modified = trim($_SERVER['HTTP_IF_MODIFIED_SINCE']);
// If string is empty, return 0. If not, attempt to parse into a timestamp
    $client_modified_timestamp = $client_last_modified ? strtotime($client_last_modified) : 0;

// Make a timestamp for our most recent modification...
    $modified_timestamp = strtotime($last_modified);

    if (( $client_last_modified && $client_etag ) ? ( ( $client_modified_timestamp >= $modified_timestamp) && ( $client_etag == $etag ) ) : ( ( $client_modified_timestamp >= $modified_timestamp) || ( $client_etag == $etag ) )) {
        status_header(304);
        exit;
    }
}
