<?php
/*
    Plugin Name: Fixes cmb2 repeatable meta field behaviour
    Description: Replaces cmb2 serialized method with WP standard separate rows/values
    Version: 1.0
    Plugin URI: https://github.com/lophas/cmb2-repeatable-field-fix
    GitHub Plugin URI: https://github.com/lophas/cmb2-repeatable-field-fix
    Author: Attila Seres
    Author URI:
*/
if (!class_exists('cmb2_fix')) :
class cmb2_fix
{
    const TYPES = ['term', 'user', 'post'];
    private static $_instance;
    public function instance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance =  new self();
        }
        return self::$_instance;
    }
    public function __construct()
    {
        add_filter('cmb2_override_meta_value', function ($data, $object_id, $a, $instance) {
            if (!in_array($a['type'], self::TYPES) || !$a['repeat']) {
                return $data;
            }
            $data = get_metadata($a['type'], $a['id'], $a['field_id'], false);
            return $data;
        }, 10, 4);

        add_filter('cmb2_override_meta_save', function ($override, $a, array $args, $instance) {
            if (!in_array($a['type'], self::TYPES) || !$a['repeat']) {
                return $override;
            }
            global $wpdb;
            $table = $a['type'].'meta';
            $object_id = $a['type'].'_id';
            $sql = $wpdb->prepare('SELECT meta_id, meta_value FROM '.$wpdb->$table.' WHERE '.$object_id.' = %d AND meta_key = %s', $a['id'], $a['field_id']);
            $results = $wpdb->get_results($sql);
            $values = array_map(function ($v) {
                return $v->meta_value;
            }, $results);
            $toadd = array_unique(array_diff($a['value'], $values));
            $todelete = array_diff($values, $a['value']);
            foreach ($results as $r) {
                if (in_array($r->meta_value, $todelete)) {
                    delete_metadata_by_mid($a['type'], $r->meta_id);
                }
            }
            foreach ($toadd as $value) {
                $id = add_metadata($a['type'], $a['id'], $a['field_id'], $value, false);
            }
            return $id ? $id : false;
        }, 10, 4);
    }
} //cmb2_fix
cmb2_fix::instance();
endif;
