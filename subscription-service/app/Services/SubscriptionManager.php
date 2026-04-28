<?php
namespace App\Services;

class SubscriptionManager
{
    /**
     * Returns the available subscription types and their fees.
     */
    public static function getTypes(): array
    {
        return [
            'basic' => config('subscriptiontype.basic.fee'),
            'premium' => config('subscriptiontype.premium.fee'),
            'ultimate' => config('subscriptiontype.ultimate.fee'),
        ];
    }
    public static function getDuration(): array
    {
        return [
            'basic' => config('subscriptiontype.basic.duration'),
            'premium' => config('subscriptiontype.premium.duration'),
            'ultimate' => config('subscriptiontype.ultimate.duration'),
        ];
    }
    public static function calculatePlanAmount(string $tire, int $slot): float
    {
        $fee = self::getTypes()[$tire] ?? null;
        if ($fee) {
            return $fee * $slot;
        }
        return 0;
    }

}