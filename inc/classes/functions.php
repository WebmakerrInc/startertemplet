<?php
/**
 * Functions
 *
 * @since  2.0.0
 * @package Astra Sites
 */

if ( ! function_exists( 'astra_sites_error_log' ) ) :

	/**
	 * Error Log
	 *
	 * A wrapper function for the error_log() function.
	 *
	 * @since 2.0.0
	 *
	 * @param  mixed $message Error message.
	 * @return void
	 */
	function astra_sites_error_log( $message = '' ) {
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			if ( is_array( $message ) ) {
				$message = wp_json_encode( $message );
			}

			if ( apply_filters( 'astra_sites_debug_logs', false ) ) {
				error_log( $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- This is for the debug logs while importing. This is conditional and will not be logged in the debug.log file for normal users.
			}
		}
	}

endif;

if ( ! function_exists( 'astra_sites_should_skip_plugin_installation' ) ) :
        /**
         * Determine whether plugin installation should be skipped.
         *
         * @since 4.4.45
         *
         * @param bool   $requested_skip Skip flag requested by the caller.
         * @param string $plugin_slug    Plugin slug.
         * @param array  $context        Additional contextual data for filters.
         * @return bool Whether the plugin installation should be skipped.
         */
        function astra_sites_should_skip_plugin_installation( $requested_skip, $plugin_slug = '', $context = array() ) {
                $skip = (bool) $requested_skip;

                if ( ! $skip && defined( 'ASTRA_SITES_SKIP_PLUGIN_INSTALLATION' ) && ASTRA_SITES_SKIP_PLUGIN_INSTALLATION ) {
                        $skip = true;
                }

                /**
                 * Filter to allow skipping plugin installation when importing starter templates.
                 *
                 * @since 4.4.45
                 *
                 * @param bool   $skip        Whether to skip the plugin installation.
                 * @param string $plugin_slug Plugin slug.
                 * @param array  $context     Additional context about the request.
                 */
                return (bool) apply_filters( 'astra_sites_skip_plugin_installation', $skip, $plugin_slug, $context );
        }
endif;

if ( ! function_exists( 'astra_sites_get_suggestion_link' ) ) :
	/**
	 *
	 * Get suggestion link.
	 *
	 * @since 2.6.1
	 *
	 * @return suggestion link.
	 */
	function astra_sites_get_suggestion_link() {
		$white_label_link = Astra_Sites_White_Label::get_option( 'astra-agency', 'licence' );

		if ( empty( $white_label_link ) ) {
			$white_label_link = 'https://wpastra.com/sites-suggestions/?utm_source=demo-import-panel&utm_campaign=astra-sites&utm_medium=suggestions';
		}
		return apply_filters( 'astra_sites_suggestion_link', $white_label_link );
	}
endif;

if ( ! function_exists( 'astra_sites_is_valid_image' ) ) :
	/**
	 * Check for the valid image
	 *
	 * @param string $link  The Image link.
	 *
	 * @since 2.6.2
	 * @return boolean
	 */
	function astra_sites_is_valid_image( $link = '' ) {
		return preg_match( '/^((https?:\/\/)|(www\.))([a-z0-9-].?)+(:[0-9]+)?\/[\w\-\@]+\.(jpg|png|gif|jpeg|svg)\/?$/i', $link );
	}
endif;

if ( ! function_exists( 'astra_get_site_data' ) ) :
	/**
	 * Returns the value of the index for the Site Data
	 *
	 * @param string $index  The index value of the data.
	 *
	 * @since 2.6.14
	 * @return mixed
	 */
	function astra_get_site_data( $index = '' ) {
		
		$demo_data = Astra_Sites_File_System::get_instance()->get_demo_content();
		if ( ! empty( $demo_data ) && isset( $demo_data[ $index ] ) ) {
			return $demo_data[ $index ];
		}
		return '';
	}
endif;

if ( ! function_exists( 'astra_sites_normalize_wxr_url' ) ) :

	/**
	 * Normalize the provided WXR URL string.
	 *
	 * @since 4.4.41
	 *
	 * @param mixed $value Potential WXR URL value.
	 * @return string Normalized WXR URL or empty string when invalid.
	 */
	function astra_sites_normalize_wxr_url( $value ) {

		if ( is_array( $value ) ) {
			$value = astra_sites_extract_wxr_url_from_array( $value );
		}

		if ( ! is_string( $value ) ) {
			return '';
		}

		$value = trim( html_entity_decode( $value ) );

		if ( '' === $value ) {
			return '';
		}

		if ( 'null' === strtolower( $value ) ) {
			return '';
		}

		if ( 0 === strpos( $value, '//' ) ) {
			$value = 'https:' . $value;
		}

		$value = esc_url_raw( $value );

		if ( '' === $value ) {
			return '';
		}

		return $value;
	}
endif;

if ( ! function_exists( 'astra_sites_extract_wxr_url_from_array' ) ) :

	/**
	 * Attempt to locate a WXR URL within an array structure.
	 *
	 * @since 4.4.41
	 *
	 * @param array $data Data array to inspect.
	 * @return string Detected WXR URL or empty string when not found.
	 */
	function astra_sites_extract_wxr_url_from_array( $data ) {

		if ( ! is_array( $data ) ) {
			return '';
		}

		foreach ( $data as $value ) {
			$candidate = '';

			if ( is_string( $value ) ) {
				$candidate = astra_sites_normalize_wxr_url( $value );
			} elseif ( is_array( $value ) ) {
				$candidate = astra_sites_extract_wxr_url_from_array( $value );
			}

			if ( '' !== $candidate && ( false !== stripos( $candidate, '.xml' ) || false !== stripos( $candidate, 'wxr' ) ) ) {
				return $candidate;
			}
		}

		return '';
	}
endif;

if ( ! function_exists( 'astra_sites_populate_wxr_path' ) ) :

	/**
	 * Ensure the provided demo data array contains a usable WXR URL.
	 *
	 * @since 4.4.41
	 *
	 * @param array $demo_data    Demo data received from the API.
	 * @param bool  $allow_remote Allow refetching the demo data from the API.
	 * @return array{0:array,1:string} Updated demo data and resolved WXR URL.
	 */
	function astra_sites_populate_wxr_path( $demo_data, $allow_remote = true ) {

		if ( ! is_array( $demo_data ) ) {
			return array( $demo_data, '' );
		}

		$wxr_url = '';

		if ( isset( $demo_data['astra-site-wxr-path'] ) ) {
			$wxr_url = astra_sites_normalize_wxr_url( $demo_data['astra-site-wxr-path'] );
		}

		if ( '' === $wxr_url ) {
			$wxr_url = astra_sites_extract_wxr_url_from_array( $demo_data );
		}

		if ( '' !== $wxr_url ) {
			$demo_data['astra-site-wxr-path'] = $wxr_url;
			return array( $demo_data, $wxr_url );
		}

		if ( ! $allow_remote ) {
			return array( $demo_data, '' );
		}

		$template_id = isset( $demo_data['id'] ) ? absint( $demo_data['id'] ) : 0;

		if ( ! $template_id ) {
			return array( $demo_data, '' );
		}

		if ( ! class_exists( 'Astra_Sites_Importer' ) && defined( 'ASTRA_SITES_DIR' ) ) {
			require_once ASTRA_SITES_DIR . 'inc/classes/class-astra-sites-importer.php';
		}

		if ( ! class_exists( 'Astra_Sites_Importer' ) ) {
			return array( $demo_data, '' );
		}

		$refetched = Astra_Sites_Importer::get_single_demo( $template_id );

		if ( is_wp_error( $refetched ) ) {
			astra_sites_error_log( 'Unable to refetch demo data for WXR: ' . $refetched->get_error_message() );
			return array( $demo_data, '' );
		}

		if ( is_array( $refetched ) ) {
			list( $refetched, $wxr_url ) = astra_sites_populate_wxr_path( $refetched, false );

			if ( '' !== $wxr_url ) {
				$demo_data['astra-site-wxr-path'] = $wxr_url;
				return array( $demo_data, $wxr_url );
			}
		}

		return array( $demo_data, '' );
	}
endif;

if ( ! function_exists( 'astra_sites_get_import_data_with_wxr' ) ) :

	/**
	 * Retrieve cached import data ensuring the WXR path is available.
	 *
	 * @since 4.4.41
	 *
	 * @return array<string, mixed>
	 */
	function astra_sites_get_import_data_with_wxr() {

		$demo_data = Astra_Sites_File_System::get_instance()->get_demo_content();

		if ( ! is_array( $demo_data ) ) {
			return array();
		}

		$template_type = get_option( 'astra_sites_current_import_template_type', 'classic' );

		if ( 'ai' === $template_type ) {
			return $demo_data;
		}

		list( $demo_data, $wxr_url ) = astra_sites_populate_wxr_path( $demo_data );

		if ( '' !== $wxr_url ) {
			Astra_Sites_File_System::get_instance()->update_demo_data( $demo_data );
		}

		return $demo_data;
	}
endif;

if ( ! function_exists( 'astra_sites_get_reset_form_data' ) ) :
	/**
	 * Get all the forms to be reset.
	 *
	 * @since 3.0.3
	 * @return array
	 */
	function astra_sites_get_reset_form_data() {
		global $wpdb;

		$form_ids = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_astra_sites_imported_wp_forms'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- We need this to get all the WP forms. Traditional WP_Query would have been expensive here.

		return $form_ids;
	}
endif;

if ( ! function_exists( 'astra_sites_get_reset_term_data' ) ) :
	/**
	 * Get all the terms to be reset.
	 *
	 * @since 3.0.3
	 * @return array
	 */
	function astra_sites_get_reset_term_data() {
		global $wpdb;

		$term_ids = $wpdb->get_col( "SELECT term_id FROM {$wpdb->termmeta} WHERE meta_key='_astra_sites_imported_term'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- We need this to get all the terms and taxonomy. Traditional WP_Query would have been expensive here.

		return $term_ids;
	}
endif;

if ( ! function_exists( 'astra_sites_empty_post_excerpt' ) ) :
	/**
	 * Remove the post excerpt
	 *
	 * @param int $post_id  The post ID.
	 * @since 3.1.0
	 */
	function astra_sites_empty_post_excerpt( $post_id = 0 ) {
		if ( ! $post_id ) {
			return;
		}

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_excerpt' => '',
			)
		);
	}
endif;

if ( ! function_exists( 'astra_sites_sanitize_recursive' ) ) :
	/**
	 * Recursively sanitize an array in single dimension or string using sanitize_text_field.
	 *
	 * @param mixed $data The data to sanitize. Can be a string or an array.
	 * @since 4.4.21
	 * @return mixed The sanitized data.
	 */
	function astra_sites_sanitize_recursive( $data ) {
		if ( is_array( $data ) ) {
			return array_map( 'astra_sites_sanitize_recursive', $data );
		}
		return sanitize_text_field( $data );
	}
endif;
