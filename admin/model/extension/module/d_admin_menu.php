<?php

class ModelExtensionModuleDAdminMenu extends Model
{
    private $codename = 'd_admin_menu';
    private $route = 'extension/module/d_admin_menu';

    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->load->language($this->route);
        $this->load->model('extension/d_opencart_patch/modification');

        if (!defined('DIR_ROOT')) {
            define('DIR_ROOT', substr_replace(DIR_SYSTEM, '/', -8));
        }
    }

    public function installCompatibility()
    {
        $this->load->model('extension/d_opencart_patch/extension');
        $this->load->model('extension/d_opencart_patch/setting');
        if (!$this->model_extension_d_opencart_patch_extension->isInstalled($this->codename)) {
            $this->model_extension_d_opencart_patch_extension->install('module', $this->codename);
            $this->load->controller('extension/module/' . $this->codename . '/install');
        }
        $setting = $this->getSettings(0);
        if (empty($setting)) {
            $setting_name = "default-setting";
            $new_setting = array(
                "name"        => $setting_name,
                "status"      => 1,
                "work_mode"   => 1,
                "main_menu"   => array(
                    "version"   => VERSION,
                    "menu_data" => $this->model_extension_module_d_admin_menu->fillMenuWithLanguage($this->model_extension_module_d_admin_menu->fillMenuWithIds())
                ),
                "custom_menu" => array()
            );
            $setting_id = $this->model_extension_module_d_admin_menu->setSetting($setting_name, $new_setting, $this->store_id);
            if (!$setting_id) {
                throw new Exeption($this->language->get('error_setting_not_created'));
            }
            $this->load->controller($this->route . '/installEvents');
        }
    }

    public function checkMenuItem($codename)
    {
        $setting_before = $this->getSetting();
        foreach ($setting_before['custom_menu'] as $custom_menu_id => $value) {
            if (isset($value['id']) && $value['id'] == $codename) {
                return true;
            }
        }
        return false;
    }

    public function addMenuItem($codename, $data)
    {
        $link = (isset($data['link'])) ? $data['link'] : '';
        $custom_route = (isset($data['custom_route']) && $data['custom_route'] != False && $link) ? $link : False;
        $href_type = $this->get_href_type($link);
        $href = ($href_type == 'direct_link') ? $link : ('index.php?route=' . $link . '&');
        $children = (isset($data['children'])) ? $data['children'] : array();
        if (!empty($children)) {
            $children = $this->prepareMenuItemChildren($children);
        }
        $sort_order = (isset($data['sort_order'])) ? $data['sort_order'] : 0;

        $setting_before = $this->getSetting();
        $setting_before['custom_menu'][$codename] = array(
            "id"           => $codename,
            "icon"         => $data['icon'],
            "name"         => $data['name'],
            "custom_route" => $custom_route,
            "href"         => $href,
            "href_type"    => $href_type,
            "children"     => $children,
            "sort_order"   => $sort_order
        );
        if (!empty($setting_before)) {
            $this->editSetting($this->getCurrentSettingId(), $setting_before);
        }
    }

    public function prepareMenuItemChildren($menu_items = array())
    {
        $sub_items = array();
        $setting_before = $this->getSetting();
        $last_custom_item_id = 0 ;
        if (!empty($setting_before['custom_menu'])) {
            $last_custom_item_id = $setting_before['custom_menu'][count($setting_before['custom_menu'])]['id'];
        }

        foreach ($menu_items as $data) {
                $custom_route = (isset($data['custom_route']) && $data['custom_route'] != False) ? $data['link'] : False;
                $href_type = $this->get_href_type($data['link']);
                $href = ($href_type == 'direct_link') ? $data['link'] : ('index.php?route=' . $data['link'] . '&');
                $icon = (isset($data['icon'])) ? $data['icon'] : '';
                $children = (isset($data['children'])) ? $data['children'] : array();
                if (!empty($children)) {
                    $children = $this->prepareMenuItemChildren($children);
                }
                $sort_order = (isset($data['sort_order'])) ? $data['sort_order'] : 0;

                $sub_items[] = array(
                    "id"           => ++$last_custom_item_id,
                    "icon"         => $icon,
                    "name"         => $data['name'],
                    "custom_route" => $custom_route,
                    "href"         => $href,
                    "href_type"    => $href_type,
                    "children"     => $children,
                    "sort_order"   => $sort_order
                );
            }

        return $sub_items;

    }

    public function deleteMenuItem($codename)
    {
        $setting_before = $this->getSetting();
        if (empty($setting_before)) return;

        if (empty($setting_before['custom_menu'])) return;

        foreach ($setting_before['custom_menu'] as $custom_menu_id => $value) {
            if (isset($value['id']) && $value['id'] == $codename) {
                unset($setting_before['custom_menu'][$custom_menu_id]);
            }
        }
        $this->editSetting($this->getCurrentSettingId(), $setting_before);
    }

    public function installDatabase()
    {
        // install oc_dam_setting ('dam' for 'Dreamvention Admin Menu')
        $query = $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "dam_setting` (
        `setting_id` int(11) NOT NULL AUTO_INCREMENT,
        `store_id` int(11) NOT NULL,
        `name` varchar(32) NOT NULL,
        `value` text NOT NULL,
        PRIMARY KEY (`setting_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
    }

    public function uninstallDatabase()
    {
        $query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "dam_setting`");
    }

    public function getCurrentSettingId($id = 'd_admin_menu', $store_id = 0)
    {
        $this->load->model('setting/setting');
        $setting = $this->model_setting_setting->getSetting($id, $store_id);

        if (isset($this->request->get['setting_id'])) {
            $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "dam_setting`
                WHERE store_id = '" . (int)$store_id . "'
                AND setting_id = '" . (int)$this->request->get['setting_id'] . "'");
            if ($query->row) {
                return $query->row['setting_id'];
            }
        }

        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "dam_setting`
            WHERE store_id = '" . (int)$store_id . "'");
        if ($query->row) {
            return $query->row['setting_id'];
        }

        return false;
    }

    public function getSettingName($setting_id)
    {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "dam_setting`
            WHERE setting_id = '" . (int)$setting_id . "'");
        if (isset($query->row['name'])) {
            return $query->row['name'];
        } else {
            return false;
        }
    }

    public function getSetting($setting_id = false)
    {
        if (!$setting_id) {
            $setting_id = $this->getLastSettingId();
        }
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "dam_setting`
            WHERE setting_id = '" . (int)$setting_id . "'");

        $result = $query->row;
        if (isset($result['value'])) {
            $result['value'] = json_decode($result['value'], true);
        }

        return $result['value'];
    }

    public function getSettings($store_id)
    {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "dam_setting`
            WHERE store_id = '" . (int)$store_id . "'");

        $results = $query->rows;

        foreach ($results as $key => $result) {
            $results[$key]['value'] = json_decode($result['value'], true);
        }

        return $results;
    }

    public function getLastSettingId()
    {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "dam_setting`
            ORDER BY setting_id DESC LIMIT 1");

        $result = $query->row;
        if (isset($result['setting_id'])) {
            return (int)$result['setting_id'];
        } else {
            return false;
        }
    }

    public function setSetting($setting_name, $setting_value, $store_id = 0)
    {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "dam_setting`
            SET store_id = '" . (int)$store_id . "',
                `name` = '" . $this->db->escape($setting_name) . "',
                `value` = '" . $this->db->escape(json_encode($setting_value)) . "'");
        return $this->db->getLastId();
    }

    public function editSetting($setting_id, $data)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "dam_setting`
                SET `name` = '" . $this->db->escape($data['name']) . "',
                    `value` = '" . $this->db->escape(json_encode($data)) . "'
                WHERE setting_id = '" . (int)$setting_id . "'");
        return $setting_id;
    }

    public function deleteSetting($setting_id)
    {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "dam_setting` WHERE setting_id = '" . (int)$setting_id . "'");
    }


    /////////////////////////////////////////////////////////////////////////////////////
    /////////                         HELPER FUNCTIONS                          /////////
    /////////////////////////////////////////////////////////////////////////////////////

    public function some_sort(&$some_array)
    {
        usort($some_array, function ($a, $b) {
            if ($a['sort_order'] == $b['sort_order']) {
                return 0;
            }
            return ($a['sort_order'] < $b['sort_order']) ? -1 : 1;
        });
    }

    public function getAppropriateConfig()
    {
        if ((VERSION >= '2.0.0.0') && (VERSION < '2.0.3.1')) {

            $this->load->config('d_admin_menu/d_admin_menu_201');

        } elseif ((VERSION >= '2.0.3.1') && (VERSION < '2.1.0.0')) {

            $this->load->config('d_admin_menu/d_admin_menu_203');

        } elseif ((VERSION >= '2.1.0.0') && (VERSION < '2.3.0.0')) {

            $this->load->config('d_admin_menu/d_admin_menu_210');

        } elseif ((VERSION >= '2.3.0.0') && (VERSION < '3.0.0.0')) {

            $this->load->config('d_admin_menu/d_admin_menu_230');

        } elseif (VERSION >= '3.0.0.0') {

            $this->load->config('d_admin_menu/d_admin_menu_302');
        }
        return $this->config->get('menu_data');
    }

    public function getLanguage($menu_item_lng_name)
    {
        $config = $this->getAppropriateConfig();
        $lang_path = $config['lang_path'];

        $lng = new Language();
        $lng->load($lang_path);

        if ($lng->get($menu_item_lng_name) &&
            $lng->get($menu_item_lng_name) != $menu_item_lng_name) {
            return $lng->get($menu_item_lng_name);
        } else {
            return false;
        }
    }

    public function fillMenuWithIds()
    {
        $standart_menu = $this->getAppropriateConfig()['menu'];

        $temp_id = 1;

        foreach ($standart_menu as $sm_key => $sm_value) {
            $standart_menu[$sm_key]['id'] = $temp_id;
            $temp_id = $temp_id + 1;

            foreach ($sm_value['children'] as $sm_key_2 => $sm_value_2) {
                $standart_menu[$sm_key]['children'][$sm_key_2]['id'] = $temp_id;
                $temp_id = $temp_id + 1;

                foreach ($sm_value_2['children'] as $sm_key_3 => $sm_value_3) {
                    $standart_menu[$sm_key]['children'][$sm_key_2]['children'][$sm_key_3]['id'] = $temp_id;
                    $temp_id = $temp_id + 1;
                }
            }
        }

        return $standart_menu;
    }

    public function fillMenuWithLanguage($standart_menu)
    {
        // first level
        foreach ($standart_menu as $sm_key => $sm_value) {

            if (array_key_exists('lng_name', $sm_value)) {
                if ($this->getLanguage($sm_value['lng_name']) !== false) {
                    $standart_menu[$sm_key]['name'] = $this->getLanguage($sm_value['lng_name']);
                }
            }

            if ($sm_value['children']) {

                // second level
                foreach ($sm_value['children'] as $sm_key_2 => $sm_value_2) {

                    if (array_key_exists('lng_name', $sm_value_2)) {
                        if ($this->getLanguage($sm_value_2['lng_name']) !== false) {
                            $standart_menu[$sm_key]['children'][$sm_key_2]['name'] = $this->getLanguage($sm_value_2['lng_name']);
                        }
                    }

                    if ($sm_value_2['children']) {

                        // third level
                        foreach ($sm_value_2['children'] as $sm_key_3 => $sm_value_3) {

                            if (array_key_exists('lng_name', $sm_value_3)) {
                                if ($this->getLanguage($sm_value_3['lng_name']) !== false) {
                                    $standart_menu[$sm_key]['children'][$sm_key_2]['children'][$sm_key_3]['name'] = $this->getLanguage($sm_value_3['lng_name']);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $standart_menu;
    }

    public function getModulesForLinks()
    {
        $tmp_mdls_data = array();

        // before 230 fix
        $path_fix = (VERSION >= '2.3.0.0') ? 'extension/' : '';

        $cat_files = glob(DIR_APPLICATION . 'controller/extension/' . $path_fix . '*.php', GLOB_BRACE);

        foreach ($cat_files as $c_file) {
            $extension = basename($c_file, '.php');

            // Compatibility code for old extension folders
            $this->load->language('extension/' . $path_fix . $extension);
            if ($this->user->hasPermission('access', 'extension/' . $path_fix . $extension)) {
                $cat_files = glob(DIR_APPLICATION . 'controller/' . $path_fix . $extension . '/*.php', GLOB_BRACE);

                $tmp_mdls_data[] = array(
                    'code'  => $extension,
                    'text'  => strip_tags($this->language->get('heading_title')),
                    'extra' => $this->getExtensionList($extension)
                );
            }

        }
        $dream_folders = glob(DIR_APPLICATION . 'controller/extension/d_*/', GLOB_BRACE);
        foreach ($dream_folders as $c_file_key => $c_folder) {
            $extension = str_replace(DIR_APPLICATION, '', $c_folder);
            $extension = str_replace('controller/extension/', '', $extension);
            $extension = str_replace('/', '', $extension);
//          method_exists
            if (file_exists(DIR_APPLICATION.'controller/extension/module/'.$extension.'.php')){
                $this->load->language('extension/module/'.$extension);
                $tmp_mdls_data[] = array(
                    'code'  => $extension,
                    'text'  => strip_tags($this->language->get('heading_title')),
                    'extra' => $this->getExtensionList($extension)
                );
            }
        }
//        $dream_modules = glob(DIR_APPLICATION . 'controller/extension/d_*/*.php', GLOB_BRACE);
//        foreach ($dream_modules as $c_file_key=>$c_file) {
//            $extension = str_replace(DIR_APPLICATION,'',$c_file);
//            $extension = str_replace('controller/','',$extension);
//            $extension = str_replace('.php','',$extension);
//            $basename = basename($c_file,'.php');
//            if ($this->user->hasPermission('access', $extension)) {
//
//                $tmp_mdls_data[] = array(
//                    'code'  => $extension,
//                    'text'  => 'ss',
//                    'extra' => $this->getExtensionList($extension)
//                );
//                $extra_data[] = array(
//                    'name'      => $this->language->get('heading_title'),
//                    'shortname' => $extension,
//                    'edit'      => $path_fix . $category_shortname . '/' . $extension
//                );
//
//            }
//        }
        return $tmp_mdls_data;
    }

    private function getExtensionList($category_shortname)
    {
        $extra_data = array();

        // before 230 fix
        // about our modules?
        $path_fix = '';

        // ,' . $category_shortname . '

        // Compatibility code for old extension folders
        $files = glob(DIR_APPLICATION . 'controller/{' . $path_fix . $category_shortname . '}/*.php', GLOB_BRACE);

        if ($files) {
            foreach ($files as $file) {
                $extension = basename($file, '.php');
                $this->load->language($path_fix . $category_shortname . '/' . $extension);


                $extra_data[] = array(
                    'name'      => $this->language->get('heading_title'),
                    'shortname' => $extension,
                    'edit'      => $path_fix . $category_shortname . '/' . $extension
                );
            }
        }
        $path_fix = 'extension/';
        if ($category_shortname != 'd_shopunity') {
            $files = glob(DIR_APPLICATION . 'controller/{' . $path_fix . $category_shortname . '}/*.php', GLOB_BRACE);
            if ($files) {
                foreach ($files as $file) {
                    $extension = basename($file, '.php');
                    //if method exist
                    $this->load->language($path_fix . $category_shortname . '/' . $extension);


                    $extra_data[] = array(
                        'name'      => $this->language->get('heading_title'),
                        'shortname' => $extension,
                        'edit'      => $path_fix . $category_shortname . '/' . $extension
                    );
                }
            }
        }

        $sort_order = array();

        foreach ($extra_data as $key => $value) {
            $sort_order[$key] = $value['name'];
        }
        array_multisort($sort_order, SORT_ASC, $extra_data);
        return $extra_data;
    }

    public function get_href_type($link)
    {

        preg_match("/(https?:\/\/).+/", $link, $matches);

        if ($matches) {
            return 'direct_link';
        } else {
            return 'route';
        }
    }

    public function modification_handler($status)
    {
        $this->model_extension_d_opencart_patch_modification->setModification('d_admin_menu.xml', $status);
        $this->model_extension_d_opencart_patch_modification->refreshCache();
    }

}
