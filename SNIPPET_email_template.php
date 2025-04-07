<?php 
/*
 * This code is avaliable and stored in a repo where the original gatecode plugin is saved. In future we will get the email finctionality integrated into latepoint smoothly :D 
 * 
 */
function email_template($field_name, $date, $gate_code, $num_dogs) {
    // Start with responsive email template
    $body = '<!doctype html>
<html>
<head>
    <meta name="viewport" content="width=device-width" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Let The Dogs Run - Booking Confirmation</title>
    <style>
        /* Base styles */
        body {
            background-color: #f6f6f6 !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            font-size: 16px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
            color: #333333;
        }
        
        /* Layout */
        .body {
            background-color: #f6f6f6;
            width: 100%;
        }
        
        .container {
            display: block;
            margin: 0 auto !important;
            max-width: 600px;
            padding: 10px;
            width: 600px;
        }
        
        .content {
            box-sizing: border-box;
            display: block;
            margin: 0 auto;
            max-width: 600px;
            padding: 10px;
        }
        
        .main {
            background: #ffffff;
            border-radius: 8px;
            width: 100%;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .wrapper {
            box-sizing: border-box;
            padding: 30px;
        }
        
        /* Typography */
        h1 {
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            margin-top: 15px;
            margin-bottom: 20px;
        }
        
        p {
            margin: 0;
            margin-bottom: 15px;
        }
        
        /* Gate Code Section - Highlighted */
        .gate-code-section {
            background-color: #f7f9fc;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            text-align: center;
            border: 2px dashed #2d54de;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }
        
        .gate-code-label {
            font-size: 14px;
            font-weight: bold;
            color: #6d6d6d;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 10px;
        }
        
        .gate-code-value {
            font-size: 36px;
            font-weight: 700;
            color: #2d54de;
            line-height: 1.2;
            letter-spacing: 1px;
        }
        
        /* Booking details table */
        .booking-details {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 20px;
        }
        
        .booking-details th {
            text-align: left;
            padding: 12px 10px;
            background-color: #f7f9fc;
            border-top: 1px solid #eaeaea;
            border-bottom: 1px solid #eaeaea;
            font-weight: 600;
            color: #6d6d6d;
        }
        
        .booking-details td {
            padding: 12px 10px;
            border-bottom: 1px solid #eaeaea;
        }
        
        /* Buttons */
        .button {
            background-color: #2d54de;
            border-radius: 5px;
            color: #ffffff !important;
            display: inline-block;
            font-size: 16px;
            font-weight: 600;
            margin: 0;
            padding: 12px 25px;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
        }
        
        .button-review {
            background-color: #34b233;
        }
        
        .button:hover {
            background-color: #1e3cb3;
        }
        
        .button-review:hover {
            background-color: #2a9229;
        }
        
        /* Social links */
        .social-links {
            text-align: center;
            padding: 20px 0;
        }
        
        .social-links a {
            display: inline-block;
            margin: 0 10px;
        }
        
        .social-links img {
            width: 32px;
            height: 32px;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            font-size: 14px;
            color: #999999;
            padding: 20px 0;
        }
        
        /* Mobile responsiveness */
        @media only screen and (max-width: 620px) {
            .booking-details td {
                text-align: center;
            }
            
            .container {
                width: 100% !important;
                padding: 10px !important;
            }
            
            .wrapper {
                padding: 15px !important;
            }
            
            h1 {
                font-size: 24px !important;
            }
            
            .gate-code-value {
                font-size: 28px !important;
            }
            
            .booking-details th,
            .booking-details td {
                display: block;
                width: 100%;
                box-sizing: border-box;
            }
            
            .booking-details th {
                text-align: center;
                border-bottom: none;
            }
            
            .button {
                display: block;
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>
<body>
    <span style="display: none; max-height: 0px; overflow: hidden;">Your booking details for '.$date->format("d/m/y").'</span>
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="body" width="100%">
        <tr>
            <td>&nbsp;</td>
            <td class="container">
                <div class="content">
                    <!-- HEADER WITH LOGO -->
                    <table role="presentation" class="main" width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="wrapper" align="center">
                                <img src="https://staging.letthedogsrun.uk/wp-content/uploads/2023/07/main-desktop-logo-1024x575.png" alt="Let The Dogs Run" style="max-width: 280px; height: auto; margin: 0 auto;" />
                                <h1>Welcome to Let The Dogs Run!</h1>
                            </td>
                        </tr>
                    </table>
                    
                    <!-- MAIN CONTENT AREA -->
                    <table role="presentation" class="main" width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="wrapper">
                                <p>We are delighted that you have made a booking with us! Please make sure you arrive and depart on time. Your booking details are below:</p>
                                
                                <!-- BOOKING DETAILS TABLE -->
                                <table class="booking-details" width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <th width="40%">Field</th>
                                        <td width="60%">'.$field_name.'</td>
                                    </tr>
                                    <tr>
                                        <th>Date</th>
                                        <td>'.$date->format("l, d F Y").'</td>
                                    </tr>
                                    <tr>
                                        <th>Time</th>
                                        <td>'.$date->format("H:i").' - '.$date->add(new DateInterval("PT1H"))->format("H:i").'</td>
                                    </tr>
                                    <tr>
                                        <th>Number of Dogs</th>
                                        <td>'.$num_dogs.'</td>
                                    </tr>
                                </table>
                                
                                <!-- GATE CODE SECTION (HIGHLIGHTED) -->
                                <div class="gate-code-section">
                                    <div class="gate-code-label">GATE CODE</div>
                                    <div class="gate-code-value">'.$gate_code.'</div>
                                </div>
                                
                                <p>All booking details can be found on our website, under the <a href="https://www.letthedogsrun.uk/my-account">My account</a> page</p>
                                
                                <p>At Let The Dogs Run, our ethos is to offer a safe and enriching environment for owners and dogs alike to exercise and enjoy each other\'s company.</p>
                                
                                <p>Any problems, queries or concerns please <a href="https://www.letthedogsrun.uk/contact-us">contact Wendy</a> on <a href="tel:07950020820">07950020820</a>.</p>
                                
                                <p>We thank you in advance for respecting our facility and we do hope you all have a fantastic time at Let The Dogs Run!</p>
                                
                                <p>Kind regards,<br />Wendy Wallace</p>
                                
                                <!-- MY ACCOUNT BUTTON -->
                                <div style="text-align: center; margin: 30px 0;">
                                    <a href="https://staging.letthedogsrun.uk/my-account" class="button">View Your Booking</a>
                                </div>
                                
                                <!-- REVIEW SECTION -->
                                <div style="background-color: #f7f9fc; padding: 20px; border-radius: 8px; text-align: center; margin: 30px 0;">
                                    <p style="font-weight: bold; font-size: 18px; margin-bottom: 15px;">Enjoyed your visit?</p>
                                    <p style="margin-bottom: 20px;">Help others discover Let The Dogs Run by leaving us a review!</p>
                                    <a href="https://g.page/r/CRuxQIT1ruIFEAI/review" class="button button-review">Leave a Review</a>
                                </div>
                            </td>
                        </tr>
                    </table>
                    
                    <!-- USEFUL LINKS SECTION -->
                    <table role="presentation" class="main" width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="wrapper" style="text-align: center;">
                                <h3 style="color: #2d54de; margin-bottom: 15px;">Useful Information</h3>
                                <p style="margin-bottom: 20px;">
                                    <a href="https://staging.letthedogsrun.uk/home/introduction" style="margin: 0 10px;">Introduction</a> | 
                                    <a href="https://staging.letthedogsrun.uk/about-us/terms-and-conditions/" style="margin: 0 10px;">Terms and Conditions</a> | 
                                    <a href="https://staging.letthedogsrun.uk/park-map/" style="margin: 0 10px;">Park Map</a>
                                </p>
                                
                                <!-- SOCIAL LINKS -->
                                <div class="social-links">
                                    <a href="https://www.instagram.com/letthedogsrun/">
                                        <img src="https://staging.letthedogsrun.uk/wp-content/uploads/2020/10/060-instagram.png" alt="Instagram" />
                                    </a>
                                    <a href="https://www.facebook.com/letthedogsrunscotland">
                                        <img src="https://staging.letthedogsrun.uk/wp-content/uploads/2020/10/049-facebook.png" alt="Facebook" />
                                    </a>
                                    <a href="https://wa.me/447950020820">
                                        <img src="https://staging.letthedogsrun.uk/wp-content/uploads/2021/01/whatsapp.png" alt="WhatsApp" />
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </table>
                    
                    <!-- FOOTER -->
                    <div class="footer">
                        <p>Let the Dogs Run, Station Corner, Tulibardine, Auchterarder, Perthshire, PH3 1NJ</p>
                        <p>&copy; '.date('Y').' Let The Dogs Run. All rights reserved.</p>
                    </div>
                </div>
            </td>
            <td>&nbsp;</td>
        </tr>
    </table>
</body>
</html>';

    return $body;
}
