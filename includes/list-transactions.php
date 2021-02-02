<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( "manage_options" ) ) {
	wp_die( __( 'You do not have sufficient permissions to access this page.', 'payro24-elementor' ) );
}

global $wpdb;
$pagenum    = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;
$limit      = 10;
$offset     = ( $pagenum - 1 ) * $limit;
$table_name = $wpdb->prefix . payro24_Elementor_Extension::payro24_TABLE_NAME;

$transactions = $wpdb->get_results( "SELECT * FROM $table_name  ORDER BY $table_name.id DESC LIMIT $offset, $limit", ARRAY_A );
$total        = $wpdb->get_var( "SELECT COUNT($table_name.id) FROM $table_name" );

$currency = get_option( 'elementor_payro24_currency' );
?>
<div class="wrap">
    <h2><?php _e( 'Forms Transactions', 'payro24-elementor' ) ?></h2>
    <table class="widefat post fixed" cellspacing="0">
        <thead>
        <tr>
            <th><?php _e( 'Form Name', 'payro24-elementor' ) ?></th>
            <th><?php _e( 'Date', 'payro24-elementor' ) ?></th>
            <th><?php _e( 'Email', 'payro24-elementor' ) ?></th>
            <th><?php _e( 'Amount', 'payro24-elementor' ) ?></th>
            <th><?php _e( 'Transaction ID', 'payro24-elementor' ) ?></th>
            <th><?php _e( 'Tracking Code', 'payro24-elementor' ) ?></th>
            <th><?php _e( 'Payment Status', 'payro24-elementor' ) ?></th>
            <th><?php _e( 'Payment Log', 'payro24-elementor' ) ?></th>
        </tr>
        </thead>
        <tfoot>
        <tr>
            <th><?php _e( 'Form Name', 'payro24-elementor' ) ?></th>
            <th><?php _e( 'Date', 'payro24-elementor' ) ?></th>
            <th><?php _e( 'Email', 'payro24-elementor' ) ?></th>
            <th><?php _e( 'Amount', 'payro24-elementor' ) ?></th>
            <th><?php _e( 'Transaction ID', 'payro24-elementor' ) ?></th>
            <th><?php _e( 'Tracking Code', 'payro24-elementor' ) ?></th>
            <th><?php _e( 'Payment Status', 'payro24-elementor' ) ?></th>
            <th><?php _e( 'Payment Log', 'payro24-elementor' ) ?></th>
        </tr>
        </tfoot>
        <tbody>
		<?php
		if ( count( $transactions ) == 0 ) :
			?>
            <tr class="alternate author-self status-publish iedit" valign="top">
                <td colspan="6"><?php _e( 'There are not any transactions.', 'payro24-elementor' ) ?></td>
            </tr>
		<?php
		else:
			foreach ( $transactions as $transaction ):
				?>
                <tr class="alternate author-self status-publish iedit"
                    valign="top">
                    <td> <?php echo get_the_title( $transaction['post_id'] ) ?> </td>
                    <td style="direction: ltr; text-align: right;">
						<?php echo date( "Y-m-d H:i:s", $transaction['created_at'] ); ?>
                    </td>

                    <td> <?php echo $transaction['email'] ?></td>
                    <td>
                        <?php
                        echo ($currency == 'rial' ? $transaction['amount'] : $transaction['amount'] / 10) . " ";
                        _e( $currency == 'rial' ? 'Rial' : 'Toman', 'payro24-elementor' );
                        ?>
                    </td>
                    <td> <?php echo $transaction['trans_id'] ?></td>
                    <td> <?php echo $transaction['track_id'] ?></td>
                    <td>
						<?php if ( $transaction['status'] == "completed" ): ?>
                            <b style="color: #388e3c"><?php _e( 'completed', 'payro24-elementor' ) ?></b>
						<?php elseif ( $transaction['status'] == "failed" ): ?>
                            <b style="color: #f00"><?php _e( 'failed', 'payro24-elementor' ) ?></b>
						<?php elseif ( $transaction['status'] == "bank" ): ?>
                            <b style="color: #ff8f00"><?php _e( 'pending payment', 'payro24-elementor' ) ?></b>
						<?php endif; ?>
                    </td>
                    <td> <?php echo $transaction['log'] ?></td>
                </tr>
			<?php
			endforeach;
		endif;
		?>
        </tbody>
    </table>
    <br>
	<?php
	$page_links = paginate_links( array(
		'base'      => add_query_arg( 'pagenum', '%#%' ),
		'format'    => '',
		'prev_text' => __( '&laquo;', 'payro24-elementor' ),
		'next_text' => __( '&raquo;', 'payro24-elementor' ),
		'total'     => ceil( $total / $limit ),
		'current'   => $pagenum,
	) );

	if ( $page_links ):
		?>
        <center>
            <div class="tablenav">
                <div class="tablenav-pages" style="float:none; margin: 1em 0">
					<?php echo $page_links ?>
                </div>
            </div>
        </center>
	<?php endif; ?>
    <br>
    <hr>
    <style>
        pre {
            max-height: 150px;
            overflow: auto;
        }
    </style>
</div>
