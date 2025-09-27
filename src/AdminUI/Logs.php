<?php
/**
 * Displays pay-in-3 logs in Admin UI.
 * 
 * @package WpShiftStudio\PayIn3ForWC\AdminUI
 */

namespace WpShiftStudio\PayIn3ForWC\AdminUI;
          
class Logs{

    /**
     * Registers the admin page for pay in 3 logs.
     *
     * @return void
     */
    public function register_menu_page(){
        add_submenu_page( 
            'woocommerce',
            'Pay in 3 Logs',
            'Pay in 3 Logs',
            'manage_woocommerce',
            'pay-in-3-logs',
            array($this, 'render_page'),
        );
    }

    /**
     * Renders the page of Pay in 3 logs in admin.
     *
     * @return void
     */
    public function render_page(){

        if(!(current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' ))){
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'pay-in-3' ) );
        }
        $current_handle = isset($_GET['handle']) ? sanitize_text_field($_GET['handle']) : '';
        $plugin_handles = self::get_plugin_log_files();
         
        ?>

        <div class='wrap'>
            <h2> <?php echo esc_html(get_admin_page_title()); ?> </h2>
            <form method='GET'>
                <input type='hidden' name='page' value='pay-in-3-logs'/>
                <select name='handle'>
                    <option value=''>
                        <?php esc_html_e( 'Select a log file', 'pay-in-3' ); ?>
                    </option>
                    <?php foreach($plugin_handles as $handle => $fullpath) : ?>
                        <option 
                            value="<?php echo esc_attr($handle); ?>" 
                            <?php selected($current_handle, $handle); ?>
                        >
                            <?php
                                $label = preg_replace( '/-[a-f0-9]{32}\.log$/', '', $handle );
                                echo esc_html($label);  
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input 
                    type='submit' 
                    class='button' 
                    value="<?php esc_html_e('View', 'pay-in-3'); ?>" 
                />
            </form>

            <?php if($current_handle): 
                $content = '';
                if( isset( $plugin_handles[ $current_handle ] ) && is_readable( $plugin_handles[ $current_handle ] ) ) {
                    $content = file_get_contents($plugin_handles[$current_handle]);
                }
            ?>
            <pre class="pay-in-3-log-viewer" style="max-height:60vh;overflow:auto;padding:12px;background:#fff;border:1px solid #ccd0d4;font-family:Menlo,Consolas,monospace;"><?php
            echo esc_html( ltrim( $content ) ); 
            ?></pre>
            <?php else : ?>
            <p> <?php esc_html_e('Please select a log file from the dropdown to view its contents.', 'pay-in-3' )?> </p>
            <?php endif; ?>
        </div>
        <?php                
    }

    /**
     * Provides an array of woocommerce log files.
     *
     * @return array Log files array. returns [ 'filename.log' => '/full/path/filename.log', ... ].
     */
    public static function get_plugin_log_files() : array {
        // Candidate log dirs.
        $dirs = [];

        if ( class_exists( '\WC_Log_Handler_File' ) && method_exists( '\WC_Log_Handler_File', 'get_log_dir' ) ) {
            $dirs[] = \WC_Log_Handler_File::get_log_dir(); // uploads/wc-logs/.
        }
        $uploads = wp_get_upload_dir();
        if ( ! empty( $uploads['basedir'] ) ) {
            $dirs[] = trailingslashit( $uploads['basedir'] ) . 'wc-logs/';           // fallback.
            $dirs[] = trailingslashit( $uploads['basedir'] ) . 'woocommerce/logs/';  // legacy.
        }
        if ( defined( 'WP_CONTENT_DIR' ) ) {
            $dirs[] = trailingslashit( WP_CONTENT_DIR ) . 'wc-logs/';                // some hosts.
        }

        // Keep existing, readable dirs only.
        $dirs = array_values( array_unique( array_filter( $dirs, static function( $d ) {
            return is_string( $d ) && is_dir( $d ) && is_readable( $d );
        } ) ) );

        // Strict prefix match: pay-in-3*, pay_in_3*, payin3*.
        $out = [];

        foreach ( $dirs as $dir ) {
            $dh = @opendir( $dir );
            if ( ! $dh ) { continue; }

            while ( false !== ( $f = readdir( $dh ) ) ) {
                if ( substr( $f, -4 ) !== '.log' ) { continue; }

                // Only our plugin logs.
                if ( ! preg_match( '/^(pay[-_]?in[-_]?3)/i', $f ) ) {
                    continue;
                }

                $full = $dir . $f;
                if ( is_file( $full ) && is_readable( $full ) ) {
                    $out[ $f ] = $full;
                }
            }
            closedir( $dh );
        }

        // Sort newest first.
        uasort( $out, static function( $a, $b ) {
            return @filemtime( $b ) <=> @filemtime( $a );
        } );

        return $out; // No fallback to "all logs".
    }


}
