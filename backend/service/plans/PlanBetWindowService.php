<?php

namespace backend\service\plans;

/** Determines whether a plan may send a real bet to a bookmaker. */
class PlanBetWindowService
{
    public static function isWithin(?string $start, ?string $end, ?int $timestamp = null): bool
    {
        $start = self::normalize($start);
        $end = self::normalize($end);
        if ($start === null && $end === null) {
            return true;
        }
        if ($start === null || $end === null) {
            throw new \InvalidArgumentException('真实投注开始和结束时间必须同时设置');
        }

        $timestamp = $timestamp ?? time();
        $minutes = ((int)date('G', $timestamp) * 60) + (int)date('i', $timestamp);
        $startMinutes = self::toMinutes($start);
        $endMinutes = self::toMinutes($end);

        if ($startMinutes <= $endMinutes) {
            return $minutes >= $startMinutes && $minutes <= $endMinutes;
        }

        return $minutes >= $startMinutes || $minutes <= $endMinutes;
    }

    private static function normalize(?string $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value)) {
            throw new \InvalidArgumentException('真实投注时间必须使用HH:MM格式');
        }

        return $value;
    }

    private static function toMinutes(string $value): int
    {
        return ((int)substr($value, 0, 2) * 60) + (int)substr($value, 3, 2);
    }
}
