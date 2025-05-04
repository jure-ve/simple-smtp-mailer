<?php
/*
Plugin Name: Simple SMTP Mailer Plugin
Plugin URI: https://github.com/jure-ve/simple-smtp-mailer
Description: Configure WordPress to send emails using PHPMailer via SMTP.
Version: 0.2
Author: Jure
Author URI: https://juredev.com/
License: GPL2
*/

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

define('SIMPLE_SMTP_MAILER_OPTIONS', 'simple_smtp_mailer_settings');
define('SIMPLE_SMTP_MAILER_PASSWORD_FIELD', 'password');
define('SIMPLE_SMTP_MAILER_PASSWORD_IV_FIELD', 'password_iv');
define('SIMPLE_SMTP_MAILER_INTERNAL_SALT', 'simple_smtp_mailer_encryption_salt_jure_unique');

/**
 * ==================================================================
 * Helper Functions for Encryption/Decryption
 * ==================================================================
 */
// Obtains a deterministic encryption key based on WP salts
function simple_smtp_mailer_get_encryption_key() {
    $salt = AUTH_KEY . SECURE_AUTH_KEY . LOGGED_IN_KEY . SIMPLE_SMTP_MAILER_INTERNAL_SALT;
    return hash('sha256', $salt, true);
}

// Obtains a deterministic IV based on the key and additional salts
function simple_smtp_mailer_get_encryption_iv($key) {
    $salt = NONCE_KEY . AUTH_SALT . SECURE_AUTH_SALT . LOGGED_IN_SALT . NONCE_SALT;
    $iv_size = openssl_cipher_iv_length('aes-256-cbc');
    return substr(hash('sha256', $key . $salt, true), 0, $iv_size);
}

// Encript plaintext text
function simple_smtp_mailer_encrypt($plaintext) {
    if (empty($plaintext)) {
        return false;
    }

    $key = simple_smtp_mailer_get_encryption_key();
    $iv = simple_smtp_mailer_get_encryption_iv($key);

    $ciphertext = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

    if ($ciphertext === false) {
        error_log("[Simple SMTP Mailer] Encryption error: " . openssl_error_string()); 
        return false;
    }

    return base64_encode($ciphertext) . '::' . base64_encode($iv);
}

// Desencript text encrypted
function simple_smtp_mailer_decrypt($encoded_ciphertext) {
    if (empty($encoded_ciphertext)) {
        return false;
    }

    $parts = explode('::', $encoded_ciphertext);
    if (count($parts) !== 2) {
        error_log("[Simple SMTP Mailer] Decryption error: Invalid data format."); 
        return false;
    }

    $ciphertext = base64_decode($parts[0]);
    $iv = base64_decode($parts[1]);

    $key = simple_smtp_mailer_get_encryption_key();

    $iv_size = openssl_cipher_iv_length('aes-256-cbc');
    if (strlen($iv) !== $iv_size) {
        error_log("[Simple SMTP Mailer] Decryption error: Invalid IV size."); 
        return false;
    }

    $plaintext = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

    if ($plaintext === false) {
        error_log("[Simple SMTP Mailer] Decryption error: " . openssl_error_string()); 
        return false;
    }
    return $plaintext;
}

/**
 * ==================================================================
 * Integrate WordPress Settings API
 * ==================================================================
 */
add_action('admin_init', 'simple_smtp_mailer_register_settings');

function simple_smtp_mailer_register_settings() {
    register_setting(
        'simple_smtp_mailer_option_group',
        SIMPLE_SMTP_MAILER_OPTIONS,
        'simple_smtp_mailer_sanitize_settings'
    );
    add_settings_section(
        'simple_smtp_mailer_smtp_section',
        'SMTP Configuration', 
        'simple_smtp_mailer_smtp_section_text',
        'simple-smtp-mailer-settings'
    );
    add_settings_field(
        'smtp_host',
        'SMTP Host', 
        'simple_smtp_mailer_field_callback',
        'simple-smtp-mailer-settings',
        'simple_smtp_mailer_smtp_section',
        ['id' => 'host', 'type' => 'text', 'label' => 'SMTP server address (e.g., smtp.gmail.com)'] 
    );
    add_settings_field(
        'smtp_port',
        'SMTP Port', 
        'simple_smtp_mailer_field_callback',
        'simple-smtp-mailer-settings',
        'simple_smtp_mailer_smtp_section',
        ['id' => 'port', 'type' => 'number', 'label' => 'SMTP server port (e.g., 587 for TLS, 465 for SSL)'] 
    );
    add_settings_field(
        'smtp_secure',
        'Security', 
        'simple_smtp_mailer_field_select_callback',
        'simple-smtp-mailer-settings',
        'simple_smtp_mailer_smtp_section',
        ['id' => 'secure', 'options' => ['' => 'None', 'ssl' => 'SSL', 'tls' => 'TLS'], 'label' => 'Security method (TLS recommended for port 587, SSL for 465)'] 
    );
    add_settings_field(
        'smtp_auth',
        'Authentication', 
        'simple_smtp_mailer_field_checkbox_callback',
        'simple-smtp-mailer-settings',
        'simple_smtp_mailer_smtp_section',
        ['id' => 'auth', 'label' => 'Require SMTP authentication (most servers require it)'] 
    );
    add_settings_field(
        'smtp_username',
        'SMTP Username', 
        'simple_smtp_mailer_field_callback',
        'simple-smtp-mailer-settings',
        'simple_smtp_mailer_smtp_section',
        ['id' => 'username', 'type' => 'text', 'label' => 'Username for the SMTP server (usually your email address)'] 
    );
	add_settings_field(
		'smtp_password',
		'SMTP Password',
		'simple_smtp_mailer_password_field_callback',
		'simple-smtp-mailer-settings', 
		'simple_smtp_mailer_smtp_section',
		['id' => SIMPLE_SMTP_MAILER_PASSWORD_FIELD, 'type' => 'password', 'label' => 'SMTP password or app-specific key. Stored encrypted. Leave empty to keep unchanged.']
	);
	
    add_settings_field(
        'smtp_from_email',
        'From Email', 
        'simple_smtp_mailer_field_callback',
        'simple-smtp-mailer-settings',
        'simple_smtp_mailer_smtp_section',
        ['id' => 'from_email', 'type' => 'email', 'label' => '"From" email address (if empty, uses the WP admin email)'] 
    );
    add_settings_field(
        'smtp_from_name',
        'From Name', 
        'simple_smtp_mailer_field_callback',
        'simple-smtp-mailer-settings',
        'simple_smtp_mailer_smtp_section',
        ['id' => 'from_name', 'type' => 'text', 'label' => '"From" name (if empty, uses the site title)'] 
    );
    add_settings_field(
        'smtp_debug',
        'PHPMailer Debug', 
        'simple_smtp_mailer_field_select_callback',
        'simple-smtp-mailer-settings',
        'simple_smtp_mailer_smtp_section',
        ['id' => 'debug', 'options' => ['0' => '0 (Disabled)', '1' => '1 (Client)', '2' => '2 (Server)', '3' => '3 (Connection)', '4' => '4 (Full)'], 'label' => 'PHPMailer debug level. Levels > 0 write to PHP error logs.'] 
    );
}

// Callback text for the section
function simple_smtp_mailer_smtp_section_text() {
    echo '<p>Enter your SMTP server details to send emails. The password will be encrypted and stored in the database.</p>'; 
}

// Callback renders the input fields
function simple_smtp_mailer_field_callback($args) {
    $options = get_option(SIMPLE_SMTP_MAILER_OPTIONS);
    $id = $args['id'];
    $type = $args['type'];
    $label = $args['label'];

    $value = isset($options[$id]) ? esc_attr($options[$id]) : '';

    echo "<input type='{$type}' id='{$id}' name='" . SIMPLE_SMTP_MAILER_OPTIONS . "[{$id}]' value='{$value}' class='regular-text' />";
    echo "<p class='description'>{$label}</p>";
}

// Callback password field (encryption)
function simple_smtp_mailer_password_field_callback($args) {
    $id = $args['id'];
    $type = $args['type'];
    $label = $args['label'];

    echo "<input type='{$type}' id='{$id}' name='" . SIMPLE_SMTP_MAILER_OPTIONS . "[{$id}]' value='' class='regular-text' autocomplete='new-password' />";
    echo "<p class='description'>{$label}</p>";
}

// Callback Select field
function simple_smtp_mailer_field_select_callback($args) {
    $options = get_option(SIMPLE_SMTP_MAILER_OPTIONS);
    $id = $args['id'];
    $select_options = $args['options'];
    $label = $args['label'];

    $value = isset($options[$id]) ? esc_attr($options[$id]) : '';

    echo "<select id='{$id}' name='" . SIMPLE_SMTP_MAILER_OPTIONS . "[{$id}]'>";
    foreach ($select_options as $val => $text) {
        echo "<option value='{$val}'" . selected($value, $val, false) . ">{$text}</option>";
    }
    echo "</select>";
    echo "<p class='description'>{$label}</p>";
}

// Callback Checkbox field
function simple_smtp_mailer_field_checkbox_callback($args) {
    $options = get_option(SIMPLE_SMTP_MAILER_OPTIONS);
    $id = $args['id'];
    $label = $args['label'];

    $checked = isset($options[$id]) ? (bool) $options[$id] : false;

    echo "<input type='checkbox' id='{$id}' name='" . SIMPLE_SMTP_MAILER_OPTIONS . "[{$id}]' value='1'" . checked(true, $checked, false) . " />";
    echo "<label for='{$id}'>{$label}</label>";
}

// Callback to render the settings page
function simple_smtp_mailer_sanitize_settings($input) {
    $sanitized_input = array();
    $old_options = get_option(SIMPLE_SMTP_MAILER_OPTIONS);

    if (isset($input['host'])) {
        $sanitized_input['host'] = sanitize_text_field($input['host']);
    }
    if (isset($input['port'])) {
        $sanitized_input['port'] = absint($input['port']);
    }
    if (isset($input['auth'])) {
        $sanitized_input['auth'] = (bool) $input['auth'];
    } else {
        $sanitized_input['auth'] = false;
    }
    if (isset($input['username'])) {
        $sanitized_input['username'] = sanitize_text_field($input['username']);
    }
    if (isset($input['secure'])) {
        $sanitized_input['secure'] = sanitize_text_field($input['secure']);
    }
    if (isset($input['from_email'])) {
        $sanitized_input['from_email'] = sanitize_email($input['from_email']);
    }
    if (isset($input['from_name'])) {
        $sanitized_input['from_name'] = sanitize_text_field($input['from_name']);
    }
    if (isset($input['debug'])) {
        $sanitized_input['debug'] = absint($input['debug']);
    }

    $password_field = SIMPLE_SMTP_MAILER_PASSWORD_FIELD; // 'password'
    $password_iv_field = SIMPLE_SMTP_MAILER_PASSWORD_IV_FIELD; // 'password_iv'

    if (isset($input[$password_field]) && !empty($input[$password_field])) {
        $encrypted_data = simple_smtp_mailer_encrypt($input[$password_field]);
        if ($encrypted_data !== false) {
            list($encrypted_password_base64, $iv_base64) = explode('::', $encrypted_data);
            $sanitized_input[$password_field] = $encrypted_password_base64;
            $sanitized_input[$password_iv_field] = $iv_base64;
        } else {
            if (isset($old_options[$password_field])) {
                $sanitized_input[$password_field] = $old_options[$password_field];
                $sanitized_input[$password_iv_field] = isset($old_options[$password_iv_field]) ? $old_options[$password_iv_field] : '';
            }
            add_settings_error(
                'simple-smtp-mailer-settings',
                'encryption_failed',
                'Error encrypting the password. The password was not saved.', 
                'error'
            );
        }
    } else {
        if (isset($old_options[$password_field])) {
            $sanitized_input[$password_field] = $old_options[$password_field];
            $sanitized_input[$password_iv_field] = isset($old_options[$password_iv_field]) ? $old_options[$password_iv_field] : '';
        } else {
            $sanitized_input[$password_field] = '';
            $sanitized_input[$password_iv_field] = '';
        }
    }
    return $sanitized_input;
}

/**
 * ==================================================================
 * Configure PHPMailer to use SMTP with stored settings
 * ==================================================================
 */
add_action('phpmailer_init', 'simple_smtp_mailer_config');

function simple_smtp_mailer_config($phpmailer) {
    $options = get_option(SIMPLE_SMTP_MAILER_OPTIONS);

    $password_field = SIMPLE_SMTP_MAILER_PASSWORD_FIELD;
    $password_iv_field = SIMPLE_SMTP_MAILER_PASSWORD_IV_FIELD;

    $decrypted_password = false;
    if (isset($options[$password_field], $options[$password_iv_field]) && !empty($options[$password_field])) {
        $encoded_ciphertext = $options[$password_field] . '::' . $options[$password_iv_field];
        $decrypted_password = simple_smtp_mailer_decrypt($encoded_ciphertext);

        if ($decrypted_password === false) {
            error_log("[Simple SMTP Mailer] Failed to decrypt password. Verify WP salts and stored password."); 
            unset($options['host']); // Forces to use PHP mail() if decryption fails
        }
    }

    if (
        isset($options['host']) && !empty($options['host']) &&
        isset($options['port']) && !empty($options['port']) &&
        isset($options['username']) && !empty($options['username'])
    ) {
        $needs_auth = isset($options['auth']) ? (bool) $options['auth'] : true;

        if ($needs_auth && $decrypted_password === false) {
            error_log("[Simple SMTP Mailer] Incomplete configuration: Authentication enabled but password is invalid or could not be decrypted."); 
            unset($options['host']);
        } else {
            $phpmailer->isSMTP();
            $phpmailer->Host       = $options['host'];
            $phpmailer->Port       = (int) $options['port'];
            $phpmailer->SMTPAuth   = $needs_auth;
            $phpmailer->Username   = $options['username'];
            $phpmailer->Password   = $needs_auth ? $decrypted_password : '';
            $phpmailer->SMTPSecure = isset($options['secure']) && !empty($options['secure']) ? $options['secure'] : '';

            if (isset($options['from_email']) && is_email($options['from_email'])) {
                $phpmailer->From = $options['from_email'];
                if (isset($options['from_name']) && !empty($options['from_name'])) {
                    $phpmailer->FromName = $options['from_name'];
                } else {
                    $phpmailer->FromName = get_bloginfo('name');
                }
            } else {
                if (isset($options['from_name']) && !empty($options['from_name'])) {
                    $phpmailer->FromName = $options['from_name'];
                } else {
                    $phpmailer->FromName = get_bloginfo('name');
                }
            }

            if (isset($options['debug']) && $options['debug'] > 0) {
                $phpmailer->SMTPDebug = (int) $options['debug'];
                $phpmailer->Debugoutput = function($str, $level) {
                    error_log("[PHPMailer Debug $level]: $str");
                };
            } else {
                $phpmailer->SMTPDebug = 0;
                $phpmailer->Debugoutput = 'html';
            }
            $phpmailer->CharSet = 'UTF-8';
        }
    }

    if (!isset($options['host']) || empty($options['host'])) {
        $phpmailer->isMail();
        $phpmailer->Host = '';
        $phpmailer->Port = '';
        $phpmailer->SMTPAuth = false;
        $phpmailer->Username = '';
        $phpmailer->Password = '';
        $phpmailer->SMTPSecure = '';
        $phpmailer->SMTPDebug = 0;
        $phpmailer->Debugoutput = 'html';
    }
}

/**
 * ==================================================================
 * Admin Panel
 * ==================================================================
 */
add_action('admin_menu', 'simple_smtp_mailer_menu');

function simple_smtp_mailer_menu() {
    add_menu_page(
        'Simple SMTP Mailer',
        'SMTP Mailer',
        'manage_options',
        'simple-smtp-mailer-settings',
        'simple_smtp_mailer_settings_page',
        'dashicons-email',
        100
    );
    add_submenu_page(
        'simple-smtp-mailer-settings',
        'SMTP Configuration', 
        'Configuration', 
        'manage_options',
        'simple-smtp-mailer-settings',
        'simple_smtp_mailer_settings_page'
    );
    add_submenu_page(
        'simple-smtp-mailer-settings',
        'Send Test Email', 
        'Send Email', 
        'manage_options',
        'simple-smtp-mailer-test',
        'simple_smtp_mailer_test_page'
    );
}

// SMTP Options Page 
function simple_smtp_mailer_settings_page() {
    if (!extension_loaded('openssl')) {
        echo '<div class="notice notice-error"><p>The OpenSSL PHP extension is not enabled. Password encryption will not work. Please contact your hosting provider to enable it.</p></div>'; 
    }
    if (!defined('AUTH_KEY') || !defined('SECURE_AUTH_KEY') || !defined('LOGGED_IN_KEY') || !defined('NONCE_KEY') || !defined('AUTH_SALT') || !defined('SECURE_AUTH_SALT') || !defined('LOGGED_IN_SALT') || !defined('NONCE_SALT')) {
        echo '<div class="notice notice-error"><p>Security constants (SALTS/KEYS) are not defined in your wp-config.php file. Password encryption will not work properly without them. Please ensure they are present.</p></div>'; 
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('simple_smtp_mailer_option_group');
            do_settings_sections('simple-smtp-mailer-settings');
            submit_button();
            ?>
        </form>
        <p>To view PHPMailer debug messages (if debug level > 0), you may need to enable WordPress debug logging by adding these lines to <code>wp-config.php</code>:</p>
        <pre><code>
define('WP_DEBUG', true);
define('WP_DEBUG_DISPLAY', false);
define('WP_DEBUG_LOG', true);
        </code></pre>
        <p>Logs will be saved in <code>wp-content/debug.log</code>.</p> 
    </div>
    <?php
}

function simple_smtp_mailer_test_page() {
    if (isset($_POST['simple_smtp_mailer_nonce']) && wp_verify_nonce($_POST['simple_smtp_mailer_nonce'], 'send_simple_smtp_email')) {
        $to = sanitize_email($_POST['to']);
        $subject = sanitize_text_field($_POST['subject']);
        $message = wp_kses_post($_POST['message']);

        $headers = array();
        $headers[] = 'Content-Type: text/html; charset=UTF-8'; // Forzamos HTML

        $result = wp_mail($to, $subject, $message, $headers);

        if ($result) {
            echo '<div class="notice notice-success"><p>Email sent successfully (via SMTP if configured).</p></div>'; 
        } else {
            echo '<div class="notice notice-error"><p>Failed to send email. Please <a href="' . admin_url('admin.php?page=simple-smtp-mailer-settings') . '">verify your SMTP configuration</a> in the Settings page. If debug is enabled, check PHP error logs for details.</p></div>'; 
        }
    }
    // Show form to send a test email
    ?>
    <div class="wrap">
        <h1>Simple SMTP Mailer</h1>
        <p>Send a test or manual email. SMTP configuration is managed in the <a href="<?php echo admin_url('admin.php?page=simple-smtp-mailer-settings'); ?>">Configuration</a> submenu.</p> 

        <h2>Send Manual Email</h2> 
        <form method="post" action="">
            <?php wp_nonce_field('send_simple_smtp_email', 'simple_smtp_mailer_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="to">Recipient:</label></th> 
                    <td><input type="email" name="to" id="to" required class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="subject">Subject:</label></th> 
                    <td><input type="text" name="subject" id="subject" required class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="message">Message (HTML allowed):</label></th> 
                    <td><textarea name="message" id="message" rows="10" class="large-text" required></textarea></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" value="Send Email" class="button button-primary"> 
            </p>
        </form>
    </div>
    <?php
}

/**
 * ==================================================================
 * Activation/Deactivation Hooks (Optional)
 * ==================================================================
 */
register_activation_hook(__FILE__, 'simple_smtp_mailer_activate');
function simple_smtp_mailer_activate() {
    // Opcional:
    // $default_options = array('port' => 587, 'auth' => true, 'secure' => 'tls');
    // add_option(SIMPLE_SMTP_MAILER_OPTIONS, $default_options);
    error_log("[Simple SMTP Mailer] Plugin activated"); 
}

register_deactivation_hook(__FILE__, 'simple_smtp_mailer_deactivate');
function simple_smtp_mailer_deactivate() {
    // Limpiar la opción al desactivar el plugin
    // delete_option(SIMPLE_SMTP_MAILER_OPTIONS);
    error_log("[Simple SMTP Mailer] Plugin deactivated"); 
}