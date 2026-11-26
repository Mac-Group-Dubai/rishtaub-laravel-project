<?php

return [
    // Where the website lives. Used to send people back after they verify their email.
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:8080'),

    // true  = like the live site: register sends a verification email, no token until the link is clicked.
    // false = register verifies the email itself and returns a token (handy for local testing and Postman).
    'require_email_verification' => (bool) env('REQUIRE_EMAIL_VERIFICATION', true),
];
