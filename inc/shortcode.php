<?php 
namespace WOO_PRODUCT_TABLE\Inc;

use WOO_PRODUCT_TABLE\Inc\Handle\Message as Msg;
use WOO_PRODUCT_TABLE\Inc\Handle\Args;
use WOO_PRODUCT_TABLE\Inc\Handle\Table_Attr;
use WOO_PRODUCT_TABLE\Inc\Table\Row;

class Shortcode extends Shortcode_Base{

    public $shortcde_text = 'SAIFUL_TABLE';
    private $assing_property = false;
    public $atts;
    public $table_id;
    public $status;
    public $post_type;
    public $req_post_type = 'wpt_product_table';
    public $posts_per_page = 20;
    public $table_type = 'normal_table';

    public $is_table;

    /**
     * Check column available or not, if empty array of _enable_cols, it will return false.
     *
     * @var boolean
     */
    public $is_table_head = true;
    public $is_table_column = true;
    public $_device;
    public $args;

    public $_enable_cols;
    public $col_count;
    public $column_array;
    public $column_settings;

    public $basics;
    public $basics_args;

    public $search_n_filter;
    public $conditions;
    public $pagination;


    public $post_include;
    public $post_exclude;
    public $min_price;
    public $max_price;
    public $minicart_position;
    public $add_to_cart_text;
    public $pagination_ajax;
    public $checkbox;
    public $template;

    public $filter_box;
    public $filter;

    public $orderby;
    public $order;
    public $meta_value_sort;

    public $table_style;
    public $_config;
    public $wrapper_class;
    public $table_class;
    public $is_column_label = false;

    public $items_directory;
    public $items_permanent_dir;


    

    public function run(){
        add_shortcode( $this->shortcde_text, [$this, 'shortcode'] );
    }
    public function shortcode($atts){
        
        $this->atts = $atts;

        $pairs = array( 'exclude' => false );
        extract( shortcode_atts( $pairs, $atts ) );
        
        $this->assing_property($atts);
        // var_dump($this);
        ob_start();

        

        ?>
        <div data-checkout_url="<?php echo esc_url( wc_get_checkout_url() ); ?>" 
        data-temp_number="<?php echo esc_attr( $this->table_id ); ?>" 
        data-add_to_cart="<?php echo esc_attr( $this->add_to_cart_text ); ?>" 
        data-site_url="<?php echo esc_url( site_url() ); ?>" 
        id="table_id_<?php echo esc_attr( $this->table_id ); ?>" 
        class="<?php echo esc_attr( Table_Attr::wrapper_class( $this ) ); ?>">
            
            <div class="wpt_table_tag_wrapper">
                <table 
                data-page_number=""
                data-temp_number=""
                data-config_json=""
                data-data_json=""
                data-data_json_backup=""
                id="wpt_table"
                class="<?php echo esc_attr( Table_Attr::table_class( $this ) ); ?>">

                <?php $this->table_head(); ?>
                <?php $this->table_body(); ?>
                </table>
            </div>

        </div>
        
        <?php 
        

        return ob_get_clean();
    }

    public function assing_property( $atts ){
        

        $this->table_id = isset( $atts['id'] ) && !empty( $atts['id'] ) ? (int) $atts['id'] : 0; 
        $this->table_id = apply_filters( 'wpml_object_id', $this->table_id, $this->req_post_type, TRUE  );
        $this->status = get_post_status( $this->table_id );
        $this->post_type = get_post_type( $this->table_id );

        // set_query_var( 'woo_product_table', $this->table_id );
        set_query_var( $this->req_post_type, $this->table_id );

        $this->is_table = $this->table_id && $this->post_type == $this->req_post_type && $this->status == 'publish';
        if( ! $this->is_table ){
            return Msg::not_found($this);
        }

          


        $this->_config = wpt_get_config_value( $this->table_id );
        $this->_device = wpt_col_settingwise_device( $this->table_id );
        $this->_enable_cols = get_post_meta( $this->table_id, 'enabled_column_array' . $this->_device, true );
        $this->column_array = get_post_meta( $this->table_id, 'column_array' . $this->_device, true );
        $this->column_settings = get_post_meta( $this->table_id, 'column_settings' . $this->_device, true);
        

        //we will removed this filter after few version. 
        $this->_enable_cols = apply_filters( 'wpto_enabled_column_array', $this->_enable_cols, $this->table_id, $this->atts, $this->column_settings, $this->column_array );
        /**
         * @Hook Filter wpto_enabled_column_array to change or modify column amount, we can use it.
         */
        $this->_enable_cols = apply_filters('wpt_enabled_column', $this->_enable_cols, $this);



        if( empty( $this->_enable_cols ) || ! is_array( $this->_enable_cols ) ){
            $this->is_table_head = false;
            $this->is_table_column = false;
            $this->_enable_cols = [];
            return Msg::not_found_cols($this);
        }

        $this->col_count = count( $this->_enable_cols );


        $this->basics = $this->get_meta( 'basics' );
        
        $this->basics_args = $this->basics['args'] ?? [];
        
        $this->conditions = $this->get_meta( 'conditions' );
        $this->table_style = $this->get_meta( 'table_style' );
        
        $this->search_n_filter = $this->get_meta( 'search_n_filter' );
        $this->pagination = $this->get_meta( 'pagination' );

        $this->posts_per_page = $this->conditions['posts_per_page'] ?? $this->posts_per_page;
        $this->table_type = $this->conditions['table_type'] ?? $this->table_type;

        //Some Basic Meta Values | All other query related available in Args Class
        $this->table_head = ! isset( $this->basics['table_head'] ) ? true : false;
        $this->minicart_position = $this->basics['minicart_position'] ?? '';
        $this->ajax_action = $this->basics['ajax_action'] ?? '';
        $this->add_to_cart_text = $this->basics['add_to_cart_text'] ?? '';//$basics['add_to_cart_text'] ?? ''
        $this->pagination_ajax = $this->basics['pagination_ajax'] ?? '';
        $this->checkbox = $this->basics['checkbox'] ?? 'wpt_no_checked_table'; //$checkbox = isset( $basics['checkbox'] ) && !empty( $basics['checkbox'] ) ? $basics['checkbox'] : 'wpt_no_checked_table';

        //Some others from other meta
        $this->template = $this->table_style['template'] ?? '';
        $filter_box = $this->search_n_filter['filter_box'] ?? '';
        $this->filter_box = $filter_box == 'yes' ? true : false;
        if( $this->filter_box ){
            $this->filter = $this->search_n_filter['filter'] ?? [];
        }
        
        $this->args = Args::manage($this);

        //This Filter will be deleted in future update
        $this->args = apply_filters( 'wpto_table_query_args', $this->args, $this->table_id, $this->atts, $this->column_settings, $this->_enable_cols, $this->column_array );

        /**
         * @Hook filter wpt_query_args manage wpt table query args using filter hook
         */
        $this->args = $this->apply_filter( 'wpt_query_args', $this->args );


        $this->assing_property = true;

        $this->items_permanent_dir = WPT_DIR_BASE . 'includes/items/';
        $this->items_permanent_dir = apply_filters('wpto_item_permanent_dir', $this->items_permanent_dir, $this->table_id, null );
        $this->items_directory = apply_filters('wpto_item_dir', $this->items_permanent_dir, $this->table_id, null );
        $this->items_directory = $this->apply_filter( 'wpt_item_dir', $this->items_directory );

        $this->is_column_label = $this->table_style['tr.wpt_table_head th']['auto-responsive-column-label'] ?? false;
    }

    public function set_shortcde_text( string $shortcde_text ){
        $this->shortcde_text = $shortcde_text;
        return $this;
    }
    public function get_shortcde_text(){
        return $this->shortcde_text;
    }

    private function table_body( $id = false ){
        if( ! $this->assing_property && ! $id ){
            $atts = [
                'id' => $id
            ];
            $this->assing_property( $atts );
        }
        $product_loop = new \WP_Query( $this->args );
        if ($this->orderby == 'random') {
            shuffle( $product_loop->posts );
        }
        
        /**
         * @deprecated 3.2.4.2 wpto_product_loop filter will removed in next version
         */
        $product_loop = apply_filters( 'wpto_product_loop', $product_loop, $this->table_id, $this->args );
        $product_loop = $this->apply_filter( 'wpt_product_loop', $product_loop );
        if (  $product_loop->have_posts() ) : while ($product_loop->have_posts()): $product_loop->the_post();
            global $product;
            $row = new Row($this);
            $row->render();


        endwhile;
        else:
        Msg::not_found_product_tr($this);
        endif;


    }
    private function table_head(){
        if( ! $this->table_head ) return;
        if( ! $this->is_table_head ) return; //Check column available or not, if empty array of _enable_cols, it will return false.
        
        ?>
        <thead>
            <tr data-temp_number="<?php echo esc_attr( $this->table_id ); ?>" class="wpt_table_header_row wpt_table_head">
            <?php 
            foreach( $this->_enable_cols as $key => $col ){
            $col_content = $this->column_array[$key] ?? $col;
            if( $key == 'check' ){
                $col_content = "<input data-type='universal_checkbox' data-temp_number='{$this->table_id}' class='wpt_check_universal' id='wpt_check_uncheck_column_{$this->table_id}' type='checkbox'><label for=wpt_check_uncheck_column_{$this->table_id}></label>";
            }
            ?>
            <th class="<?php echo esc_attr( $key ); ?>">
                <?php echo $col_content; ?>
            </th>
            <?php
            }
            ?>
            </tr>
        </thead>
        <?php
        
    }

    
}