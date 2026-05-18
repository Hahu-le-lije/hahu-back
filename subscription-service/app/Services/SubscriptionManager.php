<?php
namespace App\Services;

class SubscriptionManager
{
    /**
     * Returns the available subscription types and their fees.
     */
    protected static $minimumSlots = 1;
    protected static $maximumSlots = 10;
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
        if ($slot < self::$minimumSlots) {
            throw new \InvalidArgumentException("Slot must be at least " . self::$minimumSlots . ".");
        }else if ($slot > self::$maximumSlots) {
            throw new \InvalidArgumentException("Slot cannot exceed " . self::$maximumSlots . ".");
        }

        $fee = self::getTypes()[$tire] ?? null;
        if ($fee) {
            return $fee * $slot;
        }
        return 0;
    }

}