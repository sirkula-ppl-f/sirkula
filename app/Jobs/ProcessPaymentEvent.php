<?php

namespace App\Jobs;

use App\Models\PaymentEvent;
use App\Services\Payments\PaymentStatusResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessPaymentEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [5, 15, 60];

    public function __construct(public int $eventId) {}

    public function handle(PaymentStatusResolver $resolver): void
    {
        $event = PaymentEvent::query()->find($this->eventId);

        if ($event !== null) {
            $resolver->process($event);
        }
    }

    public function failed(Throwable $exception): void
    {
        PaymentEvent::query()
            ->whereKey($this->eventId)
            ->where('process_status', PaymentEvent::STATUS_PENDING)
            ->update([
                'process_status' => PaymentEvent::STATUS_FAILED,
                'process_note' => 'Gagal diproses: '.class_basename($exception),
                'processed_at' => now(),
            ]);
    }
}
