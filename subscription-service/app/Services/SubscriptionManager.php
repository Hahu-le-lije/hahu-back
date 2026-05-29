<?php
namespace App\Services;

class SubscriptionManager
{
    /**
     * Returns the available subscription types and their fees.
     */
    protected static $MINIMUM_SLOTS = 1;
    protected static $MAXIMUM_SLOTS = 10;
    private const MULTI_SLOT_DISCOUNT = 0.10;
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
        if ($slot < self::$MINIMUM_SLOTS) {
            throw new \InvalidArgumentException("Slot must be at least " . self::$MINIMUM_SLOTS . ".");
        } else if ($slot > self::$MAXIMUM_SLOTS) {
            throw new \InvalidArgumentException("Slot cannot exceed " . self::$MAXIMUM_SLOTS . ".");
        }

        $fee = self::getTypes()[$tire] ?? null;
        if ($fee) {
            $totalAmount = $fee * $slot;

            // Apply discount if they buy more than 1 slot
            if ($slot > 1) {
                $totalAmount = $totalAmount * (1 - self::MULTI_SLOT_DISCOUNT);
            }

            return (float) $totalAmount;
        }

        throw new \InvalidArgumentException("Invalid subscription type: " . $tire);
    }

}