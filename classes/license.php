<?php

class PeepSoLicense
{
    const PEEPSO_HOME = 'https://peepso.com';

    const OPTION_DATA = 'peepso_license_data';

	const PEEPSO_LICENSE_TRANS = 'peepso_license_';
    private static $_licenses = NULL;

    /**
     * Verifies the license key for an add-on by the plugin's slug
     * @param string $plugin_edd The PLUGIN_NAME constant value for the plugin being checked
     * @param string $plugin_slug The PLUGIN_SLUG constant value for the plugin being checked
     * @return boolean TRUE if the license is active and valid; otherwise FALSE.
     */
    public static function check_license($plugin_edd, $plugin_slug, $is_admin = FALSE)
    {
		if( FALSE === $is_admin) {
			$is_admin = is_admin();
		}

        $license_data = self::_get_product_data($plugin_slug);

        if (NULL === $license_data) {
            // no license data exists; create it
            $license_data['slug'] = $plugin_slug;
            $license_data['name'] = $plugin_edd;
            $license_data['license'] = '';
			$license_data['state'] = 'invalid';
			$license_data['response'] = 'invalid';
            $license_data['expire'] = 0;
            // write the license data
        }

        if ($is_admin) {
            self::_set_product_data($plugin_slug, $license_data);
            self::activate_license($plugin_slug, $plugin_edd);
        } else {
			// Frontend will return "FALSE" only in some scenarios
			if (!self::is_valid_key($plugin_slug)) {

				/*
				 * $license_data['response']
				 *
				 * invalid				FALSE - key BAD
				 * inactive				FALSE - key OK, not active
				 * item_name_mismatch 	FALSE - key OK, wrong plugin
				 * site_inactive		TRUE  - key OK, wrong domain
				 * expired				TRUE  - key OK, license expired
				 */

				if(!array_key_exists('response', $license_data)) {
					$license_data['response'] = 'valid';
				}

				switch ($license_data['response']) {
					case 'invalid':
					case 'inactive':
					case 'item_name_mismatch':
						return FALSE;
						break;
					default:
						return TRUE;
						break;
				}
			}
		}

        // check to see if the license key is valid for the named plugin
        return (self::is_valid_key($plugin_slug));
    }

	public static function get_license($plugin_slug)
	{
		return self::_get_product_data($plugin_slug);
	}

	public static function delete_transient($plugin_slug)
	{
		$trans_key = self::trans_key($plugin_slug);
		delete_transient($trans_key);
	}

	private static function trans_key($plugin_slug)
	{
		return self::PEEPSO_LICENSE_TRANS . $plugin_slug;
	}

    /**
     * Activates the license key for a PeepSo add-on
     * @param string $plugin_slug The add-on's slug name
     * @param string $plugin_edd The add-on's full plugin name
     * @return boolean TRUE on successful activation; otherwise FALSE
     */
    public static function activate_license($plugin_slug, $plugin_edd)
    {
        // how long to keep the transient keys?
		$trans_lifetime = 24 * HOUR_IN_SECONDS;

        // get key stored from config pages
        $key = self::_get_key($plugin_slug);

        $license_data['license'] = $key;
        $license_data['name'] = $plugin_edd;

        if (FALSE === $key || 0 === strlen($key)) {
			return;
		}

        // when asking EDD API use "item_id" if plugin_edd is numeric, otherwise "item_name"
        $key_type = 'item_name';

        if(is_numeric($plugin_edd)) {
            $key_type = 'item_id';
            $plugin_edd = (int) $plugin_edd;
        }

        $args = array(
            'edd_action' => 'activate_license',
            'license' => $key,
            $key_type => $plugin_edd
        );

        // Use transient key to check for cached values
		$trans_key = self::trans_key($plugin_slug);

		// If there is no cached value, call home
		$validation_data = get_transient($trans_key);

        if ( !is_object($validation_data) ) {

            $micro_start = microtime(TRUE);
            #new PeepSoError("license check started (".self::PEEPSO_HOME.")", 'message',$plugin_edd);

            $resp = wp_remote_get(add_query_arg($args, self::PEEPSO_HOME),	// contact the home office
                array('timeout' => 15, 'sslverify' => FALSE));				// options

            $micro_elapsed = microtime(TRUE) - $micro_start;

            if (is_wp_error($resp)) {
				// If PeepSo.com is down build a fake license for 1 hour
                new PeepSoError("license check timeout, $micro_elapsed",'error',$plugin_edd);
                new PeepSoError("license check errors\n".print_r($resp->errors, TRUE),'error',$plugin_edd);

				$trans_lifetime = 1 * HOUR_IN_SECONDS;

				$validation_data = new stdClass();

				$validation_data->success = true;

				$validation_data->license 			= 'valid';
  				$validation_data->item_name 		= $plugin_slug;
  				$validation_data->expires			= '2020-01-01 00:00:00';
  				$validation_data->payment_id		= 0;
  				$validation_data->customer_name 	= 'temporary';
  				$validation_data->customer_email	= 'temporary@peepso.com';
  				$validation_data->license_limit 	= 0;
  				$validation_data->site_count		= 0;
  				$validation_data->activations_left 	= 'unlimited';
			} else {
				$response = wp_remote_retrieve_body($resp);

				$validation_data = json_decode($response);
			}
            set_transient($trans_key, $validation_data, $trans_lifetime);
        }
        
        if ('valid' === $validation_data->license) {
            // if parent site reports the license is active, update the stored data for this plugin
			$license_data['state'] = 'valid';
            $license_data['expire'] = $validation_data->expires;
        } else {
            // if parent site reports the license as inactive, update the stored data as well
			$license_data['state'] = 'invalid';
            $license_data['expire'] = '';
        }

		// remaining options
		$license_data['response'] = $validation_data->license;

		// save
		self::_set_product_data($plugin_slug, $license_data);
    }

    /**
     * Loads the license information from the options table
     */
    private static function _load_licenses()
    {
        if (NULL === self::$_licenses) {
            $lisc = get_option(self::OPTION_DATA, FALSE);
            if (FALSE === $lisc) {
                $lisc = array();
                add_option(self::OPTION_DATA, $lisc, FALSE, FALSE);
            }
            self::$_licenses = $lisc;
        }
    }

    /**
     * Retrieves product data for a given add-on by slug name
     * @param string $plugin_slug The plugin's slug name
     * @return mixed The data array stored for the plugin or NULL if not found
     */
    private static function _get_product_data($plugin_slug)
    {
        self::_load_licenses();
        $plugin_slug = sanitize_key($plugin_slug);

        if (isset(self::$_licenses[$plugin_slug])) {
            // check license data for validity
            $data = self::$_licenses[$plugin_slug];
            $str = md5($plugin_slug . '|' . esc_html($data['name']) .
                '~' . $data['license'] . ',' . $data['expire'] . $data['state']);

            // return data only if checksum validates
            if (isset($data['checksum']) && $str === $data['checksum'])
                return ($data);
        }
        return (NULL);
    }

    /**
     * Sets the stored license information per product
     * @param string $plugin_slug The plugin's slug
     * @param array $data The data array to store
     */
    private static function _set_product_data($plugin_slug, $data)
    {
        /*
         * data:
         *	['slug'] = plugin slug
         *	['name'] = plugin name
         *	['license'] = license key
         *	['state'] = license state
         *	['expire'] = license expiration
         *	['checksum'] = checksum
         */

        $plugin_slug = sanitize_key($plugin_slug);
        $data['slug'] = $plugin_slug;
        $str = $plugin_slug . '|' . esc_html($data['name']) .
            '~' . $data['license'] . ',' . $data['expire'] . $data['state'];
        $data['checksum'] = md5($str);
        self::_load_licenses();
        self::$_licenses[$plugin_slug] = $data;
        update_option(self::OPTION_DATA, self::$_licenses);
    }

    /**
     * Get the license key stored for the named plugin
     * @param string $plugin_slug The PLUGIN_SLUG constant value for the add-on to obtain the license key for
     * @return string The entered license key or FALSE if the named license key is not found
     */
    private static function _get_key($plugin_slug)
    {
        return (PeepSo::get_option('site_license_' . $plugin_slug, FALSE));
    }

    /**
     * Determines if a key is valid and active
     * @param string $plugin Plugin slug name
     * @return boolean TRUE if the key for the named plugin is valid; otherwise FALSE
     */
    public static function is_valid_key($plugin)
    {
        self::_load_licenses();
        $plugin_slug = sanitize_key($plugin);

		if (!isset(self::$_licenses[$plugin_slug])) {
		    echo "<hr>ZJEBALOSIE $plugin_slug<hr>";
			return (FALSE);
		}

        $data = self::$_licenses[$plugin_slug];

        $str = $plugin_slug . '|' . esc_html($data['name']) .
            '~' . $data['license'] . ',' . $data['expire'] . $data['state'];

        $dt = new PeepSoDate($data['expire']);

        return (md5($str) === $data['checksum'] && 'valid' === $data['state'] && $dt->TimeStamp() > time());
    }

	public static function get_key_state($plugin)
	{
		self::_load_licenses();
		$plugin_slug = sanitize_key($plugin);
		if (!isset(self::$_licenses[$plugin_slug])) {
			return "unknown";
		}

		$data = self::$_licenses[$plugin_slug];

		return array_key_exists('response', $data) ? $data['response'] : 'unknown';
	}

    public static function dump_data()
    {
        self::_load_licenses();
        var_export(self::$_licenses);
    }

    // The old check_updates() doesn't know the difference between plugin_slug and plugin_edd
    // since 1.7.6 plugin_edd can be numeric and different from plugin_slug
    public static function check_updates_new( $plugin_edd, $plugin_slug, $plugin_version, $file, $is_core = TRUE ) {

        // Core plugins are checked only if the version number on PeepSo.com has changed
        if( TRUE == $is_core ) {
            // Version number is usually cached in a transient
            $trans = 'peepso_current_version';
            $version = get_transient($trans);

            // If not, get it from peepso.com
            if (!strlen($version)) {

                $version = 0;
                $url = 'https://peepso.com/peepsotools-integration-json/version.txt';

                // Attempt contact with PeepSo.com without sslverify
                $resp = wp_remote_get(add_query_arg(array(), $url), array('timeout' => 10, 'sslverify' => FALSE));

                // In some cases sslverify is needed
                if (is_wp_error($resp)) {
                    $resp = wp_remote_get(add_query_arg(array(), $url), array('timeout' => 10, 'sslverify' => TRUE));
                }

                // Definite failure - freeze the checks for a while
                if (is_wp_error($resp)) {
                    // trigger_error('check_updates - failed to load version.txt from PeepSo.com');
                    set_transient($trans, PeepSo::PLUGIN_VERSION, 30);
                } else {
                    // Success - store the version in a 5 minute transient
                    $version = $resp['body'];
                    set_transient($trans, $version, 5 * 60);
                }
            }

            if (1 != version_compare($version, $plugin_version)) {
                return( FALSE );
            }
        } else {

            // Other plugins use transient cache
            $trans = 'peepso_' . $plugin_edd . '_version_check';
            if (strlen(get_transient($trans))) {
                if(!isset($_GET['force-check'])) {
                    return( FALSE );
                }
            }

            // Timeout is randomized between 5 and 8 minutes
            $timeout = 4 * 60 + rand(1 * 60, 4 * 60);
            set_transient($trans, $plugin_version, $timeout);
        }

        // If neither if/else block returned FALSE, the version check will happen
        if( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
            include(dirname(__FILE__) . '/license_edd_helper.php');
        }

        $license = PeepSoLicense::get_license($plugin_slug);

        $key_name = 'item_name';
        if(is_numeric($plugin_edd)) {
            $key_name = 'item_id';
        }

        $args = array(
            'version' 	=> $plugin_version,
            'license' 	=> $license['license'],
            $key_name   => $plugin_edd,
            'author' 	=> '',
            'url'       => self::PEEPSO_HOME,
        );

        $edd_updater = new EDD_SL_Plugin_Updater( self::PEEPSO_HOME, $file, $args );


    }


    public static function check_updates( $plugin_edd, $plugin_version, $file, $is_core = TRUE )
	{
		// Core plugins are checked only if the version number on PeepSo.com has changed
		if( TRUE == $is_core ) {
			// Version number is usually cached in a transient
			$trans = 'peepso_current_version';
			$version = get_transient($trans);

			// If not, get it from peepso.com
			if (!strlen($version)) {

				$version = 0;
				$url = 'https://peepso.com/peepsotools-integration-json/version.txt';

				// Attempt contact with PeepSo.com without sslverify
				$resp = wp_remote_get(add_query_arg(array(), $url), array('timeout' => 10, 'sslverify' => FALSE));

				// In some cases sslverify is needed
				if (is_wp_error($resp)) {
					$resp = wp_remote_get(add_query_arg(array(), $url), array('timeout' => 10, 'sslverify' => TRUE));
				}

				// Definite failure - freeze the checks for a while
				if (is_wp_error($resp)) {
					// trigger_error('check_updates - failed to load version.txt from PeepSo.com');
					set_transient($trans, PeepSo::PLUGIN_VERSION, 30);
				} else {
					// Success - store the version in a 5 minute transient
					$version = $resp['body'];
					set_transient($trans, $version, 5 * 60);
				}
			}

			if (1 != version_compare($version, $plugin_version)) {
				return( FALSE );
			}
		} else {

			// Other plugins use transient cache
			$trans = 'peepso_' . $plugin_edd . '_version_check';
			if (strlen(get_transient($trans))) {
			    if(!isset($_GET['force-check'])) {
                    return( FALSE );
                }
			}

			// Timeout is randomized between 5 and 8 minutes
			$timeout = 4 * 60 + rand(1 * 60, 4 * 60);
			set_transient($trans, $plugin_version, $timeout);
		}

		// If neither if/else block returned FALSE, the version check will happen
		if( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
			include(dirname(__FILE__) . '/license_edd_helper.php');
		}

		$license = PeepSoLicense::get_license($plugin_edd);

		$args = array(
			'version' 	=> $plugin_version,
			'license' 	=> $license['license'],
			'item_name' => $plugin_edd,
			'author' 	=> '',
			'url'       => self::PEEPSO_HOME,
		);

		$edd_updater = new EDD_SL_Plugin_Updater( self::PEEPSO_HOME, $file, $args );
	}
}

// EOF
