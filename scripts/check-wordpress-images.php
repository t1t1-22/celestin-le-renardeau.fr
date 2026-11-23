<?php
/**
 * WordPress image processing health check for the Célestin stack.
 * Run inside WordPress container as www-data. No user images are accessed.
 */
declare(strict_types=1);
error_reporting(E_ALL);
$root = '/var/www/html';
$errors = [];
$report = static function (string $label, bool $ok, string $detail = '') use (&$errors): void {
    echo ($ok ? '[OK] ' : '[ERREUR] ') . $label . ($detail ? ' : ' . $detail : '') . PHP_EOL;
    if (!$ok) $errors[] = $label;
};
$report('Extension GD disponible', extension_loaded('gd'));
$report('Extension Imagick disponible', extension_loaded('imagick'));
$report('Mémoire PHP', (int)ini_get('memory_limit') >= 256, ini_get('memory_limit') . ' Mo configurés');
$report('Répertoire temporaire PHP', is_writable(sys_get_temp_dir()), sys_get_temp_dir());
$uploads = $root . '/wp-content/uploads';
if (!is_dir($uploads)) @mkdir($uploads, 0775, true);
$report('Écriture dans wp-content/uploads', is_dir($uploads) && is_writable($uploads));
if (extension_loaded('gd') && is_dir($uploads) && is_writable($uploads)) {
    $name = $uploads . '/.celestin-image-health-' . bin2hex(random_bytes(8));
    try {
        $im = imagecreatetruecolor(400, 400);
        if (!$im) throw new RuntimeException('GD imagecreatetruecolor a échoué');
        imagefill($im, 0, 0, imagecolorallocate($im, 126, 184, 222));
        $ok = imagejpeg($im, $name . '.jpg', 85);
        imagedestroy($im);
        $report('Écriture et encodage JPEG avec GD', (bool)$ok);
        if ($ok && is_file($root . '/wp-load.php')) {
            // WP may still be uninstalled during CI: test WP only once configured.
            require_once $root . '/wp-load.php';
            if (function_exists('is_blog_installed') && is_blog_installed()) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
                $editor = wp_get_image_editor($name . '.jpg');
                if (is_wp_error($editor)) {
                    $report('Traitement WordPress de JPEG', false, $editor->get_error_message());
                } else {
                    $scaled = $editor->resize(180, 180);
                    if (is_wp_error($scaled)) {
                        $report('Redimensionnement WordPress', false, $scaled->get_error_message());
                    } else {
                        $saved = $editor->save($name . '-small.jpg');
                        $report('Miniature WordPress générée', !is_wp_error($saved), is_wp_error($saved) ? $saved->get_error_message() : '');
                    }
                }
            }
        }
    } catch (Throwable $e) {
        $report('Traitement image', false, $e->getMessage());
    } finally {
        @unlink($name . '.jpg');
        @unlink($name . '-small.jpg');
    }
}
if ($errors) {
    echo 'Contrôle images Célestin échoué : ' . implode(', ', $errors) . PHP_EOL;
    exit(1);
}
echo 'Contrôle images Célestin terminé avec succès.' . PHP_EOL;
