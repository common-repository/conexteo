<?php

/**
 * Provide a admin area view for the models
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://ecomiz.com
 * @since      1.0.0
 *
 * @package    Conexteo
 * @subpackage Conexteo/admin/partials
 */

function order_status_id_translated($slug) {
    switch($slug) {
        case 'wc-pending':
            return 'En attente de paiement';
        case 'wc-processing':
            return 'En cours de traitement';
        case 'wc-on-hold':
            return 'En attente';
        case 'wc-completed':
            return 'Terminé';
        case 'wc-cancelled':
            return 'Annulé';
        case 'wc-refunded':
            return 'Remboursé';
        case 'wc-failed':
            return 'Échoué';
        default:
            return 'Inconnu';
    }
}
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Liste des modèles</h1>
    <a href="?page=conexteo-new-model" class="page-title-action">Ajouter</a>

    <?php echo isset($message) ? esc_html($message) : ''; ?>

    <table class="wp-list-table widefat fixed striped table-view-list" id="models">
        <thead>
        <tr>
            <th>Nom</th>
            <th>SENDER</th>
            <th style="width:30%;">Message</th>
            <th>Avec STOP ?</th>
            <th>Type d'événement</th>
            <th>Délais</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php
            if($models && !empty($models)) {
                $delay = [
                    '0' => 'minute',
                    '60' => 'heure',
                    '1440' => 'jour',
                ];
        ?>
                <?php
                    foreach($models as $model) {
                ?>
                    <tr>
                        <td><?php echo esc_html($model->name); ?></td>
                        <td><?php echo esc_html($model->sender); ?></td>
                        <td><?php echo esc_html($model->message); ?></td>
                        <td><?php echo $model->stop ? '<span class="dashicons dashicons-yes"></span>' : 'Non'; ?></td>
                        <td><?php echo $model->event_type == 'order' ? 'Statut commande' : 'Panier abandonné'; ?></td>
                        <td><?php echo $model->delay ? esc_html($model->delay) . ' ' . esc_html($delay[$model->delay_multiplier]) . '(s)' : 'Instantané'; ?></td>
                        <td><?php echo $model->event_type == 'order' ? order_status_id_translated($model->status) : '--'; ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=conexteo-new-model&model_id=' . $model->id_model); ?>" class="conexteo-btn edit"><span class="dashicons dashicons-edit"></span></a>
                            <a href="<?php echo admin_url('admin.php?page=conexteo-models&action=delete&id=' . $model->id_model); ?>" class="conexteo-btn delete" onclick="return confirm('Etes vous sûr de supprimer ce modèle ?')"><span class="dashicons dashicons-trash"></span></a>
                        </td>
                    </tr>
                <?php
                    }
                ?>
        <?php
            }
            else {
        ?>
            <tr>
                <td colspan="8">
                    <p class="notice notice-error"> 
                        <span>Aucun modèle trouvé, créez en un dès maintenant !</span>
                    </p>
                </td>
            </tr>
        <?php
            }
        ?>
        </tbody>
    </table>
    <div class="tablenav bottom">
        <div class="tablenav-pages one-page">
            <span class="displaying-num"><?php echo count($models); ?> éléments</span>
        </div>
        <br class="clear">
	</div>
</div>