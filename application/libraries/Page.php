<?php

/**
 * Webconfig page class.
 *
 * @category   Framework
 * @package    Application
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011-2017 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\framework\Logger as Logger;
use \clearos\framework\Config as Config;
use \clearos\apps\base\Access_Control as Access_Control;
use \clearos\apps\base\Install_Wizard as Install_Wizard;
use \clearos\apps\base\Software as Software;
use \clearos\apps\events\Events as Events;
use \clearos\apps\registration\Registration as Registration;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Webconfig page class.
 *
 * This class provides the mechanism for managing the type of a webconfig
 * page.  The view of a given ClearOS App is managed by the app developer,
 * while the view of the following is managed by this class:
 *
 * - Header 
 * - Footer
 * - Menu
 * - Summary
 * - Status messages
 *
 * This class handles the data management (e.g. parsing menu meta data), 
 * while the theme developer handles the look and feel.  This class also 
 * provides the following variables for use in their overall webconfig page:
 *
 * - login (if logged in)
 * - full name (if logged in)
 * - hostname
 * - registration status
 * - locale
 * - OS name
 * - OS version
 * - theme
 * - theme mode
 * - app 16x16 icon
 * - app 32x32 icon
 * - success message (e.g. firewall rule has been deleted)
 * - warning message (e.g. OpenVPN is running, but the firewall is not allowing connections)
 * - page type (default, splash, wizard(?))
 * - page title
 *
 * This class also handles exceptions.  When an exception occurs in a
 * controller, further processing is halted.  The exception is then shown
 * via the handle_exception() method.
 *
 * @category   Framework
 * @package    Application
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011-2017 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/
 */

class Page
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    // Important Note!  If you add a constant here, please add it to MY_Page.
    // See MY_Page for details.

    const TYPE_CONFIGURATION = 'configuration';
    const TYPE_WIDE_CONFIGURATION = 'wide_configuration';
    const TYPE_LOGIN = 'login';
    const TYPE_2FACTOR_AUTH = '2factor_auth';
    const TYPE_REPORT = 'report'; // TODO: deprecated, remove
    const TYPE_REPORTS = 'reports';
    const TYPE_REPORT_OVERVIEW = 'report_overview';
    const TYPE_SPOTLIGHT = 'spotlight';
    const TYPE_SPLASH = 'splash';
    const TYPE_SPLASH_ORGANIZATION = 'splash_org';
    const TYPE_WIZARD = 'wizard';
    const TYPE_CONSOLE = 'console';
    const TYPE_DASHBOARD = 'dashboard';
    const TYPE_DASHBOARD_WIDGET = 'dashboard_widget';
    const TYPE_EXCEPTION = 'exception';

    const MODE_CONTROL_PANEL = 'control_panel';
    const MODE_NORMAL = 'normal';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $framework = NULL;
    protected $javascript = array();
    protected $report_driver = NULL;
    protected $summary_driver = NULL;
    public $data = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Page constructor.
     */

    public function __construct()
    {
        Logger::profile_framework(__METHOD__, __LINE__, 'Page Class Initialized');

        $this->framework =& get_instance();

        $this->report_driver = 'reports';
    }

    /**
     * Clears the cache directory,
     *
     * @return void
     */

    public function clear_cache()
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        $menu_cache = CLEAROS_TEMP_DIR . '/menu_cache_' . $this->framework->session->userdata('session_id') . 
            $_SERVER['SERVER_PORT'];

        if ($handle = opendir(CLEAROS_TEMP_DIR)) {
            while (FALSE !== ($file = readdir($handle))) {
                if (preg_match('/^menu_cache_/', $file))
                    unlink(CLEAROS_TEMP_DIR . '/' . $file);
            }
        }
    }

    /**
     * Handles status added message.
     *
     * @return void
     */

    public function set_status_added()
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        $message = lang('base_item_was_added');

        $this->framework->session->set_userdata('status_success', $message);
    }

    /**
     * Handles status deleted message.
     *
     * @return void
     */

    public function set_status_deleted()
    { 
        Logger::profile_framework(__METHOD__, __LINE__);

        $message = lang('base_item_was_deleted');

        $this->framework->session->set_userdata('status_success', $message);
    }

    /**
     * Handles status disabled message.
     *
     * @return void
     */

    public function set_status_disabled()
    { 
        Logger::profile_framework(__METHOD__, __LINE__);

        $message = lang('base_item_was_disabled');

        $this->framework->session->set_userdata('status_success', $message);
    }

    /**
     * Handles status enabled message.
     *
     * @return void
     */

    public function set_status_enabled()
    { 
        Logger::profile_framework(__METHOD__, __LINE__);

        $message = lang('base_item_was_enabled');

        $this->framework->session->set_userdata('status_success', $message);
    }

    /**
     * Handles status updated message.
     *
     * @return void
     */

    public function set_status_updated()
    { 
        Logger::profile_framework(__METHOD__, __LINE__);

        $message = lang('base_system_updated');

        $this->framework->session->set_userdata('status_success', $message);
    }

    /**
     * Handles a page message.
     *
     * Though the family of set_status_x methods are optional for the
     * a theme developer, set_message is required. 
     *
     * @param string $message message
     * @param string $code    code
     * @param string $title   $title
     *
     * @return void
     */

    public function set_message($message, $code = 'warning', $title = NULL)
    { 
        Logger::profile_framework(__METHOD__, __LINE__);

        if (empty($title))
            $title = ($code === 'warning') ? lang('base_warning') : lang('base_information');

        $this->framework->session->set_userdata('message_code', $code);
        $this->framework->session->set_userdata('message_text', $message);
        $this->framework->session->set_userdata('message_title', $title);
    }

    /**
     * Displays generic confirmation box.
     *
     * @param string $message message
     * @param string $confirm confirm URI
     * @param string $cancel  cancel URI
     * @param array  $options theme options
     *
     * @return view
     */

    public function view_confirm($message, $confirm, $cancel, $options = array())
    {
        Logger::profile_framework(__METHOD__, __LINE__);
   
        if (empty($this->data))
            $this->_load_meta_data();

        // TODO: wizard mode does not work, use splash for now
        if (!empty($this->framework->session->userdata['wizard']))
            $type = MY_Page::TYPE_SPLASH;
        else
            $type = isset($options['type']) ? $options['type'] : MY_Page::TYPE_CONFIGURATION;

        $app = $this->framework->uri->segment(1);

        $title = lang('base_confirm');

        $this->data['title'] = $title;
        $this->data['type'] = $type;
        $this->data['app_view'] = theme_confirm($title, $confirm, $cancel, $message, $options);
        $this->data['page_help'] = $this->_get_help_view($app);
        $this->data['page_inline_help'] = $this->_get_inline_help_view($app);
        $this->data['page_wizard_intro'] = $this->_get_wizard_intro_view($app);
        $this->data['page_summary'] = $this->_get_summary_view($app);
        $this->data['page_report'] = $this->_get_report_view($app);

        $this->_display_page();
    }

    /**
     * Displays delete confirmation box.
     *
     * @param string $confirm confirm URI
     * @param string $cancel  cancel URI
     * @param array  $items   list of items to delete
     * @param array  $options theme options
     *
     * @return view
     */

    public function view_confirm_delete($confirm, $cancel, $items, $options = array())
    {
        Logger::profile_framework(__METHOD__, __LINE__);
   
        if (empty($this->data))
            $this->_load_meta_data();

        // TODO: wizard mode does not work, use splash for now
        if (isset($this->framework->session->userdata['wizard']) && $this->framework->session->userdata['wizard'])
            $type = MY_Page::TYPE_SPLASH;
        else
            $type = isset($options['type']) ? $options['type'] : MY_Page::TYPE_CONFIGURATION;

        $app = $this->framework->uri->segment(1);

        $message = isset($options['message']) ? $options['message'] : lang('base_are_you_sure_delete');

        $title = lang('base_confirm_delete');

        $this->data['title'] = $title;
        $this->data['type'] = $type;
        $this->data['app_view'] = theme_confirm_delete($title, $confirm, $cancel, $items, $message, $options);
        $this->data['page_help'] = $this->_get_help_view($app);
        $this->data['page_inline_help'] = $this->_get_inline_help_view($app);
        $this->data['page_wizard_intro'] = $this->_get_wizard_intro_view($app);
        $this->data['page_summary'] = $this->_get_summary_view($app);
        $this->data['page_report'] = $this->_get_report_view($app);

        $this->_display_page();
    }

    // TODO TODO //
    public function get_dashboard_widgets($app, $data, $options = array())
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        $widgets = array(
            'html' => array($this->framework->load->view($app . '/dashboard', $data, TRUE)),
            'css' => NULL,
            'javascript' => NULL
        );

        $css =  $app . '.css';
        $js =  $app . '.js.php';

        $doc_base = clearos_app_base($app) . '/htdocs/';

        if (file_exists($doc_base . '/' . $css))
            $widgets['css'] = Config::get_app_url($app) . '/' . $css;
        if (file_exists($doc_base . '/' . $js))
            $widgets['javascript'] = Config::get_app_url($app) . '/' . $js;
        return $widgets;
    }

    /**
     * Displays a page with a single form.
     *
     * Available options:
     * - $options['type']       - page type (e.g. TYPE_SPLASH)
     * - $options['javascript'] - URL for additional javascript include
     *
     * @param string $form    CodeIgniter form path
     * @param array  $data    CodeIgniter data array
     * @param string $title   page title
     * @param array  $options options array
     *
     * @return view
     */

    public function view_form($form, $data, $title, $options = array())
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        if (empty($this->data))
            $this->_load_meta_data();

        if (isset($options['javascript']))
            $this->javascript = array_merge($options['javascript'], $this->javascript);

        if (isset($options['breadcrumb_links']))
            $this->data['breadcrumb_links'] = $options['breadcrumb_links'];

        // Add Tip button
        if (!empty($this->data['tooltips'])) {
            $this->data['breadcrumb_links']['app-tip'] = array(
                'url' => '#',
                'tag' => lang('base_tips_and_hints'),
                'id' => 'app-tips-action',
                'display_tag' => FALSE,
                'button' => FALSE,
                'tag_position' => 'right'
            );
        }
        // Add Info button
        $this->data['breadcrumb_links']['app-info'] = array(
            'url' => '#',
            'tag' => lang('base_information'),
            'id' => 'app-info-action',
            'display_tag' => FALSE,
            'button' => FALSE,
            'tag_position' => 'right'
        );

        // Add Documentation button
        $app_data = $this->_load_app_data($app);
        if (empty($app_data['documentation_url']))
            $url = 'http://www.clearos.com/redirect/ClearOS/7/userguide/' . $this->data['current_basename'];
        else
            $url = $app_data['documentation_url'];
        $this->data['breadcrumb_links']['app-documentation'] = array(
            'url' => $url,
            'tag' => lang('base_documentation'),
            'id' => 'app-documentation',
            'display_tag' => FALSE,
            'button' => FALSE,
            'target' => '_blank',
            'tag_position' => 'right'
        );

        // Set default page type, but not if we are just handling a "form only" request
        if (empty($this->framework->form_only) || !$this->framework->form_only)
            $this->data['type'] = (isset($options['type'])) ? $options['type'] : MY_Page::TYPE_CONFIGURATION;

        // Load wizard view if enabled
        //----------------------------

        // TODO: this is weak logic.

        if ((($this->data['type'] === MY_Page::TYPE_CONFIGURATION) 
            || ($this->data['type'] === MY_Page::TYPE_REPORT)
            || ($this->data['type'] === MY_Page::TYPE_SPOTLIGHT)) 
            && !clearos_console()
            && isset($this->framework->session->userdata['wizard'])) {

            if ($this->_load_wizard_data())
                $this->data['type'] = MY_Page::TYPE_WIZARD;
        }

        // Non-intuitive: see view_forms for form_only explanation
        //--------------------------------------------------------

        if (!empty($this->framework->form_only) && $this->framework->form_only) {
            $this->framework->load->view($form, $data);
        } else {
            $this->data['title'] = $title;
            $segments = preg_split('/\//', uri_string());
            $app = $segments[0];
            $controller = isset($segments[1]) ? $segments[1] : 'index';
            $action = isset($segments[2]) ? $segments[2] : '';

            // More non-intuitive stuff.  When we are *not* running in "control panel" mode,
            // the user should see a full page summary once an action (e.g. adding a port
            // forward firewall) takes place.
            //
            // Also. disable this behavior in wizard mode.
            if (($this->framework->session->userdata['theme_mode'] !== self::MODE_CONTROL_PANEL) 
                && (!isset($this->framework->session->userdata['wizard'])))
            {
                $app_data = $this->_load_app_data();

                if (!$action && isset($app_data['controllers'][$controller]['title']))
                    redirect('/' . $app);
            }

            $this->data['app_view'] = $this->framework->load->view($form, $data, TRUE);
            $this->data['page_help'] = $this->_get_help_view($app);
            $this->data['page_inline_help'] = $this->_get_inline_help_view($app);
            $this->data['page_wizard_intro'] = $this->_get_wizard_intro_view($app);
            $this->data['page_app_helper'] = $this->_get_app_helper_view($app);
            $this->data['page_summary'] = $this->_get_summary_view($app);
            $this->data['page_report'] = $this->_get_report_view($app);

            $this->_display_page();
        }
    }

    /**
     * TODO: deprecate this
     */

    public function view_forms($forms, $title)
    {
        $this->view_controllers($forms, $title);
    }

    /**
     * Displays a report.
     *
     * Available options are identical to view_form
     *
     * @param string $type        dashboard or full
     * @param array  $data        CodeIgniter data array
     * @param string $title       page title
     * @param array  $options     options array
     *
     * @return view
     */

    public function view_report($type, $data, $title, $options = array())
    {
        // Common
        //-------

        $options['javascript'] = array(clearos_app_htdocs($this->report_driver) . '/' . $this->report_driver . '.js.php');
        if (isset($options['javascript']))
            $this->javascript = array_merge($options['javascript'], $this->javascript);

        $data['type'] = MY_Page::TYPE_REPORTS;

        // Dashboard
        //----------

        if ($type === 'dashboard')
            return $this->view_form($this->report_driver . '/dashboard', $data, $title, $options);

        // Full Report
        //------------

        if (empty($this->data))
            $this->_load_meta_data();

        $this->data['title'] = $title;
        $this->data['type'] = MY_Page::TYPE_REPORTS;

        $this->data['page_help'] = $this->_get_help_view($app);
        $this->data['page_report_helper'] = $this->_get_report_helper_view($data);
        $this->data['page_report_chart'] = $this->_get_report_chart_view($data, $title, $options);
        $this->data['page_report_table'] = $this->_get_report_table_view($data, $title, $options);

        $this->_display_page();
    }

    /**
     * Display a report page using multiple controllers.
     *
     * See view_controllers.
     *
     * @param array  $controllers list of controllers
     * @param string $title       page title
     * @param array  $options     options array
     *
     * @return view
     */

    public function view_reports($controllers, $data, $title, $options = array())
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        $options['type'] = MY_Page::TYPE_REPORT_OVERVIEW;

        // Woah... this is non-intutive.  Must document.
        $this->data['page_report_helper'] = $this->_get_report_helper_view($data);

        return $this->view_controllers($controllers, $title, $options);
    }

    /**
     * Display a page using multiple controllers.
     *
     * Available options:
     * - $options['type']       - page type (e.g. TYPE_SPLASH)
     *
     * @param array  $controllers list of controllers
     * @param string $title       page title
     * @param array  $options     options array
     *
     * @return view
     */

    public function view_controllers($controllers, $title, $options = array())
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        // If in wizard mode and this function is called, send back to beginning
        // Prevents users from messing around with URL's.
        if (isset($this->framework->session->userdata['wizard'])) {
            $this->set_message("Please complete the post-install wizard.");
            redirect('/base/wizard');
        }
 
        $this->_load_meta_data();

        $this->data['title'] = $title;
        $this->data['type'] = (isset($options['type'])) ? $options['type'] : MY_Page::TYPE_CONFIGURATION;

        // Non-intuitive, but this saves app developers from handling a 
        // useless variable in their controllers.  The form_only variable
        // is set to TRUE to indicate that only the raw form should be 
        // loaded (no headers, no footers, etc.).

        $this->framework->form_only = TRUE;

        // The controllers parameter can contain a simple list:
        // dhcp/settings, dhcp/subnets, dhcp/leases
        //
        // Or it can be a more complex hash array with detailed
        // information on the controller:
        //   [controller] => network_report/iface
        //   [method] => dashboard
        //   [params] => eth0

        $this->data['widget_views'] = array();

        foreach ($controllers as $controller) {
            ob_start();
            if (is_array($controller)) {
                $basename = preg_replace('/.*\//', '', $controller['controller']);
                $method = $controller['method'];

                $this->framework->load->module($controller['controller']);

                if (empty($controller['params']))
                    $this->framework->$basename->$method();
                else
                    $this->framework->$basename->$method($controller['params']);
            } else {
                $basename = preg_replace('/.*\//', '', $controller);

                $this->framework->load->module($controller);
                $this->framework->$basename->index();
            }

            $this->data['widget_views'][] = ob_get_clean();
        }

        if (!empty($options['type']) && ($options['type'] == MY_Page::TYPE_DASHBOARD_WIDGET)) {
            $this->framework->form_only = FALSE;
            return $this->data['widget_views'];
        }

        $this->data['app_view'] = implode(' ', $this->data['widget_views']);

        // Now we set form_only back to the default
        $this->framework->form_only = FALSE;

        $app = $this->framework->uri->segment(1);

        $this->data['page_help'] = $this->_get_help_view($app);
        $this->data['page_inline_help'] = $this->_get_inline_help_view($app);
        $this->data['page_wizard_intro'] = $this->_get_wizard_intro_view($app);
        $this->data['page_summary'] = $this->_get_summary_view($app);
        $this->data['page_report'] = $this->_get_report_view($app);

        if (isset($options['breadcrumb_links']))
            $this->data['breadcrumb_links'] = $options['breadcrumb_links'];

        // Add Tip button
        if (!empty($this->data['tooltips'])) {
            $this->data['breadcrumb_links']['app-tip'] = array(
                'url' => '#',
                'tag' => lang('base_tips_and_hints'),
                'id' => 'app-tips-action',
                'display_tag' => FALSE,
                'button' => FALSE,
                'tag_position' => 'right'
            );
        }
        // Add Info button
        $this->data['breadcrumb_links']['app-info'] = array(
            'url' => '#',
            'tag' => lang('base_information'),
            'id' => 'app-info-action',
            'display_tag' => FALSE,
            'button' => FALSE,
            'tag_position' => 'right'
        );

        $data = $this->_load_app_data($app);

        if (empty($data['documentation_url']))
            $url = 'http://www.clearos.com/redirect/ClearOS/7/userguide/' . $this->data['current_basename'];
        else
            $url = $data['documentation_url'];

        // Add Documentation button
        $this->data['breadcrumb_links']['app-documentation'] = array(
            'url' => $url,
            'tag' => lang('base_documentation'),
            'id' => 'app-documentation',
            'display_tag' => FALSE,
            'button' => FALSE,
            'target' => '_blank',
            'tag_position' => 'right'
        );

        $this->_display_page();
    }

    /**
     * Displays the exception view.
     *
     * @param Exception $exception exception
     *
     * @return void
     */

    public function view_exception($exception)
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        if (empty($this->data))
            $this->_load_meta_data();

        $segments = explode('/', $_SERVER['PHP_SELF']);

        if ($this->framework->form_only) {
            $message = "<p>" . clearos_exception_message($exception) . "</p>";
            echo infobox_warning(lang('base_error'), $message);
        } else {
            $link = "<p align='center'>" . anchor_custom('/app/' . $segments[2], lang('base_back')) . "</p>";

            $this->data['type'] = MY_Page::TYPE_EXCEPTION;
            $this->data['title'] = lang('base_ooops');
            // FIXME: add alert?  backtrace?
            $this->data['app_view'] = infobox_warning(lang('base_ooops'), clearos_exception_message($exception));
            $this->data['exception'] = clearos_exception_message($exception);
            $this->_display_page();
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // F R I E N D  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////
    //
    // These are for internal framework use and not intended for app developers.
    // These methods are used in MX/Controller.php and primarily intended for
    // mobile themes.
    //
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Loads the required theme files. 
     *
     * The theme hooks are loaded after the controller has been initialized.
     * - doctype.php
     * - meta.php
     * - head.php
     * - page.php
     * - widgets.php
     *
     * This is called by a CodeIgniter hook instead of the constructor since
     * the user session has not been initialized in the constructor.
     *
     * @access private
     * @return void
     */

    public function load_theme()
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        $theme_files = array('doctype.php', 'meta.php', 'head.php', 'page.php', 'widgets.php', 'javascript.php');

        $path = Config::get_theme_path($this->framework->session->userdata('theme'));

        // If a theme is deleted but still in a session, we need to fall back to the default
        if (!file_exists($path)) {
           $this->framework->session->set_userdata('theme', 'default');
           $path = Config::get_theme_path('default');
        }

        foreach ($theme_files as $file) {
            Logger::profile_framework(__METHOD__, __LINE__, "Loading theme file $file");
            $full_path = $path . '/core/' . $file;

            if (file_exists($full_path))
                include $full_path;
        }
    }

    /**
     * Display help box.
     *
     * @param string $uri URI
     *
     * @access private
     * @return HTML
     */

    public function view_help_widget($uri)
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        $app = preg_replace('/^\//', '', $uri);
        $app = preg_replace('/\/.*/', '', $app);

        $this->data = array();
        $this->_load_meta_data();

        $this->data['title'] = lang('base_help');
        $this->data['app_view'] = $this->_get_help_view($app);

        $this->_display_page();
    }

    /**
     * Display report box.
     *
     * @param string $uri URI
     *
     * @access private
     * @return HTML
     */

    public function view_dashboard_widget($uri)
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        $app = preg_replace('/^\//', '', $uri);
        $app = preg_replace('/\/.*/', '', $app);

        $this->data = array();
        $this->_load_meta_data();

        $this->data['title'] = lang('base_dashboard_report');
        $this->data['app_view'] = $this->_get_report_view($app);

        $this->_display_page();
    }

    /**
     * Display summary box.
     *
     * @param string $uri URI
     *
     * @access private
     * @return HTML
     */

    public function view_summary_widget($uri)
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        $app = preg_replace('/^\//', '', $uri);
        $app = preg_replace('/\/.*/', '', $app);

        $this->data = array();
        $this->_load_meta_data();

        $this->data['title'] = lang('base_summary');
        $this->data['app_view'] = $this->_get_summary_view($app);

        $this->_display_page();
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the HTML head section.
     *
     * @return string HTML head section
     */

    protected function _build_page_head()
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        // Adding hostname to the title is handy when managing multiple systems
        //---------------------------------------------------------------------

        $title = $this->data['title'];

        if ($this->framework->session->userdata('hostname'))
            $title = $this->framework->session->userdata('hostname') . " - " . $title;

        // Determine the version to use (trunk, 6.0, etc)
        //-----------------------------------------------

        $segments = explode('/', $_SERVER['PHP_SELF']);
        $app = $segments[2];

        $doc_base = clearos_app_base($app) . '/htdocs/';
        $app_url = Config::get_app_url($app);
        $theme_path = Config::get_theme_url($this->framework->session->userdata('theme'));

        // Add page-specific CSS
        //----------------------

        $css_head = '';
        $css =  $app . '.css';

        if (file_exists($doc_base . '/' . $css))
            $css_head .= "<link type='text/css' media='screen' href='" . $app_url . '/' . $css ."' rel='stylesheet'>\n";

        // Add Javascript hooks
        //---------------------

        $javascript_list = array();

        // Automatically pull in app basename Javascript
        $app = preg_replace('/^\//', '', uri_string());
        $app = preg_replace('/\/.*/', '', $app);
        
        $javascript_basename = $app . '.js.php';
        $javascript = clearos_app_base($app) . '/htdocs/' . $javascript_basename;

        if (file_exists($javascript)) {
            $app_url = Config::get_app_url($app);
            $javascript_list[] = $app_url . '/' . $javascript_basename;
        }

        $javascript_head = array();
        $javascript_head_list = array();

        foreach ($javascript_list as $javascript)
            $javascript_head_list[] = $javascript;

        // Automatically pull in explicit javascript requests
        if (! empty($this->javascript)) {
            foreach ($this->javascript as $javascript)
                $javascript_head_list[] = $javascript;
        }

        $javascript_head = '';

        foreach (array_unique($javascript_head_list) as $javascript)
            $javascript_head .= "<script type='text/javascript' src='" . $javascript . "'></script>\n";

        // <meta>
        //-------------------

        $meta = theme_page_meta_tags();
       
        // <html>
        //-------------------
        
        $head = "<html dir='" . $this->framework->session->userdata('text_direction') . "'>\n\n";

        // <head> commom
        //-------------------

        $head .= "<!-- Head -->
<head>

<!-- Basic Head Information -->
<title>$title</title>
<meta http-equiv='Content-Type' content='text/html; charset=" . $this->framework->session->userdata('encoding') . "'>
$meta

<!-- Jquery -->
<script type='text/javascript' src='/js/jquery.min.js'></script>

<!-- Global and theme functions -->
<script type='text/javascript' src='/js/globals.js.php'></script>
<script type='text/javascript' src='/js/widgets.js.php'></script>
";
        // <head> extras defined in theme (head.php)
        //------------------------------------------

        $head .= theme_page_head($this->framework->session->userdata('theme_' . $this->framework->session->userdata('theme')));

        // <head> extras defined in app
        //------------------------------------------

        if ($css_head)
            $head .= "<!-- Page-specific CSS -->\n$css_head\n";

        if ($javascript_head)
            $head .= "\n<!-- Page-specific Javascript -->\n$javascript_head\n";

        // </head> all done
        //------------------------------------------

        $head .= "</head>\n\n";

        return $head;
    }

    /**
     * Displays the webconfig page.
     *
     * @return string HTML of webconfig page
     */

    protected function _display_page()
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        echo theme_page_doctype() . "\n";
        echo $this->_build_page_head();

        // Grab alerts from Events library
        if (clearos_load_library('events/Events')) {
            $events = new Events();

            try {
                if ($this->framework->session->userdata('username') == 'root' || $this->framework->session->userdata['nav_acl']['events'])
                    $this->data['alerts'] = $events->get_events(Events::FLAG_WARN | Events::FLAG_CRIT, Events::FLAG_RESOLVED, 5);
            } catch (\Exception $e) {
                // Not fatal
            }
        }

        if (clearos_load_library('registration/Registration') && $this->framework->session->userdata('username')) {
            $registration = new Registration();
            $notice = $registration->get_sdn_notice();
            if (!empty($notice['root_only']) && $notice['root_only'] && $this->framework->session->userdata('username') != 'root') {
                // Show nothing
            } else {
                if (empty($notice['persistence'])) {
                    // n/a
                } else if ($notice['persistence'] == 'page') {
                    $this->data['sdn_notice'] = $notice;
                } else if ($notice['persistence'] == 'session' && $notice['id'] != $this->framework->session->userdata('sdn_notice')) {
                    $this->data['sdn_notice'] = $notice;
                    $this->framework->session->set_userdata('sdn_notice', $this->data['sdn_notice']['id']);
                } else if ($notice['persistence'] == 'one_time') {
                    $this->data['sdn_notice'] = $notice;
                }
            }
        }

        if (function_exists('theme_page_javascript')) {
            echo theme_page_open($this->framework->session->userdata['theme_' . $this->framework->session->userdata['theme']]);
            echo theme_page($this->data);
            echo theme_page_javascript($this->framework->session->userdata['theme_' . $this->framework->session->userdata['theme']]);
            echo theme_page_close($this->data);
        } else {
            echo theme_page($this->data);
        }
    }

    /**
     * Returns the help view.
     *
     * @param string $app app name
     *
     * @return string HTML for help view
     */

    protected function _get_app_helper_view($app)
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        return '';
    }

    /**
     * Returns the help view.
     *
     * @param string $app app name
     *
     * @return string HTML for help view
     */

    protected function _get_help_view($app)
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        // The 'hide_app_description' is a standard (but optional) setting in the theme
        // that allows the themer to expose in a setting whether to hide the information bar on an app
        $theme = $this->framework->session->userdata('theme');
        if (isset($this->framework->session->userdata['theme_' . $theme]['hide_app_description'])) {
             if ($this->framework->session->userdata['theme_' . $theme]['hide_app_description'])
                 return "";
        }

        $data = $this->_load_app_data($app);

        // TODO: Move these to a driver package
        // TODO: translate

        if (preg_match('/Business/', $this->framework->session->userdata('os_name'))) {
            $data['support_url'] = 'http://www.clearcenter.com/getsupport';
            $data['support_url_text'] = 'ClearCARE Support';
        }

        $segments = explode('/', $_SERVER['PHP_SELF']);

        // TODO: this segment mapping is not going to work.  Need a better way.
        // Below places an action item in the help dialog
        if (isset($segments[3]) && isset($data['controllers'][$segments[3]]['help_action'])) {
            $data['action'] = array(
                'url' => '/app/marketplace/all',
                'text' => lang('marketplace_select_all'),
                'priority' => 'high',
                'js' => array('id' => 'toggle_select')
            );
        }
        return theme_help_box($data);
    }

    /**
     * Returns the inline help view.
     *
     * @param string $app app name
     *
     * @return string HTML for inline help view
     */

    protected function _get_inline_help_view($app)
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        // This was a bit of an afterthought.  The "Network App"
        // has been broken up into smaller chunks.  This means
        // that inline help can be provided per controller (segment).

        $data = $this->_load_app_data($app);

        $segments = explode('/', $_SERVER['PHP_SELF']);

        if (isset($segments[4]) && isset($data['controllers'][$segments[4]]['inline_help']))
            $help['inline_help'] = $data['controllers'][$segments[4]]['inline_help'];
        else if (isset($segments[3]) && isset($data['controllers'][$segments[3]]['inline_help']))
            $help['inline_help'] = $data['controllers'][$segments[3]]['inline_help'];
        else if (isset($segments[2]) && isset($data['controllers'][$segments[2]]['inline_help']))
            $help['inline_help'] = $data['controllers'][$segments[2]]['inline_help'];
        else if (!empty($data['inline_help']))
            $help['inline_help'] = $data['inline_help'];
        else
            return;

        return theme_inline_help_box($help);
    }

    /**
     * Returns the report view.
     *
     * Returns NULL if no report exists for the given form.
     *
     * @param string $app app name
     *
     * @return string HTML for report box
     */

    protected function _get_report_view($app)
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        // See if an app has a "report" controller
        //----------------------------------------

        $report = $app . '/report';
        $module = 'report';

        if (isset($this->framework->$module))
            return;

        $this->framework->load->module($report);

        // Load sidebar report
        //--------------------

        if (! method_exists($this->framework->$module, 'sidebar'))
            return;

        ob_start();
        $data = $this->data;
        $this->framework->form_only = TRUE;
        $this->framework->$module->sidebar();
        $this->framework->form_only = FALSE;
        $this->data = $data;
        $report = ob_get_clean();

        // Return report widget
        //---------------------

        return $report;
    }

    /**
     * Returns the report chart view.
     *
     * @param string $app app name
     *
     * @return string HTML for report helper view
     */

    protected function _get_report_chart_view($data, $title, $options)
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        ob_start();
        $this->framework->load->view($this->report_driver . '/chart', $data);
        $view = ob_get_clean();

        return $view;
    }

    /**
     * Returns the report helper view.
     *
     * @param string $app app name
     *
     * @return string HTML for report helper view
     */

    protected function _get_report_helper_view($data)
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        ob_start();
        $this->framework->load->view($this->report_driver . '/helper', $data);
        $view = ob_get_clean();

        return $view;
    }

    /**
     * Returns the report table view.
     *
     * @param string $app app name
     *
     * @return string HTML for report table view
     */

    protected function _get_report_table_view($data, $title, $options)
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        ob_start();
        $this->framework->load->view($this->report_driver . '/data_table', $data);
        $view = ob_get_clean();

        return $view;
    }

    /**
     * Returns the summary view.
     *
     * @param string $app app name
     *
     * @return string HTML for summary view
     */

    protected function _get_summary_view($app)
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        $data = $this->_load_app_data();

        // TODO: see TODO in _load_menu_data
        $data['show_marketplace'] = $this->framework->session->userdata('show_marketplace');

        // Show app recommendations
        $theme = $this->framework->session->userdata('theme');
        if (isset($this->framework->session->userdata['theme_' . $theme]['show_recommended_apps']))
             $data['show_recommended_apps'] = $this->framework->session->userdata['theme_' . $theme]['show_recommended_apps'];

        return theme_summary_box($data);
    }

    /**
     * Returns the wizard intro view.
     *
     * @param string $app app name
     *
     * @return string HTML for wizard intro view
     */

    protected function _get_wizard_intro_view($app)
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        if (empty($this->framework->session->userdata['wizard']))
            return;

        $options = array();

        $data = $this->_load_app_data($app);

        $segments = explode('/', $_SERVER['PHP_SELF']);

        // TODO: this segment mapping is not going to work.  Need a better way.
        if (isset($segments[4]) && isset($data['controllers'][$segments[4]]['wizard_description'])) {
            $data['wizard_description'] = $data['controllers'][$segments[4]]['wizard_description'];
            // If Marketplace app selector, add action as req'd
            //if (preg_match('/mode1|mode2/' , $segments[5]))
            // TODO  Code review
            if (preg_match('/selection/' , $segments[4]))
                $options['action'] = array(
                    'url' => '/app/marketplace/all',
                    'text' => lang('marketplace_select_all'),
                    'priority' => 'high',
                    'js' => array('id' => 'toggle_select')
                );
        } else if (isset($segments[3]) && isset($data['controllers'][$segments[3]]['wizard_description'])) {
            $data['wizard_description'] = $data['controllers'][$segments[3]]['wizard_description'];
        } else if (isset($segments[2]) && isset($data['controllers'][$segments[2]]['wizard_description'])) {
            $data['wizard_description'] = $data['controllers'][$segments[2]]['wizard_description'];
        }

        if (isset($segments[4])) {
            // hack for Marketplace /wizard/selection controller...it's really dirty in here.
            if (isset($data['controllers'][$segments[4]]['wizard_name']))
                $data['wizard_name'] = $data['controllers'][$segments[4]]['wizard_name'];
            else
                unset($data['wizard_name']);
        } else if (isset($segments[3]) && isset($data['controllers'][$segments[3]]['wizard_name'])) {
            $data['wizard_name'] = $data['controllers'][$segments[3]]['wizard_name'];
        } else if (isset($segments[2]) && isset($data['controllers'][$segments[2]]['wizard_name'])) {
            $data['wizard_name'] = $data['controllers'][$segments[2]]['wizard_name'];
        }

        if (empty($data['wizard_name']))
            return;

        return theme_wizard_intro_box($data, $options);
    }

    /**
     * Returns app data in an array.
     *
     * @param string $app_name app name
     *
     * @return array app meta data
     */

    protected function _load_app_data($app_name = NULL)
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        if (is_null($app_name)) {
            $segments = explode('/', $_SERVER['PHP_SELF']);
            $app_name = $segments[2];
        }

        $app = array();
        $app_base = clearos_app_base($app_name);
        $app['basename'] = $app_name;

        $info_file = $app_base . '/deploy/info.php';

        // Install/upgrade time indicator
        //-------------------------------

        if (file_exists($info_file)) {

            // Load metadata file
            clearos_load_language($app_name);
            include $info_file;

            // Add timestamp
            $stat = stat($info_file);
            $app['modified'] = $stat['ctime'];
        }

        // Icons
        //------

        // PNG
        $icon_basename = $app_name . '_50x50.png';
        $icon_path = $app_base . '/htdocs/' . $icon_basename;

        if (file_exists($icon_path))
            $app['icon_png'] = clearos_app_htdocs($app_name) . '/' . $icon_basename;
        else
            $app['icon_png'] = '/assets/app_default_50x50.png';

        // SVG
        $icon_basename = $app_name . '.svg';
        $icon_path = $app_base . '/htdocs/' . $icon_basename;

        if (file_exists($icon_path))
            $app['icon_svg'] = clearos_app_htdocs($app_name) . '/' . $icon_basename;
        else
            $app['icon_svg'] = '/assets/app_default.svg';


        if (isset($app['powered_by'])) {
            foreach ($app['powered_by']['packages'] as $engine => $engine_data) {

                if (clearos_load_library('base/Software')) {
                    $software = new Software($engine);
                    if ($software->is_installed())
                        $app['powered_by']['packages'][$engine]['version'] = $software->get_version();
                }
            }
        }

        // Done
        //-----

        return $app;
    }

    /**
     * Returns menu data in an array.
     *
     * @return array menu meta data
     */

    protected function _load_menu_data()
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        // If timestamps are okay, use the cache file
        //-------------------------------------------

        $menu_cache = CLEAROS_TEMP_DIR . '/menu_cache_' . $this->framework->session->userdata('session_id') . 
            $_SERVER['SERVER_PORT'];

        // Find most recently installed
        //-----------------------------

        $last_change = CLEAROS_TEMP_DIR . '/app_last_change';


        // TODO - re-enable cache
        if (file_exists($menu_cache) && file_exists($last_change)) {
            $stat = stat($menu_cache);
            $cache_time = $stat['ctime'];
            $stat = stat($last_change);
            $most_recent = $stat['ctime'];

            if ($cache_time > $most_recent)
                return unserialize( file_get_contents($menu_cache) );
        }

        $apps_list = clearos_get_apps();

        // Load valid pages for given users
        //---------------------------------

        $username = ($this->framework->session->userdata('username')) ? $this->framework->session->userdata('username') : '';
        $valid_pages = array();

        if (clearos_load_library('base/Access_Control')) {
            $access = new Access_Control();
            $valid_pages = $access->get_valid_pages($username);
        }

        // Create an array with the sort key
        //----------------------------------

        // Pull from Registration library?
        $registered = '/var/clearos/registration/registered';

        if (file_exists($registered))
            $register_timestamp = filemtime($registered);
        else
            $register_timestamp = NULL;

        // Used to display newly installed/upgraded apps for 1 day
        $one_day_ago = strtotime('-1 day');
        $sorted = array();

        foreach ($apps_list as $app_name => $app) {
            // If this page is not allowed, skip it
            // Note: the demo flag is for the demo servers.  It provides a simple way to allow all pages.
            // instead of having to enumerate a full list of apps in the ACL file.
            if (! (($username === 'root') || (($username === 'get') && file_exists('/var/clearos/base/access_control/custom/cleardemo')))) {
                $full_name = '/app/' . $app_name;
                if (! in_array($full_name, $valid_pages))
                    continue;
            }

            // Determine 'new/updated' icon status
            $new_status = FALSE;

            // Don't display any icon if we are not registered
            if ($register_timestamp != NULL) {
                if ($one_day_ago < $app['modified']) {
                    // Newly installed app...better check core apps and install time
                    if ($app['is_core']) {
                        // Don't display TRUE status for any core apps if system was just (1 day) registered
                        if ($register_timestamp < $one_day_ago)
                            $new_status = TRUE;
                    } else {
                        $new_status = TRUE;
                    }
                }
            }

            $menu_info = array();
            $menu_info['/app/' . $app['basename']] = array(
                'title' => $app['name'],
                'category' => $app['category'],
                'subcategory' => $app['subcategory'],
                'new' => $new_status
            );

            $sorted[$app['priority']] = $menu_info;
        }

        // Use the sorted array to generate the menu array
        //------------------------------------------------

        ksort($sorted);

        $menu_data = array();

        foreach ($sorted as $sort_key => $sort_details) {
            foreach ($sort_details as $url => $details)
                $menu_data[$url] = $details;
        }

        // KLUDGE: Access control needs to go to a useful page when someone 
        // attempts to unauthorized page (e.g. clicking on a link to
        // https;//w.x.y.x:81/app/disk_usage without having access to it).
        //-----------------------------------------------------------------

        if (array_key_exists('/app/dashboard', $menu_data)) {
            $default_app = 'dashboard';
        } else if (array_key_exists('/app/user_profile', $menu_data)) {
            $default_app = 'user_profile';
        } else if (! empty($menu_data)) {
            $app_list = array_keys($menu_data);
            $default_app = preg_replace('/^\/app\//', '', $app_list[0]);
        } else {
            $default_app = '';
        }

        $this->framework->session->set_userdata('default_app', $default_app);

        // The sidebar needs to know if the user is allowed to view
        // Marketplace information.  
        // TODO: implement this in a better way

        $show_marketplace = (array_key_exists('/app/marketplace', $menu_data)) ? TRUE : FALSE;

        $this->framework->session->set_userdata('show_marketplace', $show_marketplace);

        // Cache the data and return it
        //-----------------------------

        file_put_contents($menu_cache, serialize($menu_data));

        return $menu_data;
    }

    /**
     * Loads the page meta data into the data class variable.
     *
     * @return void
     */

    protected function _load_meta_data()
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        $menu_data['menus'] = $this->_load_menu_data();
        $session_data = $this->_load_session_data();

        $app_data = $this->_load_app_data();
        $this->data['current_basename'] = $app_data['basename'];
        $this->data['current_name'] = $app_data['name'];
        $this->data['current_category'] = $app_data['category'];
        $this->data['current_subcategory'] = $app_data['subcategory'];
        $this->data['tooltips'] = empty($app_data['tooltip']) ? '' : $app_data['tooltip'];
        
        $this->data = array_merge($this->data, $session_data, $menu_data);
    }

    /**
     * Returns page session data in an array.
     *
     * @return array session meta data
     */

    protected function _load_session_data()
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        $session_data = array();

        // Grab all the session values
        //----------------------------

        foreach ($this->framework->session->userdata as $key => $value)
            $session_data[$key] = $value;

        // The "status_success" message is a flash value... delete it
        //-----------------------------------------------------------

        if ($this->framework->session->userdata('status_success'))
            $this->framework->session->unset_userdata('status_success');

        return $session_data;
    }

    /**
     * Displays a controller in wizard mode.
     *
     * @return view
     */

    public function _load_wizard_data()
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        if (! clearos_load_library('base/Install_Wizard'))
            return FALSE;

        $install_wizard = new Install_Wizard();
        $steps = $install_wizard->get_steps();
        $state = $install_wizard->get_state();

        // Generate previous/next links
        //-----------------------------

        $segments = explode('/', $_SERVER['PHP_SELF']);
        $app = $segments[2];
        $controller = isset($segments[3]) ? $segments[3] : '';

        $exact_count = 0;
        $exact_match = FALSE;
        $fuzzy_match = FALSE;

        foreach ($steps as $step) {
            if ($_SERVER['PHP_SELF'] === $step['nav']) {
                $exact_match = TRUE;
                break;
            } else if (preg_match("/\/app\/$app\/$controller/", $step['nav'])) {
                $fuzzy_match = TRUE;
                $fuzzy_count = $exact_count;
            } else if (!$fuzzy_match && (preg_match("/\/app\/$app/", $step['nav']))) {
                $fuzzy_match = TRUE;
                $fuzzy_count = $exact_count;
            }

            $exact_count++;
        }

        if ($exact_match) {
            $current = $exact_count;
        } else if ($fuzzy_match) {
            $current = $fuzzy_count;
        } else if ($state) {
            redirect(preg_replace('/\/app/', '', $steps[$state]['nav']));
        } else {
            throw new \Exception("Please finish the wizard.");
        }

        if (isset($steps[$current - 1])) {
            $wizard_nav['previous'] = $steps[$current - 1]['nav'];
            $wizard_nav['previous_title'] = $steps[$current - 1]['title'];
        } else {
            $wizard_nav['previous'] = '';
            $wizard_nav['previous_title'] = '';
        }

        if (isset($steps[$current + 1])) {
            $wizard_nav['next'] = $steps[$current + 1]['nav'];
            $wizard_nav['next_title'] = $steps[$current + 1]['title'];
        } else {
            $wizard_nav['next'] = '';
            $wizard_nav['next_title'] = '';
        }

        $this->data['wizard_navigation'] = $wizard_nav;
        $this->data['wizard_menu'] = $steps;
        $this->data['wizard_current'] = $current;
        $this->data['wizard_type'] = $steps[$current]['type'];

        $install_wizard->set_state($current);

        // Unset any existing breadcrumb links
        unset($this->data['breadcrumb_links']);
        $this->data['breadcrumb_links'] = array();

        if ($current > 0) {
            $this->data['breadcrumb_links']['wizard_previous'] = array(
                'url' => $steps[$current - 1]['nav'],
                'tag' => lang('base_previous'),
                'id' => 'wizard_nav_previous',
                'display_tag' => TRUE,
                'button' => 'low',
                'tag_position' => 'right'
            );
        }

        if ($current < count($steps)) {
            // See tracker #2197.
            if (isset($steps[$current]['inline_form']) && $steps[$current]['inline_form'])
                $url = '#';
            else
                $url = $steps[$current + 1]['nav'];

            // To handle inline forms, we keep the "next" click in our session
            $basename = preg_replace('/^\/app\//', '', $steps[$current + 1]['nav']);
            $this->framework->session->set_userdata('wizard_redirect', $basename);

            $this->data['breadcrumb_links']['wizard_next'] = array(
                'url' => $url,
                'tag' => lang('base_next'),
                'id' => 'wizard_nav_next',
                'display_tag' => TRUE,
                'button' => 'high',
                'tag_position' => 'left'
            );
        }

        return TRUE;
    }
}
