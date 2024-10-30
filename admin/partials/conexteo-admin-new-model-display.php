<?php
// require class conexteo model
require_once plugin_dir_path(__FILE__) . '../../includes/class-conexteo-model.php';
$update = false;

if($model) {
    $update = true;
    $model = new ConexteoModel($model->id_model);
    // var_dump($model);exit;
    $name = $model->name;
    $sender = $model->sender;
    $message = $model->message;
    $stop = $model->stop;
    $event_type = $model->event_type;
    $delay = $model->delay;
    $delay_multiplier = $model->delay_multiplier;
    $status = $model->status;
}
else {
    $model = new ConexteoModel();
}

if(isset($_POST['submit'])) {
    $name = sanitize_text_field($_POST['model_name']);
    $sender = sanitize_text_field($_POST['model_sender']);
    $message = sanitize_text_field($_POST['model_message']);
    $stop = sanitize_text_field($_POST['model_stop']);
    $event_type = sanitize_text_field($_POST['model_event_type']);
    $delay = sanitize_text_field($_POST['model_delay']);
    $delay_multiplier = sanitize_text_field($_POST['model_delay_multiplier']);
    $status = sanitize_text_field($_POST['model_status']);

    if(empty($name)) {
        $invalid_name = true;
    }

    if(empty($sender) || strlen($sender) > 11) {
        $invalid_sender = true;
    }

    if(empty($message)) {
        $invalid_message = true;
    }

    if(!isset($invalid_name) && !isset($invalid_sender) && !isset($invalid_message)) {
        $model->name = $name;
        $model->sender = $sender;
        $model->message = $message;
        $model->stop = $stop;
        $model->event_type = $event_type;
        $model->delay = $delay;
        $model->delay_multiplier = $delay_multiplier;
        $model->status = $status;

        if($update) {
            $model->update();
        }
        else {
            $model->save();
        }

        wp_redirect(admin_url('admin.php?page=conexteo-models'));
    }
}
?>

<div class="wrap">
    <?php if(!$model) { ?>
        <h1 class="wp-heading-inline">Créer un nouveau modèle de SMS</h1>
    <?php  } else { ?>
        <h1 class="wp-heading-inline">Modifier le modèle de SMS</h1>
    <?php } ?>
    <a href="?page=conexteo-models" class="page-title-action">Liste des modèles</a>

    <div class="form-wrap">
        <?php if(!$model) { ?>
            <h2>Ajouter un nouveau modèle</h2>
        <?php } ?>
        <form id="add-model" method="post" action="#">
            <div class="form-field form-required term-name-wrap<?php echo isset($invalid_name) ? ' form-invalid' : ''; ?>">
                <label for="model_name">Nom du modèle (requis)</label>
                <input name="model_name" id="model_name" type="text" size="40" aria-required="true" aria-describedby="model_name-description" value="<?php echo isset($name) ? esc_html($name) : ''; ?>">
                <p id="model_name-description">Le nom est la façon dont il apparaît dans le tableau</p>
            </div>

            <div class="form-field form-required term-sender-wrap<?php echo isset($invalid_sender) ? ' form-invalid' : ''; ?>">
                <label for="model_sender">SENDER (requis)</label>
                <input name="model_sender" id="model_sender" type="text" maxlength="11" size="40" aria-describedby="model_sender-description" placeholder="11 caractères maximum" value="<?php echo isset($sender) ? esc_attr($sender) : ''; ?>">
                <p id="model_sender-description">Le SENDER sera le nom utilisé pour l'envoi du message, max 11 caractères alphanumériques</p>
            </div>

            <div class="form-field form-required term-message-wrap<?php echo isset($invalid_message) ? ' form-invalid' : ''; ?>">
                <label for="model_message">Message (requis)</label>
                <textarea name="model_message" id="model_message" rows="2" cols="40" aria-describedby="description-description"><?php echo isset($message) ? esc_html($message) : ''; ?></textarea>
                <p id="description-description">----</p>
            </div>

            <div class="form-field term-message-wrap">
                <label for="model_stop">Avec STOP ?</label>
                <select name="model_stop">
                    <option value="1"<?php echo (isset($stop) && $stop == '1') ? ' selected' : ''; ?>>Oui</option>
                    <option value="0"<?php echo (isset($stop) && $stop == '0') ? ' selected' : ''; ?>>Non</option>
                </select>
            </div>

            <div class="form-field term-stop-wrap">
                <label for="model_event_type">Type d'événement</label>
                <select name="model_event_type">
                    <option value="order"<?php echo (isset($event_type) && $event_type == 'order') ? ' selected' : ''; ?>>Statut de commande</option>
                    <option value="cart"<?php echo (isset($event_type) && $event_type == 'cart') ? ' selected' : ''; ?>>Panier abandonné</option>
                </select>
            </div>

            <div class="form-field term-status-wrap" id="status_dynamic_display">
                <label for="model_status">Statut de commande</label>
                <select name="model_status">
                    <option value="wc-pending"<?php echo (isset($status) && $status == 'wc-pending') ? ' selected' : ''; ?>>En attente de paiement</option>
                    <option value="wc-processing"<?php echo (isset($status) && $status == 'wc-processing') ? ' selected' : ''; ?>>En cours de traitement</option>
                    <option value="wc-on-hold"<?php echo (isset($status) && $status == 'wc-on-hold') ? ' selected' : ''; ?>>En attente</option>
                    <option value="wc-completed"<?php echo (isset($status) && $status == 'wc-completed') ? ' selected' : ''; ?>>Terminé</option>
                    <option value="wc-cancelled"<?php echo (isset($status) && $status == 'wc-cancelled') ? ' selected' : ''; ?>>Annulé</option>
                    <option value="wc-refunded"<?php echo (isset($status) && $status == 'wc-refunded') ? ' selected' : ''; ?>>Remboursé</option>
                    <option value="wc-failed"<?php echo (isset($status) && $status == 'wc-failed') ? ' selected' : ''; ?>>Échec du paiement</option>
                </select>
            </div>

             <div class="form-field term-delay-wrap">
                <label for="model_delay">Délais avant l'envoi du SMS</label>
                <input name="model_delay" type="number" value="<?php echo (isset($delay)) ? esc_attr($delay) : '0'; ?>" size="40">
            </div>


            <div class="form-field term-delay-wrap">
                <label for="model_delay_multiplier">Unité de temps</label>
                <select name="model_delay_multiplier">
                    <option value="0"<?php echo (isset($delay_multiplier) && $delay_multiplier == '0') ? ' selected' : ''; ?>>Minute(s)</option>
                    <option value="60"<?php echo (isset($delay_multiplier) && $delay_multiplier == '60') ? ' selected' : ''; ?>>Heure(s)</option>
                    <option value="1440"<?php echo (isset($delay_multiplier) && $delay_multiplier == '1440') ? ' selected' : ''; ?>>Jour(s)</option>
                </select>
            </div>

            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $update ? 'Modifier' : 'Créer'; ?> ce modèle"><span class="spinner"></span>
            </p>
        </form>
    </div>
</div>