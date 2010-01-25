<?php
/* Release notes
 * == 1.3 ==
 * - Preparing for open source release
 *
 * == 1.2.2 ==
 * - Added extra options to the archive counter section
 * - Fixed a bug where the 'Recent Entries' widget caused a wrong single page counter
 *
 * == 1.2.1 ==
 * - Fixed a bug that didn't display the option page in single WP installs
 *
 * == 1.2 ===
 * - Added po_sitetype, removed thema, changed source to po_source, rewrote sitestat
 *   code a little to include basic things like whitespace and newlines
 *
 * == 1.1 ==
 * - Removed source, counter-prefix, etc.
 * - Merged counter-prefix and counter, added preview for preview
 */
class VproSitestat {
    const WIDGET_NAME = "VPRO Sitestat teller";
    const USE_NEW_COUNTER_CODE = false;

    private $path, $url, $plugin;

    private $defaults = array(
        "customername"    => "klo",     // Kijk en luisteronderzoek, doesn't change, not exposed in the UI
        "sitename"        => "vpro",    // This defaults to 'vpro'
        "counter-prefix"  => "weblogs", // Prefix before every counter
        "counter"         => "",        // mostly dynamic, has the most metadata, important field
        "category"        => "",
        "counter-preview" => "",
        "channel"         => "nieuws_informatie",
        "sitetype"        => "pip",
        "source-value"    => "fixed"
    );

    private $counterargs = array(
        "category"    => "category",
        "ns_webdir"   => "category",
        "ns_channel"  => "channel",
        "po_source"   => "source-value",
        "po_sitetype" => "sitetype"
    );

    private $channels = array(
        "arbeidsmarkt_onderwijs" => "Arbeidsmarkt en onderwijs",
        "auto_verkeer" => "Auto en verkeer",
        "computer_consumenten_elektronica" => "Computer en consumenten elektronica",
        "corporate" => "Corporate",
        "ecommerce_veiling" => "E-commerce en veiling",
        "entertainment" => "Entertainment",
        "financieel" => "Financiële producten en diensten",
        "gezin_opvoeding_gezondheid" => "Gezin, opvoeding en gezondheid",
        "huis_tuin" => "Huis en tuin",
        "internet_mobiele_services" => "Internet en mobiele services",
        "lifestyle" => "Lifestyle",
        "nieuws_informatie" => "Nieuws en informatie",
        "overheid_nonprofit" => "Overheid en not for profit",
        "portals_communities" => "Portals en communities",
        "reizen_recreatie" => "Reizen en recreatie",
        "sport" => "Sport",
        "professioneel" => "Professioneel",
        "zoekmachines_webdirectories" => "Zoekmachines en webdirectories"
    );

    private $sitetypes = array(
        "pip"     => "Programma-informatie pagina",
        "plus"    => "Pluswebsite",
        "webonly" => "Niet gerelateerd aan een RTV programma, net of zender"
    );

    function __construct() {
        // Get all the different pathnames and such, then add the hook
        $pathinfo       = pathinfo( plugin_basename(__FILE__) );
        $this->plugin   = $pathinfo['dirname'];
        $this->url      = WP_PLUGIN_URL . "/" . $this->plugin . "/";
        $this->path     = WP_PLUGIN_DIR . "/" . $this->plugin . "/";

        // Load some dynamic defaults
        $this->defaults['counter'] = $this->defaults['counter-prefix'] . "." . $this->urlize(get_bloginfo('name'));
        $this->defaults['counter-preview'] = $this->defaults['counter'];

        add_action("admin_menu", array($this, "init"));
        add_action('wp_footer', array($this, "getSitestatCode"));
    }

    public function init() {
        add_options_page(
            self::WIDGET_NAME,
            self::WIDGET_NAME,
            "administrator",
            $this->plugin,
            array($this, "form")
        );
    }

    public function reset() {
        // Essentialy, just remove the data from the db
        delete_option($this->plugin);
    }

    public function update() {
        // Loop through all the available options, check if they are
        // set in $_POST and update them in the db
        $update = array();
        foreach ($this->defaults as $key => $value) {
            if (isset($_POST[$key])) {
                $update[$key] = $this->sanitize($_POST[$key]);
            } else {
                // Load default value
                $update[$key] = $value;
            }
        }

        // TODO: disabled <input> fields do not post their contents (such as
        // 'counter'), so we need some way around that...
        update_option($this->plugin, $update);

        echo $this->parseTemplate('message', array(
            "message" => 'Je wijzigingen zijn opgeslagen.'
        ));

        return $update;
    }

    public function form() {
        // Maybe we want to reset or update options..
        if (isset($_POST['vpro-sitestat-reset'])) {
            $this->reset();
        } else if (isset($_POST['vpro-sitestat'])) {
            $this->update();
        }

        // Load options
        $data = $this->loadOptions();

        // Convert some values
        $data['channels'] = $this->generateSelectList(
            $this->channels,
            $data['channel']
        );

        $data['sitetypes'] = $this->generateSelectList(
            $this->sitetypes,
            $data['sitetype']
        );

        $data['counter-preview'] = $data['counter'];

        // Show the form with options
        echo $this->parseTemplate("form", $data);
    }

    // Output the sitestat code
    public function getSitestatCode() {
        $data = $this->loadOptions();

        // If we don't have a custom counter generate it dynamically
        $data['counter'] = $data['counter'] . $this->generateCounter();

        // Make the sitestat_url
        $data['sitestat_call_url'] = $this->generateSitestatCallUrl($data);

        $template = "counter_" . ((self::USE_NEW_COUNTER_CODE) ? "pretty" : "original");
        echo $this->parseTemplate($template, $data);
    }

    private function generateSitestatCallUrl($data) {
        // Create base url first
        $url = "http://nl.sitestat.com/" . $data['customername'] . "/" .
        $data['sitename'] . "/s?" . $data['counter'];

        // Then iterate over all values and add them
        foreach ($this->counterargs as $key => $value) {
            $url .= "&amp;" . $key . "=" . $data[$value];
        }

        return $url;
    }

    private function loadOptions() {
        // Load options from the database
        $options = get_option($this->plugin);

        // Check if there any values, otherwise, load default options
        if (!is_array($options)) {
            $options = $this->defaults;
        }

        return $options;
    }

    private function generateSelectList($data, $preselect) {
        $o = '';
        foreach($data as $value => $text) {
            $selected = $this->select($value == $preselect);
            $o .= '<option value="' . $value . '" ' . $selected .'>' . $text . '</option>' . "\n";
        }
        return $o;
    }

    private function generateCounter() {
        global $post, $wp_query;

        $sitestat = "";
        $wptitle  = $this->urlize(wp_title('', false));

        if (is_search() ){
            $sitestat .= ".zoeken";
        } else if (is_page()) {
            $sitestat .= ".pagina.". $wptitle;
        } else if (is_single()) {
            $single = get_post_meta($post->ID, "sitestat", true);
            if ($single == "") {
                // There is a bug with a few of the widgets in WP < 2.8 that don't
                // properly reset the wp_query and therefore give wrong information in
                // the $post global, so we check if the post_name in $post is different
                // than the original query, and use the original query if it is
                if ($post->post_name != $wp_query->query_vars['name']) {
                    $post_name = $wp_query->query_vars['name'];
                } else {
                    $post_name = $post->post_name;
                }

                $single = get_the_time('j-m-Y') . "_" . $post_name;
            } else {
                $single = urlencode($single);
            }

            $sitestat .= ".". $single;
        } else if (is_category()) {
            $sitestat .= ".categorie." . $wptitle;
        } else if (is_tag()) {
            $sitestat .= ".tag.". $wptitle;
        } else if (is_archive()) {
            $sitestat .= ".archief";

            // Also generate some extra information to indicate what the
            // original query was
            foreach ($wp_query->query as $key => $value) {
                $sitestat .= "." . $value;
            }
        }

        return $sitestat;
    }

    private function parseTemplate($file, $options) {
        $template = file_get_contents($this->path . $file . ".html");
        preg_match_all("!\{([^{]*)\}!", $template, $matches);

        $replacements = array();
        for ($i = 0; $i < count($matches[1]); $i++) {
            $key = $matches[1][$i];
            if (isset($options[$key])) {
                $val = $matches[0][$i];
                $template = str_replace($val, $options[$key], $template);
            }
        }

        return $template;
    }

    private function formid($id) {
        return $this->plugin . "-" . $id;
    }

    private function urlize($var) {
        // Returns an URL encoded $var with underscores replacing whitespaces
        // in lowercase, trimmed
        $var = trim($var);
        $var = str_replace(" ", "_", $var);
        $var = preg_replace("/[^\w\-\.]/", "", $var);
        $var = strtolower($var);
        return $var;
    }

    private function sanitize($var) {
        return strip_tags(stripslashes($var));
    }

    /* Input attributes */
    private function select($condition) {
        return ($condition) ? 'selected="selected"' : '';
    }

    private function checked($condition) {
        return ($condition) ? 'checked="checked"' : '';
    }

    private function disabled($condition) {
        return ($condition) ? 'disabled="disabled"' : '';
    }
}
?>