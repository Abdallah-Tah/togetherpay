<?php

namespace App\Support;

final class PRCode
{
    /**
     * Generate a unique Payment Request Code
     *
     * Format: PR_{cycle_ym}_G{group_id}_M{member_id}_{random}
     * Example: PR_2025-11_G123_M42_a3f9c2
     */
    public static function make(int $groupId, int $memberId, string $cycleYm): string
    {
        $random = substr(bin2hex(random_bytes(3)), 0, 6);

        return sprintf(
            'PR_%s_G%d_M%d_%s',
            $cycleYm,
            $groupId,
            $memberId,
            $random
        );
    }

    /**
     * Parse PR Code back into components
     *
     * @return array{cycle_ym: string, group_id: int, member_id: int, random: string}|null
     */
    public static function parse(string $prCode): ?array
    {
        $pattern = '/^PR_(\d{4}-\d{2})_G(\d+)_M(\d+)_([a-f0-9]{6})$/';

        if (! preg_match($pattern, $prCode, $matches)) {
            return null;
        }

        return [
            'cycle_ym' => $matches[1],
            'group_id' => (int) $matches[2],
            'member_id' => (int) $matches[3],
            'random' => $matches[4],
        ];
    }

    /**
     * Validate PR Code format
     */
    public static function isValid(string $prCode): bool
    {
        return self::parse($prCode) !== null;
    }

    /**
     * Extract group ID from PR Code
     */
    public static function extractGroupId(string $prCode): ?int
    {
        $parsed = self::parse($prCode);

        return $parsed['group_id'] ?? null;
    }

    /**
     * Extract member ID from PR Code
     */
    public static function extractMemberId(string $prCode): ?int
    {
        $parsed = self::parse($prCode);

        return $parsed['member_id'] ?? null;
    }

    /**
     * Extract cycle year-month from PR Code
     */
    public static function extractCycleYm(string $prCode): ?string
    {
        $parsed = self::parse($prCode);

        return $parsed['cycle_ym'] ?? null;
    }
}
