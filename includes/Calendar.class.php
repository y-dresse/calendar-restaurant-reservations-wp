<?php
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'rtbCalendar' ) ) {

/**
 * Class added by Yassine 
 * 
 * @since 2.4.4 
 *  */
class rtbCalendar {

    public function __construct() {

        // Add menu to admin
        add_action( 'admin_menu' , array($this, 'add_calendar_to_menu'));
        // Add scripts & styles to page
        add_action( 'admin_enqueue_scripts',  array( $this, 'enqueue_scripts' ) );
        // Ajax Requests
        add_action('wp_ajax_rtb-admin-calendar-events', array ($this, 'gets_events_ajax'));
    }

    
    /**
     * Add the submenu calendar to admin page
     */
    public function add_calendar_to_menu(){
        add_submenu_page( 
            'rtb-bookings', 
            'Calendrier', 
            'Calendrier', 
            'manage_options', 
            'rtb-calendar', 
            array($this, 'display_calendar_page')
        );
    }

    // Enqueues the admin script so that our hacky sub-menu opening function can run
	public function enqueue_scripts() {
		global $admin_page_hooks;

		$currentScreen = get_current_screen();
		if ( $currentScreen->id == $admin_page_hooks['rtb-bookings'] . '_page_rtb-calendar' ) {
			wp_enqueue_style( 'rtb-calendar-css', RTB_PLUGIN_URL . '/lib/full-calendar/main.css', array(), RTB_VERSION );
			wp_enqueue_script( 'rtb-calendar-js', RTB_PLUGIN_URL . '/lib/full-calendar/main.js', array( 'jquery' ), RTB_VERSION, true );
            wp_enqueue_style( 'bootstrap', '//cdn.jsdelivr.net/npm/bootstrap@4.5.0/dist/css/bootstrap.css' );
            wp_enqueue_style( 'font-awesome', '//cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.13.1/css/all.css');
        }
	}

    public function print_modal(){
        global $rtb_controller;

        return $rtb_controller->bookings->print_booking_form_fields();
    }

    public function get_bookings(){
        global $rtb_controller;

        require_once( RTB_PLUGIN_DIR . '/includes/Query.class.php' );
        $query = new rtbQuery( array() );
        $query->prepare_args();

        $bookings = $query->get_bookings();
        $booking_statuses = $rtb_controller->cpts->booking_statuses;

        return array( $bookings, $booking_statuses );
    }

    public function get_color_from_statues($statues, $booking){
        switch($statues[$booking->post_status]['label']){
            case "Confirmé":
                return "green";
            case "En attente":
                return "orange";
            case "Fermé":
                return "red";
            default:
                return "black";
        }

    }
    public function create_events_from_bookings(){
        list($bookings, $statues) = $this->get_bookings();

        $events = array();
        foreach ($bookings as $booking) {
            $object = new stdClass();
            $object->id = $booking->ID;
            $object->title = $booking->name . ' (' . $booking->party . ')';
            $object->start = $booking->date;
            $object->editable = false;
            $object->color = $this->get_color_from_statues($statues, $booking);
            $events[] = $object;
        }
        return $events;
    }
    
    public function gets_events_ajax(){
        wp_send_json_success($this->create_events_from_bookings());
    }


	public function display_calendar_page() {
        ?>
            <style>
                #calendar {
                    max-width: 85vw ;
                    max-height: 80vh ;
                    margin: 40px auto;
                    padding: 0 10px;
                }
            </style>
            <body>
                <div id='calendar'></div>


                <div id="rtb-booking-modal" class="rtb-admin-modal">
                    <div class="rtb-booking-form rtb-container">
                        <form method="POST">
                            <input type="hidden" name="action" value="admin_booking_request">
                            <input type="hidden" name="ID" value="">

                            <?php
                            /**
                             * The generated fields are wrapped in a div so we can
                             * replace its contents with an HTML blob passed back from
                             * an Ajax request. This way the field data and error
                             * messages are always populated from the same server-side
                             * code.
                             */
                            ?>
                            <div id="rtb-booking-form-fields">
                                <?php echo $this->print_modal(); ?>
                            </div>

                            <button type="submit" class="button button-primary">
                                <?php _e( 'Add Booking', 'restaurant-reservations' ); ?>
                            </button>
                            <a href="#" class="button" id="rtb-cancel-booking-modal">
                                <?php _e( 'Cancel', 'restaurant-reservations' ); ?>
                            </a>
                            <div class="action-status">
                                <span class="spinner loading"></span>
                                <span class="dashicons dashicons-no-alt error"></span>
                                <span class="dashicons dashicons-yes success"></span>
                            </div>
                        </form>
                    </div>
                </div>
            

            </body>
            <?php
	}
}
}
?>