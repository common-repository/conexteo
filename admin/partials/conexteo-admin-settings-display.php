<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://ecomiz.com
 * @since      1.0.0
 *
 * @package    Conexteo
 * @subpackage Conexteo/admin/partials
 */
?>
<div class="wrap">
    <div id="icon-themes" class="icon32"></div>
    <h2>Configuration de votre compte Conexteo</h2>

        <?php
            if($connection_ok) {
        ?>
            <div class="notice notice-success settings-error is-dismissible"> 
                <p><strong>Connexion à Conexteo établie.</strong></p>
                <button type="button" class="notice-dismiss"><span class="screen-reader-text">Ignorer cette notification.</span></button>
            </div>

            <p>
                <?php
                    if($sync_customers) {
                ?>
                    <a href="#" class="button button-primary" id="sync_now" data-url="<?php echo esc_html($cron_sync_url); ?>"><span class="dashicons dashicons-cloud-upload" style="margin-top:4px;"></span> &nbsp;&nbsp; Synchroniser maintenant</a>
                <?php
                    }
                ?>
                <a class="button button-primary" id="send-msg-panel" role="button">Envoyer un message</a>
            </p>
        <?php
            }
            else {
        ?>
            <div class="notice notice-error settings-error is-dismissible"> 
                <p><strong>Connexion à Conexteo impossible.</strong></p>
                <p>Assurez-vous d'avoir bien renseigné vos identifiants d'accès à Conexteo.</p>
                <p>Afin d’obtenir votre AppID & API Key, il faut avoir créé un compte Conexteo. Vous pouvez vous créer un compte Conexteo en cliquant ici: <a href="https://app.conexteo.com/inscription" target="_blank">app.conexteo.com/inscription</a>.<br />Si vous avez déjà créé un compte, il suffit de vous connecter et d’aller dans le menu de gauche “Mon Compte” > “Api / Webhook”. Vous trouverez ici ces informations et il vous suffira de les copier puis coller dans cette page.</p>
                <button type="button" class="notice-dismiss"><span class="screen-reader-text">Ignorer cette notification.</span></button>
            </div>
        <?php
            }
        ?>

        <div id="conexteo_message"></div>

        <div id="conexteo_send_message" style="display:none;">
            <form>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">SENDER</th>
                            <td>
                                <input type="text" id="sender" placeholder="11 caractères maximum" size="40" maxlength="11">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Message</th>
                            <td>
                                <textarea id="message-to-send" placeholder="Message à destination des contacts de la liste" rows="4" cols="40"></textarea>
                                <br />
                                <span id="conexteo_message-counter-sms">0 SMS</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">STOP</th>
                            <td>
                                <input type="checkbox" value="0" id="message-to-send-stop">
                                <label for="message-to-send-stop">
                                    Inclure un STOP dans le message
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                Liste de contacts
                            </th>
                            <td>
                                <select name="contactlist-to-send" id="contactlist-to-send">
                                    <?php foreach($lists as $id => $name) { ?>
                                        <option value="<?php echo esc_html($id); ?>"><?php echo esc_html($name); ?></option>
                                    <?php } ?>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <button id="send-message" class="button button-primary" data-url="<?php echo esc_html($send_message_url); ?>">Envoyer</button>
            </form>
        </div>

    <?php
        if($credits) {
            echo '<p>Vous avez ' . esc_html($credits['credits']) . ' crédits disponibles (' . esc_html($credits['price']) . ' €) information mise à jour le ' . date('d.m.Y à H:i:s') . '.</p>';
        }
    ?>
        <p>
            Si vous souhaitez, vous pouvez acheter des crédits SMS sur votre compte conexteo.
            Cliquez ici: <a href="https://app.conexteo.com/" target="_blank">app.conexteo.com</a> puis allez dans le menu de gauche “Boutique” > “SMS Premium” et choisissez le pack qui vous correspond.
            <br />
            Une fois achetés, les crédits s’ajouteront automatiquement sur votre compte Conexteo et sur votre interface du module Conexteo Prestashop.
        </p>
    <?php
        settings_errors();
    ?>
    <form method="POST" action="options.php">
        <?php
            settings_fields( 'conexteo_general_settings' );
            do_settings_sections( 'conexteo_general_settings' );
        ?>
        <?php submit_button(); ?>
    </form>

    <?php
        if($connection_ok) {
    ?>
    <p>
        Lien de la CRON à configurer pour l'envoi des messages de panier abandonnés: <br />
        <a href="<?php echo esc_html($cron_cart_url); ?>" target="_blank"><?php echo esc_html($cron_cart_url); ?></a> (à exécuter toutes les heures)
    </p>
    <?php
        if($sync_customers) {
    ?>
        <i class="icon icon-link"></i> La synchronisation des clients est activée. Voici l'URL de l'appel CRON à configurer :
        <br />
        <a href="<?php echo esc_html($cron_sync_url); ?>" target="_blank"><?php echo esc_html($cron_sync_url); ?></a>
    <?php
            }
        }
    ?>
</div>