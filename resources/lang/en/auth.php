<?php

return [
    'two_factor' => [
        'title' => 'Two-Factor Authentication',
        'code_sent_title' => 'Code sent',
        'code_sent_body' => 'We have sent a 6-digit code to your email.',
        'code_label' => 'Verification code',
        'code_helper' => 'Enter the 6-digit code we sent to your email.',
        'invalid_code_title' => 'Invalid code',
        'invalid_code_body' => 'The entered code is invalid or expired.',
        'code_resent_title' => 'Code resent',
        'code_resent_body' => 'A new code has been sent to your email.',
        'verify_button' => 'Verify',
        'resend_code' => 'Resend code',
        'email' => [
            'code_subject' => 'Your Two-Factor Authentication Code',
            'disable_subject' => 'Two-Factor Authentication Disable Code',
            'greeting' => 'Hello :name!',
            'login_line1' => 'You have attempted to log into your account.',
            'login_line2' => 'To complete the login, please use the following code:',
            'disable_line1' => 'You have requested to disable two-factor authentication.',
            'disable_line2' => 'To disable two-factor authentication, please use the following code:',
            'validity' => 'This code is valid for 10 minutes.',
            'ignore_warning' => 'If you did not request this action, please ignore this email.',
        ],
    ],
];
