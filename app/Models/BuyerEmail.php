<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuyerEmail extends Model
{
    use HasFactory;

    protected $table = 'buyer_emails';

    protected $fillable = [
        'buyer_code',
        'buyer_name',
        'email',
        'contact_name',
        'email_type',
        'is_primary'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Validation rules
    public static function validationRules($id = null)
    {
        return [
            'buyer_code' => 'required|string|max:50',
            'buyer_name' => 'nullable|string|max:150',
            'email' => 'required|email|max:150',
            'contact_name' => 'nullable|string|max:150',
            'email_type' => 'nullable|in:To,CC,BCC',
            'is_primary' => 'boolean'
        ];
    }

    // Scopes
    public function scopeByBuyerCode($query, $buyerCode)
    {
        return $query->where('buyer_code', $buyerCode);
    }

    public function scopePrimaryOnly($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeCCOnly($query)
    {
        return $query->where('email_type', 'CC');
    }

    // Helper Methods
    public static function getBuyerCodes()
    {
        return self::select('buyer_code', 'buyer_name')
            ->distinct()
            ->orderBy('buyer_code')
            ->get()
            ->unique('buyer_code');
    }

    public static function getEmailsByBuyerCode($buyerCode)
    {
        return self::where('buyer_code', $buyerCode)
            ->orderBy('is_primary', 'desc')
            ->orderBy('email_type')
            ->get();
    }

    // Ensure only one primary email per buyer
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->is_primary) {
                // Set all other emails for this buyer to non-primary
                self::where('buyer_code', $model->buyer_code)
                    ->where('id', '!=', $model->id)
                    ->update(['is_primary' => false]);
            }
        });
    }
}