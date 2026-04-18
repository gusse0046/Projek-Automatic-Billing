<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class EmailSentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_order',
        'customer_name',
        'billing_document',
        'booking_number',
        'recipient_emails',
        'total_recipients',
        'email_subject',
        'email_message',
        'sent_by',
        'sent_at',
        'documents_sent',
        'required_documents_snapshot',
        'send_status',
        'notes',
        'error_message'
    ];

    protected $casts = [
        'recipient_emails' => 'array',
        'documents_sent' => 'array',
        'required_documents_snapshot' => 'array',
        'sent_at' => 'datetime',
    ];

    /**
     * ✅ Check apakah delivery order sudah pernah ter-send
     */
    public static function hasBeenSent($deliveryOrder, $customerName)
    {
        return self::where('delivery_order', $deliveryOrder)
                   ->where('customer_name', $customerName)
                   ->where('send_status', 'success')
                   ->exists();
    }

    /**
     * ✅ Get log email terakhir yang berhasil
     */
    public static function getLastSuccessfulSend($deliveryOrder, $customerName)
    {
        return self::where('delivery_order', $deliveryOrder)
                   ->where('customer_name', $customerName)
                   ->where('send_status', 'success')
                   ->orderBy('sent_at', 'desc')
                   ->first();
    }

    /**
     * ✅ Log email yang berhasil terkirim
     */
    public static function logSuccessfulSend($data)
    {
        try {
            $log = self::create([
                'delivery_order' => $data['delivery_order'],
                'customer_name' => $data['customer_name'],
                'billing_document' => $data['billing_document'] ?? null,
                'booking_number' => $data['booking_number'] ?? null,
                'recipient_emails' => $data['recipient_emails'] ?? [],
                'total_recipients' => $data['total_recipients'] ?? 0,
                'email_subject' => $data['email_subject'] ?? null,
                'email_message' => $data['email_message'] ?? null,
                'sent_by' => $data['sent_by'],
                'sent_at' => $data['sent_at'] ?? now(),
                'documents_sent' => $data['documents_sent'] ?? [],
                'required_documents_snapshot' => $data['required_documents_snapshot'] ?? [],
                'send_status' => 'success',
                'notes' => $data['notes'] ?? null
            ]);

            Log::info('✅ Email sent log created', [
                'log_id' => $log->id,
                'delivery_order' => $data['delivery_order'],
                'customer_name' => $data['customer_name'],
                'sent_at' => $log->sent_at
            ]);

            return $log;

        } catch (\Exception $e) {
            Log::error('Failed to create email sent log', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            return null;
        }
    }

    /**
     * ✅ Get statistics
     */
    public static function getStatistics($startDate = null, $endDate = null)
    {
        $query = self::where('send_status', 'success');

        if ($startDate) {
            $query->where('sent_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('sent_at', '<=', $endDate);
        }

        return [
            'total_sent' => $query->count(),
            'unique_deliveries' => $query->distinct('delivery_order')->count('delivery_order'),
            'unique_customers' => $query->distinct('customer_name')->count('customer_name'),
            'total_recipients' => $query->sum('total_recipients')
        ];
    }
}