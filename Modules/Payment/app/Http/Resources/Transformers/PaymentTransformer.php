<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Resources\Transformers;

use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use League\Fractal\TransformerAbstract;
use Modules\Payment\Models\Payment;

class PaymentTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'user',
    ];

    public function transform(Payment $payment): array
    {
        return [
            'id'             => $payment->id,
            'payable_id'     => $payment->payable_id,
            'payable_type'   => $payment->payable_type,
            'user_id'        => $payment->user_id,
            'amount'         => $payment->amount,
            'driver'         => $payment->driver,
            'transaction_id' => $payment->transaction_id,
            'status'         => $payment->status->value,
            'status_label'   => $payment->status->label(),
            'created_at'     => $payment->created_at?->toIso8601String(),
            'updated_at'     => $payment->updated_at?->toIso8601String(),
            'deleted_at'     => $payment->deleted_at?->toIso8601String(),
        ];
    }

    public function includeUser(Payment $payment): Item|NullResource
    {
        $user = $payment->user;

        if (!$user) {
            return $this->null();
        }

        return $this->item($user, function ($user) {
            return [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ];
        });
    }
}
