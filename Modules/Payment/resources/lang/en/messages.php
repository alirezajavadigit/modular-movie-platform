<?php

return [
    'validation_failed' => 'Validation failed.',

    'index'        => 'Payments retrieved successfully.',
    'show'         => 'Payment retrieved successfully.',
    'created'      => 'Payment initiated successfully.',
    'deleted'      => 'Payment deleted successfully.',
    'force_deleted' => 'Payment permanently deleted.',
    'trashed'      => 'Trashed payments retrieved successfully.',
    'restored'     => 'Payment restored successfully.',
    'verified'     => 'Payment verified successfully.',

    'transaction_mismatch' => 'The transaction does not belong to this payment.',
    'transaction_missing'  => 'This payment has no gateway transaction to verify.',
    'not_confirmed'        => 'The payment gateway has not confirmed this payment.',
    'not_settled'          => 'The payment could not be confirmed right now. Please try again later.',
    'payer_action_required' => 'The payer must complete or change the payment at the gateway before it can be confirmed.',
    'callback_not_found'   => 'No matching payment was found for this callback.',
];
