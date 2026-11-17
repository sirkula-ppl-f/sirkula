<?php

namespace App\Http\Controllers;

use App\Services\Payments\MidtransSignature;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Penerima notifikasi Midtrans (webhook). Tidak memakai sesi atau CSRF; keasliannya dijamin
 * tanda tangan. Notifikasi palsu ditolak tanpa mengubah data apa pun.
 */
class MidtransWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentService $payments): JsonResponse
    {
        $payload = $request->json()->all() ?: $request->all();

        foreach (['order_id', 'status_code', 'gross_amount', 'signature_key', 'transaction_status'] as $field) {
            if (! isset($payload[$field]) || ! is_scalar($payload[$field])) {
                return response()->json(['message' => 'Payload tidak lengkap.'], 400);
            }
        }

        if (! MidtransSignature::isValid($payload)) {
            Log::warning('Midtrans: notifikasi dengan tanda tangan tidak valid ditolak', [
                'order_id' => (string) $payload['order_id'],
            ]);

            return response()->json(['message' => 'Tanda tangan tidak valid.'], 403);
        }

        $event = $payments->recordNotification($payload);

        return response()->json(['status' => $event === null ? 'duplicate' : 'accepted']);
    }
}
