<?php
class ConexteoModel {
    private $id;
    public $id_model;
    public $name;
    public $sender;
    public $message;
    public $stop;
    public $event_type;
    public $delay;
    public $status;

    public function __construct($id = null)
    {
        if ($id) {
            $this->id = $id;
            $this->get();
        }
    }

    public function getByStatusAndType($status, $event_type)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $sql = 'SELECT * FROM `' . $prefix . 'conexteo_models` WHERE `status` = "' . $status . '" AND `event_type` = "' . $event_type . '" ORDER BY `delay` ASC LIMIT 1';
        $result = $wpdb->get_results($sql, ARRAY_A);

        // fill object
        if ($result) {
            $this->id_model = $result[0]['id_model'];
            $this->name = $result[0]['name'];
            $this->sender = $result[0]['sender'];
            $this->message = $result[0]['message'];
            $this->stop = $result[0]['stop'];
            $this->event_type = $result[0]['event_type'];
            $this->delay = $result[0]['delay'];
            $this->delay_multiplier = $result[0]['delay_multiplier'];
            $this->status = $result[0]['status'];
        }
        return $this;
    }

    public function save()
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $sql = 'INSERT INTO `' . $prefix . 'conexteo_models` (`name`, `sender`, `message`, `stop`, `event_type`, `delay`, `delay_multiplier`, `status`) VALUES ("' . $this->name . '", "' . $this->sender . '", "' . $this->message . '", ' . (int)$this->stop . ', "' . $this->event_type . '", ' . (int)$this->delay . ', ' . (int)$this->delay_multiplier . ', "' . $this->status . '")';
        $wpdb->query($sql);
        return $wpdb->insert_id;
    }

    public function get()
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $sql = 'SELECT * FROM `' . $prefix . 'conexteo_models` WHERE `id_model` = ' . (int)$this->id;
        $result = $wpdb->get_results($sql, ARRAY_A);

        // fill object
        if ($result) {
            $this->id_model = $result[0]['id_model'];
            $this->name = $result[0]['name'];
            $this->sender = $result[0]['sender'];
            $this->message = $result[0]['message'];
            $this->stop = $result[0]['stop'];
            $this->event_type = $result[0]['event_type'];
            $this->delay = $result[0]['delay'];
            $this->delay_multiplier = $result[0]['delay_multiplier'];
            $this->status = $result[0]['status'];
        }
        return $this;
    }

    public function update()
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $sql = 'UPDATE `' . $prefix . 'conexteo_models` SET `name` = "' . $this->name . '", `sender` = "' . $this->sender . '", `message` = "' . $this->message . '", `stop` = ' . (int)$this->stop . ', `event_type` = "' . $this->event_type . '", `delay` = ' . (int)$this->delay . ', `delay_multiplier` = ' . (int)$this->delay_multiplier . ', `status` = "' . $this->status . '" WHERE `id_model` = ' . (int)$this->id;
        $wpdb->query($sql);
        return $this;
    }
}