<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package     CodeIgniter
 * @author      ExpressionEngine Dev Team
 * @copyright   Copyright (c) 2008 - 2011, EllisLab, Inc.
 * @license     http://codeigniter.com/user_guide/license.html
 * @link        http://codeigniter.com
 * @since       Version 1.0
 * @filesource
 */


// This function helps us to get the translated phrase from the file. If it does not exist this function will save the phrase and by default it will have the same form as given
if (!function_exists('get_phrase')) {
    function get_phrase($phrase = '')
    {
        $CI = get_instance();
        $CI->load->database();
        $CI->load->dbforge();
        if($CI->session->userdata('language')){
            $language_code = $CI->session->userdata('language');
        }else{
            $language_code = $CI->db->get_where('settings', array('key' => 'language'))->row()->value;
        }

        $key = strtolower(preg_replace('/\s+/', '_', $phrase));

        /**LANGUAGE HANDLING USING DATABASE**/
        // CHECK IF A COLUMN EXISTS IN LANGUAGE TABLE
        if (!$CI->db->field_exists($language_code, 'language')) {
            $fields = array(
                $language_code => array(
                    'type' => 'LONGTEXT',
                    'default' => null,
                    'null' => TRUE,
                    'collation' => 'utf8_unicode_ci'
                )
            );
            $CI->dbforge->add_column('language', $fields);
        }

        $phrase_query = $CI->db->get_where('language', array('phrase' => $key))->row_array();

        if (is_array($phrase_query) && count($phrase_query) > 0) {
            if (!empty($phrase_query[$language_code])) {
                return $phrase_query[$language_code];
            } else {
                $phrase = ucfirst(str_replace('_', ' ', $key));
                $checker = array('phrase' => $key);
                $updater = array($language_code => $phrase);
                $CI->db->where($checker);
                $CI->db->update('language', $updater);
                return $phrase;
            }
        } else {
            $phrase = ucfirst(str_replace('_', ' ', $key));
            $CI->db->insert('language', array('phrase' => $key, $language_code => $phrase));
            return $phrase;
        }
    }
}

if ( ! function_exists('api_phrase'))
{
    function api_phrase($phrase = '') {
        $CI = get_instance();
        $CI->load->database();
        $CI->load->dbforge();
        $language_code = $CI->db->get_where('settings', array('key' => 'language'))->row()->value;

        $key = strtolower(preg_replace('/\s+/', '_', $phrase));

        /**LANGUAGE HANDLING USING DATABASE**/
        // CHECK IF A COLUMN EXISTS IN LANGUAGE TABLE
        if (!$CI->db->field_exists($language_code, 'language')) {
            $fields = array(
                $language_code => array(
                    'type' => 'LONGTEXT',
                    'default' => null,
                    'null' => TRUE,
                    'collation' => 'utf8_unicode_ci'
                )
            );
            $CI->dbforge->add_column('language', $fields);
        }

        $phrase_query = $CI->db->get_where('language', array('phrase' => $key))->row_array();

        if (count($phrase_query) > 0) {
            if (!empty($phrase_query[$language_code])) {
                return $phrase_query[$language_code];
            } else {
                $phrase = ucfirst(str_replace('_', ' ', $key));
                $checker = array('phrase' => $key);
                $updater = array($language_code => $phrase);
                $CI->db->where($checker);
                $CI->db->update('language', $updater);
                return $phrase;
            }
        } else {
            $phrase = ucfirst(str_replace('_', ' ', $key));
            $CI->db->insert('language', array('phrase' => $key, $language_code => $phrase));
            return $phrase;
        }
    }
}

// This function helps us to get the translated phrase from the file. If it does not exist this function will save the phrase and by default it will have the same form as given
if (!function_exists('site_phrase')) {
    function site_phrase($phrase = '')
    {
        $CI = get_instance();
        $CI->load->database();
        $CI->load->dbforge();
        if(!$CI->session->userdata('language')){
            $CI->session->set_userdata('language', 'english');
        }
        $language_code = $CI->session->userdata('language');
        $key = strtolower(preg_replace('/\s+/', '_', $phrase));

        /**LANGUAGE HANDLING USING DATABASE**/
        // CHECK IF A COLUMN EXISTS IN LANGUAGE TABLE
        if (!$CI->db->field_exists($language_code, 'language')) {
            $fields = array(
                $language_code => array(
                    'type' => 'LONGTEXT',
                    'default' => null,
                    'null' => TRUE,
                    'collation' => 'utf8_unicode_ci'
                )
            );
            $CI->dbforge->add_column('language', $fields);
        }

        $phrase_query = $CI->db->get_where('language', array('phrase' => $key))->row_array();

        if (is_array($phrase_query) && count($phrase_query) > 0) {
            if (!empty($phrase_query[$language_code])) {
                return $phrase_query[$language_code];
            } else {
                $phrase = ucfirst(str_replace('_', ' ', $key));
                $checker = array('phrase' => $key);
                $updater = array($language_code => $phrase);
                $CI->db->where($checker);
                $CI->db->update('language', $updater);
                return $phrase;
            }
        } else {
            $phrase = ucfirst(str_replace('_', ' ', $key));
            $CI->db->insert('language', array('phrase' => $key, $language_code => $phrase));
            return $phrase;
        }
    }
}

// This function helps us to decode the language json and return that array to us
if (!function_exists('openJSONFile')) {
    function openJSONFile($code)
    {
        $CI = get_instance();
        $CI->load->database();
        $key_value_pairs = [];
        $language_query = $CI->db->get_where('language')->result_array();
        foreach ($language_query as $row) {
            $key = $row['phrase'];
            $value = !empty($row[$code]) ? $row[$code] : ucfirst(str_replace('_', ' ', $key));
            $key_value_pairs[$key] = $value;
        }
        return $key_value_pairs;
    }
}

// This function helps us to create a new json file for new language
if (!function_exists('saveDefaultJSONFile')) {
    function saveDefaultJSONFile($language_code)
    {
        $language_code = strtolower($language_code);
        if (!file_exists(APPPATH . 'language/' . $language_code . '.json')) {
            $fp = fopen(APPPATH . 'language/' . $language_code . '.json', 'w');
            $newLangFile = APPPATH . 'language/' . $language_code . '.json';
            $enLangFile   = APPPATH . 'language/english.json';
            copy($enLangFile, $newLangFile);
            fclose($fp);
        }
    }
}

// This function helps us to update a phrase inside the language file.
if (!function_exists('saveJSONFile')) {
    function saveJSONFile($language_code, $updating_key, $updating_value)
    {
        $CI = get_instance();
        $CI->load->database();

        $checker = array('phrase' => $updating_key);
        $updater = array($language_code => $updating_value);
        $CI->db->where($checker);
        $CI->db->update('language', $updater);
    }
}


// This function helps us to update a phrase inside the language file.
if (!function_exists('escapeJsonString')) {
    function escapeJsonString($value)
    {
        $value = str_replace('"', "'", $value);
        $escapers =     array("\\",     "/",   "\"",  "\n",  "\r",  "\t", "\x08", "\x0c");
        $replacements = array("\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t",  "\\f",  "\\b");
        $result = str_replace($escapers, $replacements, $value);
        return $result;
    }
}




// ------------------------------------------------------------------------
/* End of file language_helper.php */
/* Location: ./system/helpers/language_helper.php */
