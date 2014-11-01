<?php 

// This page is used to make a purchase.

// Every page needs the configuration file:
require('includes/config.inc.php');

// Uses sessions to test for duplicate submissions:
session_start();

// Set the Stripe key:
// Uses STRIPE_PUBLIC_KEY from the config file.
echo '<script type="text/javascript">Stripe.setPublishableKey("' . STRIPE_PUBLIC_KEY . '");</script>';

// Check for a form submission:
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	
	// Stores errors:
	$errors = array();
	
	// Need a payment token:
	if (isset($_POST['stripeToken'])) {
        
        echo "The POST is $_POST";
        var_dump($_POST);
		
		$token = $_POST['stripeToken'];
        $email  = $_POST['emailAddress'];
        $nameOnCard = $_POST['Name'];
		
		// Check for a duplicate submission, just in case:
		// Uses sessions, you could use a cookie instead.
		if (isset($_SESSION['token']) && ($_SESSION['token'] == $token)) {
			$errors['token'] = 'You have apparently resubmitted the form. Please do not do that.';
		} else { // New submission.
			$_SESSION['token'] = $token;
		}		
		
	} else {
		$errors['token'] = 'The order cannot be processed. Please make sure you have JavaScript enabled and try again.';
	}
	
	// Set the order amount somehow:
	$amount = 2091; // $20, in cents

	// Validate other form data!

	// If no errors, process the order:
	if (empty($errors)) {
		
		// create the charge on Stripe's servers - this will charge the user's card
		try {
			
			// Include the Stripe library:
			require_once('includes/stripe/lib/Stripe.php');

			// set your secret key: remember to change this to your live secret key in production
			// see your keys here https://manage.stripe.com/account
			Stripe::setApiKey(STRIPE_PRIVATE_KEY);
            $customer = Stripe_Customer::create(array(
                "email" => $email,
                "card" => $token, // obtained with Stripe.js
                "description" => $nameOnCard
                )
            );
            
            if($customer->id != null)
            {
                // Charge the order:
                $charge = Stripe_Charge::create(array(
                    "customer" => $customer->id, // Linking to Customer
                    "amount" => $amount, // amount in cents, again
                    "currency" => "eur",
                  //  "card" => $token,
                    "description" => $nameOnCard
                    )
                );

                // Check that it was paid:
                if ($charge->paid == true) {

                    // Store the order in the database.
                    // Send the email.
                    // Celebrate!
                         echo "<div class='alert'><h4>Transaction completed! Thanks for shopping :)</h4></div>";
                } 
                else 
                { // Charge was not paid!	
                    echo "<div class='alert'><h4>Payment System Error!</h4>Your payment could NOT be processed (i.e., you have not been charged) because the payment system rejected the transaction. You can try again or use another card.</div>";
                }
            }
            else { // Customer was not created!	
                echo "<div class='alert'><h4>Could not create Customer! Something is wrong here..</h4></div>";
            }			
		} catch (Stripe_CardError $e) {
		    // Card was declined.
			$e_json = $e->getJsonBody();
			$err = $e_json['error'];
			$errors['stripe'] = $err['message'];
		} catch (Stripe_ApiConnectionError $e) {
		    // Network problem, perhaps try again.
		} catch (Stripe_InvalidRequestError $e) {
		    // You screwed up in your programming. Shouldn't happen!
		} catch (Stripe_ApiError $e) {
		    // Stripe's servers are down!
		} catch (Stripe_CardError $e) {
		    // Something else that's not the customer's fault.
		}

	} // A user form submission error occurred, handled below.
	//require-validation data-cc-on-file="false" 
} // Form submission.

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>United For Hope Christmas Fundraiser - Gift Light</title>
	<script type="text/javascript" src="https://js.stripe.com/v2/"></script>
    <script src="js/jquery-1.11.0.js"></script>
    <script src="js/buy.js"></script>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        .submit-button {
          margin-top: 10px;
        }
    </style>
</head>
<body>
    <h2>Charge successful</h2>
            <?php // Show PHP errors, if they exist:
                if (isset($errors) && !empty($errors) && is_array($errors)) {
                    echo '<div class="alert alert-error"><h4>Error!</h4>The following error(s) occurred:<ul>';
                    foreach ($errors as $e) {
                        echo "<li>$e</li>";
                    }
                    echo '</ul></div>';	
            }?>
</body>