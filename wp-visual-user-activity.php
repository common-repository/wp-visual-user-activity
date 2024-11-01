<?php
/* 
* +--------------------------------------------------------------------------+
* | Copyright (c) 2009 NixonMcInnes                                          |
* +--------------------------------------------------------------------------+
* | This program is free software; you can redistribute it and/or modify     |
* | it under the terms of the GNU General Public License as published by     |
* | the Free Software Foundation; either version 2 of the License, or        |
* | (at your option) any later version.                                      |
* |                                                                          |
* | This program is distributed in the hope that it will be useful,          |
* | but WITHOUT ANY WARRANTY; without even the implied warranty of           |
* | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            |
* | GNU General Public License for more details.                             |
* |                                                                          |
* | You should have received a copy of the GNU General Public License        |
* | along with this program; if not, write to the Free Software              |
* | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA |
* +--------------------------------------------------------------------------+
*/

/**
* Plugin Name: WP Visual User Activity
* Plugin URI: http://www.nixonmcinnes.co.uk/people/telmo/
* Description: Visually display the user activity in a time period.
For version 1.0 it answers the question: "How many users made 1, 2 and 3 comments in a time period?".
It shows the numbers of comments (1..9,10+ by default) and how many users made those numbers of comments. Plots a 2D pie chart.
* Version: 1.0
*
* Author: Telmo Carlos, NixonMcInnes
* Author URI: http://www.nixonmcinnes.co.uk
*/
class nm_visual_user_activity {

    private $nm_visual_ux_from_date;
    private $nm_visual_ux_to_date;
    private $nm_visual_ux_top_limit;
    private $nm_visual_ux_type;

    private $nm_visual_ux_supported_types = array( "comments", "posts" );

    private $defaults = array();
    
    public static $languages = array('zh'=>'Chinese', 'da'=>'Danish', 'nl'=>'Dutch', 'en'=>'English', 'fi'=>'Finnish', 'fr'=>'French', 'de'=>'German', 'he'=>'Hebrew', 'it'=>'Italian', 'ja'=>'Japanese', 'ko'=>'Korean', 'no'=>'Norwegian', 'pl'=>'Polish', 'pt'=>'Portugese', 'ru'=>'Russian', 'es'=>'Spanish', 'sv'=>'Swedish');
    
    private $plugin_url;

    /**
    * Adds WP filter so we can append the AddThis button to post content.
    */
    function nm_visual_user_activity() {
        
        $this->defaults = array (
            "nm_visual_ux_top_limit" => 10,
            "nm_visual_ux_type" => $this->nm_visual_ux_supported_types[0]
        );
        
        add_action('admin_head', array(&$this, 'admin_head'));
        add_action('admin_footer', array(&$this, 'admin_footer'));
        add_filter('admin_menu', array(&$this, 'admin_menu'));

        add_option('nm_visual_ux_from_date');
        add_option('nm_visual_ux_to_date');
        add_option('nm_visual_ux_top_limit', '10');
        add_option('nm_visual_ux_type', 'comments'); /// Can be comments or posts for version 1.0
        add_option('nm_visual_ux_language', 'en');

        $this->nm_visual_ux_from_date = get_option('nm_visual_ux_from_date');
        $this->nm_visual_ux_to_date   = get_option('nm_visual_ux_to_date');
        $this->nm_visual_ux_top_limit = get_option('nm_visual_ux_top_limit');
        $this->nm_visual_ux_type      = get_option('nm_visual_ux_type');
        $this->nm_visual_ux_language  = get_option('nm_visual_ux_language');
        
        $this->plugin_url = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
        
    }
    
    public function admin_head() {
        ?>
            
            <script type="text/javascript" src="<?php echo $this->plugin_url; ?>js/jquery-1.3.2.min.js"></script>
            <script type="text/javascript" src="<?php echo $this->plugin_url; ?>js/jquery.datePicker/date.js"></script>
            <script type="text/javascript" src="<?php echo $this->plugin_url; ?>js/jquery.datePicker/jquery.datePicker.min-2.1.2.js"></script>
            <link href="<?php echo $this->plugin_url; ?>js/jquery.datePicker/datePicker.css" media="screen" type="text/css" rel="stylesheet"/>
            
            <script type="text/javascript" src="<?php echo $this->plugin_url; ?>js/extjs/ext-base.js"></script>
            <script type="text/javascript" src="<?php echo $this->plugin_url; ?>js/extjs/ext-all.js"></script>
            
        <?php
    }

    public function admin_footer() {
        ?>
            
            <script type="text/javascript">
                
                var cal;
                var $this;
                
                /// Part of "hiding the calendar on mouse out"
                var checkForMouseout = function(event) {
                    var el = event.target;
                    
                    while (true){
                        if (el == cal) {
                            return true;
                        } else if (el == document) {
                            $this.dpClose();
                            return false;
                        } else {
                            el = $(el).parent()[0];
                        }
                    }
                };
                
                $(document).ready(function() {
                    /// Change format
                    Date.format = 'yyyy/mm/dd';
                    
                    $('#nm_visual_ux_from_date').datePicker({
                        startDate:"1996/01/01",
                        endDate:"<?php echo ($this->nm_visual_ux_to_date == "")? "":$this->nm_visual_ux_to_date; ?>"
                    }).bind( /// Part of "set the lower limit of the 'to' calendar"
                        'dpClosed',
                        function(e, selectedDates){
                            var d = selectedDates[0];
                            if (d) {
                                d = new Date(d);
                                $('#nm_visual_ux_to_date').dpSetStartDate(d.asString());
                            }
                        }
                    ).bind( /// Part of "set the lower limit of the 'to' calendar"
                        'dpDisplayed',
                        function(e, datePickerDiv){
                            cal = datePickerDiv;
                            $this = $(this);
                            $(document).bind(
                                'mouseover',
                                checkForMouseout
                            );
                        }
                    );
                    
                    $('#nm_visual_ux_to_date').datePicker({
                        startDate:"<?php echo ($this->nm_visual_ux_from_date == "")? "1996/01/01":$this->nm_visual_ux_from_date; ?>"
                    }).bind( /// Part of "set the higher limit of the 'from' calendar"
                        'dpClosed',
                        function(e, selectedDates){
                            var d = selectedDates[0];
                            if (d) {
                                d = new Date(d);
                                $('#nm_visual_ux_from_date').dpSetEndDate(d.asString());
                            }
                        }
                    );
                    
                    /// Part of "hiding the calendar on mouse out"
                    $('.nm-date-pick')
                        .datePicker()
                        .bind(
                            'dpDisplayed',
                            function(event, datePickerDiv)
                            {
                                cal = datePickerDiv;
                                $this = $(this);
                                $(document).bind(
                                    'mouseover',
                                    checkForMouseout
                                );
                            }
                        ).bind(
                            'dpClosed',
                            function(event, selected)
                            {
                                $(document).unbind(
                                    'mouseover',
                                    checkForMouseout
                                );
                            }
                        );
                    
                });
                
            </script>
            
            
            <script type="text/javascript">
                /*!
                 * Ext JS Library 3.0+
                 * Copyright(c) 2006-2009 Ext JS, LLC
                 * licensing@extjs.com
                 * http://www.extjs.com/license
                 */
                Ext.chart.Chart.CHART_URL = '<?php echo $this->plugin_url; ?>js/extjs/charts.swf';
                
                Ext.onReady(function(){
                    /**
                     * Pie to show number of users per number of comments
                     */
                    var users_per_comments = new Ext.data.JsonStore({
                        fields: ['numbers', 'users'],
                        /*data: [
                            { numbers: '1', users: 690 },{ numbers: '2', users: 189 },{ numbers: '3', users: 63 },{ numbers: '4', users: 43 },{ numbers: '5', users: 153 }
                        ]*/
                        
                        data: [
                            
                            <?php
                            $query = "
                                SELECT number, COUNT(comment_author_email) as users FROM (
                                  SELECT comment_author_email, COUNT(comment_id) AS number FROM `wp_comments` AS c
                                  WHERE comment_approved = 1";
                            if( $this->nm_visual_ux_from_date != "" ) {
                                $query .= "
                                  AND '$this->nm_visual_ux_from_date' <= comment_date";
                            }
                            if( $this->nm_visual_ux_to_date != "" ) {
                                $query .= "
                                  AND comment_date <= '$this->nm_visual_ux_to_date 23:59:59'";
                            }
                            $query .= "
                                  GROUP BY comment_author_email
                                ) AS t
                                GROUP BY number
                                ORDER BY number;
                            ";
                            
                            ///echo $query; exit;
                            
                            global $wpdb;
                            $data = $wpdb->get_results( $query );
                            if( !$data ) {
                                $err_messages[] = "No data available for that period";
                            } else {
                                ///print_r( $data ); exit;
                                
                                /**
                                 * Prepare data
                                 */
                                $total_number = 0;
                                $total_users  = 0;
                                foreach( $data as $row ) {
                                    /**
                                     * Format data for the chart
                                     */
                                    $total_number += $row->number;
                                    $total_users  += $row->users;
                                    if( $this->nm_visual_ux_top_limit != "" ) {
                                        if( $row->number < $this->nm_visual_ux_top_limit ) {
                                            $chart_data[$row->number] = $row;
                                        } else {
                                            if(!isset( $chart_data[$this->nm_visual_ux_top_limit] )) {
                                                $chart_data[$this->nm_visual_ux_top_limit] = $row;
                                            }
                                            $chart_data[$this->nm_visual_ux_top_limit]->users += $row->users;
                                        }
                                    }
                                }
                                $first = true; 
                                foreach( $chart_data as $row ) {
                                    if( $first ) {
                                        $first = false;
                                    } else {
                                        echo ",";
                                    }
                                    echo "{ numbers: '$row->number', users: $row->users, label: '123' }\n";
                                }
                            }
                            ?>
                        ]
                    });
                    
                    new Ext.Panel({
                        width: 400,
                        height: 400,
                        title: '<h3>Comment activity: users per number of comments</h3>',
                        renderTo: 'pie2d-extjs-users-per-comments',
                        items: {
                            store: users_per_comments,
                            xtype: 'piechart',
                            dataField: 'users',
                            categoryField: 'numbers',
                            textField: 'label',
                            //extra styles get applied to the chart defaults
                            extraStyle:
                            {
                                legend:
                                {
                                    display: 'bottom',
                                    padding: 5,
                                    font:
                                    {
                                        family: 'Tahoma',
                                        size: 13
                                    }
                                }
                            }
                        }
                    });
                    
                    
                    /**
                     * Pie to show number of new users in period and existing users (past only)
                     */
                    var new_vs_existing_users = new Ext.data.JsonStore({
                        fields: ['type', 'users'],
                        
                        data: [
                            
                            <?php
                            $query = "
                                SELECT DISTINCT comment_author_email FROM wp_comments w
                                WHERE comment_approved = 1";
                            if( $this->nm_visual_ux_from_date != "" ) {
                                $query .= "
                                  AND '$this->nm_visual_ux_from_date' <= comment_date";
                            }
                            if( $this->nm_visual_ux_to_date != "" ) {
                                $query .= "
                                  AND comment_date <= '$this->nm_visual_ux_to_date 23:59:59'";
                            }
                            ///echo $query; exit;
                            
                            global $wpdb;
                            $all_users_in_period = $wpdb->get_results( $query );
                            if( !$all_users_in_period ) {
                                $err_messages[] = "No data available for that period";
                                echo 1;
                            } else {
                                
                                $temp = array();
                                if( $all_users_in_period ) foreach( $all_users_in_period as $user ) {
                                    $temp[] = $user->comment_author_email;
                                }
                                $all_users_in_period = $temp;
                                
                                $query = "
                                    SELECT DISTINCT comment_author_email FROM wp_comments w
                                    WHERE comment_approved = 1";
                                if( $this->nm_visual_ux_from_date != "" ) {
                                    $query .= "
                                      AND comment_date < '$this->nm_visual_ux_from_date'";
                                }
                                ///echo $query; exit;
                                
                                $all_users_before_period = $wpdb->get_results( $query );
                                if( !$all_users_before_period ) {
                                    $err_messages[] = "No data available for that period";
                                    echo 1;
                                } else {
                                    
                                    $temp = array();
                                    if( $all_users_before_period ) foreach( $all_users_before_period as $user ) {
                                        $temp[] = $user->comment_author_email;
                                    }
                                    $all_users_before_period = $temp;
                                    
                                    $all_users_in_period_count = count( $all_users_in_period );
                                    
                                    $new_users = array_diff( $all_users_in_period, $all_users_before_period );
                                    
                                    $new_users_count = count( $new_users );
                                    
                                    $existing_users_count = $all_users_in_period_count - $new_users_count;
                                    
                                    echo "
                                        { type: 'new', users: $new_users_count },
                                        { type: 'existing', users: $existing_users_count }
                                    ";
                                }
                            }
                            ?>
                        ]
                    });
                    
                    new Ext.Panel({
                        width: 400,
                        height: 400,
                        title: '<h3>User activity: New users in period versus existing users</h3>',
                        renderTo: 'pie2d-extjs-new-vs-existing-users',
                        items: {
                            store: new_vs_existing_users,
                            xtype: 'piechart',
                            dataField: 'users',
                            categoryField: 'type',
                            //extra styles get applied to the chart defaults
                            extraStyle:
                            {
                                legend:
                                {
                                    display: 'bottom',
                                    padding: 5,
                                    font:
                                    {
                                        family: 'Tahoma',
                                        size: 13
                                    }
                                }
                            }
                        }
                    });
                });                
            </script>
            
        <?php
    }

    /**
     * Show menu in the admin area, under Tools
     */
    public function admin_menu() {
        add_management_page('Visual User Activity Plugin Options', 'Visual User Activity', 8, __FILE__, array(&$this, 'plugin_options'));
    }
	
	/**
	 * Show the admin screen with options and chart
     */
    public function plugin_options() {
        ?>
        <div class="wrap">
            <h2>Visual User Activity Plugin Options</h2>
    
            <form method="post" action="options.php">
            <?php wp_nonce_field('update-options'); ?>
        
            <h3>Options</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e("From date (yyyy/mm/dd):", 'nm_visual_ux_trans_domain' ); ?></th>
                    <td><input type="text" id="nm_visual_ux_from_date" class="nm-date-pick" name="nm_visual_ux_from_date" value="<?php echo $this->nm_visual_ux_from_date; ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e("To date (yyyy/mm/dd):", 'nm_visual_ux_trans_domain' ); ?></th>
                    <td><input type="text" id="nm_visual_ux_to_date" class="nm-date-pick" name="nm_visual_ux_to_date" value="<?php echo $this->nm_visual_ux_to_date; ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e("Top limit:", 'nm_visual_ux_trans_domain' ); ?></th>
                    <td><input type="text" name="nm_visual_ux_top_limit" value="<?php echo $this->nm_visual_ux_top_limit; ?>" /></td>
                </tr>
                <!--tr valign="top">
                    <th scope="row"><?php _e("Type:", 'nm_visual_ux_trans_domain' ); ?></th>
                    <td>
                        <select name="nm_visual_ux_type">
                        <?php
                            $nm_visual_ux_type = $this->nm_visual_ux_type;
                            foreach( $this->nm_visual_ux_supported_types as $nm_visual_ux_supported_type ) {
                                echo "<option value=\"$nm_visual_ux_supported_type\"". ($nm_visual_ux_supported_type == $this->nm_visual_ux_type ? " selected":""). ">" . ucwords($nm_visual_ux_supported_type) . "</option>\n";
                            }
                        ?>
                        </select>
                    </td>
                </tr-->
            </table>
            
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="nm_visual_ux_from_date,nm_visual_ux_to_date,nm_visual_ux_top_limit,nm_visual_ux_type" />
        
            <p class="submit">
            <input type="submit" name="Submit" value="<?php _e('Save Changes') ?>" />
            </p>
        
            <br />
            <br />
            <br />
            
            <?php
            /**
             * Validate the parameters
             */
            $err_messages = array();
            if( $this->nm_visual_ux_from_date == "" ) {
                /// from WHERE condition will not be used
            } elseif(!strtotime($this->nm_visual_ux_from_date)) {
                $err_messages[] = "The 'from' date is not valid";
            }
            if( $this->nm_visual_ux_to_date == "" ) {
                /// to WHERE condition will not be used
            } elseif(!strtotime($this->nm_visual_ux_to_date)) {
                $err_messages[] = "The 'to' date is not valid";
            }
            if( $this->nm_visual_ux_top_limit == "" ) {
                /// top limit will not be used
            } elseif(!is_numeric($this->nm_visual_ux_top_limit)) {
                $this->nm_visual_ux_top_limit = $this->defaults["nm_visual_ux_top_limit"];
            }
            
            
            ?>
            
            <h2>2009 User Activity from '<?php echo $this->nm_visual_ux_from_date; ?>'
                    to '<?php echo $this->nm_visual_ux_to_date; ?>'
                    limit to the top <?php echo $this->nm_visual_ux_top_limit; ?></h2>
            
            <table><tr><td align="center">
                <div id="pie2d-extjs-users-per-comments" class="pie2d-extjs"></div>
            </td>
            <td align="center">
                <div id="pie2d-extjs-new-vs-existing-users" class="pie2d-extjs"></div>
            </tr>
            </table>
            
            </form>
        </div>
            
        <?php
    }
}

// If we're not running in PHP 4, initialize
if (strpos(phpversion(), '4') !== 0) {
    $nm_visual_user_activity &= new nm_visual_user_activity();
}
?>
