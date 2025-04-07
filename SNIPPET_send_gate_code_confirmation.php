<?php
/*
 * This code is avaliable and stored in a repo where the original gatecode plugin is saved. In future we will get the email finctionality integrated into latepoint smoothly :D 
 * 
 */

function is_gate_code_plugin_available(){
	if(!class_exists('LatePoint_Gate_Codes')){
		return false;
	}
	if(!function_exists('get_gate_code')){
		return false;
	}
	
	return true;
}

function get_user_email($customer_id){
	//return user email for gatecode confirmation email 
	$customer = new OsCustomerModel($customer_id);
	//error_log("CUSTOMER_EMAIL: ".$customer->email);
	return $customer->email;
}

function get_agent_display_name($agent_id){
	//return agent display name for email field name display

	$agent = new OsAgentModel($agent_id);
	//error_log("AGENT_NAME: ".$agent->display_name);
	return $agent->display_name;
}

function formatdate_bst($date){
	// Set your default timezone to UTC
	date_default_timezone_set('UTC');

	// Create a DateTime object for the current date and time
	$dateTime = new DateTime($date);

	// Check if the current date is within BST (last Sunday in March to last Sunday in October)
	if ($dateTime >= new DateTime('last sunday of march') && $dateTime <= new DateTime('last sunday of october')) {
		// If it's within BST, adjust the timezone to Europe/London (BST)
		$dateTime->setTimezone(new DateTimeZone('Europe/London'));
	}

	// Format and display the adjusted date and time
	//echo $dateTime->format('Y-m-d H:i:s');
	
	return $dateTime;
}

function send_gate_code($booking){
	//get user id
	$to = get_user_email($booking->customer_id);
	//$to = 'admin@wallacedevelopment.co.uk';

	//adjust time to match GMT timezone
	$date = formatdate_bst($booking->start_datetime_utc);
	
	//simple email subject
	$subject = 'LTDR - Booking confirmation '.$date->format("d/m/y"). " : #".$booking->booking_code;
	

    if(is_gate_code_plugin_available()){
        try {
            //get gate code from function
            $plugin = LatePoint_Gate_Codes();
            $gate_code = $plugin->get_gate_code($booking->agent_id, $booking->start_datetime_utc);
            if($gate_code == "#ERR" || empty($gate_code) ){
                $gate_code = "Code error: You can now check your My Account page within 2 days of your booking to reveal code.";     
                error_log('LATEPOINT_GATECODES: Email template, getcode error. ' . $booking->booking_code);
            }
        }catch (Exception $e) {
            $gate_code = "Code error: You can now check your My Account page within 2 days of your booking to reveal code.";     
            error_log('LATEPOINT_GATECODES: Gatecode exception ' . $e->getMessage());
        }
    } else {
        $gate_code = "Code error: You can now check your My Account page within 2 days of your booking to reveal code.";
        error_log('LATEPOINT_GATECODES: Gatecode plugin not active');
    }

	//body of email
	//add service name to email confirmation
	$body = email_template(get_agent_display_name($booking->agent_id), $date, $gate_code, $booking->service_id);
		
	//email headers
	$headers = array('Content-Type: text/html; charset=UTF-8');
	
	//send mail
	//$check = wp_mail( $to, $subject,'test'.$gate_code, $headers );
	$check = wp_mail( $to, $subject, $body, $headers );

    if(!check) { 
        error_log('LATEPOINT_GATECODES: Failed to send email for booking' . $booking->booking_code);
    }
}

//tag onto action hook thats generated after the booking has been confirmed
add_action('latepoint_booking_created', 'send_gate_code');

