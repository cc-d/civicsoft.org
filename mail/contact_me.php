<?php
// Check for empty fields
if(empty($_POST['name'])  		||
   empty($_POST['email']) 		||
   empty($_POST['phone']) 		||
   empty($_POST['message'])	||
   !filter_var($_POST['email'],FILTER_VALIDATE_EMAIL))
   {
	echo "No arguments Provided!";
	return false;
   }
	
$name = strip_tags(htmlspecialchars($_POST['name']));
$email_address = strip_tags(htmlspecialchars($_POST['email']));
$phone = strip_tags(htmlspecialchars($_POST['phone']));
$message = strip_tags(htmlspecialchars($_POST['message']));

// I modified it so it doesn't actually send email, it instead writes messages to the messages/ direcotry.
// The messages file records the form fields and timestamp in json.

$messages_file = '../messages/messages.json';

if (!(file_exists($messages_file))) {
    $new_file = True;
    $fh = fopen($messages_file, 'w') or die ('Unable to fopen messages.json with mode w for creation.');
    // Smallest possible valid json.
    fwrite($fh, '{}');
    fclose($fh);
} else {
    $new_file = False;
}
$message_array = array('time' => time(), 'fields' => array('name' => $name, 'email' => $email_address, 'phone' => $phone, 'message' => $message));

if ($new_file) {
    $message_json = json_encode(array($message_array));
    $fh = fopen($messages_file, 'r+') or die ('Unable to open the newly created json messages file.');
    fwrite($fh, $message_json);
    fclose($fh);
} else {
    $message_json = json_encode($message_array);
    $file_array = json_decode(file_get_contents($messages_file)) or die ('Could not read/decode message.json');
    
    # Basic rate limiting, I don't want thousands of messages to be written a second, or duplicate messages.
    # Check past 10 messages for duplicates.
    $message_count = count($file_array);
    if ($message_count < 10) {
        $index_start = 0;
    } else {
        $index_start = $message_count - 10 - 1;
    }
    $index_end = $message_count - 1;

    $last_records = array();
    $current_time = time();

    for ($i=$index_start; $i<$index_end; $i++) {
        # Check the last 10 records to see if the message is a duplicate.
        $current_message = $file_array[$i]->{'fields'}->{'message'};
        array_push($last_records, $current_message);
        
        # Make sure we are not receiving messages too frequently. In this case, make sure messages are two seconds apart.
        if ($current_time - $file_array[$i]->{'time'} < 2) {
            die('Sorry, we are currently experiecing too many contact requests. Pelase try again later.');
        }
    }

    if (in_array($message, $last_records)) {
        die('Duplicate message.');
    }

    array_push($file_array, $message_array);
    $fh = fopen($messages_file, 'r+') or die ('Unable to open message file for writing new json array.');
    fwrite($fh, json_encode($file_array));
    fclose($fh);
}

// Create the email and send the message
/*
$to = 'example@test.com'; // Add your email address inbetween the '' replacing yourname@yourdomain.com - This is where the form will send a message to.
$email_subject = "Website Contact Form:  $name";
$email_body = "You have received a new message from your website contact form.\n\n"."Here are the details:\n\nName: $name\n\nEmail: $email_address\n\nPhone: $phone\n\nMessage:\n$message";
$headers = "From: noreply@yourdomain.com\n"; // This is the email address the generated message will be from. We recommend using something like noreply@yourdomain.com.
$headers .= "Reply-To: $email_address";	
mail($to,$email_subject,$email_body,$headers);
*/

return true;			
?>
